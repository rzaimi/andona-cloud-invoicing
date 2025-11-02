<?php

namespace Database\Seeders;

use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        $customerTemplates = [
            [
                'name' => 'Müller & Partner GmbH',
                'email' => 'info@mueller-partner.de',
                'phone' => '+49 30 98765432',
                'address' => 'Unter den Linden 10',
                'postal_code' => '10117',
                'city' => 'Berlin',
                'country' => 'Deutschland',
                'tax_number' => '12/345/67890',
                'vat_number' => 'DE123456789',
                'contact_person' => 'Herr Müller',
                'customer_type' => 'business',
            ],
            [
                'name' => 'Schmidt Consulting AG',
                'email' => 'kontakt@schmidt-consulting.de',
                'phone' => '+49 89 12345678',
                'address' => 'Maximilianstraße 35',
                'postal_code' => '80539',
                'city' => 'München',
                'country' => 'Deutschland',
                'tax_number' => '143/567/89012',
                'vat_number' => 'DE987654321',
                'contact_person' => 'Frau Schmidt',
                'customer_type' => 'business',
            ],
            [
                'name' => 'Weber Industries UG',
                'email' => 'info@weber-industries.de',
                'phone' => '+49 40 55667788',
                'address' => 'Hafenstraße 123',
                'postal_code' => '20359',
                'city' => 'Hamburg',
                'country' => 'Deutschland',
                'tax_number' => '17/890/12345',
                'vat_number' => 'DE456789123',
                'contact_person' => 'Herr Weber',
                'customer_type' => 'business',
            ],
            [
                'name' => 'Fischer Handelsgesellschaft mbH',
                'email' => 'service@fischer-handel.de',
                'phone' => '+49 221 99887766',
                'address' => 'Domstraße 45',
                'postal_code' => '50668',
                'city' => 'Köln',
                'country' => 'Deutschland',
                'tax_number' => '214/123/45678',
                'vat_number' => 'DE789123456',
                'contact_person' => 'Frau Fischer',
                'customer_type' => 'business',
            ],
            [
                'name' => 'Anna Becker',
                'email' => 'anna.becker@email.de',
                'phone' => '+49 711 44556677',
                'address' => 'Königstraße 28',
                'postal_code' => '70173',
                'city' => 'Stuttgart',
                'country' => 'Deutschland',
                'tax_number' => null,
                'vat_number' => null,
                'contact_person' => null,
                'customer_type' => 'private',
            ],
            [
                'name' => 'Österreich Solutions GmbH',
                'email' => 'office@oesterreich-solutions.at',
                'phone' => '+43 1 12345678',
                'address' => 'Stephansplatz 1',
                'postal_code' => '1010',
                'city' => 'Wien',
                'country' => 'Österreich',
                'tax_number' => 'ATU12345678',
                'vat_number' => 'ATU12345678',
                'contact_person' => 'Herr Österreicher',
                'customer_type' => 'business',
            ],
        ];

        foreach ($companies as $company) {
            foreach ($customerTemplates as $index => $customerData) {
                Customer::create([
                    'company_id' => $company->id,
                    'status' => 'active',
                    'number' => 'KU-' . now()->year . '-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                    ...$customerData,
                ]);
            }
        }
    }
}
