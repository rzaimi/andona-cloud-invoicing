<?php

namespace Tests\Feature;

use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Payment\Models\Payment;
use App\Modules\User\Models\User;
use App\Services\FormattingService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePdfGirocodeTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Girocode GmbH',
            'email' => 'qr@example.com',
            'status' => 'active',
        ]);
        $this->company->setBankSettings([
            'bank_name' => 'Testbank',
            'bank_iban' => 'DE89370400440532013000',
            'bank_bic' => 'COBADEFFXXX',
            'bank_account_holder' => 'Girocode GmbH',
        ]);

        $user = User::factory()->create(['company_id' => $this->company->id]);
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'QR Kunde',
            'email' => 'kunde@example.com',
            'status' => 'active',
            'customer_type' => 'business',
        ]);

        $this->invoice = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'number' => 'RE-2026-QR01',
            'status' => 'sent',
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'subtotal' => 100.00,
            'tax_rate' => 0.19,
            'tax_amount' => 19.00,
            'total' => 119.00,
        ]);
    }

    private function renderPdfHtml(array $contentSettings): string
    {
        return view('pdf.invoice', [
            'layout' => (object) [
                'template' => 'minimal',
                'settings' => ['content' => $contentSettings],
            ],
            'invoice' => $this->invoice->load(['customer', 'items.product', 'company', 'user']),
            'company' => $this->company,
            'customer' => $this->invoice->customer,
            'settings' => app(SettingsService::class)->getAll($this->company->id),
            'formattingService' => app(FormattingService::class),
        ])->render();
    }

    public function test_girocode_renders_when_layout_option_is_enabled(): void
    {
        $html = $this->renderPdfHtml(['show_payment_qr' => true]);

        $this->assertStringContainsString('Bezahlen per Girocode', $html);
        $this->assertStringContainsString('data:image/png;base64', $html);
    }

    public function test_girocode_hidden_when_layout_option_is_disabled(): void
    {
        $html = $this->renderPdfHtml(['show_payment_qr' => false]);

        $this->assertStringNotContainsString('Bezahlen per Girocode', $html);
        $this->assertStringNotContainsString('data:image/png;base64', $html);
    }

    public function test_girocode_hidden_when_invoice_is_settled_or_bank_data_missing(): void
    {
        // Fully paid: nothing to transfer, no QR even with the option on.
        Payment::create([
            'company_id' => $this->company->id,
            'invoice_id' => $this->invoice->id,
            'amount' => 119.00,
            'payment_date' => now(),
            'payment_method' => 'bank_transfer',
            'status' => 'completed',
            'created_by' => $this->invoice->user_id,
        ]);
        $html = $this->renderPdfHtml(['show_payment_qr' => true]);
        $this->assertStringNotContainsString('Bezahlen per Girocode', $html);

        // No IBAN: no QR either.
        $this->invoice->payments()->delete();
        $this->company->setBankSettings([
            'bank_name' => null, 'bank_iban' => null, 'bank_bic' => null, 'bank_account_holder' => null,
        ]);
        $html = $this->renderPdfHtml(['show_payment_qr' => true]);
        $this->assertStringNotContainsString('Bezahlen per Girocode', $html);
    }
}
