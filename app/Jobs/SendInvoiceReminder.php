<?php

namespace App\Jobs;

use App\Modules\Invoice\Models\Invoice;
use App\Traits\ConfiguresCompanySmtp;
use App\Traits\LogsEmails;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendInvoiceReminder implements ShouldQueue
{
    use ConfiguresCompanySmtp;
    use LogsEmails;
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly string $invoiceId,
        public readonly int $level,
        public readonly float $fee,
    ) {}

    public function handle(): void
    {
        $invoice = Invoice::with(['items', 'customer', 'company', 'user'])->find($this->invoiceId);

        if (! $invoice || ! $invoice->company || ! $invoice->customer?->email) {
            Log::warning('SendInvoiceReminder skipped: invoice, company, or recipient missing', [
                'invoice_id' => $this->invoiceId,
            ]);

            return;
        }

        $company = $invoice->company;
        $this->configureCompanySmtp($company);

        $level = $this->level;
        $fee = $this->fee;

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

        $pdf = Pdf::loadView('pdf.invoice', [
            'layout' => $this->defaultLayout(),
            'invoice' => $invoice,
            'company' => $company,
            'customer' => $invoice->customer,
        ])->setPaper('a4')->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'enable-local-file-access' => false,
            'isPhpEnabled' => false,
        ]);

        $inkassoFee = $level == Invoice::REMINDER_INKASSO ? 50.00 : 0;
        $delayInterest = $level == Invoice::REMINDER_INKASSO
            ? $invoice->total * 0.09 * ($invoice->getDaysOverdue() / 365)
            : 0;

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
            $message->attachData($pdf->output(), "Rechnung_{$invoice->number}.pdf", [
                'mime' => 'application/pdf',
            ]);
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

        $invoice->addReminderToHistory($level, $fee);
        if ($level > Invoice::REMINDER_FRIENDLY) {
            $invoice->status = 'overdue';
        }
        $invoice->save();
    }

    private function defaultLayout(): object
    {
        return (object) [
            'template' => 'minimal',
            'settings' => [
                'colors' => [
                    'primary' => '#3B82F6',
                    'secondary' => '#64748B',
                    'accent' => '#F59E0B',
                ],
                'fonts' => [
                    'heading' => 'DejaVu Sans',
                    'body' => 'DejaVu Sans',
                    'size' => 'medium',
                ],
                'layout' => [
                    'margin_top' => 20,
                    'margin_right' => 20,
                    'margin_bottom' => 20,
                    'margin_left' => 20,
                    'header_height' => 120,
                    'footer_height' => 80,
                ],
                'branding' => [
                    'show_logo' => true,
                    'logo_position' => 'top-right',
                    'company_info_position' => 'top-left',
                    'show_header_line' => true,
                    'show_footer_line' => true,
                    'show_footer' => true,
                ],
                'content' => [
                    'show_company_address' => true,
                    'show_company_contact' => true,
                    'show_customer_number' => true,
                    'show_tax_number' => true,
                    'show_unit_column' => true,
                    'show_notes' => true,
                    'show_bank_details' => true,
                    'show_company_registration' => true,
                    'show_payment_terms' => true,
                    'show_item_images' => false,
                    'show_item_codes' => false,
                    'show_tax_breakdown' => false,
                ],
            ],
        ];
    }
}
