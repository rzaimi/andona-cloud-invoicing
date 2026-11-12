<?php

namespace App\Services;

use App\Modules\Customer\Services\CustomerRecipientResolver;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Models\InvoiceAuditLog;
use App\Modules\Invoice\Models\InvoiceLayout;
use App\Traits\LogsEmails;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Shared mailer for invoice dispatch. Used by both `InvoiceController::send`
 * and the recurring-invoice generator so the SMTP config + PDF attachment +
 * status transition + audit log happen in exactly one place.
 */
class InvoiceMailer
{
    use LogsEmails;

    /**
     * Send an invoice by email.
     *
     * @param  Invoice  $invoice  fully-loaded invoice model
     * @param  string  $to  recipient email
     * @param  string|null  $subject  overrides default subject
     * @param  string|null  $customMessage  optional plain-text body to embed
     * @param  array|string|null  $cc  optional CC address(es)
     * @param  string|null  $recipientName  name of the addressed contact, logged instead of the customer name
     * @return array{ok: bool, error?: string, exception?: \Throwable}
     */
    public function send(
        Invoice $invoice,
        string $to,
        ?string $subject = null,
        ?string $customMessage = null,
        array|string|null $cc = null,
        ?string $recipientName = null
    ): array {
        $invoice->loadMissing(['customer', 'company', 'items.product', 'layout', 'user', 'correctsInvoice']);

        $cc = CustomerRecipientResolver::normalizeCc($cc);

        $company = $invoice->company;

        if (! $company || ! $company->smtp_host || ! $company->smtp_username) {
            return [
                'ok' => false,
                'error' => 'SMTP-Einstellungen sind nicht konfiguriert.',
            ];
        }

        $replyTo = $company->smtp_reply_to ?: null;

        $subject ??= "Rechnung {$invoice->number}";

        try {
            $this->configureSmtp($company);

            [$pdfContent, $xrechnungXml, $eInvoiceKind] = $this->buildAttachments($invoice, $company);

            Mail::send('emails.invoice-sent', [
                'invoice' => $invoice,
                'company' => $company,
                'customMessage' => $customMessage,
            ], function ($message) use ($to, $cc, $subject, $invoice, $pdfContent, $xrechnungXml, $replyTo) {
                $message->to($to);
                if (! empty($cc)) {
                    $message->cc($cc);
                }
                if ($replyTo) {
                    $message->replyTo($replyTo);
                }
                $message->subject($subject);
                $message->attachData($pdfContent, "Rechnung_{$invoice->number}.pdf", [
                    'mime' => 'application/pdf',
                ]);
                if ($xrechnungXml !== null) {
                    $message->attachData($xrechnungXml, "XRechnung_{$invoice->number}.xml", [
                        'mime' => 'application/xml',
                    ]);
                }
            });

            $this->logEmail(
                companyId: $invoice->company_id,
                recipientEmail: $to,
                subject: $subject,
                type: 'invoice',
                customerId: $invoice->customer_id,
                recipientName: $recipientName ?: $invoice->customer?->name,
                body: $customMessage,
                relatedType: 'Invoice',
                relatedId: $invoice->id,
                metadata: [
                    'cc' => $cc,
                    'invoice_number' => $invoice->number,
                    'invoice_total' => $invoice->total,
                    'has_pdf_attachment' => true,
                    'e_invoice' => $eInvoiceKind,
                ]
            );

            if (in_array($invoice->status, ['draft', 'queued'])) {
                $oldStatus = $invoice->status;
                $invoice->freezeCompanySnapshot();
                $invoice->update(['status' => 'sent']);

                InvoiceAuditLog::log(
                    $invoice->id,
                    'sent',
                    $oldStatus,
                    'sent',
                    ['email' => $to],
                    'Rechnung per E-Mail versendet an '.$to
                );
            } else {
                InvoiceAuditLog::log(
                    $invoice->id,
                    'resent',
                    $invoice->status,
                    $invoice->status,
                    ['email' => $to],
                    'Rechnung erneut per E-Mail versendet an '.$to
                );
            }

            return ['ok' => true];
        } catch (\Throwable $e) {
            $error = sprintf(
                '%s (%s at %s:%d)',
                $e->getMessage(),
                $e::class,
                $e->getFile(),
                $e->getLine(),
            );

            Log::error('Failed to send invoice email: '.$error, [
                'invoice_id' => $invoice->id,
                'exception' => $e,
            ]);

            return [
                'ok' => false,
                'error' => $error,
                'exception' => $e,
            ];
        }
    }

    /**
     * Build the mail attachments honoring the company's E-Rechnung settings:
     * with ZUGFeRD enabled the attached PDF *is* the e-invoice (embedded XML),
     * with XRechnung enabled the XML is attached alongside. Generation
     * failures fall back to the plain PDF so invoice dispatch never breaks.
     *
     * @return array{0: string, 1: string|null, 2: string|null} [pdf, xml, kind]
     */
    private function buildAttachments(Invoice $invoice, $company): array
    {
        $flag = fn (string $key) => filter_var($company->getSetting($key, false), FILTER_VALIDATE_BOOLEAN);

        $erechnungEnabled = $flag('erechnung_enabled');
        $pdfContent = null;
        $xrechnungXml = null;
        $kind = null;

        if ($erechnungEnabled && $flag('zugferd_enabled')) {
            try {
                $pdfContent = app(ERechnungService::class)->generateZugferd($invoice);
                $kind = 'zugferd';
            } catch (\Throwable $e) {
                Log::warning('ZUGFeRD generation failed, sending plain PDF instead.', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($erechnungEnabled && $flag('xrechnung_enabled')) {
            try {
                $xrechnungXml = app(ERechnungService::class)->generateXRechnung($invoice);
                $kind = $kind === 'zugferd' ? 'zugferd+xrechnung' : 'xrechnung';
            } catch (\Throwable $e) {
                Log::warning('XRechnung generation failed, sending without XML attachment.', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $pdfContent ??= $this->renderInvoicePdf($invoice)->output();

        return [$pdfContent, $xrechnungXml, $kind];
    }

    /**
     * Apply the tenant-specific SMTP settings for the duration of the request.
     * The same dynamic-config pattern used by `InvoiceController::send` before
     * this was extracted.
     */
    private function configureSmtp($company): void
    {
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', $company->smtp_host);
        Config::set('mail.mailers.smtp.port', $company->smtp_port);
        Config::set('mail.mailers.smtp.username', $company->smtp_username);
        Config::set('mail.mailers.smtp.password', $company->smtp_password);
        Config::set('mail.mailers.smtp.encryption', $company->smtp_encryption ?: 'tls');
        Config::set('mail.from.address', $company->smtp_from_address ?: $company->email);
        Config::set('mail.from.name', $company->smtp_from_name ?: $company->name);
    }

    /**
     * Render the invoice PDF using DomPDF with the same hardened options the
     * controller uses.
     */
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

        $settingsService = app(SettingsService::class);
        $settings = $settingsService->getAll($invoice->company_id);
        $formattingService = app(FormattingService::class);

        return Pdf::loadView('pdf.invoice', [
            'layout' => $layout,
            'invoice' => $invoice,
            'company' => $invoice->company,
            'customer' => $invoice->customer,
            'settings' => $settings,
            'formattingService' => $formattingService,
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
