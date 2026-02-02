{{-- Classic Template: Traditional German invoice layout with structured design --}}
{{-- Template: classic - DISTINCTIVE: Full borders, dark header, boxed totals --}}
<div class="container">
    {{-- Header: Logo and company info with border --}}
    <div style="margin-bottom: 5mm; padding-bottom: 2mm; border-bottom: 1px solid {{ $layoutSettings['colors']['accent'] ?? '#e5e7eb' }};">
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
                @if($snapshot['address'] ?? null){{ $snapshot['address'] }}<br>@endif
                @if(($snapshot['postal_code'] ?? null) && ($snapshot['city'] ?? null)){{ $snapshot['postal_code'] }} {{ $snapshot['city'] }}@endif
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
                        </div>
                    </div>
                @endif
            </td>
            <td style="width: 50%; vertical-align: top;">
                {{-- Invoice details --}}
                <div style="text-align: right; font-size: {{ $bodyFontSize }}px;">
                    <div style="margin-bottom: 2mm;"><strong>Rechnungsdatum:</strong> {{ formatInvoiceDate($invoice->issue_date, $dateFormat ?? 'd.m.Y') }}</div>
                    <div style="margin-bottom: 2mm;"><strong>Leistungsdatum:</strong> entspricht Rechnungsdatum</div>
                    <div style="margin-bottom: 2mm;"><strong>Fälligkeitsdatum:</strong> {{ formatInvoiceDate($invoice->due_date, $dateFormat ?? 'd.m.Y') }}</div>
                    @if(isset($invoice->customer->number) && $invoice->customer->number)
                        <div style="margin-bottom: 2mm;"><strong>Kundennummer:</strong> {{ $invoice->customer->number }}</div>
                    @endif
                    @if(isset($invoice->customer->contact_person) && $invoice->customer->contact_person)
                        <div><strong>Ansprechpartner:</strong> {{ $invoice->customer->contact_person }}</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- Invoice Title - Classic centered style --}}
    <div style="text-align: center; margin-bottom: 10px;">
        @php
            $isCorrection = isset($invoice->is_correction) ? (bool)$invoice->is_correction : false;
        @endphp
        <div style="font-size: {{ $headingFontSize + 6 }}px; font-weight: 700; color: {{ $isCorrection ? '#dc2626' : ($layoutSettings['colors']['text'] ?? '#1f2937') }}; text-transform: uppercase; letter-spacing: 1px;">
            {{ $isCorrection ? 'STORNORECHNUNG' : 'RECHNUNG' }}
        </div>
        <div style="font-size: {{ $bodyFontSize + 1 }}px; color: {{ $layoutSettings['colors']['text'] ?? '#6b7280' }}; margin-top: 4px;">
            Nr. {{ $invoice->number }}
        </div>
        @if($isCorrection && isset($invoice->correctsInvoice) && $invoice->correctsInvoice)
            <div style="margin-top: 10px; padding: 10px; background-color: #fee2e2; border: 2px solid #dc2626; border-radius: 4px; font-size: {{ $bodyFontSize }}px; display: inline-block;">
                <div style="font-weight: 600; color: #991b1b; margin-bottom: 4px;">Storniert Rechnung:</div>
                <div style="color: #7f1d1d;">Nr. {{ $invoice->correctsInvoice->number }} vom {{ formatInvoiceDate($invoice->correctsInvoice->issue_date, $dateFormat ?? 'd.m.Y') }}</div>
                @if(isset($invoice->correction_reason) && $invoice->correction_reason)
                    <div style="margin-top: 6px; padding-top: 6px; border-top: 1px solid #dc2626;">
                        <strong>Grund:</strong> {{ $invoice->correction_reason }}
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Salutation and Introduction --}}
    <div style="margin-bottom: 10px; font-size: {{ $bodyFontSize }}px; line-height: 1.5;">
        <div style="margin-bottom: 4px;">Sehr geehrte Damen und Herren,</div>
        <div>vielen Dank für Ihren Auftrag und das damit verbundene Vertrauen! Hiermit stelle ich Ihnen die folgenden Leistungen in Rechnung:</div>
    </div>

    {{-- Items Table - Classic bordered style --}}
    <table style="width: 100%; border-collapse: collapse; margin: 10px 0; border: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};">
        <thead>
            <tr style="background-color: {{ $layoutSettings['colors']['text'] ?? '#1f2937' }}; color: white;">
                <th style="padding: 8px 6px; text-align: left; font-weight: 600; font-size: {{ $bodyFontSize }}px; border-right: 1px solid white; width: 5%;">Nr.</th>
                @if($layoutSettings['content']['show_item_codes'] ?? false)
                    <th style="padding: 8px 6px; text-align: left; font-weight: 600; border-right: 1px solid white; width: 12%;">Produkt-Nr.</th>
                @endif
                <th style="padding: 8px 6px; text-align: left; font-weight: 600; border-right: 1px solid white; width: {{ ($layoutSettings['content']['show_item_codes'] ?? false) ? '46%' : '58%' }};">Leistung</th>
                <th style="padding: 8px 6px; text-align: center; font-weight: 600; border-right: 1px solid white; width: 9%;">Menge</th>
                <th style="padding: 8px 6px; text-align: right; font-weight: 600; border-right: 1px solid white; width: 6%;">USt.</th>
                <th style="padding: 8px 6px; text-align: right; font-weight: 600; border-right: 1px solid white; width: 10%;">Preis</th>
                <th style="padding: 8px 6px; text-align: right; font-weight: 600; width: 12%;">Gesamt</th>
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
                <tr style="border-bottom: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};">
                    <td style="padding: 8px 6px; border-right: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};">{{ $index + 1 }}</td>
                    @if($layoutSettings['content']['show_item_codes'] ?? false)
                        <td style="padding: 8px 6px; border-right: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};">{{ $productCode ?: '-' }}</td>
                    @endif
                    <td style="padding: 8px 6px; border-right: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};">{{ $item->description }}</td>
                    <td style="padding: 8px 6px; text-align: center; border-right: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};">
                        {{ number_format($item->quantity, 2, ',', '.') }}
                        @if($layoutSettings['content']['show_unit_column'] ?? true && isset($item->unit) && $item->unit)
                            {{ $item->unit }}
                        @else
                            Std.
                        @endif
                    </td>
                    <td style="padding: 8px 6px; text-align: right; border-right: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};">{{ number_format(($item->tax_rate ?? $invoice->tax_rate ?? 0) * 100, 0, ',', '.') }}%</td>
                    <td style="padding: 8px 6px; text-align: right; border-right: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};">{{ number_format($item->unit_price, 2, ',', '.') }} €</td>
                    <td style="padding: 8px 6px; text-align: right; font-weight: 600;">
                        <div>{{ number_format($item->total, 2, ',', '.') }} €</div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals - Classic boxed style --}}
    <div style="text-align: right; margin-top: 10px;">
        @php
            $totalDiscount = 0;
            foreach ($invoice->items as $it) {
                $totalDiscount += (float)($it->discount_amount ?? 0);
            }
        @endphp
        <table style="width: 300px; margin-left: auto; border-collapse: collapse; border: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};">
            @if($totalDiscount > 0.0001)
                <tr>
                    <td style="padding: 6px 10px; text-align: left; border-bottom: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};">Zwischensumme (vor Rabatt)</td>
                    <td style="padding: 6px 10px; text-align: right; border-bottom: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};">{{ number_format($invoice->subtotal + $totalDiscount, 2, ',', '.') }} €</td>
                </tr>
                <tr>
                    <td style="padding: 6px 10px; text-align: left; border-bottom: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};">Gesamtrabatt</td>
                    <td style="padding: 6px 10px; text-align: right; border-bottom: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }}; color: #dc2626;">-{{ number_format($totalDiscount, 2, ',', '.') }} €</td>
                </tr>
            @endif
            <tr>
                <td style="padding: 6px 10px; text-align: left; border-bottom: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};">Gesamtbetrag (netto)</td>
                <td style="padding: 6px 10px; text-align: right; border-bottom: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};">{{ number_format($invoice->subtotal, 2, ',', '.') }} €</td>
            </tr>
            <tr>
                <td style="padding: 6px 10px; text-align: left; border-bottom: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};">{{ number_format($invoice->tax_rate * 100, 0) }}% Umsatzsteuer</td>
                <td style="padding: 6px 10px; text-align: right; border-bottom: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};">{{ number_format($invoice->tax_amount, 2, ',', '.') }} €</td>
            </tr>
            <tr style="background-color: {{ $layoutSettings['colors']['primary'] ?? '#1f2937' }}; color: white;">
                <td style="padding: 8px 10px; font-weight: 700; font-size: {{ $bodyFontSize + 1 }}px;">Rechnungsbetrag</td>
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
        <div style="margin-top: 12px; padding: 8px; background-color: {{ $layoutSettings['colors']['accent'] ?? '#f9fafb' }}; border-left: 4px solid {{ $layoutSettings['colors']['primary'] ?? '#1f2937' }}; font-size: {{ $bodyFontSize }}px; line-height: 1.5;">
            <strong>Zahlungsbedingungen:</strong> Der Rechnungsbetrag ist innerhalb von {{ \Carbon\Carbon::parse($invoice->due_date)->diffInDays(\Carbon\Carbon::parse($invoice->issue_date)) }} Tagen nach Rechnungseingang fällig und ohne Abzug auf das unten angegebene Konto zu überweisen.
        </div>
    @endif

    {{-- Closing --}}
    <div style="margin-top: 12px; font-size: {{ $bodyFontSize }}px;">
        <div style="margin-bottom: 4px;">Mit freundlichen Grüßen</div>
        <div style="font-weight: 600;">{{ $snapshot['name'] ?? '' }}</div>
    </div>

    {{-- Footer Style C: Multi-column structured (German Example 3) --}}
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

