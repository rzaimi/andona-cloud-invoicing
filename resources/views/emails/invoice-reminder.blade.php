<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zahlungserinnerung - Rechnung {{ $invoice->number }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.7;
            color: #1a1a1a;
            max-width: 600px;
            margin: 0 auto;
            padding: 40px 20px;
            background-color: #ffffff;
        }
        .email-container {
            background-color: #ffffff;
            padding: 0;
        }
        .header {
            margin-bottom: 48px;
        }
        .header h1 {
            color: #1a1a1a;
            margin: 0 0 8px 0;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }
        .company-name {
            font-size: 13px;
            color: #666;
            font-weight: 400;
        }
        .content {
            margin-bottom: 48px;
        }
        .content p {
            margin: 0 0 20px 0;
            color: #1a1a1a;
            font-size: 15px;
        }
        .notice {
            padding: 20px 0;
            margin: 32px 0;
            font-size: 14px;
        }
        .notice.overdue {
            color: #991b1b;
        }
        .invoice-details {
            background-color: transparent;
            padding: 0;
            margin: 32px 0;
        }
        .invoice-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .invoice-details td {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }
        .invoice-details tr:last-child td {
            border-bottom: none;
        }
        .invoice-details td:first-child {
            font-weight: 400;
            color: #666;
        }
        .invoice-details td:last-child {
            text-align: right;
            color: #1a1a1a;
        }
        .amount-highlight {
            padding: 24px 0;
            text-align: center;
            margin: 32px 0;
        }
        .amount-highlight .label {
            font-size: 13px;
            color: #666;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .amount-highlight .amount {
            font-size: 36px;
            font-weight: 600;
            color: #1a1a1a;
        }
        .footer {
            margin-top: 64px;
            padding-top: 32px;
            border-top: 1px solid #f0f0f0;
            font-size: 12px;
            color: #999;
            line-height: 1.6;
        }
        .footer p {
            margin: 0 0 4px 0;
        }
        .contact-info {
            margin-top: 32px;
            font-size: 13px;
            color: #666;
        }
        .contact-info p {
            margin: 0 0 6px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Zahlungserinnerung</h1>
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
                <div class="notice overdue">
                    Die Rechnung ist seit {{ abs($daysOverdue) }} Tag{{ abs($daysOverdue) != 1 ? 'en' : '' }} überfällig.
                </div>

                <p>leider haben wir bisher keine Zahlung für die nachstehende Rechnung erhalten. Sollte die Zahlung bereits erfolgt sein, betrachten Sie diese E-Mail bitte als gegenstandslos.</p>
            @else
                <div class="notice">
                    Die Zahlung ist in {{ $daysOverdue }} Tag{{ $daysOverdue != 1 ? 'en' : '' }} fällig.
                </div>

                <p>dies ist eine freundliche Erinnerung an die bevorstehende Zahlung für die nachfolgende Rechnung.</p>
            @endif

            <div class="invoice-details">
                <table>
                    <tr>
                        <td>Rechnungsnummer</td>
                        <td>{{ $invoice->number }}</td>
                    </tr>
                    <tr>
                        <td>Rechnungsdatum</td>
                        <td>{{ \Carbon\Carbon::parse($invoice->issue_date)->format('d.m.Y') }}</td>
                    </tr>
                    <tr>
                        <td>Fälligkeitsdatum</td>
                        <td>{{ $dueDate->format('d.m.Y') }}</td>
                    </tr>
                    @if($isOverdue)
                    <tr>
                        <td>Tage überfällig</td>
                        <td style="color: #991b1b;"><strong>{{ abs($daysOverdue) }} Tag{{ abs($daysOverdue) != 1 ? 'e' : '' }}</strong></td>
                    </tr>
                    @endif
                </table>
            </div>

            <div class="amount-highlight">
                <div class="label">Offener Betrag</div>
                <div class="amount">{{ number_format($invoice->total, 2, ',', '.') }} €</div>
            </div>

            @if($isOverdue)
                <p><strong>Bitte überweisen Sie den Betrag umgehend</strong> auf das in der Rechnung angegebene Konto. Die Rechnung finden Sie im Anhang dieser E-Mail.</p>
                
                <p>Falls Sie Fragen zur Rechnung haben oder bereits eine Zahlung veranlasst haben, informieren Sie uns bitte umgehend.</p>
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
            @if($company->email)
            <p>{{ $company->email }}</p>
            @endif
            @if($company->phone)
            <p>{{ $company->phone }}</p>
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
