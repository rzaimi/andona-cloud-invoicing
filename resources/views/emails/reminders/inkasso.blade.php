<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inkassoankündigung</title>
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
            border-bottom: 3px solid #1a1a1a;
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
            color: #1a1a1a;
            margin: 0;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .content {
            color: #1a1a1a;
            font-size: 15px;
        }
        .content p {
            margin: 0 0 16px 0;
        }
        .legal-notice {
            background: #1a1a1a;
            color: #ffffff;
            padding: 24px;
            margin: 32px 0;
            text-align: center;
        }
        .legal-notice .title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .legal-notice .subtitle {
            font-size: 14px;
            opacity: 0.9;
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
            padding: 24px;
            background: #ffffff;
            border: 2px solid #1a1a1a;
        }
        .amount-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
        }
        .amount-row.total {
            padding-top: 16px;
            border-top: 3px solid #1a1a1a;
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
            font-weight: 600;
        }
        .amount-value {
            font-weight: 600;
            color: #1a1a1a;
        }
        .amount-row.total .amount-value {
            font-size: 36px;
            color: #1a1a1a;
        }
        .inkasso-info {
            background: #fafafa;
            padding: 20px;
            margin: 24px 0;
            font-size: 14px;
        }
        .inkasso-info .label {
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 12px;
        }
        .inkasso-info div {
            margin: 4px 0;
            color: #1a1a1a;
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
        <h1>Inkassoankündigung</h1>
        <p class="subtitle">Rechtliche Schritte eingeleitet</p>
    </div>

    <div class="content">
        <p><strong>Sehr geehrte Damen und Herren,</strong></p>

        <div class="legal-notice">
            <div class="title">Offizieller rechtlicher Hinweis</div>
            <div class="subtitle">Diese Forderung wurde an unser Inkassounternehmen übergeben</div>
        </div>

        <p>Trotz mehrfacher Mahnungen haben Sie die nachfolgende Rechnung nicht beglichen. Wir haben daher ein Inkassounternehmen mit der Durchsetzung unserer Forderung beauftragt.</p>

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
                    <td>Fälligkeitsdatum</td>
                    <td>{{ $invoice->due_date->format('d.m.Y') }}</td>
                </tr>
                <tr>
                    <td>Tage überfällig</td>
                    <td>{{ $invoice->getDaysOverdue() }} Tage</td>
                </tr>
            </table>

            <div class="amount-section">
                <div class="amount-row">
                    <span class="amount-label">Ursprünglicher Rechnungsbetrag</span>
                    <span class="amount-value">{{ number_format($invoice->total, 2, ',', '.') }} €</span>
                </div>
                @if($invoice->reminder_fee > 0)
                <div class="amount-row">
                    <span class="amount-label">Mahngebühren</span>
                    <span class="amount-value">{{ number_format($invoice->reminder_fee, 2, ',', '.') }} €</span>
                </div>
                @endif
                <div class="amount-row">
                    <span class="amount-label">Verzugszinsen</span>
                    <span class="amount-value">{{ number_format($delayInterest ?? 0, 2, ',', '.') }} €</span>
                </div>
                <div class="amount-row">
                    <span class="amount-label">Inkassokosten</span>
                    <span class="amount-value">{{ number_format($inkassoFee ?? 0, 2, ',', '.') }} €</span>
                </div>
                <div class="amount-row total">
                    <span class="amount-label">Gesamtforderung</span>
                    <span class="amount-value">{{ number_format(($invoice->total + $invoice->reminder_fee + ($delayInterest ?? 0) + ($inkassoFee ?? 0)), 2, ',', '.') }} €</span>
                </div>
            </div>
        </div>

        <p style="margin-top: 32px;"><strong>Die Forderung wurde übergeben an:</strong></p>
        <div class="inkasso-info">
            <div class="label">[Inkassounternehmen Name]</div>
            <div>[Inkassounternehmen Adresse]</div>
            <div>Tel: [Inkassounternehmen Telefon]</div>
            <div>E-Mail: [Inkassounternehmen E-Mail]</div>
            <div style="margin-top: 12px; color: #666; font-size: 13px;">Aktenzeichen: {{ $invoice->number }}</div>
        </div>

        <p><strong>Weitere Schritte:</strong></p>
        <ul style="margin: 16px 0; padding-left: 20px;">
            <li>Das Inkassounternehmen wird Sie separat kontaktieren</li>
            <li>Bei Nichtzahlung erfolgt die Einleitung eines gerichtlichen Mahnverfahrens</li>
            <li>Es entstehen weitere Gerichts- und Anwaltskosten</li>
            <li>Ein negativer Eintrag bei Auskunfteien (z.B. Schufa) ist möglich</li>
        </ul>

        <p><strong>Letzte Möglichkeit zur außergerichtlichen Einigung:</strong><br>
        Kontaktieren Sie uns innerhalb der nächsten 3 Tage unter {{ $company->phone }} oder {{ $company->email }}.</p>

        <p style="margin-top: 32px; font-size: 13px; color: #666; padding: 16px; background: #fafafa;">
        <strong>Rechtlicher Hinweis:</strong> Diese Forderung ist rechtlich durchsetzbar. Ignorieren Sie diese Mitteilung nicht, da dies zu erheblichen zusätzlichen Kosten und rechtlichen Konsequenzen führen wird.
        </p>

        <p style="margin-top: 32px;">Hochachtungsvoll,<br>
        <strong>{{ $company->name }}</strong></p>
    </div>

    <div class="footer">
        <div><strong>{{ $company->name }}</strong></div>
        <div>{{ $company->address }}</div>
        <div>Tel: {{ $company->phone }} | E-Mail: {{ $company->email }}</div>
    </div>
</body>
</html>
