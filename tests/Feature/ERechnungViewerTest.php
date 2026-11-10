<?php

namespace Tests\Feature;

use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Models\InvoiceItem;
use App\Modules\User\Models\User;
use App\Services\ERechnungService;
use horstoeko\zugferdublbridge\XmlConverterCiiToUbl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ERechnungViewerTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected User $user;

    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seedRolesAndPermissions();

        $this->company = Company::create([
            'name' => 'Viewer GmbH',
            'email' => 'viewer@example.com',
            'status' => 'active',
            'vat_number' => 'DE123456789',
            'address' => 'Teststraße 1',
            'postal_code' => '10115',
            'city' => 'Berlin',
        ]);
        $this->company->setBankSettings([
            'bank_name' => 'Testbank',
            'bank_iban' => 'DE89370400440532013000',
            'bank_bic' => 'COBADEFFXXX',
            'bank_account_holder' => 'Viewer GmbH',
        ]);

        $this->user = User::factory()->create(['company_id' => $this->company->id]);

        $customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Empfänger AG',
            'email' => 'empfaenger@example.com',
            'status' => 'active',
            'customer_type' => 'business',
            'address' => 'Kundenweg 2',
            'postal_code' => '20095',
            'city' => 'Hamburg',
        ]);

        $this->invoice = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'number' => 'RE-2026-EV01',
            'status' => 'sent',
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'subtotal' => 200.00,
            'tax_rate' => 0.19,
            'tax_amount' => 38.00,
            'total' => 238.00,
        ]);

        InvoiceItem::create([
            'invoice_id' => $this->invoice->id,
            'description' => 'Beratung',
            'quantity' => 2,
            'unit_price' => 100.00,
            'unit' => 'Std.',
            'tax_rate' => 0.19,
            'total' => 200.00,
            'sort_order' => 0,
        ]);
    }

    public function test_viewer_page_renders(): void
    {
        $this->actingAs($this->user)
            ->get(route('e-invoice.viewer'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('invoices/erechnung-viewer'));
    }

    public function test_parses_a_cii_xrechnung_xml(): void
    {
        $xml = app(ERechnungService::class)->generateXRechnung($this->invoice);

        $file = UploadedFile::fake()->createWithContent('xrechnung.xml', $xml);

        $this->actingAs($this->user)
            ->post(route('e-invoice.parse'), ['file' => $file])
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('invoices/erechnung-viewer')
                ->where('parse_error', null)
                ->where('parsed.document.number', 'RE-2026-EV01')
                ->where('parsed.seller.name', 'Viewer GmbH')
                ->where('parsed.buyer.name', 'Empfänger AG')
                ->where('parsed.totals.grand_total', 238)
                ->has('parsed.positions', 1)
            );
    }

    public function test_parses_a_ubl_xrechnung_via_the_bridge(): void
    {
        $cii = app(ERechnungService::class)->generateXRechnung($this->invoice);
        $ubl = XmlConverterCiiToUbl::fromString($cii)->convert()->saveXmlString();

        $file = UploadedFile::fake()->createWithContent('xrechnung-ubl.xml', $ubl);

        $this->actingAs($this->user)
            ->post(route('e-invoice.parse'), ['file' => $file])
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('parse_error', null)
                ->where('parsed.document.number', 'RE-2026-EV01')
                ->where('parsed.totals.grand_total', 238)
            );
    }

    public function test_parses_a_zugferd_pdf(): void
    {
        $pdf = app(ERechnungService::class)->generateZugferd($this->invoice);

        $file = UploadedFile::fake()->createWithContent('rechnung.pdf', $pdf);

        $this->actingAs($this->user)
            ->post(route('e-invoice.parse'), ['file' => $file])
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('parse_error', null)
                ->where('parsed.document.number', 'RE-2026-EV01')
            );
    }

    public function test_plain_pdf_without_xml_returns_a_friendly_error(): void
    {
        $file = UploadedFile::fake()->createWithContent('brief.pdf', "%PDF-1.4\n%%EOF");

        $this->actingAs($this->user)
            ->post(route('e-invoice.parse'), ['file' => $file])
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('parsed', null)
                ->whereNot('parse_error', null)
            );
    }

    public function test_viewer_requires_the_invoices_module(): void
    {
        $this->company->update(['enabled_modules' => ['expenses']]);

        $this->actingAs($this->user)
            ->get(route('e-invoice.viewer'))
            ->assertForbidden();
    }
}
