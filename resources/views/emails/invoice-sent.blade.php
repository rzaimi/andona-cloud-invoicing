<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rechnung {{ $invoice->number }}</title>
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
            border-bottom: 3px solid #3b82f6;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #3b82f6;
            margin: 0;
            font-size: 24px;
        }
        .company-name {
            font-size: 18px;
            color: #666;
            margin-top: 5px;
        }
        .content {
            margin-bottom: 30px;
        }
        .invoice-details {
            background-color: #f8f9fa;
            border-left: 4px solid #3b82f6;
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
        .button:hover {
            background-color: #2563eb;
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
            <h1>Rechnung {{ $invoice->number }}</h1>
            <div class="company-name">{{ $company->name }}</div>
        </div>

        <div class="content">
            <p>Sehr geehrte Damen und Herren{{ $invoice->customer ? ' von ' . $invoice->customer->name : '' }},</p>
            
            <p>anbei erhalten Sie die Rechnung <strong>{{ $invoice->number }}</strong> vom {{ \Carbon\Carbon::parse($invoice->issue_date)->format('d.m.Y') }}.</p>

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
                        <td>{{ \Carbon\Carbon::parse($invoice->due_date)->format('d.m.Y') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Gesamtbetrag:</strong></td>
                        <td><strong>{{ number_format($invoice->total, 2, ',', '.') }} €</strong></td>
                    </tr>
                </table>
            </div>

            @if($invoice->notes)
            <p><strong>Hinweise:</strong><br>{{ $invoice->notes }}</p>
            @endif

            <p>Die Rechnung als PDF-Datei finden Sie im Anhang dieser E-Mail.</p>

            <p>Bitte überweisen Sie den Betrag bis zum <strong>{{ \Carbon\Carbon::parse($invoice->due_date)->format('d.m.Y') }}</strong> auf das in der Rechnung angegebene Konto.</p>

            <p>Bei Fragen stehen wir Ihnen gerne zur Verfügung.</p>

            <p>Mit freundlichen Grüßen<br>{{ $company->name }}</p>
        </div>

        <div class="contact-info">
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


