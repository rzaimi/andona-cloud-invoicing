<?php

namespace App\Console\Commands;

use App\Jobs\SendInvoiceReminder;
use App\Jobs\SendOfferExpiryReminder;
use App\Modules\Company\Models\Company;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Offer\Models\Offer;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendDailyReminders extends Command
{
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
            if (! $company->smtp_host || ! $company->smtp_username) {
                $this->warn("⚠️  Skipping {$company->name} - SMTP not configured");

                continue;
            }

            $this->info("📧 Processing reminders for: {$company->name}");

            // Send invoice reminders
            $this->sendInvoiceReminders($company, $dryRun);

            // Send offer reminders
            $this->sendOfferReminders($company, $dryRun);

            $this->newLine();
        }

        $this->info('✅ Daily reminder process completed!');
    }

    /**
     * Send reminders for invoices (German Mahnung Process)
     */
    protected function sendInvoiceReminders(Company $company, bool $dryRun)
    {
        $today = Carbon::today();

        // Respect the per-company auto-send toggle
        if (! (bool) $company->getSetting('reminder_auto_send', true)) {
            $this->line("  ⏭  Auto-send disabled for {$company->name}, skipping.");

            return;
        }

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
            if (! $invoice->customer || ! $invoice->customer->email) {
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
                SendInvoiceReminder::dispatch($invoice->id, $nextLevel, $fee);
                $levelName = $invoice->getReminderLevelNameForLevel($nextLevel);
                $this->line("  ✓ Queued {$levelName} for invoice {$invoice->number} ({$daysOverdue} days overdue) to {$invoice->customer->email}".($fee > 0 ? " [Fee: €{$fee}]" : ''));
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
            if (! $offer->customer || ! $offer->customer->email) {
                continue;
            }

            $daysRemaining = $today->diffInDays(Carbon::parse($offer->valid_until));

            if ($dryRun) {
                $this->line("  [DRY RUN] Would send expiry reminder for offer {$offer->number} ({$daysRemaining} days remaining) to {$offer->customer->email}");
            } else {
                SendOfferExpiryReminder::dispatch($offer->id);
                $this->line("  ✓ Queued expiry reminder for offer {$offer->number} ({$daysRemaining} days remaining) to {$offer->customer->email}");
            }
        }
    }
}
