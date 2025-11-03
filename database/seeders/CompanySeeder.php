<?php

namespace Database\Seeders;

use App\Modules\Company\Models\Company;
use App\Modules\Invoice\Models\InvoiceLayout;
use App\Modules\Offer\Models\OfferLayout;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            [
                'name' => 'Andona GmbH',
                'email' => 'info@andona.de',
                'phone' => '+49 (0) 6051 – 53 83 658',
                'address' => 'Bahnhofstraße 16',
                'postal_code' => '63571',
                'city' => 'Gelnhausen',
                'country' => 'Deutschland',
                'tax_number' => '019 228 35202',
                'vat_number' => 'DE369264419',
                'commercial_register' => 'HRB 100017',
                'managing_director' => 'Lirim Ziberi',
                'website' => 'https://andona.de',
                'status' => 'active',
                // Bank settings (normalized to company_settings)
                '_bank_settings' => [
                    'bank_name' => 'Raiffeisenbank eG, Rodenbach',
                    'bank_iban' => 'DE88506636990001121200',
                    'bank_bic' => 'GENODEF1RDB',
                ],
            ],
        ];

        foreach ($companies as $companyData) {
            // Extract bank settings (normalized to company_settings)
            $bankSettings = $companyData['_bank_settings'] ?? [];
            unset($companyData['_bank_settings']);
            
            // Mark first company as default (the system/merchant company)
            if (!Company::where('is_default', true)->exists()) {
                $companyData['is_default'] = true;
            }
            $company = Company::create($companyData);
            
            // Set bank settings after company is created (normalized to company_settings)
            if (!empty($bankSettings)) {
                $company->setBankSettings($bankSettings);
            }

            // Create default invoice layouts
            $invoiceLayouts = [
                [
                    'name' => 'Standard Rechnung',
                    'template' => 'modern',
                    'is_default' => true,
                ],
                [
                    'name' => 'Klassische Rechnung',
                    'template' => 'classic',
                    'is_default' => false,
                ],
                [
                    'name' => 'Minimale Rechnung',
                    'template' => 'minimal',
                    'is_default' => false,
                ],
            ];

            foreach ($invoiceLayouts as $layoutData) {
                InvoiceLayout::create([
                    'company_id' => $company->id,
                    ...$layoutData,
                ]);
            }

            // Create default offer layouts
            $offerLayouts = [
                [
                    'name' => 'Standard Angebot',
                    'template' => 'modern',
                    'is_default' => true,
                ],
                [
                    'name' => 'Professionelles Angebot',
                    'template' => 'professional',
                    'is_default' => false,
                ],
                [
                    'name' => 'Minimales Angebot',
                    'template' => 'minimal',
                    'is_default' => false,
                ],
            ];

            foreach ($offerLayouts as $layoutData) {
                OfferLayout::create([
                    'company_id' => $company->id,
                    ...$layoutData,
                ]);
            }
        }
    }
}
