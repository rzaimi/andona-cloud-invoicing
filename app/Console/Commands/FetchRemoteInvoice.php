<?php

namespace App\Console\Commands;

use App\Modules\Company\Models\Company;
use App\Modules\Document\Models\Document;
use App\Modules\User\Models\User;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Authenticates against a remote AndoBill / FusionInvoice instance and
 * downloads one or more invoice PDFs.
 *
 * Usage examples:
 *   php artisan invoices:fetch-remote 67
 *   php artisan invoices:fetch-remote 4-37                               # range: IDs 4 through 37
 *   php artisan invoices:fetch-remote 4-37 67 68 --output=/tmp/invoices  # mix range + individual
 *   php artisan invoices:fetch-remote 67 --url=https://... --email=... --password=...
 *   php artisan invoices:fetch-remote 4-37 --list                        # print URLs only
 *   php artisan invoices:fetch-remote 4-37 --company-id=<uuid>           # import into local system
 */
class FetchRemoteInvoice extends Command
{
    protected $signature = 'invoices:fetch-remote
                            {ids*                : Invoice IDs or ranges (e.g. 67  4-37  1 5 10-20)}
                            {--url=              : Base URL of the remote app (env: REMOTE_APP_URL)}
                            {--email=            : Login e-mail (env: REMOTE_APP_EMAIL)}
                            {--password=         : Login password (env: REMOTE_APP_PASSWORD)}
                            {--company-id=       : Local company UUID — saves PDFs as Documents in the system}
                            {--output=           : Directory to save PDFs locally (defaults to storage/app/remote-invoices)}
                            {--list              : Only print the download URLs, do not save the files}
                            {--skip-existing     : Skip invoices already imported for this company}
                            {--timeout=30        : HTTP timeout in seconds}';

    protected $description = 'Login to a remote AndoBill instance and download invoice PDFs';

    public function handle(): int
    {
        // ── Config resolution ──────────────────────────────────────────────
        $baseUrl   = rtrim($this->option('url')      ?: env('REMOTE_APP_URL',      ''), '/');
        $email     = $this->option('email')           ?: env('REMOTE_APP_EMAIL',    '');
        $password  = $this->option('password')        ?: env('REMOTE_APP_PASSWORD', '');
        $timeout   = (int) ($this->option('timeout') ?: 30);
        $companyId = $this->option('company-id')      ?: env('REMOTE_IMPORT_COMPANY_ID', '');
        $ids       = $this->expandIds($this->argument('ids'));

        if (! $baseUrl) {
            $this->error('Base URL is required. Pass --url= or set REMOTE_APP_URL in .env');
            return self::FAILURE;
        }
        if (! $email || ! $password) {
            $this->error('Credentials are required. Pass --email= / --password= or set REMOTE_APP_EMAIL / REMOTE_APP_PASSWORD in .env');
            return self::FAILURE;
        }

        // ── Resolve local company (when --company-id is given) ─────────────
        $company  = null;
        $importer = null;

        if ($companyId) {
            $company = Company::find($companyId);
            if (! $company) {
                $this->error("Company not found in local database: {$companyId}");
                return self::FAILURE;
            }

            // Use the first admin/super_admin of the company as the uploader
            $importer = User::where('company_id', $company->id)
                ->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'super_admin', 'user']))
                ->orderByRaw("CASE WHEN role = 'admin' THEN 0 WHEN role = 'super_admin' THEN 1 ELSE 2 END")
                ->first();

            if (! $importer) {
                $this->error("No user found for company {$company->name}. Cannot set uploaded_by.");
                return self::FAILURE;
            }

            $this->info("Importing into company: <comment>{$company->name}</comment> (uploader: {$importer->name})");
        }

        // ── Output directory (used when NOT importing into DB) ─────────────
        $outputDir = $this->option('output') ?: storage_path('app/remote-invoices');

        if (! $this->option('list') && ! $company && ! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        // ── Step 1: Fetch login page to get CSRF token ─────────────────────
        $this->info("Connecting to {$baseUrl} …");

        $cookieJar    = new CookieJar();
        $loginPageUrl = $baseUrl . '/login';

        $loginPage = Http::timeout($timeout)
            ->withOptions(['cookies' => $cookieJar, 'allow_redirects' => true])
            ->withHeaders([
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'de-DE,de;q=0.9,en;q=0.8',
                'User-Agent'      => 'AndoBill-CLI/1.0',
            ])
            ->get($loginPageUrl);

        if (! $loginPage->successful()) {
            $this->error("Failed to load login page ({$loginPage->status()}): {$loginPageUrl}");
            return self::FAILURE;
        }

        $csrfToken = $this->extractCsrfToken($loginPage->body());

        if (! $csrfToken) {
            $this->error('Could not extract CSRF token from login page. The remote app structure may have changed.');
            return self::FAILURE;
        }

        $this->line("  CSRF token acquired: " . substr($csrfToken, 0, 10) . '…');

        // ── Step 2: Submit login credentials ──────────────────────────────
        $loginResponse = Http::timeout($timeout)
            ->withOptions(['cookies' => $cookieJar, 'allow_redirects' => true])
            ->withHeaders(['Referer' => $loginPageUrl, 'User-Agent' => 'AndoBill-CLI/1.0'])
            ->asForm()
            ->post($baseUrl . '/login', [
                '_token'   => $csrfToken,
                'email'    => $email,
                'password' => $password,
            ]);

        if (
            str_contains($loginResponse->body(), 'stimmen nicht') ||
            str_contains($loginResponse->body(), 'credentials do not match') ||
            str_contains($loginResponse->body(), 'id="email"')
        ) {
            $this->error('Login failed — please check your credentials.');
            return self::FAILURE;
        }

        $this->info('  Login successful.');

        // ── Step 3: Download each invoice PDF ─────────────────────────────
        $success = 0;
        $failed  = 0;
        $skipped = 0;

        foreach ($ids as $invoiceId) {
            $pdfUrl      = $baseUrl . '/invoices/' . $invoiceId . '/pdf';
            $docName     = "Rechnung #{$invoiceId}";
            $origFilename = "invoice-{$invoiceId}.pdf";

            // Skip-existing check
            if ($company && $this->option('skip-existing')) {
                $alreadyExists = Document::where('company_id', $company->id)
                    ->where('original_filename', $origFilename)
                    ->exists();

                if ($alreadyExists) {
                    $this->line("  ↷ Skipped (already imported): invoice #{$invoiceId}");
                    $skipped++;
                    continue;
                }
            }

            $this->line("Fetching invoice #{$invoiceId} …");

            $pdfResponse = Http::timeout($timeout)
                ->withOptions(['cookies' => $cookieJar, 'allow_redirects' => true])
                ->withHeaders([
                    'Accept'     => 'application/pdf,*/*',
                    'Referer'    => $baseUrl . '/invoices/' . $invoiceId,
                    'User-Agent' => 'AndoBill-CLI/1.0',
                ])
                ->get($pdfUrl);

            if (! $pdfResponse->successful()) {
                $this->error("  ✗ HTTP {$pdfResponse->status()} for invoice #{$invoiceId}");
                $failed++;
                continue;
            }

            $contentType = $pdfResponse->header('Content-Type');
            $isPdf = str_contains($contentType, 'pdf') || str_starts_with($pdfResponse->body(), '%PDF');

            if (! $isPdf) {
                if (str_contains($pdfResponse->body(), 'id="email"') || str_contains($pdfResponse->body(), '/login')) {
                    $this->error("  ✗ Session expired or access denied for invoice #{$invoiceId}");
                } else {
                    $this->error("  ✗ Response is not a PDF (Content-Type: {$contentType})");
                }
                $failed++;
                continue;
            }

            if ($this->option('list')) {
                $this->line("  → {$pdfUrl}");
                $success++;
                continue;
            }

            $pdfContents = $pdfResponse->body();
            $sizeKb      = number_format(strlen($pdfContents) / 1024, 1);

            // ── Save into local Document system ────────────────────────────
            if ($company) {
                $year      = now()->year;
                $month     = now()->format('m');
                $uuid      = (string) Str::uuid();
                $storePath = "{$company->id}/documents/{$year}/{$month}/{$uuid}.pdf";

                Storage::disk('private')->put($storePath, $pdfContents);

                Document::create([
                    'company_id'        => $company->id,
                    'name'              => $docName,
                    'original_filename' => $origFilename,
                    'file_path'         => $storePath,
                    'file_size'         => strlen($pdfContents),
                    'mime_type'         => 'application/pdf',
                    'category'          => Document::CATEGORY_INVOICE,
                    'description'       => "Importiert von {$baseUrl}",
                    'tags'              => ['import', 'remote'],
                    'uploaded_by'       => $importer->id,
                    'link_type'         => Document::LINK_TYPE_ATTACHMENT,
                ]);

                $this->info("  ✓ Imported as Document: {$docName} ({$sizeKb} KB) → {$storePath}");
            } else {
                // ── Plain file save ────────────────────────────────────────
                $localPath = $outputDir . DIRECTORY_SEPARATOR . $origFilename;
                file_put_contents($localPath, $pdfContents);
                $this->info("  ✓ Saved: {$localPath} ({$sizeKb} KB)");
            }

            $success++;
        }

        // ── Summary ────────────────────────────────────────────────────────
        $this->newLine();
        $rows = [
            ['✓ Imported/Saved', $success],
            ['✗ Failed',         $failed],
        ];
        if ($skipped > 0) {
            $rows[] = ['↷ Skipped',  $skipped];
        }
        $this->table(['Result', 'Count'], $rows);

        if ($company && $success > 0) {
            $this->line("  → View in Documents: <comment>" . url('/documents') . "</comment>");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Expand a mixed list of IDs and ranges into a flat array of integers.
     *
     * Examples:
     *   ['67']             → [67]
     *   ['4-37']           → [4, 5, 6, …, 37]
     *   ['1', '5-8', '10'] → [1, 5, 6, 7, 8, 10]
     *
     * @param  string[]  $raw
     * @return int[]
     */
    private function expandIds(array $raw): array
    {
        $result = [];

        foreach ($raw as $token) {
            $token = trim($token);

            if (preg_match('/^(\d+)-(\d+)$/', $token, $m)) {
                $from = (int) $m[1];
                $to   = (int) $m[2];

                if ($from > $to) {
                    $this->warn("Skipping invalid range '{$token}' (start > end).");
                    continue;
                }

                foreach (range($from, $to) as $id) {
                    $result[] = $id;
                }
            } elseif (ctype_digit($token)) {
                $result[] = (int) $token;
            } else {
                $this->warn("Skipping unrecognised ID token: '{$token}'");
            }
        }

        return array_unique($result);
    }

    /**
     * Extract the CSRF token from a Laravel login page.
     */
    private function extractCsrfToken(string $html): ?string
    {
        // <meta name="csrf-token" content="...">
        if (preg_match('/<meta\s+name=["\']csrf-token["\']\s+content=["\']([\w\-+\/=]+)["\']/i', $html, $m)) {
            return $m[1];
        }

        // <input type="hidden" name="_token" value="...">
        if (preg_match('/<input[^>]+name=["\']_token["\']\s+[^>]*value=["\']([\w\-+\/=]+)["\']/i', $html, $m)) {
            return $m[1];
        }

        if (preg_match('/value=["\']([\w\-+\/=]+)["\']\s+[^>]*name=["\']_token["\']/i', $html, $m)) {
            return $m[1];
        }

        return null;
    }
}
