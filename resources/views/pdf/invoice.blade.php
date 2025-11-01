<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rechnung {{ $invoice->number }}</title>
    <style>
        @php
            // Helper function to convert font size string to pixels
            function getFontSizePx($size) {
                switch($size) {
                    case 'small': return 11;
                    case 'large': return 14;
                    case 'medium':
                    default: return 12;
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
            padding: {{ $layout->settings['layout']['margin_top'] ?? '20' }}mm {{ $layout->settings['layout']['margin_right'] ?? '20' }}mm {{ $layout->settings['layout']['margin_bottom'] ?? '20' }}mm {{ $layout->settings['layout']['margin_left'] ?? '20' }}mm;
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

<div class="container">
    <!-- Header: Company name and logo -->
    <div class="header">
        <div class="company-header-line">
            <span class="company-name">{{ $company->name }}</span> | 
            @if($layout->settings['content']['show_company_address'] ?? true)
                {{ $company->address ?? '' }} | 
                {{ $company->postal_code ?? '' }} {{ $company->city ?? '' }}
            @endif
        </div>
        @if(isset($layout->settings['branding']['show_logo']) && $layout->settings['branding']['show_logo'])
            <div class="logo-container">
                @if($company->logo)
                    <img src="{{ public_path('storage/' . $company->logo) }}" alt="Logo">
                @else
                    <span>Ihr Logo</span>
                @endif
            </div>
        @endif
        <div class="clearfix"></div>
    </div>

    <!-- Customer Address Block -->
    <div class="customer-block">
        <strong>{{ $invoice->customer->name }}</strong>
        @if($invoice->customer->contact_person)
            <span class="contact-person">{{ $invoice->customer->contact_person }}</span>
        @endif
        <div>
            {{ $invoice->customer->address }}<br>
            {{ $invoice->customer->postal_code }} {{ $invoice->customer->city }}
            @if($invoice->customer->country && $invoice->customer->country !== 'Deutschland')
                <br>{{ $invoice->customer->country }}
            @endif
        </div>
    </div>

    <!-- Invoice Meta Row: Customer number, Delivery date, Invoice date -->
    <div class="invoice-meta-row">
        @if($layout->settings['content']['show_customer_number'] ?? true && $invoice->customer->number)
            <div class="meta-item">
                <span class="meta-label">Kundennummer</span>
                <span class="meta-value">{{ $invoice->customer->number }}</span>
            </div>
        @endif
        <div class="meta-item">
            <span class="meta-label">Liefer-/Leistungsdatum</span>
            <span class="meta-value">{{ \Carbon\Carbon::parse($invoice->issue_date)->format('d.m.Y') }}</span>
        </div>
        <div class="meta-item">
            <span class="meta-label">Rechnungsdatum</span>
            <span class="meta-value">{{ \Carbon\Carbon::parse($invoice->issue_date)->format('d.m.Y') }}</span>
        </div>
        <div class="clearfix"></div>
    </div>

    <!-- Invoice Number -->
    <div class="invoice-number">Rechnung Nr. {{ $invoice->number }}</div>

    <!-- Introductory Text -->
    <div class="intro-text">
        Wir bedanken uns für die gute Zusammenarbeit und stellen Ihnen vereinbarungsgemäß folgende Lieferungen und Leistungen in Rechnung:
    </div>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
        <tr>
            <th style="width: 5%">Pos.</th>
            <th style="width: 45%">Bezeichnung</th>
            <th style="width: 20%" class="text-center">Menge</th>
            <th style="width: 15%" class="text-right">Einzel (€)</th>
            <th style="width: 15%" class="text-right">Gesamt (€)</th>
        </tr>
        </thead>
        <tbody>
        @foreach($invoice->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->description }}</td>
                <td class="text-center">
                    {{ number_format($item->quantity, 2, ',', '.') }}
                    @if($layout->settings['content']['show_unit_column'] ?? true && $item->unit)
                        {{ $item->unit }}
                    @else
                        Stück
                    @endif
                </td>
                <td class="text-right">{{ number_format($item->unit_price, 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($item->total, 2, ',', '.') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals">
        <table class="totals-table">
            <tr>
                <td>Summe Netto</td>
                <td class="text-right">€ {{ number_format($invoice->subtotal, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Umsatzsteuer {{ number_format($invoice->tax_rate * 100, 2, ',', '.') }}%</td>
                <td class="text-right">€ {{ number_format($invoice->tax_amount, 2, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td>Rechnungsbetrag</td>
                <td class="text-right">€ {{ number_format($invoice->total, 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <!-- Payment Terms and Signature -->
    <div class="payment-section">
        @if($layout->settings['content']['show_payment_terms'] ?? true)
            <div class="payment-terms">
                Zahlung innerhalb von {{ \Carbon\Carbon::parse($invoice->due_date)->diffInDays(\Carbon\Carbon::parse($invoice->issue_date)) }} Tagen ab Rechnungseingang ohne Abzüge an die unten angegebene Bankverbindung.
            </div>
        @endif
        
        <div class="greeting">Mit freundlichen Grüßen</div>
        
        @if($company->managing_director)
            <div class="signature">{{ $company->managing_director }}</div>
        @endif
    </div>

    <!-- Footer: Three Columns -->
    @if($layout->settings['branding']['show_footer'] ?? true)
        <div class="footer">
            <div class="footer-columns">
                <!-- Left Column: Company Information -->
                <div class="footer-column">
                    <strong>{{ $company->name }}</strong><br>
                    @if($company->address){{ $company->address }}<br>@endif
                    @if($company->postal_code && $company->city){{ $company->postal_code }} {{ $company->city }}<br>@endif
                    @if($company->vat_number)Ust-IdNr.: {{ $company->vat_number }}@endif
                </div>

                <!-- Middle Column: Contact Information -->
                <div class="footer-column">
                    @if($company->phone)Tel.: {{ $company->phone }}<br>@endif
                    @if($company->email)E-Mail: {{ $company->email }}<br>@endif
                    @if($company->website)Web: {{ $company->website }}@endif
                </div>

                <!-- Right Column: Bank Information -->
                <div class="footer-column">
                    @if($layout->settings['content']['show_bank_details'] ?? true)
                        @if($company->bank_name){{ $company->bank_name }}<br>@endif
                        @if($company->bank_iban)IBAN: {{ $company->bank_iban }}<br>@endif
                        @if($company->bank_bic)BIC: {{ $company->bank_bic }}@endif
                    @endif
                </div>
                <div class="clearfix"></div>
            </div>
        </div>
    @endif
</div>
</body>
</html>

