<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Migrates files from the old flat-ish storage layout to the new per-tenant structure.
 *
 * Old layout:
 *   public disk   → company-logos/{random}
 *   documents disk → {company_id}/{year}/{month}/{random}
 *   expenses disk  → {company_id}/{year}/{month}/{original_name}
 *
 * New layout:
 *   public disk   → tenants/{company_id}/logo/{uuid}.{ext}
 *   private disk  → {company_id}/documents/{year}/{month}/{uuid}.{ext}
 *   private disk  → {company_id}/expenses/{year}/{month}/{uuid}.{ext}
 *
 * Run:  php artisan storage:migrate-to-tenant-structure [--dry-run]
 */
class MigrateStorageToTenantStructure extends Command
{
    protected $signature   = 'storage:migrate-to-tenant-structure {--dry-run : Preview changes without moving files}';
    protected $description = 'Migrate existing files to the per-tenant folder structure';

    private bool $dryRun = false;
    private int  $moved  = 0;
    private int  $skipped = 0;
    private int  $errors  = 0;

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');

        if ($this->dryRun) {
            $this->warn('DRY-RUN mode — no files will be moved.');
        }

        $this->migrateLogos();
        $this->migrateDocuments();
        $this->migrateExpenses();

        $this->newLine();
        $this->info("Done. Moved: {$this->moved}  |  Skipped: {$this->skipped}  |  Errors: {$this->errors}");

        if (!$this->dryRun && $this->moved > 0) {
            $this->warn('You can now remove the legacy disks (documents / expenses) from config/filesystems.php once you have verified everything is working.');
        }

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Logos: public disk, company-logos/* → tenants/{id}/logo/{uuid}.{ext}
    // -------------------------------------------------------------------------
    private function migrateLogos(): void
    {
        $this->info('── Migrating company logos…');

        $companies = DB::table('companies')
            ->whereNotNull('logo')
            ->get(['id', 'logo']);

        foreach ($companies as $company) {
            $oldPath = $company->logo;

            // Already migrated?
            if (str_starts_with($oldPath, 'tenants/')) {
                $this->skipped++;
                continue;
            }

            if (!Storage::disk('public')->exists($oldPath)) {
                $this->warn("  Logo not found on disk: {$oldPath}");
                $this->skipped++;
                continue;
            }

            $ext     = pathinfo($oldPath, PATHINFO_EXTENSION);
            $newPath = "tenants/{$company->id}/logo/" . Str::uuid() . ($ext ? ".{$ext}" : '');

            $this->line("  {$oldPath} → {$newPath}");

            if (!$this->dryRun) {
                try {
                    $content = Storage::disk('public')->get($oldPath);
                    Storage::disk('public')->put($newPath, $content);
                    DB::table('companies')->where('id', $company->id)->update(['logo' => $newPath]);
                    Storage::disk('public')->delete($oldPath);
                    $this->moved++;
                } catch (\Throwable $e) {
                    $this->error("  ERROR: {$e->getMessage()}");
                    $this->errors++;
                }
            } else {
                $this->moved++;
            }
        }
    }

    // -------------------------------------------------------------------------
    // Documents: documents disk, {cid}/{y}/{m}/{f} → private disk, {cid}/documents/{y}/{m}/{uuid}.{ext}
    // -------------------------------------------------------------------------
    private function migrateDocuments(): void
    {
        $this->info('── Migrating documents…');

        $documents = DB::table('documents')
            ->whereNotNull('file_path')
            ->get(['id', 'company_id', 'file_path', 'original_filename']);

        foreach ($documents as $doc) {
            $oldPath = $doc->file_path;

            // Already on new structure?
            if (str_contains($oldPath, '/documents/')) {
                $this->skipped++;
                continue;
            }

            if (!Storage::disk('documents')->exists($oldPath)) {
                $this->warn("  Document not found: {$oldPath}");
                $this->skipped++;
                continue;
            }

            // Extract year/month from old path (pattern: {cid}/{year}/{month}/{file})
            $parts = explode('/', $oldPath);
            $year  = $parts[1] ?? now()->year;
            $month = $parts[2] ?? now()->format('m');
            $ext   = pathinfo($oldPath, PATHINFO_EXTENSION);

            $newPath = "{$doc->company_id}/documents/{$year}/{$month}/" . Str::uuid() . ($ext ? ".{$ext}" : '');

            $this->line("  {$oldPath} → {$newPath}");

            if (!$this->dryRun) {
                try {
                    $content = Storage::disk('documents')->get($oldPath);
                    Storage::disk('private')->put($newPath, $content);
                    DB::table('documents')->where('id', $doc->id)->update(['file_path' => $newPath]);
                    Storage::disk('documents')->delete($oldPath);
                    $this->moved++;
                } catch (\Throwable $e) {
                    $this->error("  ERROR: {$e->getMessage()}");
                    $this->errors++;
                }
            } else {
                $this->moved++;
            }
        }
    }

    // -------------------------------------------------------------------------
    // Expenses: expenses disk, {cid}/{y}/{m}/{original_name} → private disk, {cid}/expenses/{y}/{m}/{uuid}.{ext}
    // -------------------------------------------------------------------------
    private function migrateExpenses(): void
    {
        $this->info('── Migrating expense receipts…');

        $expenses = DB::table('expenses')
            ->whereNotNull('receipt_path')
            ->get(['id', 'company_id', 'receipt_path', 'receipt_original_filename']);

        foreach ($expenses as $expense) {
            $oldPath = $expense->receipt_path;

            // Already on new structure?
            if (str_contains($oldPath, '/expenses/')) {
                $this->skipped++;
                continue;
            }

            if (!Storage::disk('expenses')->exists($oldPath)) {
                $this->warn("  Receipt not found: {$oldPath}");
                $this->skipped++;
                continue;
            }

            $parts    = explode('/', $oldPath);
            $year     = $parts[1] ?? now()->year;
            $month    = $parts[2] ?? now()->format('m');
            $origName = $expense->receipt_original_filename ?? basename($oldPath);
            $ext      = pathinfo($oldPath, PATHINFO_EXTENSION);

            $newPath = "{$expense->company_id}/expenses/{$year}/{$month}/" . Str::uuid() . ($ext ? ".{$ext}" : '');

            $this->line("  {$oldPath} → {$newPath}");

            if (!$this->dryRun) {
                try {
                    $content = Storage::disk('expenses')->get($oldPath);
                    Storage::disk('private')->put($newPath, $content);
                    DB::table('expenses')->where('id', $expense->id)->update([
                        'receipt_path'             => $newPath,
                        'receipt_original_filename' => $origName,
                    ]);
                    Storage::disk('expenses')->delete($oldPath);
                    $this->moved++;
                } catch (\Throwable $e) {
                    $this->error("  ERROR: {$e->getMessage()}");
                    $this->errors++;
                }
            } else {
                $this->moved++;
            }
        }
    }
}
