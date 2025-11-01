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

        .container {
            max-width: 210mm;
            margin: 0 auto;
            padding: {{ $layout->settings['layout']['margin_top'] ?? '20' }}mm {{ $layout->settings['layout']['margin_right'] ?? '20' }}mm {{ $layout->settings['layout']['margin_bottom'] ?? '20' }}mm {{ $layout->settings['layout']['margin_left'] ?? '20' }}mm;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 20px;
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
            margin: 40px 0 30px 0;
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
            margin-bottom: 40px;
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

        .footer {
            margin-top: 50px;
            padding-top: 20px;
            @if($layout->settings['branding']['show_footer_line'] ?? true)
            border-top: 2px solid {{ $layout->settings['colors']['primary'] ?? '#3b82f6' }};
            @endif
            font-size: {{ ($layout->settings['fonts']['body_size'] ?? 11) - 1 }}px;
            color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }};
            text-align: center;
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
        @if($layout->settings['branding']['show_logo'] ?? true && $company->logo)
            <div class="logo">
                <img src="{{ public_path('storage/' . $company->logo) }}" alt="Logo" style="max-height: 80px; max-width: 200px;">
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
            <div class="section-title">Angebotempfänger</div>
            <strong>{{ $offer->customer->name }}</strong><br>
            @if($offer->customer->contact_person){{ $offer->customer->contact_person }}<br>@endif
            {{ $offer->customer->address }}<br>
            {{ $offer->customer->postal_code }} {{ $offer->customer->city }}<br>
            @if($offer->customer->country && $offer->customer->country !== 'Deutschland'){{ $offer->customer->country }}@endif
        </div>

        <div class="offer-details">
            <div class="section-title">Angebotsdetails</div>
            <div class="info-row">
                <span class="info-label">Angebotsnr.:</span>
                <span>{{ $offer->number }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Angebotsdatum:</span>
                <span>{{ \Carbon\Carbon::parse($offer->issue_date)->format('d.m.Y') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Gültig bis:</span>
                <span>{{ \Carbon\Carbon::parse($offer->valid_until)->format('d.m.Y') }}</span>
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
        <strong>Gültigkeitsdauer:</strong> Dieses Angebot ist gültig bis zum {{ \Carbon\Carbon::parse($offer->valid_until)->format('d.m.Y') }}
        @if(\Carbon\Carbon::parse($offer->valid_until)->isPast())
            <br><span style="color: #dc2626;">⚠️ Dieses Angebot ist abgelaufen</span>
        @endif
    </div>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
        <tr>
            <th style="width: 50%">Beschreibung</th>
            <th style="width: 10%" class="text-center">Menge</th>
            @if($layout->settings['content']['show_unit_column'] ?? true)
                <th style="width: 10%" class="text-center">Einheit</th>
            @endif
            <th style="width: 15%" class="text-right">Einzelpreis</th>
            <th style="width: 15%" class="text-right">Gesamtpreis</th>
        </tr>
        </thead>
        <tbody>
        @foreach($offer->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td class="text-center">{{ number_format($item->quantity, 2, ',', '.') }}</td>
                @if($layout->settings['content']['show_unit_column'] ?? true)
                    <td class="text-center">{{ $item->unit }}</td>
                @endif
                <td class="text-right">{{ number_format($item->unit_price, 2, ',', '.') }} €</td>
                <td class="text-right">{{ number_format($item->total, 2, ',', '.') }} €</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals">
        <table class="totals-table">
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

    <!-- Footer -->
    @if($layout->settings['branding']['show_footer'] ?? true)
        <div class="footer">
            @if($layout->settings['content']['show_bank_details'] ?? true)
                <p><strong>Bankverbindung:</strong>
                    @if($company->bank_name){{ $company->bank_name }} | @endif
                    @if($company->iban)IBAN: {{ $company->iban }} | @endif
                    @if($company->bic)BIC: {{ $company->bic }}@endif
                </p>
            @endif
            @if($layout->settings['content']['show_company_registration'] ?? true)
                <p>
                    @if($company->commercial_register){{ $company->commercial_register }} | @endif
                    @if($company->tax_number)Steuernummer: {{ $company->tax_number }} | @endif
                    @if($company->vat_number)USt-IdNr.: {{ $company->vat_number }}@endif
                </p>
            @endif
            <p><strong>Hinweis:</strong> Bei Annahme dieses Angebots wird eine entsprechende Rechnung erstellt.</p>
        </div>
    @endif
</div>
</body>
</html>
