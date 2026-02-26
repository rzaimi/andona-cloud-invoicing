<?php

namespace App\Console\Commands;

use App\Modules\Company\Models\Company;
use App\Modules\Expense\Models\ExpenseCategory;
use App\Modules\Invoice\Models\InvoiceLayout;
use App\Modules\Offer\Models\OfferLayout;
use App\Modules\Product\Models\Category;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Warehouse;
use Illuminate\Console\Command;

class InitializeCompanyData extends Command
{
    protected $signature = 'company:init
                            {company_id : The UUID of the company}
                            {--type= : Company type slug (gartenbau, bauunternehmen, raumausstattung, gebaudetechnik, logistik, handel, dienstleistung)}
                            {--force : Overwrite existing data}';

    protected $description = 'Initialize a company with industry-specific products, categories, warehouse and layouts';

    private const COMPANY_TYPES = [
        'gartenbau'       => 'Garten- und Außenanlagenbau',
        'bauunternehmen'  => 'Bauunternehmen',
        'raumausstattung' => 'Raumausstattung & Fliesenarbeiten',
        'gebaudetechnik'  => 'Gebäudetechnik',
        'logistik'        => 'Logistik & Palettenhandel',
        'handel'          => 'Handelsunternehmen',
        'dienstleistung'  => 'Sonstige Dienstleistungen',
    ];

    public function handle(): int
    {
        $companyId = $this->argument('company_id');
        $company   = Company::find($companyId);

        if (!$company) {
            $this->error("Company with ID {$companyId} not found.");
            return Command::FAILURE;
        }

        // Resolve type interactively if not supplied
        $typeSlug = $this->option('type');

        if (!$typeSlug || !isset(self::COMPANY_TYPES[$typeSlug])) {
            $choices = [];
            foreach (self::COMPANY_TYPES as $slug => $label) {
                $choices[$slug] = "{$slug} — {$label}";
            }
            $chosen   = $this->choice('Select company type', array_values($choices), 0);
            $typeSlug = array_search($chosen, $choices);
        }

        $typeName = self::COMPANY_TYPES[$typeSlug];
        $force    = $this->option('force');

        $this->newLine();
        $this->line("<fg=cyan>Company:</> {$company->name}");
        $this->line("<fg=cyan>Type:</> {$typeName}");
        $this->newLine();

        $data = $this->getDataForType($typeSlug);

        // 1. Warehouse
        $this->line('<fg=yellow>→ Creating main warehouse...</>');
        $this->createWarehouse($companyId, $force);

        // 2. Product categories
        $this->line('<fg=yellow>→ Creating product categories...</>');
        $categoryMap = $this->createCategories($companyId, $data['product_categories'], $force);

        // 3. Products
        $this->line('<fg=yellow>→ Creating products...</>');
        $createdProducts = $this->createProducts($companyId, $data['products'], $categoryMap, $force);

        // 4. Expense categories
        $this->line('<fg=yellow>→ Creating expense categories...</>');
        $this->createExpenseCategories($companyId, $data['expense_categories']);

        // 5. Invoice layouts
        $this->line('<fg=yellow>→ Creating invoice layouts...</>');
        $this->createInvoiceLayouts($companyId, $force);

        // 6. Offer layouts
        $this->line('<fg=yellow>→ Creating offer layouts...</>');
        $this->createOfferLayouts($companyId, $force);

        $this->newLine();
        $this->info('✓ Company initialization complete!');
        $this->table(
            ['Item', 'Count'],
            [
                ['Product Categories', count($categoryMap)],
                ['Products',           $createdProducts],
                ['Expense Categories', count($data['expense_categories'])],
                ['Warehouse',          '1 (Hauptlager)'],
                ['Invoice Layouts',    '6'],
                ['Offer Layouts',      '6'],
            ]
        );

        return Command::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CREATION HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function createWarehouse(string $companyId, bool $force): void
    {
        if ($force) {
            Warehouse::where('company_id', $companyId)->where('code', 'HQ')->delete();
        }

        [$warehouse, $created] = [
            Warehouse::firstOrCreate(
                ['company_id' => $companyId, 'code' => 'HQ'],
                [
                    'name'        => 'Hauptlager',
                    'description' => 'Zentrallager',
                    'is_default'  => true,
                    'is_active'   => true,
                ]
            ),
            null,
        ];

        $this->line('  ✓ Hauptlager (HQ)');
    }

    private function createCategories(string $companyId, array $categories, bool $force): array
    {
        $map = [];
        foreach ($categories as $cat) {
            if ($force) {
                Category::where('company_id', $companyId)->where('name', $cat['name'])->delete();
            }

            $model = Category::firstOrCreate(
                ['company_id' => $companyId, 'name' => $cat['name']],
                [
                    'description' => $cat['description'] ?? null,
                    'color'       => $cat['color'] ?? '#6366f1',
                    'sort_order'  => $cat['sort_order'] ?? 0,
                    'is_active'   => true,
                ]
            );

            $map[$cat['name']] = $model->id;
            $this->line("  ✓ {$cat['name']}");
        }
        return $map;
    }

    private function createProducts(string $companyId, array $products, array $categoryMap, bool $force): int
    {
        $count = 0;
        foreach ($products as $p) {
            if ($force) {
                Product::where('company_id', $companyId)->where('sku', $p['sku'])->delete();
            }

            $categoryId = isset($p['category']) ? ($categoryMap[$p['category']] ?? null) : null;

            Product::firstOrCreate(
                ['company_id' => $companyId, 'sku' => $p['sku']],
                [
                    'name'            => $p['name'],
                    'description'     => $p['description'] ?? null,
                    'unit'            => $p['unit'] ?? 'Stk.',
                    'price'           => $p['price'],
                    'cost_price'      => $p['cost_price'] ?? null,
                    'category_id'     => $categoryId,
                    'tax_rate'        => $p['tax_rate'] ?? 0.19,
                    'stock_quantity'  => $p['stock_quantity'] ?? 0,
                    'min_stock_level' => $p['min_stock_level'] ?? 0,
                    'track_stock'     => $p['track_stock'] ?? false,
                    'is_service'      => $p['is_service'] ?? false,
                    'status'          => 'active',
                ]
            );

            $this->line("  ✓ {$p['name']} ({$p['sku']})");
            $count++;
        }
        return $count;
    }

    private function createExpenseCategories(string $companyId, array $categories): void
    {
        foreach ($categories as $name) {
            ExpenseCategory::firstOrCreate(['company_id' => $companyId, 'name' => $name]);
            $this->line("  ✓ {$name}");
        }
    }

    private function createInvoiceLayouts(string $companyId, bool $force): void
    {
        if (!$force && InvoiceLayout::where('company_id', $companyId)->exists()) {
            $this->line('  ℹ Invoice layouts already exist — skipping (use --force to recreate).');
            return;
        }

        if ($force) {
            InvoiceLayout::where('company_id', $companyId)->delete();
        }

        $settings  = $this->defaultLayoutSettings();
        $templates = [
            ['name' => 'Standard Rechnung',    'template' => 'modern',       'is_default' => true],
            ['name' => 'Klassische Rechnung',   'template' => 'classic',      'is_default' => false],
            ['name' => 'Minimale Rechnung',     'template' => 'minimal',      'is_default' => false],
            ['name' => 'Professionell',         'template' => 'professional', 'is_default' => false],
            ['name' => 'Elegant',               'template' => 'elegant',      'is_default' => false],
            ['name' => 'Clean',                 'template' => 'clean',        'is_default' => false],
        ];

        foreach ($templates as $t) {
            InvoiceLayout::create([
                'company_id' => $companyId,
                'name'       => $t['name'],
                'template'   => $t['template'],
                'is_default' => $t['is_default'],
                'settings'   => $settings,
            ]);
            $flag = $t['is_default'] ? ' (Standard)' : '';
            $this->line("  ✓ {$t['name']}{$flag}");
        }
    }

    private function createOfferLayouts(string $companyId, bool $force): void
    {
        if (!$force && OfferLayout::where('company_id', $companyId)->exists()) {
            $this->line('  ℹ Offer layouts already exist — skipping (use --force to recreate).');
            return;
        }

        if ($force) {
            OfferLayout::where('company_id', $companyId)->delete();
        }

        $settings  = $this->defaultLayoutSettings();
        $templates = [
            ['name' => 'Standard Angebot',        'template' => 'modern',       'is_default' => true],
            ['name' => 'Klassisches Angebot',      'template' => 'classic',      'is_default' => false],
            ['name' => 'Minimales Angebot',        'template' => 'minimal',      'is_default' => false],
            ['name' => 'Professionelles Angebot',  'template' => 'professional', 'is_default' => false],
            ['name' => 'Elegantes Angebot',        'template' => 'elegant',      'is_default' => false],
            ['name' => 'Clean Angebot',            'template' => 'clean',        'is_default' => false],
        ];

        foreach ($templates as $t) {
            OfferLayout::create([
                'company_id' => $companyId,
                'name'       => $t['name'],
                'template'   => $t['template'],
                'is_default' => $t['is_default'],
                'settings'   => $settings,
            ]);
            $flag = $t['is_default'] ? ' (Standard)' : '';
            $this->line("  ✓ {$t['name']}{$flag}");
        }
    }

    private function defaultLayoutSettings(): array
    {
        return [
            'colors' => [
                'primary'   => '#1a56db',
                'secondary' => '#f3f4f6',
                'accent'    => '#1a56db',
                'text'      => '#111827',
            ],
            'fonts' => [
                'heading' => 'Helvetica',
                'body'    => 'Helvetica',
                'size'    => 'medium',
            ],
            'layout' => [
                'header_height' => 120,
                'footer_height' => 80,
                'margin_top'    => 10,
                'margin_bottom' => 10,
                'margin_left'   => 15,
                'margin_right'  => 15,
            ],
            'branding' => [
                'show_logo'             => true,
                'logo_position'         => 'top-right',
                'company_info_position' => 'top-left',
            ],
            'content' => [
                'show_item_images'   => false,
                'show_item_codes'    => true,
                'show_row_number'    => true,
                'show_bauvorhaben'   => false,
                'show_tax_breakdown' => true,
                'show_payment_terms' => true,
                'custom_footer_text' => '',
            ],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // INDUSTRY DATA
    // ─────────────────────────────────────────────────────────────────────────

    private function getDataForType(string $type): array
    {
        return match ($type) {
            'gartenbau'       => $this->getGartenbauData(),
            'bauunternehmen'  => $this->getBauData(),
            'raumausstattung' => $this->getRaumausstattungData(),
            'gebaudetechnik'  => $this->getGebaudetechnikData(),
            'logistik'        => $this->getLogistikData(),
            'handel'          => $this->getHandelData(),
            default           => $this->getDienstleistungData(),
        };
    }

    // ── 1. Garten- und Außenanlagenbau ──────────────────────────────────────

    private function getGartenbauData(): array
    {
        return [
            'product_categories' => [
                ['name' => 'Bäume & Sträucher',     'description' => 'Laubbäume, Nadelbäume, Ziersträucher',    'color' => '#166534', 'sort_order' => 1],
                ['name' => 'Stauden & Bodendecker',  'description' => 'Stauden, Bodendecker, Gräser',            'color' => '#15803d', 'sort_order' => 2],
                ['name' => 'Rasen & Saaten',         'description' => 'Rasensamen, Rollrasen, Saatgut',          'color' => '#22c55e', 'sort_order' => 3],
                ['name' => 'Erde, Substrate & Dünger','description' => 'Pflanzerde, Kompost, Düngemittel',       'color' => '#92400e', 'sort_order' => 4],
                ['name' => 'Pflaster & Beläge',      'description' => 'Pflastersteine, Platten, Kies, Schotter', 'color' => '#78716c', 'sort_order' => 5],
                ['name' => 'Zäune & Sichtschutz',    'description' => 'Zäune, Pergolen, Sichtschutzelemente',    'color' => '#a16207', 'sort_order' => 6],
                ['name' => 'Bewässerung',             'description' => 'Schläuche, Tropfer, Systeme',             'color' => '#0369a1', 'sort_order' => 7],
                ['name' => 'Dienstleistungen',        'description' => 'Planung, Pflege, Montage',                'color' => '#4f46e5', 'sort_order' => 8],
            ],
            'products' => [
                // Bäume & Sträucher
                ['sku' => 'GAR-B-001', 'name' => 'Kirschlorbeer 60–80 cm',    'description' => 'Prunus laurocerasus, immergrün, Heckenpflanze',     'unit' => 'Stk.', 'price' => 18.90,  'cost_price' => 9.00,   'category' => 'Bäume & Sträucher',      'tax_rate' => 0.07, 'track_stock' => true,  'stock_quantity' => 100, 'min_stock_level' => 20],
                ['sku' => 'GAR-B-002', 'name' => 'Thuja occidentalis 100–120 cm', 'description' => 'Lebensbaum, Heckenpflanze',                     'unit' => 'Stk.', 'price' => 24.50,  'cost_price' => 12.00,  'category' => 'Bäume & Sträucher',      'tax_rate' => 0.07, 'track_stock' => true,  'stock_quantity' => 80,  'min_stock_level' => 15],
                ['sku' => 'GAR-B-003', 'name' => 'Rotbuche Hochstamm',          'description' => 'Fagus sylvatica, 10–12 cm Stammumfang',           'unit' => 'Stk.', 'price' => 195.00, 'cost_price' => 95.00,  'category' => 'Bäume & Sträucher',      'tax_rate' => 0.07, 'track_stock' => true,  'stock_quantity' => 15,  'min_stock_level' => 3],
                ['sku' => 'GAR-B-004', 'name' => 'Rhododendron 40–60 cm',       'description' => 'Verschiedene Sorten, kalkempfindlich',            'unit' => 'Stk.', 'price' => 22.90,  'cost_price' => 11.00,  'category' => 'Bäume & Sträucher',      'tax_rate' => 0.07, 'track_stock' => true,  'stock_quantity' => 60,  'min_stock_level' => 10],
                // Stauden & Bodendecker
                ['sku' => 'GAR-S-001', 'name' => 'Lavendel im Topf 2 L',        'description' => 'Lavandula angustifolia, aromatisch',              'unit' => 'Stk.', 'price' => 5.90,   'cost_price' => 2.50,   'category' => 'Stauden & Bodendecker',  'tax_rate' => 0.07, 'track_stock' => true,  'stock_quantity' => 200, 'min_stock_level' => 40],
                ['sku' => 'GAR-S-002', 'name' => 'Efeu im Topf 9 cm',           'description' => 'Hedera helix, Bodendecker & Kletterpflanze',      'unit' => 'Stk.', 'price' => 3.50,   'cost_price' => 1.50,   'category' => 'Stauden & Bodendecker',  'tax_rate' => 0.07, 'track_stock' => true,  'stock_quantity' => 300, 'min_stock_level' => 60],
                // Rasen & Saaten
                ['sku' => 'GAR-R-001', 'name' => 'Rasensamen Universalrasen 1 kg', 'description' => 'Gebrauchsrasen für normale Beanspruchung',     'unit' => 'kg',   'price' => 8.90,   'cost_price' => 4.20,   'category' => 'Rasen & Saaten',         'tax_rate' => 0.07, 'track_stock' => true,  'stock_quantity' => 200, 'min_stock_level' => 30],
                ['sku' => 'GAR-R-002', 'name' => 'Rollrasen m²',                 'description' => 'Fertigrasensoden, direkt verlegbar',             'unit' => 'm²',   'price' => 4.50,   'cost_price' => 2.20,   'category' => 'Rasen & Saaten',         'tax_rate' => 0.19, 'track_stock' => false],
                // Erde, Substrate & Dünger
                ['sku' => 'GAR-E-001', 'name' => 'Pflanzerde 50 L',             'description' => 'Hochwertige Pflanzerde, pH-neutral',              'unit' => 'Sack', 'price' => 12.90,  'cost_price' => 6.00,   'category' => 'Erde, Substrate & Dünger', 'tax_rate' => 0.07, 'track_stock' => true, 'stock_quantity' => 150, 'min_stock_level' => 30],
                ['sku' => 'GAR-E-002', 'name' => 'Hornspäne 5 kg',              'description' => 'Organischer Langzeitdünger',                     'unit' => 'Sack', 'price' => 14.90,  'cost_price' => 7.00,   'category' => 'Erde, Substrate & Dünger', 'tax_rate' => 0.07, 'track_stock' => true, 'stock_quantity' => 80,  'min_stock_level' => 15],
                ['sku' => 'GAR-E-003', 'name' => 'Rindenmulch 70 L',            'description' => 'Grobes Rindenmulch als Bodenbedeckung',           'unit' => 'Sack', 'price' => 9.90,   'cost_price' => 4.50,   'category' => 'Erde, Substrate & Dünger', 'tax_rate' => 0.07, 'track_stock' => true, 'stock_quantity' => 120, 'min_stock_level' => 25],
                // Pflaster & Beläge
                ['sku' => 'GAR-P-001', 'name' => 'Betonpflasterstein grau m²',  'description' => 'Standardpflaster 10×10×8 cm',                   'unit' => 'm²',   'price' => 28.90,  'cost_price' => 14.00,  'category' => 'Pflaster & Beläge',      'tax_rate' => 0.19, 'track_stock' => false],
                ['sku' => 'GAR-P-002', 'name' => 'Granitsplitt 16/32 to',       'description' => 'Dekorativer Splitt als Wegbelag',                'unit' => 'to',   'price' => 85.00,  'cost_price' => 45.00,  'category' => 'Pflaster & Beläge',      'tax_rate' => 0.19, 'track_stock' => false],
                // Zäune & Sichtschutz
                ['sku' => 'GAR-Z-001', 'name' => 'Sichtschutzzaun WPC 180×180 cm', 'description' => 'Witterungsbeständig, wartungsarm',            'unit' => 'Stk.', 'price' => 89.90,  'cost_price' => 45.00,  'category' => 'Zäune & Sichtschutz',    'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 30, 'min_stock_level' => 5],
                // Bewässerung
                ['sku' => 'GAR-BEW-001', 'name' => 'Tropfschlauch 50 m',        'description' => 'Bewässerungsschlauch mit Tropfer',               'unit' => 'Rol.', 'price' => 38.90,  'cost_price' => 19.00,  'category' => 'Bewässerung',            'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 20, 'min_stock_level' => 4],
                // Dienstleistungen
                ['sku' => 'GAR-D-001', 'name' => 'Gartenplanung',               'description' => 'Planung und Beratung durch Gartenfachmann',       'unit' => 'Std.', 'price' => 75.00,  'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'GAR-D-002', 'name' => 'Rasenpflege & Mähen',         'description' => 'Rasenmähen und Kantenstechen',                   'unit' => 'Std.', 'price' => 45.00,  'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'GAR-D-003', 'name' => 'Bepflanzungsarbeiten',        'description' => 'Einpflanzen von Bäumen, Sträuchern und Stauden',  'unit' => 'Std.', 'price' => 55.00,  'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'GAR-D-004', 'name' => 'Pflasterarbeiten',            'description' => 'Verlegen von Pflastersteinen und Platten',        'unit' => 'Std.', 'price' => 65.00,  'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'GAR-D-005', 'name' => 'Heckenschnitt',               'description' => 'Schneiden und Formieren von Hecken',              'unit' => 'Std.', 'price' => 50.00,  'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'GAR-D-006', 'name' => 'Bewässerungsanlage Einbau',   'description' => 'Installation Bewässerungssystem',                'unit' => 'Psch.','price' => 850.00, 'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'GAR-D-007', 'name' => 'Rasenneuanlage m²',           'description' => 'Bodenvorbereitung und Rasenansaat',               'unit' => 'm²',   'price' => 18.00,  'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'GAR-D-008', 'name' => 'Entsorgung / Abtransport',    'description' => 'Entsorgung von Gartenabfällen und Schnittgut',    'unit' => 'Psch.','price' => 120.00, 'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
            ],
            'expense_categories' => [
                'Pflanzeneinkauf', 'Substrate & Dünger', 'Werkzeug & Maschinen',
                'Fahrzeugkosten', 'Kraftstoff', 'Miete & Nebenkosten',
                'Versicherungen', 'Marketing & Werbung', 'Entsorgung',
                'Büromaterial', 'Telefon & Internet', 'Schulungen', 'Sonstige Ausgaben',
            ],
        ];
    }

    // ── 2. Bauunternehmen ───────────────────────────────────────────────────

    private function getBauData(): array
    {
        return [
            'product_categories' => [
                ['name' => 'Rohbau & Mauerwerk',   'description' => 'Ziegel, Beton, Schalungen, Bewehrung',      'color' => '#92400e', 'sort_order' => 1],
                ['name' => 'Trockenbau',            'description' => 'Gipsplatten, Profile, Dämmmaterial',        'color' => '#d97706', 'sort_order' => 2],
                ['name' => 'Bodenbeläge Roh',       'description' => 'Estrich, Ausgleichsmassen, Abdichtung',     'color' => '#78716c', 'sort_order' => 3],
                ['name' => 'Dacharbeiten',          'description' => 'Dachziegel, Lattung, Folien',               'color' => '#374151', 'sort_order' => 4],
                ['name' => 'Baustoffe Allgemein',   'description' => 'Sand, Schotter, Kies, Betonstahl',          'color' => '#6b7280', 'sort_order' => 5],
                ['name' => 'Werkzeug & Maschinen',  'description' => 'Handwerkzeug, Mietmaschinen',               'color' => '#f59e0b', 'sort_order' => 6],
                ['name' => 'Dienstleistungen',      'description' => 'Arbeitsstunden, Pauschalleistungen',        'color' => '#4f46e5', 'sort_order' => 7],
            ],
            'products' => [
                // Rohbau & Mauerwerk
                ['sku' => 'BAU-R-001', 'name' => 'Kalksandstein KS 2DF',        'description' => '240×115×71 mm, Vollstein',                       'unit' => 'Stk.', 'price' => 0.89,   'cost_price' => 0.45,   'category' => 'Rohbau & Mauerwerk',   'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 5000, 'min_stock_level' => 1000],
                ['sku' => 'BAU-R-002', 'name' => 'Porenbeton Planstein 17,5 cm','description' => 'PP2-0,40-500, Wärmedämmstein',                   'unit' => 'Stk.', 'price' => 2.90,   'cost_price' => 1.50,   'category' => 'Rohbau & Mauerwerk',   'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 2000, 'min_stock_level' => 400],
                ['sku' => 'BAU-R-003', 'name' => 'Zement CEM II 25 kg',         'description' => 'Portlandhüttenzement CEM II/B-S 32,5 R',         'unit' => 'Sack', 'price' => 9.90,   'cost_price' => 5.50,   'category' => 'Rohbau & Mauerwerk',   'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 300,  'min_stock_level' => 60],
                ['sku' => 'BAU-R-004', 'name' => 'Betonstahl BSt 500 S 12mm 6m','description' => 'Bewehrungsstab 12 mm Durchmesser, 6 m lang',     'unit' => 'Stk.', 'price' => 12.50,  'cost_price' => 7.00,   'category' => 'Rohbau & Mauerwerk',   'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 500,  'min_stock_level' => 100],
                // Trockenbau
                ['sku' => 'BAU-T-001', 'name' => 'Gipskartonplatte 12,5 mm',    'description' => 'GKB 1200×2000×12,5 mm, Standard',               'unit' => 'Stk.', 'price' => 8.90,   'cost_price' => 4.80,   'category' => 'Trockenbau',           'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 200,  'min_stock_level' => 40],
                ['sku' => 'BAU-T-002', 'name' => 'CW-Profil 75mm 2,75m',        'description' => 'Stahlprofil für Trennwände',                     'unit' => 'Stk.', 'price' => 3.50,   'cost_price' => 1.80,   'category' => 'Trockenbau',           'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 300,  'min_stock_level' => 60],
                ['sku' => 'BAU-T-003', 'name' => 'Mineralwolle 100mm m²',       'description' => 'Wärmedämmmatte für Trockenbau',                  'unit' => 'm²',   'price' => 12.90,  'cost_price' => 6.50,   'category' => 'Trockenbau',           'tax_rate' => 0.19, 'track_stock' => false],
                // Bodenbeläge Roh
                ['sku' => 'BAU-B-001', 'name' => 'Estrich Fertigmischung 25 kg','description' => 'Zementestrich CT-C20-F4, schnelltrocknend',      'unit' => 'Sack', 'price' => 7.50,   'cost_price' => 4.00,   'category' => 'Bodenbeläge Roh',      'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 200,  'min_stock_level' => 40],
                // Baustoffe Allgemein
                ['sku' => 'BAU-A-001', 'name' => 'Kies 8/16 to',                'description' => 'Rundkorn-Kies für Fundamente und Drainagen',     'unit' => 'to',   'price' => 38.00,  'cost_price' => 22.00,  'category' => 'Baustoffe Allgemein',  'tax_rate' => 0.19, 'track_stock' => false],
                ['sku' => 'BAU-A-002', 'name' => 'Sand 0/2 to',                 'description' => 'Mauersand / Fugensand',                         'unit' => 'to',   'price' => 32.00,  'cost_price' => 18.00,  'category' => 'Baustoffe Allgemein',  'tax_rate' => 0.19, 'track_stock' => false],
                // Dienstleistungen
                ['sku' => 'BAU-D-001', 'name' => 'Maurerarbeiten',              'description' => 'Mauerwerk herstellen / versetzen',               'unit' => 'Std.', 'price' => 58.00,  'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'BAU-D-002', 'name' => 'Betonarbeiten',               'description' => 'Beton einbauen, verdichten und glätten',         'unit' => 'Std.', 'price' => 62.00,  'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'BAU-D-003', 'name' => 'Abrissarbeiten',              'description' => 'Rückbau und Abbrucharbeiten',                    'unit' => 'Std.', 'price' => 55.00,  'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'BAU-D-004', 'name' => 'Trockenbauarbeiten',          'description' => 'Montage Gipskarton, Profile und Dämmung',        'unit' => 'Std.', 'price' => 52.00,  'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'BAU-D-005', 'name' => 'Estricharbeiten',             'description' => 'Estrich einbringen und glätten',                 'unit' => 'm²',   'price' => 22.00,  'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'BAU-D-006', 'name' => 'Baustelleneinrichtung',       'description' => 'Auf- und Abbau Baustelle inkl. Gerüst',          'unit' => 'Psch.','price' => 450.00, 'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'BAU-D-007', 'name' => 'Bauberatung / Bauleitung',    'description' => 'Bauüberwachung und Dokumentation',               'unit' => 'Std.', 'price' => 85.00,  'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'BAU-D-008', 'name' => 'Schalungsarbeiten',           'description' => 'Aufbau und Abbau Betonschalung',                 'unit' => 'Std.', 'price' => 58.00,  'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
            ],
            'expense_categories' => [
                'Baumaterial', 'Werkzeugkauf', 'Maschinenmiete', 'Fahrzeugkosten',
                'Kraftstoff', 'Miete & Nebenkosten', 'Versicherungen',
                'Entsorgung', 'Marketing & Werbung', 'Büromaterial',
                'Telefon & Internet', 'Schulungen', 'Sonstige Ausgaben',
            ],
        ];
    }

    // ── 3. Raumausstattung & Fliesenarbeiten ────────────────────────────────

    private function getRaumausstattungData(): array
    {
        return [
            'product_categories' => [
                ['name' => 'Fliesen & Naturstein',  'description' => 'Wand- und Bodenfliesen, Mosaike, Naturstein',    'color' => '#78716c', 'sort_order' => 1],
                ['name' => 'Parkett & Laminat',     'description' => 'Holzparkett, Laminatböden, Vinyl',               'color' => '#92400e', 'sort_order' => 2],
                ['name' => 'Teppich & Beläge',      'description' => 'Teppichböden, PVC-Beläge, Linoleum',             'color' => '#4338ca', 'sort_order' => 3],
                ['name' => 'Kleber & Fugenmasse',   'description' => 'Fliesenkleber, Fugenmörtel, Silikon',            'color' => '#d97706', 'sort_order' => 4],
                ['name' => 'Zubehör & Randprofile', 'description' => 'Übergangsprofile, Sockelleisten, Treppenkanten', 'color' => '#6b7280', 'sort_order' => 5],
                ['name' => 'Dienstleistungen',      'description' => 'Verlegearbeiten, Beratung',                      'color' => '#4f46e5', 'sort_order' => 6],
            ],
            'products' => [
                // Fliesen
                ['sku' => 'RAU-F-001', 'name' => 'Bodenfliese 60×60 cm grau',   'description' => 'Feinsteinzeug, rektifiziert, R10',                'unit' => 'm²',   'price' => 28.90,  'cost_price' => 14.00,  'category' => 'Fliesen & Naturstein', 'tax_rate' => 0.19, 'track_stock' => false],
                ['sku' => 'RAU-F-002', 'name' => 'Wandfliese 30×60 cm weiß',    'description' => 'Keramik Wandfliese, poliert, Badzimmer',          'unit' => 'm²',   'price' => 22.90,  'cost_price' => 11.00,  'category' => 'Fliesen & Naturstein', 'tax_rate' => 0.19, 'track_stock' => false],
                ['sku' => 'RAU-F-003', 'name' => 'Terrassenfliese 40×80 cm',    'description' => 'Feinsteinzeug Outdoorfliese, R11',                'unit' => 'm²',   'price' => 34.90,  'cost_price' => 17.00,  'category' => 'Fliesen & Naturstein', 'tax_rate' => 0.19, 'track_stock' => false],
                ['sku' => 'RAU-F-004', 'name' => 'Marmor Bianco Carrara m²',    'description' => 'Naturstein poliert, 2 cm stark',                 'unit' => 'm²',   'price' => 89.00,  'cost_price' => 48.00,  'category' => 'Fliesen & Naturstein', 'tax_rate' => 0.19, 'track_stock' => false],
                // Parkett & Laminat
                ['sku' => 'RAU-P-001', 'name' => 'Echtholzparkett Eiche 15mm',  'description' => 'Fertigparkett, geölt, 15×120×1200 mm',           'unit' => 'm²',   'price' => 58.90,  'cost_price' => 32.00,  'category' => 'Parkett & Laminat',    'tax_rate' => 0.19, 'track_stock' => false],
                ['sku' => 'RAU-P-002', 'name' => 'Laminat 8 mm AC5',            'description' => 'Hochbelastbarer Laminatboden, Klick-System',     'unit' => 'm²',   'price' => 18.90,  'cost_price' => 9.00,   'category' => 'Parkett & Laminat',    'tax_rate' => 0.19, 'track_stock' => false],
                ['sku' => 'RAU-P-003', 'name' => 'Vinylboden SPC 5mm',          'description' => 'Wasserfest, für Bad und Küche geeignet',         'unit' => 'm²',   'price' => 24.90,  'cost_price' => 12.00,  'category' => 'Parkett & Laminat',    'tax_rate' => 0.19, 'track_stock' => false],
                // Kleber & Fugenmasse
                ['sku' => 'RAU-K-001', 'name' => 'Flexkleber 25 kg',            'description' => 'Verformungsfähiger Fliesenkleber C2TE',          'unit' => 'Sack', 'price' => 24.90,  'cost_price' => 12.50,  'category' => 'Kleber & Fugenmasse',  'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 100, 'min_stock_level' => 20],
                ['sku' => 'RAU-K-002', 'name' => 'Fugenmörtel 5 kg weiß',       'description' => 'Epoxid-Fugenmörtel, wasser- und fettabweisend',  'unit' => 'Eimer','price' => 19.90,  'cost_price' => 9.50,   'category' => 'Kleber & Fugenmasse',  'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 80,  'min_stock_level' => 15],
                ['sku' => 'RAU-K-003', 'name' => 'Sanitärsilikon 310 ml weiß',  'description' => 'Anti-Schimmel Silikon für Sanitärbereiche',      'unit' => 'Stk.', 'price' => 5.90,   'cost_price' => 2.80,   'category' => 'Kleber & Fugenmasse',  'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 150, 'min_stock_level' => 30],
                // Dienstleistungen
                ['sku' => 'RAU-D-001', 'name' => 'Fliesenverlegung Boden',      'description' => 'Fliesen verlegen inkl. Kleber und Fugen',        'unit' => 'm²',   'price' => 45.00,  'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'RAU-D-002', 'name' => 'Fliesenverlegung Wand',       'description' => 'Wandfliesen verlegen inkl. Kleber und Fugen',    'unit' => 'm²',   'price' => 55.00,  'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'RAU-D-003', 'name' => 'Parkettverlegung',            'description' => 'Parkett kleben oder schwimmend verlegen',        'unit' => 'm²',   'price' => 25.00,  'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'RAU-D-004', 'name' => 'Laminat-/Vinylverlegung',     'description' => 'Laminat oder Vinyl klick-verlegen',              'unit' => 'm²',   'price' => 15.00,  'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'RAU-D-005', 'name' => 'Fliesendemontage',            'description' => 'Alte Fliesen entfernen und entsorgen',           'unit' => 'm²',   'price' => 18.00,  'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'RAU-D-006', 'name' => 'Beratung & Aufmaß',           'description' => 'Beratung vor Ort und Aufmaß',                   'unit' => 'Psch.','price' => 90.00,  'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'RAU-D-007', 'name' => 'Teppichverlegung',            'description' => 'Teppich spannrahmen oder kleben',                'unit' => 'm²',   'price' => 12.00,  'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
            ],
            'expense_categories' => [
                'Flieseneinkauf', 'Bodenbelegeinkauf', 'Werkzeugkauf', 'Verbrauchsmaterial',
                'Fahrzeugkosten', 'Kraftstoff', 'Miete & Nebenkosten', 'Versicherungen',
                'Entsorgung', 'Marketing & Werbung', 'Büromaterial',
                'Telefon & Internet', 'Schulungen', 'Sonstige Ausgaben',
            ],
        ];
    }

    // ── 4. Gebäudetechnik ───────────────────────────────────────────────────

    private function getGebaudetechnikData(): array
    {
        return [
            'product_categories' => [
                ['name' => 'Heizung & Wärme',      'description' => 'Kessel, Pumpen, Heizkörper, Rohre',              'color' => '#dc2626', 'sort_order' => 1],
                ['name' => 'Sanitär & Wasser',     'description' => 'Rohre, Armaturen, Sanitärobjekte',               'color' => '#0ea5e9', 'sort_order' => 2],
                ['name' => 'Elektrotechnik',        'description' => 'Kabel, Schalter, Verteiler, Leuchten',           'color' => '#eab308', 'sort_order' => 3],
                ['name' => 'Klima & Lüftung',      'description' => 'Klimageräte, Lüftungsanlagen, Kanäle',           'color' => '#06b6d4', 'sort_order' => 4],
                ['name' => 'Solartechnik',          'description' => 'Solarmodule, Wechselrichter, Speicher',          'color' => '#f59e0b', 'sort_order' => 5],
                ['name' => 'Dienstleistungen',      'description' => 'Installation, Wartung, Inspektion',              'color' => '#4f46e5', 'sort_order' => 6],
            ],
            'products' => [
                // Heizung
                ['sku' => 'GEB-H-001', 'name' => 'Gasbrennwertkessel 24 kW',    'description' => 'Wandheizkessel Kombigerät, Effizienzklasse A',    'unit' => 'Stk.', 'price' => 1850.00, 'cost_price' => 1100.00, 'category' => 'Heizung & Wärme',    'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 5, 'min_stock_level' => 1],
                ['sku' => 'GEB-H-002', 'name' => 'Heizkörper Typ 22 600×1200', 'description' => 'Plattenheizkörper Bauhöhe 600 mm, Baulänge 1200 mm', 'unit' => 'Stk.', 'price' => 185.00, 'cost_price' => 95.00, 'category' => 'Heizung & Wärme',    'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 30, 'min_stock_level' => 5],
                ['sku' => 'GEB-H-003', 'name' => 'Kupferrohr 18mm 5m',          'description' => 'Halbhartrohr EN 1057 für Heizung/Wasser',         'unit' => 'Stg.', 'price' => 28.90,  'cost_price' => 15.00,  'category' => 'Heizung & Wärme',    'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 200, 'min_stock_level' => 40],
                // Sanitär
                ['sku' => 'GEB-S-001', 'name' => 'Dusche Komplett-Set',         'description' => 'Duschtasse 80×80 mit Armatur und Brause',         'unit' => 'Stk.', 'price' => 580.00, 'cost_price' => 310.00, 'category' => 'Sanitär & Wasser',   'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 8, 'min_stock_level' => 2],
                ['sku' => 'GEB-S-002', 'name' => 'WC-Set Stand/Wand',           'description' => 'WC-Becken mit Spülkasten und WC-Sitz',            'unit' => 'Stk.', 'price' => 320.00, 'cost_price' => 170.00, 'category' => 'Sanitär & Wasser',   'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 10, 'min_stock_level' => 2],
                ['sku' => 'GEB-S-003', 'name' => 'Absperrventil DN 22',         'description' => 'Kugelabsperrhahn Innengewinde',                   'unit' => 'Stk.', 'price' => 12.90,  'cost_price' => 6.50,   'category' => 'Sanitär & Wasser',   'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 100, 'min_stock_level' => 20],
                // Elektrotechnik
                ['sku' => 'GEB-E-001', 'name' => 'NYM-J 3×2,5mm² 100m',        'description' => 'Mantelleitung PVC, 3-adrig',                     'unit' => 'Rol.', 'price' => 89.00,  'cost_price' => 50.00,  'category' => 'Elektrotechnik',     'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 50, 'min_stock_level' => 10],
                ['sku' => 'GEB-E-002', 'name' => 'Unterputz-Steckdose SCHUKO', 'description' => 'UP-Steckdose mit Schutzkontakt',                  'unit' => 'Stk.', 'price' => 6.90,   'cost_price' => 3.20,   'category' => 'Elektrotechnik',     'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 200, 'min_stock_level' => 40],
                ['sku' => 'GEB-E-003', 'name' => 'LS-Schalter 16A B-Charakteristik', 'description' => 'Leitungsschutzschalter 1-polig',            'unit' => 'Stk.', 'price' => 8.90,   'cost_price' => 4.50,   'category' => 'Elektrotechnik',     'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 100, 'min_stock_level' => 20],
                // Dienstleistungen
                ['sku' => 'GEB-D-001', 'name' => 'Heizungsinstallation',        'description' => 'Montage und Inbetriebnahme Heizungsanlage',       'unit' => 'Std.', 'price' => 75.00,  'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'GEB-D-002', 'name' => 'Sanitärinstallation',         'description' => 'Montage Sanitärobjekte und Verrohrung',           'unit' => 'Std.', 'price' => 72.00,  'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'GEB-D-003', 'name' => 'Elektroinstallation',         'description' => 'Verlegen von Kabeln und Anschlussarbeiten',       'unit' => 'Std.', 'price' => 78.00,  'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'GEB-D-004', 'name' => 'Wartung Heizungsanlage',      'description' => 'Jährliche Wartung und Inspektion',                'unit' => 'Psch.','price' => 180.00, 'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'GEB-D-005', 'name' => 'Klimaanlage Montage',         'description' => 'Installation Split-Klimaanlage',                 'unit' => 'Psch.','price' => 450.00, 'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'GEB-D-006', 'name' => 'Heizlastberechnung',          'description' => 'Planung und Heizlastberechnung nach DIN 12831',   'unit' => 'Psch.','price' => 350.00, 'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'GEB-D-007', 'name' => 'Rohrinstandsetzung',          'description' => 'Reparatur und Austausch defekter Rohre',          'unit' => 'Std.', 'price' => 72.00,  'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
            ],
            'expense_categories' => [
                'Materialeinkauf Heizung', 'Materialeinkauf Sanitär', 'Materialeinkauf Elektro',
                'Werkzeugkauf', 'Maschinenmiete', 'Fahrzeugkosten', 'Kraftstoff',
                'Miete & Nebenkosten', 'Versicherungen', 'Marketing & Werbung',
                'Büromaterial', 'Telefon & Internet', 'Schulungen', 'Sonstige Ausgaben',
            ],
        ];
    }

    // ── 5. Logistik & Palettenhandel ────────────────────────────────────────

    private function getLogistikData(): array
    {
        return [
            'product_categories' => [
                ['name' => 'Europaletten',       'description' => 'EUR- und EPAL-Paletten, verschiedene Güten',   'color' => '#92400e', 'sort_order' => 1],
                ['name' => 'Industriepaletten',  'description' => 'H1, H2, CP-Paletten, Sondermaße',             'color' => '#78716c', 'sort_order' => 2],
                ['name' => 'Einwegpaletten',     'description' => 'Einwegpaletten aus Holz und Pressholz',        'color' => '#a16207', 'sort_order' => 3],
                ['name' => 'Kunststoffpaletten', 'description' => 'Hygienische Kunststoffpaletten',               'color' => '#0369a1', 'sort_order' => 4],
                ['name' => 'Verpackung',         'description' => 'Folie, Kantenschutz, Spanngurt',               'color' => '#059669', 'sort_order' => 5],
                ['name' => 'Transport & Logistik', 'description' => 'Frachten, Umschlag, Lager',                  'color' => '#4f46e5', 'sort_order' => 6],
            ],
            'products' => [
                // Europaletten
                ['sku' => 'LOG-E-001', 'name' => 'EPAL Europalette Güteklasse A', 'description' => '1200×800×144 mm, repariert, Klasse A (neuwertig)', 'unit' => 'Stk.', 'price' => 12.50,  'cost_price' => 8.00,   'category' => 'Europaletten',       'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 2000, 'min_stock_level' => 300],
                ['sku' => 'LOG-E-002', 'name' => 'EPAL Europalette Güteklasse B', 'description' => '1200×800 mm, Klasse B (gebraucht, guter Zustand)', 'unit' => 'Stk.', 'price' => 8.50,   'cost_price' => 5.00,   'category' => 'Europaletten',       'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 3000, 'min_stock_level' => 500],
                ['sku' => 'LOG-E-003', 'name' => 'EPAL Europalette Güteklasse C', 'description' => '1200×800 mm, Klasse C (gebraucht, Standardware)', 'unit' => 'Stk.', 'price' => 5.50,   'cost_price' => 3.20,   'category' => 'Europaletten',       'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 5000, 'min_stock_level' => 800],
                ['sku' => 'LOG-E-004', 'name' => 'Europalette neu ungestempelt', 'description' => 'Neue Palette ohne EPAL-Stempel',                  'unit' => 'Stk.', 'price' => 14.90,  'cost_price' => 9.50,   'category' => 'Europaletten',       'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 500,  'min_stock_level' => 100],
                // Industriepaletten
                ['sku' => 'LOG-I-001', 'name' => 'Palette 1200×1000 H1',        'description' => 'Industriepalette 1200×1000 mm, 4-Wege, Klasse A', 'unit' => 'Stk.', 'price' => 9.90,   'cost_price' => 6.00,   'category' => 'Industriepaletten',  'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 1000, 'min_stock_level' => 200],
                ['sku' => 'LOG-I-002', 'name' => 'Palette 1200×1000 H2',        'description' => 'Industriepalette 1200×1000 mm, Klasse B',          'unit' => 'Stk.', 'price' => 7.50,   'cost_price' => 4.50,   'category' => 'Industriepaletten',  'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 1500, 'min_stock_level' => 300],
                // Einwegpaletten
                ['sku' => 'LOG-EW-001', 'name' => 'Einwegpalette 1200×800 mm', 'description' => 'Leichte Einwegpalette aus Nadelholz',              'unit' => 'Stk.', 'price' => 6.90,   'cost_price' => 4.20,   'category' => 'Einwegpaletten',     'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 2000, 'min_stock_level' => 400],
                ['sku' => 'LOG-EW-002', 'name' => 'Pressholzpalette 1200×800', 'description' => 'ISPM-15 behandelt für Export',                    'unit' => 'Stk.', 'price' => 8.90,   'cost_price' => 5.50,   'category' => 'Einwegpaletten',     'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 800,  'min_stock_level' => 150],
                // Verpackung
                ['sku' => 'LOG-V-001', 'name' => 'Stretchfolie 23my 500mm',    'description' => 'Handstretchfolie 300m, schwarz',                  'unit' => 'Rol.', 'price' => 7.90,   'cost_price' => 4.00,   'category' => 'Verpackung',         'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 200,  'min_stock_level' => 40],
                ['sku' => 'LOG-V-002', 'name' => 'Kantenschutz Karton 50mm',   'description' => 'Kantenschutzwinkel 2000 mm lang',                 'unit' => 'Stk.', 'price' => 0.85,   'cost_price' => 0.40,   'category' => 'Verpackung',         'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 1000, 'min_stock_level' => 200],
                // Transport & Logistik
                ['sku' => 'LOG-T-001', 'name' => 'Transport bis 100 km',       'description' => 'Palettenlieferung bis 100 km',                    'unit' => 'Fhrt.','price' => 95.00,  'category' => 'Transport & Logistik', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'LOG-T-002', 'name' => 'Transport bis 250 km',       'description' => 'Palettenlieferung bis 250 km',                    'unit' => 'Fhrt.','price' => 195.00, 'category' => 'Transport & Logistik', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'LOG-T-003', 'name' => 'Abholung / Rücknahme',       'description' => 'Palettenabholung beim Kunden',                   'unit' => 'Fhrt.','price' => 75.00,  'category' => 'Transport & Logistik', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'LOG-T-004', 'name' => 'Palettensortierung',         'description' => 'Sichtung, Sortierung und Klassifizierung',        'unit' => 'Std.', 'price' => 35.00,  'category' => 'Transport & Logistik', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'LOG-T-005', 'name' => 'Palettenreparatur',          'description' => 'Instandsetzung beschädigter Paletten',            'unit' => 'Stk.', 'price' => 4.50,   'category' => 'Transport & Logistik', 'tax_rate' => 0.19, 'is_service' => true],
            ],
            'expense_categories' => [
                'Paletteneinkauf', 'Verpackungsmaterial', 'Fahrzeugkosten (LKW)',
                'Kraftstoff', 'Mautgebühren', 'Lager & Miete',
                'Versicherungen', 'Reparatur & Instandhaltung', 'Marketing & Werbung',
                'Büromaterial', 'Telefon & Internet', 'Sonstige Ausgaben',
            ],
        ];
    }

    // ── 6. Handelsunternehmen ───────────────────────────────────────────────

    private function getHandelData(): array
    {
        return [
            'product_categories' => [
                ['name' => 'Waren Gruppe A',   'description' => 'Hauptsortiment Gruppe A (A-Artikel)',   'color' => '#1d4ed8', 'sort_order' => 1],
                ['name' => 'Waren Gruppe B',   'description' => 'Mittleres Sortiment Gruppe B (B-Artikel)','color' => '#2563eb', 'sort_order' => 2],
                ['name' => 'Waren Gruppe C',   'description' => 'Ergänzungssortiment Gruppe C (C-Artikel)','color' => '#3b82f6', 'sort_order' => 3],
                ['name' => 'Verbrauchsmaterial','description' => 'Bürobedarf, Betriebsmittel',            'color' => '#6b7280', 'sort_order' => 4],
                ['name' => 'Dienstleistungen', 'description' => 'Beratung, Lieferung, Service',           'color' => '#4f46e5', 'sort_order' => 5],
            ],
            'products' => [
                ['sku' => 'HAN-A-001', 'name' => 'Artikel A01',          'description' => 'Hauptprodukt – bitte anpassen',      'unit' => 'Stk.', 'price' => 49.90,  'cost_price' => 25.00,  'category' => 'Waren Gruppe A', 'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 200, 'min_stock_level' => 30],
                ['sku' => 'HAN-A-002', 'name' => 'Artikel A02',          'description' => 'Hauptprodukt – bitte anpassen',      'unit' => 'Stk.', 'price' => 89.90,  'cost_price' => 45.00,  'category' => 'Waren Gruppe A', 'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 150, 'min_stock_level' => 20],
                ['sku' => 'HAN-A-003', 'name' => 'Artikel A03',          'description' => 'Hauptprodukt – bitte anpassen',      'unit' => 'Stk.', 'price' => 129.90, 'cost_price' => 65.00,  'category' => 'Waren Gruppe A', 'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 100, 'min_stock_level' => 15],
                ['sku' => 'HAN-B-001', 'name' => 'Artikel B01',          'description' => 'Nebenprodukt – bitte anpassen',      'unit' => 'Stk.', 'price' => 29.90,  'cost_price' => 15.00,  'category' => 'Waren Gruppe B', 'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 300, 'min_stock_level' => 50],
                ['sku' => 'HAN-B-002', 'name' => 'Artikel B02',          'description' => 'Nebenprodukt – bitte anpassen',      'unit' => 'Stk.', 'price' => 19.90,  'cost_price' => 10.00,  'category' => 'Waren Gruppe B', 'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 400, 'min_stock_level' => 80],
                ['sku' => 'HAN-C-001', 'name' => 'Artikel C01',          'description' => 'Ergänzungsartikel – bitte anpassen', 'unit' => 'Stk.', 'price' => 9.90,   'cost_price' => 4.50,   'category' => 'Waren Gruppe C', 'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 500, 'min_stock_level' => 100],
                ['sku' => 'HAN-V-001', 'name' => 'Büromaterial Paket',   'description' => 'Papier, Stifte, Ordner etc.',        'unit' => 'Pkt.', 'price' => 24.90,  'cost_price' => 12.00,  'category' => 'Verbrauchsmaterial', 'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 50, 'min_stock_level' => 10],
                ['sku' => 'HAN-D-001', 'name' => 'Beratung',             'description' => 'Fachberatung und Produktberatung',   'unit' => 'Std.', 'price' => 75.00,  'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'HAN-D-002', 'name' => 'Lieferung pauschal',   'description' => 'Lieferkostenpauschale',              'unit' => 'Psch.','price' => 15.00,  'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'HAN-D-003', 'name' => 'Expresslieferung',     'description' => 'Expresslieferung am nächsten Tag',   'unit' => 'Psch.','price' => 35.00,  'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
            ],
            'expense_categories' => [
                'Wareneinkauf', 'Frachtkosten', 'Lager & Logistik',
                'Fahrzeugkosten', 'Miete & Nebenkosten', 'Versicherungen',
                'Marketing & Werbung', 'Verpackungsmaterial',
                'Büromaterial', 'Telefon & Internet', 'Schulungen', 'Sonstige Ausgaben',
            ],
        ];
    }

    // ── 7. Sonstige Dienstleistungen ────────────────────────────────────────

    private function getDienstleistungData(): array
    {
        return [
            'product_categories' => [
                ['name' => 'Beratung',          'description' => 'Fach- und Unternehmensberatung',       'color' => '#4f46e5', 'sort_order' => 1],
                ['name' => 'IT & Technik',      'description' => 'IT-Support, Softwareentwicklung',      'color' => '#0369a1', 'sort_order' => 2],
                ['name' => 'Schulungen',        'description' => 'Trainings, Workshops, Seminare',       'color' => '#059669', 'sort_order' => 3],
                ['name' => 'Verwaltung',        'description' => 'Buchhaltung, Sekretariat, Backoffice', 'color' => '#6b7280', 'sort_order' => 4],
                ['name' => 'Projektmanagement', 'description' => 'Projektleitung und -koordination',     'color' => '#dc2626', 'sort_order' => 5],
                ['name' => 'Produkte',          'description' => 'Sachmittel und Lizenzen',              'color' => '#a16207', 'sort_order' => 6],
            ],
            'products' => [
                // Beratung
                ['sku' => 'DL-B-001', 'name' => 'Erstberatung',             'description' => 'Erstgespräch und Bedarfsanalyse',           'unit' => 'Psch.','price' => 150.00, 'category' => 'Beratung',          'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'DL-B-002', 'name' => 'Beratungsstunde',          'description' => 'Laufende Beratung je Stunde',               'unit' => 'Std.', 'price' => 90.00,  'category' => 'Beratung',          'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'DL-B-003', 'name' => 'Beratungspaket 10 Std.',   'description' => '10 Beratungsstunden im Paket',              'unit' => 'Pkt.', 'price' => 790.00, 'category' => 'Beratung',          'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'DL-B-004', 'name' => 'Konzepterstellung',        'description' => 'Erstellung eines Konzepts oder Gutachtens', 'unit' => 'Psch.','price' => 450.00, 'category' => 'Beratung',          'tax_rate' => 0.19, 'is_service' => true],
                // IT & Technik
                ['sku' => 'DL-IT-001', 'name' => 'IT-Support Stunde',      'description' => 'Technischer Support vor Ort oder remote',   'unit' => 'Std.', 'price' => 95.00,  'category' => 'IT & Technik',      'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'DL-IT-002', 'name' => 'Website-Erstellung',     'description' => 'Erstellung einer professionellen Website',  'unit' => 'Psch.','price' => 1500.00,'category' => 'IT & Technik',      'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'DL-IT-003', 'name' => 'Software-Lizenz jährl.', 'description' => 'Jährliche Softwarelizenz',                 'unit' => 'Jahr', 'price' => 240.00, 'cost_price' => 120.00, 'category' => 'IT & Technik',      'tax_rate' => 0.19, 'track_stock' => false],
                // Schulungen
                ['sku' => 'DL-S-001', 'name' => 'Workshop halbtägig',      'description' => 'Schulung / Workshop 4 Stunden',             'unit' => 'Tag',  'price' => 490.00, 'category' => 'Schulungen',        'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'DL-S-002', 'name' => 'Workshop ganztägig',      'description' => 'Schulung / Workshop ganzer Tag 8 Stunden',  'unit' => 'Tag',  'price' => 890.00, 'category' => 'Schulungen',        'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'DL-S-003', 'name' => 'Online-Schulung',         'description' => 'Webinar oder Online-Kurs',                 'unit' => 'Std.', 'price' => 65.00,  'category' => 'Schulungen',        'tax_rate' => 0.19, 'is_service' => true],
                // Verwaltung
                ['sku' => 'DL-V-001', 'name' => 'Buchhaltung monatlich',   'description' => 'Laufende Buchführung je Monat',             'unit' => 'Mon.', 'price' => 250.00, 'category' => 'Verwaltung',        'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'DL-V-002', 'name' => 'Schreibservice Stunde',   'description' => 'Texterfassung und Sachbearbeitung',         'unit' => 'Std.', 'price' => 45.00,  'category' => 'Verwaltung',        'tax_rate' => 0.19, 'is_service' => true],
                // Projektmanagement
                ['sku' => 'DL-PM-001', 'name' => 'Projektleitung Stunde',  'description' => 'Projektmanagement und -koordination',       'unit' => 'Std.', 'price' => 110.00, 'category' => 'Projektmanagement', 'tax_rate' => 0.19, 'is_service' => true],
                ['sku' => 'DL-PM-002', 'name' => 'Projektpauschale klein', 'description' => 'Kleines Projekt bis 10 Stunden',            'unit' => 'Psch.','price' => 990.00, 'category' => 'Projektmanagement', 'tax_rate' => 0.19, 'is_service' => true],
                // Produkte
                ['sku' => 'DL-P-001', 'name' => 'Fahrtkosten pauschal',    'description' => 'Fahrtkosten / Auslagenpauschale',           'unit' => 'Psch.','price' => 35.00,  'category' => 'Produkte',          'tax_rate' => 0.19, 'is_service' => false],
            ],
            'expense_categories' => [
                'Büromiete & Nebenkosten', 'Fahrzeugkosten', 'Reisekosten',
                'Hard- & Software', 'Versicherungen', 'Marketing & Werbung',
                'Büromaterial', 'Telefon & Internet', 'Fortbildung & Schulung',
                'Steuer & Rechtsberatung', 'Sonstige Ausgaben',
            ],
        ];
    }
}
