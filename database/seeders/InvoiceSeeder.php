<?php

namespace Database\Seeders;

use App\Modules\Company\Models\Company;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Models\InvoiceItem;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::with(['customers', 'users'])->get();

        $germanServices = [
            [
                'description' => 'Webentwicklung - Responsive Website',
                'unit' => 'Std.',
                'unit_price_range' => [80, 120],
                'quantity_range' => [20, 60],
            ],
            [
                'description' => 'SEO-Optimierung und Beratung',
                'unit' => 'Std.',
                'unit_price_range' => [90, 150],
                'quantity_range' => [10, 30],
            ],
            [
                'description' => 'Content Management System Setup',
                'unit' => 'Stk.',
                'unit_price_range' => [1500, 3500],
                'quantity_range' => [1, 1],
            ],
            [
                'description' => 'E-Commerce Shop Entwicklung',
                'unit' => 'Stk.',
                'unit_price_range' => [3000, 8000],
                'quantity_range' => [1, 1],
            ],
            [
                'description' => 'Wartung und Support',
                'unit' => 'Monat',
                'unit_price_range' => [200, 500],
                'quantity_range' => [1, 12],
            ],
            [
                'description' => 'Grafikdesign und Corporate Identity',
                'unit' => 'Std.',
                'unit_price_range' => [70, 100],
                'quantity_range' => [15, 40],
            ],
            [
                'description' => 'Datenbank-Design und -Optimierung',
                'unit' => 'Std.',
                'unit_price_range' => [100, 140],
                'quantity_range' => [8, 25],
            ],
            [
                'description' => 'Mobile App Entwicklung',
                'unit' => 'Stk.',
                'unit_price_range' => [5000, 15000],
                'quantity_range' => [1, 1],
            ],
        ];

        foreach ($companies as $company) {
            $customers = $company->customers;
            $users = $company->users;

            if ($customers->isEmpty() || $users->isEmpty()) {
                continue;
            }

            // Create invoices
            for ($i = 1; $i <= 15; $i++) {
                $customer = $customers->random();
                $user = $users->random();
                $issueDate = Carbon::now()->subDays(rand(1, 120));
                $dueDate = $issueDate->copy()->addDays($company->getSetting('payment_terms', 14));

                // Generate invoice number before creating
                $prefix = $company->getSetting('invoice_prefix', 'RE-');
                $year = now()->year;
                $lastNumber = Invoice::where('company_id', $company->id)
                    ->whereYear('created_at', $year)
                    ->count() + 1;
                $invoiceNumber = $prefix . $year . '-' . str_pad($lastNumber, 4, '0', STR_PAD_LEFT);

                $invoice = Invoice::create([
                    'number' => $invoiceNumber,
                    'company_id' => $company->id,
                    'customer_id' => $customer->id,
                    'user_id' => $user->id,
                    'issue_date' => $issueDate,
                    'due_date' => $dueDate,
                    'status' => collect(['draft', 'sent', 'paid', 'overdue'])->random(),
                    'tax_rate' => $customer->customer_type === 'business' ?
                        $company->getSetting('tax_rate', 0.19) :
                        $company->getSetting('reduced_tax_rate', 0.07),
                    'notes' => $company->getSetting('invoice_footer'),
                    'payment_terms' => $company->getSetting('invoice_terms'),
                    'payment_method' => collect(['Überweisung', 'SEPA-Lastschrift', 'PayPal'])->random(),
                ]);

                // Create invoice items
                $selectedServices = collect($germanServices)->random(rand(1, 4));

                foreach ($selectedServices as $index => $serviceData) {
                    $quantity = rand($serviceData['quantity_range'][0], $serviceData['quantity_range'][1]);
                    $unitPrice = rand($serviceData['unit_price_range'][0], $serviceData['unit_price_range'][1]);

                    $item = new InvoiceItem([
                        'description' => $serviceData['description'],
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'unit' => $serviceData['unit'],
                        'sort_order' => $index,
                    ]);
                    $item->calculateTotal();
                    $invoice->items()->save($item);
                }

                $invoice->calculateTotals();
                $invoice->save();
            }
        }
    }
}
