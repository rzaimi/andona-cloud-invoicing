<?php

namespace Tests\Feature;

use App\Modules\Company\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EinzelunternehmenReturnAddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_sole_trader_return_address_puts_the_owner_above_the_street(): void
    {
        foreach (['pdf.invoice', 'pdf.offer'] as $view) {
            foreach (['minimal', 'professional', 'modern'] as $template) {
                $html = $this->render($view, $template, [
                    'name' => 'Elifat Service',
                    'legal_form' => 'einzelunternehmen',
                    'managing_director' => 'Elifat Iseni',
                    'address' => 'Rembrücker Weg 22',
                    'postal_code' => '63179',
                    'city' => 'Obertshausen',
                ]);

                $this->assertStringContainsString('Elifat Service · Elifat Iseni', $html);
                $this->assertStringContainsString('Rembrücker Weg 22 · 63179 Obertshausen', $html);
                $this->assertStringNotContainsString('Elifat Iseni · Rembrücker Weg 22', $html);
            }
        }
    }

    public function test_other_legal_forms_keep_a_single_return_line(): void
    {
        $gmbh = $this->render('pdf.invoice', 'minimal', [
            'name' => 'Seiten GmbH',
            'legal_form' => 'gmbh',
            'managing_director' => 'Ada Admin',
            'address' => 'Hauptstraße 1',
            'postal_code' => '10115',
            'city' => 'Berlin',
        ]);
        $this->assertStringContainsString('Seiten GmbH · Hauptstraße 1 · 10115 Berlin', $gmbh);
        $this->assertStringNotContainsString('Seiten GmbH · Ada Admin', $gmbh);

        $freelancer = $this->render('pdf.offer', 'minimal', [
            'name' => 'Studio Nord',
            'legal_form' => 'freiberufler',
            'managing_director' => 'Nora Frei',
            'address' => 'Markt 4',
            'postal_code' => '50667',
            'city' => 'Köln',
        ]);
        $this->assertStringContainsString('Nora Frei · Markt 4 · 50667 Köln', $freelancer);
        $this->assertStringNotContainsString('Studio Nord · Nora Frei', $freelancer);

        $modern = $this->render('pdf.invoice', 'modern', [
            'name' => 'Seiten GmbH',
            'legal_form' => 'gmbh',
            'managing_director' => 'Ada Admin',
            'address' => 'Hauptstraße 1',
            'postal_code' => '10115',
            'city' => 'Berlin',
        ]);
        $this->assertStringNotContainsString('Hauptstraße 1 · 10115 Berlin', $modern);
    }

    private function render(string $view, string $template, array $company): string
    {
        $company = Company::create($company + [
            'email' => uniqid('return-', true).'@example.com',
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
            'number' => 'RE-1',
            'status' => 'draft',
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'valid_until' => now()->addDays(30),
            'subtotal' => 100,
            'tax_rate' => 0.19,
            'tax_amount' => 19,
            'total' => 119,
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
                'settings' => [],
            ],
            'invoice' => $document,
            'offer' => $document,
            'company' => $company,
            'customer' => $customer,
            'settings' => [],
            'preview' => false,
        ])->render();
    }
}
