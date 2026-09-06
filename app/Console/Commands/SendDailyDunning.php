<?php

namespace App\Console\Commands;

use App\Jobs\SendInvoiceReminder;
use App\Modules\Company\Models\Company;
use App\Modules\Mahnung\Services\DunningService;
use Illuminate\Console\Command;

class SendDailyDunning extends Command
{
    protected $signature = 'dunning:send
                            {--dry-run : Run without actually sending emails}
                            {--company= : Send reminders for a specific company only}';

    /** Old command name kept as an alias for external crontabs/runbooks. */
    protected $aliases = ['mahnungen:send'];

    protected $description = 'Escalate overdue invoices one Mahnstufe (German dunning)';

    public function handle(DunningService $dunning): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $companyFilter = $this->option('company');

        $this->info('Starting Mahnwesen process...');

        $companies = $companyFilter
            ? Company::where('id', $companyFilter)->get()
            : Company::all();

        foreach ($companies as $company) {
            if (! $company->smtp_host || ! $company->smtp_username) {
                $this->warn("Skipping {$company->name} - SMTP not configured");

                continue;
            }

            $due = $dunning->invoicesDueForEscalation($company);
            $this->info("{$company->name}: {$due->count()} invoice(s) due");

            foreach ($due as $invoice) {
                $next = $dunning->nextDueLevel($invoice, $company);
                if ($next === null) {
                    continue;
                }

                $levelName = $invoice->getReminderLevelNameForLevel($next['level']);
                $email = $invoice->customer?->email ?? '-';

                if ($dryRun) {
                    $this->line("  [DRY RUN] {$levelName} for {$invoice->number} → {$email}");

                    continue;
                }

                SendInvoiceReminder::dispatch($invoice->id);
                $this->line("  Queued {$levelName} for {$invoice->number} → {$email}");
            }
        }

        $this->info('Mahnwesen process completed.');

        return self::SUCCESS;
    }
}
