{{-- Clean Template: Based on first screenshot - Simple, professional German invoice --}}
{{-- Template: clean --}}
<div class="container">
    {{-- Header: Logo and sender address with subtle background --}}
    <div style="margin-bottom: 8mm; padding: 3mm; background-color: #f9fafb; border-radius: 2mm;">
        @php
            $logoRelPath = isset($snapshot['logo']) ? ltrim(preg_replace('#^storage/#', '', (string)$snapshot['logo']), '/') : null;
        @endphp
        @if(($layoutSettings['branding']['show_logo'] ?? true) && $logoRelPath)
            @if(isset($preview) && $preview)
                <div style="margin-bottom: 3mm;">
                    <img src="{{ asset('storage/' . $logoRelPath) }}" alt="Logo" style="max-height: 20mm; max-width: 70mm;">
                </div>
            @elseif(\Storage::disk('public')->exists($logoRelPath))
                @php
                    $logoPath = \Storage::disk('public')->path($logoRelPath);
                    $logoData = base64_encode(file_get_contents($logoPath));
                    $logoMime = mime_content_type($logoPath);
                @endphp
                <div style="margin-bottom: 3mm;">
                    <img src="data:{{ $logoMime }};base64,{{ $logoData }}" alt="Logo" style="max-height: 20mm; max-width: 70mm;">
                </div>
            @endif
        @endif
        @if($layoutSettings['content']['show_company_address'] ?? true)
            <div style="font-size: {{ $bodyFontSize }}px; color: {{ $layoutSettings['colors']['text'] ?? '#1f2937' }}; line-height: 1.5;">
                {{ $snapshot['name'] ?? '' }}<br>
                @if($snapshot['address'] ?? null){{ $snapshot['address'] }}@endif
                @if(($snapshot['postal_code'] ?? null) && ($snapshot['city'] ?? null)), {{ $snapshot['postal_code'] }} {{ $snapshot['city'] }}@endif
            </div>
        @endif
    </div>

    {{-- DIN 5008 compliant layout: Address and Invoice Details side by side --}}
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 10mm;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 10mm;">
                @php $customer = $invoice->customer ?? null; @endphp
                @if($customer)
                    {{-- DIN 5008 Address Block --}}
                    <div class="din-5008-address">
                        {{-- Recipient address --}}
                        <div style="font-weight: 600; margin-bottom: 1mm; font-size: {{ $bodyFontSize }}px; line-height: 1.3;">
                            {{ $customer->name ?? 'Unbekannt' }}
                        </div>
                        @if(isset($customer->contact_person) && $customer->contact_person)
                            <div style="margin-bottom: 1mm; font-size: {{ $bodyFontSize }}px; line-height: 1.3;">{{ $customer->contact_person }}</div>
                        @endif
                        <div style="font-size: {{ $bodyFontSize }}px; line-height: 1.3;">
                            @if($customer->address)
                                {{ $customer->address }}<br>
                            @endif
                            @if($customer->postal_code && $customer->city)
                                {{ $customer->postal_code }} {{ $customer->city }}
                                @if($customer->country && $customer->country !== 'Deutschland')
                                    <br>{{ $customer->country }}
                                @endif
                            @endif
                            @if(isset($invoice->customer->vat_number) && $invoice->customer->vat_number)
                                <br>USt-IdNr.: {{ $invoice->customer->vat_number }}
                            @endif
                        </div>
                        {{-- Customer number --}}
                        @if(($layoutSettings['content']['show_customer_number'] ?? true) && isset($invoice->customer->number) && $invoice->customer->number)
                            <div style="margin-top: 2mm; font-size: {{ $bodyFontSize }}px;">
                                <strong>Kundennr.:</strong> {{ $invoice->customer->number }}
                            </div>
                        @endif
                    </div>
                @endif
            </td>
            <td style="width: 50%; vertical-align: top;">
                {{-- Invoice Details (German standard fields) --}}
                <div style="text-align: right; font-size: {{ $bodyFontSize }}px;">
                    <div style="margin-bottom: 2mm;"><strong>Rechnungsdatum:</strong> {{ formatInvoiceDate($invoice->issue_date, $dateFormat ?? 'd.m.Y') }}</div>
                    
                    @if(isset($invoice->service_date) && $invoice->service_date)
                        <div style="margin-bottom: 2mm;"><strong>Leistungsdatum:</strong> {{ formatInvoiceDate($invoice->service_date, $dateFormat ?? 'd.m.Y') }}</div>
                    @elseif(isset($invoice->service_period_start) && isset($invoice->service_period_end) && $invoice->service_period_start && $invoice->service_period_end)
                        <div style="margin-bottom: 2mm;"><strong>Leistungszeitraum:</strong> {{ formatInvoiceDate($invoice->service_period_start, $dateFormat ?? 'd.m.Y') }} - {{ formatInvoiceDate($invoice->service_period_end, $dateFormat ?? 'd.m.Y') }}</div>
                    @else
                        <div style="margin-bottom: 2mm;"><strong>Leistungsdatum:</strong> entspricht Rechnungsdatum</div>
                    @endif

                    <div style="margin-bottom: 2mm;"><strong>Fälligkeitsdatum:</strong> {{ formatInvoiceDate($invoice->due_date, $dateFormat ?? 'd.m.Y') }}</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Invoice Title --}}
    <div style="margin-bottom: 5mm;">
        @php
            $isCorrection = isset($invoice->is_correction) ? (bool)$invoice->is_correction : false;
        @endphp
        <div style="font-size: {{ $headingFontSize + 4 }}px; font-weight: 700; color: {{ $isCorrection ? '#dc2626' : ($layoutSettings['colors']['primary'] ?? '#1f2937') }};">
            {{ $isCorrection ? 'STORNORECHNUNG' : 'Rechnung' }} {{ $invoice->number }}
        </div>
        @if($isCorrection && isset($invoice->correctsInvoice) && $invoice->correctsInvoice)
            <div style="margin-top: 3mm; padding: 3mm; background-color: #fee2e2; border-left: 4px solid #dc2626; font-size: {{ $bodyFontSize }}px;">
                <div style="font-weight: 600; color: #991b1b; margin-bottom: 2mm;">Storniert Rechnung:</div>
                <div style="color: #7f1d1d;">Nr. {{ $invoice->correctsInvoice->number }} vom {{ formatInvoiceDate($invoice->correctsInvoice->issue_date, $dateFormat) }}</div>
                @if(isset($invoice->correction_reason) && $invoice->correction_reason)
                    <div style="margin-top: 2mm; padding-top: 2mm; border-top: 1px solid #dc2626;">
                        <strong>Grund:</strong> {{ $invoice->correction_reason }}
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Salutation and Introduction --}}
    <div style="margin-bottom: 15px; font-size: {{ $bodyFontSize }}px; line-height: 1.6;">
        <div style="margin-bottom: 4px;">Sehr geehrte Damen und Herren,</div>
        <div>vielen Dank für Ihren Auftrag und das damit verbundene Vertrauen! Hiermit stelle ich Ihnen die folgenden Leistungen in Rechnung:</div>
    </div>

    {{-- Items Table --}}
    <table style="width: 100%; border-collapse: collapse; margin: 15px 0;">
        <thead>
            <tr style="border-bottom: 2px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};">
                @if($layoutSettings['content']['show_item_codes'] ?? false)
                    <th style="padding: 8px 6px; text-align: left; font-weight: 600; font-size: {{ $bodyFontSize }}px; width: 12%;">PRODUKT-NR.</th>
                @endif
                <th style="padding: 8px 6px; text-align: left; font-weight: 600; font-size: {{ $bodyFontSize }}px; width: {{ ($layoutSettings['content']['show_item_codes'] ?? false) ? '45%' : '55%' }};">LEISTUNG</th>
                <th style="padding: 8px 6px; text-align: left; font-weight: 600; width: 9%;">MENGE</th>
                <th style="padding: 8px 6px; text-align: right; font-weight: 600; width: 6%;">UST.</th>
                <th style="padding: 8px 6px; text-align: right; font-weight: 600; width: 10%;">PREIS</th>
                <th style="padding: 8px 6px; text-align: right; font-weight: 600; width: 12%;">GESAMT</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
                @php
                    $discountAmount = (float)($item->discount_amount ?? 0);
                    $hasDiscount = $discountAmount > 0.0001;
                    $baseTotal = (float)($item->quantity ?? 0) * (float)($item->unit_price ?? 0);
                    $discountType = $item->discount_type ?? null;
                    $discountValue = $item->discount_value ?? null;
                    $productCode = data_get($item, 'product.number')
                        ?? data_get($item, 'product.sku')
                        ?? data_get($item, 'product_number')
                        ?? data_get($item, 'product_sku');
                @endphp
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    @if($layoutSettings['content']['show_item_codes'] ?? false)
                        <td style="padding: 8px 6px;">
                            {{ $productCode ?: '-' }}
                        </td>
                    @endif
                    <td style="padding: 8px 6px;">{{ $item->description }}</td>
                    <td style="padding: 8px 6px;">
                        {{ number_format($item->quantity, 2, ',', '.') }}
                        @if($layoutSettings['content']['show_unit_column'] ?? true && isset($item->unit) && $item->unit)
                            {{ $item->unit }}
                        @else
                            Std.
                        @endif
                    </td>
                    <td style="padding: 8px 6px; text-align: right;">{{ number_format(($item->tax_rate ?? $invoice->tax_rate ?? 0) * 100, 0, ',', '.') }}%</td>
                    <td style="padding: 8px 6px; text-align: right;">{{ number_format($item->unit_price, 2, ',', '.') }} €</td>
                    <td style="padding: 8px 6px; text-align: right;">
                        <div>{{ number_format($item->total, 2, ',', '.') }} €</div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- VAT Regime Note --}}
    @php $vatNote = getVatRegimeNote($invoice->vat_regime ?? 'standard'); @endphp
    @if($vatNote)
        <div style="margin-top: 10px; font-size: {{ $bodyFontSize }}px; font-style: italic;">
            {{ $vatNote }}
        </div>
    @endif

    {{-- Totals --}}
    <div style="text-align: right; margin-top: 15px;">
        @php
            $totalDiscount = 0;
            foreach ($invoice->items as $it) {
                $totalDiscount += (float)($it->discount_amount ?? 0);
            }
        @endphp
        <table style="width: 280px; margin-left: auto; border-collapse: collapse;">
            @if($totalDiscount > 0.0001)
                <tr>
                    <td style="padding: 6px 10px; text-align: left; border-bottom: 1px solid #e5e7eb;">Zwischensumme (vor Rabatt)</td>
                    <td style="padding: 6px 10px; text-align: right; border-bottom: 1px solid #e5e7eb;">{{ number_format($invoice->subtotal + $totalDiscount, 2, ',', '.') }} €</td>
                </tr>
                <tr>
                    <td style="padding: 6px 10px; text-align: left; border-bottom: 1px solid #e5e7eb;">Gesamtrabatt</td>
                    <td style="padding: 6px 10px; text-align: right; border-bottom: 1px solid #e5e7eb; color: #dc2626;">-{{ number_format($totalDiscount, 2, ',', '.') }} €</td>
                </tr>
            @endif
            <tr>
                <td style="padding: 6px 10px; text-align: left; border-bottom: 1px solid #e5e7eb;">Gesamtbetrag (netto)</td>
                <td style="padding: 6px 10px; text-align: right; border-bottom: 1px solid #e5e7eb;">{{ number_format($invoice->subtotal, 2, ',', '.') }} €</td>
            </tr>
            <tr>
                <td style="padding: 6px 10px; text-align: left; border-bottom: 1px solid #e5e7eb;">{{ number_format($invoice->tax_rate * 100, 0) }}% Umsatzsteuer</td>
                <td style="padding: 6px 10px; text-align: right; border-bottom: 1px solid #e5e7eb;">{{ number_format($invoice->tax_amount, 2, ',', '.') }} €</td>
            </tr>
            <tr>
                <td style="padding: 8px 10px; text-align: left; font-weight: 700; font-size: {{ $bodyFontSize + 1 }}px;">Gesamtbetrag (brutto)</td>
                <td style="padding: 8px 10px; text-align: right; font-weight: 700; font-size: {{ $bodyFontSize + 1 }}px;">{{ number_format($invoice->total, 2, ',', '.') }} €</td>
            </tr>
        </table>
    </div>

    {{-- Payment Instructions --}}
    @php
        $taxNote = $settings['invoice_tax_note'] ?? null;
    @endphp
    @if($taxNote)
        <div style="margin-top: 12px; font-size: {{ $bodyFontSize ?? 11 }}px; line-height: 1.5;">
            {{ $taxNote }}
        </div>
    @endif
    @if($layoutSettings['content']['show_payment_terms'] ?? true)
        <div style="margin-top: 20px; font-size: {{ $bodyFontSize }}px; line-height: 1.6;">
            Bitte überweisen Sie den Rechnungsbetrag unter Angabe der Rechnungsnummer auf das unten angegebene Konto. Der Rechnungsbetrag ist sofort fällig.
        </div>
    @endif

    {{-- Closing --}}
    <div style="margin-top: 20px; font-size: {{ $bodyFontSize }}px;">
        <div style="margin-bottom: 4px;">Mit freundlichen Grüßen</div>
        <div style="font-weight: 600;">{{ $snapshot['name'] ?? '' }}</div>
    </div>

    {{-- Footer Style A: Multi-line right-aligned blocks (German Example 1) --}}
    @if($layoutSettings['branding']['show_footer'] ?? true)
    <div class="pdf-footer" style="margin-top: 15mm; border-top: 1px solid {{ $layoutSettings['colors']['accent'] ?? '#e5e7eb' }}; padding-top: 3mm;">
        <table style="width: 100%; font-size: 7pt; line-height: 1.5; color: {{ $layoutSettings['colors']['text'] ?? '#6b7280' }}; border-collapse: collapse;">
            <tr>
                {{-- Column 1: Address (22%) --}}
                <td style="width: 22%; vertical-align: top; padding-right: 5px;">
                    @if($snapshot['name'] ?? null)<div style="font-weight: 600; margin-bottom: 2px;">{{ $snapshot['name'] }}</div>@endif
                    @if($snapshot['address'] ?? null)<div>{{ $snapshot['address'] }}</div>@endif
                    @if(($snapshot['postal_code'] ?? null) && ($snapshot['city'] ?? null))
                        <div>{{ $snapshot['postal_code'] }} {{ $snapshot['city'] }}</div>
                    @endif
                </td>
                {{-- Column 2: Contact (24%) --}}
                <td style="width: 24%; vertical-align: top; padding-right: 5px;">
                    @if($snapshot['phone'] ?? null)<div><strong>FON</strong> {{ $snapshot['phone'] }}</div>@endif
                    @if($snapshot['email'] ?? null)<div><strong>MAIL</strong> {{ $snapshot['email'] }}</div>@endif
                    @if($snapshot['website'] ?? null)<div><strong>WEB</strong> {{ $snapshot['website'] }}</div>@endif
                </td>
                {{-- Column 3: Tax Info (18%) --}}
                <td style="width: 18%; vertical-align: top; padding-right: 5px;">
                    @if($snapshot['tax_number'] ?? null)<div><strong>Steuernr.</strong> {{ $snapshot['tax_number'] }}</div>@endif
                    @if($snapshot['vat_number'] ?? null)<div><strong>UST-ID</strong> {{ $snapshot['vat_number'] }}</div>@endif
                </td>
                {{-- Column 4: Banking (36%) --}}
                @if($layoutSettings['content']['show_bank_details'] ?? true)
                <td style="width: 36%; vertical-align: top;">
                    @if($snapshot['bank_name'] ?? null)<div><strong>BANK</strong> {{ $snapshot['bank_name'] }}</div>@endif
                    @if($snapshot['bank_iban'] ?? null)<div><strong>IBAN</strong> {{ $snapshot['bank_iban'] }}</div>@endif
                    @if($snapshot['bank_bic'] ?? null)<div><strong>BIC</strong> {{ $snapshot['bank_bic'] }}</div>@endif
                </td>
                @endif
            </tr>
        </table>
    </div>
    @endif

</div>
