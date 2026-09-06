<?php

namespace App\Console\Commands;

use App\Jobs\SendOfferExpiryReminder;
use App\Modules\Company\Models\Company;
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
    protected $description = 'Send daily Mahnungen (via dunning:send) and expiring-offer reminders';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $params = [];
        if ($this->option('dry-run')) {
            $params['--dry-run'] = true;
        }
        if ($this->option('company')) {
            $params['--company'] = $this->option('company');
        }

        // Keep the status column honest before escalating: sent + past due
        // date becomes overdue, so status-based counts match the Mahnwesen.
        if (! $this->option('dry-run')) {
            $this->call('invoices:mark-overdue', array_filter([
                '--company' => $this->option('company'),
            ]));
            $this->newLine();
        }

        $this->call('dunning:send', $params);
        $this->newLine();
        $this->sendOfferReminders((bool) $this->option('dry-run'), $this->option('company'));

        return self::SUCCESS;
    }

    protected function sendOfferReminders(bool $dryRun, ?string $companyFilter): void
    {
        $this->info('Starting offer expiry reminders...');

        $companies = $companyFilter
            ? Company::where('id', $companyFilter)->get()
            : Company::all();

        foreach ($companies as $company) {
            if (! $company->smtp_host || ! $company->smtp_username) {
                $this->warn("Skipping {$company->name} - SMTP not configured");

                continue;
            }

            $today = Carbon::today();
            $threeDaysFromNow = Carbon::today()->addDays(3);

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
                    $this->line("  Queued expiry reminder for offer {$offer->number} ({$daysRemaining} days remaining) to {$offer->customer->email}");
                }
            }
        }

        $this->info('Offer expiry reminders completed.');
    }
}
