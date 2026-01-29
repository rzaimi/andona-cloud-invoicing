<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Angebot {{ $offer->number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: {{ $layout->settings['fonts']['body'] ?? 'DejaVu Sans' }}, sans-serif;
            font-size: {{ $layout->settings['fonts']['body_size'] ?? '11' }}px;
            line-height: 1.4;
            color: {{ $layout->settings['colors']['text'] ?? '#1f2937' }};
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
            font-size: {{ $layout->settings['fonts']['body_size'] ?? 11 }}px;
            line-height: 1.3;
            color: {{ $layout->settings['colors']['text'] ?? '#1f2937' }};
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
            padding: {{ min($layout->settings['layout']['margin_top'] ?? 20, 25) }}mm {{ min($layout->settings['layout']['margin_right'] ?? 20, 25) }}mm {{ max(min($layout->settings['layout']['margin_bottom'] ?? 20, 25) + 60, 80) }}mm {{ min($layout->settings['layout']['margin_left'] ?? 20, 25) }}mm;
            position: relative; /* For absolute positioning of address block */
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 15px;
            @if($layout->settings['branding']['show_header_line'] ?? true)
            border-bottom: 2px solid {{ $layout->settings['colors']['primary'] ?? '#3b82f6' }};
        @endif
}

        .company-info {
            flex: 1;
        }

        .company-name {
            font-family: {{ $layout->settings['fonts']['heading'] ?? 'DejaVu Sans' }}, sans-serif;
            font-size: {{ ($layout->settings['fonts']['heading_size'] ?? 16) + 4 }}px;
            font-weight: bold;
            color: {{ $layout->settings['colors']['primary'] ?? '#3b82f6' }};
            margin-bottom: 10px;
        }

        .company-details {
            font-size: {{ ($layout->settings['fonts']['body_size'] ?? 11) - 1 }}px;
            color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }};
            line-height: 1.3;
        }

        .logo {
            flex-shrink: 0;
            margin-left: 20px;
        }

        .offer-title {
            text-align: center;
            margin: 25px 0 20px 0;
        }

        .offer-title h1 {
            font-family: {{ $layout->settings['fonts']['heading'] ?? 'DejaVu Sans' }}, sans-serif;
            font-size: {{ ($layout->settings['fonts']['heading_size'] ?? 16) + 8 }}px;
            color: {{ $layout->settings['colors']['primary'] ?? '#3b82f6' }};
            margin-bottom: 10px;
        }

        .offer-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
        }

        .customer-info, .offer-details {
            flex: 1;
        }

        .customer-info {
            margin-right: 40px;
        }

        .section-title {
            font-family: {{ $layout->settings['fonts']['heading'] ?? 'DejaVu Sans' }}, sans-serif;
            font-size: {{ ($layout->settings['fonts']['heading_size'] ?? 16) - 2 }}px;
            font-weight: bold;
            color: {{ $layout->settings['colors']['secondary'] ?? '#1f2937' }};
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid {{ $layout->settings['colors']['accent'] ?? '#e5e7eb' }};
        }

        .info-row {
            display: flex;
            margin-bottom: 5px;
        }

        .info-label {
            font-weight: bold;
            width: 120px;
            color: {{ $layout->settings['colors']['secondary'] ?? '#374151' }};
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
        }

        .items-table th {
            background-color: {{ $layout->settings['colors']['primary'] ?? '#3b82f6' }};
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-family: {{ $layout->settings['fonts']['heading'] ?? 'DejaVu Sans' }}, sans-serif;
            font-size: {{ ($layout->settings['fonts']['body_size'] ?? 11) - 1 }}px;
            font-weight: bold;
        }

        .items-table td {
            padding: 10px 8px;
            border-bottom: 1px solid {{ $layout->settings['colors']['accent'] ?? '#e5e7eb' }};
        }

        .items-table tr:nth-child(even) {
            background-color: #f9fafb;
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
            padding: 8px 12px;
            border-bottom: 1px solid {{ $layout->settings['colors']['accent'] ?? '#e5e7eb' }};
        }

        .totals-table .total-row {
            background-color: {{ $layout->settings['colors']['primary'] ?? '#3b82f6' }};
            color: white;
            font-weight: bold;
            font-size: {{ ($layout->settings['fonts']['body_size'] ?? 11) + 1 }}px;
        }

        .notes, .terms {
            clear: both;
            margin-top: 30px;
            padding-top: 20px;
            @if($layout->settings['content']['show_notes_border'] ?? true)
            border-top: 1px solid {{ $layout->settings['colors']['accent'] ?? '#e5e7eb' }};
        @endif
}

        .validity-notice {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 15px;
            margin: 30px 0;
            text-align: center;
        }

        .validity-notice strong {
            color: #92400e;
        }

        .pdf-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            padding: 10px {{ $layout->settings['layout']['margin_right'] ?? 20 }}mm 10px {{ $layout->settings['layout']['margin_left'] ?? 20 }}mm;
            border-top: 1px solid #e5e7eb;
            font-size: {{ ($layout->settings['fonts']['body_size'] ?? 11) - 1 }}px;
            color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }};
            background-color: white;
            z-index: 1000;
        }

        @page {
            margin-bottom: 50mm; /* Reserve space for fixed footer */
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: {{ ($layout->settings['fonts']['body_size'] ?? 11) - 1 }}px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-draft { background-color: #fef3c7; color: #92400e; }
        .status-sent { background-color: #dbeafe; color: #1e40af; }
        .status-accepted { background-color: #d1fae5; color: #065f46; }
        .status-rejected { background-color: #fee2e2; color: #991b1b; }

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

{{-- DIN 5008 compliant address block for envelope window (positioned absolutely) --}}
@php
    $customer = $offer->customer ?? null;
    $offerBodyFontSize = isset($layout->settings['fonts']['size']) ? getFontSizePx($layout->settings['fonts']['size']) : (isset($layout->settings['fonts']['body_size']) ? (int)$layout->settings['fonts']['body_size'] : 11);
@endphp
@if($customer)
    <div class="din-5008-address">
        <div style="font-weight: 600; margin-bottom: 3px; font-size: {{ $offerBodyFontSize }}px; line-height: 1.2;">
            {{ $customer->name ?? 'Unbekannt' }}
        </div>
        @if(isset($customer->contact_person) && $customer->contact_person)
            <div style="margin-bottom: 2px; font-size: {{ $offerBodyFontSize }}px; line-height: 1.2;">{{ $customer->contact_person }}</div>
        @endif
        <div style="font-size: {{ $offerBodyFontSize }}px; line-height: 1.2;">
            @if($customer->address)
                {{ $customer->address }}<br>
            @endif
            @if($customer->postal_code && $customer->city)
                {{ $customer->postal_code }} {{ $customer->city }}
                @if($customer->country && $customer->country !== 'Deutschland')
                    <br>{{ $customer->country }}
                @endif
            @endif
        </div>
    </div>
@endif

{{-- Footer must be defined early and as direct child of body for DomPDF fixed positioning --}}
@php
    // Use saved snapshot instead of live company data to preserve footer information
    // Handle both model instance and stdClass/array (DomPDF may convert models)
    if (is_object($offer) && method_exists($offer, 'getCompanySnapshot')) {
        $snapshot = $offer->getCompanySnapshot();
    } elseif (isset($offer->company_snapshot) && is_array($offer->company_snapshot)) {
        $snapshot = $offer->company_snapshot;
    } elseif (is_array($offer) && isset($offer['company_snapshot']) && is_array($offer['company_snapshot'])) {
        $snapshot = $offer['company_snapshot'];
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
    
    // Get date format from settings or use default
    $dateFormat = $settings['date_format'] ?? 'd.m.Y';
    
    // Helper function for date formatting
    if (!function_exists('formatInvoiceDate')) {
        function formatInvoiceDate($date, $format = 'd.m.Y') {
            return \Carbon\Carbon::parse($date)->format($format);
        }
    }
    
    // Helper function to convert font size string to pixels
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
    
    // Calculate body font size
    $bodyFontSize = isset($layout->settings['fonts']['size']) ? getFontSizePx($layout->settings['fonts']['size']) : (isset($layout->settings['fonts']['body_size']) ? (int)$layout->settings['fonts']['body_size'] : 12);
@endphp
@if($layout->settings['branding']['show_footer'] ?? true)
    <div class="pdf-footer" style="border-top: {{ $layout->settings['branding']['show_footer_line'] ?? true ? '2px solid ' . ($layout->settings['colors']['primary'] ?? '#3b82f6') : '1px solid #e5e7eb' }}; text-align: center;">
        @if($layout->settings['content']['show_bank_details'] ?? true)
            <p><strong>Bankverbindung:</strong>
                @if($snapshot['bank_name'] ?? null){{ $snapshot['bank_name'] }} | @endif
                @if($snapshot['bank_iban'] ?? null)IBAN: {{ $snapshot['bank_iban'] }} | @endif
                @if($snapshot['bank_bic'] ?? null)BIC: {{ $snapshot['bank_bic'] }}@endif
            </p>
        @endif
        @if($layout->settings['content']['show_company_registration'] ?? true)
            <p>
                @if($snapshot['commercial_register'] ?? null){{ $snapshot['commercial_register'] }} | @endif
                @if($snapshot['tax_number'] ?? null)Steuernummer: {{ $snapshot['tax_number'] }} | @endif
                @if($snapshot['vat_number'] ?? null)USt-IdNr.: {{ $snapshot['vat_number'] }}@endif
            </p>
        @endif
        @if($snapshot['address'] ?? null){{ $snapshot['address'] }}@endif
        @if(($snapshot['postal_code'] ?? null) && ($snapshot['city'] ?? null)), {{ $snapshot['postal_code'] }} {{ $snapshot['city'] }}@endif
        @if(isset($settings['offer_footer']) && !empty($settings['offer_footer']))
            <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid {{ $layout->settings['colors']['accent'] ?? '#e5e7eb' }}; font-size: {{ $bodyFontSize - 1 }}px;">
                {{ $settings['offer_footer'] }}
            </div>
        @endif
        @if($snapshot['email'] ?? null) · {{ $snapshot['email'] }}@endif
        @if($snapshot['phone'] ?? null) · {{ $snapshot['phone'] }}@endif
    </div>

    {{-- Page number (bottom-right inside footer area) --}}
    <script type="text/php">
    if (isset($pdf)) {
        $pdf->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
            $font = $fontMetrics->get_font("DejaVu Sans", "normal");
            $size = 7;
            $text = "Seite {$pageNumber} / {$pageCount}";
            $w = $fontMetrics->get_text_width($text, $font, $size);
            $x = $canvas->get_width() - $w - 6;
            $y = $canvas->get_height() - 14;
            $canvas->text($x, $y, $text, $font, $size, [0, 0, 0]);
        });
    }
    </script>
@endif

<div class="container">
    <!-- Header -->
    <div class="header">
        <div class="company-info">
            <div class="company-name">{{ $company->name }}</div>
            <div class="company-details">
                @if($layout->settings['content']['show_company_address'] ?? true)
                    {{ $company->address }}<br>
                    {{ $company->postal_code }} {{ $company->city }}<br>
                @endif
                @if($layout->settings['content']['show_company_contact'] ?? true)
                    @if($company->phone)Tel: {{ $company->phone }}<br>@endif
                    @if($company->email)E-Mail: {{ $company->email }}<br>@endif
                    @if($company->website)Web: {{ $company->website }}@endif
                @endif
            </div>
        </div>
        @php
            $logoRelPath = isset($snapshot['logo']) ? ltrim(preg_replace('#^storage/#', '', (string)$snapshot['logo']), '/') : null;
        @endphp
        @if(($layout->settings['branding']['show_logo'] ?? true) && $logoRelPath)
            <div class="logo">
                @if(isset($preview) && $preview)
                    <img src="{{ asset('storage/' . $logoRelPath) }}" alt="Logo" style="max-height: 80px; max-width: 200px;">
                @elseif(\Storage::disk('public')->exists($logoRelPath))
                    @php
                        $logoPath = \Storage::disk('public')->path($logoRelPath);
                        $logoData = base64_encode(file_get_contents($logoPath));
                        $logoMime = mime_content_type($logoPath);
                    @endphp
                    <img src="data:{{ $logoMime }};base64,{{ $logoData }}" alt="Logo" style="max-height: 80px; max-width: 200px;">
                @endif
            </div>
        @endif
    </div>

    <!-- Offer Title -->
    <div class="offer-title">
        <h1>ANGEBOT</h1>
        <div class="status-badge status-{{ $offer->status }}">{{ ucfirst($offer->status) }}</div>
    </div>

    <!-- Offer Meta Information -->
    <div class="offer-meta">
        <div class="customer-info">
            {{-- Receiver address is always rendered in DIN 5008 window (absolute). Nothing to render here. --}}
        </div>

        <div class="offer-details">
            <div class="section-title">Angebotsdetails</div>
            <div class="info-row">
                <span class="info-label">Angebotsnr.:</span>
                <span>{{ $offer->number }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Angebotsdatum:</span>
                <span>{{ formatInvoiceDate($offer->issue_date, $dateFormat ?? 'd.m.Y') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Gültig bis:</span>
                <span>{{ formatInvoiceDate($offer->valid_until, $dateFormat ?? 'd.m.Y') }}</span>
            </div>
            @if($layout->settings['content']['show_customer_number'] ?? true && $offer->customer->customer_number)
                <div class="info-row">
                    <span class="info-label">Kundennummer:</span>
                    <span>{{ $offer->customer->customer_number }}</span>
                </div>
            @endif
            @if($layout->settings['content']['show_tax_number'] ?? true && $company->tax_number)
                <div class="info-row">
                    <span class="info-label">Steuernummer:</span>
                    <span>{{ $company->tax_number }}</span>
                </div>
            @endif
        </div>
    </div>

    <!-- Validity Notice -->
    <div class="validity-notice">
        <strong>Gültigkeitsdauer:</strong> Dieses Angebot ist gültig bis zum {{ formatInvoiceDate($offer->valid_until, $dateFormat ?? 'd.m.Y') }}
        @if(\Carbon\Carbon::parse($offer->valid_until)->isPast())
            <br><span style="color: #dc2626;">⚠️ Dieses Angebot ist abgelaufen</span>
        @endif
    </div>

    @php
        $totalDiscount = 0;
        foreach ($offer->items as $it) {
            $totalDiscount += (float)($it->discount_amount ?? 0);
        }
    @endphp

    <!-- Items Table -->
    <table class="items-table">
        <thead>
        <tr>
            @if(($layout->settings['content']['show_item_codes'] ?? false))
                <th style="width: 12%">Produkt-Nr.</th>
            @endif
            <th style="width: 42%">Beschreibung</th>
            <th style="width: 8%" class="text-center">Menge</th>
            @if($layout->settings['content']['show_unit_column'] ?? true)
                <th style="width: 8%" class="text-center">Einheit</th>
            @endif
            <th style="width: 8%" class="text-right">USt.</th>
            <th style="width: 14%" class="text-right">Einzelpreis</th>
            <th style="width: 13%" class="text-right">Rabatt</th>
            <th style="width: 15%" class="text-right">Gesamtpreis</th>
        </tr>
        </thead>
        <tbody>
        @foreach($offer->items as $item)
            @php
                $discountAmount = (float)($item->discount_amount ?? 0);
                $hasDiscount = $discountAmount > 0.0001;
                $baseTotal = (float)($item->quantity ?? 0) * (float)($item->unit_price ?? 0);
                $discountType = $item->discount_type ?? null;
                $discountValue = $item->discount_value ?? null;
                $productCode = data_get($item, 'product.number')
                    ?? data_get($item, 'product.sku')
                    ?? data_get($item, 'product_number')
                    ?? data_get($item, 'product_sku');
            @endphp
            <tr>
                @if(($layout->settings['content']['show_item_codes'] ?? false))
                    <td>{{ $productCode ?: '-' }}</td>
                @endif
                <td>{{ $item->description }}</td>
                <td class="text-center">{{ number_format($item->quantity, 2, ',', '.') }}</td>
                @if($layout->settings['content']['show_unit_column'] ?? true)
                    <td class="text-center">{{ $item->unit }}</td>
                @endif
                <td class="text-right">{{ number_format(($offer->tax_rate ?? 0) * 100, 0, ',', '.') }}%</td>
                <td class="text-right">{{ number_format($item->unit_price, 2, ',', '.') }} €</td>
                <td class="text-right">
                    @if($hasDiscount)
                        <div>
                            @if($discountType === 'percentage')
                                {{ number_format((float)$discountValue, 0, ',', '.') }}%
                            @elseif($discountType === 'fixed')
                                {{ number_format((float)$discountValue, 2, ',', '.') }} €
                            @else
                                Rabatt
                            @endif
                        </div>
                        <div style="font-size: 10px; color: #dc2626;">-{{ number_format($discountAmount, 2, ',', '.') }} €</div>
                    @else
                        -
                    @endif
                </td>
                <td class="text-right">{{ number_format($item->total, 2, ',', '.') }} €</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals">
        <table class="totals-table">
            @if($totalDiscount > 0.0001)
                <tr>
                    <td><strong>Zwischensumme (vor Rabatt):</strong></td>
                    <td class="text-right">{{ number_format($offer->subtotal + $totalDiscount, 2, ',', '.') }} €</td>
                </tr>
                <tr>
                    <td><strong>Gesamtrabatt:</strong></td>
                    <td class="text-right" style="color: #dc2626;">-{{ number_format($totalDiscount, 2, ',', '.') }} €</td>
                </tr>
            @endif
            <tr>
                <td><strong>Zwischensumme:</strong></td>
                <td class="text-right">{{ number_format($offer->subtotal, 2, ',', '.') }} €</td>
            </tr>
            <tr>
                <td><strong>MwSt. ({{ number_format($offer->tax_rate * 100, 0) }}%):</strong></td>
                <td class="text-right">{{ number_format($offer->tax_amount, 2, ',', '.') }} €</td>
            </tr>
            <tr class="total-row">
                <td><strong>Gesamtbetrag:</strong></td>
                <td class="text-right"><strong>{{ number_format($offer->total, 2, ',', '.') }} €</strong></td>
            </tr>
        </table>
    </div>

    <!-- Notes -->
    @if($offer->notes && ($layout->settings['content']['show_notes'] ?? true))
        <div class="notes">
            <div class="section-title">Anmerkungen</div>
            <p>{{ $offer->notes }}</p>
        </div>
    @endif

    <!-- Terms -->
    @if($offer->terms && ($layout->settings['content']['show_terms'] ?? true))
        <div class="terms">
            <div class="section-title">Angebotsbedingungen</div>
            <p>{{ $offer->terms }}</p>
        </div>
    @endif

</div>

</body>
</html>
