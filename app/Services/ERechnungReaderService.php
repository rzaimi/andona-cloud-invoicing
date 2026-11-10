<?php

namespace App\Services;

use horstoeko\zugferd\ZugferdDocumentPdfReader;
use horstoeko\zugferd\ZugferdDocumentReader;
use horstoeko\zugferdublbridge\XmlConverterUblToCii;
use InvalidArgumentException;

/**
 * Parses received e-invoices (E-Rechnungen) into a display-friendly array.
 *
 * Accepts every format a German business must be able to receive since the
 * B2B mandate (§ 14 UStG): CII XML (ZUGFeRD/Factur-X/XRechnung CII), UBL XML
 * (XRechnung UBL, converted through the UBL bridge) and PDFs with embedded
 * XML (ZUGFeRD/Factur-X hybrid).
 */
class ERechnungReaderService
{
    private const TYPE_CODES = [
        '380' => 'Rechnung',
        '381' => 'Gutschrift',
        '384' => 'Rechnungskorrektur',
        '326' => 'Teilrechnung (Abschlag)',
        '389' => 'Selbstfakturierte Rechnung',
        '875' => 'Teilrechnung Bauleistung',
        '876' => 'Teilschlussrechnung Bauleistung',
        '877' => 'Schlussrechnung Bauleistung',
    ];

    private const TAX_CATEGORIES = [
        'S' => 'Normalsatz',
        'Z' => 'Nullsatz',
        'E' => 'Steuerbefreit',
        'AE' => 'Umkehr der Steuerschuld (Reverse Charge)',
        'K' => 'Innergemeinschaftliche Lieferung',
        'G' => 'Export (steuerfrei)',
        'O' => 'Nicht steuerbar',
        'L' => 'Kanarische Inseln (IGIC)',
        'M' => 'Ceuta/Melilla (IPSI)',
    ];

    private const PAYMENT_MEANS = [
        '30' => 'Überweisung',
        '58' => 'SEPA-Überweisung',
        '59' => 'SEPA-Lastschrift',
        '49' => 'Lastschrift',
        '48' => 'Kartenzahlung',
        '54' => 'Kreditkarte',
        '55' => 'Debitkarte',
        '10' => 'Bar',
        '20' => 'Scheck',
        '68' => 'Online-Zahlungsdienst',
        '97' => 'Verrechnung',
        '1' => 'Nicht definiert',
    ];

    /**
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException when the file is not a readable e-invoice
     */
    public function parse(string $content, string $filename): array
    {
        $reader = $this->makeReader($content, $filename);

        return $this->extract($reader);
    }

    private function makeReader(string $content, string $filename): ZugferdDocumentReader
    {
        if (str_starts_with($content, '%PDF')) {
            try {
                return ZugferdDocumentPdfReader::readAndGuessFromContent($content);
            } catch (\Throwable $e) {
                throw new InvalidArgumentException(
                    'Das PDF enthält keine lesbare E-Rechnung. Nur ZUGFeRD-/Factur-X-PDFs tragen eingebettetes Rechnungs-XML — ein normales PDF ist keine E-Rechnung.'
                );
            }
        }

        $trimmed = ltrim($content, "\xEF\xBB\xBF \t\r\n");
        if (! str_starts_with($trimmed, '<')) {
            throw new InvalidArgumentException(
                'Das Format wurde nicht erkannt. Unterstützt werden XML-Dateien (XRechnung, ZUGFeRD/Factur-X) und PDFs mit eingebettetem XML.'
            );
        }

        // XRechnung comes in two syntaxes: UBL (root <Invoice>/<CreditNote>
        // in the OASIS namespace) and CII (<CrossIndustryInvoice>). The
        // reader speaks CII, so UBL goes through the bridge first.
        if ($this->isUbl($trimmed)) {
            try {
                $trimmed = XmlConverterUblToCii::fromString($trimmed)->convert()->saveXmlString();
            } catch (\Throwable $e) {
                throw new InvalidArgumentException(
                    'Die UBL-XRechnung konnte nicht konvertiert werden: '.$e->getMessage()
                );
            }
        }

        try {
            return ZugferdDocumentReader::readAndGuessFromContent($trimmed);
        } catch (\Throwable $e) {
            throw new InvalidArgumentException(
                'Die XML-Datei konnte nicht als E-Rechnung gelesen werden. Erwartet wird eine EN-16931-konforme Rechnung (XRechnung oder ZUGFeRD/Factur-X).'
            );
        }
    }

    private function isUbl(string $xml): bool
    {
        return str_contains($xml, 'urn:oasis:names:specification:ubl');
    }

    /**
     * @return array<string, mixed>
     */
    private function extract(ZugferdDocumentReader $reader): array
    {
        $profileParams = $reader->getProfileDefinitionParameter('contextparameter');

        $reader->getDocumentInformation($number, $typeCode, $date, $currency, $taxCurrency, $docName, $language, $period);
        $reader->getDocumentBuyerReference($buyerReference);
        $reader->getDocumentNotes($notes);

        return [
            'profile' => [
                'context' => $profileParams,
                'label' => $this->profileLabel($profileParams),
            ],
            'document' => [
                'number' => $number,
                'type_code' => $typeCode,
                'type_label' => self::TYPE_CODES[$typeCode] ?? 'Dokumenttyp '.$typeCode,
                'date' => $date?->format('Y-m-d'),
                'currency' => $currency,
                'buyer_reference' => $buyerReference ?: null,
                'notes' => collect($notes ?? [])
                    ->map(fn ($note) => is_array($note) ? ($note['content'] ?? '') : (string) $note)
                    ->filter()
                    ->values()
                    ->all(),
            ],
            'seller' => $this->extractSeller($reader),
            'buyer' => $this->extractBuyer($reader),
            'positions' => $this->extractPositions($reader),
            'allowances' => $this->extractAllowances($reader),
            'taxes' => $this->extractTaxes($reader),
            'totals' => $this->extractTotals($reader),
            'payment' => $this->extractPayment($reader),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function extractSeller(ZugferdDocumentReader $reader): array
    {
        $reader->getDocumentSeller($name, $ids, $description);
        $reader->getDocumentSellerAddress($line1, $line2, $line3, $postCode, $city, $country, $subDivision);
        $reader->getDocumentSellerTaxRegistration($taxReg);

        $contact = null;
        if ($reader->firstDocumentSellerContact()) {
            $reader->getDocumentSellerContact($contactName, $contactDept, $contactPhone, $contactFax, $contactEmail);
            $contact = array_filter([
                'name' => $contactName,
                'phone' => $contactPhone,
                'email' => $contactEmail,
            ]);
        }

        return [
            'name' => $name,
            'address' => $this->formatAddress($line1, $line2, $line3, $postCode, $city, $country),
            'tax_registrations' => $this->formatTaxRegistrations($taxReg ?? []),
            'contact' => $contact ?: null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function extractBuyer(ZugferdDocumentReader $reader): array
    {
        $reader->getDocumentBuyer($name, $ids, $description);
        $reader->getDocumentBuyerAddress($line1, $line2, $line3, $postCode, $city, $country, $subDivision);
        $reader->getDocumentBuyerTaxRegistration($taxReg);

        return [
            'name' => $name,
            'address' => $this->formatAddress($line1, $line2, $line3, $postCode, $city, $country),
            'tax_registrations' => $this->formatTaxRegistrations($taxReg ?? []),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function extractPositions(ZugferdDocumentReader $reader): array
    {
        $positions = [];

        if (! $reader->firstDocumentPosition()) {
            return $positions;
        }

        do {
            $reader->getDocumentPositionGenerals($lineId, $lineStatus, $lineStatusReason);
            $reader->getDocumentPositionProductDetails($name, $description, $sellerId, $buyerId, $globalIdType, $globalId);
            $reader->getDocumentPositionNetPrice($netPrice, $basisQuantity, $basisUnit);
            $reader->getDocumentPositionQuantity($quantity, $unitCode, $chargeFree, $chargeFreeUnit, $package, $packageUnit);
            $reader->getDocumentPositionLineSummationSimple($lineTotal);

            $taxRate = null;
            $taxCategory = null;
            if ($reader->firstDocumentPositionTax()) {
                $reader->getDocumentPositionTax($taxCategory, $taxType, $taxRate, $taxCalculated, $exemptionReason, $exemptionReasonCode);
            }

            $positions[] = [
                'line_id' => $lineId,
                'name' => $name,
                'description' => $description ?: null,
                'quantity' => $quantity,
                'unit' => $unitCode,
                'unit_price' => $netPrice,
                'line_total' => $lineTotal,
                'tax_rate' => $taxRate,
                'tax_category' => $taxCategory,
            ];
        } while ($reader->nextDocumentPosition());

        return $positions;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function extractAllowances(ZugferdDocumentReader $reader): array
    {
        $allowances = [];

        if (! $reader->firstDocumentAllowanceCharge()) {
            return $allowances;
        }

        do {
            $reader->getDocumentAllowanceCharge($amount, $isCharge, $taxCategory, $taxType, $taxRate, $sequence, $calcPercent, $basisAmount, $basisQty, $basisUnit, $reasonCode, $reason);

            $allowances[] = [
                'amount' => $amount,
                'is_charge' => (bool) $isCharge,
                'reason' => $reason ?: ($isCharge ? 'Zuschlag' : 'Nachlass'),
                'tax_rate' => $taxRate,
            ];
        } while ($reader->nextDocumentAllowanceCharge());

        return $allowances;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function extractTaxes(ZugferdDocumentReader $reader): array
    {
        $taxes = [];

        if (! $reader->firstDocumentTax()) {
            return $taxes;
        }

        do {
            $reader->getDocumentTax($categoryCode, $typeCode, $basisAmount, $calculatedAmount, $rate, $exemptionReason, $exemptionReasonCode, $lineTotalBasis, $allowanceBasis, $taxPointDate, $dueDateTypeCode);

            $taxes[] = [
                'category' => $categoryCode,
                'category_label' => self::TAX_CATEGORIES[$categoryCode] ?? $categoryCode,
                'rate' => $rate,
                'basis' => $basisAmount,
                'amount' => $calculatedAmount,
                'exemption_reason' => $exemptionReason ?: null,
            ];
        } while ($reader->nextDocumentTax());

        return $taxes;
    }

    /**
     * @return array<string, float|null>
     */
    private function extractTotals(ZugferdDocumentReader $reader): array
    {
        $reader->getDocumentSummation($grandTotal, $duePayable, $lineTotal, $chargeTotal, $allowanceTotal, $taxBasisTotal, $taxTotal, $rounding, $prepaid);

        return [
            'line_total' => $lineTotal,
            'allowance_total' => $allowanceTotal,
            'charge_total' => $chargeTotal,
            'net_total' => $taxBasisTotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
            'prepaid' => $prepaid,
            'due_payable' => $duePayable,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function extractPayment(ZugferdDocumentReader $reader): array
    {
        $means = [];
        if ($reader->firstGetDocumentPaymentMeans()) {
            do {
                $reader->getDocumentPaymentMeans($typeCode, $information, $cardType, $cardId, $cardHolder, $buyerIban, $payeeIban, $payeeAccountName, $payeePropId, $payeeBic);
                $means[] = array_filter([
                    'type' => self::PAYMENT_MEANS[$typeCode] ?? ($typeCode ? 'Zahlungsart '.$typeCode : null),
                    'iban' => $payeeIban ?: $buyerIban,
                    'bic' => $payeeBic,
                    'account_name' => $payeeAccountName,
                ]);
            } while ($reader->nextGetDocumentPaymentMeans());
        }

        $terms = [];
        if ($reader->firstDocumentPaymentTerms()) {
            do {
                $reader->getDocumentPaymentTerm($description, $dueDate, $mandateId);
                $terms[] = array_filter([
                    'description' => $description,
                    'due_date' => $dueDate?->format('Y-m-d'),
                ]);
            } while ($reader->nextDocumentPaymentTerms());
        }

        return [
            'means' => array_values(array_filter($means)),
            'terms' => array_values(array_filter($terms)),
        ];
    }

    private function profileLabel(?string $context): string
    {
        if (! $context) {
            return 'Unbekanntes Profil';
        }

        if (str_contains($context, 'xrechnung_3')) {
            return 'XRechnung 3.0';
        }
        if (str_contains($context, 'xrechnung_2')) {
            return 'XRechnung 2.x (veraltet)';
        }
        if (str_contains($context, 'xrechnung_1')) {
            return 'XRechnung 1.2 (veraltet)';
        }
        if (str_contains($context, 'extended')) {
            return 'ZUGFeRD/Factur-X EXTENDED';
        }
        if (str_contains($context, 'en16931')) {
            return 'ZUGFeRD/Factur-X EN 16931 (COMFORT)';
        }
        if (str_contains($context, 'basicwl')) {
            return 'ZUGFeRD/Factur-X BASIC WL';
        }
        if (str_contains($context, 'basic')) {
            return 'ZUGFeRD/Factur-X BASIC';
        }
        if (str_contains($context, 'minimum')) {
            return 'ZUGFeRD/Factur-X MINIMUM';
        }

        return $context;
    }

    private function formatAddress(?string $line1, ?string $line2, ?string $line3, ?string $postCode, ?string $city, ?string $country): string
    {
        $parts = array_filter([
            trim(implode(' ', array_filter([$line1, $line2, $line3]))),
            trim(implode(' ', array_filter([$postCode, $city]))),
            $country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * @param  array<int, array<string, string>>  $taxReg
     * @return list<array{type: string, number: string}>
     */
    private function formatTaxRegistrations(array $taxReg): array
    {
        $result = [];

        foreach ($taxReg as $scheme => $number) {
            // The reader returns either [scheme => number] or a list of
            // ['schemeid' => ..., 'id' => ...] entries depending on version.
            if (is_array($number)) {
                $scheme = $number['schemeid'] ?? $number['type'] ?? '';
                $number = $number['id'] ?? '';
            }

            if ($number === '' || $number === null) {
                continue;
            }

            $result[] = [
                'type' => match ((string) $scheme) {
                    'VA' => 'USt-IdNr.',
                    'FC' => 'Steuernummer',
                    default => (string) $scheme,
                },
                'number' => (string) $number,
            ];
        }

        return $result;
    }
}
