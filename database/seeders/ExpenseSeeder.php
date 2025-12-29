<?php

namespace Database\Seeders;

use App\Modules\Company\Models\Company;
use App\Modules\Expense\Models\Expense;
use App\Modules\Expense\Models\ExpenseCategory;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::with('users')->get();

        $expenseTemplates = [
            [
                'title' => 'Büromaterial Einkauf',
                'description' => 'Papier, Stifte, Ordner und andere Büroartikel',
                'amount_range' => [50, 200],
                'vat_rate' => 0.19,
                'payment_methods' => ['Überweisung', 'Bar', 'Kreditkarte'],
            ],
            [
                'title' => 'Software-Lizenz',
                'description' => 'Jährliche Lizenz für Projektmanagement-Software',
                'amount_range' => [300, 1200],
                'vat_rate' => 0.19,
                'payment_methods' => ['Überweisung', 'SEPA-Lastschrift'],
            ],
            [
                'title' => 'Benzin Tanken',
                'description' => 'Tankfüllung für Geschäftsfahrzeug',
                'amount_range' => [60, 120],
                'vat_rate' => 0.19,
                'payment_methods' => ['Kreditkarte', 'Bar'],
            ],
            [
                'title' => 'Kaffee & Getränke',
                'description' => 'Kaffee, Tee und Getränke für Büro',
                'amount_range' => [30, 80],
                'vat_rate' => 0.19,
                'payment_methods' => ['Bar', 'Kreditkarte'],
            ],
            [
                'title' => 'Marketing-Kampagne',
                'description' => 'Online-Werbung und Social Media Marketing',
                'amount_range' => [500, 2000],
                'vat_rate' => 0.19,
                'payment_methods' => ['Überweisung', 'SEPA-Lastschrift'],
            ],
            [
                'title' => 'Steuerberatung',
                'description' => 'Quartalsweise Steuerberatung',
                'amount_range' => [400, 800],
                'vat_rate' => 0.19,
                'payment_methods' => ['Überweisung'],
            ],
            [
                'title' => 'Hardware-Kauf',
                'description' => 'Neuer Laptop/Computer für Mitarbeiter',
                'amount_range' => [800, 2500],
                'vat_rate' => 0.19,
                'payment_methods' => ['Überweisung', 'Kreditkarte'],
            ],
            [
                'title' => 'Fortbildungskurs',
                'description' => 'Online-Kurs oder Workshop-Teilnahme',
                'amount_range' => [200, 1500],
                'vat_rate' => 0.19,
                'payment_methods' => ['Überweisung', 'Kreditkarte'],
            ],
            [
                'title' => 'Internet & Telefon',
                'description' => 'Monatliche Rechnung für Internet und Telefon',
                'amount_range' => [40, 120],
                'vat_rate' => 0.19,
                'payment_methods' => ['SEPA-Lastschrift', 'Überweisung'],
            ],
            [
                'title' => 'Geschäftsessen',
                'description' => 'Geschäftsessen mit Kunden oder Partnern',
                'amount_range' => [80, 300],
                'vat_rate' => 0.19,
                'payment_methods' => ['Kreditkarte', 'Bar'],
            ],
        ];

        foreach ($companies as $company) {
            $users = $company->users;
            $categories = \App\Modules\Expense\Models\ExpenseCategory::where('company_id', $company->id)->get();

            if ($users->isEmpty() || $categories->isEmpty()) {
                continue;
            }

            // Create expenses
            for ($i = 1; $i <= 25; $i++) {
                $user = $users->random();
                $category = $categories->random();
                $template = collect($expenseTemplates)->random();
                
                $expenseDate = Carbon::now()->subDays(rand(1, 90));
                $amount = rand($template['amount_range'][0] * 100, $template['amount_range'][1] * 100) / 100;
                $vatRate = $template['vat_rate'];
                $vatAmount = round($amount * $vatRate, 2);
                $netAmount = round($amount - $vatAmount, 2);

                Expense::create([
                    'company_id' => $company->id,
                    'user_id' => $user->id,
                    'category_id' => $category->id,
                    'title' => $template['title'],
                    'description' => $template['description'],
                    'amount' => $amount,
                    'vat_rate' => $vatRate,
                    'vat_amount' => $vatAmount,
                    'net_amount' => $netAmount,
                    'expense_date' => $expenseDate,
                    'payment_method' => collect($template['payment_methods'])->random(),
                    'reference' => rand(0, 1) ? 'REF-' . strtoupper(uniqid()) : null,
                ]);
            }
        }
    }
}
