<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zahlungsbestätigung - Rechnung {{ $invoice->number }}</title>
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
            border-bottom: 3px solid #22c55e;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #22c55e;
            margin: 0;
            font-size: 24px;
        }
        .company-name {
            font-size: 18px;
            color: #666;
            margin-top: 5px;
        }
        .success-badge {
            background-color: #d1fae5;
            border-left: 4px solid #22c55e;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
            text-align: center;
        }
        .success-badge .icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .success-badge h3 {
            margin: 0 0 5px 0;
            color: #15803d;
            font-size: 18px;
        }
        .content {
            margin-bottom: 30px;
        }
        .payment-details {
            background-color: #f8f9fa;
            border-left: 4px solid #22c55e;
            padding: 15px;
            margin: 20px 0;
        }
        .payment-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .payment-details td {
            padding: 8px 0;
        }
        .payment-details td:first-child {
            font-weight: 600;
            color: #555;
            width: 40%;
        }
        .amount-paid {
            background-color: #d1fae5;
            border: 2px solid #22c55e;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .amount-paid .label {
            font-size: 14px;
            color: #15803d;
            margin-bottom: 5px;
        }
        .amount-paid .amount {
            font-size: 32px;
            font-weight: bold;
            color: #16a34a;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        .contact-info {
            margin-top: 20px;
            font-size: 13px;
            color: #666;
        }
        .thank-you-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
        .thank-you-box h3 {
            margin: 0 0 10px 0;
            font-size: 20px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>✅ Zahlungsbestätigung</h1>
            <div class="company-name">{{ $company->name }}</div>
        </div>

        <div class="content">
            <div class="success-badge">
                <div class="icon">✓</div>
                <h3>Zahlung erfolgreich eingegangen</h3>
                <p style="margin: 5px 0 0 0; color: #15803d;">Vielen Dank für Ihre pünktliche Zahlung!</p>
            </div>

            <p>Sehr geehrte Damen und Herren{{ $invoice->customer ? ' von ' . $invoice->customer->name : '' }},</p>
            
            <p>wir freuen uns, Ihnen bestätigen zu können, dass Ihre Zahlung für die nachstehende Rechnung bei uns eingegangen ist.</p>

            <div class="payment-details">
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
                        <td>Zahlungseingang:</td>
                        <td>{{ $paymentDate ?? \Carbon\Carbon::now()->format('d.m.Y') }}</td>
                    </tr>
                    <tr>
                        <td>Status:</td>
                        <td><strong style="color: #16a34a;">✓ Bezahlt</strong></td>
                    </tr>
                </table>
            </div>

            <div class="amount-paid">
                <div class="label">Gezahlter Betrag</div>
                <div class="amount">{{ number_format($invoice->total, 2, ',', '.') }} €</div>
            </div>

            <p>Diese Rechnung ist nun vollständig beglichen. Sie brauchen nichts weiter zu unternehmen.</p>

            @if(isset($receiptAttached) && $receiptAttached)
            <p>Eine detaillierte Zahlungsbestätigung finden Sie im Anhang dieser E-Mail.</p>
            @endif

            <div class="thank-you-box">
                <h3>🙏 Herzlichen Dank!</h3>
                <p style="margin: 0;">Wir schätzen Ihre Zuverlässigkeit und freuen uns auf die weitere Zusammenarbeit mit Ihnen.</p>
            </div>

            <p>Falls Sie Fragen haben oder weitere Informationen benötigen, stehen wir Ihnen gerne zur Verfügung.</p>

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


