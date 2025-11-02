<?php

namespace Database\Seeders;

use App\Modules\Company\Models\Company;
use App\Modules\Product\Models\Category;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Warehouse;
use App\Modules\Product\Models\WarehouseStock;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        $productTemplates = [
            // Physical products with stock tracking
            [
                'name' => 'Office Stuhl Premium',
                'description' => 'Ergonomischer Bürostuhl mit Lendenwirbelstütze und verstellbaren Armlehnen',
                'unit' => 'Stk.',
                'price' => 299.99,
                'cost_price' => 180.00,
                'sku' => 'STUHL-001',
                'tax_rate' => 0.19,
                'stock_quantity' => 45,
                'min_stock_level' => 10,
                'track_stock' => true,
                'is_service' => false,
                'status' => 'active',
            ],
            [
                'name' => 'Schreibtisch Executive',
                'description' => 'Massiver Schreibtisch aus Eiche mit zwei Schubladen und Kabelmanagement',
                'unit' => 'Stk.',
                'price' => 599.00,
                'cost_price' => 350.00,
                'sku' => 'DESK-002',
                'tax_rate' => 0.19,
                'stock_quantity' => 12,
                'min_stock_level' => 5,
                'track_stock' => true,
                'is_service' => false,
                'status' => 'active',
            ],
            [
                'name' => 'Druckerpapier A4 (500 Blatt)',
                'description' => 'Hochwertiges Büropapier, 80g/m², weiß, in Packungen zu 500 Blatt',
                'unit' => 'Packung',
                'price' => 6.99,
                'cost_price' => 3.50,
                'sku' => 'PAPER-A4-500',
                'tax_rate' => 0.19,
                'stock_quantity' => 120,
                'min_stock_level' => 30,
                'track_stock' => true,
                'is_service' => false,
                'status' => 'active',
            ],
            [
                'name' => 'Tastatur Mechanisch',
                'description' => 'Mechanische Tastatur mit RGB-Beleuchtung und Cherry MX Switches',
                'unit' => 'Stk.',
                'price' => 89.99,
                'cost_price' => 55.00,
                'sku' => 'KEYB-MECH-001',
                'tax_rate' => 0.19,
                'stock_quantity' => 28,
                'min_stock_level' => 8,
                'track_stock' => true,
                'is_service' => false,
                'status' => 'active',
            ],
            [
                'name' => 'Monitor 27 Zoll 4K',
                'description' => 'Ultra HD Monitor mit IPS Panel, 60Hz, USB-C Anschluss',
                'unit' => 'Stk.',
                'price' => 349.00,
                'cost_price' => 220.00,
                'sku' => 'MON-27-4K',
                'tax_rate' => 0.19,
                'stock_quantity' => 15,
                'min_stock_level' => 5,
                'track_stock' => true,
                'is_service' => false,
                'status' => 'active',
            ],
            [
                'name' => 'Kabeltrommel 10m',
                'description' => 'Verlängerungskabel mit 5 Steckdosen, 10 Meter Länge, schwarz',
                'unit' => 'Stk.',
                'price' => 24.99,
                'cost_price' => 12.00,
                'sku' => 'CABLE-EXT-10',
                'tax_rate' => 0.19,
                'stock_quantity' => 65,
                'min_stock_level' => 20,
                'track_stock' => true,
                'is_service' => false,
                'status' => 'active',
            ],
            [
                'name' => 'Whiteboard 120x90cm',
                'description' => 'Magnetisches Whiteboard mit Aluminiumrahmen und Marker-Set',
                'unit' => 'Stk.',
                'price' => 79.99,
                'cost_price' => 45.00,
                'sku' => 'WHT-BRD-120',
                'tax_rate' => 0.19,
                'stock_quantity' => 8,
                'min_stock_level' => 3,
                'track_stock' => true,
                'is_service' => false,
                'status' => 'active',
            ],
            [
                'name' => 'Lampe Schreibtisch LED',
                'description' => 'Moderne LED-Tischlampe mit Dimmer und USB-Ladeport',
                'unit' => 'Stk.',
                'price' => 49.99,
                'cost_price' => 28.00,
                'sku' => 'LAMP-LED-DESK',
                'tax_rate' => 0.19,
                'stock_quantity' => 35,
                'min_stock_level' => 10,
                'track_stock' => true,
                'is_service' => false,
                'status' => 'active',
            ],
            // Low stock items for testing
            [
                'name' => 'Maus Wireless',
                'description' => 'Ergonomische Funkmaus mit langer Batterielaufzeit',
                'unit' => 'Stk.',
                'price' => 29.99,
                'cost_price' => 15.00,
                'sku' => 'MOUSE-WIRELESS',
                'tax_rate' => 0.19,
                'stock_quantity' => 5,
                'min_stock_level' => 10,
                'track_stock' => true,
                'is_service' => false,
                'status' => 'active',
            ],
            [
                'name' => 'Notizblock A4',
                'description' => 'Collegeblock mit 80 Blättern, liniert, weiß',
                'unit' => 'Stk.',
                'price' => 2.99,
                'cost_price' => 1.20,
                'sku' => 'NOTEPAD-A4',
                'tax_rate' => 0.19,
                'stock_quantity' => 2,
                'min_stock_level' => 15,
                'track_stock' => true,
                'is_service' => false,
                'status' => 'active',
            ],
            // Service products (no stock)
            [
                'name' => 'IT-Beratung (Stunde)',
                'description' => 'Professionelle IT-Beratung vor Ort, pro Stunde',
                'unit' => 'Std.',
                'price' => 95.00,
                'cost_price' => null,
                'sku' => 'SERV-IT-CONS',
                'tax_rate' => 0.19,
                'stock_quantity' => 0,
                'min_stock_level' => 0,
                'track_stock' => false,
                'is_service' => true,
                'status' => 'active',
            ],
            [
                'name' => 'Wartungsvertrag monatlich',
                'description' => 'Monatliche Wartung und Support für Hard- und Software',
                'unit' => 'Monat',
                'price' => 199.00,
                'cost_price' => null,
                'sku' => 'SERV-MAINT-MON',
                'tax_rate' => 0.19,
                'stock_quantity' => 0,
                'min_stock_level' => 0,
                'track_stock' => false,
                'is_service' => true,
                'status' => 'active',
            ],
            [
                'name' => 'Software-Installation',
                'description' => 'Installation und Konfiguration von Softwarelösungen',
                'unit' => 'Installation',
                'price' => 149.00,
                'cost_price' => null,
                'sku' => 'SERV-INSTALL',
                'tax_rate' => 0.19,
                'stock_quantity' => 0,
                'min_stock_level' => 0,
                'track_stock' => false,
                'is_service' => true,
                'status' => 'active',
            ],
        ];

        foreach ($companies as $company) {
            // Get categories for this company
            $hardwareCategory = Category::where('company_id', $company->id)
                ->where('name', 'Hardware')
                ->first();
            $officeCategory = Category::where('company_id', $company->id)
                ->where('name', 'Bürobedarf')
                ->first();
            $servicesCategory = Category::where('company_id', $company->id)
                ->where('name', 'Dienstleistungen')
                ->first();
            
            // Category mapping by product name
            $categoryMapping = [
                'Office Stuhl Premium' => $officeCategory?->id,
                'Schreibtisch Executive' => $officeCategory?->id,
                'Druckerpapier A4 (500 Blatt)' => $officeCategory?->id,
                'Tastatur Mechanisch' => $hardwareCategory?->id,
                'Monitor 27 Zoll 4K' => $hardwareCategory?->id,
                'Kabeltrommel 10m' => $officeCategory?->id,
                'Whiteboard 120x90cm' => $officeCategory?->id,
                'Lampe Schreibtisch LED' => $officeCategory?->id,
                'Maus Wireless' => $hardwareCategory?->id,
                'Notizblock A4' => $officeCategory?->id,
                'Laminiergerät A3' => $officeCategory?->id,
                'Aktenvernichter Cross-Cut' => $officeCategory?->id,
                'IT-Beratung (Stunde)' => $servicesCategory?->id,
                'Wartungsvertrag monatlich' => $servicesCategory?->id,
                'Software-Installation' => $servicesCategory?->id,
            ];
            
            // Get or create default warehouse for this company
            $warehouse = Warehouse::where('company_id', $company->id)
                ->where('is_default', true)
                ->first();

            if (!$warehouse) {
                // Create a default warehouse if it doesn't exist
                // Generate a unique code based on company name
                $companyCode = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $company->name), 0, 5));
                $warehouseCode = $companyCode . '-WH-001';
                
                // Ensure code is unique by checking and incrementing if needed
                $counter = 1;
                while (Warehouse::where('code', $warehouseCode)->exists()) {
                    $counter++;
                    $warehouseCode = $companyCode . '-WH-' . str_pad($counter, 3, '0', STR_PAD_LEFT);
                }
                
                // Parse company address into street and street_number if available
                $street = null;
                $streetNumber = null;
                if ($company->address) {
                    // Try to parse address like "Alexanderplatz 1" into street and number
                    if (preg_match('/^(.+?)\s+(\d+[a-z]?)$/i', $company->address, $matches)) {
                        $street = $matches[1];
                        $streetNumber = $matches[2];
                    } else {
                        $street = $company->address;
                    }
                }
                
                $warehouse = Warehouse::create([
                    'company_id' => $company->id,
                    'code' => $warehouseCode,
                    'name' => 'Hauptlager',
                    'description' => 'Standard Lager für ' . $company->name,
                    'street' => $street,
                    'street_number' => $streetNumber,
                    'postal_code' => $company->postal_code ?? null,
                    'city' => $company->city ?? null,
                    'country' => 'DE',
                    'is_default' => true,
                    'is_active' => true,
                ]);
            }

            // Create products for this company
            foreach ($productTemplates as $index => $productData) {
                // Generate unique product number per company
                $companyPrefix = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $company->name), 0, 3));
                $productNumber = $companyPrefix . '-PR-' . now()->year . '-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT);
                
                // Get category_id for this product
                $categoryId = $categoryMapping[$productData['name']] ?? null;
                
                $product = Product::create([
                    'company_id' => $company->id,
                    'status' => $productData['status'],
                    'number' => $productNumber,
                    'category_id' => $categoryId,
                    ...$productData,
                ]);

                // Create warehouse stock for physical products
                if ($product->track_stock && !$product->is_service && $product->stock_quantity > 0) {
                    WarehouseStock::create([
                        'company_id' => $company->id,
                        'warehouse_id' => $warehouse->id,
                        'product_id' => $product->id,
                        'quantity' => $product->stock_quantity,
                        'reserved_quantity' => 0,
                        'average_cost' => $product->cost_price ?? 0,
                    ]);
                }
            }
        }
    }
}

