<!-- Modern Template: Contemporary design with clean lines -->
<div class="container">
    <!-- Header: Modern, balanced layout -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 35px; padding-bottom: 20px; border-bottom: 2px solid {{ $layout->settings['colors']['primary'] ?? '#3b82f6' }};">
        <div style="flex: 1;">
            @if(($layout->settings['branding']['show_logo'] ?? true) && $company->logo)
                <div style="margin-bottom: 15px;">
                    <img src="{{ public_path('storage/' . $company->logo) }}" alt="Logo" style="max-height: 70px; max-width: 220px;">
                </div>
            @endif
            <div class="company-name" style="font-size: {{ $headingFontSize + 4 }}px; font-weight: 600; color: {{ $layout->settings['colors']['primary'] ?? '#3b82f6' }}; margin-bottom: 10px;">
                {{ $company->name }}
            </div>
            @if($layout->settings['content']['show_company_address'] ?? true)
                <div style="font-size: {{ $bodyFontSize }}px; color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }}; line-height: 1.6;">
                    @if($company->address){{ $company->address }}@endif
                    @if($company->postal_code && $company->city), {{ $company->postal_code }} {{ $company->city }}@endif
                    @if($company->country && $company->country !== 'Deutschland')<br>{{ $company->country }}@endif
                </div>
            @endif
            @if($layout->settings['content']['show_company_contact'] ?? true && ($company->phone || $company->email))
                <div style="font-size: {{ $bodyFontSize - 1 }}px; color: {{ $layout->settings['colors']['text'] ?? '#9ca3af' }}; margin-top: 8px;">
                    @if($company->email){{ $company->email }}@endif
                    @if($company->email && $company->phone) · @endif
                    @if($company->phone){{ $company->phone }}@endif
                </div>
            @endif
        </div>
    </div>

    <!-- Invoice Title: Modern, bold -->
    <div style="margin-bottom: 30px;">
        <div style="font-size: {{ $headingFontSize + 6 }}px; font-weight: 700; color: {{ $invoice->is_correction ? '#dc2626' : ($layout->settings['colors']['primary'] ?? '#3b82f6') }}; margin-bottom: 8px;">
            {{ $invoice->is_correction ? 'STORNORECHNUNG' : 'RECHNUNG' }}
        </div>
        <div style="font-size: {{ $bodyFontSize + 1 }}px; color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }};">
            Nr. {{ $invoice->number }} · {{ \Carbon\Carbon::parse($invoice->issue_date)->format('d.m.Y') }}
        </div>
        @if($invoice->is_correction && $invoice->correctsInvoice)
            <div style="margin-top: 15px; padding: 15px; background-color: #fee2e2; border-left: 4px solid #dc2626; border-radius: 6px; font-size: {{ $bodyFontSize }}px;">
                <div style="font-weight: 600; color: #991b1b; margin-bottom: 6px;">Storniert Rechnung:</div>
                <div style="color: #7f1d1d;">Nr. {{ $invoice->correctsInvoice->number }} vom {{ \Carbon\Carbon::parse($invoice->correctsInvoice->issue_date)->format('d.m.Y') }}</div>
                @if($invoice->correction_reason)
                    <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #dc2626;">
                        <strong>Grund:</strong> {{ $invoice->correction_reason }}
                    </div>
                @endif
            </div>
        @endif
    </div>

    <!-- Customer: Modern block -->
    @if(isset($invoice->customer) && $invoice->customer)
        <div style="margin-bottom: 35px; padding: 15px; background-color: {{ $layout->settings['colors']['accent'] ?? '#f3f4f6' }}; border-left: 4px solid {{ $layout->settings['colors']['primary'] ?? '#3b82f6' }};">
            <div style="font-size: {{ $bodyFontSize }}px; font-weight: 600; margin-bottom: 8px; color: {{ $layout->settings['colors']['text'] ?? '#1f2937' }};">
                {{ $invoice->customer->name ?? 'Unbekannt' }}
            </div>
            <div style="font-size: {{ $bodyFontSize }}px; color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }}; line-height: 1.6;">
                @if(isset($invoice->customer->contact_person) && $invoice->customer->contact_person)
                    {{ $invoice->customer->contact_person }}<br>
                @endif
                {{ $invoice->customer->address ?? '' }}<br>
                {{ ($invoice->customer->postal_code ?? '') }} {{ ($invoice->customer->city ?? '') }}
                @if(isset($invoice->customer->country) && $invoice->customer->country && $invoice->customer->country !== 'Deutschland')
                    <br>{{ $invoice->customer->country }}
                @endif
                @if(($layout->settings['content']['show_customer_number'] ?? true) && isset($invoice->customer->number) && $invoice->customer->number)
                    <br><br><strong>Kundennummer:</strong> {{ $invoice->customer->number }}
                @endif
            </div>
        </div>
    @endif

    <!-- Items Table: Modern, clean -->
    <table style="width: 100%; border-collapse: collapse; margin: 30px 0;">
        <thead>
            <tr style="background-color: {{ $layout->settings['colors']['primary'] ?? '#3b82f6' }}; color: white;">
                <th style="padding: 12px 8px; text-align: left; font-weight: 600; font-size: {{ $bodyFontSize }}px;">Pos.</th>
                <th style="padding: 12px 8px; text-align: left; font-weight: 600;">Beschreibung</th>
                <th style="padding: 12px 8px; text-align: right; font-weight: 600;">Menge</th>
                <th style="padding: 12px 8px; text-align: right; font-weight: 600;">Einzelpreis</th>
                <th style="padding: 12px 8px; text-align: right; font-weight: 600;">Gesamt</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
                <tr style="border-bottom: 1px solid {{ $layout->settings['colors']['accent'] ?? '#e5e7eb' }};">
                    <td style="padding: 12px 8px; color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }};">{{ $index + 1 }}</td>
                    <td style="padding: 12px 8px;">{{ $item->description }}</td>
                    <td style="padding: 12px 8px; text-align: right;">
                        {{ number_format($item->quantity, 2, ',', '.') }}
                        @if($layout->settings['content']['show_unit_column'] ?? true && isset($item->unit) && $item->unit)
                            {{ $item->unit }}
                        @endif
                    </td>
                    <td style="padding: 12px 8px; text-align: right;">€ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                    <td style="padding: 12px 8px; text-align: right; font-weight: 600;">€ {{ number_format($item->total, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals: Modern, right aligned -->
    <div style="text-align: right; margin-top: 25px;">
        <table style="width: 280px; margin-left: auto; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 12px; text-align: left; border-bottom: 1px solid {{ $layout->settings['colors']['accent'] ?? '#e5e7eb' }}; color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }};">Zwischensumme</td>
                <td style="padding: 8px 12px; text-align: right; border-bottom: 1px solid {{ $layout->settings['colors']['accent'] ?? '#e5e7eb' }};">€ {{ number_format($invoice->subtotal, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 12px; text-align: left; border-bottom: 1px solid {{ $layout->settings['colors']['accent'] ?? '#e5e7eb' }}; color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }};">MwSt. {{ number_format($invoice->tax_rate * 100, 0) }}%</td>
                <td style="padding: 8px 12px; text-align: right; border-bottom: 1px solid {{ $layout->settings['colors']['accent'] ?? '#e5e7eb' }};">€ {{ number_format($invoice->tax_amount, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="padding: 12px; text-align: left; background-color: {{ $layout->settings['colors']['primary'] ?? '#3b82f6' }}; color: white; font-weight: 700; font-size: {{ $bodyFontSize + 1 }}px;">Gesamt</td>
                <td style="padding: 12px; text-align: right; background-color: {{ $layout->settings['colors']['primary'] ?? '#3b82f6' }}; color: white; font-weight: 700; font-size: {{ $bodyFontSize + 1 }}px;">€ {{ number_format($invoice->total, 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <!-- Payment Terms: Modern styling -->
    @if($layout->settings['content']['show_payment_terms'] ?? true)
        <div style="margin-top: 40px; padding: 15px; background-color: {{ $layout->settings['colors']['accent'] ?? '#f9fafb' }}; border-radius: 6px; font-size: {{ $bodyFontSize }}px; line-height: 1.6; color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }};">
            <strong>Zahlungsbedingungen:</strong> Zahlung innerhalb von {{ \Carbon\Carbon::parse($invoice->due_date)->diffInDays(\Carbon\Carbon::parse($invoice->issue_date)) }} Tagen ohne Abzug.
        </div>
    @endif

    <!-- Notes -->
    @if(($layout->settings['content']['show_notes'] ?? true) && isset($invoice->notes) && $invoice->notes)
        <div style="margin-top: 30px; padding: 15px; border-left: 3px solid {{ $layout->settings['colors']['secondary'] ?? '#6366f1' }}; background-color: {{ $layout->settings['colors']['accent'] ?? '#f9fafb' }}; font-size: {{ $bodyFontSize }}px; line-height: 1.6;">
            {{ $invoice->notes }}
        </div>
    @endif

    <!-- Footer: Modern -->
    @if($layout->settings['branding']['show_footer'] ?? true)
        <div style="margin-top: 50px; padding-top: 20px; border-top: 1px solid {{ $layout->settings['colors']['accent'] ?? '#e5e7eb' }}; font-size: {{ $bodyFontSize - 1 }}px; color: {{ $layout->settings['colors']['text'] ?? '#9ca3af' }}; line-height: 1.8;">
            @if($company->address){{ $company->address }}@endif
            @if($company->postal_code && $company->city), {{ $company->postal_code }} {{ $company->city }}@endif
            @if($company->email) · {{ $company->email }}@endif
            @if($company->phone) · {{ $company->phone }}@endif
            @if($company->vat_number) · USt-IdNr.: {{ $company->vat_number }}@endif
            @if($layout->settings['content']['show_bank_details'] ?? true && $company->bank_iban)
                <br>IBAN: {{ $company->bank_iban }}
                @if($company->bank_bic) · BIC: {{ $company->bank_bic }}@endif
            @endif
        </div>
    @endif
</div>

