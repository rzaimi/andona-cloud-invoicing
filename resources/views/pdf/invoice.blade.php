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

            // Determine template early (so we can vary DIN-address rendering per template)
            $template = is_object($layout) ? ($layout->template ?? 'clean') : ($layout['template'] ?? 'clean');
            $validTemplates = ['clean', 'modern', 'professional', 'elegant', 'minimal', 'classic'];
            if (!in_array($template, $validTemplates)) {
                $template = 'clean';
            }
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
        /* Standard: Address window 45mm from top, 20mm from left, 85mm × 45mm */
        .din-5008-address {
            width: 85mm; /* 8.5cm max width - DIN 5008 standard */
            min-height: 40mm; /* Minimum height for address block */
            max-height: 45mm; /* 4.5cm max height - DIN 5008 standard */
            font-size: {{ $bodyFontSize }}px;
            line-height: 1.3;
            color: {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};
            page-break-inside: avoid;
            margin-bottom: 10mm;
        }
        
        /* Company return address (small text above recipient) - DIN 5008 */
        .sender-return-address {
            font-size: 7pt;
            line-height: 1.2;
            color: #6b7280;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 1mm;
            margin-bottom: 2mm;
        }
        
        /* Regular address block (for display in document, not envelope window) */
        .address-block {
            margin-bottom: 20px;
        }

        .container {
            max-width: 210mm;
            margin: 0 auto;
            /* Bottom padding should NOT also reserve footer space (that's handled by @page margin-bottom). */
            padding: {{ min($layoutSettings['layout']['margin_top'] ?? 15, 20) }}mm {{ min($layoutSettings['layout']['margin_right'] ?? 20, 25) }}mm {{ min($layoutSettings['layout']['margin_bottom'] ?? 20, 25) }}mm {{ min($layoutSettings['layout']['margin_left'] ?? 20, 25) }}mm;
            position: relative;
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

        /* Page margins:
           - Keep page 1 as-is (so DIN address window stays correct)
           - Add some top margin on following pages so content doesn't start too high */
        @page {
            margin-top: 25mm;
            margin-bottom: 50mm; /* Reserve space for fixed footer */
        }
        @page :first {
            margin-top: 0mm;
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

        /* @page margin-bottom already defined above */
    </style>
</head>
<body>
@if(isset($preview) && $preview)
    <div class="preview-notice">
        VORSCHAU - Diese Ansicht zeigt, wie die PDF-Datei aussehen wird
    </div>
@endif

{{-- Receiver address is rendered inside each template to keep layouts independent --}}
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

    // Backwards-compat: older stored snapshots may miss the logo even when the company has one.
    if (empty($snapshot['logo'] ?? null) && isset($company) && !empty($company->logo ?? null)) {
        $snapshot['logo'] = $company->logo;
    }
    
    // Get date format from settings or use default
    $dateFormat = $settings['date_format'] ?? 'd.m.Y';
    
    // Helper function for date formatting
    if (!function_exists('formatInvoiceDate')) {
        function formatInvoiceDate($date, $format = 'd.m.Y') {
            if (!$date) {
                return '';
            }
            return \Carbon\Carbon::parse($date)->format($format);
        }
    }

    // Readable invoice type label
    if (!function_exists('getReadableInvoiceType')) {
        function getReadableInvoiceType($type, $sequenceNumber = null) {
            switch ($type) {
                case 'abschlagsrechnung': return 'Abschlagsrechnung ' . ($sequenceNumber ?? '');
                case 'schlussrechnung':   return 'Schlussrechnung';
                case 'nachtragsrechnung': return 'Nachtragsrechnung';
                case 'korrekturrechnung': return 'Korrekturrechnung';
                default:                  return 'Rechnung';
            }
        }
    }

    // Helper function for VAT regime legal text
    if (!function_exists('getVatRegimeNote')) {
        function getVatRegimeNote($regime) {
            switch ($regime) {
                case 'small_business':
                    return 'Gemäß § 19 UStG wird keine Umsatzsteuer berechnet.';
                case 'reverse_charge':
                    return 'Steuerschuldnerschaft des Leistungsempfängers (Reverse Charge).';
                case 'intra_community':
                    return 'Innergemeinschaftliche Lieferung. Steuerfrei gem. § 4 Nr. 1b UStG.';
                case 'export':
                    return 'Steuerfreie Ausfuhrlieferung gem. § 4 Nr. 1a UStG.';
                default:
                    return null;
            }
        }
    }
@endphp
    @php
        // Helper function to get VAT regime text
        if (!function_exists('getVatRegimeText')) {
            function getVatRegimeText($regime) {
                return match($regime) {
                    'small_business' => 'Gemäß § 19 UStG wird keine Umsatzsteuer berechnet.',
                    'reverse_charge' => 'Steuerschuldnerschaft des Leistungsempfängers (Reverse Charge) gemäß § 13b UStG.',
                    'intra_community' => 'Steuerfreie innergemeinschaftliche Lieferung gemäß § 4 Nr. 1b UStG.',
                    'export' => 'Steuerfreie Ausfuhrlieferung gemäß § 4 Nr. 1a UStG.',
                    default => null,
                };
            }
        }
    @endphp

    {{-- Footer moved to individual templates for variety --}}

    {{-- Page number (bottom-right inside footer area) --}}
    <script type="text/php">
    if (isset($pdf)) {
        $pdf->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
            $font = $fontMetrics->get_font("DejaVu Sans", "normal");
            $size = 7;
            $text = "Seite {$pageNumber} / {$pageCount}";
            $w = $fontMetrics->get_text_width($text, $font, $size);
            // simple: bottom-right, aligned to the right edge, slightly above the page bottom
            $x = $canvas->get_width() - $w - 6;
            $y = $canvas->get_height() - 14;
            $canvas->text($x, $y, $text, $font, $size, [0, 0, 0]);
        });
    }
    </script>


@php
    // Ensure settings are accessible (keep behavior, but do not recompute $template here)
    if (is_object($layout)) {
        if (!isset($layout->settings) || !is_array($layout->settings)) {
            $layout->settings = [];
        }
    } else {
        if (!isset($layout['settings']) || !is_array($layout['settings'])) {
            $layout['settings'] = [];
        }
    }

    $templateFile = 'pdf.invoice-templates.' . ($template ?? 'clean');
@endphp

{{-- Debug indicator in preview mode --}}
@if(isset($preview) && $preview)
    <div style="position: fixed; top: 0; right: 0; background: rgba(59, 130, 246, 0.9); color: white; padding: 5px 10px; font-size: 10px; z-index: 9999; border-bottom-left-radius: 4px; font-weight: bold;">
        Template: {{ $template }} | Layout: {{ is_object($layout) ? ($layout->name ?? 'N/A') : ($layout['name'] ?? 'N/A') }}
    </div>
@endif

{{-- Include the specific template file based on layout->template --}}
@includeFirst([$templateFile, 'pdf.invoice-templates.clean'])

{{-- Page number script (at end of document for all pages) --}}
<script type="text/php">
if (isset($pdf)) {
    $pdf->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
        $font = $fontMetrics->get_font("DejaVu Sans", "normal");
        $size = 7;
        $text = "Seite {$pageNumber} / {$pageCount}";
        $w = $fontMetrics->get_text_width($text, $font, $size);
        // Bottom-right corner, slightly above page bottom
        $x = $canvas->get_width() - $w - 20;
        $y = $canvas->get_height() - 12;
        $canvas->text($x, $y, $text, $font, $size, [0, 0, 0]);
    });
}
</script>

</body>
</html>
