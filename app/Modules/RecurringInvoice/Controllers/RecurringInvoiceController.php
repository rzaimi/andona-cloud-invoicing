<?php

namespace App\Modules\RecurringInvoice\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\InvoiceLayout;
use App\Modules\RecurringInvoice\Models\RecurringInvoiceItem;
use App\Modules\RecurringInvoice\Models\RecurringInvoiceProfile;
use App\Services\RecurringInvoiceService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RecurringInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', RecurringInvoiceProfile::class);

        $companyId = $this->getEffectiveCompanyId();

        $query = RecurringInvoiceProfile::forCompany($companyId)
            ->with(['customer:id,name'])
            ->withCount('generatedInvoices');

        if ($status = $request->string('status')->toString()) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        $sort = $request->string('sort')->toString() ?: 'next_run_date';
        $direction = strtolower($request->string('direction')->toString() ?: 'asc');
        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'asc';

        $allowedSorts = ['name', 'status', 'next_run_date', 'start_date'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'next_run_date';
        }
        $query->orderBy($sort, $direction);

        $profiles = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => RecurringInvoiceProfile::forCompany($companyId)->count(),
            'active' => RecurringInvoiceProfile::forCompany($companyId)->where('status', 'active')->count(),
            'paused' => RecurringInvoiceProfile::forCompany($companyId)->where('status', 'paused')->count(),
            'completed' => RecurringInvoiceProfile::forCompany($companyId)->where('status', 'completed')->count(),
        ];

        return Inertia::render('recurring-invoices/index', [
            'profiles' => $profiles,
            'filters' => $request->only(['status', 'search', 'sort', 'direction']),
            'stats' => $stats,
        ]);
    }

    public function create()
    {
        $this->authorize('create', RecurringInvoiceProfile::class);

        $companyId = $this->getEffectiveCompanyId();

        return Inertia::render('recurring-invoices/create', [
            'customers' => Customer::forCompany($companyId)->active()->select('id', 'name', 'email')->get(),
            'layouts' => InvoiceLayout::forCompany($companyId)->get(),
            'products' => \App\Modules\Product\Models\Product::where('company_id', $companyId)
                ->where('status', 'active')
                ->select('id', 'name', 'description', 'price', 'unit', 'tax_rate', 'sku', 'number')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', RecurringInvoiceProfile::class);

        $validated = $this->validatePayload($request);

        $companyId = $this->getEffectiveCompanyId();

        $customer = Customer::forCompany($companyId)->find($validated['customer_id']);
        if (! $customer) {
            abort(403, 'Customer does not belong to your company');
        }

        if (! empty($validated['layout_id'])) {
            $layout = InvoiceLayout::forCompany($companyId)->find($validated['layout_id']);
            if (! $layout) {
                abort(403, 'Layout does not belong to your company');
            }
        }

        $profile = DB::transaction(function () use ($validated, $companyId, $request) {
            $startDate = CarbonImmutable::parse($validated['start_date']);

            $profile = RecurringInvoiceProfile::create([
                'company_id' => $companyId,
                'customer_id' => $validated['customer_id'],
                'user_id' => $request->user()->id,
                'layout_id' => $validated['layout_id'] ?? null,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'vat_regime' => $validated['vat_regime'] ?? 'standard',
                'tax_rate' => $validated['tax_rate'] ?? 0.19,
                'payment_method' => $validated['payment_method'] ?? null,
                'payment_terms' => $validated['payment_terms'] ?? null,
                'skonto_percent' => $validated['skonto_percent'] ?? null,
                'skonto_days' => $validated['skonto_days'] ?? null,
                'due_days_after_issue' => $validated['due_days_after_issue'] ?? 14,
                'notes' => $validated['notes'] ?? null,
                'bauvorhaben' => $validated['bauvorhaben'] ?? null,
                'auftragsnummer' => $validated['auftragsnummer'] ?? null,
                'interval_unit' => $validated['interval_unit'],
                'interval_count' => $validated['interval_count'] ?? 1,
                'day_of_month' => $validated['day_of_month'] ?? null,
                'start_date' => $startDate->toDateString(),
                'end_date' => $validated['end_date'] ?? null,
                'max_occurrences' => $validated['max_occurrences'] ?? null,
                'occurrences_count' => 0,
                'next_run_date' => $startDate->toDateString(),
                'status' => 'active',
                'auto_send' => (bool) ($validated['auto_send'] ?? false),
                'email_subject_template' => $validated['email_subject_template'] ?? null,
                'email_body_template' => $validated['email_body_template'] ?? null,
            ]);

            $this->syncItems($profile, $validated['items'], $companyId);

            return $profile;
        });

        return redirect()
            ->route('recurring-invoices.show', $profile)
            ->with('success', 'Abo-Rechnung wurde erfolgreich erstellt.');
    }

    public function show(RecurringInvoiceProfile $recurringInvoice)
    {
        $this->authorize('view', $recurringInvoice);

        $recurringInvoice->load([
            'customer:id,name,email',
            'items',
            'user:id,name',
            'layout:id,name',
            'generatedInvoices' => fn ($q) => $q->select('id', 'number', 'status', 'issue_date', 'due_date', 'total', 'recurring_profile_id')
                ->limit(50),
        ]);

        return Inertia::render('recurring-invoices/show', [
            'profile' => $recurringInvoice,
            'nextRuns' => $recurringInvoice->previewNextRuns(5),
            'scheduleLabel' => $recurringInvoice->schedule_label,
        ]);
    }

    public function edit(RecurringInvoiceProfile $recurringInvoice)
    {
        $this->authorize('update', $recurringInvoice);

        $recurringInvoice->load('items');
        $companyId = $recurringInvoice->company_id;

        return Inertia::render('recurring-invoices/edit', [
            'profile' => $recurringInvoice,
            'customers' => Customer::forCompany($companyId)->active()->select('id', 'name', 'email')->get(),
            'layouts' => InvoiceLayout::forCompany($companyId)->get(),
            'products' => \App\Modules\Product\Models\Product::where('company_id', $companyId)
                ->where('status', 'active')
                ->select('id', 'name', 'description', 'price', 'unit', 'tax_rate', 'sku', 'number')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, RecurringInvoiceProfile $recurringInvoice)
    {
        $this->authorize('update', $recurringInvoice);

        $validated = $this->validatePayload($request);

        $companyId = $recurringInvoice->company_id;

        $customer = Customer::forCompany($companyId)->find($validated['customer_id']);
        if (! $customer) {
            abort(403, 'Customer does not belong to your company');
        }

        if (! empty($validated['layout_id'])) {
            $layout = InvoiceLayout::forCompany($companyId)->find($validated['layout_id']);
            if (! $layout) {
                abort(403, 'Layout does not belong to your company');
            }
        }

        DB::transaction(function () use ($validated, $recurringInvoice, $companyId) {
            $recurringInvoice->update([
                'customer_id' => $validated['customer_id'],
                'layout_id' => $validated['layout_id'] ?? null,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'vat_regime' => $validated['vat_regime'] ?? 'standard',
                'tax_rate' => $validated['tax_rate'] ?? 0.19,
                'payment_method' => $validated['payment_method'] ?? null,
                'payment_terms' => $validated['payment_terms'] ?? null,
                'skonto_percent' => $validated['skonto_percent'] ?? null,
                'skonto_days' => $validated['skonto_days'] ?? null,
                'due_days_after_issue' => $validated['due_days_after_issue'] ?? 14,
                'notes' => $validated['notes'] ?? null,
                'bauvorhaben' => $validated['bauvorhaben'] ?? null,
                'auftragsnummer' => $validated['auftragsnummer'] ?? null,
                'interval_unit' => $validated['interval_unit'],
                'interval_count' => $validated['interval_count'] ?? 1,
                'day_of_month' => $validated['day_of_month'] ?? null,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'] ?? null,
                'max_occurrences' => $validated['max_occurrences'] ?? null,
                'auto_send' => (bool) ($validated['auto_send'] ?? false),
                'email_subject_template' => $validated['email_subject_template'] ?? null,
                'email_body_template' => $validated['email_body_template'] ?? null,
            ]);

            $this->syncItems($recurringInvoice, $validated['items'], $companyId);
        });

        return redirect()
            ->route('recurring-invoices.show', $recurringInvoice)
            ->with('success', 'Abo-Rechnung wurde aktualisiert.');
    }

    public function destroy(RecurringInvoiceProfile $recurringInvoice)
    {
        $this->authorize('delete', $recurringInvoice);

        $recurringInvoice->delete();

        return redirect()
            ->route('recurring-invoices.index')
            ->with('success', 'Abo-Rechnung wurde gelöscht.');
    }

    public function pause(RecurringInvoiceProfile $recurringInvoice)
    {
        $this->authorize('update', $recurringInvoice);

        $recurringInvoice->update(['status' => RecurringInvoiceProfile::STATUS_PAUSED]);

        return back()->with('success', 'Abo-Rechnung wurde pausiert.');
    }

    public function resume(RecurringInvoiceProfile $recurringInvoice)
    {
        $this->authorize('update', $recurringInvoice);

        $recurringInvoice->update([
            'status' => RecurringInvoiceProfile::STATUS_ACTIVE,
            'paused_until' => null,
        ]);

        return back()->with('success', 'Abo-Rechnung wurde fortgesetzt.');
    }

    public function runNow(RecurringInvoiceProfile $recurringInvoice, RecurringInvoiceService $service)
    {
        $this->authorize('runNow', $recurringInvoice);

        if ($recurringInvoice->status !== RecurringInvoiceProfile::STATUS_ACTIVE) {
            return back()->with('error', 'Nur aktive Abo-Rechnungen können manuell ausgelöst werden.');
        }

        $invoice = $service->runOnce($recurringInvoice->id, CarbonImmutable::now());

        if (! $invoice) {
            return back()->with('error', 'Die Abo-Rechnung ist zurzeit nicht fällig.');
        }

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Rechnung '.$invoice->number.' wurde erfolgreich erzeugt.');
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'customer_id' => 'required|uuid|exists:customers,id',
            'layout_id' => 'nullable|uuid|exists:invoice_layouts,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'vat_regime' => 'nullable|in:standard,small_business,reverse_charge,reverse_charge_domestic,intra_community,export',
            'tax_rate' => 'nullable|numeric|min:0|max:1',
            'payment_method' => 'nullable|string|max:100',
            'payment_terms' => 'nullable|string|max:2000',
            'skonto_percent' => 'nullable|numeric|in:2,3,4,5',
            'skonto_days' => 'nullable|integer|in:7,10,14',
            'due_days_after_issue' => 'nullable|integer|min:0|max:365',
            'notes' => 'nullable|string|max:5000',
            'bauvorhaben' => 'nullable|string|max:255',
            'auftragsnummer' => 'nullable|string|max:100',
            'interval_unit' => 'required|in:day,week,month,quarter,year',
            'interval_count' => 'nullable|integer|min:1|max:365',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'max_occurrences' => 'nullable|integer|min:1|max:1000',
            'auto_send' => 'nullable|boolean',
            'email_subject_template' => 'nullable|string|max:255',
            'email_body_template' => 'nullable|string|max:5000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|uuid|exists:products,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.unit' => 'required|string|max:50',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:1',
            'items.*.discount_type' => 'nullable|in:percentage,fixed',
            'items.*.discount_value' => 'nullable|numeric|min:0',
        ], [
            'items.*.unit.required' => 'Bitte wählen Sie für jede Position eine Einheit aus.',
        ]);
    }

    private function syncItems(RecurringInvoiceProfile $profile, array $items, string $companyId): void
    {
        $profile->items()->delete();

        foreach ($items as $index => $itemData) {
            $productId = null;
            if (! empty($itemData['product_id'])) {
                $product = \App\Modules\Product\Models\Product::where('company_id', $companyId)
                    ->where('id', $itemData['product_id'])
                    ->first();
                if (! $product) {
                    abort(403, 'Product does not belong to your company');
                }
                $productId = $product->id;
            }

            $discountType = isset($itemData['discount_type'])
                && $itemData['discount_type'] !== ''
                && $itemData['discount_type'] !== 'none'
                ? $itemData['discount_type']
                : null;

            $discountValue = isset($itemData['discount_value'])
                && $itemData['discount_value'] !== ''
                && $itemData['discount_value'] !== null
                ? $itemData['discount_value']
                : null;

            RecurringInvoiceItem::create([
                'recurring_profile_id' => $profile->id,
                'product_id' => $productId,
                'description' => $itemData['description'],
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'unit' => $itemData['unit'] ?: 'Stk.',
                'tax_rate' => $itemData['tax_rate'] ?? null,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'sort_order' => $index,
            ]);
        }
    }
}
