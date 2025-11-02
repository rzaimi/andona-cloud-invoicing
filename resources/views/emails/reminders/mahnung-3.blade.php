<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3. und letzte Mahnung</title>
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
            background: linear-gradient(135deg, #c0392b 0%, #8e44ad 100%);
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
        .critical-box {
            background: #ffcccc;
            border: 2px solid #c0392b;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: center;
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
            color: #c0392b;
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
            color: #c0392b;
            font-weight: bold;
        }
        .deadline {
            font-size: 24px;
            color: #c0392b;
            font-weight: bold;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>⛔ 3. und LETZTE Mahnung</h1>
    </div>

    <div class="content">
        <p>Sehr geehrte Damen und Herren,</p>

        <p><strong style="color: #c0392b;">DIES IST UNSERE LETZTE MAHNUNG</strong> vor Einleitung rechtlicher Schritte.</p>

        <p>Trotz mehrfacher Zahlungsaufforderungen ist die Zahlung der nachfolgend aufgeführten Rechnung bis heute ausgeblieben:</p>

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
                <div style="color: #666; font-size: 14px; margin-top: 10px;">Mahngebühr (3. Mahnung)</div>
                <div style="font-size: 20px; color: #c0392b;">+ {{ number_format($fee, 2, ',', '.') }} €</div>
                @endif

                <div style="border-top: 2px solid #333; margin: 15px 50px; padding-top: 10px;">
                    <div style="color: #666; font-size: 14px;">Gesamtforderung</div>
                    <div class="amount">{{ number_format($invoice->total + $invoice->reminder_fee + $fee, 2, ',', '.') }} €</div>
                </div>
            </div>
        </div>

        <div class="critical-box">
            <div style="font-size: 18px; margin-bottom: 10px;">⛔ <strong>LETZTE ZAHLUNGSFRIST</strong> ⛔</div>
            <div class="deadline">{{ now()->addDays(7)->format('d.m.Y') }}</div>
            <div style="margin-top: 15px; font-size: 14px;">
                <strong>Bei Nichtzahlung bis zu diesem Datum werden wir ohne weitere Ankündigung:</strong>
            </div>
            <ul style="text-align: left; display: inline-block; margin: 10px 0;">
                <li>Ein Inkassounternehmen beauftragen</li>
                <li>Ein gerichtliches Mahnverfahren einleiten</li>
                <li>Verzugszinsen ({{ config('app.default_interest_rate', 9) }}% über Basiszinssatz) berechnen</li>
            </ul>
            <div style="margin-top: 10px; color: #c0392b; font-weight: bold;">
                Dies wird zu erheblichen zusätzlichen Kosten für Sie führen!
            </div>
        </div>

        <p><strong style="color: #c0392b;">HANDELN SIE JETZT!</strong></p>

        <p>Falls Sie Zahlungsschwierigkeiten haben, <strong>kontaktieren Sie uns SOFORT</strong> unter {{ $company->phone }} oder {{ $company->email }}, um eine außergerichtliche Lösung zu finden.</p>

        <p>Bei Ignorieren dieser letzten Mahnung übernehmen wir keine Verantwortung für die entstehenden Mehrkosten.</p>

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


