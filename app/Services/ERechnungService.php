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
        
        // Document header
        $document->setDocumentInformation(
            $invoice->number ?? 'DRAFT-' . $invoice->id,
            '380', // Invoice type code
            $invoice->issue_date ?? new \DateTime(),
            $settings->currency ?? 'EUR'
        );
        
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
                'Zahlbar bis ' . $invoice->due_date->format('d.m.Y'),
                $invoice->due_date
            );
        }
        
        // Line items
        foreach ($invoice->items as $index => $item) {
            $lineTotal = $item->quantity * $item->unit_price;
            
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
            $taxRate = $item->tax_rate ?? 19.0;
            $document->addDocumentPositionTax(
                ZugferdDutyTaxFeeCategories::STANDARD_RATE,
                'VAT',
                $taxRate
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
        
        // Add tax breakdown
        $taxRate = 19.0; // Default German VAT rate
        $document->addDocumentTax(
            ZugferdDutyTaxFeeCategories::STANDARD_RATE,
            'VAT',
            $subtotal,
            $taxAmount,
            $taxRate
        );
        
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
        $profile = $settings->zugferd_profile ?? 'EN16931';
        
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

