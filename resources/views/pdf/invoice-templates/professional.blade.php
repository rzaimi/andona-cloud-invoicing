<!-- Professional Template: Corporate, structured layout -->
<div class="container">
    <!-- Header: Logo left, company info right -->
    <table style="width: 100%; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid {{ $layout->settings['colors']['primary'] ?? '#2563eb' }};">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                @if(isset($layout->settings['branding']['show_logo']) && $layout->settings['branding']['show_logo'] && $company->logo)
                    <img src="{{ public_path('storage/' . $company->logo) }}" alt="Logo" style="max-height: 70px; max-width: 200px; margin-bottom: 10px;">
                @endif
            </td>
            <td style="width: 50%; text-align: right; vertical-align: top;">
            <div class="company-name" style="font-size: {{ $headingFontSize + 4 }}px; font-weight: bold; color: {{ $layout->settings['colors']['primary'] ?? '#2563eb' }}; margin-bottom: 8px;">
                {{ $company->name }}
            </div>
            @if($layout->settings['content']['show_company_address'] ?? true)
                <div style="font-size: {{ $bodyFontSize }}px; color: {{ $layout->settings['colors']['text'] ?? '#374151' }}; line-height: 1.6;">
                    @if($company->address){{ $company->address }}<br>@endif
                    @if($company->postal_code && $company->city){{ $company->postal_code }} {{ $company->city }}@endif
                    @if($company->country && $company->country !== 'Deutschland')<br>{{ $company->country }}@endif
                </div>
            @endif
            @if($layout->settings['content']['show_company_contact'] ?? true)
                <div style="font-size: {{ $bodyFontSize - 1 }}px; color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }}; margin-top: 5px;">
                    @if($company->phone)Tel: {{ $company->phone }}<br>@endif
                    @if($company->email)E-Mail: {{ $company->email }}@endif
                </div>
            @endif
            @if($layout->settings['content']['show_tax_number'] ?? true && $company->tax_number)
                <div style="font-size: {{ $bodyFontSize - 1 }}px; color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }}; margin-top: 5px;">
                    Steuernummer: {{ $company->tax_number }}
                </div>
            @endif
            </td>
        </tr>
    </table>

    <!-- Invoice Title: Bold, structured -->
    <table style="width: 100%; margin-bottom: 25px;">
        <tr>
            <td style="vertical-align: middle;">
                <h1 style="font-size: {{ $headingFontSize + 8 }}px; font-weight: bold; color: {{ $layout->settings['colors']['primary'] ?? '#2563eb' }}; margin: 0;">
                    RECHNUNG
                </h1>
            </td>
            <td style="text-align: right; vertical-align: middle;">
                <div style="font-size: {{ $headingFontSize }}px; font-weight: bold; color: {{ $layout->settings['colors']['text'] ?? '#1f2937' }};">
                    Nr. {{ $invoice->number }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Two Column: Customer and Invoice Details -->
    <table style="width: 100%; margin-bottom: 35px;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 20px;">
            <div style="font-size: {{ $bodyFontSize - 1 }}px; font-weight: bold; color: {{ $layout->settings['colors']['primary'] ?? '#2563eb' }}; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px; padding-bottom: 5px; border-bottom: 2px solid {{ $layout->settings['colors']['primary'] ?? '#2563eb' }};">
                Rechnungsempfänger
            </div>
            @if(isset($invoice->customer) && $invoice->customer)
                <div style="font-size: {{ $bodyFontSize + 1 }}px; font-weight: bold; margin-bottom: 5px;">
                    {{ $invoice->customer->name ?? 'Unbekannt' }}
                </div>
                @if(isset($invoice->customer->contact_person) && $invoice->customer->contact_person)
                    <div style="margin-bottom: 5px;">{{ $invoice->customer->contact_person }}</div>
                @endif
                <div style="line-height: 1.6;">
                    {{ $invoice->customer->address ?? '' }}<br>
                    {{ ($invoice->customer->postal_code ?? '') }} {{ ($invoice->customer->city ?? '') }}
                    @if(isset($invoice->customer->country) && $invoice->customer->country && $invoice->customer->country !== 'Deutschland')
                        <br>{{ $invoice->customer->country }}
                    @endif
                </div>
                @if(($layout->settings['content']['show_customer_number'] ?? true) && isset($invoice->customer->number) && $invoice->customer->number)
                    <div style="margin-top: 8px; font-size: {{ $bodyFontSize - 1 }}px; color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }};">
                        <strong>Kundennummer:</strong> {{ $invoice->customer->number }}
                    </div>
                @endif
            @endif
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 20px;">
                <div style="font-size: {{ $bodyFontSize - 1 }}px; font-weight: bold; color: {{ $layout->settings['colors']['primary'] ?? '#2563eb' }}; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px; padding-bottom: 5px; border-bottom: 2px solid {{ $layout->settings['colors']['primary'] ?? '#2563eb' }};">
                    Rechnungsdetails
                </div>
            <table style="width: 100%; border-collapse: collapse; font-size: {{ $bodyFontSize }}px;">
                <tr>
                    <td style="padding: 6px 0; width: 45%;">Rechnungsdatum:</td>
                    <td style="padding: 6px 0; font-weight: bold;">{{ \Carbon\Carbon::parse($invoice->issue_date)->format('d.m.Y') }}</td>
                </tr>
                <tr>
                    <td style="padding: 6px 0;">Fälligkeitsdatum:</td>
                    <td style="padding: 6px 0; font-weight: bold;">{{ \Carbon\Carbon::parse($invoice->due_date)->format('d.m.Y') }}</td>
                </tr>
            </table>
            </td>
        </tr>
    </table>

    <!-- Items Table: Professional styled -->
    <table style="width: 100%; border-collapse: collapse; margin: 30px 0;">
        <thead>
            <tr style="background-color: {{ $layout->settings['colors']['primary'] ?? '#2563eb' }}; color: white;">
                <th style="padding: 12px 10px; text-align: left; font-weight: bold; font-size: {{ $bodyFontSize }}px;">Pos.</th>
                <th style="padding: 12px 10px; text-align: left; font-weight: bold;">Bezeichnung</th>
                <th style="padding: 12px 10px; text-align: center; font-weight: bold;">Menge</th>
                <th style="padding: 12px 10px; text-align: right; font-weight: bold;">Einzelpreis</th>
                <th style="padding: 12px 10px; text-align: right; font-weight: bold;">Gesamt</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
                <tr style="border-bottom: 1px solid #e5e7eb; {{ $index % 2 == 1 ? 'background-color: #f9fafb;' : '' }}">
                    <td style="padding: 12px 10px;">{{ $index + 1 }}</td>
                    <td style="padding: 12px 10px;">{{ $item->description }}</td>
                    <td style="padding: 12px 10px; text-align: center;">
                        {{ number_format($item->quantity, 2, ',', '.') }}
                        @if($layout->settings['content']['show_unit_column'] ?? true && isset($item->unit) && $item->unit)
                            {{ $item->unit }}
                        @else
                            Stk.
                        @endif
                    </td>
                    <td style="padding: 12px 10px; text-align: right;">€ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                    <td style="padding: 12px 10px; text-align: right; font-weight: bold;">€ {{ number_format($item->total, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals: Professional boxed -->
    <div style="text-align: right; margin-top: 30px;">
        <table style="width: 320px; margin-left: auto; border-collapse: collapse; border: 2px solid {{ $layout->settings['colors']['primary'] ?? '#2563eb' }};">
            <tr>
                <td style="padding: 10px 15px; text-align: left; border-bottom: 1px solid #e5e7eb; background-color: #f9fafb;">Zwischensumme (netto)</td>
                <td style="padding: 10px 15px; text-align: right; border-bottom: 1px solid #e5e7eb; background-color: #f9fafb;">€ {{ number_format($invoice->subtotal, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="padding: 10px 15px; text-align: left; border-bottom: 1px solid #e5e7eb; background-color: #f9fafb;">MwSt. {{ number_format($invoice->tax_rate * 100, 0) }}%</td>
                <td style="padding: 10px 15px; text-align: right; border-bottom: 1px solid #e5e7eb; background-color: #f9fafb;">€ {{ number_format($invoice->tax_amount, 2, ',', '.') }}</td>
            </tr>
            <tr style="background-color: {{ $layout->settings['colors']['primary'] ?? '#2563eb' }}; color: white;">
                <td style="padding: 15px; font-weight: bold; font-size: {{ $bodyFontSize + 2 }}px;">Rechnungsbetrag</td>
                <td style="padding: 15px; text-align: right; font-weight: bold; font-size: {{ $bodyFontSize + 2 }}px;">€ {{ number_format($invoice->total, 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <!-- Payment Terms: Structured -->
    @if($layout->settings['content']['show_payment_terms'] ?? true)
        <div style="margin-top: 35px; padding: 20px; background-color: #f9fafb; border-left: 4px solid {{ $layout->settings['colors']['primary'] ?? '#2563eb' }}; font-size: {{ $bodyFontSize }}px; line-height: 1.6;">
            <strong style="color: {{ $layout->settings['colors']['primary'] ?? '#2563eb' }};">Zahlungsbedingungen:</strong><br>
            Bitte überweisen Sie den Rechnungsbetrag innerhalb von {{ \Carbon\Carbon::parse($invoice->due_date)->diffInDays(\Carbon\Carbon::parse($invoice->issue_date)) }} Tagen nach Rechnungseingang ohne Abzug auf das unten angegebene Bankkonto.
        </div>
    @endif

    <!-- Notes -->
    @if(($layout->settings['content']['show_notes'] ?? true) && isset($invoice->notes) && $invoice->notes)
        <div style="margin-top: 25px; padding: 15px; background-color: #f9fafb; border-left: 3px solid {{ $layout->settings['colors']['accent'] ?? '#cbd5e1' }}; font-size: {{ $bodyFontSize }}px;">
            <strong>Anmerkungen:</strong><br>
            {{ $invoice->notes }}
        </div>
    @endif

    <!-- Footer: Three columns, structured -->
    @if($layout->settings['branding']['show_footer'] ?? true)
        <table style="width: 100%; margin-top: 50px; padding-top: 20px; border-top: 3px solid {{ $layout->settings['colors']['primary'] ?? '#2563eb' }}; font-size: {{ $bodyFontSize - 1 }}px; color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }};">
            <tr>
                <td style="width: 33%; vertical-align: top;">
                    <strong style="color: {{ $layout->settings['colors']['primary'] ?? '#2563eb' }};">{{ $company->name }}</strong><br>
                    @if($company->address){{ $company->address }}<br>@endif
                    @if($company->postal_code && $company->city){{ $company->postal_code }} {{ $company->city }}@endif
                    @if($company->vat_number)<br>USt-IdNr.: {{ $company->vat_number }}@endif
                </td>
                <td style="width: 34%; text-align: center; vertical-align: top;">
                    @if($company->phone)Tel: {{ $company->phone }}<br>@endif
                    @if($company->email)E-Mail: {{ $company->email }}@endif
                </td>
                <td style="width: 33%; text-align: right; vertical-align: top;">
                    @if($layout->settings['content']['show_bank_details'] ?? true)
                        @if($company->bank_name)<strong>{{ $company->bank_name }}</strong><br>@endif
                        @if($company->bank_iban)IBAN: {{ $company->bank_iban }}<br>@endif
                        @if($company->bank_bic)BIC: {{ $company->bank_bic }}@endif
                    @endif
                </td>
            </tr>
        </table>
    @endif

    <!-- Signature -->
    <div style="margin-top: 50px;">
        <div style="margin-bottom: 50px;">Mit freundlichen Grüßen</div>
        @if($company->managing_director)
            <div style="font-size: {{ $headingFontSize }}px; font-weight: bold; color: {{ $layout->settings['colors']['primary'] ?? '#2563eb' }};">
                {{ $company->managing_director }}
            </div>
        @endif
    </div>
</div>

