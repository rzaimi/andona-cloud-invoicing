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
        $this->buildDocument($document, $invoice);

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
        $this->buildDocument($document, $invoice);
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
    private function buildDocument(ZugferdDocumentBuilder $document, Invoice $invoice): void
    {
        $company = $invoice->company;
        $customer = $invoice->customer;
        $settings = $company->settings;

        // Document header
        $document->setDocumentInformation(
            $invoice->number ?? 'DRAFT-'.$invoice->id,
            '380', // Invoice type code
            $invoice->issue_date ?? new \DateTime,
            $settings->currency ?? 'EUR'
        );

        // BT-10 Buyer Reference / Routing ID. Mandatory for XRechnung to
        // German public-sector customers (Leitweg-ID). Populated from the
        // customer record when present.
        if (! empty($customer->leitweg_id)) {
            $document->setDocumentBuyerReference($customer->leitweg_id);
        }

        // Seller (Company) information
        $document->setDocumentSeller(
            $company->name,
            $company->commercial_register ?? null
        );

        $document->addDocumentSellerGlobalId(
            $company->vat_number ?? '',
            '0088' // VAT registration number scheme
        );

        $document->setDocumentSellerAddress(
            $company->address ?? '',
            '',
            '',
            $company->postal_code ?? '',
            $company->city ?? '',
            $this->getCountryCode($company->country ?? 'Deutschland')
        );

        $document->setDocumentSellerContact(
            '',
            '',
            $company->phone ?? '',
            '',
            $company->email ?? ''
        );

        // Buyer (Customer) information
        $document->setDocumentBuyer(
            $customer->company_name ?? $customer->name,
            $customer->commercial_register ?? null
        );

        if ($customer->vat_number) {
            $document->addDocumentBuyerGlobalId(
                $customer->vat_number,
                '0088'
            );
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
            '',
            '',
            $customer->phone ?? '',
            '',
            $customer->email ?? ''
        );

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
                    ZugferdDutyTaxFeeCategories::STANDARD_RATE,
                    'VAT',
                    $this->taxPercent($item->tax_rate ?? $invoice->tax_rate),
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

            $document->setDocumentPositionGrossPrice($item->unit_price ?? 0);
            $document->setDocumentPositionNetPrice($item->unit_price ?? 0);
            $document->setDocumentPositionQuantity($item->quantity ?? 1, 'C62');

            $document->setDocumentPositionLineSummation($lineTotal);

            $taxRate = $item->tax_rate ?? 19.0;
            $document->addDocumentPositionTax(
                ZugferdDutyTaxFeeCategories::STANDARD_RATE,
                'VAT',
                $taxRate
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
        if (count($vatBreakdown) > 0) {
            foreach ($vatBreakdown as $data) {
                $document->addDocumentTax(
                    ZugferdDutyTaxFeeCategories::STANDARD_RATE,
                    'VAT',
                    $data['net_amount'],
                    $data['tax_amount'],
                    $data['rate'] * 100
                );
            }
        } elseif (($invoice->vat_regime ?? 'standard') === 'standard') {
            $taxRate = ($invoice->tax_rate ?? 0.19) * 100;
            $document->addDocumentTax(
                ZugferdDutyTaxFeeCategories::STANDARD_RATE,
                'VAT',
                $subtotal,
                $taxAmount,
                $taxRate
            );
        } else {
            // Special regimes (tax exempt or reverse charge)
            $category = match ($invoice->vat_regime) {
                'reverse_charge' => ZugferdDutyTaxFeeCategories::VAT_REVERSE_CHARGE,
                'reverse_charge_domestic' => ZugferdDutyTaxFeeCategories::VAT_REVERSE_CHARGE,
                'intra_community' => ZugferdDutyTaxFeeCategories::VAT_EXEMPT_FOR_EEA_INTRACOMMUNITY_SUPPLY_OF_GOODS_AND_SERVICES,
                'export' => ZugferdDutyTaxFeeCategories::FREE_EXPORT_ITEM_TAX_NOT_CHARGED,
                'small_business' => ZugferdDutyTaxFeeCategories::EXEMPT_FROM_TAX,
                default => ZugferdDutyTaxFeeCategories::EXEMPT_FROM_TAX,
            };

            $document->addDocumentTax(
                $category,
                'VAT',
                $subtotal,
                0,
                0
            );
        }

        // Payment means (if bank details available)
        if ($company->bank_iban) {
            $document->addDocumentPaymentMean(
                '58', // SEPA credit transfer
                null,
                null,
                null,
                null,
                null,
                $company->bank_iban,
                null,
                $company->bank_bic ?? null
            );
        }
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
        if (! empty($invoice->customer?->leitweg_id)) {
            return ZugferdProfiles::PROFILE_XRECHNUNG;
        }

        // `settings` is a HasMany of key/value rows, not an object — use
        // getSetting() to read a single key.
        $profile = $invoice->company?->getSetting('zugferd_profile') ?? 'EN16931';

        return match ($profile) {
            'MINIMUM' => ZugferdProfiles::PROFILE_MINIMUM,
            'BASIC' => ZugferdProfiles::PROFILE_BASICWL,
            'EN16931' => ZugferdProfiles::PROFILE_EN16931,
            'EXTENDED' => ZugferdProfiles::PROFILE_EXTENDED,
            'XRECHNUNG' => ZugferdProfiles::PROFILE_XRECHNUNG,
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
