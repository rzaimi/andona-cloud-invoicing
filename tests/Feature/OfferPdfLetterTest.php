<?php

namespace Tests\Feature;

use App\Modules\Company\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OfferPdfLetterTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->company = Company::create([
            'name' => 'Kameri Abdichtung UG',
            'email' => 'angebot@example.com',
            'status' => 'active',
            'legal_form' => 'ug',
            'address' => 'Mühlstr. 14',
            'postal_code' => '63688',
            'city' => 'Gedern',
        ]);
    }

    public function test_offer_letter_uses_the_request_intro_closing_and_acceptance_block(): void
    {
        foreach (['minimal', 'professional', 'modern'] as $template) {
            $html = $this->renderOffer($template, 'standard');

            $this->assertStringContainsString('vielen Dank für Ihre Anfrage und dem damit verbundenen Interesse an unserem Unternehmen.', $html);
            $this->assertStringContainsString('Wir möchten Ihnen folgendes Angebot unterbreiten:', $html);
            $this->assertStringContainsString('Es gelten unsere allgemeinen Geschäftsbedingungen.', $html);
            $this->assertStringContainsString('Wenn Sie weitere Informationen benötigen, zögern Sie nicht, uns zu kontaktieren. Wir beraten Sie gerne.', $html);
            $this->assertStringContainsString('Vielen Dank für Ihren Auftrag!', $html);
            $this->assertStringContainsString('ANGEBOTSBESTÄTIGUNG', $html);
            $this->assertStringContainsString('Hiermit bestätige ich als Auftraggeber das Angebot und erteile den Auftrag.', $html);
            $this->assertStringContainsString('Unterschrift Auftraggeber', $html);
            $this->assertStringNotContainsString('Rechnung nach § 13b UStG.', $html);
        }
    }

    public function test_domestic_reverse_charge_notice_is_printed_only_for_that_regime(): void
    {
        $domestic = $this->renderOffer('modern', 'reverse_charge_domestic');
        $this->assertStringContainsString(
            'Rechnung nach § 13b UStG. Die Umsatzsteuer für diese umsatzsteuerpflichtige Leistung schuldet der Auftraggeber.',
            $domestic
        );

        $smallBusiness = $this->renderOffer('modern', 'small_business');
        $this->assertStringContainsString('Gemäß § 19 UStG wird keine Umsatzsteuer berechnet.', $smallBusiness);
        $this->assertStringNotContainsString('Rechnung nach § 13b UStG.', $smallBusiness);

        $foreign = $this->renderOffer('modern', 'reverse_charge');
        $this->assertStringContainsString('Steuerschuldnerschaft des Leistungsempfängers gemäß § 13b UStG (Reverse Charge).', $foreign);
        $this->assertStringNotContainsString('schuldet der Auftraggeber.', $foreign);
    }

    public function test_custom_offer_notes_and_terms_stay_on_the_letter(): void
    {
        $html = $this->renderOffer('modern', 'standard', 'Bitte um Rückruf.', 'Zahlung binnen 14 Tagen.');

        $this->assertStringContainsString('Bitte um Rückruf.', $html);
        $this->assertStringContainsString('Zahlung binnen 14 Tagen.', $html);
    }

    private function renderOffer(string $template, string $vatRegime, ?string $notes = null, ?string $terms = null): string
    {
        $customer = (object) [
            'name' => 'Polygon Deutschland GmbH',
            'contact_person' => null,
            'address' => 'Postfach 20 60',
            'postal_code' => '57450',
            'city' => 'Olpe',
            'country' => 'Deutschland',
            'number' => '4',
            'customer_type' => 'business',
        ];

        $offer = (object) [
            'number' => 'ANG-20251003',
            'status' => 'sent',
            'issue_date' => now(),
            'valid_until' => now()->addDays(30),
            'subtotal' => 18019.30,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => 18019.30,
            'notes' => $notes,
            'terms_conditions' => $terms,
            'bauvorhaben' => null,
            'vat_regime' => $vatRegime,
            'title' => null,
            'customer' => $customer,
            'items' => collect([
                (object) [
                    'id' => 'item-1',
                    'description' => 'Dach provisorisch abdichten.',
                    'quantity' => 216,
                    'unit' => 'Std.',
                    'unit_price' => 55,
                    'tax_rate' => 0,
                    'total' => 11880,
                    'discount_type' => null,
                    'discount_value' => null,
                    'discount_amount' => 0,
                ],
            ]),
        ];

        return view('pdf.offer', [
            'layout' => (object) [
                'name' => 'Modern',
                'template' => $template,
                'settings' => [
                    'colors' => [
                        'primary' => '#0e7490',
                        'accent' => '#5eead4',
                        'text' => '#0f172a',
                        'secondary' => '#64748b',
                    ],
                ],
            ],
            'offer' => $offer,
            'company' => $this->company,
            'customer' => $customer,
            'settings' => [],
            'preview' => false,
        ])->render();
    }
}
