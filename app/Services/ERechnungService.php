<?php

namespace App\Services;

use App\Modules\Invoice\Models\Invoice;
use horstoeko\zugferd\ZugferdDocumentBuilder;
use horstoeko\zugferd\ZugferdDocumentPdfMerger;
use horstoeko\zugferd\ZugferdProfiles;
use horstoeko\zugferd\codelists\ZugferdCountryCodes;
use horstoeko\zugferd\codelists\ZugferdDutyTaxFeeCategories;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class ERechnungService
{
    private function getSettingsValue($settings, string $key, $default = null)
    {
        // Company settings are stored as array casts in this project
        if (is_array($settings)) {
            return $settings[$key] ?? $default;
        }

        if (is_object($settings)) {
            return $settings->{$key} ?? $default;
        }

        return $default;
    }

    private function toPercentRate($rate): float
    {
        if ($rate === null) {
            return 0.0;
        }

        $r = (float)$rate;
        // In this project VAT rates are stored as fractions (e.g. 0.19). ZUGFeRD expects percent (e.g. 19.0).
        return $r <= 1.0 ? $r * 100.0 : $r;
    }

    private function taxCategory(bool $isReverseCharge, bool $isVatExempt, float $percentRate)
    {
        $fallback = ZugferdDutyTaxFeeCategories::STANDARD_RATE;

        $pick = function (array $names) use ($fallback) {
            foreach ($names as $name) {
                $constName = ZugferdDutyTaxFeeCategories::class . '::' . $name;
                if (defined($constName)) {
                    return constant($constName);
                }
            }
            return $fallback;
        };

        if ($isReverseCharge) {
            return $pick(['REVERSE_CHARGE', 'VAT_REVERSE_CHARGE']);
        }

        if ($isVatExempt) {
            return $pick(['EXEMPT_FROM_TAX', 'EXEMPT']);
        }

        if ($percentRate <= 0.00001) {
            return $pick(['ZERO_RATED_GOODS', 'ZERO_RATE', 'ZERO_RATED']);
        }

        // Use reduced rate when possible (e.g. 7%)
        if ($percentRate > 0 && $percentRate < 19.0) {
            return $pick(['REDUCED_RATE']);
        }

        return $fallback;
    }

    /**
     * Generate XRechnung (XML only) for an invoice
     */
    public function generateXRechnung(Invoice $invoice): string
    {
        $invoice->load(['customer', 'company', 'company.settings', 'items']);
        
        $profile = $this->getProfileFromSettings($invoice->company->settings);
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
        if (!$layout) {
            $layout = \App\Modules\Invoice\Models\InvoiceLayout::forCompany($invoice->company_id)
                ->where('is_default', true)
                ->first();
        }
        
        // If no layout exists, create a minimal default layout
        if (!$layout) {
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
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ]);
        
        $pdfContent = $pdf->output();
        
        // Generate XML
        $profile = $this->getProfileFromSettings($invoice->company->settings);
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

        $isSmallBusiness = (bool)($company->is_small_business ?? false);
        $isReverseCharge = (bool)($invoice->is_reverse_charge ?? false);
        $hasVatExemption = (($invoice->vat_exemption_type ?? 'none') !== 'none');
        $isVatExempt = $isSmallBusiness || $hasVatExemption;
        $isVatFree = $isReverseCharge || $isVatExempt;
        
        // Document header
        $document->setDocumentInformation(
            $invoice->number ?? 'DRAFT-' . $invoice->id,
            '380', // Invoice type code
            $invoice->issue_date ?? new \DateTime(),
            $this->getSettingsValue($settings, 'currency', 'EUR')
        );
        
        // Seller (Company) information
        $document->setDocumentSeller(
            $company->name,
            $company->commercial_register ?? null
        );
        
        if (!empty($company->vat_number)) {
            $document->addDocumentSellerGlobalId(
                $company->vat_number,
                '0088' // VAT registration number scheme
            );
        }
        
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
        
        $buyerVatId = $isReverseCharge && !empty($invoice->buyer_vat_id)
            ? $invoice->buyer_vat_id
            : ($customer->vat_number ?? null);

        if (!empty($buyerVatId)) {
            $document->addDocumentBuyerGlobalId(
                $buyerVatId,
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
                'Zahlbar bis ' . $invoice->due_date->format('d.m.Y'),
                $invoice->due_date
            );
        }
        
        // Line items
        foreach ($invoice->items as $index => $item) {
            $lineTotal = (float)($item->total ?? ($item->quantity * $item->unit_price));
            
            // Extract name and description from the description field
            $description = $item->description ?? 'Position ' . ($index + 1);
            $lines = explode("\n", $description, 2);
            $itemName = $lines[0];
            $itemDescription = $lines[1] ?? '';
            
            $document->addNewPosition(($index + 1) . '');
            $document->setDocumentPositionProductDetails(
                $itemName,
                $itemDescription,
                $item->product_id ?? null
            );
            
            $document->setDocumentPositionGrossPrice($item->unit_price ?? 0);
            $document->setDocumentPositionNetPrice($item->unit_price ?? 0);
            $document->setDocumentPositionQuantity($item->quantity ?? 1, 'C62'); // Unit code: piece
            
            $document->setDocumentPositionLineSummation($lineTotal);
            
            // Add tax for line item
            $ratePercent = $this->toPercentRate($isVatFree ? 0 : ($item->tax_rate ?? $invoice->tax_rate ?? 0));
            $category = $this->taxCategory($isReverseCharge, $isVatExempt, $ratePercent);
            $document->addDocumentPositionTax(
                $category,
                'VAT',
                $ratePercent
            );
        }
        
        // Summation
        $subtotal = $invoice->subtotal ?? 0;
        $taxAmount = $invoice->tax_amount ?? 0;
        $total = $invoice->total ?? 0;
        
        $document->setDocumentSummation(
            $total,      // Grand total
            $total,      // Due payable amount
            $subtotal,   // Line total
            0.0,         // Charge total
            0.0,         // Allowance total
            $subtotal,   // Tax basis total
            $taxAmount,  // Tax total
            0.0,         // Rounding amount
            0.0          // Prepaid amount
        );
        
        // Add tax breakdown per rate (or 0% when VAT-free)
        if ($isVatFree) {
            $ratePercent = 0.0;
            $category = $this->taxCategory($isReverseCharge, $isVatExempt, $ratePercent);
            $document->addDocumentTax(
                $category,
                'VAT',
                (float)$subtotal,
                0.0,
                $ratePercent
            );
        } else {
            $taxRates = [];
            foreach ($invoice->items as $it) {
                $rateFraction = (float)($it->tax_rate ?? $invoice->tax_rate ?? 0);
                $ratePercent = $this->toPercentRate($rateFraction);
                if (!isset($taxRates[$ratePercent])) {
                    $taxRates[$ratePercent] = ['base' => 0.0, 'amount' => 0.0];
                }
                $base = (float)($it->total ?? 0);
                $taxRates[$ratePercent]['base'] += $base;
                $taxRates[$ratePercent]['amount'] += $base * ($rateFraction <= 1.0 ? $rateFraction : ($rateFraction / 100.0));
            }

            ksort($taxRates);
            foreach ($taxRates as $ratePercent => $data) {
                $category = $this->taxCategory(false, false, (float)$ratePercent);
                $document->addDocumentTax(
                    $category,
                    'VAT',
                    (float)$data['base'],
                    (float)$data['amount'],
                    (float)$ratePercent
                );
            }
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
     * Get ZUGFeRD profile from company settings
     */
    private function getProfileFromSettings($settings): int
    {
        $profile = $this->getSettingsValue($settings, 'zugferd_profile', 'EN16931');
        
        return match($profile) {
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
        return match(strtolower($country)) {
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
        $filename = 'XRechnung_' . ($invoice->number ?? 'DRAFT-' . $invoice->id) . '.xml';
        
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
        $filename = 'ZUGFeRD_' . ($invoice->number ?? 'DRAFT-' . $invoice->id) . '.pdf';
        
        return [
            'content' => $pdf,
            'filename' => $filename,
            'mime_type' => 'application/pdf',
        ];
    }
}

