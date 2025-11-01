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
                'name' => 'TechSolutions GmbH',
                'email' => 'info@techsolutions.de',
                'phone' => '+49 30 12345678',
                'address' => 'Alexanderplatz 1',
                'tax_number' => '12/345/67890',
                'vat_number' => 'DE123456789',
                'commercial_register' => 'HRB 12345 B',
                'managing_director' => 'Max Mustermann',
                'bank_name' => 'Deutsche Bank AG',
                'bank_iban' => 'DE89370400440532013000',
                'bank_bic' => 'DEUTDEDBBER',
                'website' => 'www.techsolutions.de',
                'status' => 'active',
            ],
            [
                'name' => 'Digitale Dienste AG',
                'email' => 'kontakt@digitaledienste.de',
                'phone' => '+49 89 98765432',
                'address' => 'Marienplatz 8',
                'tax_number' => '143/567/89012',
                'vat_number' => 'DE987654321',
                'commercial_register' => 'HRB 67890 M',
                'managing_director' => 'Anna Schmidt',
                'bank_name' => 'Commerzbank AG',
                'bank_iban' => 'DE44500800000123456789',
                'bank_bic' => 'DRESDEFF500',
                'website' => 'www.digitaledienste.de',
                'status' => 'active',
            ],
            [
                'name' => 'Innovative Lösungen UG',
                'email' => 'hello@innovative-loesungen.de',
                'phone' => '+49 40 55667788',
                'address' => 'Speicherstadt 15',
                'tax_number' => '17/890/12345',
                'vat_number' => 'DE456789123',
                'commercial_register' => 'HRB 11111 HH',
                'managing_director' => 'Thomas Weber',
                'bank_name' => 'Sparkasse Hamburg',
                'bank_iban' => 'DE75200505501234567890',
                'bank_bic' => 'HASPDEHHXXX',
                'website' => 'www.innovative-loesungen.de',
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
