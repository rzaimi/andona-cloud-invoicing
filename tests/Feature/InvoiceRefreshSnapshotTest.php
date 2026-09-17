<?php

namespace Tests\Feature;

use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\User\Models\User;
use Tests\TestCase;

class InvoiceRefreshSnapshotTest extends TestCase
{
    protected Company $company;

    protected User $admin;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seedRolesAndPermissions();

        $this->company = Company::create([
            'name' => 'Alte Firma',
            'email' => 'alt@example.com',
            'address' => 'Altstrasse 1',
            'postal_code' => '10115',
            'city' => 'Berlin',
            'status' => 'active',
            'legal_form' => Company::LEGAL_FORM_GMBH,
        ]);

        $this->admin = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'admin',
        ]);
        $this->admin->assignRole('admin');

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Kunde Snapshot',
            'email' => 'kunde-snapshot@example.com',
            'status' => 'active',
            'customer_type' => 'business',
        ]);
    }

    public function test_draft_invoice_overwrites_existing_company_snapshot_fields(): void
    {
        $invoice = $this->makeInvoice('draft', [
            'name' => 'Alte Firma',
            'address' => 'Altstrasse 1',
            'display_name' => 'Alte Firma GmbH',
        ]);

        $this->company->update([
            'name' => 'Neue Firma',
            'address' => 'Neustrasse 9',
        ]);

        $this->actingAs($this->admin)
            ->post("/invoices/{$invoice->id}/refresh-snapshot")
            ->assertRedirect()
            ->assertSessionHas('success');

        $snapshot = $invoice->fresh()->company_snapshot;

        $this->assertSame('Neue Firma', $snapshot['name']);
        $this->assertSame('Neustrasse 9', $snapshot['address']);
        $this->assertSame('Neue Firma GmbH', $snapshot['display_name']);
    }

    public function test_sent_invoice_does_not_overwrite_existing_snapshot_fields(): void
    {
        $invoice = $this->makeInvoice('sent', [
            'name' => 'Alte Firma',
            'address' => 'Altstrasse 1',
        ]);

        $this->company->update([
            'name' => 'Neue Firma',
            'address' => 'Neustrasse 9',
        ]);

        $this->actingAs($this->admin)
            ->post("/invoices/{$invoice->id}/refresh-snapshot")
            ->assertRedirect()
            ->assertSessionHas('success');

        $snapshot = $invoice->fresh()->company_snapshot;

        $this->assertSame('Alte Firma', $snapshot['name']);
        $this->assertSame('Altstrasse 1', $snapshot['address']);
    }

    public function test_sent_invoice_fills_missing_snapshot_fields(): void
    {
        $invoice = $this->makeInvoice('sent', [
            'name' => 'Alte Firma',
            'address' => 'Altstrasse 1',
        ]);

        $this->actingAs($this->admin)
            ->post("/invoices/{$invoice->id}/refresh-snapshot")
            ->assertRedirect()
            ->assertSessionHas('success');

        $snapshot = $invoice->fresh()->company_snapshot;

        $this->assertSame('Alte Firma', $snapshot['name']);
        $this->assertSame(Company::LEGAL_FORM_GMBH, $snapshot['legal_form']);
        $this->assertSame('GmbH', $snapshot['legal_form_label']);
        $this->assertNotEmpty($snapshot['display_name']);
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function makeInvoice(string $status, array $snapshot): Invoice
    {
        return Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'number' => 'RE-2026-SNAP-'.$status,
            'status' => $status,
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'subtotal' => 100.00,
            'tax_rate' => 0.19,
            'tax_amount' => 19.00,
            'total' => 119.00,
            'company_snapshot' => $snapshot,
        ]);
    }
}
