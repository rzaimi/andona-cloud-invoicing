<?php

namespace App\Modules\Invoice\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Company\Models\Company;
use App\Modules\Invoice\Models\InvoiceLayout;
use App\Services\ContextService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoiceLayoutController extends Controller
{
    public function __construct(ContextService $contextService)
    {
        parent::__construct($contextService);
        $this->middleware('auth');
    }

    public function index()
    {
        $companyId = $this->getEffectiveCompanyId();
        $company = Company::find($companyId);
        $layouts = InvoiceLayout::where('company_id', $companyId)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $templates = [
            [
                'id' => 'modern',
                'name' => 'Modern',
                'description' => 'Modernes, zeitgemäßes Design mit klaren Linien und frischem Layout',
                'preview_image' => '/images/templates/modern.png',
                'features' => ['Modern', 'Zeitgemäß', 'Klar'],
                'colors' => ['#3b82f6', '#2563eb', '#1d4ed8', '#1f2937'],
                'fonts' => ['Inter', 'Roboto']
            ],
            [
                'id' => 'minimal',
                'name' => 'Minimal',
                'description' => 'Minimalistisches Design mit Fokus auf Inhalt und Lesbarkeit',
                'preview_image' => '/images/templates/minimal.png',
                'features' => ['Schlicht', 'Übersichtlich', 'Fokussiert'],
                'colors' => ['#000000', '#666666', '#999999', '#333333'],
                'fonts' => ['Helvetica', 'Arial']
            ],
            [
                'id' => 'classic',
                'name' => 'Klassisch',
                'description' => 'Traditionelles Layout für professionelle Dokumente mit bewährter Struktur',
                'preview_image' => '/images/templates/classic.png',
                'features' => ['Traditionell', 'Bewährt', 'Seriös'],
                'colors' => ['#1f2937', '#6b7280', '#374151', '#111827'],
                'fonts' => ['Times New Roman', 'Georgia']
            ],
            [
                'id' => 'professional',
                'name' => 'Professionell',
                'description' => 'Geschäftliches Layout für Unternehmen mit Corporate Design',
                'preview_image' => '/images/templates/professional.png',
                'features' => ['Corporate', 'Strukturiert', 'Vertrauenswürdig'],
                'colors' => ['#1e40af', '#3b82f6', '#60a5fa', '#1e293b'],
                'fonts' => ['Open Sans', 'Lato']
            ],
            [
                'id' => 'creative',
                'name' => 'Kreativ',
                'description' => 'Kreatives Design für moderne Unternehmen mit frischen Akzenten',
                'preview_image' => '/images/templates/creative.png',
                'features' => ['Modern', 'Auffällig', 'Innovativ'],
                'colors' => ['#7c3aed', '#a855f7', '#c084fc', '#1f2937'],
                'fonts' => ['Montserrat', 'Poppins']
            ],
            [
                'id' => 'elegant',
                'name' => 'Elegant',
                'description' => 'Elegantes Design mit raffinierten Details und hochwertiger Anmutung',
                'preview_image' => '/images/templates/elegant.png',
                'features' => ['Raffiniert', 'Hochwertig', 'Stilvoll'],
                'colors' => ['#059669', '#10b981', '#34d399', '#1f2937'],
                'fonts' => ['Playfair Display', 'Source Sans Pro']
            ]
        ];

        return Inertia::render('settings/invoice-layouts', [
            'company' => [
                'id' => $company?->id,
                'logo' => $company?->logo,
                'name' => $company?->name,
            ],
            'layouts' => $layouts,
            'templates' => $templates
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:invoice,offer,both',
            'template' => 'required|string',
            'save_and_preview' => 'sometimes|boolean',
            'settings' => 'required|array',
            'settings.colors' => 'required|array',
            'settings.colors.primary' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.colors.secondary' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.colors.accent' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.colors.text' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.fonts' => 'required|array',
            'settings.fonts.heading' => 'required|string|max:50',
            'settings.fonts.body' => 'required|string|max:50',
            'settings.fonts.size' => 'required|in:small,medium,large',
            'settings.layout' => 'required|array',
            'settings.layout.header_height' => 'required|integer|min:50|max:300',
            'settings.layout.footer_height' => 'required|integer|min:30|max:200',
            'settings.layout.margin_top' => 'required|integer|min:0|max:100',
            'settings.layout.margin_bottom' => 'required|integer|min:0|max:100',
            'settings.layout.margin_left' => 'required|integer|min:0|max:100',
            'settings.layout.margin_right' => 'required|integer|min:0|max:100',
            'settings.branding' => 'required|array',
            'settings.branding.show_logo' => 'required|boolean',
            'settings.branding.logo_position' => 'required|in:top-left,top-center,top-right',
            'settings.branding.company_info_position' => 'required|in:top-left,top-center,top-right',
            'settings.content' => 'required|array',
            'settings.content.show_item_images' => 'required|boolean',
            'settings.content.show_item_codes' => 'required|boolean',
            'settings.content.show_tax_breakdown' => 'required|boolean',
            'settings.content.show_payment_terms' => 'required|boolean',
            'settings.content.custom_footer_text' => 'nullable|string|max:2000',
            'settings.template_specific' => 'sometimes|array',
        ]);

        // Check if this is the first layout for the company
        $companyId = $this->getEffectiveCompanyId();
        $isFirstLayout = !InvoiceLayout::where('company_id', $companyId)->exists();

        $layout = InvoiceLayout::create([
            'company_id' => $companyId,
            'name' => $request->name,
            'type' => $request->type,
            'template' => $request->template,
            'settings' => $request->settings,
            'is_default' => $isFirstLayout, // First layout becomes default
        ]);

        if ($request->boolean('save_and_preview')) {
            return redirect()->route('invoice-layouts.index', ['preview' => $layout->id])
                ->with('success', 'Layout wurde erfolgreich erstellt.');
        }

        return redirect()->route('invoice-layouts.index')
            ->with('success', 'Layout wurde erfolgreich erstellt.');
    }

    public function update(Request $request, InvoiceLayout $invoiceLayout)
    {
        // Check if user can access this layout
        $companyId = $this->getEffectiveCompanyId();
        if ($invoiceLayout->company_id !== $companyId) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:invoice,offer,both',
            'template' => 'required|string',
            'save_and_preview' => 'sometimes|boolean',
            'settings' => 'required|array',
            'settings.colors' => 'required|array',
            'settings.colors.primary' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.colors.secondary' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.colors.accent' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.colors.text' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.fonts' => 'required|array',
            'settings.fonts.heading' => 'required|string|max:50',
            'settings.fonts.body' => 'required|string|max:50',
            'settings.fonts.size' => 'required|in:small,medium,large',
            'settings.layout' => 'required|array',
            'settings.layout.header_height' => 'required|integer|min:50|max:300',
            'settings.layout.footer_height' => 'required|integer|min:30|max:200',
            'settings.layout.margin_top' => 'required|integer|min:0|max:100',
            'settings.layout.margin_bottom' => 'required|integer|min:0|max:100',
            'settings.layout.margin_left' => 'required|integer|min:0|max:100',
            'settings.layout.margin_right' => 'required|integer|min:0|max:100',
            'settings.branding' => 'required|array',
            'settings.branding.show_logo' => 'required|boolean',
            'settings.branding.logo_position' => 'required|in:top-left,top-center,top-right',
            'settings.branding.company_info_position' => 'required|in:top-left,top-center,top-right',
            'settings.content' => 'required|array',
            'settings.content.show_item_images' => 'required|boolean',
            'settings.content.show_item_codes' => 'required|boolean',
            'settings.content.show_tax_breakdown' => 'required|boolean',
            'settings.content.show_payment_terms' => 'required|boolean',
            'settings.content.custom_footer_text' => 'nullable|string|max:2000',
            'settings.template_specific' => 'sometimes|array',
        ]);

        $invoiceLayout->update([
            'name' => $request->name,
            'type' => $request->type,
            'template' => $request->template,
            'settings' => $request->settings,
        ]);

        if ($request->boolean('save_and_preview')) {
            return redirect()->route('invoice-layouts.index', ['preview' => $invoiceLayout->id])
                ->with('success', 'Layout wurde erfolgreich aktualisiert.');
        }

        return redirect()->route('invoice-layouts.index')
            ->with('success', 'Layout wurde erfolgreich aktualisiert.');
    }

    public function destroy(InvoiceLayout $invoiceLayout)
    {
        // Check if user can access this layout
        $companyId = $this->getEffectiveCompanyId();
        if ($invoiceLayout->company_id !== $companyId) {
            abort(403);
        }

        // Prevent deletion of default layout
        if ($invoiceLayout->is_default) {
            return redirect()->route('invoice-layouts.index')
                ->with('error', 'Das Standard-Layout kann nicht gelöscht werden.');
        }

        // Check if layout is in use
        $invoicesCount = $invoiceLayout->invoices()->count();
        if ($invoicesCount > 0) {
            return redirect()->route('invoice-layouts.index')
                ->with('error', "Das Layout wird von {$invoicesCount} Rechnung(en) verwendet und kann nicht gelöscht werden.");
        }

        $invoiceLayout->delete();

        return redirect()->route('invoice-layouts.index')
            ->with('success', 'Layout wurde erfolgreich gelöscht.');
    }

    public function setDefault(InvoiceLayout $invoiceLayout)
    {
        // Check if user can access this layout
        $companyId = $this->getEffectiveCompanyId();
        if ($invoiceLayout->company_id !== $companyId) {
            abort(403);
        }

        // Remove default from all layouts of this company
        InvoiceLayout::where('company_id', $companyId)
            ->update(['is_default' => false]);

        // Set this layout as default
        $invoiceLayout->update(['is_default' => true]);

        return redirect()->route('invoice-layouts.index')
            ->with('success', 'Layout wurde als Standard festgelegt.');
    }

    public function duplicate(InvoiceLayout $invoiceLayout)
    {
        // Check if user can access this layout
        $companyId = $this->getEffectiveCompanyId();
        if ($invoiceLayout->company_id !== $companyId) {
            abort(403);
        }

        $duplicatedLayout = $invoiceLayout->replicate();
        $duplicatedLayout->name = $invoiceLayout->name . ' (Kopie)';
        $duplicatedLayout->is_default = false;
        $duplicatedLayout->save();

        return redirect()->route('invoice-layouts.index')
            ->with('success', 'Layout wurde erfolgreich dupliziert.');
    }

    public function preview(InvoiceLayout $invoiceLayout)
    {
        // Check if user can access this layout
        $companyId = $this->getEffectiveCompanyId();
        if ($invoiceLayout->company_id !== $companyId) {
            abort(403);
        }

        // Create sample data objects that match Invoice, Customer, and Company models
        $sampleInvoice = (object) [
            'id' => 'sample-001',
            'number' => 'RE-2024-0001',
            'status' => 'draft',
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'subtotal' => 125.00,
            'tax_rate' => 0.19,
            'tax_amount' => 23.75,
            'total' => 148.75,
            'notes' => null,
            'items' => collect([
                (object) [
                    'id' => 'item-001',
                    'description' => 'Beispielprodukt',
                    'quantity' => 2,
                    'unit' => 'Stk.',
                    'unit_price' => 50.00,
                    'total' => 100.00,
                ],
                (object) [
                    'id' => 'item-002',
                    'description' => 'Weiteres Produkt',
                    'quantity' => 1,
                    'unit' => 'Stk.',
                    'unit_price' => 25.00,
                    'total' => 25.00,
                ],
            ]),
        ];

        $sampleCustomer = (object) [
            'id' => 'customer-001',
            'name' => 'Musterkunde GmbH',
            'contact_person' => 'Herr Mustermann',
            'address' => 'Kundenstraße 456',
            'postal_code' => '54321',
            'city' => 'Kundenstadt',
            'country' => 'Deutschland',
            'number' => 'KU-2024-0001', // Changed from customer_number to number
        ];

        // Attach customer to invoice (as the view expects $invoice->customer)
        $sampleInvoice->customer = $sampleCustomer;

        $sampleCompany = \App\Modules\Company\Models\Company::find($companyId);
        
        // Use the same PDF view for preview
        return view('pdf.invoice', [
            'layout' => $invoiceLayout,
            'invoice' => $sampleInvoice,
            'company' => $sampleCompany,
            'customer' => $sampleCustomer,
            'preview' => true,
        ]);
    }

    /**
     * Live preview without saving: render preview HTML from posted layout settings.
     */
    public function previewLive(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();

        $request->validate([
            'template' => 'required|string',
            'type' => 'required|in:invoice,offer,both',
            'settings' => 'required|array',
            'settings.colors' => 'required|array',
            'settings.colors.primary' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.colors.secondary' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.colors.accent' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.colors.text' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.fonts' => 'required|array',
            'settings.fonts.heading' => 'required|string|max:50',
            'settings.fonts.body' => 'required|string|max:50',
            'settings.fonts.size' => 'required|in:small,medium,large',
            'settings.layout' => 'required|array',
            'settings.layout.header_height' => 'required|integer|min:50|max:300',
            'settings.layout.footer_height' => 'required|integer|min:30|max:200',
            'settings.layout.margin_top' => 'required|integer|min:0|max:100',
            'settings.layout.margin_bottom' => 'required|integer|min:0|max:100',
            'settings.layout.margin_left' => 'required|integer|min:0|max:100',
            'settings.layout.margin_right' => 'required|integer|min:0|max:100',
            'settings.branding' => 'required|array',
            'settings.branding.show_logo' => 'required|boolean',
            'settings.branding.logo_position' => 'required|in:top-left,top-center,top-right',
            'settings.branding.company_info_position' => 'required|in:top-left,top-center,top-right',
            'settings.content' => 'required|array',
            'settings.content.show_item_images' => 'required|boolean',
            'settings.content.show_item_codes' => 'required|boolean',
            'settings.content.show_tax_breakdown' => 'required|boolean',
            'settings.content.show_payment_terms' => 'required|boolean',
            'settings.content.custom_footer_text' => 'nullable|string|max:2000',
            'settings.template_specific' => 'sometimes|array',
        ]);

        // Build an in-memory layout (not persisted)
        $layout = new InvoiceLayout();
        $layout->company_id = $companyId;
        $layout->name = 'Live Preview';
        $layout->type = $request->type;
        $layout->template = $request->template;
        $layout->settings = $request->input('settings');

        // Sample data (same as preview())
        $sampleInvoice = (object) [
            'id' => 'sample-001',
            'number' => 'RE-2024-0001',
            'status' => 'draft',
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'subtotal' => 125.00,
            'tax_rate' => 0.19,
            'tax_amount' => 23.75,
            'total' => 148.75,
            'notes' => null,
            'items' => collect([
                (object) [
                    'id' => 'item-001',
                    'description' => 'Beispielprodukt',
                    'quantity' => 2,
                    'unit' => 'Stk.',
                    'unit_price' => 50.00,
                    'total' => 100.00,
                ],
                (object) [
                    'id' => 'item-002',
                    'description' => 'Weiteres Produkt',
                    'quantity' => 1,
                    'unit' => 'Stk.',
                    'unit_price' => 25.00,
                    'total' => 25.00,
                ],
            ]),
        ];

        $sampleCustomer = (object) [
            'id' => 'customer-001',
            'name' => 'Musterkunde GmbH',
            'contact_person' => 'Herr Mustermann',
            'address' => 'Kundenstraße 456',
            'postal_code' => '54321',
            'city' => 'Kundenstadt',
            'country' => 'Deutschland',
            'number' => 'KU-2024-0001',
        ];
        $sampleInvoice->customer = $sampleCustomer;

        $sampleCompany = Company::find($companyId);

        return response()->view('pdf.invoice', [
            'layout' => $layout,
            'invoice' => $sampleInvoice,
            'company' => $sampleCompany,
            'customer' => $sampleCustomer,
            'preview' => true,
        ], 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    /**
     * Live preview as real PDF (matches DomPDF rendering 1:1)
     */
    public function previewLivePdf(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();

        $request->validate([
            'template' => 'required|string',
            'type' => 'required|in:invoice,offer,both',
            'settings' => 'required|array',
            'settings.colors' => 'required|array',
            'settings.colors.primary' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.colors.secondary' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.colors.accent' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.colors.text' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.fonts' => 'required|array',
            'settings.fonts.heading' => 'required|string|max:50',
            'settings.fonts.body' => 'required|string|max:50',
            'settings.fonts.size' => 'required|in:small,medium,large',
            'settings.layout' => 'required|array',
            'settings.layout.header_height' => 'required|integer|min:50|max:300',
            'settings.layout.footer_height' => 'required|integer|min:30|max:200',
            'settings.layout.margin_top' => 'required|integer|min:0|max:100',
            'settings.layout.margin_bottom' => 'required|integer|min:0|max:100',
            'settings.layout.margin_left' => 'required|integer|min:0|max:100',
            'settings.layout.margin_right' => 'required|integer|min:0|max:100',
            'settings.branding' => 'required|array',
            'settings.branding.show_logo' => 'required|boolean',
            'settings.branding.logo_position' => 'required|in:top-left,top-center,top-right',
            'settings.branding.company_info_position' => 'required|in:top-left,top-center,top-right',
            'settings.content' => 'required|array',
            'settings.content.show_item_images' => 'required|boolean',
            'settings.content.show_item_codes' => 'required|boolean',
            'settings.content.show_tax_breakdown' => 'required|boolean',
            'settings.content.show_payment_terms' => 'required|boolean',
            'settings.content.custom_footer_text' => 'nullable|string|max:2000',
            'settings.template_specific' => 'sometimes|array',
        ]);

        $layout = new InvoiceLayout();
        $layout->company_id = $companyId;
        $layout->name = 'Live Preview';
        $layout->type = $request->type;
        $layout->template = $request->template;
        $layout->settings = $request->input('settings');

        $sampleInvoice = (object) [
            'id' => 'sample-001',
            'number' => 'RE-2026-0007',
            'status' => 'draft',
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'subtotal' => 16029.00,
            'tax_rate' => 0.19,
            'tax_amount' => 3045.51,
            'total' => 19074.51,
            'notes' => null,
            'items' => collect([
                (object) ['id' => 'item-001', 'description' => 'SEO-Optimierung und Beratung', 'quantity' => 19, 'unit' => 'Std.', 'unit_price' => 102.00, 'total' => 1938.00],
                (object) ['id' => 'item-002', 'description' => 'E-Commerce Shop Entwicklung', 'quantity' => 1, 'unit' => 'Stk.', 'unit_price' => 7598.00, 'total' => 7598.00],
                (object) ['id' => 'item-003', 'description' => 'Datenbank-Design und -Optimierung', 'quantity' => 13, 'unit' => 'Std.', 'unit_price' => 105.00, 'total' => 1365.00],
                (object) ['id' => 'item-004', 'description' => 'Mobile App Entwicklung', 'quantity' => 1, 'unit' => 'Stk.', 'unit_price' => 5128.00, 'total' => 5128.00],
            ]),
        ];

        $sampleCustomer = (object) [
            'id' => 'customer-001',
            'name' => 'Schmidt Consulting AG',
            'contact_person' => 'Frau Schmidt',
            'address' => 'Maximilianstraße 35',
            'postal_code' => '80539',
            'city' => 'München',
            'country' => 'Deutschland',
            'number' => 'KU-2026-0002',
        ];
        $sampleInvoice->customer = $sampleCustomer;

        $company = Company::find($companyId);

        $html = view('pdf.invoice', [
            'layout' => $layout,
            'invoice' => $sampleInvoice,
            'company' => $company,
            'customer' => $sampleCustomer,
            'preview' => true,
        ])->render();

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
            ]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

}

