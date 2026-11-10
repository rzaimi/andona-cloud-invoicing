<?php

namespace Tests\Feature;

use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Models\InvoiceItem;
use App\Modules\User\Models\User;
use App\Services\ERechnungService;
use horstoeko\zugferd\ZugferdDocumentReader;
use horstoeko\zugferd\ZugferdXsdValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ERechnungStandardsTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Customer $customer;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Standards GmbH',
            'email' => 'standards@example.com',
            'status' => 'active',
            'vat_number' => 'DE123456789',
            'tax_number' => '22/815/08154',
            'managing_director' => 'Max Mustermann',
            'phone' => '+49 30 123456',
            'address' => 'Teststraße 1',
            'postal_code' => '10115',
            'city' => 'Berlin',
        ]);
        $this->company->setBankSettings([
            'bank_name' => 'Testbank',
            'bank_iban' => 'DE89370400440532013000',
            'bank_bic' => 'COBADEFFXXX',
            'bank_account_holder' => 'Standards GmbH',
        ]);

        $this->user = User::factory()->create(['company_id' => $this->company->id]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Kunde AG',
            'number' => 'KU-2026-0001',
            'email' => 'kunde@example.com',
            'status' => 'active',
            'customer_type' => 'business',
            'vat_number' => 'DE987654321',
            'address' => 'Kundenweg 2',
            'postal_code' => '20095',
            'city' => 'Hamburg',
        ]);
    }

    private function makeInvoice(array $overrides = [], array $itemOverrides = []): Invoice
    {
        $invoice = Invoice::create(array_merge([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'number' => 'RE-2026-STD-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'status' => 'sent',
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'subtotal' => 100.00,
            'tax_rate' => 0.19,
            'tax_amount' => 19.00,
            'total' => 119.00,
        ], $overrides));

        InvoiceItem::create(array_merge([
            'invoice_id' => $invoice->id,
            'description' => 'Leistung',
            'quantity' => 1,
            'unit_price' => 100.00,
            'unit' => 'Stk.',
            'tax_rate' => 0.19,
            'total' => 100.00,
            'sort_order' => 0,
        ], $itemOverrides));

        return $invoice->fresh();
    }

    private function xrechnungXml(Invoice $invoice): string
    {
        $this->company->setSetting('zugferd_profile', 'XRECHNUNG', 'string');

        return app(ERechnungService::class)->generateXRechnung($invoice);
    }

    public function test_xrechnung_uses_version_3(): void
    {
        $xml = $this->xrechnungXml($this->makeInvoice());

        $this->assertStringContainsString('urn:xeinkauf.de:kosit:xrechnung_3.0', $xml);
        $this->assertStringNotContainsString('xrechnung_1.2', $xml);
    }

    public function test_vat_id_is_written_as_tax_registration_not_global_id(): void
    {
        $xml = $this->xrechnungXml($this->makeInvoice());

        $this->assertStringContainsString('schemeID="VA">DE123456789<', $xml);
        $this->assertStringContainsString('schemeID="FC">22/815/08154<', $xml);
        // Buyer VAT id as tax registration too
        $this->assertStringContainsString('schemeID="VA">DE987654321<', $xml);
        // The old, wrong GLN scheme must be gone
        $this->assertStringNotContainsString('schemeID="0088"', $xml);
    }

    public function test_xrechnung_carries_mandatory_german_fields(): void
    {
        $xml = $this->xrechnungXml($this->makeInvoice());

        // BT-10 buyer reference falls back to the customer number (BR-DE-15)
        $this->assertStringContainsString('<ram:BuyerReference>KU-2026-0001</ram:BuyerReference>', $xml);
        // BG-6 seller contact with a real name (BR-DE-5)
        $this->assertStringContainsString('Max Mustermann', $xml);
        // BG-16 payment instructions with IBAN (BR-DE-1)
        $this->assertStringContainsString('DE89370400440532013000', $xml);
        // BT-23 business process
        $this->assertStringContainsString('urn:fdc:peppol.eu:2017:poacc:billing:01:1.0', $xml);
        // BT-34/BT-49 electronic addresses (email scheme)
        $this->assertStringContainsString('standards@example.com', $xml);
        $this->assertStringContainsString('kunde@example.com', $xml);
    }

    public function test_leitweg_id_forces_xrechnung_3(): void
    {
        $this->customer->update(['leitweg_id' => '04011000-1234512345-06']);

        $xml = app(ERechnungService::class)->generateXRechnung($this->makeInvoice());

        $this->assertStringContainsString('urn:xeinkauf.de:kosit:xrechnung_3.0', $xml);
        $this->assertStringContainsString('<ram:BuyerReference>04011000-1234512345-06</ram:BuyerReference>', $xml);
    }

    public function test_reverse_charge_invoice_uses_category_ae_on_lines_and_document(): void
    {
        $invoice = $this->makeInvoice([
            'vat_regime' => 'reverse_charge',
            'tax_amount' => 0,
            'total' => 100.00,
        ]);

        $xml = app(ERechnungService::class)->generateXRechnung($invoice);

        $this->assertStringContainsString('<ram:CategoryCode>AE</ram:CategoryCode>', $xml);
        // Exemption reason is mandatory for AE breakdowns (BR-AE-10)
        $this->assertStringContainsString('Steuerschuldnerschaft des Leistungsempfängers', $xml);
        // No line may still claim the standard category
        $this->assertStringNotContainsString('<ram:CategoryCode>S</ram:CategoryCode>', $xml);
    }

    public function test_small_business_invoice_is_exempt_with_paragraph_19_reason(): void
    {
        $invoice = $this->makeInvoice([
            'vat_regime' => 'small_business',
            'tax_amount' => 0,
            'total' => 100.00,
        ]);

        $xml = app(ERechnungService::class)->generateXRechnung($invoice);

        $this->assertStringContainsString('<ram:CategoryCode>E</ram:CategoryCode>', $xml);
        $this->assertStringContainsString('§ 19 UStG', $xml);
    }

    public function test_korrekturrechnung_uses_type_code_384(): void
    {
        $invoice = $this->makeInvoice(['invoice_type' => Invoice::TYPE_KORREKTURRECHNUNG]);

        $xml = app(ERechnungService::class)->generateXRechnung($invoice);

        $this->assertStringContainsString('<ram:TypeCode>384</ram:TypeCode>', $xml);
    }

    public function test_item_units_map_to_unece_codes(): void
    {
        $invoice = $this->makeInvoice([], ['unit' => 'Std.']);

        $xml = app(ERechnungService::class)->generateXRechnung($invoice);

        $this->assertStringContainsString('unitCode="HUR"', $xml);
    }

    public function test_generated_xml_passes_xsd_validation(): void
    {
        foreach (['standard', 'reverse_charge', 'small_business'] as $regime) {
            $invoice = $this->makeInvoice([
                'vat_regime' => $regime,
                'tax_amount' => $regime === 'standard' ? 19.00 : 0,
                'total' => $regime === 'standard' ? 119.00 : 100.00,
            ]);
            $this->company->setSetting('zugferd_profile', 'XRECHNUNG', 'string');

            $xml = app(ERechnungService::class)->generateXRechnung($invoice);
            $validator = new ZugferdXsdValidator(ZugferdDocumentReader::readAndGuessFromContent($xml));
            $validator->validate();

            $this->assertFalse(
                $validator->hasValidationErrors(),
                "XSD validation failed for regime {$regime}: ".json_encode($validator->validationErrors())
            );
        }
    }
}
