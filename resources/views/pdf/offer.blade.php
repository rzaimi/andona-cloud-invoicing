<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Angebot {{ $offer->number ?? '' }}</title>
    <style>
    @php
        // ── Font size helper ──────────────────────────────────────────────────
        if (!function_exists('getOfferFontSizePx')) {
            function getOfferFontSizePx($size) {
                // Target: body 10–12pt, matching invoice sizing convention.
                switch ($size) {
                    case 'small':  return 11;   //  ~8 pt
                    case 'large':  return 16;   // ~12 pt
                    case 'medium':
                    default:       return 13;   // ~10 pt — offer default
                }
            }
        }

        // ── Resolve layout settings ───────────────────────────────────────────
        $layoutSettings = [];
        if (is_object($layout) && isset($layout->settings) && is_array($layout->settings)) {
            $layoutSettings = $layout->settings;
        } elseif (is_array($layout) && isset($layout['settings']) && is_array($layout['settings'])) {
            $layoutSettings = $layout['settings'];
        }

        $bodyFontSize    = getOfferFontSizePx($layoutSettings['fonts']['size'] ?? 'small');
        $headingFontSize = $bodyFontSize + 3;

        // ── Determine template ────────────────────────────────────────────────
        $template = is_object($layout) ? ($layout->template ?? 'minimal') : ($layout['template'] ?? 'minimal');
        // Three unified themes: minimal, professional, modern.
        // Legacy template keys (including offer-only "creative") alias to
        // the closest survivor.
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

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: {{ $layoutSettings['fonts']['body'] ?? 'DejaVu Sans' }}, DejaVu Sans, sans-serif;
        font-size: {{ $bodyFontSize }}px;
        line-height: 1.45;
        color: {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};
        background: white;
        /* margin-bottom (not padding) — DomPDF paginates against body
           margins reliably; reserves room for the fixed 5-column
           footer block. Kept in sync with invoice.blade.php so shared
           templates render identically across both document kinds. */
        margin-bottom: 35mm;
    }

    /* ── DIN 5008 address block ──────────────────────────────────────────────── */
    .din-5008-address {
        width: 85mm;
        min-height: 35mm;
        max-height: 45mm;
        font-size: {{ $bodyFontSize }}px;
        line-height: 1.35;
        page-break-inside: avoid;
    }
    .sender-return-address {
        font-size: 7pt;
        color: #6b7280;
        border-bottom: 1px solid #d1d5db;
        padding-bottom: 1mm;
        margin-bottom: 2mm;
        white-space: nowrap;
        overflow: hidden;
    }

    /* ── Container ───────────────────────────────────────────────────────────── */
    /* .container intentionally rule-free — see invoice.blade.php for
       the rationale. Templates handle inline padding themselves. */
    .container { }

    /* ── Items table (base styles) ───────────────────────────────────────────── */
    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin: 5mm 0;
    }
    .items-table th {
        padding: 6px 7px;
        text-align: left;
        font-size: {{ $bodyFontSize - 1 }}px;
        font-weight: bold;
        letter-spacing: 0.3px;
    }
    .items-table td {
        padding: 5px 7px;
        font-size: {{ $bodyFontSize }}px;
        vertical-align: top;
    }
    .text-right  { text-align: right; }
    .text-center { text-align: center; }

    /* ── Document info table (right column) ──────────────────────────────────── */
    .offer-info-table {
        width: 100%;
        border-collapse: collapse;
        font-size: {{ $bodyFontSize - 1 }}px;
    }
    .offer-info-table td {
        border: 1px solid {{ $layoutSettings['colors']['accent'] ?? '#9ca3af' }};
        padding: 3px 6px;
        vertical-align: top;
    }
    .offer-info-table td:first-child {
        color: {{ $layoutSettings['colors']['secondary'] ?? '#374151' }};
        white-space: nowrap;
        width: 42%;
    }
    .offer-info-table td:last-child { font-weight: bold; }

    /* ── Totals (table-based, no floats) ─────────────────────────────────────── */
    .totals-outer { width: 100%; border-collapse: collapse; margin-top: 3mm; }
    .totals-outer td { padding: 0; vertical-align: top; }
    .totals-inner {
        width: 100%;
        border-collapse: collapse;
        font-size: {{ $bodyFontSize }}px;
    }
    .totals-inner td { padding: 4px 8px; border-bottom: 1px solid #e5e7eb; }
    .totals-inner td:last-child { text-align: right; white-space: nowrap; }
    .totals-inner .total-row td {
        font-weight: bold;
        font-size: {{ $bodyFontSize + 1 }}px;
        border-top: 2px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};
        border-bottom: 2px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};
    }

    /* ── Footer: rendered by pdf.partials.footer partial ─────────────── */

    /* ── Preview notice ──────────────────────────────────────────────────────── */
    @if(isset($preview) && $preview)
    .preview-notice {
        position: fixed;
        top: 0; left: 0; right: 0;
        background: #fbbf24;
        color: #92400e;
        text-align: center;
        padding: 8px;
        font-weight: bold;
        font-size: {{ $bodyFontSize - 1 }}px;
        z-index: 2000;
    }
    @endif

    /* Small uniform top/bottom @page margin (5mm ≈ 20px). Horizontal
       insets are 0 — templates apply their own 20mm inline padding so
       content lands at the DIN 5008 address-window x-coordinate
       (20mm from page edge). Kept in sync with invoice.blade.php. */
    @page {
        margin: 5mm 0;
    }
    </style>
</head>
<body>

@php
    // ── Resolve snapshot ──────────────────────────────────────────────────────
    if (is_object($offer) && method_exists($offer, 'getCompanySnapshot')) {
        $snapshot = $offer->getCompanySnapshot();
    } elseif (isset($offer->company_snapshot) && is_array($offer->company_snapshot)) {
        $snapshot = $offer->company_snapshot;
    } elseif (is_array($offer) && isset($offer['company_snapshot']) && is_array($offer['company_snapshot'])) {
        $snapshot = $offer['company_snapshot'];
    } elseif (isset($company)) {
        $snapshot = [
            'name'                => $company->name               ?? '',
            'email'               => $company->email              ?? '',
            'phone'               => $company->phone              ?? '',
            'address'             => $company->address            ?? '',
            'postal_code'         => $company->postal_code        ?? '',
            'city'                => $company->city               ?? '',
            'country'             => $company->country            ?? 'Deutschland',
            'tax_number'          => $company->tax_number         ?? '',
            'vat_number'          => $company->vat_number         ?? '',
            'commercial_register' => $company->commercial_register ?? '',
            'managing_director'   => $company->managing_director  ?? '',
            'legal_form'          => $company->legal_form         ?? null,
            'legal_form_label'    => method_exists($company, 'getLegalFormLabel') ? $company->getLegalFormLabel() : null,
            'manager_title'       => method_exists($company, 'getManagerTitle') ? $company->getManagerTitle() : null,
            'display_name'        => method_exists($company, 'getDisplayName')  ? $company->getDisplayName()   : ($company->name ?? ''),
            'website'             => $company->website            ?? '',
            'logo'                => $company->logo               ?? '',
            'bank_name'           => $company->bank_name          ?? '',
            'bank_iban'           => $company->bank_iban          ?? '',
            'bank_bic'            => $company->bank_bic           ?? '',
        ];
    } else {
        $snapshot = [];
    }

    if (empty($snapshot['logo'] ?? null) && isset($company) && !empty($company->logo ?? null)) {
        $snapshot['logo'] = $company->logo;
    }

    // ── Date helpers ──────────────────────────────────────────────────────────
    $dateFormat = $settings['date_format'] ?? 'd.m.Y';

    // The unified templates + partials call formatInvoiceDate() and
    // getReadableInvoiceType() regardless of doc kind; define them here too
    // (same bodies as pdf/invoice.blade.php, guarded by function_exists so
    // a single request that renders both documents doesn't redeclare).
    if (!function_exists('formatInvoiceDate')) {
        function formatInvoiceDate($date, $format = 'd.m.Y') {
            if (!$date) {
                return '';
            }
            try { return \Carbon\Carbon::parse($date)->format($format); } catch (\Exception $e) { return ''; }
        }
    }

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

    // Legacy alias used by the old offer-only flow — kept for any external
    // views that still reference it.
    if (!function_exists('fmtOfferDate')) {
        function fmtOfferDate($date, $format = 'd.m.Y') {
            return formatInvoiceDate($date, $format);
        }
    }

    // ── Make $layoutSettings available to sub-templates ───────────────────────
    if (is_object($layout)) {
        if (!isset($layout->settings) || !is_array($layout->settings)) {
            $layout->settings = $layoutSettings;
        }
    }

    // Offer-side doc helpers used by the shared template files.
    $doc          = $offer;
    $docKind      = 'offer';
    $templateFile = 'pdf.templates.' . $template;
@endphp

@if(isset($preview) && $preview)
<div class="preview-notice">
    VORSCHAU – Template: {{ $template }} | Diese Ansicht zeigt, wie die PDF-Datei aussehen wird
</div>
@endif

{{-- ── FOOTER: rendered as a DIRECT child of <body> so DomPDF's
     position:fixed extractor clones it onto every page. If it's
     nested inside the template's container <div>, DomPDF falls back
     to flow rendering and only the last page shows the footer. ──── --}}
@include('pdf.partials.footer')

{{-- ── INCLUDE SUB-TEMPLATE ─────────────────────────────────────────── --}}
@includeFirst([$templateFile, 'pdf.templates.minimal'])

{{-- ── PAGE NUMBER: bottom-right corner, small letters. y=height-12pt
     (~4mm from bottom edge) places it inside the footer's right edge,
     below the column text on the footer's white background. Matches
     invoice.blade.php so both render identically. ── --}}
<script type="text/php">
if (isset($pdf)) {
    $pdf->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
        $font = $fontMetrics->get_font("DejaVu Sans", "normal");
        $size = 6.5;
        $text = "Seite {$pageNumber} / {$pageCount}";
        $w    = $fontMetrics->get_text_width($text, $font, $size);
        $x    = $canvas->get_width() - $w - 23;
        $y    = $canvas->get_height() - 12;
        $canvas->text($x, $y, $text, $font, $size, [0.4, 0.4, 0.4]);
    });
}
</script>

</body>
</html>
