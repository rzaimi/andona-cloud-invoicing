<?php

namespace Tests\Feature;

use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Offer\Models\Offer;
use App\Modules\User\Models\User;
use Tests\TestCase;

class OfferNumberCounterTest extends TestCase
{
    public function test_a_new_offer_starts_at_the_configured_counter(): void
    {
        $this->seedRolesAndPermissions();

        $company = Company::create([
            'name' => 'Counter GmbH',
            'email' => 'counter@example.com',
            'status' => 'active',
        ]);
        $company->setSetting('offer_number_format', 'AN-{YYYY}-{####}', 'string');
        $company->setSetting('offer_next_counter', 766, 'integer');

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'user',
        ]);
        $user->assignRole('user');

        $customer = Customer::create([
            'company_id' => $company->id,
            'name' => 'Musterkunde',
            'email' => 'kunde@example.com',
            'status' => 'active',
            'customer_type' => 'business',
        ]);

        $year = now()->format('Y');
        Offer::create([
            'number' => "AN-{$year}-0001",
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'status' => 'draft',
            'issue_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'subtotal' => 100,
            'tax_rate' => 0.19,
            'tax_amount' => 19,
            'total' => 119,
            'vat_regime' => 'standard',
        ]);

        $this->actingAs($user)->post(route('offers.store'), [
            'customer_id' => $customer->id,
            'issue_date' => now()->toDateString(),
            'valid_until' => now()->addDays(14)->toDateString(),
            'vat_regime' => 'standard',
            'items' => [[
                'description' => 'Leistung',
                'quantity' => 1,
                'unit_price' => 100,
                'unit' => 'Stk.',
                'tax_rate' => 0.19,
            ]],
        ])->assertRedirect();

        $this->assertDatabaseHas('offers', [
            'company_id' => $company->id,
            'number' => "AN-{$year}-0766",
        ]);
    }
}
