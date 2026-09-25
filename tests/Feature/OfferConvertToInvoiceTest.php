<?php

namespace Tests\Feature;

use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Models\InvoiceLayout;
use App\Modules\Offer\Models\Offer;
use App\Modules\Offer\Models\OfferItem;
use App\Modules\Offer\Models\OfferLayout;
use App\Modules\User\Models\User;
use Tests\TestCase;

class OfferConvertToInvoiceTest extends TestCase
{
    protected Company $company;

    protected User $user;

    protected Customer $customer;

    protected OfferLayout $offerLayout;

    protected InvoiceLayout $invoiceLayout;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seedRolesAndPermissions();

        $this->company = Company::create([
            'name' => 'Kameri Abdichtung UG',
            'email' => 'convert@example.com',
            'status' => 'active',
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'user',
        ]);
        $this->user->assignRole('user');

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Convert Customer',
            'email' => 'convert-customer@example.com',
            'status' => 'active',
            'customer_type' => 'business',
        ]);

        $this->offerLayout = OfferLayout::create([
            'company_id' => $this->company->id,
            'name' => 'Angebot Standard',
            'is_default' => true,
        ]);

        $this->invoiceLayout = InvoiceLayout::create([
            'company_id' => $this->company->id,
            'name' => 'Rechnung Standard',
            'is_default' => true,
        ]);

        // Production stores this as a string. Carbon rejects that in addDays().
        $this->company->setSetting('payment_terms', '30', 'string');
    }

    public function test_accepted_offer_converts_without_reusing_the_offer_layout(): void
    {
        $offer = Offer::create([
            'number' => 'AN-2026-0001',
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'status' => 'accepted',
            'issue_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'subtotal' => 13350.00,
            'tax_rate' => 0.19,
            'tax_amount' => 2536.50,
            'total' => 15886.50,
            'bauvorhaben' => 'Abdichtung',
            'notes' => 'Bitte Anschlüsse prüfen',
            'layout_id' => $this->offerLayout->id,
            'vat_regime' => 'standard',
        ]);

        OfferItem::create([
            'offer_id' => $offer->id,
            'description' => 'Anschlüsse mit Bitumenvoranstrich streichen',
            'quantity' => 1,
            'unit_price' => 13350.00,
            'unit' => 'Pauschal',
            'tax_rate' => 0.19,
            'total' => 13350.00,
            'sort_order' => 0,
        ]);

        $this->actingAs($this->user)
            ->post(route('offers.convert-to-invoice', $offer))
            ->assertRedirect(route('invoices.index'));

        $invoice = Invoice::where('company_id', $this->company->id)->first();

        $this->assertNotNull($invoice);
        $this->assertSame(now()->addDays(30)->toDateString(), $invoice->due_date->toDateString());
        $this->assertSame($this->invoiceLayout->id, $invoice->layout_id);
        $this->assertNotSame($this->offerLayout->id, $invoice->layout_id);
        $this->assertSame('Abdichtung', $invoice->bauvorhaben);
        $this->assertSame('Bitte Anschlüsse prüfen', $invoice->notes);
        $this->assertCount(1, $invoice->items);
        $this->assertSame('Anschlüsse mit Bitumenvoranstrich streichen', $invoice->items->first()->description);

        $offer->refresh();
        $this->assertSame($invoice->id, $offer->converted_to_invoice_id);
    }
}
