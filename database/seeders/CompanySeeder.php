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
                'bank_name' => 'Raiffeisenbank eG, Rodenbach',
                'bank_iban' => 'DE88506636990001121200',
                'bank_bic' => 'GENODEF1RDB',
                'website' => 'https://andona.de',
                'status' => 'active',
            ],
        ];

        foreach ($companies as $companyData) {
            $company = Company::create($companyData);

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
