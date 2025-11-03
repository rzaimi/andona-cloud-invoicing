<!-- Creative Template: Modern, asymmetric layout with fresh accents -->
<div class="container">
    <!-- Header: Large, colorful -->
    <div style="background: linear-gradient(135deg, {{ $layout->settings['colors']['primary'] ?? '#8b5cf6' }} 0%, {{ $layout->settings['colors']['secondary'] ?? '#6366f1' }} 100%); color: white; padding: 30px; margin: -{{ $layout->settings['layout']['margin_top'] ?? '20' }}mm -{{ $layout->settings['layout']['margin_right'] ?? '20' }}mm 30px -{{ $layout->settings['layout']['margin_left'] ?? '20' }}mm; margin-bottom: 35px;">
        <table style="width: 100%;">
            <tr>
                <td style="width: 70%; vertical-align: middle;">
                    <div class="company-name" style="font-size: {{ $headingFontSize + 10 }}px; font-weight: bold; margin-bottom: 10px;">
                        {{ $company->name }}
                    </div>
                    @if($layout->settings['content']['show_company_address'] ?? true)
                        <div style="font-size: {{ $bodyFontSize }}px; opacity: 0.9; line-height: 1.6;">
                            @if($company->address){{ $company->address }}@endif
                            @if($company->postal_code && $company->city), {{ $company->postal_code }} {{ $company->city }}@endif
                        </div>
                    @endif
                </td>
                @if($company->logo)
                <td style="width: 30%; text-align: right; vertical-align: middle;">
                    <img src="{{ public_path('storage/' . $company->logo) }}" alt="Logo" style="max-height: 80px; max-width: 200px; background: white; padding: 10px; border-radius: 8px;">
                </td>
                @endif
            </tr>
        </table>
    </div>

    <!-- Invoice Title: Large, bold -->
    <div style="margin-bottom: 30px;">
        <div style="font-size: {{ $headingFontSize + 12 }}px; font-weight: bold; color: {{ $layout->settings['colors']['primary'] ?? '#8b5cf6' }}; margin-bottom: 8px;">
            RECHNUNG #{{ $invoice->number }}
        </div>
        <div style="font-size: {{ $bodyFontSize }}px; color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }};">
            Rechnungsdatum: {{ \Carbon\Carbon::parse($invoice->issue_date)->format('d.m.Y') }} · Fällig: {{ \Carbon\Carbon::parse($invoice->due_date)->format('d.m.Y') }}
        </div>
    </div>

    <!-- Two Column: Asymmetric layout -->
    <table style="width: 100%; margin-bottom: 35px;">
        <tr>
            <!-- Left: Customer (larger) -->
            <td style="width: 60%; vertical-align: top; padding-right: 20px;">
            <div style="font-size: {{ $bodyFontSize }}px; font-weight: bold; color: {{ $layout->settings['colors']['primary'] ?? '#8b5cf6' }}; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 3px solid {{ $layout->settings['colors']['primary'] ?? '#8b5cf6' }};">
                👤 Rechnungsempfänger
            </div>
            @if(isset($invoice->customer) && $invoice->customer)
                <div style="font-size: {{ $bodyFontSize + 2 }}px; font-weight: bold; margin-bottom: 8px; color: {{ $layout->settings['colors']['text'] ?? '#1f2937' }};">
                    {{ $invoice->customer->name ?? 'Unbekannt' }}
                </div>
                @if(isset($invoice->customer->contact_person) && $invoice->customer->contact_person)
                    <div style="margin-bottom: 8px; color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }};">{{ $invoice->customer->contact_person }}</div>
                @endif
                <div style="line-height: 1.8; color: {{ $layout->settings['colors']['text'] ?? '#374151' }};">
                    {{ $invoice->customer->address ?? '' }}<br>
                    {{ ($invoice->customer->postal_code ?? '') }} {{ ($invoice->customer->city ?? '') }}
                    @if(isset($invoice->customer->country) && $invoice->customer->country && $invoice->customer->country !== 'Deutschland')
                        <br>{{ $invoice->customer->country }}
                    @endif
                </div>
                @if(($layout->settings['content']['show_customer_number'] ?? true) && isset($invoice->customer->number) && $invoice->customer->number)
                    <div style="margin-top: 12px; padding: 8px; background-color: #f3f4f6; border-radius: 4px; font-size: {{ $bodyFontSize - 1 }}px;">
                        <strong>Kundennummer:</strong> {{ $invoice->customer->number }}
                    </div>
                @endif
            @endif
            </td>
            <!-- Right: Invoice Details (smaller) -->
            <td style="width: 40%; vertical-align: top; padding-left: 20px; background-color: #f9fafb; padding: 20px; border-left: 4px solid {{ $layout->settings['colors']['primary'] ?? '#8b5cf6' }};">
            <div style="font-size: {{ $bodyFontSize - 1 }}px; font-weight: bold; color: {{ $layout->settings['colors']['primary'] ?? '#8b5cf6' }}; margin-bottom: 12px; text-transform: uppercase;">
                Rechnungsdetails
            </div>
            <div style="font-size: {{ $bodyFontSize }}px; line-height: 2;">
                <div><strong>Datum:</strong> {{ \Carbon\Carbon::parse($invoice->issue_date)->format('d.m.Y') }}</div>
                <div><strong>Fällig:</strong> {{ \Carbon\Carbon::parse($invoice->due_date)->format('d.m.Y') }}</div>
                @if($layout->settings['content']['show_tax_number'] ?? true && $company->tax_number)
                    <div><strong>Steuernr.:</strong> {{ $company->tax_number }}</div>
                @endif
            </div>
            </td>
        </tr>
    </table>

    <!-- Items Table: Colorful header -->
    <table style="width: 100%; border-collapse: collapse; margin: 30px 0; border-radius: 8px; overflow: hidden;">
        <thead>
            <tr style="background: linear-gradient(135deg, {{ $layout->settings['colors']['primary'] ?? '#8b5cf6' }} 0%, {{ $layout->settings['colors']['secondary'] ?? '#6366f1' }} 100%); color: white;">
                <th style="padding: 14px 10px; text-align: left; font-weight: bold; font-size: {{ $bodyFontSize }}px;">#</th>
                <th style="padding: 14px 10px; text-align: left; font-weight: bold;">Bezeichnung</th>
                <th style="padding: 14px 10px; text-align: center; font-weight: bold;">Menge</th>
                <th style="padding: 14px 10px; text-align: right; font-weight: bold;">Einzelpreis</th>
                <th style="padding: 14px 10px; text-align: right; font-weight: bold;">Gesamt</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
                <tr style="border-bottom: 1px solid #e5e7eb; {{ $index % 2 == 0 ? 'background-color: #ffffff;' : 'background-color: #f9fafb;' }}">
                    <td style="padding: 12px 10px; font-weight: bold; color: {{ $layout->settings['colors']['primary'] ?? '#8b5cf6' }};">{{ $index + 1 }}</td>
                    <td style="padding: 12px 10px;">{{ $item->description }}</td>
                    <td style="padding: 12px 10px; text-align: center;">
                        {{ number_format($item->quantity, 2, ',', '.') }}
                        @if($layout->settings['content']['show_unit_column'] ?? true && isset($item->unit) && $item->unit)
                            {{ $item->unit }}
                        @endif
                    </td>
                    <td style="padding: 12px 10px; text-align: right;">€ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                    <td style="padding: 12px 10px; text-align: right; font-weight: bold;">€ {{ number_format($item->total, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals: Colorful box -->
    <div style="text-align: right; margin-top: 30px;">
        <table style="width: 350px; margin-left: auto; border-collapse: collapse; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <tr style="background-color: #f9fafb;">
                <td style="padding: 10px 15px; text-align: left; border-bottom: 1px solid #e5e7eb;">Zwischensumme</td>
                <td style="padding: 10px 15px; text-align: right; border-bottom: 1px solid #e5e7eb;">€ {{ number_format($invoice->subtotal, 2, ',', '.') }}</td>
            </tr>
            <tr style="background-color: #f9fafb;">
                <td style="padding: 10px 15px; text-align: left; border-bottom: 1px solid #e5e7eb;">MwSt. {{ number_format($invoice->tax_rate * 100, 0) }}%</td>
                <td style="padding: 10px 15px; text-align: right; border-bottom: 1px solid #e5e7eb;">€ {{ number_format($invoice->tax_amount, 2, ',', '.') }}</td>
            </tr>
            <tr style="background: linear-gradient(135deg, {{ $layout->settings['colors']['primary'] ?? '#8b5cf6' }} 0%, {{ $layout->settings['colors']['secondary'] ?? '#6366f1' }} 100%); color: white;">
                <td style="padding: 15px; font-weight: bold; font-size: {{ $bodyFontSize + 3 }}px;">Gesamtbetrag</td>
                <td style="padding: 15px; text-align: right; font-weight: bold; font-size: {{ $bodyFontSize + 3 }}px;">€ {{ number_format($invoice->total, 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <!-- Payment Terms: Styled box -->
    @if($layout->settings['content']['show_payment_terms'] ?? true)
        <div style="margin-top: 35px; padding: 20px; background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); border-radius: 8px; border-left: 5px solid {{ $layout->settings['colors']['primary'] ?? '#8b5cf6' }}; font-size: {{ $bodyFontSize }}px; line-height: 1.6;">
            <strong style="color: {{ $layout->settings['colors']['primary'] ?? '#8b5cf6' }};">💳 Zahlungsbedingungen:</strong><br>
            Bitte überweisen Sie den Rechnungsbetrag innerhalb von {{ \Carbon\Carbon::parse($invoice->due_date)->diffInDays(\Carbon\Carbon::parse($invoice->issue_date)) }} Tagen nach Rechnungseingang.
        </div>
    @endif

    <!-- Notes -->
    @if(($layout->settings['content']['show_notes'] ?? true) && isset($invoice->notes) && $invoice->notes)
        <div style="margin-top: 25px; padding: 18px; background-color: #fef3c7; border-radius: 8px; border-left: 4px solid #fbbf24; font-size: {{ $bodyFontSize }}px;">
            <strong>📝 Anmerkungen:</strong><br>
            {{ $invoice->notes }}
        </div>
    @endif

    <!-- Footer: Colorful -->
    @if($layout->settings['branding']['show_footer'] ?? true)
        <div style="margin-top: 50px; padding: 25px; background-color: #f9fafb; border-radius: 8px; font-size: {{ $bodyFontSize - 1 }}px; color: {{ $layout->settings['colors']['text'] ?? '#6b7280' }};">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 33%; vertical-align: top;">
                        <strong style="color: {{ $layout->settings['colors']['primary'] ?? '#8b5cf6' }};">{{ $company->name }}</strong><br>
                        @if($company->address){{ $company->address }}<br>@endif
                        @if($company->postal_code && $company->city){{ $company->postal_code }} {{ $company->city }}@endif
                        @if($company->vat_number)USt-IdNr.: {{ $company->vat_number }}@endif
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
        </div>
    @endif

    <!-- Signature -->
    <div style="margin-top: 50px;">
        <div style="margin-bottom: 40px;">Mit freundlichen Grüßen</div>
        @if($company->managing_director)
            <div style="font-size: {{ $headingFontSize + 2 }}px; font-weight: bold; color: {{ $layout->settings['colors']['primary'] ?? '#8b5cf6' }};">
                {{ $company->managing_director }}
            </div>
        @endif
    </div>
</div>

