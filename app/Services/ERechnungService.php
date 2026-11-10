<?php

namespace App\Services;

use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Models\InvoiceLayout;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use horstoeko\zugferd\codelists\ZugferdCountryCodes;
use horstoeko\zugferd\codelists\ZugferdDutyTaxFeeCategories;
use horstoeko\zugferd\ZugferdDocumentBuilder;
use horstoeko\zugferd\ZugferdDocumentPdfMerger;
use horstoeko\zugferd\ZugferdProfiles;

class ERechnungService
{
    /**
     * Generate XRechnung (XML only) for an invoice
     */
    public function generateXRechnung(Invoice $invoice): string
    {
        $invoice->load(['customer', 'company', 'company.settings', 'items']);

        $profile = $this->resolveProfile($invoice);
        $document = ZugferdDocumentBuilder::CreateNew($profile);

        // Build the document
        $this->buildDocument($document, $invoice, $profile);

        // Get XML content
        $xml = $document->getContent();

        return $xml;
    }

    /**
     * Generate ZUGFeRD (PDF with embedded XML) for an invoice
     */
    public function generateZugferd(Invoice $invoice): string
    {
        $invoice->load(['customer', 'company', 'company.settings', 'items', 'layout', 'user']);

        // Get layout - either assigned to invoice or company default
        $layout = $invoice->layout;
        if (! $layout) {
            $layout = InvoiceLayout::forCompany($invoice->company_id)
                ->where('is_default', true)
                ->first();
        }

        // If no layout exists, create a minimal default layout
        if (! $layout) {
            $layout = $this->getDefaultLayout();
        }

        // Generate the PDF first using the same view as regular invoice PDFs
        $html = view('pdf.invoice', [
            'layout' => $layout,
            'invoice' => $invoice,
            'company' => $invoice->company,
            'customer' => $invoice->customer,
        ])->render();

        $pdf = PDF::loadHTML($html)
            ->setPaper('a4')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => false, // Prevent SSRF via attacker-controlled URLs in HTML
                'isHtml5ParserEnabled' => true,
            ]);

        $pdfContent = $pdf->output();

        // Generate XML
        $profile = $this->resolveProfile($invoice);
        $document = ZugferdDocumentBuilder::CreateNew($profile);
        $this->buildDocument($document, $invoice, $profile);
        $xml = $document->getContent();

        // Merge PDF and XML using ZUGFeRD library
        $pdfMerger = new ZugferdDocumentPdfMerger($xml, $pdfContent);
        $pdfMerger->generateDocument();
        $zugferdPdf = $pdfMerger->downloadString();

        return $zugferdPdf;
    }

    /**
     * Build the ZUGFeRD/XRechnung document
     */
    private function buildDocument(ZugferdDocumentBuilder $document, Invoice $invoice, int $profile): void
    {
        $company = $invoice->company;
        $customer = $invoice->customer;
        $isXRechnung = in_array($profile, [
            ZugferdProfiles::PROFILE_XRECHNUNG,
            ZugferdProfiles::PROFILE_XRECHNUNG_2,
            ZugferdProfiles::PROFILE_XRECHNUNG_2_1,
            ZugferdProfiles::PROFILE_XRECHNUNG_2_2,
            ZugferdProfiles::PROFILE_XRECHNUNG_2_3,
            ZugferdProfiles::PROFILE_XRECHNUNG_3,
        ], true);

        // The whole invoice shares one VAT category derived from its regime
        // (lines, allowances and the document breakdown must agree —
        // BR-S-*/BR-AE-* category-consistency rules).
        $taxCategory = $this->taxCategoryFor($invoice);
        $isStandardRegime = $taxCategory === ZugferdDutyTaxFeeCategories::STANDARD_RATE;

        // Document header
        $document->setDocumentInformation(
            $invoice->number ?? 'DRAFT-'.$invoice->id,
            $this->typeCodeFor($invoice),
            $invoice->issue_date ?? new \DateTime,
            $company->getSetting('currency') ?? 'EUR'
        );

        // BT-23 business process. XRechnung expects one; the Peppol billing
        // process is the documented default.
        if ($isXRechnung) {
            $document->setDocumentBusinessProcess(
                $company->getSetting('business_process_id') ?: 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0'
            );
        }

        // BT-10 Buyer Reference. For public-sector customers this is the
        // Leitweg-ID; XRechnung requires BT-10 for every invoice (BR-DE-15),
        // so B2B falls back to the customer number.
        $buyerReference = $customer->leitweg_id
            ?: ($isXRechnung ? ($customer->number ?: $invoice->number) : null);
        if (! empty($buyerReference)) {
            $document->setDocumentBuyerReference($buyerReference);
        }

        // Seller (Company) information
        $document->setDocumentSeller(
            $company->name,
            $company->commercial_register ?? null
        );

        // BT-31/BT-32: VAT id ('VA') and Steuernummer ('FC') belong in the
        // tax registration, not in a GlobalID (0088 would mean a GLN).
        if (! empty($company->vat_number)) {
            $document->addDocumentSellerTaxRegistration('VA', $company->vat_number);
        }
        if (! empty($company->tax_number)) {
            $document->addDocumentSellerTaxRegistration('FC', $company->tax_number);
        }

        $document->setDocumentSellerAddress(
            $company->address ?? '',
            '',
            '',
            $company->postal_code ?? '',
            $company->city ?? '',
            $this->getCountryCode($company->country ?? 'Deutschland')
        );

        // BG-6 seller contact: XRechnung requires name, phone AND email
        // (BR-DE-5/6/7) — an empty name fails validation.
        $document->setDocumentSellerContact(
            $company->managing_director ?: $company->name,
            '',
            $company->phone ?? '',
            '',
            $company->email ?? ''
        );

        // BT-34 seller electronic address (scheme EM = email unless the
        // company configured a Peppol/other address in the settings).
        $sellerElectronicAddress = $company->getSetting('electronic_address') ?: $company->email;
        if (! empty($sellerElectronicAddress)) {
            $document->setDocumentSellerCommunication(
                $company->getSetting('electronic_address_scheme') ?: 'EM',
                $sellerElectronicAddress
            );
        }

        // Buyer (Customer) information
        $document->setDocumentBuyer(
            $customer->company_name ?? $customer->name,
            $customer->number ?: null
        );

        if (! empty($customer->vat_number)) {
            $document->addDocumentBuyerTaxRegistration('VA', $customer->vat_number);
        }

        $document->setDocumentBuyerAddress(
            $customer->address ?? '',
            '',
            '',
            $customer->postal_code ?? '',
            $customer->city ?? '',
            $this->getCountryCode($customer->country ?? 'Deutschland')
        );

        $document->setDocumentBuyerContact(
            $customer->contact_person ?? $customer->name ?? '',
            '',
            $customer->phone ?? '',
            '',
            $customer->email ?? ''
        );

        // BT-49 buyer electronic address.
        if (! empty($customer->email)) {
            $document->setDocumentBuyerCommunication('EM', $customer->email);
        }

        // Payment terms
        if ($invoice->due_date) {
            $document->addDocumentPaymentTerm(
                'Zahlbar bis '.$invoice->due_date->format('d.m.Y'),
                $invoice->due_date
            );
        }

        // Line items. Abzug lines (negative net) are EN 16931 document
        // allowances (BG-20), not negative product quantities.
        $allowanceTotal = 0.0;
        $lineTotalSum = 0.0;
        $position = 0;

        foreach ($invoice->items as $index => $item) {
            $itemNet = (float) ($item->total ?? ($item->quantity * $item->unit_price));
            $isAbzug = (float) $item->unit_price < 0 || $itemNet < 0;

            if ($isAbzug) {
                $amount = abs($itemNet);
                $allowanceTotal += $amount;
                $description = $item->description ?? 'Nachlass';
                $reason = explode("\n", $description, 2)[0];

                $document->addDocumentAllowanceCharge(
                    $amount,
                    false,
                    $taxCategory,
                    'VAT',
                    $isStandardRegime ? $this->taxPercent($item->tax_rate ?? $invoice->tax_rate) : 0.0,
                    null,
                    null,
                    null,
                    null,
                    null,
                    '95',
                    $reason
                );

                continue;
            }

            $position++;
            $lineTotal = $item->quantity * $item->unit_price;
            $lineTotalSum += $lineTotal;

            $description = $item->description ?? 'Position '.($index + 1);
            $lines = explode("\n", $description, 2);
            $itemName = $lines[0];
            $itemDescription = $lines[1] ?? '';

            $document->addNewPosition($position.'');
            $document->setDocumentPositionProductDetails(
                $itemName,
                $itemDescription,
                $item->product_id ?? null
            );

            $document->setDocumentPositionNetPrice($item->unit_price ?? 0);
            $document->setDocumentPositionQuantity($item->quantity ?? 1, $this->unitCode($item->unit ?? null));

            $document->setDocumentPositionLineSummation($lineTotal);

            $document->addDocumentPositionTax(
                $taxCategory,
                'VAT',
                $isStandardRegime ? $this->taxPercent($item->tax_rate ?? $invoice->tax_rate) : 0.0
            );
        }

        $subtotal = $invoice->subtotal ?? 0;
        $taxAmount = $invoice->tax_amount ?? 0;
        $total = $invoice->total ?? 0;

        $document->setDocumentSummation(
            $total,
            $total,
            $lineTotalSum,
            0.0,
            $allowanceTotal,
            $subtotal,
            $taxAmount,
            0.0,
            0.0
        );

        // Add tax breakdown
        $vatBreakdown = $invoice->getVatBreakdown();
        if ($isStandardRegime && count($vatBreakdown) > 0) {
            foreach ($vatBreakdown as $data) {
                $document->addDocumentTax(
                    $taxCategory,
                    'VAT',
                    $data['net_amount'],
                    $data['tax_amount'],
                    $data['rate'] * 100
                );
            }
        } elseif ($isStandardRegime) {
            $taxRate = ($invoice->tax_rate ?? 0.19) * 100;
            $document->addDocumentTax(
                $taxCategory,
                'VAT',
                $subtotal,
                $taxAmount,
                $taxRate
            );
        } else {
            // Special regimes: category E/AE/K/G breakdowns MUST carry an
            // exemption reason (BR-E-10/AE-10/IC-10/G-10).
            $document->addDocumentTax(
                $taxCategory,
                'VAT',
                $subtotal,
                0,
                0,
                $this->exemptionReasonFor($invoice)
            );
        }

        // BG-16 payment instructions — mandatory for XRechnung (BR-DE-1).
        if ($company->bank_iban) {
            $document->addDocumentPaymentMean(
                '58', // SEPA credit transfer
                null,
                null,
                null,
                null,
                null,
                $company->bank_iban,
                $company->bank_account_holder ?: $company->name,
                null,
                $company->bank_bic ?? null
            );
        } else {
            // No bank details configured: still emit the payment-means group
            // so XRechnung validation does not fail outright.
            $document->addDocumentPaymentMean('30', 'Zahlung per Überweisung');
        }
    }

    /**
     * EN 16931 invoice type code (BT-3) for the invoice.
     */
    private function typeCodeFor(Invoice $invoice): string
    {
        return match ($invoice->invoice_type ?? Invoice::TYPE_STANDARD) {
            Invoice::TYPE_KORREKTURRECHNUNG => '384',
            Invoice::TYPE_ABSCHLAGSRECHNUNG => '326',
            default => '380',
        };
    }

    /**
     * The VAT category (BT-151/BT-118) shared by all lines of this invoice,
     * derived from its vat_regime.
     */
    private function taxCategoryFor(Invoice $invoice): string
    {
        return match ($invoice->vat_regime ?? 'standard') {
            'reverse_charge', 'reverse_charge_domestic' => ZugferdDutyTaxFeeCategories::VAT_REVERSE_CHARGE,
            'intra_community' => ZugferdDutyTaxFeeCategories::VAT_EXEMPT_FOR_EEA_INTRACOMMUNITY_SUPPLY_OF_GOODS_AND_SERVICES,
            'export' => ZugferdDutyTaxFeeCategories::FREE_EXPORT_ITEM_TAX_NOT_CHARGED,
            'small_business' => ZugferdDutyTaxFeeCategories::EXEMPT_FROM_TAX,
            'standard' => ZugferdDutyTaxFeeCategories::STANDARD_RATE,
            default => ZugferdDutyTaxFeeCategories::EXEMPT_FROM_TAX,
        };
    }

    /**
     * BT-120 exemption reason text for non-standard VAT regimes.
     */
    private function exemptionReasonFor(Invoice $invoice): string
    {
        return match ($invoice->vat_regime ?? 'standard') {
            'reverse_charge', 'reverse_charge_domestic' => 'Steuerschuldnerschaft des Leistungsempfängers (Reverse Charge, § 13b UStG)',
            'intra_community' => 'Steuerfreie innergemeinschaftliche Lieferung (§ 4 Nr. 1b UStG)',
            'export' => 'Steuerfreie Ausfuhrlieferung (§ 4 Nr. 1a UStG)',
            'small_business' => 'Kein Ausweis von Umsatzsteuer, da Kleinunternehmer gemäß § 19 UStG',
            default => 'Steuerbefreit',
        };
    }

    /**
     * Map the item's unit label to a UN/ECE Recommendation 20 code (BT-130).
     */
    private function unitCode(?string $unit): string
    {
        return match (mb_strtolower(trim((string) $unit))) {
            'std.', 'std', 'stunde', 'stunden', 'h' => 'HUR',
            'tag', 'tage' => 'DAY',
            'm²', 'qm' => 'MTK',
            'm³', 'cbm' => 'MTQ',
            'm', 'meter', 'lfm' => 'MTR',
            'km' => 'KMT',
            'kg' => 'KGM',
            't', 'tonne', 'tonnen' => 'TNE',
            'l', 'liter' => 'LTR',
            'pauschal', 'psch.', 'psch' => 'LS',
            'set' => 'SET',
            'paket', 'pkt.' => 'XPK',
            'karton' => 'CT',
            'prozent', '%' => 'P1',
            default => 'H87', // piece
        };
    }

    /**
     * Resolve the ZUGFeRD/XRechnung profile to use for this invoice.
     *
     * A customer with a Leitweg-ID is, by definition, a public-sector
     * recipient — those invoices must be XRechnung per § 4a E-RechV. The
     * company-level `zugferd_profile` setting only applies to B2B invoices.
     */
    private function resolveProfile(Invoice $invoice): int
    {
        // XRechnung 3.0 is the version mandated since 2024-02 — the older
        // 1.2/2.x customization IDs are rejected by ZRE/OZG-RE and Peppol.
        if (! empty($invoice->customer?->leitweg_id)) {
            return ZugferdProfiles::PROFILE_XRECHNUNG_3;
        }

        // `settings` is a HasMany of key/value rows, not an object — use
        // getSetting() to read a single key.
        $profile = $invoice->company?->getSetting('zugferd_profile') ?? 'EN16931';

        return match ($profile) {
            'MINIMUM' => ZugferdProfiles::PROFILE_MINIMUM,
            'BASIC' => ZugferdProfiles::PROFILE_BASIC,
            'EN16931' => ZugferdProfiles::PROFILE_EN16931,
            'EXTENDED' => ZugferdProfiles::PROFILE_EXTENDED,
            'XRECHNUNG' => ZugferdProfiles::PROFILE_XRECHNUNG_3,
            default => ZugferdProfiles::PROFILE_EN16931,
        };
    }

    /**
     * Get country code from country name
     */
    private function getCountryCode(string $country): string
    {
        return match (strtolower($country)) {
            'deutschland', 'germany' => ZugferdCountryCodes::GERMANY,
            'österreich', 'austria' => ZugferdCountryCodes::AUSTRIA,
            'schweiz', 'switzerland' => ZugferdCountryCodes::SWITZERLAND,
            'frankreich', 'france' => ZugferdCountryCodes::FRANCE,
            default => ZugferdCountryCodes::GERMANY,
        };
    }

    /**
     * Get default layout settings
     */
    private function getDefaultLayout(): object
    {
        return (object) [
            'settings' => [
                'colors' => [
                    'primary' => '#3b82f6',
                    'secondary' => '#1f2937',
                    'accent' => '#e5e7eb',
                    'text' => '#1f2937',
                ],
                'fonts' => [
                    'heading' => 'DejaVu Sans',
                    'body' => 'DejaVu Sans',
                    'size' => 'medium',
                ],
                'layout' => [
                    'margin_top' => 20,
                    'margin_right' => 20,
                    'margin_bottom' => 20,
                    'margin_left' => 20,
                    'header_height' => 120,
                    'footer_height' => 80,
                ],
                'branding' => [
                    'show_logo' => true,
                    'logo_position' => 'top-right',
                    'company_info_position' => 'top-left',
                    'show_header_line' => true,
                    'show_footer_line' => true,
                    'show_footer' => true,
                ],
                'content' => [
                    'show_company_address' => true,
                    'show_company_contact' => true,
                    'show_customer_number' => true,
                    'show_tax_number' => true,
                    'show_unit_column' => true,
                    'show_notes' => true,
                    'show_bank_details' => true,
                    'show_company_registration' => true,
                    'show_payment_terms' => true,
                    'show_item_images' => false,
                    'show_item_codes' => false,
                    'show_tax_breakdown' => false,
                ],
            ],
        ];
    }

    /**
     * Download XRechnung as XML file
     */
    public function downloadXRechnung(Invoice $invoice): array
    {
        $xml = $this->generateXRechnung($invoice);
        $filename = 'XRechnung_'.($invoice->number ?? 'DRAFT-'.$invoice->id).'.xml';

        return [
            'content' => $xml,
            'filename' => $filename,
            'mime_type' => 'application/xml',
        ];
    }

    /**
     * Download ZUGFeRD as PDF file
     */
    public function downloadZugferd(Invoice $invoice): array
    {
        $pdf = $this->generateZugferd($invoice);
        $filename = 'ZUGFeRD_'.($invoice->number ?? 'DRAFT-'.$invoice->id).'.pdf';

        return [
            'content' => $pdf,
            'filename' => $filename,
            'mime_type' => 'application/pdf',
        ];
    }

    /**
     * EN 16931 tax rates are percentages (19), while invoice items store
     * fractions (0.19).
     */
    private function taxPercent(?float $rate): float
    {
        $rate = (float) ($rate ?? 0.19);

        return $rate <= 1 ? $rate * 100 : $rate;
    }
}
