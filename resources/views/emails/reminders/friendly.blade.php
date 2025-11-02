<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freundliche Zahlungserinnerung</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #ffffff;
            padding: 30px;
            border: 1px solid #e0e0e0;
        }
        .invoice-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .amount {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin: 10px 0;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 0 0 10px 10px;
            color: #666;
            font-size: 14px;
        }
        .button {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>💌 Freundliche Zahlungserinnerung</h1>
    </div>

    <div class="content">
        <p>Sehr geehrte Damen und Herren,</p>

        <p>dies ist eine freundliche Erinnerung bezüglich der ausstehenden Zahlung für folgende Rechnung:</p>

        <div class="invoice-details">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0;"><strong>Rechnungsnummer:</strong></td>
                    <td style="padding: 8px 0; text-align: right;">{{ $invoice->number }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0;"><strong>Rechnungsdatum:</strong></td>
                    <td style="padding: 8px 0; text-align: right;">{{ $invoice->issue_date->format('d.m.Y') }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0;"><strong>Fälligkeitsdatum:</strong></td>
                    <td style="padding: 8px 0; text-align: right;">{{ $invoice->due_date->format('d.m.Y') }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0;"><strong>Tage überfällig:</strong></td>
                    <td style="padding: 8px 0; text-align: right; color: #e74c3c;">{{ $invoice->getDaysOverdue() }} Tage</td>
                </tr>
            </table>

            <div style="text-align: center; margin-top: 20px;">
                <div style="color: #666; font-size: 14px;">Offener Betrag</div>
                <div class="amount">{{ number_format($invoice->total, 2, ',', '.') }} €</div>
            </div>
        </div>

        <p>Falls Sie die Zahlung bereits veranlasst haben, betrachten Sie diese E-Mail bitte als gegenstandslos.</p>

        <p>Sollten Sie Fragen zur Rechnung haben oder eine Ratenzahlung vereinbaren möchten, zögern Sie nicht, uns zu kontaktieren.</p>

        <div style="text-align: center;">
            <a href="{{ config('app.url') }}" class="button">Zur Rechnung</a>
        </div>

        <p style="margin-top: 30px;"><strong>Zahlungsdetails:</strong></p>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; font-size: 14px;">
            <div><strong>Bankverbindung:</strong></div>
            <div>{{ $company->name }}</div>
            <div>IBAN: {{ $company->iban ?? 'N/A' }}</div>
            <div>BIC: {{ $company->bic ?? 'N/A' }}</div>
            <div style="margin-top: 10px;"><strong>Verwendungszweck:</strong> {{ $invoice->number }}</div>
        </div>

        <p style="margin-top: 30px;">Mit freundlichen Grüßen,<br>
        <strong>{{ $company->name }}</strong></p>
    </div>

    <div class="footer">
        <p>{{ $company->name }}<br>
        {{ $company->address }}<br>
        Tel: {{ $company->phone }} | E-Mail: {{ $company->email }}</p>
    </div>
</body>
</html>


