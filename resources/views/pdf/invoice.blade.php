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

            if (!function_exists('pdfContinuationLogoMm')) {
                function pdfContinuationLogoMm(string $template, string $size): array {
                    $minimal = $template === 'minimal';
                    $height = match ($size) {
                        'small' => $minimal ? 10 : 14,
                        'large' => $minimal ? 24 : 28,
                        default => $minimal ? 16 : 22,
                    };
                    $width = match ($size) {
                        'small' => $minimal ? 38 : 50,
                        'large' => $minimal ? 72 : 90,
                        default => $minimal ? 54 : 70,
                    };

                    return [$height, $width];
                }
            }

            $logoOnEveryPage = (bool) ($layoutSettings['branding']['logo_on_every_page'] ?? false);
            $footerLastPageOnly = (bool) ($layoutSettings['content']['footer_last_page_only'] ?? false);
            [$continuationLogoHmm, $continuationLogoWmm] = pdfContinuationLogoMm(
                $template,
                (string) ($layoutSettings['branding']['logo_size'] ?? 'medium')
            );
            // 4mm above the logo and 4mm below it, so line items clear the image.
            $continuationMarginTop = $logoOnEveryPage ? ($continuationLogoHmm + 8) : 5;
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
            margin: {{ $continuationMarginTop }}mm 0 5mm 0;
        }
        @if($logoOnEveryPage)
        /* Page 1 keeps the in-flow 40mm letterhead, so its top margin
           stays 5mm and the DIN 5008 address window does not move.
           Later pages use the larger top margin reserved above. */
        @page :first {
            margin: 5mm 0;
        }
        @endif

        body {
            font-family: {{ $layoutSettings['fonts']['body'] ?? 'DejaVu Sans' }}, sans-serif;
            font-size: {{ $bodyFontSize }}px;
            line-height: 1.4;
            color: {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};
            background: white;
            /* Reserves the fixed 5-column footer so body content never
               runs into it. The last-page option still pins the footer
               here and covers it on earlier pages. */
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
            'bank_account_holder' => $company->bank_account_holder ?? '',
        ];
    } else {
        $snapshot = [];
    }

    // Backwards-compat: older stored snapshots may miss the logo even when the company has one.
    // Each logo upload also uses a unique filename and deletes the previous file, so a snapshot
    // may reference a logo path that no longer exists on disk — in that case fall back to the
    // company's current logo so historical documents still render a logo.
    if (isset($company) && !empty($company->logo ?? null)) {
        $snapLogo    = $snapshot['logo'] ?? null;
        $snapLogoRel = $snapLogo ? ltrim(preg_replace('#^storage/#', '', (string) $snapLogo), '/') : null;
        if (empty($snapLogoRel) || !\Storage::disk('public')->exists($snapLogoRel)) {
            $snapshot['logo'] = $company->logo;
        }
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

    {{-- Footer is a direct child of <body> with position:fixed, so DomPDF
         pins it to the bottom of every page. When it should appear only
         on the last page, page-script covers it on the pages before that. --}}
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

    // Sender identity shown in the return-address line and sign-off.
    // Sole traders (Einzelunternehmen) and freelancers (Freiberufler) are
    // legally identified by the owner's name, not a registered company name.
    $personalForms = ['einzelunternehmen', 'freiberufler'];
    $senderName = (in_array($snapshot['legal_form'] ?? null, $personalForms) && !empty($snapshot['managing_director']))
        ? $snapshot['managing_director']
        : ($snapshot['display_name'] ?? $snapshot['name'] ?? '');
@endphp

{{-- Debug indicator in preview mode --}}
@if(isset($preview) && $preview)
    <div style="position: fixed; top: 0; right: 0; background: rgba(59, 130, 246, 0.9); color: white; padding: 5px 10px; font-size: 10px; z-index: 9999; border-bottom-left-radius: 4px; font-weight: bold;">
        Template: {{ $template }} | Layout: {{ is_object($layout) ? ($layout->name ?? 'N/A') : ($layout['name'] ?? 'N/A') }}
    </div>
@endif

{{-- Include the specific template file based on layout->template --}}
@includeFirst([$templateFile, 'pdf.templates.minimal'])

@include('pdf.partials.page-script')

</body>
</html>
