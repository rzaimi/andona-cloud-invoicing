<?php

namespace App\Console\Commands;

use App\Modules\Company\Models\Company;
use App\Modules\Expense\Models\ExpenseCategory;
use App\Modules\Product\Models\Category;
use App\Modules\Product\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class InitializeCompanyData extends Command
{
    protected $signature = 'company:init 
                            {company_id : The UUID of the company}
                            {--type= : Company type (bau, gartenbau, baeckerei, restaurant, generic)}';

    protected $description = 'Initialize company data with products, categories, and expense categories based on company type';

    public function handle()
    {
        $companyId = $this->argument('company_id');
        $companyType = $this->option('type') ?: 'generic';

        // Validate company exists
        $company = Company::find($companyId);
        if (!$company) {
            $this->error("Company with ID {$companyId} not found.");
            return Command::FAILURE;
        }

        $this->info("Initializing data for company: {$company->name} (Type: {$companyType})");

        // Get data based on company type
        $data = $this->getCompanyTypeData($companyType);

        if (!$data) {
            $this->error("Unknown company type: {$companyType}");
            $this->info("Available types: bau, gartenbau, baeckerei, restaurant, generic");
            return Command::FAILURE;
        }

        // Create product categories
        $this->info("Creating product categories...");
        $categoryMap = $this->createProductCategories($companyId, $data['product_categories']);

        // Create products
        $this->info("Creating products...");
        $this->createProducts($companyId, $data['products'], $categoryMap);

        // Create expense categories
        $this->info("Creating expense categories...");
        $this->createExpenseCategories($companyId, $data['expense_categories']);

        $this->info("✓ Company data initialized successfully!");
        $this->newLine();
        $this->info("Summary:");
        $this->line("  - Product Categories: " . count($categoryMap));
        $this->line("  - Products: " . count($data['products']));
        $this->line("  - Expense Categories: " . count($data['expense_categories']));

        return Command::SUCCESS;
    }

    private function createProductCategories($companyId, array $categories): array
    {
        $categoryMap = [];

        foreach ($categories as $categoryData) {
            $category = Category::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'name' => $categoryData['name'],
                ],
                [
                    'description' => $categoryData['description'] ?? null,
                    'color' => $categoryData['color'] ?? null,
                    'icon' => $categoryData['icon'] ?? null,
                    'sort_order' => $categoryData['sort_order'] ?? 0,
                    'is_active' => true,
                ]
            );

            $categoryMap[$categoryData['name']] = $category->id;
            $this->line("  ✓ Created category: {$category->name}");
        }

        return $categoryMap;
    }

    private function createProducts($companyId, array $products, array $categoryMap): void
    {
        foreach ($products as $productData) {
            $categoryId = null;
            if (isset($productData['category']) && isset($categoryMap[$productData['category']])) {
                $categoryId = $categoryMap[$productData['category']];
            }

            Product::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'sku' => $productData['sku'],
                ],
                [
                    'name' => $productData['name'],
                    'description' => $productData['description'] ?? null,
                    'unit' => $productData['unit'] ?? 'Stk.',
                    'price' => $productData['price'],
                    'cost_price' => $productData['cost_price'] ?? null,
                    'category_id' => $categoryId,
                    'tax_rate' => $productData['tax_rate'] ?? 0.19,
                    'stock_quantity' => $productData['stock_quantity'] ?? 0,
                    'min_stock_level' => $productData['min_stock_level'] ?? 0,
                    'track_stock' => $productData['track_stock'] ?? false,
                    'is_service' => $productData['is_service'] ?? false,
                    'status' => $productData['status'] ?? 'active',
                ]
            );

            $this->line("  ✓ Created product: {$productData['name']} (SKU: {$productData['sku']})");
        }
    }

    private function createExpenseCategories($companyId, array $categories): void
    {
        foreach ($categories as $categoryName) {
            ExpenseCategory::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'name' => $categoryName,
                ]
            );

            $this->line("  ✓ Created expense category: {$categoryName}");
        }
    }

    private function getCompanyTypeData(string $type): ?array
    {
        return match ($type) {
            'bau' => $this->getBauData(),
            'gartenbau' => $this->getGartenbauData(),
            'baeckerei' => $this->getBaeckereiData(),
            'restaurant' => $this->getRestaurantData(),
            'generic' => $this->getGenericData(),
            default => null,
        };
    }

    private function getBauData(): array
    {
        return [
            'product_categories' => [
                ['name' => 'Baumaterialien', 'description' => 'Zement, Ziegel, Holz, etc.', 'color' => '#8B4513', 'sort_order' => 1],
                ['name' => 'Werkzeuge', 'description' => 'Handwerkzeuge und Maschinen', 'color' => '#FFA500', 'sort_order' => 2],
                ['name' => 'Dienstleistungen', 'description' => 'Beratung, Planung, Montage', 'color' => '#4169E1', 'sort_order' => 3],
                ['name' => 'Sanitär', 'description' => 'Rohre, Armaturen, Installationen', 'color' => '#00CED1', 'sort_order' => 4],
                ['name' => 'Elektro', 'description' => 'Kabel, Schalter, Installationen', 'color' => '#FFD700', 'sort_order' => 5],
            ],
            'products' => [
                ['name' => 'Zement 25kg', 'sku' => 'BAU-ZEM-001', 'description' => 'Portlandzement 25kg Sack', 'unit' => 'Sack', 'price' => 8.50, 'cost_price' => 5.20, 'category' => 'Baumaterialien', 'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 100, 'min_stock_level' => 20],
                ['name' => 'Ziegelstein', 'sku' => 'BAU-ZIE-001', 'description' => 'Standard Ziegelstein', 'unit' => 'Stk.', 'price' => 0.45, 'cost_price' => 0.25, 'category' => 'Baumaterialien', 'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 5000, 'min_stock_level' => 1000],
                ['name' => 'Betonmischung', 'sku' => 'BAU-BET-001', 'description' => 'Fertigmischung 40kg', 'unit' => 'Sack', 'price' => 6.90, 'cost_price' => 4.10, 'category' => 'Baumaterialien', 'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 80, 'min_stock_level' => 15],
                ['name' => 'Hammer', 'sku' => 'BAU-HAM-001', 'description' => 'Universaler Hammer 500g', 'unit' => 'Stk.', 'price' => 12.90, 'cost_price' => 7.50, 'category' => 'Werkzeuge', 'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 25, 'min_stock_level' => 5],
                ['name' => 'Bohrmaschine', 'sku' => 'BAU-BOH-001', 'description' => 'Akku-Bohrmaschine 18V', 'unit' => 'Stk.', 'price' => 89.90, 'cost_price' => 55.00, 'category' => 'Werkzeuge', 'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 10, 'min_stock_level' => 2],
                ['name' => 'Beratung', 'sku' => 'BAU-BER-001', 'description' => 'Baubetreuung und Beratung', 'unit' => 'Std.', 'price' => 75.00, 'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['name' => 'Planung', 'sku' => 'BAU-PLA-001', 'description' => 'Architektur und Planung', 'unit' => 'Std.', 'price' => 95.00, 'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['name' => 'Montage', 'sku' => 'BAU-MON-001', 'description' => 'Montage und Installation', 'unit' => 'Std.', 'price' => 65.00, 'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['name' => 'Kupferrohr 15mm', 'sku' => 'BAU-ROH-001', 'description' => 'Kupferrohr 15mm x 2m', 'unit' => 'm', 'price' => 8.50, 'cost_price' => 5.00, 'category' => 'Sanitär', 'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 200, 'min_stock_level' => 50],
                ['name' => 'Waschbecken', 'sku' => 'BAU-WAS-001', 'description' => 'Waschbecken weiß', 'unit' => 'Stk.', 'price' => 125.00, 'cost_price' => 75.00, 'category' => 'Sanitär', 'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 15, 'min_stock_level' => 3],
                ['name' => 'Elektrokabel 2.5mm²', 'sku' => 'BAU-KAB-001', 'description' => 'NYM-J 3x2.5mm²', 'unit' => 'm', 'price' => 2.50, 'cost_price' => 1.40, 'category' => 'Elektro', 'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 500, 'min_stock_level' => 100],
                ['name' => 'Steckdose', 'sku' => 'BAU-STE-001', 'description' => 'Steckdose mit Schutzkontakt', 'unit' => 'Stk.', 'price' => 8.90, 'cost_price' => 4.50, 'category' => 'Elektro', 'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 50, 'min_stock_level' => 10],
            ],
            'expense_categories' => [
                'Materialkosten',
                'Werkzeugkauf',
                'Fahrzeugkosten',
                'Miete & Nebenkosten',
                'Versicherungen',
                'Marketing & Werbung',
                'Schulungen',
                'Lizenzen & Gebühren',
                'Büromaterial',
                'Telefon & Internet',
                'Beratungskosten',
                'Sonstige Ausgaben',
            ],
        ];
    }

    private function getGartenbauData(): array
    {
        return [
            'product_categories' => [
                ['name' => 'Pflanzen', 'description' => 'Bäume, Sträucher, Blumen', 'color' => '#228B22', 'sort_order' => 1],
                ['name' => 'Erde & Dünger', 'description' => 'Pflanzerde, Düngemittel', 'color' => '#8B4513', 'sort_order' => 2],
                ['name' => 'Gartenwerkzeuge', 'description' => 'Schaufeln, Rechen, Scheren', 'color' => '#FFA500', 'sort_order' => 3],
                ['name' => 'Bewässerung', 'description' => 'Schläuche, Sprinkler, Systeme', 'color' => '#00CED1', 'sort_order' => 4],
                ['name' => 'Dienstleistungen', 'description' => 'Gartenpflege, Planung', 'color' => '#4169E1', 'sort_order' => 5],
            ],
            'products' => [
                ['name' => 'Rhododendron', 'sku' => 'GAR-RHO-001', 'description' => 'Rhododendron Strauch 40-60cm', 'unit' => 'Stk.', 'price' => 24.90, 'cost_price' => 12.00, 'category' => 'Pflanzen', 'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 50, 'min_stock_level' => 10],
                ['name' => 'Rasenmischung', 'sku' => 'GAR-RAS-001', 'description' => 'Rasensamen 1kg', 'unit' => 'kg', 'price' => 8.90, 'cost_price' => 4.50, 'category' => 'Pflanzen', 'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 30, 'min_stock_level' => 5],
                ['name' => 'Pflanzerde 50L', 'sku' => 'GAR-ERD-001', 'description' => 'Hochwertige Pflanzerde 50 Liter', 'unit' => 'Sack', 'price' => 12.90, 'cost_price' => 7.00, 'category' => 'Erde & Dünger', 'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 40, 'min_stock_level' => 8],
                ['name' => 'Universaldünger', 'sku' => 'GAR-DUE-001', 'description' => 'Flüssigdünger 1 Liter', 'unit' => 'Flasche', 'price' => 6.50, 'cost_price' => 3.20, 'category' => 'Erde & Dünger', 'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 60, 'min_stock_level' => 12],
                ['name' => 'Gartenschere', 'sku' => 'GAR-SCH-001', 'description' => 'Bypass-Gartenschere', 'unit' => 'Stk.', 'price' => 18.90, 'cost_price' => 10.00, 'category' => 'Gartenwerkzeuge', 'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 20, 'min_stock_level' => 4],
                ['name' => 'Gartenschaufel', 'sku' => 'GAR-SCH-002', 'description' => 'Stabile Gartenschaufel', 'unit' => 'Stk.', 'price' => 22.90, 'cost_price' => 12.00, 'category' => 'Gartenwerkzeuge', 'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 15, 'min_stock_level' => 3],
                ['name' => 'Gartenschlauch 20m', 'sku' => 'GAR-SCH-003', 'description' => 'Gartenschlauch 20 Meter', 'unit' => 'Stk.', 'price' => 29.90, 'cost_price' => 15.00, 'category' => 'Bewässerung', 'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 25, 'min_stock_level' => 5],
                ['name' => 'Sprinkler', 'sku' => 'GAR-SPR-001', 'description' => 'Viereckregner', 'unit' => 'Stk.', 'price' => 34.90, 'cost_price' => 18.00, 'category' => 'Bewässerung', 'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 12, 'min_stock_level' => 3],
                ['name' => 'Gartenpflege', 'sku' => 'GAR-PFL-001', 'description' => 'Rasenmähen und Pflege', 'unit' => 'Std.', 'price' => 45.00, 'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
                ['name' => 'Gartenplanung', 'sku' => 'GAR-PLA-001', 'description' => 'Gartenplanung und Beratung', 'unit' => 'Std.', 'price' => 65.00, 'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
            ],
            'expense_categories' => [
                'Pflanzenkauf',
                'Erde & Dünger',
                'Werkzeugkauf',
                'Fahrzeugkosten',
                'Miete & Nebenkosten',
                'Versicherungen',
                'Marketing & Werbung',
                'Schulungen',
                'Lizenzen & Gebühren',
                'Büromaterial',
                'Telefon & Internet',
                'Sonstige Ausgaben',
            ],
        ];
    }

    private function getBaeckereiData(): array
    {
        return [
            'product_categories' => [
                ['name' => 'Brot & Brötchen', 'description' => 'Verschiedene Brotsorten', 'color' => '#D2691E', 'sort_order' => 1],
                ['name' => 'Kuchen & Torten', 'description' => 'Torten und Kuchen', 'color' => '#FF69B4', 'sort_order' => 2],
                ['name' => 'Gebäck', 'description' => 'Kekse, Plätzchen, Kleingebäck', 'color' => '#FFD700', 'sort_order' => 3],
                ['name' => 'Zutaten', 'description' => 'Mehl, Zucker, etc.', 'color' => '#F0E68C', 'sort_order' => 4],
                ['name' => 'Dienstleistungen', 'description' => 'Catering, Events', 'color' => '#4169E1', 'sort_order' => 5],
            ],
            'products' => [
                ['name' => 'Vollkornbrot', 'sku' => 'BAE-BRO-001', 'description' => 'Vollkornbrot 1kg', 'unit' => 'Stk.', 'price' => 4.50, 'cost_price' => 1.80, 'category' => 'Brot & Brötchen', 'tax_rate' => 0.07, 'track_stock' => false],
                ['name' => 'Weißbrot', 'sku' => 'BAE-BRO-002', 'description' => 'Weißbrot 1kg', 'unit' => 'Stk.', 'price' => 3.90, 'cost_price' => 1.50, 'category' => 'Brot & Brötchen', 'tax_rate' => 0.07, 'track_stock' => false],
                ['name' => 'Brötchen 6er', 'sku' => 'BAE-BRO-003', 'description' => 'Frische Brötchen 6 Stück', 'unit' => 'Packung', 'price' => 2.90, 'cost_price' => 1.00, 'category' => 'Brot & Brötchen', 'tax_rate' => 0.07, 'track_stock' => false],
                ['name' => 'Schwarzwälder Kirschtorte', 'sku' => 'BAE-TOR-001', 'description' => 'Schwarzwälder Kirschtorte', 'unit' => 'Stk.', 'price' => 28.90, 'cost_price' => 12.00, 'category' => 'Kuchen & Torten', 'tax_rate' => 0.07, 'track_stock' => false],
                ['name' => 'Sachertorte', 'sku' => 'BAE-TOR-002', 'description' => 'Sachertorte', 'unit' => 'Stk.', 'price' => 32.90, 'cost_price' => 14.00, 'category' => 'Kuchen & Torten', 'tax_rate' => 0.07, 'track_stock' => false],
                ['name' => 'Butterkekse', 'sku' => 'BAE-GEB-001', 'description' => 'Butterkekse 250g', 'unit' => 'Packung', 'price' => 3.50, 'cost_price' => 1.20, 'category' => 'Gebäck', 'tax_rate' => 0.07, 'track_stock' => false],
                ['name' => 'Plätzchen', 'sku' => 'BAE-GEB-002', 'description' => 'Weihnachtsplätzchen 500g', 'unit' => 'Packung', 'price' => 8.90, 'cost_price' => 3.50, 'category' => 'Gebäck', 'tax_rate' => 0.07, 'track_stock' => false],
                ['name' => 'Mehl Type 405', 'sku' => 'BAE-ZUT-001', 'description' => 'Weizenmehl Type 405 1kg', 'unit' => 'kg', 'price' => 1.20, 'cost_price' => 0.60, 'category' => 'Zutaten', 'tax_rate' => 0.07, 'track_stock' => true, 'stock_quantity' => 200, 'min_stock_level' => 50],
                ['name' => 'Zucker', 'sku' => 'BAE-ZUT-002', 'description' => 'Haushaltszucker 1kg', 'unit' => 'kg', 'price' => 1.50, 'cost_price' => 0.80, 'category' => 'Zutaten', 'tax_rate' => 0.07, 'track_stock' => true, 'stock_quantity' => 150, 'min_stock_level' => 30],
                ['name' => 'Catering Service', 'sku' => 'BAE-CAT-001', 'description' => 'Catering für Events', 'unit' => 'Std.', 'price' => 85.00, 'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
            ],
            'expense_categories' => [
                'Zutaten & Rohstoffe',
                'Verpackungsmaterial',
                'Energiekosten',
                'Miete & Nebenkosten',
                'Versicherungen',
                'Marketing & Werbung',
                'Schulungen',
                'Lizenzen & Gebühren',
                'Büromaterial',
                'Telefon & Internet',
                'Fahrzeugkosten',
                'Sonstige Ausgaben',
            ],
        ];
    }

    private function getRestaurantData(): array
    {
        return [
            'product_categories' => [
                ['name' => 'Hauptgerichte', 'description' => 'Fleisch, Fisch, Vegetarisch', 'color' => '#8B0000', 'sort_order' => 1],
                ['name' => 'Vorspeisen', 'description' => 'Suppen, Salate, Antipasti', 'color' => '#FF6347', 'sort_order' => 2],
                ['name' => 'Desserts', 'description' => 'Nachspeisen und Süßes', 'color' => '#FF69B4', 'sort_order' => 3],
                ['name' => 'Getränke', 'description' => 'Alkoholisch und alkoholfrei', 'color' => '#00CED1', 'sort_order' => 4],
                ['name' => 'Zutaten', 'description' => 'Küchenzutaten', 'color' => '#F0E68C', 'sort_order' => 5],
            ],
            'products' => [
                ['name' => 'Schnitzel Wiener Art', 'sku' => 'RES-HAU-001', 'description' => 'Schnitzel mit Pommes', 'unit' => 'Portion', 'price' => 18.90, 'cost_price' => 7.50, 'category' => 'Hauptgerichte', 'tax_rate' => 0.07, 'track_stock' => false],
                ['name' => 'Lachsfilet', 'sku' => 'RES-HAU-002', 'description' => 'Lachsfilet mit Gemüse', 'unit' => 'Portion', 'price' => 24.90, 'cost_price' => 10.00, 'category' => 'Hauptgerichte', 'tax_rate' => 0.07, 'track_stock' => false],
                ['name' => 'Vegetarisches Gericht', 'sku' => 'RES-HAU-003', 'description' => 'Gemüsepfanne', 'unit' => 'Portion', 'price' => 16.90, 'cost_price' => 6.00, 'category' => 'Hauptgerichte', 'tax_rate' => 0.07, 'track_stock' => false],
                ['name' => 'Caesar Salad', 'sku' => 'RES-VOR-001', 'description' => 'Caesar Salad', 'unit' => 'Portion', 'price' => 12.90, 'cost_price' => 4.50, 'category' => 'Vorspeisen', 'tax_rate' => 0.07, 'track_stock' => false],
                ['name' => 'Tomaten-Mozzarella', 'sku' => 'RES-VOR-002', 'description' => 'Caprese Salat', 'unit' => 'Portion', 'price' => 10.90, 'cost_price' => 4.00, 'category' => 'Vorspeisen', 'tax_rate' => 0.07, 'track_stock' => false],
                ['name' => 'Tiramisu', 'sku' => 'RES-DES-001', 'description' => 'Hausgemachtes Tiramisu', 'unit' => 'Portion', 'price' => 8.90, 'cost_price' => 3.00, 'category' => 'Desserts', 'tax_rate' => 0.07, 'track_stock' => false],
                ['name' => 'Schokoladenkuchen', 'sku' => 'RES-DES-002', 'description' => 'Schokoladenkuchen', 'unit' => 'Portion', 'price' => 7.90, 'cost_price' => 2.50, 'category' => 'Desserts', 'tax_rate' => 0.07, 'track_stock' => false],
                ['name' => 'Wein Rot 0.75L', 'sku' => 'RES-GET-001', 'description' => 'Rotwein Flasche', 'unit' => 'Flasche', 'price' => 24.90, 'cost_price' => 10.00, 'category' => 'Getränke', 'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 50, 'min_stock_level' => 10],
                ['name' => 'Cola 0.5L', 'sku' => 'RES-GET-002', 'description' => 'Cola Flasche', 'unit' => 'Flasche', 'price' => 3.90, 'cost_price' => 1.20, 'category' => 'Getränke', 'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 100, 'min_stock_level' => 20],
                ['name' => 'Olivenöl', 'sku' => 'RES-ZUT-001', 'description' => 'Olivenöl extra vergine 1L', 'unit' => 'Liter', 'price' => 12.90, 'cost_price' => 6.00, 'category' => 'Zutaten', 'tax_rate' => 0.07, 'track_stock' => true, 'stock_quantity' => 30, 'min_stock_level' => 5],
            ],
            'expense_categories' => [
                'Lebensmittel',
                'Getränke',
                'Küchenausstattung',
                'Miete & Nebenkosten',
                'Energiekosten',
                'Versicherungen',
                'Marketing & Werbung',
                'Schulungen',
                'Lizenzen & Gebühren',
                'Büromaterial',
                'Telefon & Internet',
                'Personal',
                'Sonstige Ausgaben',
            ],
        ];
    }

    private function getGenericData(): array
    {
        return [
            'product_categories' => [
                ['name' => 'Produkte', 'description' => 'Allgemeine Produkte', 'color' => '#4169E1', 'sort_order' => 1],
                ['name' => 'Dienstleistungen', 'description' => 'Beratung und Service', 'color' => '#32CD32', 'sort_order' => 2],
            ],
            'products' => [
                ['name' => 'Produkt 1', 'sku' => 'GEN-PRO-001', 'description' => 'Beispielprodukt 1', 'unit' => 'Stk.', 'price' => 29.90, 'cost_price' => 15.00, 'category' => 'Produkte', 'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 50, 'min_stock_level' => 10],
                ['name' => 'Produkt 2', 'sku' => 'GEN-PRO-002', 'description' => 'Beispielprodukt 2', 'unit' => 'Stk.', 'price' => 49.90, 'cost_price' => 25.00, 'category' => 'Produkte', 'tax_rate' => 0.19, 'track_stock' => true, 'stock_quantity' => 30, 'min_stock_level' => 5],
                ['name' => 'Beratung', 'sku' => 'GEN-BER-001', 'description' => 'Beratungsdienstleistung', 'unit' => 'Std.', 'price' => 75.00, 'category' => 'Dienstleistungen', 'tax_rate' => 0.19, 'is_service' => true],
            ],
            'expense_categories' => [
                'Materialkosten',
                'Fahrzeugkosten',
                'Miete & Nebenkosten',
                'Versicherungen',
                'Marketing & Werbung',
                'Schulungen',
                'Lizenzen & Gebühren',
                'Büromaterial',
                'Telefon & Internet',
                'Sonstige Ausgaben',
            ],
        ];
    }
}

