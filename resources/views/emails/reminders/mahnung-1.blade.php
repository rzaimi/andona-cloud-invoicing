<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>1. Mahnung</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Inter', 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #1a1a1a;
            max-width: 600px;
            margin: 0 auto;
            padding: 40px 20px;
            background: #ffffff;
        }
        .header {
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e5e5e5;
        }
        .header h1 {
            font-size: 24px;
            font-weight: 600;
            color: #1a1a1a;
            margin: 0 0 8px 0;
            letter-spacing: -0.02em;
        }
        .header .subtitle {
            font-size: 14px;
            color: #d97706;
            margin: 0;
        }
        .content {
            color: #1a1a1a;
            font-size: 15px;
        }
        .content p {
            margin: 0 0 16px 0;
        }
        .notice {
            background: #fffbeb;
            padding: 20px;
            margin: 24px 0;
            font-size: 14px;
        }
        .notice strong {
            color: #d97706;
        }
        .invoice-details {
            background: #fafafa;
            padding: 24px;
            margin: 32px 0;
        }
        .invoice-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .invoice-details td {
            padding: 10px 0;
            font-size: 14px;
        }
        .invoice-details td:first-child {
            color: #666;
        }
        .invoice-details td:last-child {
            text-align: right;
            color: #1a1a1a;
            font-weight: 500;
        }
        .amount-section {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e5e5e5;
        }
        .amount-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
        }
        .amount-row.total {
            padding-top: 16px;
            border-top: 2px solid #1a1a1a;
            margin-top: 16px;
        }
        .amount-label {
            color: #666;
        }
        .amount-row.total .amount-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #1a1a1a;
        }
        .amount-value {
            font-weight: 600;
            color: #1a1a1a;
        }
        .amount-row.total .amount-value {
            font-size: 28px;
            color: #d97706;
        }
        .payment-info {
            background: #fafafa;
            padding: 20px;
            margin: 32px 0;
            font-size: 14px;
        }
        .payment-info div {
            margin: 4px 0;
            color: #1a1a1a;
        }
        .payment-info .label {
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 12px;
        }
        .footer {
            margin-top: 48px;
            padding-top: 24px;
            border-top: 1px solid #e5e5e5;
            text-align: center;
            font-size: 13px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>1. Mahnung</h1>
        <p class="subtitle">Zahlungserinnerung mit Mahngebühr</p>
    </div>

    <div class="content">
        <p>Sehr geehrte Damen und Herren,</p>

        <p>trotz Fälligkeit und unserer freundlichen Zahlungserinnerung haben wir bisher keine Zahlung für die folgende Rechnung erhalten:</p>

        <div class="invoice-details">
            <table>
                <tr>
                    <td>Rechnungsnummer</td>
                    <td>{{ $invoice->number }}</td>
                </tr>
                <tr>
                    <td>Rechnungsdatum</td>
                    <td>{{ $invoice->issue_date->format('d.m.Y') }}</td>
                </tr>
                <tr>
                    <td>Ursprüngliches Fälligkeitsdatum</td>
                    <td>{{ $invoice->due_date->format('d.m.Y') }}</td>
                </tr>
                <tr>
                    <td>Tage überfällig</td>
                    <td>{{ $invoice->getDaysOverdue() }} Tage</td>
                </tr>
            </table>

            <div class="amount-section">
                <div class="amount-row">
                    <span class="amount-label">Rechnungsbetrag</span>
                    <span class="amount-value">{{ number_format($invoice->total, 2, ',', '.') }} €</span>
                </div>
                @if($fee > 0)
                <div class="amount-row">
                    <span class="amount-label">Mahngebühr (1. Mahnung)</span>
                    <span class="amount-value">{{ number_format($fee, 2, ',', '.') }} €</span>
                </div>
                @endif
                <div class="amount-row total">
                    <span class="amount-label">Gesamtbetrag</span>
                    <span class="amount-value">{{ number_format($invoice->total + $fee, 2, ',', '.') }} €</span>
                </div>
            </div>
        </div>

        <div class="notice">
            <strong>Wichtig:</strong> Bitte begleichen Sie den offenen Betrag innerhalb der nächsten 7 Tage, um weitere Mahngebühren und rechtliche Schritte zu vermeiden.
        </div>

        <p>Falls Sie die Zahlung bereits veranlasst haben, betrachten Sie diese Mahnung bitte als gegenstandslos.</p>

        <p>Bei Fragen oder Zahlungsschwierigkeiten kontaktieren Sie uns bitte umgehend, um eine Lösung zu finden.</p>

        <div class="payment-info">
            <div class="label">Zahlungsdetails</div>
            <div>{{ $company->name }}</div>
            <div>IBAN: {{ $company->iban ?? 'N/A' }}</div>
            <div>BIC: {{ $company->bic ?? 'N/A' }}</div>
            <div style="margin-top: 12px;">Verwendungszweck: <strong>{{ $invoice->number }}</strong></div>
        </div>

        <p style="margin-top: 32px;">Mit freundlichen Grüßen,<br>
        <strong>{{ $company->name }}</strong></p>
    </div>

    <div class="footer">
        <div>{{ $company->name }}</div>
        <div>{{ $company->address }}</div>
        <div>Tel: {{ $company->phone }} | E-Mail: {{ $company->email }}</div>
    </div>
</body>
</html>
