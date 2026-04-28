<?php

namespace App\Modules\Offer\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Company\Models\Company;
use App\Modules\Offer\Models\OfferLayout;
use App\Services\ContextService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OfferLayoutController extends Controller
{
    public function __construct(ContextService $contextService)
    {
        parent::__construct($contextService);
        $this->middleware('auth');
    }

    public function index()
    {
        $companyId = $this->getEffectiveCompanyId();
        $layouts = OfferLayout::where('company_id', $companyId)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Three unified themes: minimal, professional, modern.
        $templates = [
            [
                'id' => 'minimal',
                'name' => 'Minimal',
                'description' => 'Ultra-aufgeräumt mit viel Weißraum und schlichter Typografie — ideal für moderne Dienstleister',
                'preview_image' => '/images/templates/minimal.png',
                'features' => ['Schlicht', 'Übersichtlich', 'Modern'],
                'colors' => ['#111111', '#888888', '#dedede', '#1c1033'],
                'fonts' => ['Inter', 'Roboto'],
            ],
            [
                'id' => 'professional',
                'name' => 'Professionell',
                'description' => 'Navy-Titelbalken, Infopanel-Raster und Zahlungsboxen — repräsentativ und strukturiert',
                'preview_image' => '/images/templates/professional.png',
                'features' => ['Professionell', 'Strukturiert', 'Repräsentativ'],
                'colors' => ['#0d2240', '#64748b', '#f7f9fc', '#1e293b'],
                'fonts' => ['DejaVu Sans', 'Inter'],
            ],
            [
                'id' => 'modern',
                'name' => 'Modern',
                'description' => 'Farbige Seitenleiste und Info-Band — präsent und auffällig',
                'preview_image' => '/images/templates/modern.png',
                'features' => ['Modern', 'Auffällig', 'Farbstark'],
                'colors' => ['#2563eb', '#475569', '#94a3b8', '#0f172a'],
                'fonts' => ['Inter', 'Open Sans'],
            ],
        ];

        $company = Company::find($companyId);

        return Inertia::render('settings/offer-layouts', [
            'layouts'   => $layouts,
            'templates' => $templates,
            'company'   => $company ? [
                'id'   => $company->id,
                'name' => $company->name,
                'logo' => $company->logo,
            ] : null,
        ]);
    }

    private function layoutValidationRules(): array
    {
        return [
            'name'                                        => 'required|string|max:255',
            'template'                                    => 'required|string',
            'settings'                                    => 'required|array',
            'settings.colors'                             => 'required|array',
            'settings.colors.primary'                     => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.colors.secondary'                   => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.colors.accent'                      => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.colors.text'                        => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.fonts'                              => 'required|array',
            'settings.fonts.heading'                      => 'required|string|max:50',
            'settings.fonts.body'                         => 'required|string|max:50',
            'settings.fonts.size'                         => 'required|in:small,medium,large',
            'settings.layout'                             => 'required|array',
            'settings.layout.header_height'               => 'required|integer|min:50|max:300',
            'settings.layout.footer_height'               => 'required|integer|min:30|max:200',
            'settings.layout.margin_top'                  => 'required|integer|min:0|max:100',
            'settings.layout.margin_bottom'               => 'required|integer|min:0|max:100',
            'settings.layout.margin_left'                 => 'required|integer|min:0|max:100',
            'settings.layout.margin_right'                => 'required|integer|min:0|max:100',
            'settings.branding'                           => 'required|array',
            'settings.branding.show_logo'                 => 'required|boolean',
            'settings.branding.logo_position'             => 'required|in:top-left,top-center,top-right,left,center,right',
            'settings.branding.company_info_position'     => 'required|in:top-left,top-center,top-right,left,center,right',
            'settings.branding.show_header_line'          => 'sometimes|boolean',
            'settings.branding.show_footer_line'          => 'sometimes|boolean',
            'settings.branding.show_footer'               => 'sometimes|boolean',
            'settings.content'                            => 'required|array',
            'settings.content.show_company_address'       => 'sometimes|boolean',
            'settings.content.show_company_contact'       => 'sometimes|boolean',
            'settings.content.show_customer_number'       => 'sometimes|boolean',
            'settings.content.show_tax_number'            => 'sometimes|boolean',
            'settings.content.show_unit_column'           => 'sometimes|boolean',
            'settings.content.show_notes'                 => 'sometimes|boolean',
            'settings.content.show_bank_details'          => 'sometimes|boolean',
            'settings.content.show_company_registration'  => 'sometimes|boolean',
            'settings.content.show_payment_terms'         => 'required|boolean',
            'settings.content.show_validity_period'       => 'required|boolean',
            'settings.content.show_item_images'           => 'required|boolean',
            'settings.content.show_item_codes'            => 'required|boolean',
            'settings.content.show_row_number'            => 'required|boolean',
            'settings.content.show_bauvorhaben'           => 'required|boolean',
            'settings.content.show_tax_breakdown'         => 'required|boolean',
            'settings.content.custom_footer_text'         => 'nullable|string|max:2000',
            'settings.template_specific'                  => 'sometimes|array',
        ];
    }

    public function store(Request $request)
    {
        $request->validate($this->layoutValidationRules());

        // Check if this is the first layout for the company
        $companyId = $this->getEffectiveCompanyId();
        $isFirstLayout = !OfferLayout::where('company_id', $companyId)->exists();

        $layout = OfferLayout::create([
            'company_id' => $companyId,
            'name' => $request->name,
            'template' => $request->template,
            'settings' => $request->settings,
            'is_default' => $isFirstLayout, // First layout becomes default
        ]);

        return redirect()->route('offer-layouts.index')
            ->with('success', 'Angebotslayout wurde erfolgreich erstellt.');
    }

    public function update(Request $request, OfferLayout $offerLayout)
    {
        $this->authorize('update', $offerLayout);

        $request->validate($this->layoutValidationRules());

        $offerLayout->update([
            'name' => $request->name,
            'template' => $request->template,
            'settings' => $request->settings,
        ]);

        return redirect()->route('offer-layouts.index')
            ->with('success', 'Angebotslayout wurde erfolgreich aktualisiert.');
    }

    public function destroy(OfferLayout $offerLayout)
    {
        $this->authorize('delete', $offerLayout);

        // Prevent deletion of default layout
        if ($offerLayout->is_default) {
            return redirect()->route('offer-layouts.index')
                ->with('error', 'Das Standard-Layout kann nicht gelöscht werden.');
        }

        // Check if layout is in use
        $offersCount = $offerLayout->offers()->count();
        if ($offersCount > 0) {
            return redirect()->route('offer-layouts.index')
                ->with('error', "Das Layout wird von {$offersCount} Angebot(en) verwendet und kann nicht gelöscht werden.");
        }

        $offerLayout->delete();

        return redirect()->route('offer-layouts.index')
            ->with('success', 'Angebotslayout wurde erfolgreich gelöscht.');
    }

    public function setDefault(OfferLayout $offerLayout)
    {
        $this->authorize('update', $offerLayout);

        // Remove default from all layouts of this company
        OfferLayout::where('company_id', $offerLayout->company_id)
            ->update(['is_default' => false]);

        // Set this layout as default
        $offerLayout->update(['is_default' => true]);

        return redirect()->route('offer-layouts.index')
            ->with('success', 'Angebotslayout wurde als Standard festgelegt.');
    }

    public function duplicate(OfferLayout $offerLayout)
    {
        $this->authorize('update', $offerLayout);

        $duplicatedLayout = $offerLayout->replicate();
        $duplicatedLayout->name = $offerLayout->name . ' (Kopie)';
        $duplicatedLayout->is_default = false;
        $duplicatedLayout->save();

        return redirect()->route('offer-layouts.index')
            ->with('success', 'Angebotslayout wurde erfolgreich dupliziert.');
    }

    public function preview(OfferLayout $offerLayout)
    {
        $this->authorize('view', $offerLayout);

        [$sampleOffer, $sampleCustomer] = $this->buildSampleOffer();
        $company = Company::find($offerLayout->company_id);

        return view('pdf.offer', [
            'layout'   => $offerLayout,
            'offer'    => $sampleOffer,
            'company'  => $company,
            'customer' => $sampleCustomer,
            'settings' => [],
            'preview'  => true,
        ]);
    }

    public function previewLive(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();

        $request->validate([
            'template'                                    => 'required|string',
            'settings'                                    => 'required|array',
            'settings.colors'                             => 'required|array',
            'settings.colors.primary'                     => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.colors.secondary'                   => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.colors.accent'                      => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.colors.text'                        => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.fonts'                              => 'required|array',
            'settings.fonts.heading'                      => 'required|string|max:50',
            'settings.fonts.body'                         => 'required|string|max:50',
            'settings.fonts.size'                         => 'required|in:small,medium,large',
            'settings.layout'                             => 'required|array',
            'settings.layout.header_height'               => 'required|integer|min:50|max:300',
            'settings.layout.footer_height'               => 'required|integer|min:30|max:200',
            'settings.layout.margin_top'                  => 'required|integer|min:0|max:100',
            'settings.layout.margin_bottom'               => 'required|integer|min:0|max:100',
            'settings.layout.margin_left'                 => 'required|integer|min:0|max:100',
            'settings.layout.margin_right'                => 'required|integer|min:0|max:100',
            'settings.branding'                           => 'required|array',
            'settings.branding.show_logo'                 => 'required|boolean',
            'settings.branding.logo_position'             => 'required|in:top-left,top-center,top-right,left,center,right',
            'settings.branding.company_info_position'     => 'required|in:top-left,top-center,top-right,left,center,right',
            'settings.content'                            => 'required|array',
            'settings.content.show_item_images'           => 'required|boolean',
            'settings.content.show_item_codes'            => 'required|boolean',
            'settings.content.show_row_number'            => 'required|boolean',
            'settings.content.show_bauvorhaben'           => 'required|boolean',
            'settings.content.show_tax_breakdown'         => 'required|boolean',
            'settings.content.show_payment_terms'         => 'required|boolean',
            'settings.content.show_validity_period'       => 'required|boolean',
            'settings.content.custom_footer_text'         => 'nullable|string|max:2000',
            'settings.template_specific'                  => 'sometimes|array',
        ]);

        $layout             = new OfferLayout();
        $layout->company_id = $companyId;
        $layout->name       = 'Live Preview';
        $layout->template   = $request->template;
        $layout->settings   = $request->input('settings');

        [$sampleOffer, $sampleCustomer] = $this->buildSampleOffer();
        $company = Company::find($companyId);

        return response()->view('pdf.offer', [
            'layout'   => $layout,
            'offer'    => $sampleOffer,
            'company'  => $company,
            'customer' => $sampleCustomer,
            'settings' => [],
            'preview'  => true,
        ], 200, [
            'Content-Type'  => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function previewLivePdf(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();

        $request->validate([
            'template'                                    => 'required|string',
            'settings'                                    => 'required|array',
            'settings.colors'                             => 'required|array',
            'settings.colors.primary'                     => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.colors.secondary'                   => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.colors.accent'                      => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.colors.text'                        => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.fonts'                              => 'required|array',
            'settings.fonts.heading'                      => 'required|string|max:50',
            'settings.fonts.body'                         => 'required|string|max:50',
            'settings.fonts.size'                         => 'required|in:small,medium,large',
            'settings.layout'                             => 'required|array',
            'settings.layout.header_height'               => 'required|integer|min:50|max:300',
            'settings.layout.footer_height'               => 'required|integer|min:30|max:200',
            'settings.layout.margin_top'                  => 'required|integer|min:0|max:100',
            'settings.layout.margin_bottom'               => 'required|integer|min:0|max:100',
            'settings.layout.margin_left'                 => 'required|integer|min:0|max:100',
            'settings.layout.margin_right'                => 'required|integer|min:0|max:100',
            'settings.branding'                           => 'required|array',
            'settings.branding.show_logo'                 => 'required|boolean',
            'settings.branding.logo_position'             => 'required|in:top-left,top-center,top-right,left,center,right',
            'settings.branding.company_info_position'     => 'required|in:top-left,top-center,top-right,left,center,right',
            'settings.content'                            => 'required|array',
            'settings.content.show_item_images'           => 'required|boolean',
            'settings.content.show_item_codes'            => 'required|boolean',
            'settings.content.show_row_number'            => 'required|boolean',
            'settings.content.show_bauvorhaben'           => 'required|boolean',
            'settings.content.show_tax_breakdown'         => 'required|boolean',
            'settings.content.show_payment_terms'         => 'required|boolean',
            'settings.content.show_validity_period'       => 'required|boolean',
            'settings.content.custom_footer_text'         => 'nullable|string|max:2000',
            'settings.template_specific'                  => 'sometimes|array',
        ]);

        $layout             = new OfferLayout();
        $layout->company_id = $companyId;
        $layout->name       = 'Live Preview';
        $layout->template   = $request->template;
        $layout->settings   = $request->input('settings');

        [$sampleOffer, $sampleCustomer] = $this->buildSampleOffer();
        $company = Company::find($companyId);

        $html = view('pdf.offer', [
            'layout'   => $layout,
            'offer'    => $sampleOffer,
            'company'  => $company,
            'customer' => $sampleCustomer,
            'settings' => [],
            'preview'  => true,
        ])->render();

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4')
            ->setOptions([
                'defaultFont'              => 'DejaVu Sans',
                // PHP enabled for page_script (page numbering in preview).
                // Remote and local file access remain disabled.
                'isRemoteEnabled'          => false,
                'isHtml5ParserEnabled'     => true,
                'enable-local-file-access' => false,
                'isPhpEnabled'             => true,
            ]);

        return response($pdf->output(), 200, [
            'Content-Type'  => 'application/pdf',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    private function buildSampleOffer(): array
    {
        $sampleCustomer = (object) [
            'id'             => 'customer-001',
            'name'           => 'Schmidt Consulting AG',
            'contact_person' => 'Frau Schmidt',
            'address'        => 'Maximilianstraße 35',
            'postal_code'    => '80539',
            'city'           => 'München',
            'country'        => 'Deutschland',
            'number'         => 'KU-2026-0002',
        ];

        $sampleOffer = (object) [
            'id'              => 'sample-offer-001',
            'number'          => 'AN-2026-0007',
            'status'          => 'sent',
            'issue_date'      => now(),
            'valid_until'     => now()->addDays(30),
            'subtotal'        => 16029.00,
            'tax_rate'        => 0.19,
            'tax_amount'      => 3045.51,
            'vat_regime'      => 'standard',
            'total'           => 19074.51,
            'notes'           => null,
            'terms_conditions' => null,
            'customer'        => $sampleCustomer,
            'items'           => collect([
                (object) ['id' => 'item-001', 'description' => 'SEO-Optimierung und Beratung',    'quantity' => 19, 'unit' => 'Std.', 'unit_price' => 102.00, 'total' => 1938.00, 'tax_rate' => 0.19, 'discount_amount' => 0, 'discount_type' => null, 'discount_value' => null],
                (object) ['id' => 'item-002', 'description' => 'E-Commerce Shop Entwicklung',     'quantity' => 1,  'unit' => 'Stk.', 'unit_price' => 7598.00, 'total' => 7598.00, 'tax_rate' => 0.19, 'discount_amount' => 0, 'discount_type' => null, 'discount_value' => null],
                (object) ['id' => 'item-003', 'description' => 'Datenbank-Design und -Optimierung', 'quantity' => 13, 'unit' => 'Std.', 'unit_price' => 105.00, 'total' => 1365.00, 'tax_rate' => 0.19, 'discount_amount' => 0, 'discount_type' => null, 'discount_value' => null],
                (object) ['id' => 'item-004', 'description' => 'Mobile App Entwicklung',         'quantity' => 1,  'unit' => 'Stk.', 'unit_price' => 5128.00, 'total' => 5128.00, 'tax_rate' => 0.19, 'discount_amount' => 0, 'discount_type' => null, 'discount_value' => null],
            ]),
        ];

        return [$sampleOffer, $sampleCustomer];
    }

}

