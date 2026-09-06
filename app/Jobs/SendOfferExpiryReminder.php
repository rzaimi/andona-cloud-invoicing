<?php

namespace App\Jobs;

use App\Modules\Offer\Models\Offer;
use App\Traits\ConfiguresCompanySmtp;
use App\Traits\LogsEmails;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOfferExpiryReminder implements ShouldQueue
{
    use ConfiguresCompanySmtp;
    use LogsEmails;
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly string $offerId,
    ) {}

    public function handle(): void
    {
        $offer = Offer::with(['items', 'customer', 'company', 'user'])->find($this->offerId);

        if (! $offer || ! $offer->company || ! $offer->customer?->email) {
            Log::warning('SendOfferExpiryReminder skipped: offer, company, or recipient missing', [
                'offer_id' => $this->offerId,
            ]);

            return;
        }

        $company = $offer->company;
        $this->configureCompanySmtp($company);

        $pdf = Pdf::loadView('pdf.offer', [
            'layout' => $this->defaultLayout(),
            'offer' => $offer,
            'company' => $company,
            'customer' => $offer->customer,
        ])->setPaper('a4')->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'enable-local-file-access' => false,
            'isPhpEnabled' => false,
        ]);

        $subject = "Erinnerung - Angebot {$offer->number}";
        $replyTo = $company->smtp_reply_to ?: null;

        Mail::send('emails.offer-reminder', [
            'offer' => $offer,
            'company' => $company,
        ], function ($message) use ($offer, $pdf, $subject, $replyTo) {
            $message->to($offer->customer->email);
            if ($replyTo) {
                $message->replyTo($replyTo);
            }
            $message->subject($subject);
            $message->attachData($pdf->output(), "Angebot_{$offer->number}.pdf", [
                'mime' => 'application/pdf',
            ]);
        });

        $this->logEmail(
            companyId: $company->id,
            recipientEmail: $offer->customer->email,
            subject: $subject,
            type: 'reminder',
            customerId: $offer->customer_id,
            recipientName: $offer->customer->name,
            relatedType: 'Offer',
            relatedId: $offer->id,
            metadata: [
                'offer_number' => $offer->number,
                'valid_until' => $offer->valid_until->format('Y-m-d'),
                'days_remaining' => Carbon::today()->diffInDays(Carbon::parse($offer->valid_until)),
                'has_pdf_attachment' => true,
            ]
        );
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
