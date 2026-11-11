<?php

namespace Tests\Unit\Views;

use Tests\TestCase;

class EmailDocumentDetailsTest extends TestCase
{
    public function test_invoice_and_offer_emails_show_the_details_table_by_default(): void
    {
        $invoice = $this->renderInvoice('1');
        $offer = $this->renderOffer('1');

        $this->assertStringContainsString('Rechnungsnummer', $invoice);
        $this->assertStringContainsString('Fälligkeitsdatum', $invoice);
        $this->assertStringContainsString('148,75 €', $invoice);
        $this->assertStringContainsString('anbei erhalten Sie die Rechnung', $invoice);

        $this->assertStringContainsString('Angebotsnummer', $offer);
        $this->assertStringContainsString('Gültig bis', $offer);
        $this->assertStringContainsString('250,00 €', $offer);
        $this->assertStringContainsString('folgendes Angebot', $offer);
    }

    public function test_invoice_and_offer_emails_hide_the_details_table_when_the_setting_is_off(): void
    {
        $invoice = $this->renderInvoice('0');
        $offer = $this->renderOffer('0');

        $this->assertStringNotContainsString('Rechnungsnummer', $invoice);
        $this->assertStringNotContainsString('Fälligkeitsdatum', $invoice);
        $this->assertStringNotContainsString('148,75 €', $invoice);
        $this->assertStringContainsString('anbei erhalten Sie die Rechnung', $invoice);
        $this->assertStringContainsString('RE-2024-0001', $invoice);

        $this->assertStringNotContainsString('Angebotsnummer', $offer);
        $this->assertStringNotContainsString('Gültig bis', $offer);
        $this->assertStringNotContainsString('250,00 €', $offer);
        $this->assertStringContainsString('folgendes Angebot', $offer);
    }

    public function test_a_preview_company_without_settings_can_hide_the_table(): void
    {
        $html = view('emails.invoice-sent', [
            'invoice' => (object) [
                'number' => 'RE-2024-0001',
                'issue_date' => '2026-10-12',
                'due_date' => '2026-11-11',
                'total' => 148.75,
                'notes' => null,
                'customer' => (object) ['name' => 'Musterkunde GmbH'],
            ],
            'company' => (object) [
                'name' => 'Andona GmbH',
                'email' => null,
                'phone' => null,
                'website' => null,
                'address' => null,
                'postal_code' => null,
                'city' => null,
                'tax_number' => null,
                'vat_number' => null,
                'email_show_document_details' => '0',
            ],
        ])->render();

        $this->assertStringNotContainsString('Rechnungsnummer', $html);
        $this->assertStringContainsString('RE-2024-0001', $html);
    }

    public function test_a_custom_message_replaces_the_standard_letter(): void
    {
        $invoice = view('emails.invoice-sent', [
            'invoice' => (object) [
                'number' => 'RE-2024-0001',
                'issue_date' => '2026-10-12',
                'due_date' => '2026-11-11',
                'total' => 148.75,
                'notes' => 'Interne Notiz',
                'customer' => (object) ['name' => 'Musterkunde GmbH'],
            ],
            'company' => $this->company('0'),
            'customMessage' => "Guten Tag,\n\nbitte beachten Sie die geänderte Fassung.",
        ])->render();

        $offer = view('emails.offer-sent', [
            'offer' => (object) [
                'number' => 'AN-2026-0001',
                'issue_date' => '2026-10-12',
                'valid_until' => '2026-11-11',
                'total' => 250,
                'notes' => 'Interne Notiz',
                'customer' => (object) ['name' => 'Musterkunde GmbH'],
            ],
            'company' => $this->company('0'),
            'customMessage' => 'Bitte nur diese Angebotsfassung verwenden.',
        ])->render();

        $this->assertStringContainsString('bitte beachten Sie die geänderte Fassung.', $invoice);
        $this->assertStringNotContainsString('anbei erhalten Sie die Rechnung', $invoice);
        $this->assertStringNotContainsString('Interne Notiz', $invoice);
        $this->assertStringNotContainsString('Fälligkeitsdatum', $invoice);

        $this->assertStringContainsString('Bitte nur diese Angebotsfassung verwenden.', $offer);
        $this->assertStringNotContainsString('folgendes Angebot', $offer);
        $this->assertStringNotContainsString('Interne Notiz', $offer);
        $this->assertStringNotContainsString('Angebotsnummer', $offer);
    }

    private function renderInvoice(string $setting): string
    {
        return view('emails.invoice-sent', [
            'invoice' => (object) [
                'number' => 'RE-2024-0001',
                'issue_date' => '2026-10-12',
                'due_date' => '2026-11-11',
                'total' => 148.75,
                'notes' => null,
                'customer' => (object) ['name' => 'Musterkunde GmbH'],
            ],
            'company' => $this->company($setting),
        ])->render();
    }

    private function renderOffer(string $setting): string
    {
        return view('emails.offer-sent', [
            'offer' => (object) [
                'number' => 'AN-2026-0001',
                'issue_date' => '2026-10-12',
                'valid_until' => '2026-11-11',
                'total' => 250,
                'notes' => null,
                'customer' => (object) ['name' => 'Musterkunde GmbH'],
            ],
            'company' => $this->company($setting),
        ])->render();
    }

    private function company(string $setting): object
    {
        return new class($setting)
        {
            public string $name = 'Andona GmbH';

            public ?string $email = null;

            public ?string $phone = null;

            public ?string $website = null;

            public ?string $address = null;

            public ?string $postal_code = null;

            public ?string $city = null;

            public ?string $tax_number = null;

            public ?string $vat_number = null;

            public function __construct(private string $details) {}

            public function getSetting(string $key, mixed $default = null): mixed
            {
                return $key === 'email_show_document_details' ? $this->details : $default;
            }
        };
    }
}
