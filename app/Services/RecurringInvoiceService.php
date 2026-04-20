<?php

namespace App\Services;

use App\Modules\Company\Models\Company;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Models\InvoiceAuditLog;
use App\Modules\Invoice\Models\InvoiceItem;
use App\Modules\Product\Models\Product;
use App\Modules\RecurringInvoice\Models\RecurringInvoiceProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Generates invoices from recurring profiles and advances their schedules.
 *
 * Invariants the service protects:
 *
 *  - A profile is not generated twice for the same period: we advance
 *    `next_run_date` inside the same DB transaction that creates the invoice
 *    so a crashed or retried run never double-invoices.
 *  - Invoice numbering remains fortlaufend + lückenlos: we take the same
 *    pessimistic lock on the company row that `InvoiceController::store` uses
 *    before calling `NumberFormatService::next()`.
 *  - Auto-send failures do not block generation: if SMTP is misconfigured we
 *    still create the invoice (as a draft) and log the send failure.
 */
class RecurringInvoiceService
{
    public function __construct(
        private readonly InvoiceMailer $mailer,
    ) {}

    /**
     * Generate invoices for every profile that's due on (or before) the given
     * date. Safe to run multiple times a day — already-advanced profiles are
     * simply skipped.
     *
     * @return Collection<int, array{profile_id: string, invoice_id: ?string, status: string, error?: string}>
     */
    public function generateDue(?CarbonImmutable $now = null, ?string $onlyCompanyId = null): Collection
    {
        $now ??= CarbonImmutable::now();
        $results = collect();

        $query = RecurringInvoiceProfile::dueFor($now)
            ->with(['items', 'customer', 'company']);

        if ($onlyCompanyId) {
            $query->where('company_id', $onlyCompanyId);
        }

        $profileIds = $query->pluck('id');

        foreach ($profileIds as $profileId) {
            try {
                $invoice = $this->runOnce($profileId, $now);
                $results->push([
                    'profile_id' => $profileId,
                    'invoice_id' => $invoice?->id,
                    'status' => $invoice ? 'generated' : 'skipped',
                ]);
            } catch (\Throwable $e) {
                Log::error('Recurring invoice generation failed', [
                    'profile_id' => $profileId,
                    'error' => $e->getMessage(),
                ]);
                $results->push([
                    'profile_id' => $profileId,
                    'invoice_id' => null,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Generate one invoice for the given profile, advance its schedule, and
     * optionally send it. Returns the created invoice or null if, upon
     * re-reading inside the transaction, the profile turned out to be
     * ineligible (paused/completed/another worker got here first).
     */
    public function runOnce(string $profileId, ?CarbonImmutable $now = null): ?Invoice
    {
        $now ??= CarbonImmutable::now();

        return DB::transaction(function () use ($profileId, $now) {
            // Re-read the profile with a lock so concurrent workers can't both
            // create an invoice for the same period.
            /** @var RecurringInvoiceProfile|null $profile */
            $profile = RecurringInvoiceProfile::whereKey($profileId)
                ->lockForUpdate()
                ->first();

            if (! $profile || ! $this->isEligible($profile, $now)) {
                return null;
            }

            $profile->loadMissing(['items', 'customer']);

            $issueDate = CarbonImmutable::instance($profile->next_run_date);
            $invoice = $this->createInvoiceFromProfile($profile, $issueDate);

            $this->advanceSchedule($profile, $issueDate);

            // Best-effort auto-send AFTER the schedule is advanced so a mail
            // failure never causes a double-generation on the next run.
            if ($profile->auto_send && $invoice && $profile->customer?->email) {
                $this->autoSend($profile, $invoice);
            }

            return $invoice;
        });
    }

    /**
     * Create one invoice by copying the profile header + items. Uses the same
     * company-lock + number-generation pattern as `InvoiceController::store`.
     */
    public function createInvoiceFromProfile(
        RecurringInvoiceProfile $profile,
        CarbonImmutable $issueDate
    ): Invoice {
        /** @var Company $company */
        $company = Company::whereKey($profile->company_id)
            ->lockForUpdate()
            ->first();

        if (! $company) {
            throw new \RuntimeException("Company {$profile->company_id} not found for recurring profile {$profile->id}");
        }

        $svc = new NumberFormatService;
        $allNumbers = Invoice::where('company_id', $company->id)->pluck('number');
        $format = $svc->normaliseToFormat(
            $company->getSetting('invoice_number_format')
                ?? $company->getSetting('invoice_prefix', 'RE-')
        );
        $minCounter = (int) ($company->getSetting('invoice_next_counter') ?? 1);
        $invoiceNumber = $svc->next($format, $allNumbers, null, $minCounter);

        $dueDate = $issueDate->addDays(max(0, (int) $profile->due_days_after_issue));

        $invoice = Invoice::create([
            'number' => $invoiceNumber,
            'company_id' => $company->id,
            'customer_id' => $profile->customer_id,
            'user_id' => $profile->user_id ?? $company->users()->value('id'),
            'status' => 'draft',
            'issue_date' => $issueDate->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'notes' => $profile->notes,
            'bauvorhaben' => $profile->bauvorhaben,
            'auftragsnummer' => $profile->auftragsnummer,
            'layout_id' => $profile->layout_id,
            'recurring_profile_id' => $profile->id,
            'vat_regime' => $profile->vat_regime ?: 'standard',
            'tax_rate' => ($profile->vat_regime ?: 'standard') === 'standard'
                                         ? (float) ($profile->tax_rate ?? $company->getSetting('tax_rate', 0.19))
                                         : 0,
            'invoice_type' => Invoice::TYPE_STANDARD,
            'payment_method' => $profile->payment_method,
            'payment_terms' => $profile->payment_terms,
            'skonto_percent' => $profile->skonto_percent,
            'skonto_days' => $profile->skonto_days,
        ]);

        $invoice->company_snapshot = $invoice->createCompanySnapshot();
        $invoice->save();

        foreach ($profile->items as $templateItem) {
            // Security: verify the template's product (if any) still belongs
            // to the profile's company. Products can be soft-deleted or moved
            // between companies by super admins; we silently drop the
            // reference in that case and keep the line text.
            $productId = null;
            if ($templateItem->product_id) {
                $product = Product::where('company_id', $company->id)
                    ->where('id', $templateItem->product_id)
                    ->first();
                if ($product) {
                    $productId = $product->id;
                }
            }

            $item = new InvoiceItem([
                'invoice_id' => $invoice->id,
                'product_id' => $productId,
                'description' => $templateItem->description,
                'quantity' => $templateItem->quantity,
                'unit_price' => $templateItem->unit_price,
                'unit' => $templateItem->unit ?: 'Stk.',
                'tax_rate' => $templateItem->tax_rate ?? $invoice->tax_rate,
                'discount_type' => $templateItem->discount_type ?: null,
                'discount_value' => $templateItem->discount_value,
                'sort_order' => $templateItem->sort_order,
            ]);
            $item->calculateTotal();
            $item->save();
        }

        $invoice->load('items');
        $invoice->calculateTotals();
        $invoice->calculateSkonto();
        $invoice->save();

        InvoiceAuditLog::log(
            $invoice->id,
            'recurring_generated',
            null,
            'draft',
            [
                'profile_id' => $profile->id,
                'profile_name' => $profile->name,
            ],
            'Automatisch aus Abo "'.$profile->name.'" erstellt.'
        );

        return $invoice;
    }

    /**
     * Advance the profile's schedule after a successful generation.
     *
     * We advance from the `next_run_date` we just used, not from "today", so
     * a profile that was overdue (e.g. the job didn't run for a day) catches
     * up one invoice per call rather than skipping periods.
     */
    public function advanceSchedule(RecurringInvoiceProfile $profile, CarbonImmutable $usedIssueDate): void
    {
        $profile->occurrences_count = (int) $profile->occurrences_count + 1;
        $profile->last_run_date = $usedIssueDate->toDateString();

        $next = RecurringInvoiceProfile::advanceDate(
            $usedIssueDate,
            $profile->interval_unit,
            (int) $profile->interval_count,
            $profile->day_of_month
        );

        $profile->next_run_date = $next->toDateString();

        $maxReached = $profile->max_occurrences !== null
            && $profile->occurrences_count >= $profile->max_occurrences;

        $endReached = $profile->end_date
            && $next->greaterThan(CarbonImmutable::instance($profile->end_date));

        if ($maxReached || $endReached) {
            $profile->status = RecurringInvoiceProfile::STATUS_COMPLETED;
        }

        $profile->save();
    }

    /**
     * Profile eligibility gate used inside the locked transaction. Mirrors
     * the `dueFor` scope but works against a freshly-loaded model so races
     * between two workers resolve safely.
     */
    private function isEligible(RecurringInvoiceProfile $profile, CarbonImmutable $now): bool
    {
        if ($profile->status !== RecurringInvoiceProfile::STATUS_ACTIVE) {
            return false;
        }

        if (! $profile->next_run_date
            || CarbonImmutable::instance($profile->next_run_date)->greaterThan($now)
        ) {
            return false;
        }

        if ($profile->end_date
            && CarbonImmutable::instance($profile->end_date)->lessThan($now->startOfDay())
        ) {
            return false;
        }

        if ($profile->paused_until
            && CarbonImmutable::instance($profile->paused_until)->greaterThan($now)
        ) {
            return false;
        }

        if ($profile->max_occurrences !== null
            && $profile->occurrences_count >= $profile->max_occurrences
        ) {
            return false;
        }

        return true;
    }

    /**
     * Fire the shared mailer. Failures are logged but swallowed — we do NOT
     * roll back the invoice, because invoice numbers must stay gap-free.
     */
    private function autoSend(RecurringInvoiceProfile $profile, Invoice $invoice): void
    {
        $to = $profile->customer?->email;
        if (! $to) {
            return;
        }

        $subject = $this->resolveTemplate(
            $profile->email_subject_template,
            $invoice,
            "Rechnung {$invoice->number}"
        );
        $body = $profile->email_body_template
            ? $this->resolveTemplate($profile->email_body_template, $invoice)
            : null;

        $result = $this->mailer->send(
            invoice: $invoice,
            to: $to,
            subject: $subject,
            customMessage: $body,
        );

        if (! $result['ok']) {
            Log::warning('Recurring invoice auto-send failed, invoice kept as draft', [
                'invoice_id' => $invoice->id,
                'profile_id' => $profile->id,
                'error' => $result['error'] ?? null,
            ]);
        }
    }

    /**
     * Minimal placeholder substitution for email templates. Keeps the surface
     * tiny on purpose — the email template editor is a separate feature.
     */
    private function resolveTemplate(?string $template, Invoice $invoice, ?string $default = null): string
    {
        if (! $template) {
            return $default ?? '';
        }

        return strtr($template, [
            '{invoice_number}' => $invoice->number,
            '{customer_name}' => $invoice->customer?->name ?? '',
            '{total}' => number_format((float) $invoice->total, 2, ',', '.'),
            '{issue_date}' => $invoice->issue_date?->format('d.m.Y') ?? '',
            '{due_date}' => $invoice->due_date?->format('d.m.Y') ?? '',
        ]);
    }
}
