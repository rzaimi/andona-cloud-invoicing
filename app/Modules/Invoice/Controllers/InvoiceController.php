<?php

namespace App\Modules\Invoice\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Models\InvoiceAuditLog;
use App\Modules\Invoice\Models\InvoiceItem;
use App\Modules\Invoice\Models\InvoiceLayout;
use App\Services\NumberFormatService;
use App\Traits\LogsEmails;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    use LogsEmails;

    public function index(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();

        $query = Invoice::forCompany($companyId)
            ->with(['customer:id,name', 'user:id,name'])
            ->select('invoices.*'); // Ensure all invoice fields including is_correction are selected

        // Apply status filter
        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Apply search filter
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('number', 'like', "%{$request->search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($request) {
                        $customerQuery->where('name', 'like', "%{$request->search}%");
                    });
            });
        }

        // Sorting (whitelisted)
        $sort = $request->string('sort')->toString() ?: 'issue_date';
        $direction = strtolower($request->string('direction')->toString() ?: 'desc');
        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc';

        $allowedSorts = ['number', 'issue_date', 'due_date', 'total', 'status', 'customer'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'issue_date';
        }

        if ($sort === 'customer') {
            $query->orderBy(
                Customer::select('name')
                    ->whereColumn('customers.id', 'invoices.customer_id'),
                $direction
            );
        } else {
            $query->orderBy($sort, $direction);
        }

        $invoices = $query->paginate(15)->withQueryString();

        // Calculate statistics
        $stats = [
            'total' => Invoice::forCompany($companyId)->count(),
            'draft' => Invoice::forCompany($companyId)->where('status', 'draft')->count(),
            'sent' => Invoice::forCompany($companyId)->where('status', 'sent')->count(),
            'paid' => Invoice::forCompany($companyId)->where('status', 'paid')->count(),
            'overdue' => Invoice::forCompany($companyId)->where('status', 'overdue')->count(),
            'cancelled' => Invoice::forCompany($companyId)->where('status', 'cancelled')->count(),
        ];

        return Inertia::render('invoices/index', [
            'invoices' => $invoices,
            'filters' => $request->only(['status', 'search', 'sort', 'direction']),
            'stats' => $stats,
        ]);
    }

    /**
     * Kanban-style board grouped by status. Limits to ~50 per column by
     * `issue_date desc` so the UI stays snappy — full history is still
     * available via the classic index/table view.
     */
    public function board(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();
        $perColumn = 50;

        $columns = [];
        foreach (['draft', 'sent', 'paid', 'overdue', 'cancelled'] as $status) {
            $columns[$status] = Invoice::forCompany($companyId)
                ->where('status', $status)
                ->with(['customer:id,name'])
                ->orderBy('issue_date', 'desc')
                ->limit($perColumn)
                ->get(['id', 'number', 'customer_id', 'status', 'issue_date', 'due_date', 'total']);
        }

        return Inertia::render('invoices/board', [
            'columns'     => $columns,
            'perColumn'   => $perColumn,
        ]);
    }

    /**
     * Transition an invoice's status (used by the kanban board's drop handler).
     *
     * Allowed transitions are narrow on purpose — everything else must go
     * through dedicated actions so the audit trail stays meaningful:
     *   - draft    → sent | cancelled
     *   - sent     → cancelled
     *   - overdue  → cancelled
     *
     * `paid` is deliberately NOT a valid target here: an invoice becomes paid
     * only after a matching Payment row is recorded (which flips the status
     * itself). The board redirects users to the Zahlung-erfassen flow when
     * they drop on the Paid column.
     *
     * GoBD: paid invoices are terminal. Corrections require a Stornorechnung.
     */
    public function setStatus(Request $request, Invoice $invoice)
    {
        $this->authorize('send', $invoice); // loose company-boundary check

        $validated = $request->validate([
            'status' => 'required|in:draft,sent,overdue,cancelled',
        ]);

        $allowed = [
            'draft'   => ['sent', 'cancelled'],
            'sent'    => ['cancelled'],
            'overdue' => ['cancelled'],
        ];

        $from = $invoice->status;
        $to   = $validated['status'];

        if ($from === $to) {
            return response()->json(['ok' => true]);
        }

        if (!isset($allowed[$from]) || !in_array($to, $allowed[$from], true)) {
            return response()->json([
                'ok'      => false,
                'message' => "Wechsel von \"{$from}\" nach \"{$to}\" ist nicht erlaubt. Für \"Bezahlt\" erfassen Sie bitte eine Zahlung; abgeschlossene Rechnungen werden per Stornorechnung korrigiert.",
            ], 422);
        }

        DB::transaction(function () use ($invoice, $from, $to) {
            $invoice->status = $to;
            $invoice->save();

            InvoiceAuditLog::log(
                $invoice->id,
                'status_changed',
                $from,
                $to,
                null,
                "Status via Board von {$from} auf {$to} geändert"
            );
        });

        return response()->json(['ok' => true, 'status' => $to]);
    }

    /**
     * Backfill any company-snapshot fields that were missing on the invoice
     * — typically because the field (e.g. legal_form_label, manager_title,
     * display_name) was added to the schema after the invoice was issued.
     *
     * GoBD-safe: the underlying Invoice::refreshCompanySnapshot() only fills
     * null/empty fields. Existing values stay frozen. Every refresh writes
     * an audit-log entry with the list of fields that changed.
     */
    public function refreshSnapshot(Invoice $invoice)
    {
        $this->authorize('refreshSnapshot', $invoice);

        $filled = $invoice->refreshCompanySnapshot();

        if (empty($filled)) {
            return back()->with('success', 'Firmendaten sind bereits aktuell.');
        }

        InvoiceAuditLog::log(
            $invoice->id,
            'snapshot_refreshed',
            $invoice->status,
            $invoice->status,
            ['filled_fields' => $filled],
            'Fehlende Firmendaten-Felder ergänzt: ' . implode(', ', $filled)
        );

        return back()->with(
            'success',
            count($filled) . ' Feld(er) ergänzt: ' . implode(', ', $filled)
        );
    }

    public function create()
    {
        $companyId = $this->getEffectiveCompanyId();
        $company = \App\Modules\Company\Models\Company::find($companyId);

        $customers = Customer::forCompany($companyId)
            ->active()
            ->select('id', 'name', 'email')
            ->get();

        $layouts = InvoiceLayout::forCompany($companyId)
            ->get();

        $products = \App\Modules\Product\Models\Product::where('company_id', $companyId)
            ->where('status', 'active')
            ->select('id', 'name', 'description', 'price', 'unit', 'tax_rate', 'sku', 'number')
            ->orderBy('name')
            ->get();

        $svc = new NumberFormatService;
        $allNumbers = Invoice::where('company_id', $companyId)->pluck('number');

        $stdFormat = $svc->normaliseToFormat($company->getSetting('invoice_number_format') ?? $company->getSetting('invoice_prefix', 'RE-'));
        $arFormat = $svc->normaliseToFormat($company->getSetting('abschlag_number_format') ?? 'AR-{YYYY}-{####}');
        $srFormat = $svc->normaliseToFormat($company->getSetting('schluss_number_format') ?? 'SR-{YYYY}-{####}');

        $nextNumbers = [
            'standard' => $svc->next($stdFormat, $allNumbers, null, (int) ($company->getSetting('invoice_next_counter') ?? 1)),
            'abschlagsrechnung' => $svc->next($arFormat, $allNumbers, null, (int) ($company->getSetting('abschlag_next_counter') ?? 1)),
            'schlussrechnung' => $svc->next($srFormat, $allNumbers, null, (int) ($company->getSetting('schluss_next_counter') ?? 1)),
        ];

        return Inertia::render('invoices/create', [
            'customers' => $customers,
            'layouts' => $layouts,
            'products' => $products,
            'nextNumbers' => $nextNumbers,
            'settings' => array_merge($company->getDefaultSettings(), [
                'is_small_business' => (bool) ($company->is_small_business ?? false),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Invoice::class);

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'issue_date' => 'required|date',
            'service_date' => 'nullable|date',
            'service_period_start' => 'nullable|date',
            'service_period_end' => 'nullable|date|after_or_equal:service_period_start',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
            'bauvorhaben' => 'nullable|string|max:255',
            'auftragsnummer' => 'nullable|string|max:100',
            'layout_id' => 'nullable|exists:invoice_layouts,id',
            'vat_regime' => 'nullable|in:standard,small_business,reverse_charge,reverse_charge_domestic,intra_community,export',
            // Rechnungstyp
            'invoice_type' => 'nullable|in:standard,abschlagsrechnung,schlussrechnung,nachtragsrechnung,korrekturrechnung',
            'sequence_number' => 'nullable|integer|between:1,20',
            // Abschlagsrechnungen linked to a Schlussrechnung
            'abschlag_refs' => 'nullable|array',
            'abschlag_refs.*.invoice_id' => 'required_with:abschlag_refs|string',
            'abschlag_refs.*.number' => 'required_with:abschlag_refs|string',
            'abschlag_refs.*.amount' => 'required_with:abschlag_refs|numeric|min:0',
            'abschlag_refs.*.date' => 'required_with:abschlag_refs|date',
            // Skonto
            'skonto_percent' => 'nullable|numeric|in:2,3,4,5',
            'skonto_days' => 'nullable|integer|in:7,10,14',
            // Items
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
            'items.*.unit.max' => 'Die Einheit darf maximal 50 Zeichen lang sein.',
        ]);

        // Business rule: Abschlagsrechnung requires sequence_number; others must not have one
        if (($validated['invoice_type'] ?? 'standard') === 'abschlagsrechnung') {
            $request->validate(['sequence_number' => 'required|integer|between:1,20']);
        } else {
            $validated['sequence_number'] = null;
        }

        DB::transaction(function () use ($validated, $request) {
            $user = $request->user();
            $effectiveCompanyId = $this->getEffectiveCompanyId();

            // GoBD: invoice numbers must be fortlaufend + lückenlos. Serialise
            // number generation per company by taking a pessimistic lock on the
            // company row for the duration of the transaction. Concurrent stores
            // will wait here instead of racing on `next($numbers)`.
            $company = \App\Modules\Company\Models\Company::whereKey($effectiveCompanyId)
                ->lockForUpdate()
                ->first();

            // Security: Verify customer belongs to the same company
            $customer = Customer::forCompany($effectiveCompanyId)->find($validated['customer_id']);
            if (! $customer) {
                abort(403, 'Customer does not belong to your company');
            }

            // Security: Verify layout belongs to the same company if provided
            if (! empty($validated['layout_id'])) {
                $layout = InvoiceLayout::forCompany($effectiveCompanyId)->find($validated['layout_id']);
                if (! $layout) {
                    abort(403, 'Layout does not belong to your company');
                }
            }

            // Generate invoice number using the format for the selected invoice type
            $svc = new NumberFormatService;
            $allNums = Invoice::where('company_id', $effectiveCompanyId)->pluck('number');
            $invType = $validated['invoice_type'] ?? 'standard';
            if ($invType === 'abschlagsrechnung') {
                $format = $svc->normaliseToFormat($company->getSetting('abschlag_number_format') ?? 'AR-{YYYY}-{####}');
                $minCounter = (int) ($company->getSetting('abschlag_next_counter') ?? 1);
            } elseif ($invType === 'schlussrechnung') {
                $format = $svc->normaliseToFormat($company->getSetting('schluss_number_format') ?? 'SR-{YYYY}-{####}');
                $minCounter = (int) ($company->getSetting('schluss_next_counter') ?? 1);
            } else {
                $format = $svc->normaliseToFormat($company->getSetting('invoice_number_format') ?? $company->getSetting('invoice_prefix', 'RE-'));
                $minCounter = (int) ($company->getSetting('invoice_next_counter') ?? 1);
            }
            $invoiceNumber = $svc->next($format, $allNums, null, $minCounter);

            // Create invoice
            $invoice = Invoice::create([
                'number' => $invoiceNumber,
                'company_id' => $effectiveCompanyId,
                'customer_id' => $validated['customer_id'],
                'user_id' => $user->id,
                'issue_date' => $validated['issue_date'],
                'service_date' => $validated['service_date'] ?? null,
                'service_period_start' => $validated['service_period_start'] ?? null,
                'service_period_end' => $validated['service_period_end'] ?? null,
                'due_date' => $validated['due_date'],
                'notes' => $validated['notes'],
                'bauvorhaben' => $validated['bauvorhaben'] ?? null,
                'auftragsnummer' => $validated['auftragsnummer'] ?? null,
                'abschlag_refs' => ($validated['invoice_type'] ?? 'standard') === 'schlussrechnung'
                                             ? ($validated['abschlag_refs'] ?? null)
                                             : null,
                'layout_id' => $validated['layout_id'],
                'vat_regime' => $validated['vat_regime'] ?? 'standard',
                'tax_rate' => ($validated['vat_regime'] ?? 'standard') === 'standard' ? $company->getSetting('tax_rate', 0.19) : 0,
                'invoice_type' => $validated['invoice_type'] ?? 'standard',
                'sequence_number' => $validated['sequence_number'] ?? null,
                'skonto_percent' => isset($validated['skonto_percent']) ? (float) $validated['skonto_percent'] : null,
                'skonto_days' => isset($validated['skonto_days']) ? (int) $validated['skonto_days'] : null,
            ]);

            // Save company snapshot
            $invoice->company_snapshot = $invoice->createCompanySnapshot();
            $invoice->save();

            // Create invoice items
            foreach ($validated['items'] as $index => $itemData) {
                $productId = null;
                if (! empty($itemData['product_id'])) {
                    $product = \App\Modules\Product\Models\Product::where('company_id', $effectiveCompanyId)
                        ->where('id', $itemData['product_id'])
                        ->first();
                    if (! $product) {
                        abort(403, 'Product does not belong to your company');
                    }
                    $productId = $product->id;
                }

                // Handle discount fields - convert empty strings and 'none' to null
                $discountType = isset($itemData['discount_type']) && $itemData['discount_type'] !== '' && $itemData['discount_type'] !== 'none'
                    ? $itemData['discount_type']
                    : null;
                $discountValue = isset($itemData['discount_value']) && $itemData['discount_value'] !== '' && $itemData['discount_value'] !== null
                    ? $itemData['discount_value']
                    : null;

                $item = new InvoiceItem([
                    'invoice_id' => $invoice->id,
                    'product_id' => $productId,
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'unit' => $itemData['unit'] ?? 'Stk.',
                    'tax_rate' => $itemData['tax_rate'] ?? $invoice->tax_rate, // Use item tax rate or fallback to invoice rate
                    'discount_type' => $discountType,
                    'discount_value' => $discountValue,
                    'sort_order' => $index,
                ]);
                $item->calculateTotal();
                $item->save();
            }

            // Recalculate totals, then skonto (skonto depends on the final total)
            $invoice->calculateTotals();
            $invoice->calculateSkonto();
            $invoice->save();

            // Audit Log: Invoice created
            InvoiceAuditLog::log(
                $invoice->id,
                'created',
                null,
                'draft',
                null,
                'Rechnung erstellt mit '.count($validated['items']).' Positionen'
            );
        });

        return redirect()->route('invoices.index')
            ->with('success', 'Rechnung wurde erfolgreich erstellt.');
    }

    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['customer', 'items.product', 'layout', 'user', 'documents', 'payments.createdBy']);

        // Calculate payment totals
        $paidAmount = $invoice->getPaidAmount();
        $remainingBalance = $invoice->getRemainingBalance();

        return Inertia::render('invoices/show', [
            'invoice' => $invoice,
            'settings' => $invoice->company->getDefaultSettings(),
            'paidAmount' => $paidAmount,
            'remainingBalance' => $remainingBalance,
        ]);
    }

    public function edit(Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $customers = Customer::forCompany($invoice->company_id)
            ->active()
            ->select('id', 'name', 'email')
            ->get();

        $layouts = InvoiceLayout::forCompany($invoice->company_id)
            ->get();

        $products = \App\Modules\Product\Models\Product::where('company_id', $invoice->company_id)
            ->where('status', 'active')
            ->select('id', 'name', 'description', 'price', 'unit', 'tax_rate', 'sku', 'number')
            ->orderBy('name')
            ->get();

        $invoice->load(['items.product', 'correctsInvoice', 'correctedByInvoice', 'documents']);

        return Inertia::render('invoices/edit', [
            'invoice' => $invoice,
            'customers' => $customers,
            'layouts' => $layouts,
            'products' => $products,
            'settings' => $invoice->company->getDefaultSettings(),
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        // GoBD Compliance: Prevent editing non-draft invoices
        if (! $invoice->canBeEdited()) {
            return back()->withErrors([
                'status' => 'Rechnung kann im Status "'.ucfirst($invoice->status).'" nicht bearbeitet werden. Gemäß GoBD-Richtlinien können nur Entwurfsrechnungen bearbeitet werden. Bitte erstellen Sie eine Stornorechnung für Korrekturen.',
            ])->withInput();
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'issue_date' => 'required|date',
            'service_date' => 'nullable|date',
            'service_period_start' => 'nullable|date',
            'service_period_end' => 'nullable|date|after_or_equal:service_period_start',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
            'bauvorhaben' => 'nullable|string|max:255',
            'auftragsnummer' => 'nullable|string|max:100',
            'layout_id' => 'nullable|exists:invoice_layouts,id',
            'status' => 'required|in:draft,sent,paid,overdue,cancelled',
            'vat_regime' => 'nullable|in:standard,small_business,reverse_charge,reverse_charge_domestic,intra_community,export',
            // Rechnungstyp
            'invoice_type' => 'nullable|in:standard,abschlagsrechnung,schlussrechnung,nachtragsrechnung,korrekturrechnung',
            'sequence_number' => 'nullable|integer|between:1,20',
            // Abschlagsrechnungen linked to a Schlussrechnung
            'abschlag_refs' => 'nullable|array',
            'abschlag_refs.*.invoice_id' => 'required_with:abschlag_refs|string',
            'abschlag_refs.*.number' => 'required_with:abschlag_refs|string',
            'abschlag_refs.*.amount' => 'required_with:abschlag_refs|numeric|min:0',
            'abschlag_refs.*.date' => 'required_with:abschlag_refs|date',
            // Skonto
            'skonto_percent' => 'nullable|numeric|in:2,3,4,5',
            'skonto_days' => 'nullable|integer|in:7,10,14',
            // Items
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
            'items.*.unit.max' => 'Die Einheit darf maximal 50 Zeichen lang sein.',
        ]);

        if (($validated['invoice_type'] ?? 'standard') === 'abschlagsrechnung') {
            $request->validate(['sequence_number' => 'required|integer|between:1,20']);
        } else {
            $validated['sequence_number'] = null;
        }

        DB::transaction(function () use ($validated, $invoice) {
            $effectiveCompanyId = $this->getEffectiveCompanyId();

            // Track changes for audit log
            $oldStatus = $invoice->status;
            $changes = [];

            // Security: Verify customer belongs to the same company
            $customer = Customer::forCompany($effectiveCompanyId)->find($validated['customer_id']);
            if (! $customer) {
                abort(403, 'Customer does not belong to your company');
            }

            // Security: Verify layout belongs to the same company if provided
            if (! empty($validated['layout_id'])) {
                $layout = InvoiceLayout::forCompany($effectiveCompanyId)->find($validated['layout_id']);
                if (! $layout) {
                    abort(403, 'Layout does not belong to your company');
                }
            }

            // Track key field changes
            if ($invoice->customer_id != $validated['customer_id']) {
                $changes['customer_id'] = ['old' => $invoice->customer_id, 'new' => $validated['customer_id']];
            }
            if ($invoice->status != $validated['status']) {
                $changes['status'] = ['old' => $invoice->status, 'new' => $validated['status']];
            }
            if ($invoice->notes != $validated['notes']) {
                $changes['notes'] = ['old' => $invoice->notes, 'new' => $validated['notes']];
            }

            // Update invoice
            $invoice->update([
                'customer_id' => $validated['customer_id'],
                'issue_date' => $validated['issue_date'],
                'service_date' => $validated['service_date'] ?? null,
                'service_period_start' => $validated['service_period_start'] ?? null,
                'service_period_end' => $validated['service_period_end'] ?? null,
                'due_date' => $validated['due_date'],
                'notes' => $validated['notes'],
                'bauvorhaben' => $validated['bauvorhaben'] ?? null,
                'auftragsnummer' => $validated['auftragsnummer'] ?? null,
                'abschlag_refs' => ($validated['invoice_type'] ?? 'standard') === 'schlussrechnung'
                                             ? ($validated['abschlag_refs'] ?? null)
                                             : null,
                'layout_id' => $validated['layout_id'],
                'status' => $validated['status'],
                'vat_regime' => $validated['vat_regime'] ?? 'standard',
                'tax_rate' => ($validated['vat_regime'] ?? 'standard') === 'standard' ? $invoice->company->getSetting('tax_rate', 0.19) : 0,
                'invoice_type' => $validated['invoice_type'] ?? 'standard',
                'sequence_number' => $validated['sequence_number'] ?? null,
                'skonto_percent' => isset($validated['skonto_percent']) ? (float) $validated['skonto_percent'] : null,
                'skonto_days' => isset($validated['skonto_days']) ? (int) $validated['skonto_days'] : null,
            ]);

            // Delete existing items and create new ones
            $invoice->items()->delete();

            foreach ($validated['items'] as $index => $itemData) {
                $productId = null;
                if (! empty($itemData['product_id'])) {
                    $product = \App\Modules\Product\Models\Product::where('company_id', $effectiveCompanyId)
                        ->where('id', $itemData['product_id'])
                        ->first();
                    if (! $product) {
                        abort(403, 'Product does not belong to your company');
                    }
                    $productId = $product->id;
                }

                // Handle discount fields - convert empty strings and 'none' to null
                $discountType = isset($itemData['discount_type']) && $itemData['discount_type'] !== '' && $itemData['discount_type'] !== 'none'
                    ? $itemData['discount_type']
                    : null;
                $discountValue = isset($itemData['discount_value']) && $itemData['discount_value'] !== '' && $itemData['discount_value'] !== null
                    ? $itemData['discount_value']
                    : null;

                $item = new InvoiceItem([
                    'product_id' => $productId,
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'unit' => $itemData['unit'] ?? 'Stk.',
                    'tax_rate' => $itemData['tax_rate'] ?? $invoice->tax_rate, // Use item tax rate or fallback to invoice rate
                    'discount_type' => $discountType,
                    'discount_value' => $discountValue,
                    'sort_order' => $index,
                ]);
                $item->calculateTotal();
                $invoice->items()->save($item);
            }

            // Ensure company snapshot exists (only if missing, to preserve historical data)
            if (! $invoice->company_snapshot) {
                $invoice->company_snapshot = $invoice->createCompanySnapshot();
            }

            // Recalculate totals, then skonto
            $invoice->calculateTotals();
            $invoice->calculateSkonto();
            $invoice->save();

            // Audit Log: Invoice updated
            $action = $oldStatus != $validated['status'] ? 'status_changed' : 'updated';
            InvoiceAuditLog::log(
                $invoice->id,
                $action,
                $oldStatus,
                $validated['status'],
                ! empty($changes) ? $changes : null,
                'Rechnung aktualisiert mit '.count($validated['items']).' Positionen'
            );
        });

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Rechnung wurde erfolgreich aktualisiert.');
    }

    public function destroy(Invoice $invoice)
    {
        $this->authorize('delete', $invoice);

        // GoBD: once an invoice leaves draft status it must be retained.
        // Only drafts may ever be removed, and even then we soft-delete and log.
        if (! $invoice->canBeEdited()) {
            return back()->with(
                'error',
                'Rechnung im Status "'.ucfirst($invoice->status).'" kann gemäß GoBD nicht gelöscht werden. Bitte erstellen Sie eine Stornorechnung.'
            );
        }

        DB::transaction(function () use ($invoice) {
            InvoiceAuditLog::log(
                $invoice->id,
                'deleted',
                $invoice->status,
                null,
                null,
                'Entwurfsrechnung gelöscht (soft-delete)'
            );
            $invoice->delete();
        });

        return redirect()->route('invoices.index')
            ->with('success', 'Rechnung wurde erfolgreich gelöscht.');
    }

    public function pdf(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['customer', 'items.product', 'layout', 'user', 'company', 'correctsInvoice']);

        // Get layout - either assigned to invoice or company default
        if ($invoice->layout) {
            $layout = $invoice->layout;
        } else {
            $layout = InvoiceLayout::forCompany($invoice->company_id)
                ->where('is_default', true)
                ->first();
        }

        // If no layout exists, create a minimal default layout
        if (! $layout) {
            $layout = (object) [
                'settings' => [
                    'colors' => [
                        'primary' => '#3b82f6',
                        'secondary' => '#1f2937',
                        'accent' => '#e5e7eb',
                        'text' => '#1f2937',
                    ],
                    'fonts' => [
                        'heading' => 'DejaVu Sans',
                        'body' => 'DejaVu Sans',
                        'size' => 'medium',
                    ],
                    'layout' => [
                        'margin_top' => 20,
                        'margin_right' => 20,
                        'margin_bottom' => 20,
                        'margin_left' => 20,
                        'header_height' => 120,
                        'footer_height' => 80,
                    ],
                    'branding' => [
                        'show_logo' => true,
                        'logo_position' => 'top-right',
                        'company_info_position' => 'top-left',
                        'show_header_line' => true,
                        'show_footer_line' => true,
                        'show_footer' => true,
                    ],
                    'content' => [
                        'show_company_address' => true,
                        'show_company_contact' => true,
                        'show_customer_number' => true,
                        'show_tax_number' => true,
                        'show_unit_column' => true,
                        'show_notes' => true,
                        'show_bank_details' => true,
                        'show_company_registration' => true,
                        'show_payment_terms' => true,
                        'show_item_images' => false,
                        'show_item_codes' => false,
                        'show_tax_breakdown' => false,
                        'show_payment_terms' => true,
                    ],
                ],
            ];
        }

        // Get company settings for formatting
        $settingsService = app(\App\Services\SettingsService::class);
        $settings = $settingsService->getAll($invoice->company_id);
        $formattingService = app(\App\Services\FormattingService::class);

        $html = view('pdf.invoice', [
            'layout' => $layout,
            'invoice' => $invoice,
            'company' => $invoice->company,
            'customer' => $invoice->customer,
            'settings' => $settings,
            'formattingService' => $formattingService,
        ])->render();

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                // SECURITY: disable remote fetch (SSRF), local file reads, and
                // embedded PHP evaluation (RCE). Logos are inlined as base64
                // data-URIs in the Blade templates so no remote/local access
                // is needed. JS is already disabled.
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'enable-local-file-access' => false,
                'isPhpEnabled' => false,
                'dpi' => 96,
            ]);

        return $pdf->download("Rechnung-{$invoice->number}.pdf");
    }

    public function preview(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['customer', 'items.product', 'layout', 'user', 'company']);

        // Get layout - either assigned to invoice or company default
        if ($invoice->layout) {
            $layout = $invoice->layout;
            // Ensure settings and template are properly set
            if (! $layout->settings) {
                $layout->settings = [];
            }
            if (! $layout->template) {
                $layout->template = 'clean';
            }
        } else {
            $layout = InvoiceLayout::forCompany($invoice->company_id)
                ->where('is_default', true)
                ->first();

            if ($layout) {
                // Ensure settings and template are properly set
                if (! $layout->settings) {
                    $layout->settings = [];
                }
                if (! $layout->template) {
                    $layout->template = 'clean';
                }
            }
        }

        // If no layout exists, create a minimal default layout
        if (! $layout) {
            $layout = (object) [
                'template' => 'clean',
                'settings' => [
                    'colors' => [
                        'primary' => '#3b82f6',
                        'secondary' => '#1f2937',
                        'accent' => '#e5e7eb',
                        'text' => '#1f2937',
                    ],
                    'fonts' => [
                        'heading' => 'DejaVu Sans',
                        'body' => 'DejaVu Sans',
                        'size' => 'medium',
                    ],
                    'layout' => [
                        'margin_top' => 20,
                        'margin_right' => 20,
                        'margin_bottom' => 20,
                        'margin_left' => 20,
                        'header_height' => 120,
                        'footer_height' => 80,
                    ],
                    'branding' => [
                        'show_logo' => true,
                        'logo_position' => 'top-right',
                        'company_info_position' => 'top-left',
                        'show_header_line' => true,
                        'show_footer_line' => true,
                        'show_footer' => true,
                    ],
                    'content' => [
                        'show_company_address' => true,
                        'show_company_contact' => true,
                        'show_customer_number' => true,
                        'show_tax_number' => true,
                        'show_unit_column' => true,
                        'show_notes' => true,
                        'show_bank_details' => true,
                        'show_company_registration' => true,
                        'show_payment_terms' => true,
                        'show_item_images' => false,
                        'show_item_codes' => false,
                        'show_tax_breakdown' => false,
                    ],
                ],
            ];
        }

        return view('pdf.invoice', [
            'layout' => $layout,
            'invoice' => $invoice,
            'company' => $invoice->company,
            'customer' => $invoice->customer,
            'preview' => true,
        ]);
    }

    public function send(Request $request, Invoice $invoice, \App\Services\InvoiceMailer $mailer)
    {
        $this->authorize('send', $invoice);

        $validated = $request->validate([
            'to' => 'required|email',
            'cc' => 'nullable|email',
            'subject' => 'required|string|max:255',
            'message' => 'nullable|string|max:2000',
        ]);

        if (! $invoice->customer || ! $invoice->customer->email) {
            return back()->withErrors(['email' => 'Kunde hat keine E-Mail-Adresse hinterlegt.']);
        }

        $result = $mailer->send(
            invoice: $invoice,
            to: $validated['to'],
            subject: $validated['subject'] ?: "Rechnung {$invoice->number}",
            customMessage: $validated['message'] ?? null,
            cc: $validated['cc'] ?? null,
        );

        if (! $result['ok']) {
            return back()->withErrors([
                'email' => 'E-Mail konnte nicht versendet werden: '.($result['error'] ?? 'Unbekannter Fehler'),
            ]);
        }

        return redirect()->back()->with('success', 'Rechnung wurde erfolgreich per E-Mail versendet.');
    }

    private function generateInvoicePdf(Invoice $invoice)
    {
        $invoice->load(['items.product', 'customer', 'company', 'user', 'correctsInvoice']);

        $layout = $invoice->layout ?: InvoiceLayout::forCompany($invoice->company_id)
            ->where('is_default', true)
            ->first();

        if ($layout) {
            // Ensure settings and template are set
            if (! $layout->settings) {
                $layout->settings = [];
            }
            if (! $layout->template) {
                $layout->template = 'clean';
            }
        }

        if (! $layout) {
            $layout = (object) [
                'template' => 'clean',
                'settings' => [
                    'colors' => [
                        'primary' => '#3B82F6',
                        'secondary' => '#64748B',
                        'accent' => '#F59E0B',
                    ],
                    'fonts' => [
                        'heading' => 'DejaVu Sans',
                        'body' => 'DejaVu Sans',
                        'size' => 'medium',
                    ],
                    'layout' => [
                        'margin_top' => 20,
                        'margin_right' => 20,
                        'margin_bottom' => 20,
                        'margin_left' => 20,
                        'header_height' => 120,
                        'footer_height' => 80,
                    ],
                    'branding' => [
                        'show_logo' => true,
                        'logo_position' => 'top-right',
                        'company_info_position' => 'top-left',
                        'show_header_line' => true,
                        'show_footer_line' => true,
                        'show_footer' => true,
                    ],
                    'content' => [
                        'show_company_address' => true,
                        'show_company_contact' => true,
                        'show_customer_number' => true,
                        'show_tax_number' => true,
                        'show_unit_column' => true,
                        'show_notes' => true,
                        'show_bank_details' => true,
                        'show_company_registration' => true,
                        'show_payment_terms' => true,
                        'show_item_images' => false,
                        'show_item_codes' => false,
                        'show_tax_breakdown' => false,
                    ],
                ],
            ];
        }

        // Get company settings for formatting
        $settingsService = app(\App\Services\SettingsService::class);
        $settings = $settingsService->getAll($invoice->company_id);
        $formattingService = app(\App\Services\FormattingService::class);

        return Pdf::loadView('pdf.invoice', [
            'layout' => $layout,
            'invoice' => $invoice,
            'company' => $invoice->company,
            'customer' => $invoice->customer,
            'settings' => $settings,
            'formattingService' => $formattingService,
        ])
            ->setPaper('a4')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                // SECURITY: see pdf() above — no remote/local/PHP/JS access needed.
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'enable-local-file-access' => false,
                'enable-javascript' => false,
                'isPhpEnabled' => false,
                'dpi' => 96,
            ]);
    }

    /**
     * Manually send next reminder for an invoice (Mahnung)
     */
    public function sendReminder(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        // Check if invoice can receive another reminder
        if (! $invoice->canSendNextReminder()) {
            return redirect()->back()->with('error', 'Diese Rechnung kann keine weiteren Mahnungen erhalten.');
        }

        $companyId = $this->getEffectiveCompanyId();
        $company = \App\Modules\Company\Models\Company::find($companyId);

        // Check if company has SMTP configured
        if (! $company->smtp_host || ! $company->smtp_username) {
            return redirect()->back()->with('error', 'E-Mail Einstellungen sind nicht konfiguriert.');
        }

        // Check if customer has email
        if (! $invoice->customer || ! $invoice->customer->email) {
            return redirect()->back()->with('error', 'Kunde hat keine E-Mail-Adresse.');
        }

        try {
            // Configure SMTP
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host', $company->smtp_host);
            Config::set('mail.mailers.smtp.port', $company->smtp_port);
            Config::set('mail.mailers.smtp.username', $company->smtp_username);
            Config::set('mail.mailers.smtp.password', $company->smtp_password);
            Config::set('mail.mailers.smtp.encryption', $company->smtp_encryption ?: 'tls');
            Config::set('mail.from.address', $company->smtp_from_address ?: $company->email);
            Config::set('mail.from.name', $company->smtp_from_name ?: $company->name);

            // Get next reminder level and fee
            $nextLevel = $invoice->getNextReminderLevel();
            $fee = $this->getReminderFee($nextLevel, $company);

            // Send the reminder email
            $this->sendMahnungEmail($invoice, $company, $nextLevel, $fee);

            // Update invoice
            $invoice->addReminderToHistory($nextLevel, $fee);
            if ($nextLevel > Invoice::REMINDER_FRIENDLY) {
                $invoice->status = 'overdue';
            }
            $invoice->save();

            $levelName = $invoice->getReminderLevelNameForLevel($nextLevel);

            return redirect()->back()->with('success', "{$levelName} wurde erfolgreich versendet.");

        } catch (\Exception $e) {
            Log::error("Manual reminder failed: {$e->getMessage()}", [
                'invoice_id' => $invoice->id,
            ]);

            return redirect()->back()->with('error', 'Fehler beim Versenden der Mahnung: '.$e->getMessage());
        }
    }

    /**
     * Get reminder fee for a specific level
     */
    private function getReminderFee(int $level, $company): float
    {
        return match ($level) {
            Invoice::REMINDER_MAHNUNG_1 => (float) $company->getSetting('reminder_mahnung1_fee', 5.00),
            Invoice::REMINDER_MAHNUNG_2 => (float) $company->getSetting('reminder_mahnung2_fee', 10.00),
            Invoice::REMINDER_MAHNUNG_3 => (float) $company->getSetting('reminder_mahnung3_fee', 15.00),
            default => 0.00,
        };
    }

    /**
     * Send Mahnung email (same as in SendDailyReminders command)
     */
    private function sendMahnungEmail(Invoice $invoice, $company, int $level, float $fee)
    {
        $invoice->load(['items.product', 'customer', 'company', 'user']);

        // Determine which email template to use
        $template = match ($level) {
            Invoice::REMINDER_FRIENDLY => 'emails.reminders.friendly',
            Invoice::REMINDER_MAHNUNG_1 => 'emails.reminders.mahnung-1',
            Invoice::REMINDER_MAHNUNG_2 => 'emails.reminders.mahnung-2',
            Invoice::REMINDER_MAHNUNG_3 => 'emails.reminders.mahnung-3',
            Invoice::REMINDER_INKASSO => 'emails.reminders.inkasso',
            default => 'emails.reminders.friendly',
        };

        // Determine subject
        $subject = match ($level) {
            Invoice::REMINDER_FRIENDLY => "Freundliche Zahlungserinnerung - Rechnung {$invoice->number}",
            Invoice::REMINDER_MAHNUNG_1 => "1. Mahnung - Rechnung {$invoice->number}",
            Invoice::REMINDER_MAHNUNG_2 => "2. Mahnung - Rechnung {$invoice->number}",
            Invoice::REMINDER_MAHNUNG_3 => "3. und LETZTE Mahnung - Rechnung {$invoice->number}",
            Invoice::REMINDER_INKASSO => "Inkassoankündigung - Rechnung {$invoice->number}",
            default => "Zahlungserinnerung - Rechnung {$invoice->number}",
        };

        // Generate PDF
        $pdf = $this->generateInvoicePdf($invoice);

        // Calculate additional data for Inkasso level
        $inkassoFee = $level == Invoice::REMINDER_INKASSO ? 50.00 : 0;
        $delayInterest = $level == Invoice::REMINDER_INKASSO
            ? $invoice->total * 0.09 * ($invoice->getDaysOverdue() / 365)
            : 0;

        Mail::send($template, [
            'invoice' => $invoice,
            'company' => $company,
            'fee' => $fee,
            'inkassoFee' => $inkassoFee,
            'delayInterest' => $delayInterest,
        ], function ($message) use ($invoice, $pdf, $subject) {
            $message->to($invoice->customer->email);
            $message->subject($subject);
            $message->attachData($pdf->output(), "Rechnung_{$invoice->number}.pdf", [
                'mime' => 'application/pdf',
            ]);
        });

        // Log the mahnung email
        $this->logEmail(
            companyId: $company->id,
            recipientEmail: $invoice->customer->email,
            subject: $subject,
            type: 'mahnung',
            customerId: $invoice->customer_id,
            recipientName: $invoice->customer->name,
            relatedType: 'Invoice',
            relatedId: $invoice->id,
            metadata: [
                'reminder_level' => $level,
                'reminder_level_name' => $invoice->getReminderLevelNameForLevel($level),
                'invoice_number' => $invoice->number,
                'invoice_total' => $invoice->total,
                'reminder_fee' => $fee,
                'days_overdue' => $invoice->getDaysOverdue(),
                'has_pdf_attachment' => true,
            ]
        );
    }

    /**
     * View audit log for an invoice
     */
    public function auditLog(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $logs = $invoice->auditLogs()
            ->with('user:id,name,email')
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'old_status' => $log->old_status,
                    'new_status' => $log->new_status,
                    'changes' => $log->changes,
                    'notes' => $log->notes,
                    'user' => $log->user ? [
                        'name' => $log->user->name,
                        'email' => $log->user->email,
                    ] : null,
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at->toISOString(),
                ];
            });

        return response()->json([
            'logs' => $logs,
        ]);
    }

    /**
     * View reminder history for an invoice
     */
    public function reminderHistory(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        return response()->json([
            'reminder_level' => $invoice->reminder_level,
            'reminder_level_name' => $invoice->reminder_level_name,
            'last_reminder_sent_at' => $invoice->last_reminder_sent_at,
            'reminder_fee' => $invoice->reminder_fee,
            'reminder_history' => $invoice->reminder_history ?? [],
            'days_overdue' => $invoice->getDaysOverdue(),
            'can_send_next' => $invoice->canSendNextReminder(),
            'next_level' => $invoice->canSendNextReminder() ? $invoice->getNextReminderLevel() : null,
            'next_level_name' => $invoice->canSendNextReminder()
                ? $invoice->getReminderLevelNameForLevel($invoice->getNextReminderLevel())
                : null,
        ]);
    }

    /**
     * Download invoice as XRechnung (XML)
     */
    public function downloadXRechnung(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $eRechnungService = app(\App\Services\ERechnungService::class);
        $result = $eRechnungService->downloadXRechnung($invoice);

        return response($result['content'], 200, [
            'Content-Type' => $result['mime_type'],
            'Content-Disposition' => 'attachment; filename="'.$result['filename'].'"',
        ]);
    }

    /**
     * Download invoice as ZUGFeRD (PDF with embedded XML)
     */
    public function downloadZugferd(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $eRechnungService = app(\App\Services\ERechnungService::class);
        $result = $eRechnungService->downloadZugferd($invoice);

        return response($result['content'], 200, [
            'Content-Type' => $result['mime_type'],
            'Content-Disposition' => 'attachment; filename="'.$result['filename'].'"',
        ]);
    }

    /**
     * Create a correction invoice (Stornorechnung)
     */
    public function createCorrection(Request $request, Invoice $invoice)
    {
        // Requires special permission to create Stornorechnung
        $this->authorize('createCorrection', $invoice);

        // Validate that invoice can be corrected
        if (! $invoice->canBeCorrect()) {
            return back()->with('error', 'Diese Rechnung kann nicht storniert werden.');
        }

        $validated = $request->validate([
            'correction_reason' => 'required|string|max:500',
            'create_new_invoice' => 'boolean',
            'send_email' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            // Load relationships
            $invoice->load(['customer', 'items.product', 'company']);

            // GoBD: serialise Stornorechnung numbering alongside the normal
            // invoice counter by locking the company row for the duration of
            // the transaction (same pattern as store()).
            \App\Modules\Company\Models\Company::whereKey($invoice->company_id)
                ->lockForUpdate()
                ->first();

            // Create the correction invoice (Stornorechnung)
            $correctionInvoice = new Invoice;
            $correctionInvoice->company_id = $invoice->company_id;
            $correctionInvoice->customer_id = $invoice->customer_id;
            $correctionInvoice->user_id = $request->user()->id;
            $correctionInvoice->status = 'sent'; // Correction invoices are immediately sent
            $correctionInvoice->issue_date = now();
            $correctionInvoice->due_date = now()->addDays(14);
            $correctionInvoice->tax_rate = $invoice->tax_rate;
            $correctionInvoice->notes = 'Stornorechnung für '.$invoice->number."\nGrund: ".$validated['correction_reason'];
            $correctionInvoice->payment_method = $invoice->payment_method;
            $correctionInvoice->payment_terms = $invoice->payment_terms;
            $correctionInvoice->layout_id = $invoice->layout_id;

            // Correction invoice fields
            $correctionInvoice->is_correction = true;
            $correctionInvoice->corrects_invoice_id = $invoice->id;
            $correctionInvoice->correction_reason = $validated['correction_reason'];

            // Negative amounts (canceling the original)
            $correctionInvoice->subtotal = -$invoice->subtotal;
            $correctionInvoice->tax_amount = -$invoice->tax_amount;
            $correctionInvoice->total = -$invoice->total;

            // Generate correction (STORNO) number using the company's storno_number_format setting
            $correctionInvoice->number = $correctionInvoice->generateCorrectionNumber();

            // Now save with the number
            $correctionInvoice->save();

            // Copy items with negative quantities
            foreach ($invoice->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $correctionInvoice->id,
                    'product_id' => $item->product_id,
                    'description' => $item->description,
                    'quantity' => -$item->quantity, // Negative quantity
                    'unit_price' => $item->unit_price,
                    'unit' => $item->unit,
                    'tax_rate' => $item->tax_rate,
                    'total' => -$item->total, // Negative total
                    'sort_order' => $item->sort_order,
                ]);
            }

            // Mark original invoice as corrected
            $oldInvoiceStatus = $invoice->status;
            $invoice->corrected_by_invoice_id = $correctionInvoice->id;
            $invoice->corrected_at = now();
            $invoice->status = 'cancelled';
            $invoice->save();

            // Audit Logs
            // Log original invoice correction
            InvoiceAuditLog::log(
                $invoice->id,
                'corrected',
                $oldInvoiceStatus,
                'cancelled',
                ['corrected_by' => $correctionInvoice->id],
                'Rechnung korrigiert durch Stornorechnung. Grund: '.$validated['correction_reason']
            );

            // Log new correction invoice creation
            InvoiceAuditLog::log(
                $correctionInvoice->id,
                'created',
                null,
                'sent',
                ['corrects' => $invoice->id, 'reason' => $validated['correction_reason']],
                'Stornorechnung erstellt für Rechnung '.$invoice->number
            );

            DB::commit();

            // Send email if requested
            $emailSent = false;
            if ($validated['send_email'] ?? false) {
                try {
                    $company = $correctionInvoice->company;
                    if ($company->smtp_host && $company->smtp_username && $correctionInvoice->customer && $correctionInvoice->customer->email) {
                        // Configure SMTP settings dynamically
                        Config::set('mail.default', 'smtp');
                        Config::set('mail.mailers.smtp.host', $company->smtp_host);
                        Config::set('mail.mailers.smtp.port', $company->smtp_port);
                        Config::set('mail.mailers.smtp.username', $company->smtp_username);
                        Config::set('mail.mailers.smtp.password', $company->smtp_password);
                        Config::set('mail.mailers.smtp.encryption', $company->smtp_encryption ?: 'tls');
                        Config::set('mail.from.address', $company->smtp_from_address ?: $company->email);
                        Config::set('mail.from.name', $company->smtp_from_name ?: $company->name);

                        // Generate PDF
                        $pdf = $this->generateInvoicePdf($correctionInvoice);

                        // Send email
                        Mail::send('emails.invoice-sent', [
                            'invoice' => $correctionInvoice,
                            'company' => $company,
                            'customer' => $correctionInvoice->customer,
                            'message' => 'Die Stornorechnung '.$correctionInvoice->number.' wurde erstellt und storniert die Rechnung '.$invoice->number.'.',
                        ], function ($message) use ($correctionInvoice, $pdf) {
                            $message->to($correctionInvoice->customer->email)
                                ->subject('Stornorechnung '.$correctionInvoice->number)
                                ->attachData($pdf->output(), 'Stornorechnung_'.$correctionInvoice->number.'.pdf', [
                                    'mime' => 'application/pdf',
                                ]);
                        });

                        // Log email
                        $this->logEmail('invoice-sent', $correctionInvoice->customer->email, $correctionInvoice->id, $correctionInvoice->company_id);
                        $emailSent = true;
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to send correction invoice email: '.$e->getMessage());
                    // Don't fail the entire operation if email fails
                }
            }

            $successMessage = 'Stornorechnung '.$correctionInvoice->number.' wurde erfolgreich erstellt. Die ursprüngliche Rechnung '.$invoice->number.' wurde storniert.';
            if ($emailSent) {
                $successMessage .= ' Die Stornorechnung wurde per E-Mail versendet.';
            }

            return redirect()->route('invoices.edit', $correctionInvoice->id)
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice correction failed: '.$e->getMessage());

            return back()->with('error', 'Fehler beim Erstellen der Stornorechnung: '.$e->getMessage());
        }
    }

    /**
     * Return Abschlagsrechnungen for a given customer that can be linked to a Schlussrechnung.
     * Excludes invoices that are already referenced by another Schlussrechnung.
     */
    public function selectableAbschlaege(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();
        $customerId = $request->query('customer_id');
        $excludeId = $request->query('exclude_invoice_id'); // current Schlussrechnung being edited

        $query = Invoice::forCompany($companyId)
            ->where('invoice_type', 'abschlagsrechnung')
            ->whereNotNull('customer_id');

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        $abschlaege = $query
            ->select('id', 'number', 'total', 'issue_date', 'sequence_number', 'status', 'bauvorhaben')
            ->orderBy('sequence_number')
            ->orderBy('issue_date')
            ->get();

        // Find IDs already claimed by any other Schlussrechnung
        $allSchluss = Invoice::forCompany($companyId)
            ->where('invoice_type', 'schlussrechnung')
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->whereNotNull('abschlag_refs')
            ->pluck('abschlag_refs');

        $claimedIds = collect($allSchluss)
            ->flatten(1)
            ->pluck('invoice_id')
            ->filter()
            ->unique()
            ->values();

        return response()->json(
            $abschlaege->map(fn ($inv) => [
                'id' => $inv->id,
                'number' => $inv->number,
                'amount' => (float) $inv->total,
                'date' => $inv->issue_date?->format('Y-m-d'),
                'sequence_number' => $inv->sequence_number,
                'status' => $inv->status,
                'bauvorhaben' => $inv->bauvorhaben,
                'already_claimed' => $claimedIds->contains($inv->id),
            ])
        );
    }
}
