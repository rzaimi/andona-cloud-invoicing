<?php

namespace Database\Seeders;

use App\Modules\Company\Models\Company;
use App\Modules\Company\Models\CompanySetting;
use Illuminate\Database\Seeder;

class CompanySettingsSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        $defaultSettings = [
            [
                'key' => 'currency',
                'value' => 'EUR',
                'type' => 'string',
                'description' => 'Währung für Rechnungen und Angebote',
            ],
            [
                'key' => 'tax_rate',
                'value' => '0.19',
                'type' => 'decimal',
                'description' => 'Standard Mehrwertsteuersatz (19%)',
            ],
            [
                'key' => 'reduced_tax_rate',
                'value' => '0.07',
                'type' => 'decimal',
                'description' => 'Ermäßigter Mehrwertsteuersatz (7%)',
            ],
            [
                'key' => 'invoice_prefix',
                'value' => 'RE-',
                'type' => 'string',
                'description' => 'Präfix für Rechnungsnummern',
            ],
            [
                'key' => 'offer_prefix',
                'value' => 'AN-',
                'type' => 'string',
                'description' => 'Präfix für Angebotsnummern',
            ],
            [
                'key' => 'date_format',
                'value' => 'd.m.Y',
                'type' => 'string',
                'description' => 'Datumsformat (deutsch)',
            ],
            [
                'key' => 'payment_terms',
                'value' => '14',
                'type' => 'integer',
                'description' => 'Zahlungsziel in Tagen',
            ],
            [
                'key' => 'language',
                'value' => 'de',
                'type' => 'string',
                'description' => 'Sprache der Anwendung',
            ],
            [
                'key' => 'timezone',
                'value' => 'Europe/Berlin',
                'type' => 'string',
                'description' => 'Zeitzone',
            ],
            [
                'key' => 'decimal_separator',
                'value' => ',',
                'type' => 'string',
                'description' => 'Dezimaltrennzeichen',
            ],
            [
                'key' => 'thousands_separator',
                'value' => '.',
                'type' => 'string',
                'description' => 'Tausendertrennzeichen',
            ],
            [
                'key' => 'invoice_footer',
                'value' => 'Vielen Dank für Ihr Vertrauen! Bei Fragen stehen wir Ihnen gerne zur Verfügung.',
                'type' => 'string',
                'description' => 'Standard-Fußzeile für Rechnungen',
            ],
            [
                'key' => 'offer_footer',
                'value' => 'Wir freuen uns auf Ihre Rückmeldung und stehen für Rückfragen gerne zur Verfügung.',
                'type' => 'string',
                'description' => 'Standard-Fußzeile für Angebote',
            ],
            [
                'key' => 'payment_methods',
                'value' => '["Überweisung", "SEPA-Lastschrift", "PayPal", "Kreditkarte"]',
                'type' => 'json',
                'description' => 'Verfügbare Zahlungsmethoden',
            ],
            [
                'key' => 'offer_validity_days',
                'value' => '30',
                'type' => 'integer',
                'description' => 'Standard-Gültigkeitsdauer für Angebote in Tagen',
            ],
            [
                'key' => 'invoice_terms',
                'value' => 'Zahlbar innerhalb von 14 Tagen ohne Abzug. Bei Zahlungsverzug werden Verzugszinsen in Höhe von 9 Prozentpunkten über dem Basiszinssatz berechnet.',
                'type' => 'string',
                'description' => 'Standard-Zahlungsbedingungen für Rechnungen',
            ],
            [
                'key' => 'offer_terms',
                'value' => 'Dieses Angebot ist freibleibend und 30 Tage gültig. Alle Preise verstehen sich zzgl. der gesetzlichen Mehrwertsteuer.',
                'type' => 'string',
                'description' => 'Standard-Bedingungen für Angebote',
            ],
            [
                'key' => 'default_units',
                'value' => '["Stk.", "Std.", "Tag", "Monat", "Jahr", "m", "m²", "m³", "kg", "l"]',
                'type' => 'json',
                'description' => 'Standard-Einheiten für Positionen',
            ],
            [
                'key' => 'email_signature',
                'value' => 'Mit freundlichen Grüßen\nIhr Team',
                'type' => 'string',
                'description' => 'E-Mail-Signatur',
            ],
            [
                'key' => 'reminder_text',
                'value' => 'Hiermit möchten wir Sie freundlich daran erinnern, dass die oben genannte Rechnung noch offen ist.',
                'type' => 'string',
                'description' => 'Standard-Text für Zahlungserinnerungen',
            ],
        ];

        foreach ($companies as $company) {
            foreach ($defaultSettings as $setting) {
                CompanySetting::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'key' => $setting['key'],
                    ],
                    [
                        'value' => $setting['value'],
                        'type' => $setting['type'],
                        'description' => $setting['description'],
                    ]
                );
            }
        }
    }
}
