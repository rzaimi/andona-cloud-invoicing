<!-- Elegant Template: Refined design with centered alignment -->
<div class="container">
    <!-- Header: Elegant, centered -->
    <div style="text-align: center; margin-bottom: 45px; padding-bottom: 25px; border-bottom: 1px solid {{ $layout->settings['colors']['accent'] ?? '#e5e7eb' }};">
        @if(($layout->settings['branding']['show_logo'] ?? true) && $company->logo)
            <div style="margin-bottom: 20px;">
                <img src="{{ public_path('storage/' . $company->logo) }}" alt="Logo" style="max-height: 90px; max-width: 250px;">
            </div>
        @endif
        <div class="company-name" style="font-size: {{ $headingFontSize + 6 }}px; font-weight: 300; letter-spacing: 2px; color: {{ $layout->settings['colors']['primary'] ?? '#059669' }}; margin-bottom: 12px; text-transform: uppercase;">
            {{ $company->name }}
        </div>
        @if($layout->settings['content']['show_company_address'] ?? true)
            <div style="font-size: {{ $bodyFontSize }}px; color: {{ $layout->settings['colors']['text'] ?? '#4b5563' }}; line-height: 1.8; font-style: italic;">
                @if($company->address){{ $company->address }}@endif
                @if($company->postal_code && $company->city), {{ $company->postal_code }} {{ $company->city }}@endif
                @if($company->country && $company->country !== 'Deutschland')<br>{{ $company->country }}@endif
            </div>
        @endif
        @if($layout->settings['content']['show_company_contact'] ?? true && ($company->phone || $company->email))
            <div style="font-size: {{ $bodyFontSize - 1 }}px; color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }}; margin-top: 8px; letter-spacing: 0.5px;">
                @if($company->email){{ $company->email }}@endif
                @if($company->email && $company->phone) · @endif
                @if($company->phone){{ $company->phone }}@endif
            </div>
        @endif
    </div>

    <!-- Invoice Title: Elegant, refined -->
    <div style="text-align: center; margin-bottom: 40px;">
        <div style="font-size: {{ $headingFontSize - 2 }}px; color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }}; letter-spacing: 3px; text-transform: uppercase; margin-bottom: 10px;">
            {{ $invoice->is_correction ? 'Stornorechnung' : 'Rechnung' }}
        </div>
        <div style="font-size: {{ $headingFontSize + 10 }}px; font-weight: 300; color: {{ $invoice->is_correction ? '#dc2626' : ($layout->settings['colors']['primary'] ?? '#059669') }}; letter-spacing: 1px; margin-bottom: 8px;">
            {{ $invoice->number }}
        </div>
        <div style="font-size: {{ $bodyFontSize }}px; color: {{ $layout->settings['colors']['text'] ?? '#9ca3af' }}; font-style: italic;">
            {{ \Carbon\Carbon::parse($invoice->issue_date)->format('d.m.Y') }}
        </div>
        @if($invoice->is_correction && $invoice->correctsInvoice)
            <div style="margin-top: 20px; padding: 15px; background-color: #fee2e2; border: 1px solid #dc2626; border-radius: 4px; font-size: {{ $bodyFontSize }}px; max-width: 500px; margin-left: auto; margin-right: auto;">
                <div style="font-weight: 400; color: #991b1b; margin-bottom: 6px; letter-spacing: 0.5px;">Storniert Rechnung:</div>
                <div style="color: #7f1d1d; font-weight: 300;">Nr. {{ $invoice->correctsInvoice->number }} vom {{ \Carbon\Carbon::parse($invoice->correctsInvoice->issue_date)->format('d.m.Y') }}</div>
                @if($invoice->correction_reason)
                    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #dc2626; font-size: {{ $bodyFontSize - 1 }}px; font-style: italic;">
                        <strong>Grund:</strong> {{ $invoice->correction_reason }}
                    </div>
                @endif
            </div>
        @endif
    </div>

    <!-- Two Column: Symmetric, elegant spacing -->
    <table style="width: 100%; margin-bottom: 45px;">
        <tr>
            <!-- Left: Customer -->
            <td style="width: 50%; text-align: left; vertical-align: top; padding-right: 30px;">
            <div style="font-size: {{ $bodyFontSize - 1 }}px; font-weight: 300; color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }}; margin-bottom: 15px; letter-spacing: 1px; text-transform: uppercase; padding-bottom: 8px; border-bottom: 1px solid {{ $layout->settings['colors']['accent'] ?? '#e5e7eb' }};">
                Rechnungsempfänger
            </div>
            @if(isset($invoice->customer) && $invoice->customer)
                <div style="font-size: {{ $bodyFontSize + 2 }}px; font-weight: 400; margin-bottom: 10px; color: {{ $layout->settings['colors']['text'] ?? '#1f2937' }}; letter-spacing: 0.3px;">
                    {{ $invoice->customer->name ?? 'Unbekannt' }}
                </div>
                @if(isset($invoice->customer->contact_person) && $invoice->customer->contact_person)
                    <div style="margin-bottom: 10px; font-style: italic; color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }};">{{ $invoice->customer->contact_person }}</div>
                @endif
                <div style="line-height: 2; color: {{ $layout->settings['colors']['text'] ?? '#4b5563' }}; font-weight: 300;">
                    {{ $invoice->customer->address ?? '' }}<br>
                    {{ ($invoice->customer->postal_code ?? '') }} {{ ($invoice->customer->city ?? '') }}
                    @if(isset($invoice->customer->country) && $invoice->customer->country && $invoice->customer->country !== 'Deutschland')
                        <br>{{ $invoice->customer->country }}
                    @endif
                </div>
                @if(($layout->settings['content']['show_customer_number'] ?? true) && isset($invoice->customer->number) && $invoice->customer->number)
                    <div style="margin-top: 15px; padding: 10px; background-color: #f9fafb; font-size: {{ $bodyFontSize - 1 }}px; color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }}; border-left: 2px solid {{ $layout->settings['colors']['accent'] ?? '#e5e7eb' }};">
                        Kundennummer: {{ $invoice->customer->number }}
                    </div>
                @endif
            @endif
            </td>
            <!-- Right: Invoice Details -->
            <td style="width: 50%; text-align: right; vertical-align: top; padding-left: 30px;">
            <div style="font-size: {{ $bodyFontSize - 1 }}px; font-weight: 300; color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }}; margin-bottom: 15px; letter-spacing: 1px; text-transform: uppercase; padding-bottom: 8px; border-bottom: 1px solid {{ $layout->settings['colors']['accent'] ?? '#e5e7eb' }};">
                Rechnungsdetails
            </div>
            <div style="font-size: {{ $bodyFontSize }}px; line-height: 2.2; color: {{ $layout->settings['colors']['text'] ?? '#374151' }}; font-weight: 300;">
                <div>Rechnungsdatum:<br><strong style="font-weight: 400;">{{ \Carbon\Carbon::parse($invoice->issue_date)->format('d.m.Y') }}</strong></div>
                <div style="margin-top: 10px;">Fälligkeitsdatum:<br><strong style="font-weight: 400;">{{ \Carbon\Carbon::parse($invoice->due_date)->format('d.m.Y') }}</strong></div>
                @if($layout->settings['content']['show_tax_number'] ?? true && $company->tax_number)
                    <div style="margin-top: 10px;">Steuernummer:<br><strong style="font-weight: 400;">{{ $company->tax_number }}</strong></div>
                @endif
            </div>
            </td>
        </tr>
    </table>

    <!-- Items Table: Elegant, refined -->
    <table style="width: 100%; border-collapse: collapse; margin: 40px 0;">
        <thead>
            <tr style="border-bottom: 2px solid {{ $layout->settings['colors']['primary'] ?? '#059669' }};">
                <th style="padding: 15px 12px; text-align: left; font-weight: 400; font-size: {{ $bodyFontSize }}px; color: {{ $layout->settings['colors']['primary'] ?? '#059669' }}; letter-spacing: 0.5px; text-transform: uppercase;">Pos.</th>
                <th style="padding: 15px 12px; text-align: left; font-weight: 400; color: {{ $layout->settings['colors']['primary'] ?? '#059669' }}; letter-spacing: 0.5px; text-transform: uppercase;">Beschreibung</th>
                <th style="padding: 15px 12px; text-align: center; font-weight: 400; color: {{ $layout->settings['colors']['primary'] ?? '#059669' }}; letter-spacing: 0.5px; text-transform: uppercase;">Menge</th>
                <th style="padding: 15px 12px; text-align: right; font-weight: 400; color: {{ $layout->settings['colors']['primary'] ?? '#059669' }}; letter-spacing: 0.5px; text-transform: uppercase;">Einzelpreis</th>
                <th style="padding: 15px 12px; text-align: right; font-weight: 400; color: {{ $layout->settings['colors']['primary'] ?? '#059669' }}; letter-spacing: 0.5px; text-transform: uppercase;">Gesamt</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
                <tr style="border-bottom: 1px solid {{ $layout->settings['colors']['accent'] ?? '#e5e7eb' }};">
                    <td style="padding: 15px 12px; color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }};">{{ $index + 1 }}</td>
                    <td style="padding: 15px 12px;">{{ $item->description }}</td>
                    <td style="padding: 15px 12px; text-align: center; color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }};">
                        {{ number_format($item->quantity, 2, ',', '.') }}
                        @if($layout->settings['content']['show_unit_column'] ?? true && isset($item->unit) && $item->unit)
                            {{ $item->unit }}
                        @endif
                    </td>
                    <td style="padding: 15px 12px; text-align: right; color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }};">€ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                    <td style="padding: 15px 12px; text-align: right; font-weight: 400;">€ {{ number_format($item->total, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals: Elegant, centered alignment -->
    <div style="text-align: right; margin-top: 35px;">
        <table style="width: 380px; margin-left: auto; border-collapse: collapse;">
            <tr>
                <td style="padding: 10px 15px; text-align: left; border-bottom: 1px solid {{ $layout->settings['colors']['accent'] ?? '#e5e7eb' }}; font-weight: 300; color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }};">Zwischensumme</td>
                <td style="padding: 10px 15px; text-align: right; border-bottom: 1px solid {{ $layout->settings['colors']['accent'] ?? '#e5e7eb' }}; font-weight: 300;">€ {{ number_format($invoice->subtotal, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="padding: 10px 15px; text-align: left; border-bottom: 1px solid {{ $layout->settings['colors']['accent'] ?? '#e5e7eb' }}; font-weight: 300; color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }};">MwSt. {{ number_format($invoice->tax_rate * 100, 0) }}%</td>
                <td style="padding: 10px 15px; text-align: right; border-bottom: 1px solid {{ $layout->settings['colors']['accent'] ?? '#e5e7eb' }}; font-weight: 300;">€ {{ number_format($invoice->tax_amount, 2, ',', '.') }}</td>
            </tr>
            <tr style="border-top: 2px solid {{ $layout->settings['colors']['primary'] ?? '#059669' }};">
                <td style="padding: 15px; text-align: left; font-weight: 400; font-size: {{ $bodyFontSize + 2 }}px; color: {{ $layout->settings['colors']['primary'] ?? '#059669' }}; letter-spacing: 0.5px;">Gesamtbetrag</td>
                <td style="padding: 15px; text-align: right; font-weight: 400; font-size: {{ $bodyFontSize + 2 }}px; color: {{ $layout->settings['colors']['primary'] ?? '#059669' }};">€ {{ number_format($invoice->total, 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <!-- Payment Terms: Elegant styling -->
    @if($layout->settings['content']['show_payment_terms'] ?? true)
        <div style="margin-top: 45px; padding: 25px; background-color: #f9fafb; border-left: 3px solid {{ $layout->settings['colors']['primary'] ?? '#059669' }}; font-size: {{ $bodyFontSize }}px; line-height: 1.8; font-weight: 300; font-style: italic; color: {{ $layout->settings['colors']['text'] ?? '#4b5563' }};">
            Der Rechnungsbetrag ist innerhalb von {{ \Carbon\Carbon::parse($invoice->due_date)->diffInDays(\Carbon\Carbon::parse($invoice->issue_date)) }} Tagen nach Rechnungseingang fällig und ohne Abzug auf das unten angegebene Konto zu überweisen.
        </div>
    @endif

    <!-- Notes -->
    @if(($layout->settings['content']['show_notes'] ?? true) && isset($invoice->notes) && $invoice->notes)
        <div style="margin-top: 30px; padding: 20px; background-color: #fefefe; border-left: 3px solid {{ $layout->settings['colors']['accent'] ?? '#e5e7eb' }}; font-size: {{ $bodyFontSize }}px; line-height: 1.8; font-weight: 300; color: {{ $layout->settings['colors']['text'] ?? '#4b5563' }};">
            {{ $invoice->notes }}
        </div>
    @endif

    <!-- Footer: Elegant, centered -->
    @if($layout->settings['branding']['show_footer'] ?? true)
        <div style="margin-top: 60px; padding-top: 30px; border-top: 1px solid {{ $layout->settings['colors']['accent'] ?? '#e5e7eb' }}; text-align: center; font-size: {{ $bodyFontSize - 1 }}px; color: {{ $layout->settings['colors']['text'] ?? '#9ca3af' }}; line-height: 2; font-weight: 300;">
            <div style="margin-bottom: 10px;">
                <strong style="color: {{ $layout->settings['colors']['primary'] ?? '#059669' }}; font-weight: 400;">{{ $company->name }}</strong>
            </div>
            <div style="margin-bottom: 15px;">
                @if($company->address){{ $company->address }}@endif
                @if($company->postal_code && $company->city), {{ $company->postal_code }} {{ $company->city }}@endif
                @if($company->phone) · Tel: {{ $company->phone }}@endif
                @if($company->email) · E-Mail: {{ $company->email }}@endif
            </div>
            <div>
                @if($company->vat_number)USt-IdNr.: {{ $company->vat_number }}@endif
                @if($layout->settings['content']['show_bank_details'] ?? true && $company->bank_iban)
                    @if($company->vat_number) · @endif
                    IBAN: {{ $company->bank_iban }}
                    @if($company->bank_bic) · BIC: {{ $company->bank_bic }}@endif
                @endif
            </div>
        </div>
    @endif
</div>

