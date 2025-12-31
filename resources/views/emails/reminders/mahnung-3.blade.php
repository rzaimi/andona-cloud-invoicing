<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3. und letzte Mahnung</title>
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
            border-bottom: 2px solid #1a1a1a;
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
            color: #dc2626;
            margin: 0;
            font-weight: 600;
        }
        .content {
            color: #1a1a1a;
            font-size: 15px;
        }
        .content p {
            margin: 0 0 16px 0;
        }
        .critical-notice {
            background: #fef2f2;
            padding: 24px;
            margin: 32px 0;
            font-size: 14px;
            text-align: center;
        }
        .critical-notice .title {
            font-size: 16px;
            font-weight: 600;
            color: #dc2626;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .critical-notice .deadline {
            font-size: 32px;
            font-weight: 600;
            color: #dc2626;
            margin: 16px 0;
        }
        .critical-notice ul {
            text-align: left;
            display: inline-block;
            margin: 16px 0;
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
            font-size: 32px;
            color: #dc2626;
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
        <h1>3. und letzte Mahnung</h1>
        <p class="subtitle">Letzte Zahlungsaufforderung vor rechtlichen Schritten</p>
    </div>

    <div class="content">
        <p>Sehr geehrte Damen und Herren,</p>

        <p><strong>Dies ist unsere letzte Mahnung</strong> vor Einleitung rechtlicher Schritte.</p>

        <p>Trotz mehrfacher Zahlungsaufforderungen ist die Zahlung der nachfolgend aufgeführten Rechnung bis heute ausgeblieben:</p>

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
                @if($invoice->reminder_fee > 0)
                <div class="amount-row">
                    <span class="amount-label">Bisherige Mahngebühren</span>
                    <span class="amount-value">{{ number_format($invoice->reminder_fee, 2, ',', '.') }} €</span>
                </div>
                @endif
                @if($fee > 0)
                <div class="amount-row">
                    <span class="amount-label">Mahngebühr (3. Mahnung)</span>
                    <span class="amount-value">{{ number_format($fee, 2, ',', '.') }} €</span>
                </div>
                @endif
                <div class="amount-row total">
                    <span class="amount-label">Gesamtforderung</span>
                    <span class="amount-value">{{ number_format($invoice->total + $invoice->reminder_fee + $fee, 2, ',', '.') }} €</span>
                </div>
            </div>
        </div>

        <div class="critical-notice">
            <div class="title">Letzte Zahlungsfrist</div>
            <div class="deadline">{{ now()->addDays(7)->format('d.m.Y') }}</div>
            <div style="margin-top: 16px;"><strong>Bei Nichtzahlung bis zu diesem Datum werden wir ohne weitere Ankündigung:</strong></div>
            <ul>
                <li>Ein Inkassounternehmen beauftragen</li>
                <li>Ein gerichtliches Mahnverfahren einleiten</li>
                <li>Verzugszinsen ({{ config('app.default_interest_rate', 9) }}% über Basiszinssatz) berechnen</li>
            </ul>
            <div style="margin-top: 12px; font-weight: 600;">Dies wird zu erheblichen zusätzlichen Kosten für Sie führen.</div>
        </div>

        <p><strong>Handeln Sie jetzt!</strong></p>

        <p>Falls Sie Zahlungsschwierigkeiten haben, <strong>kontaktieren Sie uns sofort</strong> unter {{ $company->phone }} oder {{ $company->email }}, um eine außergerichtliche Lösung zu finden.</p>

        <p>Bei Ignorieren dieser letzten Mahnung übernehmen wir keine Verantwortung für die entstehenden Mehrkosten.</p>

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
