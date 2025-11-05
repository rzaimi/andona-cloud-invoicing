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
            
            $bodyFontSize = getFontSizePx($layout->settings['fonts']['size'] ?? 'medium');
            $headingFontSize = $bodyFontSize + 4;
            $titleFontSize = $bodyFontSize + 8;
        @endphp
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: {{ $layout->settings['fonts']['body'] ?? 'DejaVu Sans' }}, sans-serif;
            font-size: {{ $bodyFontSize }}px;
            line-height: 1.4;
            color: {{ $layout->settings['colors']['text'] ?? '#1f2937' }};
            background: white;
        }

        .container {
            max-width: 210mm;
            margin: 0 auto;
            padding: {{ min($layout->settings['layout']['margin_top'] ?? 15, 15) }}mm {{ min($layout->settings['layout']['margin_right'] ?? 15, 15) }}mm {{ min($layout->settings['layout']['margin_bottom'] ?? 15, 15) }}mm {{ min($layout->settings['layout']['margin_left'] ?? 15, 15) }}mm;
        }

        .header {
            width: 100%;
            margin-bottom: 25px;
            overflow: hidden;
        }

        .company-header-line {
            font-size: {{ $bodyFontSize }}px;
            color: {{ $layout->settings['colors']['primary'] ?? '#3b82f6' }};
            margin-bottom: 20px;
            float: left;
            width: 70%;
        }

        .company-header-line .company-name {
            font-weight: bold;
            color: {{ $layout->settings['colors']['primary'] ?? '#3b82f6' }};
        }

        .logo-container {
            width: 120px;
            height: 60px;
            background-color: {{ $layout->settings['colors']['primary'] ?? '#3b82f6' }};
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
            color: {{ $layout->settings['colors']['text'] ?? '#1f2937' }};
        }

        .intro-text {
            margin-bottom: 25px;
            line-height: 1.6;
            color: {{ $layout->settings['colors']['text'] ?? '#1f2937' }};
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
            color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }};
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
            border-top: 1px solid {{ $layout->settings['colors']['text'] ?? '#1f2937' }};
            border-bottom: 1px solid {{ $layout->settings['colors']['text'] ?? '#1f2937' }};
        }

        .items-table th {
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            font-size: {{ $bodyFontSize }}px;
            border-bottom: 1px solid {{ $layout->settings['colors']['text'] ?? '#1f2937' }};
            color: {{ $layout->settings['colors']['text'] ?? '#1f2937' }};
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
            border-top: 1px solid {{ $layout->settings['colors']['text'] ?? '#1f2937' }};
            border-bottom: 1px solid {{ $layout->settings['colors']['text'] ?? '#1f2937' }};
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
            color: {{ $layout->settings['colors']['primary'] ?? '#3b82f6' }};
            margin-top: 40px;
        }

        .footer {
            margin-top: 50px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            font-size: {{ $bodyFontSize - 1 }}px;
            color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }};
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
    </style>
</head>
<body>
@if(isset($preview) && $preview)
    <div class="preview-notice">
        VORSCHAU - Diese Ansicht zeigt, wie die PDF-Datei aussehen wird
    </div>
@endif

@php
    // Get template from layout, ensure it's a valid template name
    $template = $layout->template ?? 'minimal';
    $validTemplates = ['modern', 'classic', 'minimal', 'professional', 'creative', 'elegant'];
    if (!in_array($template, $validTemplates)) {
        $template = 'minimal'; // Fallback to minimal if invalid
    }
    $templateFile = 'pdf.invoice-templates.' . $template;
@endphp

{{-- Include the specific template file based on layout->template --}}
@includeFirst([$templateFile, 'pdf.invoice-templates.minimal'])
</body>
</html>
