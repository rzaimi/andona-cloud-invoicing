<?php

namespace Database\Seeders;

use App\Modules\Company\Models\Company;
use App\Modules\Product\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            // Main Categories
            $hardware = Category::create([
                'company_id' => $company->id,
                'name' => 'Hardware',
                'description' => 'Computer-Hardware und Peripheriegeräte',
                'color' => '#3b82f6',
                'icon' => 'laptop',
                'parent_id' => null,
                'sort_order' => 1,
                'is_active' => true,
            ]);

            $software = Category::create([
                'company_id' => $company->id,
                'name' => 'Software',
                'description' => 'Softwarelizenzen und digitale Produkte',
                'color' => '#8b5cf6',
                'icon' => 'code',
                'parent_id' => null,
                'sort_order' => 2,
                'is_active' => true,
            ]);

            $services = Category::create([
                'company_id' => $company->id,
                'name' => 'Dienstleistungen',
                'description' => 'Beratung, Support und andere Services',
                'color' => '#10b981',
                'icon' => 'headset',
                'parent_id' => null,
                'sort_order' => 3,
                'is_active' => true,
            ]);

            $office = Category::create([
                'company_id' => $company->id,
                'name' => 'Bürobedarf',
                'description' => 'Büromaterial und Verbrauchsmaterialien',
                'color' => '#f59e0b',
                'icon' => 'briefcase',
                'parent_id' => null,
                'sort_order' => 4,
                'is_active' => true,
            ]);

            // Hardware Subcategories
            Category::create([
                'company_id' => $company->id,
                'name' => 'Computer',
                'description' => 'Desktop-PCs und Workstations',
                'color' => '#3b82f6',
                'icon' => 'desktop',
                'parent_id' => $hardware->id,
                'sort_order' => 1,
                'is_active' => true,
            ]);

            Category::create([
                'company_id' => $company->id,
                'name' => 'Laptops',
                'description' => 'Notebooks und mobile Geräte',
                'color' => '#3b82f6',
                'icon' => 'laptop',
                'parent_id' => $hardware->id,
                'sort_order' => 2,
                'is_active' => true,
            ]);

            Category::create([
                'company_id' => $company->id,
                'name' => 'Monitore',
                'description' => 'Bildschirme und Displays',
                'color' => '#3b82f6',
                'icon' => 'monitor',
                'parent_id' => $hardware->id,
                'sort_order' => 3,
                'is_active' => true,
            ]);

            Category::create([
                'company_id' => $company->id,
                'name' => 'Peripheriegeräte',
                'description' => 'Tastaturen, Mäuse, Drucker, etc.',
                'color' => '#3b82f6',
                'icon' => 'keyboard',
                'parent_id' => $hardware->id,
                'sort_order' => 4,
                'is_active' => true,
            ]);

            // Software Subcategories
            Category::create([
                'company_id' => $company->id,
                'name' => 'Betriebssysteme',
                'description' => 'Windows, Linux, macOS Lizenzen',
                'color' => '#8b5cf6',
                'icon' => 'settings',
                'parent_id' => $software->id,
                'sort_order' => 1,
                'is_active' => true,
            ]);

            Category::create([
                'company_id' => $company->id,
                'name' => 'Office-Software',
                'description' => 'Microsoft Office, LibreOffice, etc.',
                'color' => '#8b5cf6',
                'icon' => 'file-text',
                'parent_id' => $software->id,
                'sort_order' => 2,
                'is_active' => true,
            ]);

            Category::create([
                'company_id' => $company->id,
                'name' => 'Sicherheitssoftware',
                'description' => 'Antivirus, Firewall, Backup-Software',
                'color' => '#8b5cf6',
                'icon' => 'shield',
                'parent_id' => $software->id,
                'sort_order' => 3,
                'is_active' => true,
            ]);

            // Services Subcategories
            Category::create([
                'company_id' => $company->id,
                'name' => 'IT-Beratung',
                'description' => 'Strategieberatung und Konzeption',
                'color' => '#10b981',
                'icon' => 'lightbulb',
                'parent_id' => $services->id,
                'sort_order' => 1,
                'is_active' => true,
            ]);

            Category::create([
                'company_id' => $company->id,
                'name' => 'Support & Wartung',
                'description' => 'Technischer Support und Systemwartung',
                'color' => '#10b981',
                'icon' => 'tool',
                'parent_id' => $services->id,
                'sort_order' => 2,
                'is_active' => true,
            ]);

            Category::create([
                'company_id' => $company->id,
                'name' => 'Schulungen',
                'description' => 'Mitarbeiterschulungen und Workshops',
                'color' => '#10b981',
                'icon' => 'graduation-cap',
                'parent_id' => $services->id,
                'sort_order' => 3,
                'is_active' => true,
            ]);

            Category::create([
                'company_id' => $company->id,
                'name' => 'Installation',
                'description' => 'Hardware- und Softwareinstallation',
                'color' => '#10b981',
                'icon' => 'download',
                'parent_id' => $services->id,
                'sort_order' => 4,
                'is_active' => true,
            ]);

            // Office Subcategories
            Category::create([
                'company_id' => $company->id,
                'name' => 'Papier & Druckerzubehör',
                'description' => 'Druckerpapier, Toner, Tintenpatronen',
                'color' => '#f59e0b',
                'icon' => 'printer',
                'parent_id' => $office->id,
                'sort_order' => 1,
                'is_active' => true,
            ]);

            Category::create([
                'company_id' => $company->id,
                'name' => 'Schreibwaren',
                'description' => 'Stifte, Notizblöcke, etc.',
                'color' => '#f59e0b',
                'icon' => 'edit',
                'parent_id' => $office->id,
                'sort_order' => 2,
                'is_active' => true,
            ]);

            Category::create([
                'company_id' => $company->id,
                'name' => 'Möbel',
                'description' => 'Schreibtische, Stühle, Regale',
                'color' => '#f59e0b',
                'icon' => 'home',
                'parent_id' => $office->id,
                'sort_order' => 3,
                'is_active' => true,
            ]);
        }
    }
}


