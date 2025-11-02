<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2. Mahnung</title>
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
            background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);
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
        .warning-box {
            background: #ffe5e5;
            border-left: 4px solid #e74c3c;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
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
            color: #d35400;
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
        .important {
            color: #e74c3c;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚨 2. Mahnung</h1>
    </div>

    <div class="content">
        <p>Sehr geehrte Damen und Herren,</p>

        <p><strong>trotz unserer bisherigen Mahnungen</strong> ist die Zahlung der nachfolgend aufgeführten Rechnung weiterhin ausgeblieben:</p>

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
                    <td style="padding: 8px 0;"><strong>Ursprüngliches Fälligkeitsdatum:</strong></td>
                    <td style="padding: 8px 0; text-align: right;">{{ $invoice->due_date->format('d.m.Y') }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0;"><strong>Tage überfällig:</strong></td>
                    <td style="padding: 8px 0; text-align: right;" class="important">{{ $invoice->getDaysOverdue() }} Tage</td>
                </tr>
            </table>

            <div style="text-align: center; margin-top: 20px;">
                <div style="color: #666; font-size: 14px;">Rechnungsbetrag</div>
                <div style="font-size: 20px; color: #666;">{{ number_format($invoice->total, 2, ',', '.') }} €</div>
                
                @if($invoice->reminder_fee > 0)
                <div style="color: #666; font-size: 14px; margin-top: 10px;">Bisherige Mahngebühren</div>
                <div style="font-size: 20px; color: #e67e22;">+ {{ number_format($invoice->reminder_fee, 2, ',', '.') }} €</div>
                @endif

                @if($fee > 0)
                <div style="color: #666; font-size: 14px; margin-top: 10px;">Mahngebühr (2. Mahnung)</div>
                <div style="font-size: 20px; color: #d35400;">+ {{ number_format($fee, 2, ',', '.') }} €</div>
                @endif

                <div style="border-top: 2px solid #333; margin: 15px 50px; padding-top: 10px;">
                    <div style="color: #666; font-size: 14px;">Gesamtbetrag</div>
                    <div class="amount">{{ number_format($invoice->total + $invoice->reminder_fee + $fee, 2, ',', '.') }} €</div>
                </div>
            </div>
        </div>

        <div class="warning-box">
            <strong>🚨 DRINGEND:</strong> Bitte begleichen Sie den offenen Betrag <strong>sofort, spätestens innerhalb von 7 Tagen</strong>. Andernfalls sind wir gezwungen, die 3. Mahnung zu versenden und anschließend rechtliche Schritte einzuleiten. Dies würde zu weiteren Kosten führen.
        </div>

        <p><strong>Sollten Sie Zahlungsschwierigkeiten haben, setzen Sie sich unverzüglich mit uns in Verbindung</strong>, um eine Ratenzahlung oder andere Lösungen zu besprechen. Ignorieren Sie diese Mahnung nicht!</p>

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


