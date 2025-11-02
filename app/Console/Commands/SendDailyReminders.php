<?php

namespace App\Console\Commands;

use App\Modules\Company\Models\Company;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Offer\Models\Offer;
use App\Traits\LogsEmails;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

class SendDailyReminders extends Command
{
    use LogsEmails;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:send 
                            {--dry-run : Run without actually sending emails}
                            {--company= : Send reminders for specific company only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily reminders for due invoices and expiring offers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $companyFilter = $this->option('company');

        $this->info('🔔 Starting daily reminder process...');
        $this->newLine();

        $companies = $companyFilter 
            ? Company::where('id', $companyFilter)->get()
            : Company::all();

        foreach ($companies as $company) {
            // Skip companies without SMTP configured
            if (!$company->smtp_host || !$company->smtp_username) {
                $this->warn("⚠️  Skipping {$company->name} - SMTP not configured");
                continue;
            }

            $this->info("📧 Processing reminders for: {$company->name}");

            // Configure SMTP for this company
            $this->configureSMTP($company);

            // Send invoice reminders
            $this->sendInvoiceReminders($company, $dryRun);

            // Send offer reminders
            $this->sendOfferReminders($company, $dryRun);

            $this->newLine();
        }

        $this->info('✅ Daily reminder process completed!');
    }

    /**
     * Configure SMTP settings for the company
     */
    protected function configureSMTP(Company $company)
    {
        // Set default mailer to smtp (otherwise it might use 'log' which just logs emails)
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
     * Send reminders for invoices (German Mahnung Process)
     */
    protected function sendInvoiceReminders(Company $company, bool $dryRun)
    {
        $today = Carbon::today();

        // Get company reminder settings
        $friendlyReminderDays = (int) $company->getSetting('reminder_friendly_days', 7); // 7 days after due
        $mahnung1Days = (int) $company->getSetting('reminder_mahnung1_days', 14); // 14 days after due
        $mahnung2Days = (int) $company->getSetting('reminder_mahnung2_days', 21); // 21 days after due
        $mahnung3Days = (int) $company->getSetting('reminder_mahnung3_days', 30); // 30 days after due
        $inkassoDays = (int) $company->getSetting('reminder_inkasso_days', 45); // 45 days after due

        // Get fee settings
        $mahnung1Fee = (float) $company->getSetting('reminder_mahnung1_fee', 5.00);
        $mahnung2Fee = (float) $company->getSetting('reminder_mahnung2_fee', 10.00);
        $mahnung3Fee = (float) $company->getSetting('reminder_mahnung3_fee', 15.00);

        // Get all unpaid overdue invoices
        $overdueInvoices = Invoice::where('company_id', $company->id)
            ->whereIn('status', ['sent', 'overdue'])
            ->whereDate('due_date', '<', $today)
            ->where('reminder_level', '<', Invoice::REMINDER_INKASSO) // Not yet at Inkasso level
            ->with('customer')
            ->get();

        foreach ($overdueInvoices as $invoice) {
            if (!$invoice->customer || !$invoice->customer->email) {
                continue;
            }

            $daysOverdue = $invoice->getDaysOverdue();
            $currentLevel = $invoice->reminder_level;
            $nextLevel = null;
            $fee = 0;

            // Determine if we should send the next reminder
            if ($currentLevel == Invoice::REMINDER_NONE && $daysOverdue >= $friendlyReminderDays) {
                $nextLevel = Invoice::REMINDER_FRIENDLY;
                $fee = 0;
            } elseif ($currentLevel == Invoice::REMINDER_FRIENDLY && $daysOverdue >= $mahnung1Days) {
                $nextLevel = Invoice::REMINDER_MAHNUNG_1;
                $fee = $mahnung1Fee;
            } elseif ($currentLevel == Invoice::REMINDER_MAHNUNG_1 && $daysOverdue >= $mahnung2Days) {
                $nextLevel = Invoice::REMINDER_MAHNUNG_2;
                $fee = $mahnung2Fee;
            } elseif ($currentLevel == Invoice::REMINDER_MAHNUNG_2 && $daysOverdue >= $mahnung3Days) {
                $nextLevel = Invoice::REMINDER_MAHNUNG_3;
                $fee = $mahnung3Fee;
            } elseif ($currentLevel == Invoice::REMINDER_MAHNUNG_3 && $daysOverdue >= $inkassoDays) {
                $nextLevel = Invoice::REMINDER_INKASSO;
                $fee = 0;
            }

            // If no escalation is needed, skip
            if ($nextLevel === null) {
                continue;
            }

            if ($dryRun) {
                $levelName = $invoice->getReminderLevelNameForLevel($nextLevel);
                $this->line("  [DRY RUN] Would send {$levelName} for invoice {$invoice->number} ({$daysOverdue} days overdue) to {$invoice->customer->email}");
            } else {
                try {
                    $this->sendMahnungEmail($invoice, $company, $nextLevel, $fee);
                    
                    // Update invoice
                    $invoice->addReminderToHistory($nextLevel, $fee);
                    if ($nextLevel > Invoice::REMINDER_FRIENDLY) {
                        $invoice->status = 'overdue';
                    }
                    $invoice->save();
                    
                    $levelName = $invoice->getReminderLevelNameForLevel($nextLevel);
                    $this->line("  ✓ Sent {$levelName} for invoice {$invoice->number} ({$daysOverdue} days overdue) to {$invoice->customer->email}" . ($fee > 0 ? " [Fee: €{$fee}]" : ""));
                } catch (\Exception $e) {
                    $this->error("  ✗ Failed to send reminder for invoice {$invoice->number}: {$e->getMessage()}");
                    Log::error("Invoice Mahnung failed: {$e->getMessage()}", [
                        'invoice_id' => $invoice->id,
                        'company_id' => $company->id,
                        'next_level' => $nextLevel,
                    ]);
                }
            }
        }
    }

    /**
     * Send reminders for offers
     */
    protected function sendOfferReminders(Company $company, bool $dryRun)
    {
        $today = Carbon::today();
        $threeDaysFromNow = Carbon::today()->addDays(3);

        // Offers expiring in 3 days or less (but not expired)
        $expiringOffers = Offer::where('company_id', $company->id)
            ->where('status', 'sent')
            ->whereDate('valid_until', '>=', $today)
            ->whereDate('valid_until', '<=', $threeDaysFromNow)
            ->with('customer')
            ->get();

        foreach ($expiringOffers as $offer) {
            if (!$offer->customer || !$offer->customer->email) {
                continue;
            }

            $daysRemaining = $today->diffInDays(Carbon::parse($offer->valid_until));

            if ($dryRun) {
                $this->line("  [DRY RUN] Would send expiry reminder for offer {$offer->number} ({$daysRemaining} days remaining) to {$offer->customer->email}");
            } else {
                try {
                    $this->sendOfferReminder($offer, $company);
                    $this->line("  ✓ Sent expiry reminder for offer {$offer->number} ({$daysRemaining} days remaining) to {$offer->customer->email}");
                } catch (\Exception $e) {
                    $this->error("  ✗ Failed to send reminder for offer {$offer->number}: {$e->getMessage()}");
                    Log::error("Offer reminder failed: {$e->getMessage()}", [
                        'offer_id' => $offer->id,
                        'company_id' => $company->id,
                    ]);
                }
            }
        }
    }

    /**
     * Send Mahnung email based on level
     */
    protected function sendMahnungEmail(Invoice $invoice, Company $company, int $level, float $fee)
    {
        $invoice->load(['items', 'customer', 'company', 'user']);

        // Determine which email template to use
        $template = match($level) {
            Invoice::REMINDER_FRIENDLY => 'emails.reminders.friendly',
            Invoice::REMINDER_MAHNUNG_1 => 'emails.reminders.mahnung-1',
            Invoice::REMINDER_MAHNUNG_2 => 'emails.reminders.mahnung-2',
            Invoice::REMINDER_MAHNUNG_3 => 'emails.reminders.mahnung-3',
            Invoice::REMINDER_INKASSO => 'emails.reminders.inkasso',
            default => 'emails.reminders.friendly',
        };

        // Determine subject
        $subject = match($level) {
            Invoice::REMINDER_FRIENDLY => "Freundliche Zahlungserinnerung - Rechnung {$invoice->number}",
            Invoice::REMINDER_MAHNUNG_1 => "1. Mahnung - Rechnung {$invoice->number}",
            Invoice::REMINDER_MAHNUNG_2 => "2. Mahnung - Rechnung {$invoice->number}",
            Invoice::REMINDER_MAHNUNG_3 => "3. und LETZTE Mahnung - Rechnung {$invoice->number}",
            Invoice::REMINDER_INKASSO => "Inkassoankündigung - Rechnung {$invoice->number}",
            default => "Zahlungserinnerung - Rechnung {$invoice->number}",
        };

        // Generate PDF
        $pdf = Pdf::loadView('pdf.invoice', [
            'layout' => $this->getDefaultLayout('invoice', $company),
            'invoice' => $invoice,
            'company' => $company,
            'customer' => $invoice->customer,
        ]);

        // Calculate additional data for Inkasso level
        $inkassoFee = $level == Invoice::REMINDER_INKASSO ? 50.00 : 0;
        $delayInterest = $level == Invoice::REMINDER_INKASSO 
            ? $invoice->total * 0.09 * ($invoice->getDaysOverdue() / 365) 
            : 0;

        Mail::send($template, [
            'invoice' => $invoice,
            'company' => $company,
            'fee' => $fee,
            'inkassoFee' => $inkassoFee,
            'delayInterest' => $delayInterest,
        ], function ($message) use ($invoice, $pdf, $subject) {
            $message->to($invoice->customer->email);
            $message->subject($subject);
            $message->attachData($pdf->output(), "Rechnung_{$invoice->number}.pdf", [
                'mime' => 'application/pdf',
            ]);
        });

        // Log the mahnung email
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

    /**
     * Send offer reminder email
     */
    protected function sendOfferReminder(Offer $offer, Company $company)
    {
        $offer->load(['items', 'customer', 'company', 'user']);

        // Generate PDF
        $pdf = Pdf::loadView('pdf.offer', [
            'layout' => $this->getDefaultLayout('offer', $company),
            'offer' => $offer,
            'company' => $company,
            'customer' => $offer->customer,
        ]);

        $subject = "Erinnerung - Angebot {$offer->number}";

        Mail::send('emails.offer-reminder', [
            'offer' => $offer,
            'company' => $company,
        ], function ($message) use ($offer, $pdf, $subject) {
            $message->to($offer->customer->email);
            $message->subject($subject);
            $message->attachData($pdf->output(), "Angebot_{$offer->number}.pdf", [
                'mime' => 'application/pdf',
            ]);
        });

        // Log the offer reminder email
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

    /**
     * Get default layout for PDF generation
     */
    protected function getDefaultLayout(string $type, Company $company)
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

