<?php

namespace App\Console\Commands;

use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Models\InvoiceAuditLog;
use App\Modules\Offer\Models\Offer;
use Illuminate\Console\Command;

/**
 * Backfill company-snapshot fields that are missing on historic invoices /
 * offers. Useful after adding new snapshot fields (e.g. legal_form_label,
 * manager_title, display_name) so old rows render the same way new ones do.
 *
 * GoBD-safe: only fills fields that are currently null/empty. Existing
 * snapshot values are never overwritten.
 */
class RefreshInvoiceSnapshots extends Command
{
    protected $signature = 'invoices:refresh-snapshots
                            {--company= : Limit to a specific company UUID}
                            {--offers-too : Also refresh offers}
                            {--dry-run : Report what would change without writing}';

    protected $description = 'Backfill missing company-snapshot fields on invoices (and optionally offers).';

    public function handle(): int
    {
        $companyId = $this->option('company');
        $dryRun    = (bool) $this->option('dry-run');
        $doOffers  = (bool) $this->option('offers-too');

        $this->refreshInvoices($companyId, $dryRun);

        if ($doOffers) {
            $this->refreshOffers($companyId, $dryRun);
        }

        return self::SUCCESS;
    }

    private function refreshInvoices(?string $companyId, bool $dryRun): void
    {
        $query = Invoice::query();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $total   = $query->count();
        $touched = 0;
        $filledPerField = [];

        $this->info("Scanning {$total} invoice(s)…");

        $query->chunkById(200, function ($rows) use (&$touched, &$filledPerField, $dryRun) {
            foreach ($rows as $invoice) {
                if ($dryRun) {
                    // Replicate the fill logic without persisting.
                    $fresh    = $invoice->createCompanySnapshot();
                    $current  = $invoice->company_snapshot ?? [];
                    $wouldFill = [];
                    foreach ($fresh as $k => $v) {
                        if ($k === 'snapshot_date') continue;
                        $missing = !array_key_exists($k, $current) || ($current[$k] ?? null) === null || $current[$k] === '';
                        if ($missing && $v !== null && $v !== '') $wouldFill[] = $k;
                    }
                    if (!empty($wouldFill)) {
                        $touched++;
                        foreach ($wouldFill as $f) $filledPerField[$f] = ($filledPerField[$f] ?? 0) + 1;
                    }
                    continue;
                }

                $filled = $invoice->refreshCompanySnapshot();
                if (!empty($filled)) {
                    $touched++;
                    foreach ($filled as $f) $filledPerField[$f] = ($filledPerField[$f] ?? 0) + 1;

                    InvoiceAuditLog::log(
                        $invoice->id,
                        'snapshot_refreshed',
                        $invoice->status,
                        $invoice->status,
                        ['filled_fields' => $filled, 'source' => 'cli'],
                        'CLI: Fehlende Firmendaten-Felder ergänzt: ' . implode(', ', $filled)
                    );
                }
            }
        });

        $verb = $dryRun ? 'would be updated' : 'updated';
        $this->info("Invoices {$verb}: {$touched}");
        foreach ($filledPerField as $field => $count) {
            $this->line("  {$field}: {$count}");
        }
    }

    private function refreshOffers(?string $companyId, bool $dryRun): void
    {
        $query = Offer::query();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $total   = $query->count();
        $touched = 0;

        $this->info("Scanning {$total} offer(s)…");

        $query->chunkById(200, function ($rows) use (&$touched, $dryRun) {
            foreach ($rows as $offer) {
                if ($dryRun) {
                    $fresh    = $offer->createCompanySnapshot();
                    $current  = $offer->company_snapshot ?? [];
                    $has = false;
                    foreach ($fresh as $k => $v) {
                        if ($k === 'snapshot_date') continue;
                        $missing = !array_key_exists($k, $current) || ($current[$k] ?? null) === null || $current[$k] === '';
                        if ($missing && $v !== null && $v !== '') { $has = true; break; }
                    }
                    if ($has) $touched++;
                    continue;
                }

                $filled = $offer->refreshCompanySnapshot();
                if (!empty($filled)) $touched++;
            }
        });

        $verb = $dryRun ? 'would be updated' : 'updated';
        $this->info("Offers {$verb}: {$touched}");
    }
}
