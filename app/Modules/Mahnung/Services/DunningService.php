<?php

namespace App\Modules\Mahnung\Services;

use App\Models\EmailLog;
use App\Modules\Company\Models\Company;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Models\InvoiceAuditLog;
use App\Modules\Invoice\Models\InvoiceLayout;
use App\Services\FormattingService;
use App\Services\GirocodeService;
use App\Services\SettingsService;
use App\Traits\ConfiguresCompanySmtp;
use App\Traits\LogsEmails;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DunningService
{
    use ConfiguresCompanySmtp;
    use LogsEmails;

    /** @var array<string, array> settings() issues 11 queries — cache per company */
    private array $settingsCache = [];

    public function settings(Company $company): array
    {
        return $this->settingsCache[$company->id] ??= [
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

    public function flushSettingsCache(): void
    {
        $this->settingsCache = [];
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
        return $invoice->canSendNextReminder() && ! $invoice->isFullyPaid();
    }

    public function pause(Invoice $invoice, CarbonInterface $until): void
    {
        $invoice->update(['dunning_paused_until' => $until->toDateString()]);

        InvoiceAuditLog::log(
            $invoice->id,
            'dunning_paused',
            $invoice->status,
            $invoice->status,
            ['dunning_paused_until' => $until->toDateString()],
            'Mahnlauf pausiert bis '.$until->format('d.m.Y')
        );
    }

    public function resume(Invoice $invoice): void
    {
        $previous = $invoice->dunning_paused_until?->toDateString();
        $invoice->update(['dunning_paused_until' => null]);

        InvoiceAuditLog::log(
            $invoice->id,
            'dunning_resumed',
            $invoice->status,
            $invoice->status,
            ['dunning_paused_until' => ['old' => $previous, 'new' => null]],
            'Mahnlauf fortgesetzt'
        );
    }

    public function isPaused(Invoice $invoice): bool
    {
        return $invoice->dunning_paused_until !== null
            && $invoice->dunning_paused_until->endOfDay()->isFuture();
    }

    public function openBalance(Invoice $invoice): float
    {
        return $invoice->getRemainingBalance();
    }

    /**
     * @return array{level: int, fee: float}|null
     */
    public function nextDueLevel(Invoice $invoice, Company $company): ?array
    {
        // Settled invoices are never due — payments beat a stale status column.
        if ($invoice->isFullyPaid()) {
            return null;
        }

        // Mahnsperre: automatic escalation is suspended while a pause date is
        // set ("Kunde zahlt Freitag"). Manual sends remain possible.
        if ($this->isPaused($invoice)) {
            return null;
        }

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

        // Never dun a settled invoice: payments are the source of truth, even
        // when the status column has not caught up yet.
        if ($invoice->isFullyPaid()) {
            return ['ok' => false, 'error' => 'Rechnung ist bereits vollständig bezahlt.'];
        }

        if (! $invoice->canSendNextReminder()) {
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

            // Failed dunning mails are legally relevant (Zugang der Mahnung) —
            // record them so the workspace can surface and retry them.
            $this->logEmail(
                companyId: $company->id,
                recipientEmail: $invoice->customer->email,
                subject: $invoice->getReminderLevelNameForLevel($level).' - Rechnung '.$invoice->number,
                type: 'mahnung',
                customerId: $invoice->customer_id,
                recipientName: $invoice->customer->name,
                relatedType: 'Invoice',
                relatedId: $invoice->id,
                metadata: ['reminder_level' => $level],
                status: 'failed',
                errorMessage: $e->getMessage(),
            );

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
    public function invoicesDueForEscalation(Company $company, bool $requireAutoSend = true): Collection
    {
        if ($requireAutoSend && ! $this->settings($company)['reminder_auto_send']) {
            return collect();
        }

        if (! $company->smtp_host || ! $company->smtp_username) {
            return collect();
        }

        return $this->openDunningQuery($company->id)
            ->get()
            ->filter(fn (Invoice $invoice) => $invoice->customer?->email
                && $this->nextDueLevel($invoice, $company) !== null)
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

        // Must count exactly what the bulk "Alle jetzt versenden" action would
        // queue — same filters (customer email, SMTP), auto-send toggle ignored.
        return $this->invoicesDueForEscalation($company, requireAutoSend: false)->count();
    }

    public function openDunningQuery(?string $companyId)
    {
        return Invoice::forCompany($companyId)
            ->whereIn('status', ['sent', 'overdue'])
            ->where('reminder_level', '<', Invoice::REMINDER_INKASSO)
            ->whereDate('due_date', '<', now()->toDateString())
            ->unsettled()
            ->withSum(['payments as completed_payments_sum' => fn ($q) => $q->where('status', 'completed')], 'amount')
            ->with(['customer:id,name,email', 'company']);
    }

    public function workspaceQuery(?string $companyId)
    {
        return Invoice::forCompany($companyId)
            ->where(function ($q) {
                $q->whereIn('status', ['sent', 'overdue'])
                    ->orWhere('reminder_level', '>', Invoice::REMINDER_NONE);
            })
            ->whereNotIn('status', ['draft', 'cancelled', 'paid'])
            ->unsettled()
            ->withSum(['payments as completed_payments_sum' => fn ($q) => $q->where('status', 'completed')], 'amount')
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
            'dunning_paused_until' => $invoice->dunning_paused_until?->toDateString(),
            'is_paused' => $this->isPaused($invoice),
            'last_reminder_sent_at' => $invoice->last_reminder_sent_at,
            'reminder_fee' => $invoice->reminder_fee,
            'reminder_history' => $invoice->reminder_history ?? [],
            'days_overdue' => $invoice->getDaysOverdue(),
            'can_send_next' => $canSend = $this->canSendManually($invoice),
            'next_level' => $canSend ? $invoice->getNextReminderLevel() : null,
            'next_level_name' => $canSend
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

        // Dun the open balance, not the invoice total: partial payments are
        // deducted, and Verzugszinsen accrue on the open amount only.
        $paidAmount = $invoice->getPaidAmount();
        $openAmount = $invoice->getRemainingBalance();

        // Fees are folded into the total as items; after a partial payment the
        // open amount can be smaller than the accumulated fees, so the
        // "davon Mahngebühren" line must never exceed what is actually open.
        $feesIncluded = min((float) $invoice->reminder_fee, $openAmount);

        $interestRate = $settings['reminder_interest_rate'] / 100;
        $delayInterest = $level === Invoice::REMINDER_INKASSO
            ? $openAmount * $interestRate * ($invoice->getDaysOverdue() / 365)
            : 0;

        // Girocode (EPC-QR) over the Gesamtbetrag of this Mahnstufe — scan to
        // pre-fill recipient, amount and reference in any banking app.
        $qrAmount = $openAmount + $fee + $delayInterest + $inkassoFee;
        $girocodePng = app(GirocodeService::class)->png(
            (string) ($company->bank_account_holder ?: $company->name),
            (string) $company->bank_iban,
            round($qrAmount, 2),
            'Rechnung '.$invoice->number,
        );

        $pdf = app()->runningUnitTests() ? null : $this->renderInvoicePdf($invoice);
        $replyTo = $company->smtp_reply_to ?: null;

        Mail::send($template, [
            'invoice' => $invoice,
            'company' => $company,
            'fee' => $fee,
            'inkassoFee' => $inkassoFee,
            'delayInterest' => $delayInterest,
            'openAmount' => $openAmount,
            'paidAmount' => $paidAmount,
            'feesIncluded' => $feesIncluded,
            'girocodePng' => $girocodePng,
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
                'open_amount' => round($openAmount, 2),
                'paid_amount' => round($paidAmount, 2),
                'reminder_fee' => $fee,
                'days_overdue' => $invoice->getDaysOverdue(),
                'has_pdf_attachment' => true,
            ]
        );
    }

    /**
     * Inkasso-Übergabedossier: ein ZIP mit Rechnungs-PDF, Mahnhistorie,
     * E-Mail-Protokoll und Forderungsübersicht für das Inkassobüro.
     * Returns the path of a temp file; the caller deletes it after sending.
     */
    public function buildDossier(Invoice $invoice): string
    {
        $invoice->loadMissing(['customer', 'company', 'items.product', 'user', 'layout']);

        $path = tempnam(sys_get_temp_dir(), 'dossier');
        $zip = new \ZipArchive;
        if ($zip->open($path, \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Inkasso-Dossier konnte nicht erstellt werden (ZIP-Fehler).');
        }

        if (! app()->runningUnitTests()) {
            $zip->addFromString('Rechnung_'.$invoice->number.'.pdf', $this->renderInvoicePdf($invoice)->output());
        }

        $bom = "\xEF\xBB\xBF";

        $rows = [$this->csvLine(['Stufe', 'Bezeichnung', 'Versendet am', 'Tage ueberfaellig', 'Gebuehr (EUR)'])];
        foreach ($invoice->reminder_history ?? [] as $entry) {
            $rows[] = $this->csvLine([
                $entry['level'] ?? '',
                $entry['level_name'] ?? '',
                $entry['sent_at'] ?? '',
                $entry['days_overdue'] ?? '',
                number_format((float) ($entry['fee'] ?? 0), 2, ',', ''),
            ]);
        }
        $zip->addFromString('Mahnhistorie.csv', $bom.implode("\r\n", $rows));

        $logs = EmailLog::forCompany($invoice->company_id)
            ->where('related_type', 'Invoice')
            ->where('related_id', $invoice->id)
            ->orderBy('created_at')
            ->get();
        $rows = [$this->csvLine(['Datum', 'Empfaenger', 'Betreff', 'Typ', 'Status'])];
        foreach ($logs as $log) {
            $rows[] = $this->csvLine([
                optional($log->sent_at ?? $log->created_at)->format('d.m.Y H:i'),
                $log->recipient_email,
                (string) $log->subject,
                $log->type,
                $log->status,
            ]);
        }
        $zip->addFromString('E-Mail-Protokoll.csv', $bom.implode("\r\n", $rows));

        $paid = $invoice->getPaidAmount();
        $open = $invoice->getRemainingBalance();
        $customer = $invoice->customer;
        $zip->addFromString('Forderungsuebersicht.txt', implode("\n", [
            'FORDERUNGSÜBERGABE — Rechnung '.$invoice->number,
            str_repeat('=', 50),
            '',
            'Gläubiger:      '.$invoice->company?->name,
            '',
            'Schuldner:      '.($customer?->name ?? '—'),
            'E-Mail:         '.($customer?->email ?? '—'),
            'Adresse:        '.trim(($customer?->address ?? '').', '.($customer?->postal_code ?? '').' '.($customer?->city ?? ''), ', '),
            '',
            'Rechnungsdatum: '.$invoice->issue_date?->format('d.m.Y'),
            'Fällig seit:    '.$invoice->due_date?->format('d.m.Y').' ('.$invoice->getDaysOverdue().' Tage überfällig)',
            'Rechnungsbetrag: '.number_format((float) $invoice->total, 2, ',', '.').' EUR (inkl. Mahngebühren '.number_format((float) $invoice->reminder_fee, 2, ',', '.').' EUR)',
            'Bereits gezahlt: '.number_format($paid, 2, ',', '.').' EUR',
            'Offene Forderung: '.number_format($open, 2, ',', '.').' EUR',
            'Mahnstufe:      '.$invoice->reminder_level_name,
            '',
            'Erstellt am '.now()->format('d.m.Y H:i').' mit AndoBill.',
        ]));

        $zip->close();

        return $path;
    }

    /**
     * RFC-4180-style CSV line with ';' separator: fields are quoted, quotes
     * doubled, so semicolons/newlines in subjects or names cannot shift columns.
     */
    private function csvLine(array $fields): string
    {
        return implode(';', array_map(
            fn ($field) => '"'.str_replace('"', '""', (string) $field).'"',
            $fields
        ));
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
