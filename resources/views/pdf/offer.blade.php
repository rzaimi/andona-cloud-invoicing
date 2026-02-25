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
                switch ($size) {
                    case 'small':  return 10;
                    case 'large':  return 13;
                    case 'medium':
                    default:       return 11;
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

        $bodyFontSize    = getOfferFontSizePx($layoutSettings['fonts']['size'] ?? 'medium');
        $headingFontSize = $bodyFontSize + 3;

        // ── Determine template ────────────────────────────────────────────────
        $template      = is_object($layout) ? ($layout->template ?? 'modern') : ($layout['template'] ?? 'modern');
        $validTemplates = ['modern', 'classic', 'minimal', 'professional', 'creative', 'elegant'];
        if (!in_array($template, $validTemplates)) $template = 'modern';
    @endphp

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: {{ $layoutSettings['fonts']['body'] ?? 'DejaVu Sans' }}, DejaVu Sans, sans-serif;
        font-size: {{ $bodyFontSize }}px;
        line-height: 1.45;
        color: {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};
        background: white;
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
    .container {
        max-width: 210mm;
        margin: 0 auto;
        padding: {{ min($layoutSettings['layout']['margin_top'] ?? 15, 20) }}mm
                 {{ min($layoutSettings['layout']['margin_right'] ?? 20, 25) }}mm
                 {{ min($layoutSettings['layout']['margin_bottom'] ?? 20, 25) }}mm
                 {{ min($layoutSettings['layout']['margin_left'] ?? 20, 25) }}mm;
    }

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

    /* ── Footer: rendered by pdf.invoice-partials.footer partial ─────────────── */

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

    /* ── Page margins ─────────────────────────────────────────────────────────── */
    @page {
        margin-top: 22mm;
        margin-bottom: 30mm;
    }
    @page :first {
        margin-top: 0mm;
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

    if (!function_exists('fmtOfferDate')) {
        function fmtOfferDate($date, $format = 'd.m.Y') {
            if (!$date) return '';
            try { return \Carbon\Carbon::parse($date)->format($format); } catch (\Exception $e) { return ''; }
        }
    }

    // ── Make $layoutSettings available to sub-templates ───────────────────────
    if (is_object($layout)) {
        if (!isset($layout->settings) || !is_array($layout->settings)) {
            $layout->settings = $layoutSettings;
        }
    }

    $templateFile = 'pdf.offer-templates.' . $template;
@endphp

@if(isset($preview) && $preview)
<div class="preview-notice">
    VORSCHAU – Template: {{ $template }} | Diese Ansicht zeigt, wie die PDF-Datei aussehen wird
</div>
@endif

{{-- ── FOOTER (fixed, direct child of body) ──────────────────────────────── --}}
@if($layoutSettings['branding']['show_footer'] ?? true)
@include('pdf.invoice-partials.footer')
@endif

{{-- ── PAGE NUMBER ─────────────────────────────────────────────────────────── --}}
<script type="text/php">
if (isset($pdf)) {
    $pdf->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
        $font = $fontMetrics->get_font("DejaVu Sans", "normal");
        $size = 7;
        $text = "Seite {$pageNumber} / {$pageCount}";
        $w    = $fontMetrics->get_text_width($text, $font, $size);
        $x    = $canvas->get_width() - $w - 20;
        $y    = $canvas->get_height() - 12;
        $canvas->text($x, $y, $text, $font, $size, [0, 0, 0]);
    });
}
</script>

{{-- ── INCLUDE SUB-TEMPLATE ────────────────────────────────────────────────── --}}
@includeFirst([$templateFile, 'pdf.offer-templates.modern'])

</body>
</html>
