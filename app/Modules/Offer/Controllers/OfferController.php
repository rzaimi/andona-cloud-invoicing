<?php

namespace App\Modules\Offer\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Models\InvoiceItem;
use App\Modules\Offer\Models\Offer;
use App\Modules\Offer\Models\OfferItem;
use App\Modules\Offer\Models\OfferLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class OfferController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();

        $offers = Offer::forCompany($companyId)
            ->with(['customer:id,name,email', 'user:id,name'])
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

        $companyId = $this->getEffectiveCompanyId();
        // Calculate statistics
        $stats = [
            'total' => Offer::forCompany($companyId)->count(),
            'draft' => Offer::forCompany($companyId)->where('status', 'draft')->count(),
            'sent' => Offer::forCompany($companyId)->where('status', 'sent')->count(),
            'accepted' => Offer::forCompany($companyId)->where('status', 'accepted')->count(),
            'rejected' => Offer::forCompany($companyId)->where('status', 'rejected')->count(),
            'expired' => Offer::forCompany($companyId)
                ->where('valid_until', '<', now())
                ->whereIn('status', ['sent'])
                ->count(),
        ];

        return Inertia::render('offers/index', [
            'offers' => $offers,
            'filters' => $request->only(['status', 'search']),
            'stats' => $stats,
        ]);
    }

    public function create()
    {
        $companyId = $this->getEffectiveCompanyId();
        $customers = Customer::forCompany($companyId)
            ->active()
            ->select('id', 'name', 'email')
            ->get();

        $layouts = OfferLayout::forCompany($companyId)
            ->get();

        return Inertia::render('offers/create', [
            'customers' => $customers,
            'layouts' => $layouts,
            'settings' => \App\Modules\Company\Models\Company::find($companyId)->getDefaultSettings(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'issue_date' => 'required|date',
            'valid_until' => 'required|date|after:issue_date',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'layout_id' => 'nullable|exists:offer_layouts,id',
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

            // Generate offer number
            $prefix = $company->getSetting('offer_prefix', 'AN-');
            $year = now()->year;
            $lastNumber = Offer::where('company_id', $effectiveCompanyId)
                    ->whereYear('created_at', $year)
                    ->count() + 1;
            $offerNumber = $prefix . $year . '-' . str_pad($lastNumber, 4, '0', STR_PAD_LEFT);

            // Create offer
            $offer = Offer::create([
                'number' => $offerNumber,
                'company_id' => $effectiveCompanyId,
                'customer_id' => $validated['customer_id'],
                'user_id' => $user->id,
                'issue_date' => $validated['issue_date'],
                'valid_until' => $validated['valid_until'],
                'notes' => $validated['notes'] ?? null,
                'terms_conditions' => $validated['terms'] ?? null,
                'layout_id' => $validated['layout_id'] ?? null,
                'tax_rate' => $company->getSetting('tax_rate', 0.19),
            ]);

            // Create offer items
            foreach ($validated['items'] as $index => $itemData) {
                $item = new OfferItem([
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'unit' => $itemData['unit'] ?? 'Stk.',
                    'sort_order' => $index,
                ]);
                $item->calculateTotal();
                $offer->items()->save($item);
            }

            // Calculate totals
            $offer->calculateTotals();
            $offer->save();
        });

        return redirect()->route('offers.index')
            ->with('success', 'Angebot wurde erfolgreich erstellt.');
    }

    public function show(Offer $offer)
    {
        $this->authorize('view', $offer);

        $offer->load(['customer', 'items', 'layout', 'user']);

        $companyId = $this->getEffectiveCompanyId();
        return Inertia::render('offers/show', [
            'offer' => $offer,
            'settings' => \App\Modules\Company\Models\Company::find($companyId)->getDefaultSettings(),
        ]);
    }

    public function edit(Offer $offer)
    {
        $this->authorize('update', $offer);

        $companyId = $this->getEffectiveCompanyId();
        $customers = Customer::forCompany($companyId)
            ->active()
            ->select('id', 'name', 'email')
            ->get();

        $layouts = OfferLayout::forCompany($companyId)
            ->get();

        $offer->load('items');

        return Inertia::render('offers/edit', [
            'offer' => $offer,
            'customers' => $customers,
            'layouts' => $layouts,
            'settings' => \App\Modules\Company\Models\Company::find($companyId)->getDefaultSettings(),
        ]);
    }

    public function update(Request $request, Offer $offer)
    {
        $this->authorize('update', $offer);

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'issue_date' => 'required|date',
            'valid_until' => 'required|date|after:issue_date',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'layout_id' => 'nullable|exists:offer_layouts,id',
            'status' => 'required|in:draft,sent,accepted,rejected',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.unit' => 'nullable|string|max:10',
        ]);

        DB::transaction(function () use ($validated, $offer) {
            // Update offer
            $offer->update([
                'customer_id' => $validated['customer_id'],
                'issue_date' => $validated['issue_date'],
                'valid_until' => $validated['valid_until'],
                'notes' => $validated['notes'] ?? null,
                'terms_conditions' => $validated['terms'] ?? null,
                'layout_id' => $validated['layout_id'] ?? null,
                'status' => $validated['status'],
            ]);

            // Delete existing items and create new ones
            $offer->items()->delete();

            foreach ($validated['items'] as $index => $itemData) {
                $item = new OfferItem([
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'unit' => $itemData['unit'] ?? 'Stk.',
                    'sort_order' => $index,
                ]);
                $item->calculateTotal();
                $offer->items()->save($item);
            }

            // Recalculate totals
            $offer->calculateTotals();
            $offer->save();
        });

        return redirect()->route('offers.index')
            ->with('success', 'Angebot wurde erfolgreich aktualisiert.');
    }

    public function destroy(Offer $offer)
    {
        $this->authorize('delete', $offer);

        $offer->delete();

        return redirect()->route('offers.index')
            ->with('success', 'Angebot wurde erfolgreich gelöscht.');
    }

    public function convertToInvoice(Offer $offer)
    {
        $this->authorize('update', $offer);

        if ($offer->status !== 'accepted') {
            return redirect()->back()
                ->with('error', 'Nur angenommene Angebote können in Rechnungen umgewandelt werden.');
        }

        DB::transaction(function () use ($offer) {
            $company = $offer->company;

            // Generate invoice number
            $prefix = $company->getSetting('invoice_prefix', 'RE-');
            $year = now()->year;
            $lastNumber = Invoice::where('company_id', $offer->company_id)
                    ->whereYear('created_at', $year)
                    ->count() + 1;
            $invoiceNumber = $prefix . $year . '-' . str_pad($lastNumber, 4, '0', STR_PAD_LEFT);

            // Create invoice from offer
            $invoice = Invoice::create([
                'number' => $invoiceNumber,
                'company_id' => $offer->company_id,
                'customer_id' => $offer->customer_id,
                'user_id' => $offer->user_id,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays($company->getSetting('payment_terms', 14))->toDateString(),
                'subtotal' => $offer->subtotal,
                'tax_rate' => $offer->tax_rate,
                'tax_amount' => $offer->tax_amount,
                'total' => $offer->total,
                'notes' => $offer->notes,
                'layout_id' => $offer->layout_id,
            ]);

            // Copy offer items to invoice items
            foreach ($offer->items as $offerItem) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $offerItem->description,
                    'quantity' => $offerItem->quantity,
                    'unit_price' => $offerItem->unit_price,
                    'unit' => $offerItem->unit,
                    'total' => $offerItem->total,
                    'sort_order' => $offerItem->sort_order,
                ]);
            }
        });

        return redirect()->route('invoices.index')
            ->with('success', 'Angebot wurde erfolgreich in eine Rechnung umgewandelt.');
    }

    public function pdf(Offer $offer)
    {
        $this->authorize('view', $offer);

        $offer->load(['customer', 'items', 'layout', 'user', 'company']);

        $layout = $offer->layout ?? $offer->company->defaultOfferLayout;

        $html = view('pdf.offer', [
            'layout' => $layout,
            'offer' => $offer,
            'company' => $offer->company,
            'customer' => $offer->customer,
        ])->render();

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ]);

        return $pdf->download("Angebot-{$offer->number}.pdf");
    }

    public function preview(Offer $offer)
    {
        $this->authorize('view', $offer);

        $offer->load(['customer', 'items', 'layout', 'user', 'company']);

        $layout = $offer->layout ?? $offer->company->defaultOfferLayout;

        return view('pdf.offer', [
            'layout' => $layout,
            'offer' => $offer,
            'company' => $offer->company,
            'customer' => $offer->customer,
            'preview' => true,
        ]);
    }
}

