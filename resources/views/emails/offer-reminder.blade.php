<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erinnerung - Angebot {{ $offer->number }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.7;
            color: #1a1a1a;
            max-width: 600px;
            margin: 0 auto;
            padding: 40px 20px;
            background-color: #ffffff;
        }
        .email-container {
            background-color: #ffffff;
            padding: 0;
        }
        .header {
            margin-bottom: 48px;
        }
        .header h1 {
            color: #1a1a1a;
            margin: 0 0 8px 0;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }
        .company-name {
            font-size: 13px;
            color: #666;
            font-weight: 400;
        }
        .content {
            margin-bottom: 48px;
        }
        .content p {
            margin: 0 0 20px 0;
            color: #1a1a1a;
            font-size: 15px;
        }
        .notice {
            padding: 20px 0;
            margin: 32px 0;
            font-size: 14px;
            color: #666;
        }
        .offer-details {
            background-color: transparent;
            padding: 0;
            margin: 32px 0;
        }
        .offer-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .offer-details td {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }
        .offer-details tr:last-child td {
            border-bottom: none;
        }
        .offer-details td:first-child {
            font-weight: 400;
            color: #666;
        }
        .offer-details td:last-child {
            text-align: right;
            color: #1a1a1a;
        }
        .footer {
            margin-top: 64px;
            padding-top: 32px;
            border-top: 1px solid #f0f0f0;
            font-size: 12px;
            color: #999;
            line-height: 1.6;
        }
        .footer p {
            margin: 0 0 4px 0;
        }
        .contact-info {
            margin-top: 32px;
            font-size: 13px;
            color: #666;
        }
        .contact-info p {
            margin: 0 0 6px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Angebotserinnerung</h1>
            <div class="company-name">{{ $company->name }}</div>
        </div>

        <div class="content">
            <p>Sehr geehrte Damen und Herren{{ $offer->customer ? ' von ' . $offer->customer->name : '' }},</p>
            
            @php
                $validUntil = \Carbon\Carbon::parse($offer->valid_until);
                $today = \Carbon\Carbon::now();
                $daysRemaining = $today->diffInDays($validUntil, false);
            @endphp

            @if($daysRemaining > 0)
                <div class="notice">
                    Unser Angebot läuft in {{ $daysRemaining }} Tag{{ $daysRemaining != 1 ? 'en' : '' }} ab.
                </div>

                <p>vor einiger Zeit haben wir Ihnen ein Angebot unterbreitet. Wir möchten Sie freundlich daran erinnern und fragen, ob Sie bereits eine Entscheidung treffen konnten.</p>
            @else
                <div class="notice">
                    Unser Angebot ist zwischenzeitlich abgelaufen.
                </div>

                <p>unser Angebot ist zwischenzeitlich abgelaufen. Falls Sie noch Interesse haben, verlängern wir gerne die Gültigkeit oder unterbreiten Ihnen ein aktualisiertes Angebot.</p>
            @endif

            <div class="offer-details">
                <table>
                    <tr>
                        <td>Angebotsnummer</td>
                        <td>{{ $offer->number }}</td>
                    </tr>
                    <tr>
                        <td>Angebotsdatum</td>
                        <td>{{ \Carbon\Carbon::parse($offer->issue_date)->format('d.m.Y') }}</td>
                    </tr>
                    <tr>
                        <td>Gültig bis</td>
                        <td>{{ $validUntil->format('d.m.Y') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Gesamtbetrag</strong></td>
                        <td><strong>{{ number_format($offer->total, 2, ',', '.') }} €</strong></td>
                    </tr>
                </table>
            </div>

            @if($daysRemaining > 0)
                <p>Falls Sie weitere Informationen benötigen oder Anpassungen am Angebot wünschen, lassen Sie es uns gerne wissen. Wir freuen uns darauf, von Ihnen zu hören!</p>
            @else
                <p>Sollten Sie weiterhin Interesse haben, nehmen Sie gerne Kontakt mit uns auf. Wir erstellen Ihnen gerne ein aktualisiertes Angebot oder verlängern die Gültigkeit des bestehenden Angebots.</p>
            @endif

            <p>Mit freundlichen Grüßen<br>{{ $company->name }}</p>
        </div>

        <div class="contact-info">
            @if($company->email)
            <p>{{ $company->email }}</p>
            @endif
            @if($company->phone)
            <p>{{ $company->phone }}</p>
            @endif
            @if($company->website)
            <p>{{ $company->website }}</p>
            @endif
        </div>

        <div class="footer">
            <p>{{ $company->name }}</p>
            @if($company->address || $company->postal_code || $company->city)
            <p>{{ $company->address }}, {{ $company->postal_code }} {{ $company->city }}</p>
            @endif
            @if($company->tax_number)
            <p>Steuernummer: {{ $company->tax_number }}</p>
            @endif
            @if($company->vat_number)
            <p>USt-IdNr.: {{ $company->vat_number }}</p>
            @endif
        </div>
    </div>
</body>
</html>
