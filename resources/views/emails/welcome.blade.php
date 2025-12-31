<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Willkommen bei {{ $company->name }}</title>
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
        .info-section {
            padding: 20px 0;
            margin: 32px 0;
        }
        .info-section h4 {
            margin: 0 0 16px 0;
            color: #1a1a1a;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .info-section p {
            margin: 0 0 8px 0;
            font-size: 14px;
            color: #666;
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
            <h1>Willkommen</h1>
            <div class="company-name">{{ $company->name }}</div>
        </div>

        <div class="content">
            <p>Sehr geehrte Damen und Herren{{ isset($customer) && $customer ? ' von ' . $customer->name : '' }},</p>
            
            <p>herzlich willkommen bei {{ $company->name }}! Wir freuen uns sehr, Sie als neuen Kunden begrüßen zu dürfen und bedanken uns für Ihr Vertrauen.</p>

            @if(isset($customer) && $customer)
            <div class="info-section">
                <h4>Ihre Kundendaten</h4>
                @if($customer->number)
                <p>Kundennummer: {{ $customer->number }}</p>
                @endif
                @if($customer->contact_person)
                <p>Ansprechpartner: {{ $customer->contact_person }}</p>
                @endif
                <p>E-Mail: {{ $customer->email }}</p>
                @if($customer->phone)
                <p>Telefon: {{ $customer->phone }}</p>
                @endif
            </div>
            @endif

            <p>Unser Team steht Ihnen jederzeit zur Verfügung und wird Sie mit professionellem Service, transparenter Abrechnung und persönlicher Betreuung unterstützen.</p>

            @if(isset($specialOffer) && $specialOffer)
            <p style="padding: 20px 0; margin: 32px 0; font-size: 14px; color: #666;">{{ $specialOffer }}</p>
            @endif

            <p>Falls Sie Fragen haben oder Unterstützung benötigen, zögern Sie bitte nicht, uns zu kontaktieren. Wir sind für Sie da!</p>

            <p>Wir freuen uns auf eine erfolgreiche Zusammenarbeit.</p>

            <p>Mit freundlichen Grüßen<br>{{ $company->name }}</p>
        </div>

        <div class="contact-info">
            @if($company->email)
            <p>{{ $company->email }}</p>
            @endif
            @if($company->phone)
            <p>{{ $company->phone }}</p>
            @endif
            @if($company->website)
            <p>{{ $company->website }}</p>
            @endif
            @if($company->address || $company->postal_code || $company->city)
            <p>{{ $company->address }}, {{ $company->postal_code }} {{ $company->city }}</p>
            @endif
        </div>

        <div class="footer">
            <p>{{ $company->name }}</p>
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
