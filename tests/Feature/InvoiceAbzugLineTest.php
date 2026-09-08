<?php

namespace Tests\Feature;

use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Models\InvoiceItem;
use App\Modules\User\Models\User;
use App\Services\ERechnungService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InvoiceAbzugLineTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected User $user;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seedRolesAndPermissions();

        $this->company = Company::create([
            'name' => 'Abzug GmbH',
            'email' => 'abzug@example.com',
            'status' => 'active',
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'user',
        ]);
        $this->user->assignRole('user');

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Kunde Abzug',
            'email' => 'kunde-abzug@example.com',
            'status' => 'active',
            'customer_type' => 'business',
        ]);
    }

    public function test_mixed_plus_and_minus_lines_reduce_net_and_vat(): void
    {
        $this->actingAs($this->user);

        $this->post('/invoices', $this->payload([
            [
                'description' => 'Leistung',
                'quantity' => 1,
                'unit_price' => 100.00,
                'unit' => 'Stk.',
                'tax_rate' => 0.19,
            ],
            [
                'description' => 'Nachlass',
                'quantity' => 1,
                'unit_price' => -20.00,
                'unit' => 'Stk.',
                'tax_rate' => 0.19,
            ],
        ]))->assertRedirect();

        $invoice = $this->latestInvoice();
        $invoice->load('items');

        $this->assertCount(2, $invoice->items);
        $this->assertEqualsWithDelta(80.00, (float) $invoice->subtotal, 0.01);
        $this->assertEqualsWithDelta(15.20, (float) $invoice->tax_amount, 0.01);
        $this->assertEqualsWithDelta(95.20, (float) $invoice->total, 0.01);

        $abzug = $invoice->items->firstWhere('description', 'Nachlass');
        $this->assertTrue($abzug->isAbzug());
        $this->assertEqualsWithDelta(-20.00, (float) $abzug->total, 0.01);
        $this->assertNull($abzug->discount_type);
        $this->assertEquals(0.0, (float) $abzug->discount_amount);
    }

    public function test_negative_invoice_total_is_rejected(): void
    {
        $this->actingAs($this->user);

        $this->post('/invoices', $this->payload([
            [
                'description' => 'Kleine Leistung',
                'quantity' => 1,
                'unit_price' => 10.00,
                'unit' => 'Stk.',
                'tax_rate' => 0.19,
            ],
            [
                'description' => 'Zu hoher Nachlass',
                'quantity' => 1,
                'unit_price' => -50.00,
                'unit' => 'Stk.',
                'tax_rate' => 0.19,
            ],
        ]))->assertSessionHasErrors('items');

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_negative_quantity_is_rejected_on_normal_invoices(): void
    {
        $this->actingAs($this->user);

        $this->post('/invoices', $this->payload([
            [
                'description' => 'Ungueltige Menge',
                'quantity' => -1,
                'unit_price' => 50.00,
                'unit' => 'Stk.',
                'tax_rate' => 0.19,
            ],
        ]))->assertSessionHasErrors('items.0.quantity');

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_discount_on_abzug_line_is_rejected(): void
    {
        $this->actingAs($this->user);

        $this->post('/invoices', $this->payload([
            [
                'description' => 'Leistung',
                'quantity' => 1,
                'unit_price' => 100.00,
                'unit' => 'Stk.',
                'tax_rate' => 0.19,
            ],
            [
                'description' => 'Nachlass',
                'quantity' => 1,
                'unit_price' => -20.00,
                'unit' => 'Stk.',
                'tax_rate' => 0.19,
                'discount_type' => 'fixed',
                'discount_value' => 5,
            ],
        ]))->assertSessionHasErrors('items.1.discount_type');

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_stornorechnung_still_uses_negative_quantity(): void
    {
        Permission::firstOrCreate(['name' => 'create_stornorechnung', 'guard_name' => 'web']);
        $this->user->givePermissionTo('create_stornorechnung');
        $this->actingAs($this->user);

        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'number' => 'RE-2026-STORNO-SRC',
            'status' => 'sent',
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'subtotal' => 200.00,
            'tax_rate' => 0.19,
            'tax_amount' => 38.00,
            'total' => 238.00,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Originalposition',
            'quantity' => 2,
            'unit_price' => 100.00,
            'tax_rate' => 0.19,
            'total' => 200.00,
            'sort_order' => 0,
        ]);

        $this->post(route('invoices.create-correction', $invoice), [
            'correction_reason' => 'Falsche Abrechnung',
        ])->assertRedirect();

        $storno = Invoice::query()
            ->where('is_correction', true)
            ->where('corrects_invoice_id', $invoice->id)
            ->first();

        $this->assertNotNull($storno);
        $storno->load('items');

        $this->assertCount(1, $storno->items);
        $this->assertEqualsWithDelta(-2.0, (float) $storno->items->first()->quantity, 0.01);
        $this->assertEqualsWithDelta(100.00, (float) $storno->items->first()->unit_price, 0.01);
        $this->assertEqualsWithDelta(-200.00, (float) $storno->items->first()->total, 0.01);
        $this->assertEqualsWithDelta(-238.00, (float) $storno->total, 0.01);
    }

    public function test_xrechnung_maps_abzug_as_document_allowance(): void
    {
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'number' => 'RE-2026-ABZUG-XML',
            'status' => 'sent',
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'subtotal' => 80.00,
            'tax_rate' => 0.19,
            'tax_amount' => 15.20,
            'total' => 95.20,
            'vat_regime' => 'standard',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Leistung',
            'quantity' => 1,
            'unit_price' => 100.00,
            'tax_rate' => 0.19,
            'total' => 100.00,
            'sort_order' => 0,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Nachlass Kulanz',
            'quantity' => 1,
            'unit_price' => -20.00,
            'tax_rate' => 0.19,
            'total' => -20.00,
            'sort_order' => 1,
        ]);

        $xml = app(ERechnungService::class)->generateXRechnung($invoice->fresh(['items', 'company', 'customer']));

        $this->assertStringContainsString('SpecifiedTradeAllowanceCharge', $xml);
        $this->assertStringContainsString('Nachlass Kulanz', $xml);
        $this->assertStringContainsString('>20.00</', $xml);
        $this->assertStringNotContainsString('<ram:BilledQuantity>-', $xml);
        $this->assertStringContainsString('Leistung', $xml);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function payload(array $items): array
    {
        return [
            'customer_id' => $this->customer->id,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(14)->format('Y-m-d'),
            'notes' => null,
            'layout_id' => null,
            'vat_regime' => 'standard',
            'items' => $items,
        ];
    }

    private function latestInvoice(): Invoice
    {
        $invoice = Invoice::query()
            ->where('company_id', $this->company->id)
            ->orderByDesc('created_at')
            ->first();

        $this->assertNotNull($invoice);

        return $invoice;
    }
}
