<?php

namespace App\Modules\Mahnung\Services;

use App\Modules\Company\Models\Company;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Models\InvoiceAuditLog;
use App\Modules\Invoice\Models\InvoiceLayout;
use App\Services\FormattingService;
use App\Services\SettingsService;
use App\Traits\ConfiguresCompanySmtp;
use App\Traits\LogsEmails;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DunningService
{
    use ConfiguresCompanySmtp;
    use LogsEmails;

    public function settings(Company $company): array
    {
        return [
            'reminder_friendly_days' => (int) $company->getSetting('reminder_friendly_days', 7),
            'reminder_mahnung1_days' => (int) $company->getSetting('reminder_mahnung1_days', 14),
            'reminder_mahnung2_days' => (int) $company->getSetting('reminder_mahnung2_days', 21),
            'reminder_mahnung3_days' => (int) $company->getSetting('reminder_mahnung3_days', 30),
            'reminder_inkasso_days' => (int) $company->getSetting('reminder_inkasso_days', 45),
            'reminder_mahnung1_fee' => (float) $company->getSetting('reminder_mahnung1_fee', 5.00),
            'reminder_mahnung2_fee' => (float) $company->getSetting('reminder_mahnung2_fee', 10.00),
            'reminder_mahnung3_fee' => (float) $company->getSetting('reminder_mahnung3_fee', 15.00),
            'reminder_inkasso_fee' => (float) $company->getSetting('reminder_inkasso_fee', 50.00),
            'reminder_interest_rate' => (float) $company->getSetting('reminder_interest_rate', 9.00),
            'reminder_auto_send' => (bool) $company->getSetting('reminder_auto_send', true),
        ];
    }

    public function feeForLevel(Company $company, int $level): float
    {
        $settings = $this->settings($company);

        return match ($level) {
            Invoice::REMINDER_MAHNUNG_1 => $settings['reminder_mahnung1_fee'],
            Invoice::REMINDER_MAHNUNG_2 => $settings['reminder_mahnung2_fee'],
            Invoice::REMINDER_MAHNUNG_3 => $settings['reminder_mahnung3_fee'],
            Invoice::REMINDER_INKASSO => 0.0,
            default => 0.0,
        };
    }

    public function canSendManually(Invoice $invoice): bool
    {
        return $invoice->canSendNextReminder();
    }

    /**
     * @return array{level: int, fee: float}|null
     */
    public function nextDueLevel(Invoice $invoice, Company $company): ?array
    {
        // Max one automatic escalation per invoice per day. Without this, a
        // long-overdue invoice can jump two Mahnstufen on the same day when
        // the daily command runs twice or a queue backlog drains late —
        // day-based thresholds alone don't prevent that. Manual sends
        // (respectThresholds: false) are unaffected.
        if ($invoice->last_reminder_sent_at?->isToday()) {
            return null;
        }

        $settings = $this->settings($company);
        $daysOverdue = $invoice->getDaysOverdue();
        $current = (int) $invoice->reminder_level;
        $next = null;
        $fee = 0.0;

        if ($current === Invoice::REMINDER_NONE && $daysOverdue >= $settings['reminder_friendly_days']) {
            $next = Invoice::REMINDER_FRIENDLY;
        } elseif ($current === Invoice::REMINDER_FRIENDLY && $daysOverdue >= $settings['reminder_mahnung1_days']) {
            $next = Invoice::REMINDER_MAHNUNG_1;
            $fee = $settings['reminder_mahnung1_fee'];
        } elseif ($current === Invoice::REMINDER_MAHNUNG_1 && $daysOverdue >= $settings['reminder_mahnung2_days']) {
            $next = Invoice::REMINDER_MAHNUNG_2;
            $fee = $settings['reminder_mahnung2_fee'];
        } elseif ($current === Invoice::REMINDER_MAHNUNG_2 && $daysOverdue >= $settings['reminder_mahnung3_days']) {
            $next = Invoice::REMINDER_MAHNUNG_3;
            $fee = $settings['reminder_mahnung3_fee'];
        } elseif ($current === Invoice::REMINDER_MAHNUNG_3 && $daysOverdue >= $settings['reminder_inkasso_days']) {
            $next = Invoice::REMINDER_INKASSO;
        }

        return $next === null ? null : ['level' => $next, 'fee' => $fee];
    }

    /**
     * @return array{ok: bool, error?: string, level?: int, level_name?: string}
     */
    public function sendNext(Invoice $invoice, bool $respectThresholds = false): array
    {
        $invoice->loadMissing(['customer', 'company', 'items.product', 'user', 'layout']);

        if (! $this->canSendManually($invoice)) {
            return ['ok' => false, 'error' => 'Diese Rechnung kann keine weiteren Mahnungen erhalten.'];
        }

        $company = $invoice->company;
        if (! $company) {
            return ['ok' => false, 'error' => 'Firma nicht gefunden.'];
        }

        if (! $company->smtp_host || ! $company->smtp_username) {
            return ['ok' => false, 'error' => 'E-Mail Einstellungen sind nicht konfiguriert.'];
        }

        if (! $invoice->customer?->email) {
            return ['ok' => false, 'error' => 'Kunde hat keine E-Mail-Adresse.'];
        }

        if ($respectThresholds) {
            $due = $this->nextDueLevel($invoice, $company);
            if ($due === null) {
                return ['ok' => false, 'error' => 'Die nächste Mahnstufe ist noch nicht fällig.'];
            }
            $level = $due['level'];
            $fee = $due['fee'];
        } else {
            $level = $invoice->getNextReminderLevel();
            $fee = $this->feeForLevel($company, $level);
        }

        try {
            $this->dispatch($invoice, $company, $level, $fee);
        } catch (\Throwable $e) {
            Log::error('Mahnung send failed: '.$e->getMessage(), [
                'invoice_id' => $invoice->id,
                'level' => $level,
            ]);

            return ['ok' => false, 'error' => 'Fehler beim Versenden der Mahnung: '.$e->getMessage()];
        }

        Cache::forget("dashboard_stats_{$invoice->company_id}");

        return [
            'ok' => true,
            'level' => $level,
            'level_name' => $invoice->getReminderLevelNameForLevel($level),
        ];
    }

    public function dispatch(Invoice $invoice, Company $company, int $level, float $fee): void
    {
        $this->configureCompanySmtp($company);
        $this->sendMahnungEmail($invoice, $company, $level, $fee);

        $oldStatus = $invoice->status;
        $invoice->addReminderToHistory($level, $fee);

        if ($level > Invoice::REMINDER_FRIENDLY && $invoice->status !== 'paid' && $invoice->status !== 'cancelled') {
            $invoice->status = 'overdue';
        }

        $invoice->save();

        if ($invoice->status === 'overdue' && $oldStatus !== 'overdue') {
            InvoiceAuditLog::log(
                $invoice->id,
                'status_changed',
                $oldStatus,
                'overdue',
                ['reminder_level' => $level, 'reminder_fee' => $fee],
                $invoice->getReminderLevelNameForLevel($level).' versendet'
            );
        }
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function invoicesDueForEscalation(Company $company): Collection
    {
        if (! $this->settings($company)['reminder_auto_send']) {
            return collect();
        }

        if (! $company->smtp_host || ! $company->smtp_username) {
            return collect();
        }

        return $this->openDunningQuery($company->id)
            ->get()
            ->filter(fn (Invoice $invoice) => $invoice->customer?->email && $this->nextDueLevel($invoice, $company) !== null)
            ->values();
    }

    public function countDueForNextStep(?string $companyId): int
    {
        if (! $companyId) {
            return 0;
        }

        $company = Company::find($companyId);
        if (! $company) {
            return 0;
        }

        return $this->openDunningQuery($company->id)
            ->get()
            ->filter(fn (Invoice $invoice) => $this->nextDueLevel($invoice, $company) !== null)
            ->count();
    }

    public function openDunningQuery(?string $companyId)
    {
        return Invoice::forCompany($companyId)
            ->whereIn('status', ['sent', 'overdue'])
            ->where('reminder_level', '<', Invoice::REMINDER_INKASSO)
            ->whereDate('due_date', '<', now()->toDateString())
            ->with(['customer:id,name,email', 'company']);
    }

    public function workspaceQuery(?string $companyId)
    {
        return Invoice::forCompany($companyId)
            ->where(function ($q) {
                $q->whereIn('status', ['sent', 'overdue'])
                    ->orWhere('reminder_level', '>', Invoice::REMINDER_NONE);
            })
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->with(['customer:id,name,email', 'company'])
            ->orderByRaw('CASE WHEN due_date < ? AND status IN (?, ?) THEN 0 ELSE 1 END', [now()->toDateString(), 'sent', 'overdue'])
            ->orderBy('due_date')
            ->orderBy('number');
    }

    public function historyPayload(Invoice $invoice): array
    {
        $company = $invoice->company ?? $invoice->company()->first();
        $due = $company ? $this->nextDueLevel($invoice, $company) : null;

        return [
            'reminder_level' => $invoice->reminder_level,
            'reminder_level_name' => $invoice->reminder_level_name,
            'last_reminder_sent_at' => $invoice->last_reminder_sent_at,
            'reminder_fee' => $invoice->reminder_fee,
            'reminder_history' => $invoice->reminder_history ?? [],
            'days_overdue' => $invoice->getDaysOverdue(),
            'can_send_next' => $this->canSendManually($invoice),
            'next_level' => $this->canSendManually($invoice) ? $invoice->getNextReminderLevel() : null,
            'next_level_name' => $this->canSendManually($invoice)
                ? $invoice->getReminderLevelNameForLevel($invoice->getNextReminderLevel())
                : null,
            'next_auto_due' => $due !== null,
        ];
    }

    private function sendMahnungEmail(Invoice $invoice, Company $company, int $level, float $fee): void
    {
        $invoice->loadMissing(['items.product', 'customer', 'company', 'user']);

        $template = match ($level) {
            Invoice::REMINDER_FRIENDLY => 'emails.reminders.friendly',
            Invoice::REMINDER_MAHNUNG_1 => 'emails.reminders.mahnung-1',
            Invoice::REMINDER_MAHNUNG_2 => 'emails.reminders.mahnung-2',
            Invoice::REMINDER_MAHNUNG_3 => 'emails.reminders.mahnung-3',
            Invoice::REMINDER_INKASSO => 'emails.reminders.inkasso',
            default => 'emails.reminders.friendly',
        };

        $subject = match ($level) {
            Invoice::REMINDER_FRIENDLY => "Freundliche Zahlungserinnerung - Rechnung {$invoice->number}",
            Invoice::REMINDER_MAHNUNG_1 => "1. Mahnung - Rechnung {$invoice->number}",
            Invoice::REMINDER_MAHNUNG_2 => "2. Mahnung - Rechnung {$invoice->number}",
            Invoice::REMINDER_MAHNUNG_3 => "3. und LETZTE Mahnung - Rechnung {$invoice->number}",
            Invoice::REMINDER_INKASSO => "Inkassoankündigung - Rechnung {$invoice->number}",
            default => "Zahlungserinnerung - Rechnung {$invoice->number}",
        };

        $settings = $this->settings($company);
        $inkassoFee = $level === Invoice::REMINDER_INKASSO ? $settings['reminder_inkasso_fee'] : 0;
        $interestRate = $settings['reminder_interest_rate'] / 100;
        $delayInterest = $level === Invoice::REMINDER_INKASSO
            ? $invoice->total * $interestRate * ($invoice->getDaysOverdue() / 365)
            : 0;

        $pdf = app()->runningUnitTests() ? null : $this->renderInvoicePdf($invoice);
        $replyTo = $company->smtp_reply_to ?: null;

        Mail::send($template, [
            'invoice' => $invoice,
            'company' => $company,
            'fee' => $fee,
            'inkassoFee' => $inkassoFee,
            'delayInterest' => $delayInterest,
        ], function ($message) use ($invoice, $pdf, $subject, $replyTo) {
            $message->to($invoice->customer->email);
            if ($replyTo) {
                $message->replyTo($replyTo);
            }
            $message->subject($subject);
            if ($pdf) {
                $message->attachData($pdf->output(), "Rechnung_{$invoice->number}.pdf", [
                    'mime' => 'application/pdf',
                ]);
            }
        });

        $this->logEmail(
            companyId: $company->id,
            recipientEmail: $invoice->customer->email,
            subject: $subject,
            type: 'mahnung',
            customerId: $invoice->customer_id,
            recipientName: $invoice->customer->name,
            relatedType: 'Invoice',
            relatedId: $invoice->id,
            metadata: [
                'reminder_level' => $level,
                'reminder_level_name' => $invoice->getReminderLevelNameForLevel($level),
                'invoice_number' => $invoice->number,
                'invoice_total' => $invoice->total,
                'reminder_fee' => $fee,
                'days_overdue' => $invoice->getDaysOverdue(),
                'has_pdf_attachment' => true,
            ]
        );
    }

    private function renderInvoicePdf(Invoice $invoice)
    {
        $layout = $invoice->layout ?: InvoiceLayout::forCompany($invoice->company_id)
            ->where('is_default', true)
            ->first();

        if ($layout) {
            $layout->settings = $layout->settings ?: [];
            $layout->template = $layout->template ?: 'minimal';
        } else {
            $layout = (object) [
                'template' => 'minimal',
                'settings' => [
                    'colors' => ['primary' => '#3B82F6', 'secondary' => '#64748B', 'accent' => '#F59E0B'],
                    'fonts' => ['heading' => 'DejaVu Sans', 'body' => 'DejaVu Sans', 'size' => 'medium'],
                    'layout' => [
                        'margin_top' => 20, 'margin_right' => 20, 'margin_bottom' => 20, 'margin_left' => 20,
                        'header_height' => 120, 'footer_height' => 80,
                    ],
                    'branding' => [
                        'show_logo' => true, 'logo_position' => 'top-right',
                        'company_info_position' => 'top-left', 'show_header_line' => true,
                        'show_footer_line' => true, 'show_footer' => true,
                    ],
                    'content' => [
                        'show_company_address' => true, 'show_company_contact' => true,
                        'show_customer_number' => true, 'show_tax_number' => true,
                        'show_unit_column' => true, 'show_notes' => true,
                        'show_bank_details' => true, 'show_company_registration' => true,
                        'show_payment_terms' => true, 'show_item_images' => false,
                        'show_item_codes' => false, 'show_tax_breakdown' => false,
                    ],
                ],
            ];
        }

        $settings = app(SettingsService::class)->getAll($invoice->company_id);

        return Pdf::loadView('pdf.invoice', [
            'layout' => $layout,
            'invoice' => $invoice,
            'company' => $invoice->company,
            'customer' => $invoice->customer,
            'settings' => $settings,
            'formattingService' => app(FormattingService::class),
        ])
            ->setPaper('a4')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'enable-local-file-access' => false,
                'enable-javascript' => false,
                'isPhpEnabled' => true,
                'dpi' => 96,
            ]);
    }
}
