<?php

namespace Database\Seeders;

use App\Modules\Company\Models\Company;
use App\Modules\Expense\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        $categoryNames = [
            'Büromaterial',
            'Reisekosten',
            'Software & Lizenzen',
            'Marketing & Werbung',
            'Miete & Nebenkosten',
            'Fahrzeugkosten',
            'Beratung & Dienstleistungen',
            'Hardware & IT',
            'Telefon & Internet',
            'Versicherungen',
            'Steuerberatung',
            'Fortbildung',
        ];

        foreach ($companies as $company) {
            foreach ($categoryNames as $categoryName) {
                ExpenseCategory::firstOrCreate([
                    'company_id' => $company->id,
                    'name' => $categoryName,
                ]);
            }
        }
    }
}
