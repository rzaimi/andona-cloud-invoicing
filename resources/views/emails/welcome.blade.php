<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Willkommen bei {{ $company->name }}</title>
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
            border-bottom: 3px solid #8b5cf6;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #8b5cf6;
            margin: 0;
            font-size: 28px;
        }
        .company-name {
            font-size: 20px;
            color: #666;
            margin-top: 10px;
            font-weight: 600;
        }
        .welcome-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            margin: 20px 0;
            border-radius: 8px;
            text-align: center;
        }
        .welcome-badge .icon {
            font-size: 64px;
            margin-bottom: 15px;
        }
        .welcome-badge h3 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        .content {
            margin-bottom: 30px;
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #8b5cf6;
            padding: 15px;
            margin: 20px 0;
        }
        .info-box h4 {
            margin: 0 0 10px 0;
            color: #6d28d9;
        }
        .benefits {
            background-color: #eff6ff;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .benefits h4 {
            margin: 0 0 15px 0;
            color: #1e40af;
        }
        .benefits ul {
            margin: 0;
            padding-left: 20px;
        }
        .benefits li {
            margin-bottom: 8px;
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
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>🎉 Willkommen!</h1>
            <div class="company-name">{{ $company->name }}</div>
        </div>

        <div class="content">
            <div class="welcome-badge">
                <div class="icon">👋</div>
                <h3>Schön, dass Sie da sind!</h3>
                <p style="margin: 5px 0 0 0;">Wir freuen uns auf die Zusammenarbeit</p>
            </div>

            <p>Sehr geehrte Damen und Herren{{ isset($customer) && $customer ? ' von ' . $customer->name : '' }},</p>
            
            <p>herzlich willkommen bei {{ $company->name }}! Wir freuen uns sehr, Sie als neuen Kunden begrüßen zu dürfen und bedanken uns für Ihr Vertrauen.</p>

            <div class="info-box">
                <h4>📋 Ihre Kundendaten</h4>
                @if(isset($customer) && $customer)
                <p style="margin: 0;">
                    <strong>Kundennummer:</strong> {{ $customer->number ?? 'Wird zugewiesen' }}<br>
                    @if($customer->contact_person)
                    <strong>Ansprechpartner:</strong> {{ $customer->contact_person }}<br>
                    @endif
                    <strong>E-Mail:</strong> {{ $customer->email }}<br>
                    @if($customer->phone)
                    <strong>Telefon:</strong> {{ $customer->phone }}
                    @endif
                </p>
                @endif
            </div>

            <div class="benefits">
                <h4>✨ Das erwartet Sie bei uns</h4>
                <ul>
                    <li><strong>Professioneller Service:</strong> Unser erfahrenes Team steht Ihnen jederzeit zur Verfügung</li>
                    <li><strong>Transparente Abrechnung:</strong> Klare und verständliche Rechnungen</li>
                    <li><strong>Schnelle Bearbeitung:</strong> Zügige Abwicklung Ihrer Anfragen und Aufträge</li>
                    <li><strong>Persönliche Betreuung:</strong> Individuelle Lösungen für Ihre Bedürfnisse</li>
                </ul>
            </div>

            <p>Falls Sie Fragen haben oder Unterstützung benötigen, zögern Sie bitte nicht, uns zu kontaktieren. Wir sind für Sie da!</p>

            @if(isset($specialOffer) && $specialOffer)
            <div style="background-color: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px; padding: 15px; text-align: center; margin: 20px 0;">
                <h4 style="margin: 0 0 10px 0; color: #d97706;">🎁 Willkommensangebot</h4>
                <p style="margin: 0;">{{ $specialOffer }}</p>
            </div>
            @endif

            <p><strong>Wir freuen uns auf eine erfolgreiche Zusammenarbeit!</strong></p>

            <p>Mit freundlichen Grüßen<br>{{ $company->name }}</p>
        </div>

        <div class="contact-info">
            <p style="margin: 0 0 10px 0; font-weight: 600;">📞 Ihr direkter Draht zu uns:</p>
            @if($company->email)
            <p style="margin: 5px 0;"><strong>E-Mail:</strong> {{ $company->email }}</p>
            @endif
            @if($company->phone)
            <p style="margin: 5px 0;"><strong>Telefon:</strong> {{ $company->phone }}</p>
            @endif
            @if($company->website)
            <p style="margin: 5px 0;"><strong>Website:</strong> {{ $company->website }}</p>
            @endif
            @if($company->address || $company->postal_code || $company->city)
            <p style="margin: 5px 0;"><strong>Adresse:</strong> {{ $company->address }}, {{ $company->postal_code }} {{ $company->city }}</p>
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


