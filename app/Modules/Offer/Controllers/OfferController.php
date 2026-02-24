<?php

namespace App\Modules\Offer\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Models\InvoiceItem;
use App\Modules\Offer\Models\Offer;
use App\Modules\Offer\Models\OfferItem;
use App\Modules\Offer\Models\OfferLayout;
use App\Services\NumberFormatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class OfferController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();

        $query = Offer::forCompany($companyId)
            ->with(['customer:id,name,email', 'user:id,name', 'convertedToInvoice:id,number']);
        
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

        $allowedSorts = ['number', 'issue_date', 'valid_until', 'total', 'status', 'customer'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'issue_date';
        }

        if ($sort === 'customer') {
            $query->orderBy(
                Customer::select('name')
                    ->whereColumn('customers.id', 'offers.customer_id'),
                $direction
            );
        } else {
            $query->orderBy($sort, $direction);
        }

        $offers = $query->paginate(15)->withQueryString();

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
            'filters' => $request->only(['status', 'search', 'sort', 'direction']),
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

        $products = \App\Modules\Product\Models\Product::where('company_id', $companyId)
            ->where('status', 'active')
            ->select('id', 'name', 'description', 'price', 'unit', 'tax_rate', 'sku', 'number')
            ->orderBy('name')
            ->get();

        return Inertia::render('offers/create', [
            'customers' => $customers,
            'layouts' => $layouts,
            'products' => $products,
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
            'items.*.product_id' => 'nullable|uuid|exists:products,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.unit' => 'nullable|string|max:10',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:1', // German tax rates: 0.00, 0.07, 0.19
            'items.*.discount_type' => 'nullable|in:percentage,fixed',
            'items.*.discount_value' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $user = $request->user();
            $effectiveCompanyId = $this->getEffectiveCompanyId();
            $company = \App\Modules\Company\Models\Company::find($effectiveCompanyId);

            // Generate offer number using dynamic format setting
            $svc    = new NumberFormatService();
            $format = $svc->normaliseToFormat(
                $company->getSetting('offer_number_format')
                    ?? $company->getSetting('offer_prefix', 'AN-')
            );
            $offerNumber = $svc->next($format, Offer::where('company_id', $effectiveCompanyId)->pluck('number'));

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

            // Save company snapshot
            $offer->company_snapshot = $offer->createCompanySnapshot();
            $offer->save();
            
            // Create offer items
            foreach ($validated['items'] as $index => $itemData) {
                $productId = null;
                if (!empty($itemData['product_id'])) {
                    $product = \App\Modules\Product\Models\Product::where('company_id', $effectiveCompanyId)
                        ->where('id', $itemData['product_id'])
                        ->first();
                    if (!$product) {
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
                
                $item = new OfferItem([
                    'offer_id' => $offer->id,
                    'product_id' => $productId,
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'unit' => $itemData['unit'] ?? 'Stk.',
                    'tax_rate' => $itemData['tax_rate'] ?? $offer->tax_rate, // Use item tax rate or fallback to offer rate
                    'discount_type' => $discountType,
                    'discount_value' => $discountValue,
                    'sort_order' => $index,
                ]);
                $item->calculateTotal();
                $item->save();
            }

            // Calculate totals - calculateTotals() will load items if needed
            $offer->calculateTotals();
            $offer->save();
        });

        return redirect()->route('offers.index')
            ->with('success', 'Angebot wurde erfolgreich erstellt.');
    }

    public function show(Offer $offer)
    {
        $this->authorize('view', $offer);

        $offer->load(['customer', 'items.product', 'layout', 'user', 'convertedToInvoice:id,number']);

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

        $products = \App\Modules\Product\Models\Product::where('company_id', $companyId)
            ->where('status', 'active')
            ->select('id', 'name', 'description', 'price', 'unit', 'tax_rate', 'sku', 'number')
            ->orderBy('name')
            ->get();

        $offer->load('items');

        return Inertia::render('offers/edit', [
            'offer' => $offer,
            'customers' => $customers,
            'layouts' => $layouts,
            'products' => $products,
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
            'items.*.product_id' => 'nullable|uuid|exists:products,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.unit' => 'nullable|string|max:10',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:1', // German tax rates: 0.00, 0.07, 0.19
            'items.*.discount_type' => 'nullable|in:percentage,fixed',
            'items.*.discount_value' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $offer) {
            $effectiveCompanyId = $this->getEffectiveCompanyId();

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
                $productId = null;
                if (!empty($itemData['product_id'])) {
                    $product = \App\Modules\Product\Models\Product::where('company_id', $effectiveCompanyId)
                        ->where('id', $itemData['product_id'])
                        ->first();
                    if (!$product) {
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
                
                $item = new OfferItem([
                    'product_id' => $productId,
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'unit' => $itemData['unit'] ?? 'Stk.',
                    'tax_rate' => $itemData['tax_rate'] ?? $offer->tax_rate, // Use item tax rate or fallback to offer rate
                    'discount_type' => $discountType,
                    'discount_value' => $discountValue,
                    'sort_order' => $index,
                ]);
                $item->calculateTotal();
                $offer->items()->save($item);
            }

            // Ensure company snapshot exists (only if missing, to preserve historical data)
            if (!$offer->company_snapshot) {
                $offer->company_snapshot = $offer->createCompanySnapshot();
            }

            // Recalculate totals
            $offer->calculateTotals();
            $offer->save();
        });

        return redirect()->route('offers.show', $offer)
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

            // Generate invoice number using dynamic format setting
            $svc    = new NumberFormatService();
            $format = $svc->normaliseToFormat(
                $company->getSetting('invoice_number_format')
                    ?? $company->getSetting('invoice_prefix', 'RE-')
            );
            $invoiceNumber = $svc->next($format, Invoice::where('company_id', $offer->company_id)->pluck('number'));

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

            // Save company snapshot for the new invoice
            $invoice->company_snapshot = $invoice->createCompanySnapshot();
            $invoice->save();

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

            // Mark offer as converted
            $offer->update(['converted_to_invoice_id' => $invoice->id]);
        });

        return redirect()->route('invoices.index')
            ->with('success', 'Angebot wurde erfolgreich in eine Rechnung umgewandelt.');
    }

    public function accept(Offer $offer)
    {
        $this->authorize('update', $offer);

        if ($offer->status !== 'sent') {
            return redirect()->back()
                ->with('error', 'Nur versendete Angebote können als angenommen markiert werden.');
        }

        $offer->update(['status' => 'accepted']);

        return redirect()->route('offers.show', $offer)
            ->with('success', 'Angebot wurde als angenommen markiert.');
    }

    public function reject(Offer $offer)
    {
        $this->authorize('update', $offer);

        if ($offer->status !== 'sent') {
            return redirect()->back()
                ->with('error', 'Nur versendete Angebote können als abgelehnt markiert werden.');
        }

        $offer->update(['status' => 'rejected']);

        return redirect()->route('offers.show', $offer)
            ->with('success', 'Angebot wurde als abgelehnt markiert.');
    }

    public function pdf(Offer $offer)
    {
        $this->authorize('view', $offer);

        $offer->load(['customer', 'items.product', 'layout', 'user', 'company']);

        $layout = $offer->layout ?? $offer->company->defaultOfferLayout;

        // Get company settings for formatting
        $settingsService = app(\App\Services\SettingsService::class);
        $settings = $settingsService->getAll($offer->company_id);
        $formattingService = app(\App\Services\FormattingService::class);

        $html = view('pdf.offer', [
            'layout' => $layout,
            'offer' => $offer,
            'company' => $offer->company,
            'customer' => $offer->customer,
            'settings' => $settings,
            'formattingService' => $formattingService,
        ])->render();

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
            ]);

        return $pdf->download("Angebot-{$offer->number}.pdf");
    }

    public function preview(Offer $offer)
    {
        $this->authorize('view', $offer);

        $offer->load(['customer', 'items.product', 'layout', 'user', 'company']);

        $layout = $offer->layout ?? $offer->company->defaultOfferLayout;

        // Get company settings for formatting
        $settingsService = app(\App\Services\SettingsService::class);
        $settings = $settingsService->getAll($offer->company_id);
        $formattingService = app(\App\Services\FormattingService::class);

        return view('pdf.offer', [
            'layout' => $layout,
            'offer' => $offer,
            'company' => $offer->company,
            'customer' => $offer->customer,
            'settings' => $settings,
            'formattingService' => $formattingService,
            'preview' => true,
        ]);
    }

    public function send(Request $request, Offer $offer)
    {
        $this->authorize('update', $offer);

        $validated = $request->validate([
            'to' => 'required|email',
            'cc' => 'nullable|email',
            'subject' => 'required|string|max:255',
            'message' => 'nullable|string|max:2000',
        ]);

        $companyId = $this->getEffectiveCompanyId();
        $company = \App\Modules\Company\Models\Company::find($companyId);

        // Validate customer email exists
        if (!$offer->customer || !$offer->customer->email) {
            return back()->withErrors(['email' => 'Kunde hat keine E-Mail-Adresse hinterlegt.']);
        }

        // Check if SMTP is configured
        if (!$company->smtp_host || !$company->smtp_username) {
            return back()->withErrors(['email' => 'SMTP-Einstellungen sind nicht konfiguriert. Bitte konfigurieren Sie die E-Mail-Einstellungen.']);
        }

        try {
            // Configure SMTP settings dynamically for this company
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host', $company->smtp_host);
            Config::set('mail.mailers.smtp.port', $company->smtp_port);
            Config::set('mail.mailers.smtp.username', $company->smtp_username);
            Config::set('mail.mailers.smtp.password', $company->smtp_password);
            Config::set('mail.mailers.smtp.encryption', $company->smtp_encryption ?: 'tls');
            Config::set('mail.from.address', $company->smtp_from_address ?: $company->email);
            Config::set('mail.from.name', $company->smtp_from_name ?: $company->name);

            // Generate PDF
            $pdf = $this->generateOfferPdf($offer);

            // Send email
            Mail::send('emails.offer-sent', [
                'offer' => $offer,
                'company' => $company,
                'customMessage' => $validated['message'] ?? null,
            ], function ($message) use ($validated, $offer, $pdf, $company) {
                $message->to($validated['to']);
                
                if (!empty($validated['cc'])) {
                    $message->cc($validated['cc']);
                }
                
                $message->subject($validated['subject'] ?: "Angebot {$offer->number}");
                $message->attachData($pdf->output(), "Angebot_{$offer->number}.pdf", [
                    'mime' => 'application/pdf',
                ]);
            });

            // Update offer status to 'sent' if it's still 'draft'
            if ($offer->status === 'draft') {
                $offer->update(['status' => 'sent']);
            }

            return redirect()->back()->with('success', 'Angebot wurde erfolgreich per E-Mail versendet.');

        } catch (\Exception $e) {
            Log::error('Failed to send offer email: ' . $e->getMessage());
            return back()->withErrors(['email' => 'E-Mail konnte nicht versendet werden: ' . $e->getMessage()]);
        }
    }

    private function generateOfferPdf(Offer $offer)
    {
        $offer->load(['items.product', 'customer', 'company', 'user']);
        
        $layout = $offer->layout ?: OfferLayout::forCompany($offer->company_id)
            ->where('is_default', true)
            ->first();

        if (!$layout) {
            $layout = (object) [
                'template' => 'minimal',
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
        $settings = $settingsService->getAll($offer->company_id);
        $formattingService = app(\App\Services\FormattingService::class);

        return Pdf::loadView('pdf.offer', [
            'layout' => $layout,
            'offer' => $offer,
            'company' => $offer->company,
            'customer' => $offer->customer,
            'settings' => $settings,
            'formattingService' => $formattingService,
        ]);
    }
}

