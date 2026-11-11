<?php

namespace Tests\Feature;

use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Offer\Models\Offer;
use App\Modules\Offer\Models\OfferItem;
use App\Modules\User\Models\User;
use Tests\TestCase;

class OfferDuplicateTest extends TestCase
{
    public function test_an_offer_can_be_copied_as_a_new_draft(): void
    {
        $this->seedRolesAndPermissions();

        $company = Company::create([
            'name' => 'Kopie GmbH',
            'email' => 'kopie@example.com',
            'status' => 'active',
        ]);
        $company->setSetting('offer_number_format', 'AN-{YYYY}-{####}', 'string');
        $company->setSetting('offer_next_counter', 10, 'integer');

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'user',
        ]);
        $user->assignRole('user');

        $customer = Customer::create([
            'company_id' => $company->id,
            'name' => 'Schmidt Consulting AG',
            'email' => 'kontakt@schmidt-consulting.de',
            'status' => 'active',
            'customer_type' => 'business',
        ]);

        $offer = Offer::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'number' => 'AN-2026-0009',
            'status' => 'accepted',
            'issue_date' => '2026-09-01',
            'valid_until' => '2026-09-15',
            'notes' => 'Bitte die Ausführungsdetails beachten.',
            'bauvorhaben' => 'München',
            'terms_conditions' => 'Zahlung nach Abnahme.',
            'vat_regime' => 'standard',
            'tax_rate' => 0.19,
            'subtotal' => 100,
            'tax_amount' => 19,
            'total' => 119,
            'converted_to_invoice_id' => null,
        ]);

        OfferItem::create([
            'offer_id' => $offer->id,
            'description' => 'Beratung',
            'quantity' => 2,
            'unit_price' => 50,
            'unit' => 'Std.',
            'tax_rate' => 0.19,
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'total' => 90,
            'sort_order' => 0,
        ]);

        $this->actingAs($user)
            ->post(route('offers.duplicate', $offer))
            ->assertRedirect();

        $copy = Offer::where('company_id', $company->id)
            ->where('id', '!=', $offer->id)
            ->first();

        $this->assertNotNull($copy);
        $this->assertSame('draft', $copy->status);
        $this->assertSame('AN-'.now()->format('Y').'-0010', $copy->number);
        $this->assertSame($customer->id, $copy->customer_id);
        $this->assertSame('Bitte die Ausführungsdetails beachten.', $copy->notes);
        $this->assertSame('München', $copy->bauvorhaben);
        $this->assertSame('Zahlung nach Abnahme.', $copy->terms_conditions);
        $this->assertNull($copy->converted_to_invoice_id);
        $this->assertSame(now()->toDateString(), $copy->issue_date->toDateString());
        $this->assertSame(now()->addDays(14)->toDateString(), $copy->valid_until->toDateString());
        $this->assertSame('accepted', $offer->fresh()->status);

        $item = $copy->items()->first();
        $this->assertSame('Beratung', $item->description);
        $this->assertEquals(2, $item->quantity);
        $this->assertEquals(50, $item->unit_price);
        $this->assertSame('percentage', $item->discount_type);
        $this->assertNotSame($offer->items()->first()->id, $item->id);
    }
}
