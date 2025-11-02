<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inkassoankündigung</title>
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
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #ffffff;
            padding: 30px;
            border: 2px solid #c0392b;
        }
        .legal-notice {
            background: #2c3e50;
            color: white;
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
            border: 1px solid #c0392b;
        }
        .amount {
            font-size: 36px;
            font-weight: bold;
            color: #c0392b;
            margin: 10px 0;
        }
        .footer {
            background: #2c3e50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 0 0 10px 10px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>⚖️ Inkassoankündigung</h1>
        <div style="font-size: 14px; margin-top: 10px;">Rechtliche Schritte eingeleitet</div>
    </div>

    <div class="content">
        <p><strong>Sehr geehrte Damen und Herren,</strong></p>

        <div class="legal-notice">
            <div style="font-size: 20px; margin-bottom: 10px;">⚖️ OFFIZIELLER RECHTLICHER HINWEIS</div>
            <div>Diese Forderung wurde an unser Inkassounternehmen übergeben</div>
        </div>

        <p>Trotz mehrfacher Mahnungen haben Sie die nachfolgende Rechnung nicht beglichen. Wir haben daher ein Inkassounternehmen mit der Durchsetzung unserer Forderung beauftragt.</p>

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
                    <td style="padding: 8px 0; text-align: right; color: #c0392b; font-weight: bold;">{{ $invoice->getDaysOverdue() }} Tage</td>
                </tr>
            </table>

            <div style="text-align: center; margin-top: 20px; padding: 20px; background: #fff; border: 2px solid #c0392b;">
                <div style="color: #666; font-size: 14px;">Ursprünglicher Rechnungsbetrag</div>
                <div style="font-size: 20px; color: #666;">{{ number_format($invoice->total, 2, ',', '.') }} €</div>
                
                @if($invoice->reminder_fee > 0)
                <div style="color: #666; font-size: 14px; margin-top: 10px;">Mahngebühren</div>
                <div style="font-size: 20px; color: #e67e22;">+ {{ number_format($invoice->reminder_fee, 2, ',', '.') }} €</div>
                @endif

                <div style="color: #666; font-size: 14px; margin-top: 10px;">Verzugszinsen</div>
                <div style="font-size: 20px; color: #e67e22;">+ {{ number_format($delayInterest ?? 0, 2, ',', '.') }} €</div>

                <div style="color: #666; font-size: 14px; margin-top: 10px;">Inkassokosten</div>
                <div style="font-size: 20px; color: #c0392b;">+ {{ number_format($inkassoFee ?? 0, 2, ',', '.') }} €</div>

                <div style="border-top: 3px solid #c0392b; margin: 15px 30px; padding-top: 10px;">
                    <div style="color: #c0392b; font-size: 16px; font-weight: bold;">GESAMTFORDERUNG</div>
                    <div class="amount">{{ number_format(($invoice->total + $invoice->reminder_fee + ($delayInterest ?? 0) + ($inkassoFee ?? 0)), 2, ',', '.') }} €</div>
                </div>
            </div>
        </div>

        <p><strong style="color: #c0392b;">Die Forderung wurde übergeben an:</strong></p>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #c0392b;">
            <div><strong>[Inkassounternehmen Name]</strong></div>
            <div>[Inkassounternehmen Adresse]</div>
            <div>Tel: [Inkassounternehmen Telefon]</div>
            <div>E-Mail: [Inkassounternehmen E-Mail]</div>
            <div style="margin-top: 10px; font-size: 12px; color: #666;">Aktenzeichen: {{ $invoice->number }}</div>
        </div>

        <p style="margin-top: 30px;"><strong>Weitere Schritte:</strong></p>
        <ul>
            <li>Das Inkassounternehmen wird Sie separat kontaktieren</li>
            <li>Bei Nichtzahlung erfolgt die Einleitung eines gerichtlichen Mahnverfahrens</li>
            <li>Es entstehen weitere Gerichts- und Anwaltskosten</li>
            <li>Ein negativer Eintrag bei Auskunfteien (z.B. Schufa) ist möglich</li>
        </ul>

        <p><strong style="color: #c0392b;">LETZTE MÖGLICHKEIT zur außergerichtlichen Einigung:</strong><br>
        Kontaktieren Sie uns innerhalb der nächsten 3 Tage unter {{ $company->phone }} oder {{ $company->email }}.</p>

        <p style="margin-top: 30px; font-size: 12px; color: #666;">
        <strong>Rechtlicher Hinweis:</strong> Diese Forderung ist rechtlich durchsetzbar. Ignorieren Sie diese Mitteilung nicht, da dies zu erheblichen zusätzlichen Kosten und rechtlichen Konsequenzen führen wird.
        </p>

        <p style="margin-top: 30px;">Hochachtungsvoll,<br>
        <strong>{{ $company->name }}</strong></p>
    </div>

    <div class="footer">
        <p><strong>{{ $company->name }}</strong><br>
        {{ $company->address }}<br>
        Tel: {{ $company->phone }} | E-Mail: {{ $company->email }}</p>
    </div>
</body>
</html>


