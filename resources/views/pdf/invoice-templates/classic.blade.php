<!-- Classic Template: Traditional layout, centered, compact design -->
<div class="container">
    <!-- Header: Centered -->
    <div style="text-align: center; margin-bottom: 35px; padding-bottom: 15px; border-bottom: 1px solid {{ $layout->settings['colors']['primary'] ?? '#1f2937' }};">
        @if($company->logo)
            <div style="margin-bottom: 15px;">
                <img src="{{ public_path('storage/' . $company->logo) }}" alt="Logo" style="max-height: 60px; max-width: 200px;">
            </div>
        @endif
        <div class="company-name" style="font-size: {{ $headingFontSize + 4 }}px; font-weight: bold; color: {{ $layout->settings['colors']['primary'] ?? '#1f2937' }}; margin-bottom: 8px;">
            {{ $company->name }}
        </div>
        @if($layout->settings['content']['show_company_address'] ?? true)
            <div style="font-size: {{ $bodyFontSize - 1 }}px; color: {{ $layout->settings['colors']['text'] ?? '#4b5563' }}; line-height: 1.5;">
                @if($company->address){{ $company->address }}@endif
                @if($company->postal_code && $company->city), {{ $company->postal_code }} {{ $company->city }}@endif
                @if($company->country) - {{ $company->country }}@endif
            </div>
        @endif
        @if($layout->settings['content']['show_company_contact'] ?? true && ($company->phone || $company->email))
            <div style="font-size: {{ $bodyFontSize - 1 }}px; color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }}; margin-top: 5px;">
                @if($company->phone)Tel: {{ $company->phone }}@endif
                @if($company->phone && $company->email) | @endif
                @if($company->email)E-Mail: {{ $company->email }}@endif
            </div>
        @endif
    </div>

    <!-- Invoice Title: Centered -->
    <div style="text-align: center; margin-bottom: 25px;">
        <h1 style="font-size: {{ $headingFontSize + 10 }}px; font-weight: bold; color: {{ $layout->settings['colors']['primary'] ?? '#1f2937' }}; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 2px;">
            RECHNUNG
        </h1>
        <div style="font-size: {{ $headingFontSize }}px; color: {{ $layout->settings['colors']['text'] ?? '#1f2937' }};">
            Nr. {{ $invoice->number }}
        </div>
    </div>

    <!-- Two Column: Customer Left, Invoice Details Right -->
    <table style="width: 100%; margin-bottom: 30px;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 15px;">
            <div style="font-size: {{ $bodyFontSize }}px; font-weight: bold; margin-bottom: 8px; border-bottom: 1px solid {{ $layout->settings['colors']['accent'] ?? '#e5e7eb' }}; padding-bottom: 5px;">
                Rechnungsempfänger:
            </div>
            @if(isset($invoice->customer) && $invoice->customer)
                <div style="font-size: {{ $bodyFontSize }}px; line-height: 1.6;">
                    <strong>{{ $invoice->customer->name ?? 'Unbekannt' }}</strong><br>
                    @if(isset($invoice->customer->contact_person) && $invoice->customer->contact_person)
                        {{ $invoice->customer->contact_person }}<br>
                    @endif
                    {{ $invoice->customer->address ?? '' }}<br>
                    {{ ($invoice->customer->postal_code ?? '') }} {{ ($invoice->customer->city ?? '') }}
                    @if(isset($invoice->customer->country) && $invoice->customer->country && $invoice->customer->country !== 'Deutschland')
                        <br>{{ $invoice->customer->country }}
                    @endif
                    @if(($layout->settings['content']['show_customer_number'] ?? true) && isset($invoice->customer->number) && $invoice->customer->number)
                        <br><br>Kundennummer: {{ $invoice->customer->number }}
                    @endif
                </div>
            @endif
            </td>
            <td style="width: 50%; text-align: right; vertical-align: top; padding-left: 15px;">
                <table style="width: 100%; border-collapse: collapse; font-size: {{ $bodyFontSize }}px;">
                <tr>
                    <td style="padding: 4px 0; text-align: left; width: 50%;">Rechnungsdatum:</td>
                    <td style="padding: 4px 0; text-align: right; width: 50%;">{{ \Carbon\Carbon::parse($invoice->issue_date)->format('d.m.Y') }}</td>
                </tr>
                <tr>
                    <td style="padding: 4px 0; text-align: left;">Fälligkeitsdatum:</td>
                    <td style="padding: 4px 0; text-align: right;">{{ \Carbon\Carbon::parse($invoice->due_date)->format('d.m.Y') }}</td>
                </tr>
                @if($layout->settings['content']['show_tax_number'] ?? true && $company->tax_number)
                    <tr>
                        <td style="padding: 4px 0; text-align: left;">Steuernummer:</td>
                        <td style="padding: 4px 0; text-align: right;">{{ $company->tax_number }}</td>
                    </tr>
                @endif
            </table>
            </td>
        </tr>
    </table>

    <!-- Items Table: Traditional bordered table -->
    <table style="width: 100%; border-collapse: collapse; margin: 25px 0; border: 1px solid {{ $layout->settings['colors']['text'] ?? '#1f2937' }};">
        <thead>
            <tr style="background-color: {{ $layout->settings['colors']['text'] ?? '#1f2937' }}; color: white;">
                <th style="padding: 10px 8px; text-align: left; font-weight: bold; font-size: {{ $bodyFontSize }}px; border-right: 1px solid white;">Nr.</th>
                <th style="padding: 10px 8px; text-align: left; font-weight: bold; border-right: 1px solid white;">Beschreibung</th>
                <th style="padding: 10px 8px; text-align: center; font-weight: bold; border-right: 1px solid white;">Menge</th>
                <th style="padding: 10px 8px; text-align: right; font-weight: bold; border-right: 1px solid white;">Einzelpreis</th>
                <th style="padding: 10px 8px; text-align: right; font-weight: bold;">Gesamt</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
                <tr style="border-bottom: 1px solid {{ $layout->settings['colors']['text'] ?? '#1f2937' }};">
                    <td style="padding: 10px 8px; border-right: 1px solid {{ $layout->settings['colors']['text'] ?? '#1f2937' }};">{{ $index + 1 }}</td>
                    <td style="padding: 10px 8px; border-right: 1px solid {{ $layout->settings['colors']['text'] ?? '#1f2937' }};">{{ $item->description }}</td>
                    <td style="padding: 10px 8px; text-align: center; border-right: 1px solid {{ $layout->settings['colors']['text'] ?? '#1f2937' }};">
                        {{ number_format($item->quantity, 2, ',', '.') }}
                        @if($layout->settings['content']['show_unit_column'] ?? true && isset($item->unit) && $item->unit)
                            {{ $item->unit }}
                        @else
                            Stk.
                        @endif
                    </td>
                    <td style="padding: 10px 8px; text-align: right; border-right: 1px solid {{ $layout->settings['colors']['text'] ?? '#1f2937' }};">€ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                    <td style="padding: 10px 8px; text-align: right; font-weight: bold;">€ {{ number_format($item->total, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals: Right aligned, boxed -->
    <div style="text-align: right; margin-top: 25px;">
        <table style="width: 280px; margin-left: auto; border-collapse: collapse; border: 1px solid {{ $layout->settings['colors']['text'] ?? '#1f2937' }};">
            <tr>
                <td style="padding: 8px 12px; text-align: left; border-bottom: 1px solid {{ $layout->settings['colors']['text'] ?? '#1f2937' }};">Zwischensumme (netto)</td>
                <td style="padding: 8px 12px; text-align: right; border-bottom: 1px solid {{ $layout->settings['colors']['text'] ?? '#1f2937' }};">€ {{ number_format($invoice->subtotal, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 12px; text-align: left; border-bottom: 1px solid {{ $layout->settings['colors']['text'] ?? '#1f2937' }};">MwSt. {{ number_format($invoice->tax_rate * 100, 0) }}%</td>
                <td style="padding: 8px 12px; text-align: right; border-bottom: 1px solid {{ $layout->settings['colors']['text'] ?? '#1f2937' }};">€ {{ number_format($invoice->tax_amount, 2, ',', '.') }}</td>
            </tr>
            <tr style="background-color: {{ $layout->settings['colors']['primary'] ?? '#1f2937' }}; color: white;">
                <td style="padding: 12px; font-weight: bold; font-size: {{ $bodyFontSize + 1 }}px;">Rechnungsbetrag</td>
                <td style="padding: 12px; text-align: right; font-weight: bold; font-size: {{ $bodyFontSize + 1 }}px;">€ {{ number_format($invoice->total, 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <!-- Payment Terms -->
    @if($layout->settings['content']['show_payment_terms'] ?? true)
        <div style="margin-top: 30px; padding: 15px; background-color: #f9fafb; border-left: 4px solid {{ $layout->settings['colors']['primary'] ?? '#1f2937' }}; font-size: {{ $bodyFontSize }}px;">
            <strong>Zahlungsbedingungen:</strong> Der Rechnungsbetrag ist innerhalb von {{ \Carbon\Carbon::parse($invoice->due_date)->diffInDays(\Carbon\Carbon::parse($invoice->issue_date)) }} Tagen nach Rechnungseingang fällig und ohne Abzug auf das unten angegebene Konto zu überweisen.
        </div>
    @endif

    <!-- Notes -->
    @if(($layout->settings['content']['show_notes'] ?? true) && isset($invoice->notes) && $invoice->notes)
        <div style="margin-top: 25px; padding: 15px; background-color: #f9fafb; font-size: {{ $bodyFontSize }}px;">
            <strong>Anmerkungen:</strong><br>
            {{ $invoice->notes }}
        </div>
    @endif

    <!-- Footer: Centered, compact -->
    @if($layout->settings['branding']['show_footer'] ?? true)
        <div style="margin-top: 40px; padding-top: 15px; border-top: 1px solid {{ $layout->settings['colors']['accent'] ?? '#e5e7eb' }}; text-align: center; font-size: {{ $bodyFontSize - 1 }}px; color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }}; line-height: 1.8;">
            <strong>{{ $company->name }}</strong> | 
            @if($company->address){{ $company->address }}@endif
            @if($company->postal_code && $company->city), {{ $company->postal_code }} {{ $company->city }}@endif
            @if($company->phone) | Tel: {{ $company->phone }}@endif
            @if($company->email) | E-Mail: {{ $company->email }}@endif
            @if($company->vat_number) | USt-IdNr.: {{ $company->vat_number }}@endif
            @if($layout->settings['content']['show_bank_details'] ?? true && $company->bank_iban)
                <br>Bankverbindung: {{ $company->bank_name ?? '' }} | IBAN: {{ $company->bank_iban }}
                @if($company->bank_bic)| BIC: {{ $company->bank_bic }}@endif
            @endif
        </div>
    @endif

    <!-- Signature -->
    <div style="margin-top: 40px; text-align: center;">
        <div style="margin-bottom: 50px;">Mit freundlichen Grüßen</div>
        @if($company->managing_director)
            <div style="font-size: {{ $headingFontSize }}px; font-weight: bold; color: {{ $layout->settings['colors']['primary'] ?? '#1f2937' }};">
                {{ $company->managing_director }}
            </div>
        @endif
    </div>
</div>

