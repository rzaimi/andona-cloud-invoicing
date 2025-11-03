<!-- Minimal Template: Focus on content, minimal design -->
<div class="container">
    <!-- Header: Simple, left-aligned -->
    <div style="margin-bottom: 40px;">
        @if($company->logo)
            <div style="margin-bottom: 15px;">
                <img src="{{ public_path('storage/' . $company->logo) }}" alt="Logo" style="max-height: 60px; max-width: 200px;">
            </div>
        @endif
        <div class="company-name" style="font-size: {{ $headingFontSize + 2 }}px; font-weight: bold; color: {{ $layout->settings['colors']['text'] ?? '#1f2937' }}; margin-bottom: 5px;">
            {{ $company->name }}
        </div>
        @if($layout->settings['content']['show_company_address'] ?? true)
            <div style="font-size: {{ $bodyFontSize }}px; color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }}; line-height: 1.5;">
                @if($company->address){{ $company->address }}@endif
                @if($company->postal_code && $company->city), {{ $company->postal_code }} {{ $company->city }}@endif
            </div>
        @endif
        @if($layout->settings['content']['show_company_contact'] ?? true && ($company->phone || $company->email))
            <div style="font-size: {{ $bodyFontSize - 1 }}px; color: {{ $layout->settings['colors']['text'] ?? '#9ca3af' }}; margin-top: 3px;">
                @if($company->email){{ $company->email }}@endif
                @if($company->email && $company->phone) · @endif
                @if($company->phone){{ $company->phone }}@endif
            </div>
        @endif
    </div>

    <!-- Invoice Title: Simple, bold -->
    <div style="margin-bottom: 30px;">
        <div style="font-size: {{ $headingFontSize + 4 }}px; font-weight: bold; color: {{ $layout->settings['colors']['text'] ?? '#1f2937' }}; margin-bottom: 5px;">
            Rechnung {{ $invoice->number }}
        </div>
        <div style="font-size: {{ $bodyFontSize }}px; color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }};">
            {{ \Carbon\Carbon::parse($invoice->issue_date)->format('d.m.Y') }}
        </div>
    </div>

    <!-- Customer: Simple block -->
    @if(isset($invoice->customer) && $invoice->customer)
        <div style="margin-bottom: 35px;">
            <div style="font-size: {{ $bodyFontSize }}px; font-weight: bold; margin-bottom: 8px; color: {{ $layout->settings['colors']['text'] ?? '#1f2937' }};">
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
                    <br><br>Kundennummer: {{ $invoice->customer->number }}
                @endif
            </div>
        </div>
    @endif

    <!-- Items Table: Clean, minimal borders -->
    <table style="width: 100%; border-collapse: collapse; margin: 30px 0;">
        <thead>
            <tr style="border-bottom: 2px solid {{ $layout->settings['colors']['text'] ?? '#1f2937' }};">
                <th style="padding: 10px 0; text-align: left; font-weight: bold; font-size: {{ $bodyFontSize }}px; color: {{ $layout->settings['colors']['text'] ?? '#1f2937' }};">Pos.</th>
                <th style="padding: 10px 0; text-align: left; font-weight: bold;">Beschreibung</th>
                <th style="padding: 10px 0; text-align: right; font-weight: bold;">Menge</th>
                <th style="padding: 10px 0; text-align: right; font-weight: bold;">Einzelpreis</th>
                <th style="padding: 10px 0; text-align: right; font-weight: bold;">Gesamt</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 10px 0;">{{ $index + 1 }}</td>
                    <td style="padding: 10px 0;">{{ $item->description }}</td>
                    <td style="padding: 10px 0; text-align: right;">
                        {{ number_format($item->quantity, 2, ',', '.') }}
                        @if($layout->settings['content']['show_unit_column'] ?? true && isset($item->unit) && $item->unit)
                            {{ $item->unit }}
                        @endif
                    </td>
                    <td style="padding: 10px 0; text-align: right;">€ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                    <td style="padding: 10px 0; text-align: right;">€ {{ number_format($item->total, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals: Right aligned, simple -->
    <div style="text-align: right; margin-top: 25px;">
        <table style="width: 250px; margin-left: auto; border-collapse: collapse;">
            <tr>
                <td style="padding: 6px 0; text-align: left; border-bottom: 1px solid #e5e7eb;">Zwischensumme</td>
                <td style="padding: 6px 0; text-align: right; border-bottom: 1px solid #e5e7eb;">€ {{ number_format($invoice->subtotal, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="padding: 6px 0; text-align: left; border-bottom: 1px solid #e5e7eb;">MwSt. {{ number_format($invoice->tax_rate * 100, 0) }}%</td>
                <td style="padding: 6px 0; text-align: right; border-bottom: 1px solid #e5e7eb;">€ {{ number_format($invoice->tax_amount, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="padding: 12px 0; text-align: left; border-top: 2px solid {{ $layout->settings['colors']['text'] ?? '#1f2937' }}; font-weight: bold; font-size: {{ $bodyFontSize + 1 }}px;">Gesamt</td>
                <td style="padding: 12px 0; text-align: right; border-top: 2px solid {{ $layout->settings['colors']['text'] ?? '#1f2937' }}; font-weight: bold; font-size: {{ $bodyFontSize + 1 }}px;">€ {{ number_format($invoice->total, 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <!-- Payment Terms: Simple text -->
    @if($layout->settings['content']['show_payment_terms'] ?? true)
        <div style="margin-top: 40px; font-size: {{ $bodyFontSize }}px; line-height: 1.6; color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }};">
            Zahlung innerhalb von {{ \Carbon\Carbon::parse($invoice->due_date)->diffInDays(\Carbon\Carbon::parse($invoice->issue_date)) }} Tagen ohne Abzug.
        </div>
    @endif

    <!-- Notes -->
    @if(($layout->settings['content']['show_notes'] ?? true) && isset($invoice->notes) && $invoice->notes)
        <div style="margin-top: 30px; font-size: {{ $bodyFontSize }}px; line-height: 1.6;">
            {{ $invoice->notes }}
        </div>
    @endif

    <!-- Footer: Minimal -->
    @if($layout->settings['branding']['show_footer'] ?? true)
        <div style="margin-top: 50px; padding-top: 20px; border-top: 1px solid #e5e7eb; font-size: {{ $bodyFontSize - 1 }}px; color: {{ $layout->settings['colors']['text'] ?? '#9ca3af' }}; line-height: 1.8;">
            @if($company->address){{ $company->address }}, @endif
            @if($company->postal_code && $company->city){{ $company->postal_code }} {{ $company->city }}@endif
            @if($company->email) · {{ $company->email }}@endif
            @if($company->phone) · {{ $company->phone }}@endif
            @if($company->vat_number) · USt-IdNr.: {{ $company->vat_number }}@endif
            @if($layout->settings['content']['show_bank_details'] ?? true && $company->bank_iban)
                <br>IBAN: {{ $company->bank_iban }}
                @if($company->bank_bic) · BIC: {{ $company->bank_bic }}@endif
            @endif
        </div>
    @endif

    <!-- Signature: Minimal -->
    <div style="margin-top: 50px;">
        <div style="margin-bottom: 40px;">Mit freundlichen Grüßen</div>
        @if($company->managing_director)
            <div style="font-size: {{ $bodyFontSize }}px; color: {{ $layout->settings['colors']['text'] ?? '#1f2937' }};">
                {{ $company->managing_director }}
            </div>
        @endif
    </div>
</div>

