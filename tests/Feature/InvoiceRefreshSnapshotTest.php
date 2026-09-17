<?php

namespace Tests\Feature;

use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\User\Models\User;
use App\Services\FormattingService;
use App\Services\SettingsService;
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
        $this->company->setBankSettings([
            'bank_name' => 'Alte Bank',
            'bank_iban' => 'DE11111111111111111111',
            'bank_bic' => 'ALTEDEFFXXX',
            'bank_account_holder' => 'Alte Firma',
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
            'bank_iban' => 'DE11111111111111111111',
        ]);

        $this->company->update([
            'name' => 'Neue Firma',
            'address' => 'Neustrasse 9',
        ]);
        $this->company->setBankSettings([
            'bank_name' => 'Neue Bank',
            'bank_iban' => 'DE22222222222222222222',
            'bank_bic' => 'NEUEDEFFXXX',
            'bank_account_holder' => 'Neue Firma',
        ]);

        $this->actingAs($this->admin)
            ->post("/invoices/{$invoice->id}/refresh-snapshot")
            ->assertRedirect()
            ->assertSessionHas('success');

        $snapshot = $invoice->fresh()->company_snapshot;

        $this->assertSame('Neue Firma', $snapshot['name']);
        $this->assertSame('Neustrasse 9', $snapshot['address']);
        $this->assertSame('Neue Firma GmbH', $snapshot['display_name']);
        $this->assertSame('DE22222222222222222222', $snapshot['bank_iban']);
        $this->assertSame('Neue Bank', $snapshot['bank_name']);
    }

    public function test_sent_invoice_button_overwrites_bank_snapshot_fields(): void
    {
        $invoice = $this->makeInvoice('sent', [
            'name' => 'Alte Firma',
            'address' => 'Altstrasse 1',
            'bank_iban' => 'DE11111111111111111111',
            'bank_name' => 'Alte Bank',
        ]);

        $this->company->setBankSettings([
            'bank_name' => 'Neue Bank',
            'bank_iban' => 'DE22222222222222222222',
            'bank_bic' => 'NEUEDEFFXXX',
            'bank_account_holder' => 'Neue Firma',
        ]);

        $this->actingAs($this->admin)
            ->post("/invoices/{$invoice->id}/refresh-snapshot")
            ->assertRedirect()
            ->assertSessionHas('success');

        $snapshot = $invoice->fresh()->company_snapshot;

        $this->assertSame('DE22222222222222222222', $snapshot['bank_iban']);
        $this->assertSame('Neue Bank', $snapshot['bank_name']);
    }

    public function test_fill_only_refresh_does_not_overwrite_existing_bank_fields(): void
    {
        $invoice = $this->makeInvoice('sent', [
            'name' => 'Alte Firma',
            'bank_iban' => 'DE11111111111111111111',
            'bank_name' => 'Alte Bank',
        ]);

        $this->company->setBankSettings([
            'bank_name' => 'Neue Bank',
            'bank_iban' => 'DE22222222222222222222',
            'bank_bic' => 'NEUEDEFFXXX',
            'bank_account_holder' => 'Neue Firma',
        ]);

        $filled = $invoice->refreshCompanySnapshot(false);

        $this->assertNotContains('bank_iban', $filled);
        $this->assertSame('DE11111111111111111111', $invoice->fresh()->company_snapshot['bank_iban']);
    }

    public function test_draft_pdf_footer_uses_live_bank_settings_without_refresh(): void
    {
        $invoice = $this->makeInvoice('draft', [
            'name' => 'Alte Firma',
            'bank_iban' => 'DE11111111111111111111',
            'bank_name' => 'Alte Bank',
        ]);

        $this->company->setBankSettings([
            'bank_name' => 'Neue Bank',
            'bank_iban' => 'DE22222222222222222222',
            'bank_bic' => 'NEUEDEFFXXX',
            'bank_account_holder' => 'Neue Firma',
        ]);

        $html = view('pdf.invoice', [
            'layout' => (object) [
                'template' => 'minimal',
                'settings' => ['content' => ['show_bank_details' => true]],
            ],
            'invoice' => $invoice->fresh()->load(['customer', 'company', 'user']),
            'company' => $this->company->fresh(),
            'customer' => $this->customer,
            'settings' => app(SettingsService::class)->getAll($this->company->id),
            'formattingService' => app(FormattingService::class),
        ])->render();

        $this->assertStringContainsString('DE22222222222222222222', $html);
        $this->assertStringNotContainsString('DE11111111111111111111', $html);
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
            'number' => 'RE-2026-SNAP-'.$status.'-'.substr(uniqid(), -6),
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
