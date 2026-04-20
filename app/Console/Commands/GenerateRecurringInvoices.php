<?php

namespace App\Console\Commands;

use App\Services\RecurringInvoiceService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class GenerateRecurringInvoices extends Command
{
    protected $signature = 'invoices:recurring-generate
                            {--dry-run : Run the scan without creating invoices}
                            {--company= : Limit to a specific company UUID}
                            {--date= : Override the current date (YYYY-MM-DD) for testing}';

    protected $description = 'Generate invoices for every recurring profile that is due today';

    public function handle(RecurringInvoiceService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $companyId = $this->option('company') ?: null;
        $dateOpt = $this->option('date');

        $now = $dateOpt
            ? CarbonImmutable::parse($dateOpt)
            : CarbonImmutable::now();

        $this->info('🔁 Recurring invoice generation — '.$now->toDateString());
        if ($dryRun) {
            $this->warn('Dry-run: no invoices will be created.');
        }
        if ($companyId) {
            $this->line("Scoped to company: {$companyId}");
        }

        if ($dryRun) {
            $due = \App\Modules\RecurringInvoice\Models\RecurringInvoiceProfile::dueFor($now);
            if ($companyId) {
                $due->where('company_id', $companyId);
            }
            $count = $due->count();
            $this->line("Eligible profiles: {$count}");

            return self::SUCCESS;
        }

        $results = $service->generateDue($now, $companyId);

        $generated = $results->where('status', 'generated')->count();
        $skipped = $results->where('status', 'skipped')->count();
        $failed = $results->where('status', 'failed')->count();

        $this->line('');
        $this->info("Generated: {$generated}");
        $this->line("Skipped:   {$skipped}");
        if ($failed > 0) {
            $this->error("Failed:    {$failed}");
            foreach ($results->where('status', 'failed') as $row) {
                $this->error("  profile={$row['profile_id']} — {$row['error']}");
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
