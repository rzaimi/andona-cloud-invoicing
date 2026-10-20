<?php

namespace Tests\Feature;

use App\Modules\Company\Models\Company;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfPageChromeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::disk('public')->put(
            'logos/page-chrome.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==')
        );
    }

    public function test_invoice_keeps_the_repeating_footer_and_first_page_logo_by_default(): void
    {
        $html = $this->render('pdf.invoice', 'invoice', []);

        $this->assertStringContainsString('margin-bottom: 35mm', $html);
        $this->assertStringContainsString('class="pdf-footer"', $html);
        $this->assertStringContainsString('position: fixed', $html);
        $this->assertStringNotContainsString('@page :first', $html);
        $this->assertStringNotContainsString('class="pdf-footer-flow"', $html);
        $this->assertStringContainsString("'enabled' => false", $html);
        $this->assertStringContainsString("'hideFooterExceptLast' => false", $html);
    }

    public function test_invoice_can_repeat_the_logo_and_keep_the_footer_for_the_last_page(): void
    {
        $html = $this->render('pdf.invoice', 'invoice', [
            'branding' => [
                'logo_on_every_page' => true,
                'logo_position' => 'top-right',
                'logo_size' => 'small',
            ],
            'content' => [
                'footer_last_page_only' => true,
            ],
        ]);

        $logoPath = Storage::disk('public')->path('logos/page-chrome.png');

        $this->assertStringContainsString('margin: 18mm 0 5mm 0', $html);
        $this->assertStringContainsString('@page :first', $html);
        $this->assertStringContainsString('margin-bottom: 35mm', $html);
        $this->assertStringContainsString('class="pdf-footer"', $html);
        $this->assertStringContainsString('position: fixed', $html);
        $this->assertStringContainsString($logoPath, $html);
        $this->assertStringContainsString("'pos' => \"top-right\"", $html);
        $this->assertStringContainsString("'hideFooterExceptLast' => true", $html);

        $pdf = $this->renderPdf($html);
        $this->assertGreaterThanOrEqual(2, $this->pageCount($pdf));
    }

    public function test_offer_uses_the_same_page_chrome_options(): void
    {
        $html = $this->render('pdf.offer', 'offer', [
            'branding' => [
                'logo_on_every_page' => true,
                'logo_position' => 'center',
                'logo_size' => 'medium',
            ],
            'content' => [
                'footer_last_page_only' => true,
            ],
        ]);

        $this->assertStringContainsString('margin: 24mm 0 5mm 0', $html);
        $this->assertStringContainsString('@page :first', $html);
        $this->assertStringContainsString('position: fixed', $html);
        $this->assertStringContainsString("'pos' => \"top-center\"", $html);
        $this->assertStringContainsString("'hideFooterExceptLast' => true", $html);
        $this->assertGreaterThanOrEqual(2, $this->pageCount($this->renderPdf($html)));
    }

    private function render(string $view, string $kind, array $settings): string
    {
        $company = Company::create([
            'name' => 'Seiten GmbH',
            'email' => 'seiten@example.com',
            'status' => 'active',
            'logo' => 'logos/page-chrome.png',
            'address' => 'Hauptstraße 1',
            'postal_code' => '10115',
            'city' => 'Berlin',
        ]);

        $customer = (object) [
            'name' => 'Musterkunde GmbH',
            'contact_person' => 'Herr Mustermann',
            'address' => 'Kundenstraße 1',
            'postal_code' => '80331',
            'city' => 'München',
            'country' => 'Deutschland',
            'number' => 'KU-1',
        ];

        $items = collect(range(1, 30))->map(fn (int $n) => (object) [
            'id' => 'item-'.$n,
            'description' => 'Position '.$n,
            'quantity' => 1,
            'unit' => 'Stk.',
            'unit_price' => 10,
            'tax_rate' => 0.19,
            'total' => 10,
            'discount_type' => null,
            'discount_value' => null,
            'discount_amount' => 0,
        ]);

        $document = (object) [
            'number' => $kind === 'offer' ? 'AN-1' : 'RE-1',
            'status' => 'draft',
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'valid_until' => now()->addDays(30),
            'subtotal' => 300,
            'tax_rate' => 0.19,
            'tax_amount' => 57,
            'total' => 357,
            'notes' => null,
            'terms_conditions' => null,
            'service_date' => null,
            'service_period_start' => null,
            'service_period_end' => null,
            'bauvorhaben' => null,
            'vat_regime' => 'standard',
            'skonto_percent' => null,
            'skonto_days' => null,
            'skonto_amount' => null,
            'skonto_due_date' => null,
            'invoice_type' => 'standard',
            'is_correction' => false,
            'sequence_number' => null,
            'title' => null,
            'salutation' => null,
            'customer' => $customer,
            'items' => $items,
        ];

        return view($view, [
            'layout' => (object) [
                'name' => 'Test',
                'template' => 'minimal',
                'settings' => $settings,
            ],
            'invoice' => $document,
            'offer' => $document,
            'company' => $company,
            'customer' => $customer,
            'settings' => [],
            'preview' => false,
        ])->render();
    }

    private function renderPdf(string $html): string
    {
        return Pdf::loadHTML($html)
            ->setPaper('a4')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'enable-local-file-access' => true,
                'isPhpEnabled' => true,
            ])
            ->output();
    }

    private function pageCount(string $pdf): int
    {
        preg_match_all('/\/Type\s*\/Page(?!s)/', $pdf, $matches);

        return count($matches[0]);
    }
}
