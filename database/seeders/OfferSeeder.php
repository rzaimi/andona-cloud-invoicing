<?php

namespace Database\Seeders;

use App\Modules\Company\Models\Company;
use App\Modules\Offer\Models\Offer;
use App\Modules\Offer\Models\OfferItem;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class OfferSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::with(['customers', 'users'])->get();

        $germanOfferServices = [
            [
                'description' => 'Komplette Website-Erstellung mit CMS',
                'unit' => 'Stk.',
                'unit_price_range' => [2500, 6000],
                'quantity_range' => [1, 1],
            ],
            [
                'description' => 'E-Commerce Lösung mit Zahlungsintegration',
                'unit' => 'Stk.',
                'unit_price_range' => [4000, 12000],
                'quantity_range' => [1, 1],
            ],
            [
                'description' => 'Mobile App für iOS und Android',
                'unit' => 'Stk.',
                'unit_price_range' => [8000, 25000],
                'quantity_range' => [1, 1],
            ],
            [
                'description' => 'SEO-Paket für 6 Monate',
                'unit' => 'Stk.',
                'unit_price_range' => [1800, 4200],
                'quantity_range' => [1, 1],
            ],
            [
                'description' => 'Corporate Design Komplettpaket',
                'unit' => 'Stk.',
                'unit_price_range' => [1200, 3500],
                'quantity_range' => [1, 1],
            ],
            [
                'description' => 'Wartungsvertrag (12 Monate)',
                'unit' => 'Jahr',
                'unit_price_range' => [1200, 3600],
                'quantity_range' => [1, 1],
            ],
            [
                'description' => 'Schulung und Einweisung',
                'unit' => 'Tag',
                'unit_price_range' => [600, 1200],
                'quantity_range' => [1, 3],
            ],
            [
                'description' => 'Datenbank-Migration und -Optimierung',
                'unit' => 'Stk.',
                'unit_price_range' => [1500, 4000],
                'quantity_range' => [1, 1],
            ],
        ];

        foreach ($companies as $company) {
            $customers = $company->customers;
            $users = $company->users;

            if ($customers->isEmpty() || $users->isEmpty()) {
                continue;
            }

            // Create offers
            for ($i = 1; $i <= 8; $i++) {
                $customer = $customers->random();
                $user = $users->random();
                $issueDate = Carbon::now()->subDays(rand(1, 60));
                $validityDays = $company->getSetting('offer_validity_days', 30);
                $validUntil = $issueDate->copy()->addDays($validityDays);

                // Generate offer number before creating
                $prefix = $company->getSetting('offer_prefix', 'AN-');
                $year = now()->year;
                $lastNumber = Offer::where('company_id', $company->id)
                    ->whereYear('created_at', $year)
                    ->count() + 1;
                $offerNumber = $prefix . $year . '-' . str_pad($lastNumber, 4, '0', STR_PAD_LEFT);

                $offer = Offer::create([
                    'number' => $offerNumber,
                    'company_id' => $company->id,
                    'customer_id' => $customer->id,
                    'user_id' => $user->id,
                    'issue_date' => $issueDate,
                    'valid_until' => $validUntil,
                    'validity_days' => $validityDays,
                    'status' => collect(['draft', 'sent', 'accepted', 'rejected'])->random(),
                    'tax_rate' => $customer->customer_type === 'business' ?
                        $company->getSetting('tax_rate', 0.19) :
                        $company->getSetting('reduced_tax_rate', 0.07),
                    'notes' => $company->getSetting('offer_footer'),
                    'terms_conditions' => $company->getSetting('offer_terms'),
                ]);

                // Create offer items
                $selectedServices = collect($germanOfferServices)->random(rand(1, 3));

                foreach ($selectedServices as $index => $serviceData) {
                    $quantity = rand($serviceData['quantity_range'][0], $serviceData['quantity_range'][1]);
                    $unitPrice = rand($serviceData['unit_price_range'][0], $serviceData['unit_price_range'][1]);

                    $item = new OfferItem([
                        'description' => $serviceData['description'],
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'unit' => $serviceData['unit'],
                        'sort_order' => $index,
                    ]);
                    $item->calculateTotal();
                    $offer->items()->save($item);
                }

                $offer->calculateTotals();
                $offer->save();
            }
        }
    }
}
