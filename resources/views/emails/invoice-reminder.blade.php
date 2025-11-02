<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zahlungserinnerung - Rechnung {{ $invoice->number }}</title>
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
        .urgent-badge {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .urgent-badge.overdue {
            background-color: #fee2e2;
            border-left-color: #ef4444;
        }
        .urgent-badge h3 {
            margin: 0 0 10px 0;
            color: #d97706;
            font-size: 16px;
        }
        .urgent-badge.overdue h3 {
            color: #dc2626;
        }
        .content {
            margin-bottom: 30px;
        }
        .invoice-details {
            background-color: #f8f9fa;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
        }
        .invoice-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .invoice-details td {
            padding: 8px 0;
        }
        .invoice-details td:first-child {
            font-weight: 600;
            color: #555;
            width: 40%;
        }
        .amount-due {
            background-color: #fef3c7;
            border: 2px solid #f59e0b;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .amount-due .label {
            font-size: 14px;
            color: #92400e;
            margin-bottom: 5px;
        }
        .amount-due .amount {
            font-size: 32px;
            font-weight: bold;
            color: #d97706;
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
            background-color: #3b82f6;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: 600;
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
            <h1>⏰ Zahlungserinnerung</h1>
            <div class="company-name">{{ $company->name }}</div>
        </div>

        <div class="content">
            <p>Sehr geehrte Damen und Herren{{ $invoice->customer ? ' von ' . $invoice->customer->name : '' }},</p>
            
            @php
                $dueDate = \Carbon\Carbon::parse($invoice->due_date);
                $today = \Carbon\Carbon::now();
                $daysOverdue = $today->diffInDays($dueDate, false);
                $isOverdue = $daysOverdue < 0;
            @endphp

            @if($isOverdue)
                <div class="urgent-badge overdue">
                    <h3>🚨 Zahlungsverzug</h3>
                    <p style="margin: 0;">Die Rechnung ist seit {{ abs($daysOverdue) }} Tag{{ abs($daysOverdue) != 1 ? 'en' : '' }} überfällig.</p>
                </div>

                <p>leider haben wir bisher keine Zahlung für die nachstehende Rechnung erhalten. Sollte die Zahlung bereits erfolgt sein, betrachten Sie diese E-Mail bitte als gegenstandslos.</p>
            @else
                <div class="urgent-badge">
                    <h3>📌 Freundliche Erinnerung</h3>
                    <p style="margin: 0;">Die Zahlung ist in {{ $daysOverdue }} Tag{{ $daysOverdue != 1 ? 'en' : '' }} fällig.</p>
                </div>

                <p>dies ist eine freundliche Erinnerung an die bevorstehende Zahlung für die nachfolgende Rechnung.</p>
            @endif

            <div class="invoice-details">
                <table>
                    <tr>
                        <td>Rechnungsnummer:</td>
                        <td>{{ $invoice->number }}</td>
                    </tr>
                    <tr>
                        <td>Rechnungsdatum:</td>
                        <td>{{ \Carbon\Carbon::parse($invoice->issue_date)->format('d.m.Y') }}</td>
                    </tr>
                    <tr>
                        <td>Fälligkeitsdatum:</td>
                        <td><strong>{{ $dueDate->format('d.m.Y') }}</strong></td>
                    </tr>
                    @if($isOverdue)
                    <tr>
                        <td style="color: #dc2626;"><strong>Tage überfällig:</strong></td>
                        <td style="color: #dc2626;"><strong>{{ abs($daysOverdue) }} Tag{{ abs($daysOverdue) != 1 ? 'e' : '' }}</strong></td>
                    </tr>
                    @endif
                </table>
            </div>

            <div class="amount-due">
                <div class="label">Offener Betrag</div>
                <div class="amount">{{ number_format($invoice->total, 2, ',', '.') }} €</div>
            </div>

            @if($isOverdue)
                <p><strong>Bitte überweisen Sie den Betrag umgehend</strong> auf das in der Rechnung angegebene Konto. Die Rechnung finden Sie im Anhang dieser E-Mail.</p>
                
                <p>Falls Sie Fragen zur Rechnung haben oder bereits eine Zahlung veranlasst haben, informieren Sie uns bitte umgehend.</p>
                
                <p style="color: #dc2626; font-size: 14px; background: #fee2e2; padding: 10px; border-radius: 4px;">
                    <strong>Hinweis:</strong> Bei weiterer Zahlungsverzögerung behalten wir uns vor, Mahngebühren zu erheben und gegebenenfalls rechtliche Schritte einzuleiten.
                </p>
            @else
                <p>Bitte überweisen Sie den Betrag bis zum <strong>{{ $dueDate->format('d.m.Y') }}</strong> auf das in der Rechnung angegebene Konto. Die Rechnung finden Sie im Anhang dieser E-Mail.</p>
            @endif

            @if($invoice->notes)
            <p><strong>Hinweise zur Rechnung:</strong><br>{{ $invoice->notes }}</p>
            @endif

            <p>Vielen Dank für Ihr Verständnis und Ihre pünktliche Zahlung.</p>

            <p>Mit freundlichen Grüßen<br>{{ $company->name }}</p>
        </div>

        <div class="contact-info">
            <p><strong>Bei Fragen kontaktieren Sie uns:</strong></p>
            @if($company->email)
            <p><strong>E-Mail:</strong> {{ $company->email }}</p>
            @endif
            @if($company->phone)
            <p><strong>Telefon:</strong> {{ $company->phone }}</p>
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


