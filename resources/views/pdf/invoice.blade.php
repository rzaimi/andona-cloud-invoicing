<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rechnung {{ $invoice->number }}</title>
    <style>
        @php
            // Helper function to convert font size string to pixels
            // Check if function exists to avoid redeclaration errors when multiple PDFs are generated
            if (!function_exists('getFontSizePx')) {
                function getFontSizePx($size) {
                    switch($size) {
                        case 'small': return 11;
                        case 'large': return 14;
                        case 'medium':
                        default: return 12;
                    }
                }
            }
            
            // Safely access layout settings - handle both object and array
            $layoutSettings = is_object($layout) && isset($layout->settings) ? $layout->settings : (is_array($layout) ? ($layout['settings'] ?? []) : []);
            
            // Ensure layoutSettings is always an array
            if (!is_array($layoutSettings)) {
                $layoutSettings = [];
            }
            
            // Make layoutSettings available to all templates by ensuring layout->settings exists
            if (is_object($layout)) {
                if (!isset($layout->settings) || !is_array($layout->settings)) {
                    $layout->settings = $layoutSettings;
                } else {
                    $layoutSettings = $layout->settings;
                }
            }
            
            $bodyFontSize = getFontSizePx($layoutSettings['fonts']['size'] ?? 'medium');
            $headingFontSize = $bodyFontSize + 4;
            $titleFontSize = $bodyFontSize + 8;
        @endphp
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: {{ $layoutSettings['fonts']['body'] ?? 'DejaVu Sans' }}, sans-serif;
            font-size: {{ $bodyFontSize }}px;
            line-height: 1.4;
            color: {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};
            background: white;
        }

        /* DIN 5008 compliant address block for German envelope windows */
        /* Standard: 4.5cm from top, 2cm from left, max 8.5cm × 4.5cm */
        .din-5008-address {
            position: absolute;
            top: 45mm; /* 4.5cm from top - DIN 5008 standard for envelope window */
            left: 20mm; /* 2cm from left - DIN 5008 standard */
            width: 85mm; /* 8.5cm max width - DIN 5008 standard */
            max-height: 45mm; /* 4.5cm max height - DIN 5008 standard */
            font-size: {{ $bodyFontSize }}px;
            line-height: 1.3;
            color: {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};
            z-index: 10;
            page-break-inside: avoid;
        }
        
        /* Regular address block (for display in document, not envelope window) */
        .address-block {
            margin-bottom: 20px;
        }

        .container {
            max-width: 210mm;
            margin: 0 auto;
            padding: {{ min($layoutSettings['layout']['margin_top'] ?? 15, 20) }}mm {{ min($layoutSettings['layout']['margin_right'] ?? 20, 25) }}mm {{ max(min($layoutSettings['layout']['margin_bottom'] ?? 20, 25) + 60, 80) }}mm {{ min($layoutSettings['layout']['margin_left'] ?? 20, 25) }}mm;
            position: relative; /* For absolute positioning of address block */
        }

        .header {
            width: 100%;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .company-header-line {
            font-size: {{ $bodyFontSize }}px;
            color: {{ $layoutSettings['colors']['primary'] ?? '#3b82f6' }};
            margin-bottom: 20px;
            float: left;
            width: 70%;
        }

        .company-header-line .company-name {
            font-weight: bold;
            color: {{ $layoutSettings['colors']['primary'] ?? '#3b82f6' }};
        }

        .logo-container {
            width: 120px;
            height: 60px;
            background-color: {{ $layoutSettings['colors']['primary'] ?? '#3b82f6' }};
            float: right;
            text-align: center;
            vertical-align: middle;
            color: white;
            font-size: {{ $bodyFontSize - 2 }}px;
            padding: 8px;
            line-height: 44px;
            box-sizing: border-box;
        }

        .logo-container img {
            max-width: 104px;
            max-height: 44px;
            vertical-align: middle;
        }

        .invoice-number {
            font-size: {{ $headingFontSize + 4 }}px;
            font-weight: bold;
            margin: 25px 0 15px 0;
            color: {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};
        }

        .intro-text {
            margin-bottom: 25px;
            line-height: 1.6;
            color: {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};
        }

        .customer-block {
            margin-bottom: 30px;
        }

        .customer-block strong {
            font-size: {{ $bodyFontSize }}px;
            display: block;
            margin-bottom: 5px;
        }

        .customer-block .contact-person {
            margin-bottom: 5px;
        }

        .invoice-meta-row {
            width: 100%;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .meta-item {
            float: left;
            margin-right: 40px;
            margin-bottom: 10px;
        }

        .meta-label {
            font-size: {{ $bodyFontSize - 1 }}px;
            color: {{ $layoutSettings['colors']['text'] ?? '#6b7280' }};
            margin-bottom: 3px;
        }

        .meta-value {
            font-weight: bold;
            font-size: {{ $bodyFontSize }}px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
            border-top: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};
            border-bottom: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};
        }

        .items-table th {
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            font-size: {{ $bodyFontSize }}px;
            border-bottom: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};
            color: {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};
        }

        .items-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: {{ $bodyFontSize }}px;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .totals {
            margin-top: 30px;
            float: right;
            width: 300px;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 8px 0;
            text-align: right;
        }

        .totals-table td:first-child {
            text-align: left;
        }

        .totals-table tr {
            border-bottom: 1px solid #e5e7eb;
        }

        .totals-table .total-row {
            border-top: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};
            border-bottom: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};
            font-weight: bold;
            font-size: {{ $bodyFontSize + 1 }}px;
            padding-top: 5px;
            padding-bottom: 5px;
        }

        .payment-section {
            clear: both;
            margin-top: 40px;
        }

        .payment-terms {
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .greeting {
            margin-bottom: 15px;
        }

        .signature {
            font-size: {{ $headingFontSize }}px;
            font-style: italic;
            color: {{ $layoutSettings['colors']['primary'] ?? '#3b82f6' }};
            margin-top: 40px;
        }

        .pdf-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            padding: 10px {{ min($layoutSettings['layout']['margin_right'] ?? 15, 15) }}mm 10px {{ min($layoutSettings['layout']['margin_left'] ?? 15, 15) }}mm;
            border-top: 1px solid #e5e7eb;
            font-size: {{ $bodyFontSize - 1 }}px;
            color: {{ $layoutSettings['colors']['text'] ?? '#6b7280' }};
            background-color: white;
            z-index: 1000;
            height: auto;
        }

        @page {
            margin-bottom: 50mm; /* Reserve space for fixed footer */
        }

        .footer-columns {
            width: 100%;
            overflow: hidden;
        }

        .footer-column {
            float: left;
            width: 32%;
            margin-right: 2%;
            line-height: 1.5;
        }

        .footer-column:last-child {
            margin-right: 0;
        }

        .clearfix {
            clear: both;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            font-size: {{ $bodyFontSize - 1 }}px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-draft { background-color: #fef3c7; color: #92400e; }
        .status-sent { background-color: #dbeafe; color: #1e40af; }
        .status-paid { background-color: #d1fae5; color: #065f46; }
        .status-overdue { background-color: #fee2e2; color: #991b1b; }
        .status-cancelled { background-color: #f3f4f6; color: #374151; }

        @if(isset($preview) && $preview)
        .preview-notice {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #fbbf24;
            color: #92400e;
            text-align: center;
            padding: 10px;
            font-weight: bold;
            z-index: 1000;
        }
        @endif

        @page {
            margin-bottom: 50mm; /* Reserve space for fixed footer */
        }
    </style>
</head>
<body>
@if(isset($preview) && $preview)
    <div class="preview-notice">
        VORSCHAU - Diese Ansicht zeigt, wie die PDF-Datei aussehen wird
    </div>
@endif

{{-- DIN 5008 compliant address block for envelope window (positioned absolutely) --}}
@if($layoutSettings['content']['use_din_5008_address'] ?? true)
    @include('pdf.partials.address-block', ['invoice' => $invoice, 'bodyFontSize' => $bodyFontSize, 'offer' => null])
@endif

{{-- Footer must be defined early and as direct child of body for DomPDF fixed positioning --}}
@php
    // Use saved snapshot instead of live company data to preserve footer information
    // Handle both model instance and stdClass/array (DomPDF may convert models)
    if (is_object($invoice) && method_exists($invoice, 'getCompanySnapshot')) {
        $snapshot = $invoice->getCompanySnapshot();
    } elseif (isset($invoice->company_snapshot) && is_array($invoice->company_snapshot)) {
        $snapshot = $invoice->company_snapshot;
    } elseif (is_array($invoice) && isset($invoice['company_snapshot']) && is_array($invoice['company_snapshot'])) {
        $snapshot = $invoice['company_snapshot'];
    } elseif (isset($company)) {
        // Fallback: create snapshot from current company
        $snapshot = [
            'name' => $company->name ?? '',
            'email' => $company->email ?? '',
            'phone' => $company->phone ?? '',
            'address' => $company->address ?? '',
            'postal_code' => $company->postal_code ?? '',
            'city' => $company->city ?? '',
            'country' => $company->country ?? 'Deutschland',
            'tax_number' => $company->tax_number ?? '',
            'vat_number' => $company->vat_number ?? '',
            'commercial_register' => $company->commercial_register ?? '',
            'managing_director' => $company->managing_director ?? '',
            'website' => $company->website ?? '',
            'logo' => $company->logo ?? '',
            'bank_name' => $company->bank_name ?? '',
            'bank_iban' => $company->bank_iban ?? '',
            'bank_bic' => $company->bank_bic ?? '',
        ];
    } else {
        $snapshot = [];
    }
@endphp
@if($layoutSettings['branding']['show_footer'] ?? true)
    <div class="pdf-footer" style="border-top: 1px solid {{ $layoutSettings['colors']['accent'] ?? '#e5e7eb' }}; color: {{ $layoutSettings['colors']['text'] ?? '#9ca3af' }}; line-height: 1.8;">
        @if($snapshot['address'] ?? null){{ $snapshot['address'] }}@endif
        @if(($snapshot['postal_code'] ?? null) && ($snapshot['city'] ?? null)), {{ $snapshot['postal_code'] }} {{ $snapshot['city'] }}@endif
        @if($snapshot['email'] ?? null) · {{ $snapshot['email'] }}@endif
        @if($snapshot['phone'] ?? null) · {{ $snapshot['phone'] }}@endif
        @if($snapshot['vat_number'] ?? null) · USt-IdNr.: {{ $snapshot['vat_number'] }}@endif
        @if($layoutSettings['content']['show_bank_details'] ?? true && ($snapshot['bank_iban'] ?? null))
            <br>IBAN: {{ $snapshot['bank_iban'] }}
            @if($snapshot['bank_bic'] ?? null) · BIC: {{ $snapshot['bank_bic'] }}@endif
        @endif
        @if(isset($settings['invoice_footer']) && !empty($settings['invoice_footer']))
            <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid {{ $layoutSettings['colors']['accent'] ?? '#e5e7eb' }}; font-size: {{ $bodyFontSize - 1 }}px;">
                {{ $settings['invoice_footer'] }}
            </div>
        @endif
    </div>
@endif

@php
    // Get template from layout, ensure it's a valid template name
    // Check if layout is an object with template property or an array
    if (is_object($layout)) {
        $template = $layout->template ?? 'clean';
        // Ensure settings are accessible
        if (!isset($layout->settings) || !is_array($layout->settings)) {
            $layout->settings = [];
        }
    } else {
        $template = $layout['template'] ?? 'clean';
        // Ensure settings are accessible
        if (!isset($layout['settings']) || !is_array($layout['settings'])) {
            $layout['settings'] = [];
        }
    }
    
    $validTemplates = ['clean', 'modern', 'professional', 'elegant', 'minimal', 'classic'];
    if (!in_array($template, $validTemplates)) {
        $template = 'clean'; // Fallback to clean if invalid
    }
    $templateFile = 'pdf.invoice-templates.' . $template;
    
    // Make snapshot available to all templates (use saved snapshot instead of live company data)
    // Snapshot is already set above, no need to recalculate
@endphp

{{-- Debug indicator in preview mode --}}
@if(isset($preview) && $preview)
    <div style="position: fixed; top: 0; right: 0; background: rgba(59, 130, 246, 0.9); color: white; padding: 5px 10px; font-size: 10px; z-index: 9999; border-bottom-left-radius: 4px; font-weight: bold;">
        Template: {{ $template }} | Layout: {{ is_object($layout) ? ($layout->name ?? 'N/A') : ($layout['name'] ?? 'N/A') }}
    </div>
@endif

{{-- Include the specific template file based on layout->template --}}
@includeFirst([$templateFile, 'pdf.invoice-templates.clean'])
</body>
</html>
