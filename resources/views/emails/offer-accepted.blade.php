<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Angebot angenommen - {{ $offer->number }}</title>
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
        .celebration-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            margin: 20px 0;
            border-radius: 8px;
            text-align: center;
        }
        .celebration-badge .icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .celebration-badge h3 {
            margin: 0 0 5px 0;
            font-size: 20px;
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
        .next-steps {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin: 20px 0;
        }
        .next-steps h4 {
            margin: 0 0 10px 0;
            color: #1e40af;
        }
        .next-steps ul {
            margin: 0;
            padding-left: 20px;
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
            <h1>🎉 Angebot angenommen</h1>
            <div class="company-name">{{ $company->name }}</div>
        </div>

        <div class="content">
            <div class="celebration-badge">
                <div class="icon">🎊</div>
                <h3>Vielen Dank für Ihre Zusage!</h3>
                <p style="margin: 5px 0 0 0;">Wir freuen uns auf die Zusammenarbeit</p>
            </div>

            <p>Sehr geehrte Damen und Herren{{ $offer->customer ? ' von ' . $offer->customer->name : '' }},</p>
            
            <p>herzlichen Dank für die Annahme unseres Angebots! Wir freuen uns sehr, dass Sie sich für uns entschieden haben und werden unser Bestes geben, um Ihre Erwartungen zu übertreffen.</p>

            <div class="offer-details">
                <table>
                    <tr>
                        <td>Angebotsnummer:</td>
                        <td>{{ $offer->number }}</td>
                    </tr>
                    <tr>
                        <td>Angenommen am:</td>
                        <td>{{ \Carbon\Carbon::now()->format('d.m.Y') }}</td>
                    </tr>
                    <tr>
                        <td>Gesamtbetrag:</td>
                        <td><strong>{{ number_format($offer->total, 2, ',', '.') }} €</strong></td>
                    </tr>
                    <tr>
                        <td>Status:</td>
                        <td><strong style="color: #16a34a;">✓ Angenommen</strong></td>
                    </tr>
                </table>
            </div>

            <div class="next-steps">
                <h4>📋 Nächste Schritte</h4>
                <ul>
                    <li>Sie erhalten in Kürze eine Auftragsbestätigung</li>
                    <li>Die offizielle Rechnung wird nach Abschluss der Arbeiten erstellt</li>
                    <li>Wir werden uns zeitnah mit Ihnen in Verbindung setzen, um die weiteren Details zu besprechen</li>
                    @if(isset($startDate))
                    <li>Geplanter Projektstart: {{ $startDate }}</li>
                    @endif
                </ul>
            </div>

            @if($offer->notes)
            <p><strong>Hinweise:</strong><br>{{ $offer->notes }}</p>
            @endif

            <p>Sollten Sie vorab noch Fragen haben oder weitere Informationen benötigen, zögern Sie bitte nicht, uns zu kontaktieren.</p>

            <p><strong>Nochmals vielen Dank für Ihr Vertrauen!</strong></p>

            <p>Mit freundlichen Grüßen<br>{{ $company->name }}</p>
        </div>

        <div class="contact-info">
            <p><strong>Ihr Ansprechpartner:</strong></p>
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


