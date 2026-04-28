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
                    // Target: body 10–12pt, title 14–18pt, footer 8–10pt (DIN/ISO best practice).
                    // At 96 dpi: 1pt ≈ 1.333px  →  10pt ≈ 13px, 11pt ≈ 14.7px, 12pt ≈ 16px
                    switch($size) {
                        case 'small': return 11;   //  ~8 pt  — compact layouts
                        case 'large': return 16;   // ~12 pt  — generous readability
                        case 'medium':
                        default: return 13;        //  ~10 pt — DIN-compliant default
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

            $bodyFontSize    = getFontSizePx($layoutSettings['fonts']['size'] ?? 'small');
            $headingFontSize = $bodyFontSize + 4;
            $titleFontSize   = $bodyFontSize + 8;
            $bodyFont        = $layoutSettings['fonts']['body']    ?? 'DejaVu Sans';
            $headingFont     = $layoutSettings['fonts']['heading']  ?? $bodyFont;

            // Determine template early (so we can vary DIN-address rendering per template)
            $template = is_object($layout) ? ($layout->template ?? 'minimal') : ($layout['template'] ?? 'minimal');
            // Three unified themes: minimal, professional, modern.
            // Legacy template keys map onto the closest survivor.
            $templateAliases = [
                'clean'        => 'minimal',
                'classic'      => 'professional',
                'elegant'      => 'professional',
                'creative'     => 'modern',
            ];
            if (isset($templateAliases[$template])) {
                $template = $templateAliases[$template];
            }
            $validTemplates = ['minimal', 'professional', 'modern'];
            if (!in_array($template, $validTemplates, true)) {
                $template = 'minimal';
            }
        @endphp
        /* Minimal stylesheet — only rules that active templates
           (minimal/modern/professional) actually consume. The legacy
           class rules (.header, .logo-container, .items-table,
           .totals, .payment-section, …) were left over from an older
           template structure; templates now use inline styles so the
           class rules were dead code. Worse, several of them
           (float-based .header, position:fixed .preview-notice + body
           layout interactions) interfered with DomPDF's per-page
           margin engine and silently dropped @page margin-top on
           continuation pages. Stripping the dead rules made the
           strip-test PDF render page 2's top margin correctly. */

        /* Small uniform top/bottom @page margin (5mm ≈ 20px) on every
           page. Horizontal insets are 0 here — templates apply their
           own 20mm inline left/right padding so content lands at the
           DIN 5008 address-window x-coordinate (20mm from page edge). */
        @page {
            margin: 5mm 0;
        }

        body {
            font-family: {{ $layoutSettings['fonts']['body'] ?? 'DejaVu Sans' }}, sans-serif;
            font-size: {{ $bodyFontSize }}px;
            line-height: 1.4;
            color: {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};
            background: white;
            /* margin-bottom reserves space for the fixed 5-column
               footer block (~25mm) plus buffer so body content
               never bleeds into the footer area. */
            margin-bottom: 35mm;
        }

        .container { }

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
        }

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
            'name'                => $company->name ?? '',
            'email'               => $company->email ?? '',
            'phone'               => $company->phone ?? '',
            'fax'                 => $company->fax ?? '',
            'address'             => $company->address ?? '',
            'postal_code'         => $company->postal_code ?? '',
            'city'                => $company->city ?? '',
            'country'             => $company->country ?? 'Deutschland',
            'tax_number'          => $company->tax_number ?? '',
            'tax_office'          => $company->tax_office ?? '',
            'vat_number'          => $company->vat_number ?? '',
            'commercial_register' => $company->commercial_register ?? '',
            'managing_director'   => $company->managing_director ?? '',
            'legal_form'          => $company->legal_form ?? null,
            'legal_form_label'    => method_exists($company, 'getLegalFormLabel') ? $company->getLegalFormLabel() : null,
            'manager_title'       => method_exists($company, 'getManagerTitle') ? $company->getManagerTitle() : null,
            'display_name'        => method_exists($company, 'getDisplayName')   ? $company->getDisplayName()   : ($company->name ?? ''),
            'website'             => $company->website ?? '',
            'logo'                => $company->logo ?? '',
            'bank_name'           => $company->bank_name ?? '',
            'bank_iban'           => $company->bank_iban ?? '',
            'bank_bic'            => $company->bank_bic ?? '',
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

@endphp

    {{-- Footer must be a DIRECT child of <body> for DomPDF's
         position:fixed extractor to clone it onto every page.
         If it's nested inside the template's container <div>, DomPDF
         falls back to flow rendering and only the LAST page shows
         the footer (issue reproduced repeatedly during DIN 5008 work).
         Render it here, BEFORE the template include — DomPDF will
         strip the position:fixed element and apply it on every page. --}}
    @include('pdf.partials.footer')

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

    // Invoice-side doc helpers used by the shared template files.
    $doc          = $invoice;
    $docKind      = 'invoice';
    $templateFile = 'pdf.templates.' . ($template ?? 'minimal');
@endphp

{{-- Debug indicator in preview mode --}}
@if(isset($preview) && $preview)
    <div style="position: fixed; top: 0; right: 0; background: rgba(59, 130, 246, 0.9); color: white; padding: 5px 10px; font-size: 10px; z-index: 9999; border-bottom-left-radius: 4px; font-weight: bold;">
        Template: {{ $template }} | Layout: {{ is_object($layout) ? ($layout->name ?? 'N/A') : ($layout['name'] ?? 'N/A') }}
    </div>
@endif

{{-- Include the specific template file based on layout->template --}}
@includeFirst([$templateFile, 'pdf.templates.minimal'])

{{-- Page number: bottom-right corner of every page in small
     letters. page_script fires once per page, so this draws on
     EVERY page automatically. y = height - 12pt (≈4mm from
     bottom edge) places it inside the footer's right edge — below
     the column text, on the footer's white background. --}}
<script type="text/php">
if (isset($pdf)) {
    $pdf->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
        $font = $fontMetrics->get_font("DejaVu Sans", "normal");
        $size = 6.5;
        $text = "Seite {$pageNumber} / {$pageCount}";
        $w    = $fontMetrics->get_text_width($text, $font, $size);
        $x = $canvas->get_width() - $w - 23;
        $y = $canvas->get_height() - 12;
        $canvas->text($x, $y, $text, $font, $size, [0.4, 0.4, 0.4]);
    });
}
</script>

</body>
</html>
