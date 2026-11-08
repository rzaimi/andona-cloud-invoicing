<?php

namespace Tests\Feature;

use App\Modules\Company\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentDetailsStyleTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_values_share_one_weight_and_the_columns_sit_close(): void
    {
        foreach (['pdf.invoice', 'pdf.offer'] as $view) {
            foreach (['minimal', 'modern', 'professional'] as $template) {
                $html = $this->render($view, $template);

                $this->assertStringNotContainsString('width:46%', $html);
                $this->assertStringContainsString('width:60mm; table-layout:fixed', $html);
                $this->assertStringContainsString('word-wrap:break-word', $html);
                $this->assertStringContainsString('align="right"', $html);
                $this->assertStringContainsString('padding-right:2mm', $html);
                $this->assertStringContainsString($view === 'pdf.offer' ? 'Angebotsnr.:' : 'Rechnungsnr.:', $html);
                $this->assertStringContainsString('BV:', $html);
                $this->assertStringContainsString('Auftragsnr.:', $html);
                $this->assertStringNotContainsString('Deutschland', $html);
                if ($template === 'minimal') {
                    $this->assertStringContainsString('margin-bottom:1mm', $html);
                }

                foreach (['RE-2026-050', '02.11.2026', 'Bolongarostraße 109', '2510-199 MAC'] as $value) {
                    $this->assertMatchesRegularExpression(
                        '/font-size:10px; font-weight:700;[^>]*>'.preg_quote($value, '/').'/',
                        $html
                    );
                }
            }
        }
    }

    private function render(string $view, string $template): string
    {
        $company = Company::create([
            'name' => 'Details GmbH',
            'email' => uniqid('details-', true).'@example.com',
            'status' => 'active',
        ]);

        $customer = (object) [
            'name' => 'Musterkunde GmbH',
            'contact_person' => null,
            'address' => 'Kundenstraße 1',
            'postal_code' => '80331',
            'city' => 'München',
            'country' => 'Deutschland',
            'number' => 'KU-1',
        ];

        $document = (object) [
            'number' => 'RE-2026-050',
            'status' => 'sent',
            'issue_date' => '2026-11-02',
            'due_date' => '2026-11-18',
            'valid_until' => '2026-12-02',
            'subtotal' => 100,
            'tax_rate' => 0.19,
            'tax_amount' => 19,
            'total' => 119,
            'notes' => null,
            'terms_conditions' => null,
            'service_date' => null,
            'service_period_start' => null,
            'service_period_end' => null,
            'bauvorhaben' => 'Bolongarostraße 109, Frankfurt am Main',
            'auftragsnummer' => '2510-199 MAC',
            'vat_regime' => 'standard',
            'skonto_percent' => 2,
            'skonto_days' => 7,
            'skonto_amount' => 2.38,
            'skonto_due_date' => '2026-11-09',
            'invoice_type' => 'standard',
            'is_correction' => false,
            'sequence_number' => null,
            'title' => null,
            'customer' => $customer,
            'items' => collect([
                (object) [
                    'id' => 'item-1',
                    'description' => 'Position',
                    'quantity' => 1,
                    'unit' => 'Stk.',
                    'unit_price' => 100,
                    'tax_rate' => 0.19,
                    'total' => 100,
                    'discount_type' => null,
                    'discount_value' => null,
                    'discount_amount' => 0,
                ],
            ]),
        ];

        return view($view, [
            'layout' => (object) [
                'name' => 'Test',
                'template' => $template,
                'settings' => [
                    'colors' => ['primary' => '#0e7490', 'text' => '#1f2937', 'secondary' => '#64748b'],
                    'fonts' => ['size' => 'small'],
                ],
            ],
            'invoice' => $document,
            'offer' => $document,
            'company' => $company,
            'customer' => $customer,
            'settings' => ['date_format' => 'd.m.Y'],
            'preview' => false,
        ])->render();
    }
}
