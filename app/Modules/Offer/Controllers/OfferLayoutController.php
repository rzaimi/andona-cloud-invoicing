<?php

namespace App\Modules\Offer\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Offer\Models\OfferLayout;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OfferLayoutController extends Controller
{
    public function index()
    {
        $companyId = $this->getEffectiveCompanyId();
        $layouts = OfferLayout::where('company_id', $companyId)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $templates = [
            [
                'id' => 'modern',
                'name' => 'Modern',
                'description' => 'Modernes, sauberes Design mit klaren Linien und großzügigen Weißräumen',
                'preview_image' => '/images/templates/modern.png',
                'features' => ['Minimalistisch', 'Professionell', 'Responsive'],
                'colors' => ['#2563eb', '#64748b', '#0ea5e9', '#1e293b'],
                'fonts' => ['Inter', 'Roboto']
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
                'id' => 'minimal',
                'name' => 'Minimal',
                'description' => 'Minimalistisches Design mit Fokus auf Inhalt und Lesbarkeit',
                'preview_image' => '/images/templates/minimal.png',
                'features' => ['Schlicht', 'Übersichtlich', 'Fokussiert'],
                'colors' => ['#000000', '#666666', '#999999', '#333333'],
                'fonts' => ['Helvetica', 'Arial']
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

        return Inertia::render('settings/offer-layouts', [
            'layouts' => $layouts,
            'templates' => $templates
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'template' => 'required|string',
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
            'settings.content.show_validity_period' => 'required|boolean',
            'settings.content.custom_footer_text' => 'nullable|string|max:2000',
            'settings.template_specific' => 'sometimes|array',
        ]);

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
        // Check if user can access this layout
        $companyId = $this->getEffectiveCompanyId();
        if ($offerLayout->company_id !== $companyId) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'template' => 'required|string',
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
            'settings.content.show_validity_period' => 'required|boolean',
            'settings.content.custom_footer_text' => 'nullable|string|max:2000',
            'settings.template_specific' => 'sometimes|array',
        ]);

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
        // Check if user can access this layout
        $companyId = $this->getEffectiveCompanyId();
        if ($offerLayout->company_id !== $companyId) {
            abort(403);
        }

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
        // Check if user can access this layout
        $companyId = $this->getEffectiveCompanyId();
        if ($offerLayout->company_id !== $companyId) {
            abort(403);
        }

        // Remove default from all layouts of this company
        OfferLayout::where('company_id', $companyId)
            ->update(['is_default' => false]);

        // Set this layout as default
        $offerLayout->update(['is_default' => true]);

        return redirect()->route('offer-layouts.index')
            ->with('success', 'Angebotslayout wurde als Standard festgelegt.');
    }

    public function duplicate(OfferLayout $offerLayout)
    {
        // Check if user can access this layout
        $companyId = $this->getEffectiveCompanyId();
        if ($offerLayout->company_id !== $companyId) {
            abort(403);
        }

        $duplicatedLayout = $offerLayout->replicate();
        $duplicatedLayout->name = $offerLayout->name . ' (Kopie)';
        $duplicatedLayout->is_default = false;
        $duplicatedLayout->save();

        return redirect()->route('offer-layouts.index')
            ->with('success', 'Angebotslayout wurde erfolgreich dupliziert.');
    }

    public function preview(OfferLayout $offerLayout)
    {
        // Check if user can access this layout
        $companyId = $this->getEffectiveCompanyId();
        if ($offerLayout->company_id !== $companyId) {
            abort(403);
        }

        // Return preview data or render preview template
        return response()->json([
            'layout' => $offerLayout,
            'preview_html' => $this->generatePreviewHtml($offerLayout)
        ]);
    }

    private function generatePreviewHtml(OfferLayout $layout)
    {
        // Generate HTML preview based on layout settings
        return view('layouts.offer', [
            'layout' => $layout,
            'sample_data' => $this->getSampleData()
        ])->render();
    }

    private function getSampleData()
    {
        return [
            'offer_number' => 'AN-2024-0001',
            'offer_date' => now()->format('d.m.Y'),
            'valid_until' => now()->addDays(30)->format('d.m.Y'),
            'company' => [
                'name' => 'Ihre Firma GmbH',
                'address' => 'Musterstraße 123',
                'postal_code' => '12345',
                'city' => 'Musterstadt',
                'phone' => '+49 123 456789',
                'email' => 'info@ihrefirma.de',
            ],
            'customer' => [
                'name' => 'Musterkunde GmbH',
                'address' => 'Kundenstraße 456',
                'postal_code' => '54321',
                'city' => 'Kundenstadt',
            ],
            'items' => [
                [
                    'code' => 'ART-001',
                    'description' => 'Beispielprodukt',
                    'quantity' => 2,
                    'unit_price' => 50.00,
                    'total' => 100.00,
                ],
                [
                    'code' => 'ART-002',
                    'description' => 'Weiteres Produkt',
                    'quantity' => 1,
                    'unit_price' => 25.00,
                    'total' => 25.00,
                ]
            ],
            'subtotal' => 125.00,
            'tax_amount' => 23.75,
            'total' => 148.75,
        ];
    }
}
