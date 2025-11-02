<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erinnerung - Angebot {{ $offer->number }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 3px solid #f59e0b;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #f59e0b;
            margin: 0;
            font-size: 24px;
        }
        .company-name {
            font-size: 18px;
            color: #666;
            margin-top: 5px;
        }
        .reminder-badge {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .reminder-badge h3 {
            margin: 0 0 10px 0;
            color: #d97706;
            font-size: 16px;
        }
        .content {
            margin-bottom: 30px;
        }
        .offer-details {
            background-color: #f8f9fa;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
        }
        .offer-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .offer-details td {
            padding: 8px 0;
        }
        .offer-details td:first-child {
            font-weight: 600;
            color: #555;
            width: 40%;
        }
        .expiry-warning {
            background-color: #fee2e2;
            border: 2px solid #ef4444;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
        }
        .expiry-warning .label {
            font-size: 14px;
            color: #991b1b;
            margin-bottom: 5px;
        }
        .expiry-warning .days {
            font-size: 28px;
            font-weight: bold;
            color: #dc2626;
        }
        .cta-box {
            background-color: #eff6ff;
            border: 2px solid #3b82f6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .cta-box h4 {
            margin: 0 0 10px 0;
            color: #1e40af;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        .contact-info {
            margin-top: 20px;
            font-size: 13px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>⏰ Angebotserinnerung</h1>
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
                <div class="reminder-badge">
                    <h3>📌 Freundliche Erinnerung</h3>
                    <p style="margin: 0;">Unser Angebot läuft bald ab. Haben Sie schon eine Entscheidung getroffen?</p>
                </div>

                <p>vor einiger Zeit haben wir Ihnen ein Angebot unterbreitet. Wir möchten Sie freundlich daran erinnern und fragen, ob Sie bereits eine Entscheidung treffen konnten.</p>
            @else
                <div class="reminder-badge">
                    <h3>🚨 Angebot abgelaufen</h3>
                    <p style="margin: 0;">Unser Angebot ist abgelaufen, aber wir können es gerne für Sie verlängern.</p>
                </div>

                <p>unser Angebot ist zwischenzeitlich abgelaufen. Falls Sie noch Interesse haben, verlängern wir gerne die Gültigkeit oder unterbreiten Ihnen ein aktualisiertes Angebot.</p>
            @endif

            <div class="offer-details">
                <table>
                    <tr>
                        <td>Angebotsnummer:</td>
                        <td>{{ $offer->number }}</td>
                    </tr>
                    <tr>
                        <td>Angebotsdatum:</td>
                        <td>{{ \Carbon\Carbon::parse($offer->issue_date)->format('d.m.Y') }}</td>
                    </tr>
                    <tr>
                        <td>Gültig bis:</td>
                        <td>{{ $validUntil->format('d.m.Y') }}</td>
                    </tr>
                    <tr>
                        <td>Gesamtbetrag:</td>
                        <td><strong>{{ number_format($offer->total, 2, ',', '.') }} €</strong></td>
                    </tr>
                </table>
            </div>

            @if($daysRemaining > 0 && $daysRemaining <= 7)
                <div class="expiry-warning">
                    <div class="label">⚠️ Angebot läuft ab in</div>
                    <div class="days">{{ $daysRemaining }} Tag{{ $daysRemaining != 1 ? 'en' : '' }}</div>
                </div>
            @endif

            <div class="cta-box">
                <h4>💬 Haben Sie Fragen?</h4>
                <p style="margin: 0;">Wir stehen Ihnen gerne für Rückfragen zur Verfügung und beraten Sie bei Ihrer Entscheidung.</p>
            </div>

            @if($daysRemaining > 0)
                <p>Falls Sie weitere Informationen benötigen oder Anpassungen am Angebot wünschen, lassen Sie es uns gerne wissen. Wir freuen uns darauf, von Ihnen zu hören!</p>
            @else
                <p>Sollten Sie weiterhin Interesse haben, nehmen Sie gerne Kontakt mit uns auf. Wir erstellen Ihnen gerne ein aktualisiertes Angebot oder verlängern die Gültigkeit des bestehenden Angebots.</p>
            @endif

            <p>Mit freundlichen Grüßen<br>{{ $company->name }}</p>
        </div>

        <div class="contact-info">
            <p><strong>Kontaktieren Sie uns:</strong></p>
            @if($company->email)
            <p><strong>E-Mail:</strong> {{ $company->email }}</p>
            @endif
            @if($company->phone)
            <p><strong>Telefon:</strong> {{ $company->phone }}</p>
            @endif
            @if($company->website)
            <p><strong>Website:</strong> {{ $company->website }}</p>
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


