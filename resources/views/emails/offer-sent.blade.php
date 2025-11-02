<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Angebot {{ $offer->number }}</title>
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
            border-bottom: 3px solid #22c55e;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #22c55e;
            margin: 0;
            font-size: 24px;
        }
        .company-name {
            font-size: 18px;
            color: #666;
            margin-top: 5px;
        }
        .content {
            margin-bottom: 30px;
        }
        .offer-details {
            background-color: #f8f9fa;
            border-left: 4px solid #22c55e;
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
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #22c55e;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: 600;
        }
        .button:hover {
            background-color: #16a34a;
        }
        .contact-info {
            margin-top: 20px;
            font-size: 13px;
            color: #666;
        }
        .highlight {
            background-color: #fef3c7;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Angebot {{ $offer->number }}</h1>
            <div class="company-name">{{ $company->name }}</div>
        </div>

        <div class="content">
            <p>Sehr geehrte Damen und Herren{{ $offer->customer ? ' von ' . $offer->customer->name : '' }},</p>
            
            <p>vielen Dank für Ihre Anfrage. Gerne unterbreiten wir Ihnen folgendes Angebot:</p>

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
                        <td>{{ \Carbon\Carbon::parse($offer->valid_until)->format('d.m.Y') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Gesamtbetrag:</strong></td>
                        <td><strong>{{ number_format($offer->total, 2, ',', '.') }} €</strong></td>
                    </tr>
                </table>
            </div>

            <div class="highlight">
                <p style="margin: 0;"><strong>⏰ Dieses Angebot ist gültig bis zum {{ \Carbon\Carbon::parse($offer->valid_until)->format('d.m.Y') }}</strong></p>
            </div>

            @if($offer->notes)
            <p><strong>Hinweise:</strong><br>{{ $offer->notes }}</p>
            @endif

            <p>Das vollständige Angebot mit allen Details finden Sie als PDF-Datei im Anhang dieser E-Mail.</p>

            <p>Wir würden uns freuen, Sie als Kunden begrüßen zu dürfen. Bei Fragen oder für weitere Informationen stehen wir Ihnen gerne zur Verfügung.</p>

            <p>Mit freundlichen Grüßen<br>{{ $company->name }}</p>
        </div>

        <div class="contact-info">
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


