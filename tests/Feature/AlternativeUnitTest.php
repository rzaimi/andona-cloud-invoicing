<?php

namespace Tests\Feature;

use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Models\InvoiceItem;
use App\Modules\Offer\Models\OfferItem;
use App\Modules\User\Models\User;
use App\Services\ERechnungService;
use Tests\TestCase;

class AlternativeUnitTest extends TestCase
{
    public function test_an_ep_line_is_stored_with_a_zero_total_and_left_out_of_the_sum(): void
    {
        $this->seedRolesAndPermissions();

        $company = Company::create([
            'name' => 'Alternativ GmbH',
            'email' => 'ep@example.com',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'user',
        ]);
        $user->assignRole('user');

        $customer = Customer::create([
            'company_id' => $company->id,
            'name' => 'Kunde EP',
            'email' => 'kunde-ep@example.com',
            'status' => 'active',
            'customer_type' => 'business',
        ]);

        $this->actingAs($user)->post('/invoices', [
            'customer_id' => $customer->id,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(14)->format('Y-m-d'),
            'notes' => null,
            'layout_id' => null,
            'vat_regime' => 'standard',
            'items' => [
                [
                    'description' => 'Hauptleistung',
                    'quantity' => 2,
                    'unit_price' => 50,
                    'unit' => 'Stk.',
                    'tax_rate' => 0.19,
                ],
                [
                    'description' => 'Alternativposition',
                    'quantity' => 4,
                    'unit_price' => 125,
                    'unit' => 'EP',
                    'tax_rate' => 0.19,
                    'discount_type' => 'percentage',
                    'discount_value' => 10,
                ],
            ],
        ])->assertRedirect();

        $invoice = Invoice::query()->where('company_id', $company->id)->firstOrFail();
        $invoice->load('items');

        $alternative = $invoice->items->firstWhere('description', 'Alternativposition');
        $this->assertNotNull($alternative);
        $this->assertSame('EP', $alternative->unit);
        $this->assertEqualsWithDelta(125.0, (float) $alternative->unit_price, 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $alternative->total, 0.01);
        $this->assertNull($alternative->discount_type);
        $this->assertEqualsWithDelta(100.0, (float) $invoice->subtotal, 0.01);
        $this->assertEqualsWithDelta(19.0, (float) $invoice->tax_amount, 0.01);
        $this->assertEqualsWithDelta(119.0, (float) $invoice->total, 0.01);

        $offerItem = new OfferItem([
            'description' => 'Angebotsalternative',
            'quantity' => 3,
            'unit_price' => 80,
            'unit' => ' ep ',
            'tax_rate' => 0.19,
            'discount_type' => 'fixed',
            'discount_value' => 5,
        ]);
        $offerItem->calculateTotal();

        $this->assertEqualsWithDelta(0.0, (float) $offerItem->total, 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $offerItem->discount_amount, 0.01);

        $xml = app(ERechnungService::class)->generateXRechnung($invoice->fresh(['items', 'company', 'customer']));

        $this->assertStringContainsString('Hauptleistung', $xml);
        $this->assertStringNotContainsString('Alternativposition', $xml);
        $this->assertStringNotContainsString('>500.00<', $xml);
        $this->assertStringContainsString('>100.00<', $xml);
    }

    public function test_a_normal_line_is_unchanged(): void
    {
        $item = new InvoiceItem([
            'description' => 'Normal',
            'quantity' => 2,
            'unit_price' => 10,
            'unit' => 'Stk.',
            'tax_rate' => 0.19,
        ]);
        $item->calculateTotal();

        $this->assertEqualsWithDelta(20.0, (float) $item->total, 0.01);
    }
}
