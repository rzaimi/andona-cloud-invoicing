<?php

namespace App\Modules\Invoice\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Models\InvoiceItem;
use App\Modules\Invoice\Models\InvoiceLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();
        
        $invoices = Invoice::forCompany($companyId)
            ->with(['customer:id,name', 'user:id,name'])
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->search, function ($query, $search) {
                $query->where('number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('invoices/index', [
            'invoices' => $invoices,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function create()
    {
        $companyId = $this->getEffectiveCompanyId();
        $customers = Customer::forCompany($companyId)
            ->active()
            ->select('id', 'name', 'email')
            ->get();

        $layouts = InvoiceLayout::forCompany($companyId)
            ->get();

        return Inertia::render('invoices/create', [
            'customers' => $customers,
            'layouts' => $layouts,
            'settings' => \App\Modules\Company\Models\Company::find($this->getEffectiveCompanyId())->getDefaultSettings(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
            'layout_id' => 'nullable|exists:invoice_layouts,id',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.unit' => 'nullable|string|max:10',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $user = $request->user();
            $effectiveCompanyId = $this->getEffectiveCompanyId();
            $company = \App\Modules\Company\Models\Company::find($effectiveCompanyId);

            // Generate invoice number before creating
            $prefix = $company->getSetting('invoice_prefix', 'RE-');
            $year = now()->year;
            $lastNumber = Invoice::where('company_id', $effectiveCompanyId)
                    ->whereYear('created_at', $year)
                    ->count() + 1;
            $invoiceNumber = $prefix . $year . '-' . str_pad($lastNumber, 4, '0', STR_PAD_LEFT);

            // Create invoice
            $invoice = Invoice::create([
                'number' => $invoiceNumber,
                'company_id' => $effectiveCompanyId,
                'customer_id' => $validated['customer_id'],
                'user_id' => $user->id,
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'notes' => $validated['notes'],
                'layout_id' => $validated['layout_id'],
                'tax_rate' => $company->getSetting('tax_rate', 0.19),
            ]);

            // Create invoice items
            foreach ($validated['items'] as $index => $itemData) {
                $item = new InvoiceItem([
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'unit' => $itemData['unit'] ?? 'Stk.',
                    'sort_order' => $index,
                ]);
                $item->calculateTotal();
                $invoice->items()->save($item);
            }

            // Calculate totals
            $invoice->calculateTotals();
            $invoice->save();
        });

        return redirect()->route('invoices.index')
            ->with('success', 'Rechnung wurde erfolgreich erstellt.');
    }

    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['customer', 'items', 'layout', 'user']);

        return Inertia::render('invoices/show', [
            'invoice' => $invoice,
            'settings' => $invoice->company->getDefaultSettings(),
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

        $invoice->load('items');

        return Inertia::render('invoices/edit', [
            'invoice' => $invoice,
            'customers' => $customers,
            'layouts' => $layouts,
            'settings' => $invoice->company->getDefaultSettings(),
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
            'layout_id' => 'nullable|exists:invoice_layouts,id',
            'status' => 'required|in:draft,sent,paid,overdue,cancelled',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.unit' => 'nullable|string|max:10',
        ]);

        DB::transaction(function () use ($validated, $invoice) {
            // Update invoice
            $invoice->update([
                'customer_id' => $validated['customer_id'],
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'notes' => $validated['notes'],
                'layout_id' => $validated['layout_id'],
                'status' => $validated['status'],
            ]);

            // Delete existing items and create new ones
            $invoice->items()->delete();

            foreach ($validated['items'] as $index => $itemData) {
                $item = new InvoiceItem([
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'unit' => $itemData['unit'] ?? 'Stk.',
                    'sort_order' => $index,
                ]);
                $item->calculateTotal();
                $invoice->items()->save($item);
            }

            // Recalculate totals
            $invoice->calculateTotals();
            $invoice->save();
        });

        return redirect()->route('invoices.index')
            ->with('success', 'Rechnung wurde erfolgreich aktualisiert.');
    }

    public function destroy(Invoice $invoice)
    {
        $this->authorize('delete', $invoice);

        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('success', 'Rechnung wurde erfolgreich gelöscht.');
    }

    public function pdf(Invoice $invoice)
    {
        $invoice->load(['customer', 'items', 'layout', 'user', 'company']);

        // Get layout - either assigned to invoice or company default
        if ($invoice->layout) {
            $layout = $invoice->layout;
        } else {
            $layout = InvoiceLayout::forCompany($invoice->company_id)
                ->where('is_default', true)
                ->first();
        }

        // If no layout exists, create a minimal default layout
        if (!$layout) {
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

        $html = view('pdf.invoice', [
            'layout' => $layout,
            'invoice' => $invoice,
            'company' => $invoice->company,
            'customer' => $invoice->customer,
        ])->render();

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ]);

        return $pdf->download("Rechnung-{$invoice->number}.pdf");
    }

    public function preview(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['customer', 'items', 'layout', 'user', 'company']);

        // Get layout - either assigned to invoice or company default
        if ($invoice->layout) {
            $layout = $invoice->layout;
        } else {
            $layout = InvoiceLayout::forCompany($invoice->company_id)
                ->where('is_default', true)
                ->first();
        }

        // If no layout exists, create a minimal default layout
        if (!$layout) {
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
}
