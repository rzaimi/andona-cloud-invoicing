<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PurgeTrashedInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:purge-trashed
                            {--company= : Purge only invoices of this company ID}
                            {--days=0 : Purge only invoices trashed at least this many days ago}
                            {--dry-run : List what would be purged without deleting}
                            {--force : Skip the confirmation prompt (for non-interactive use)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently delete soft-deleted invoices left over from before soft deletion was removed';

    /**
     * Invoices are no longer soft-deletable, so this works on the raw
     * `deleted_at` column rather than Eloquent's SoftDeletes API. That way
     * the command runs against a database still on the old schema (the
     * intended use: cleaning up prod before/without the drop-column
     * migration) and exits gracefully once the column is gone.
     */
    public function handle(): int
    {
        if (! Schema::hasColumn('invoices', 'deleted_at')) {
            $this->info('The invoices table has no deleted_at column (soft deletes already removed) — nothing to purge.');

            return self::SUCCESS;
        }

        $query = DB::table('invoices')->whereNotNull('deleted_at');

        if ($company = $this->option('company')) {
            $query->where('company_id', $company);
        }

        if (($days = (int) $this->option('days')) > 0) {
            $query->where('deleted_at', '<=', now()->subDays($days));
        }

        $invoices = $query->orderBy('deleted_at')
            ->get(['id', 'number', 'status', 'company_id', 'deleted_at']);

        if ($invoices->isEmpty()) {
            $this->info('No trashed invoices to purge.');

            return self::SUCCESS;
        }

        $this->table(
            ['Number', 'Status', 'Company', 'Deleted at'],
            $invoices->map(fn ($i) => [$i->number, $i->status, $i->company_id, $i->deleted_at])
        );

        if ($this->option('dry-run')) {
            $this->info("Dry run: {$invoices->count()} trashed invoice(s) would be permanently deleted.");

            return self::SUCCESS;
        }

        if (! $this->option('force')
            && ! $this->confirm("Permanently delete {$invoices->count()} trashed invoice(s)? Their invoice numbers become reusable. This cannot be undone.")) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        // Items, payments and audit logs cascade at the DB level. The rows
        // (audit trail included) are gone afterwards, so log each purge.
        foreach ($invoices as $invoice) {
            Log::warning('Trashed invoice purged via invoices:purge-trashed', [
                'invoice_id' => $invoice->id,
                'number' => $invoice->number,
                'status' => $invoice->status,
                'company_id' => $invoice->company_id,
                'trashed_at' => $invoice->deleted_at,
            ]);

            DB::table('invoices')->where('id', $invoice->id)->delete();
        }

        $this->info("{$invoices->count()} invoice(s) permanently deleted.");

        return self::SUCCESS;
    }
}
