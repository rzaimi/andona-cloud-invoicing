{{-- Modern Template: Modern layout with colored left border - DISTINCTIVE FEATURE --}}
{{-- Template: modern --}}
<div class="container">
@php
    $snapshot = $invoice->getCompanySnapshot();
    $customer = $invoice->customer ?? null;
@endphp
@if($customer)
    <div class="din-5008-address">
        <div style="font-weight: 600; margin-bottom: 3px; font-size: {{ $bodyFontSize }}px; line-height: 1.2;">
            {{ $customer->name ?? 'Unbekannt' }}
        </div>
        @if(isset($customer->contact_person) && $customer->contact_person)
            <div style="margin-bottom: 2px; font-size: {{ $bodyFontSize }}px; line-height: 1.2;">{{ $customer->contact_person }}</div>
        @endif
        <div style="font-size: {{ $bodyFontSize }}px; line-height: 1.2;">
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
        @if(isset($customer->vat_number) && $customer->vat_number)
            <div style="margin-top: 2px; font-size: {{ $bodyFontSize }}px; line-height: 1.2;">
                USt-IdNr.: {{ $customer->vat_number }}
            </div>
        @endif
    </div>
@endif
    {{-- Header: Logo and company name with colored left border accent --}}
    <div style="margin-bottom: 15px; padding-left: 12px; border-left: 4px solid {{ $layoutSettings['colors']['primary'] ?? '#3b82f6' }};">
        @php
            $logoRelPath = isset($snapshot['logo']) ? ltrim(preg_replace('#^storage/#', '', (string)$snapshot['logo']), '/') : null;
        @endphp
        @if(($layoutSettings['branding']['show_logo'] ?? true) && $logoRelPath)
            @if(isset($preview) && $preview)
                <div style="margin-bottom: 8px;">
                    <img src="{{ asset('storage/' . $logoRelPath) }}" alt="Logo" style="max-height: 60px; max-width: 200px;">
                </div>
            @elseif(\Storage::disk('public')->exists($logoRelPath))
                @php
                    $logoPath = \Storage::disk('public')->path($logoRelPath);
                    $logoData = base64_encode(file_get_contents($logoPath));
                    $logoMime = mime_content_type($logoPath);
                @endphp
                <div style="margin-bottom: 8px;">
                    <img src="data:{{ $logoMime }};base64,{{ $logoData }}" alt="Logo" style="max-height: 60px; max-width: 200px;">
                </div>
            @endif
        @endif
        <div style="font-size: {{ $headingFontSize + 6 }}px; font-weight: 700; color: {{ $layoutSettings['colors']['primary'] ?? '#3b82f6' }}; margin-bottom: 6px;">
            {{ $snapshot['name'] ?? '' }}
        </div>
        @if($layoutSettings['content']['show_company_address'] ?? true)
            <div style="font-size: {{ $bodyFontSize }}px; color: {{ $layoutSettings['colors']['text'] ?? '#6b7280' }}; line-height: 1.5;">
                {{ $snapshot['address'] ?? '' }}
                @if(($snapshot['postal_code'] ?? null) && ($snapshot['city'] ?? null)), {{ $snapshot['postal_code'] }} {{ $snapshot['city'] }}@endif
            </div>
        @endif
    </div>

    {{-- Invoice Details Right --}}
    <div style="text-align: right; font-size: {{ $bodyFontSize }}px; margin-bottom: 15px; padding: 10px; background-color: {{ $layoutSettings['colors']['accent'] ?? '#f3f4f6' }}; border-radius: 4px;">
        <div style="margin-bottom: 4px;"><strong>DATUM:</strong> {{ formatInvoiceDate($invoice->issue_date, $dateFormat ?? 'd.m.Y') }}</div>
        @if(isset($invoice->service_date) && $invoice->service_date)
            <div style="margin-bottom: 4px;"><strong>LEISTUNGSDATUM:</strong> {{ formatInvoiceDate($invoice->service_date, $dateFormat ?? 'd.m.Y') }}</div>
        @else
            <div style="margin-bottom: 4px;"><strong>LEISTUNGSDATUM:</strong> entspricht Rechnungsdatum</div>
        @endif
        <div style="margin-bottom: 4px;"><strong>FÄLLIGKEITSDATUM:</strong> {{ formatInvoiceDate($invoice->due_date, $dateFormat ?? 'd.m.Y') }}</div>
        @if(isset($invoice->customer->number) && $invoice->customer->number)
            <div><strong>KUNDENNR.:</strong> {{ $invoice->customer->number }}</div>
        @endif
    </div>

    {{-- Invoice Title --}}
    <div style="margin-bottom: 15px;">
        @php
            $isCorrection = isset($invoice->is_correction) ? (bool)$invoice->is_correction : false;
        @endphp
        <div style="font-size: {{ $headingFontSize + 4 }}px; font-weight: 700; color: {{ $isCorrection ? '#dc2626' : ($layoutSettings['colors']['primary'] ?? '#3b82f6') }};">
            {{ $isCorrection ? 'STORNORECHNUNG' : 'Rechnung' }} {{ $invoice->number }}
        </div>
        @if($isCorrection && isset($invoice->correctsInvoice) && $invoice->correctsInvoice)
            <div style="margin-top: 10px; padding: 10px; background-color: #fee2e2; border-left: 4px solid #dc2626; font-size: {{ $bodyFontSize }}px;">
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
    <div style="margin-bottom: 15px; font-size: {{ $bodyFontSize }}px; line-height: 1.6;">
        <div style="margin-bottom: 4px;">Sehr geehrte Damen und Herren,</div>
        <div>vielen Dank für Ihren Auftrag und das damit verbundene Vertrauen! Hiermit stelle ich Ihnen die folgenden Leistungen in Rechnung:</div>
    </div>

    {{-- Items Table with colored header - DISTINCTIVE: Blue header --}}
    <table style="width: 100%; border-collapse: collapse; margin: 15px 0;">
        <thead>
            <tr style="background-color: {{ $layoutSettings['colors']['primary'] ?? '#3b82f6' }}; color: white;">
                @if($layoutSettings['content']['show_item_codes'] ?? false)
                    <th style="padding: 10px 8px; text-align: left; font-weight: 600; font-size: {{ $bodyFontSize }}px; width: 12%;">PRODUKT-NR.</th>
                @endif
                <th style="padding: 10px 8px; text-align: left; font-weight: 600; font-size: {{ $bodyFontSize }}px; width: {{ ($layoutSettings['content']['show_item_codes'] ?? false) ? '45%' : '55%' }};">LEISTUNG</th>
                <th style="padding: 10px 8px; text-align: left; font-weight: 600; width: 9%;">MENGE</th>
                <th style="padding: 10px 8px; text-align: right; font-weight: 600; width: 6%;">UST.</th>
                <th style="padding: 10px 8px; text-align: right; font-weight: 600; width: 10%;">PREIS</th>
                <th style="padding: 10px 8px; text-align: right; font-weight: 600; width: 12%;">GESAMT</th>
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
                <tr style="border-bottom: 1px solid #e5e7eb; {{ $index % 2 == 1 ? 'background-color: ' . ($layoutSettings['colors']['accent'] ?? '#f9fafb') . ';' : '' }}">
                    @if($layoutSettings['content']['show_item_codes'] ?? false)
                        <td style="padding: 10px 8px;">{{ $productCode ?: '-' }}</td>
                    @endif
                    <td style="padding: 10px 8px;">{{ $item->description }}</td>
                    <td style="padding: 10px 8px;">
                        {{ number_format($item->quantity, 2, ',', '.') }}
                        @if($layoutSettings['content']['show_unit_column'] ?? true && isset($item->unit) && $item->unit)
                            {{ $item->unit }}
                        @else
                            Std.
                        @endif
                    </td>
                    @php $itemTaxRate = (float)($item->tax_rate ?? $invoice->tax_rate ?? 0); @endphp
                    <td style="padding: 10px 8px; text-align: right;">{{ number_format($itemTaxRate * 100, 0, ',', '.') }}%</td>
                    <td style="padding: 10px 8px; text-align: right;">{{ number_format($item->unit_price, 2, ',', '.') }} €</td>
                    <td style="padding: 10px 8px; text-align: right; font-weight: 600;">
                        <div>{{ number_format($item->total, 2, ',', '.') }} €</div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

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
            @php
                $isSmallBusiness = $snapshot['is_small_business'] ?? false;
                $isVatFree = $isSmallBusiness || (bool)($invoice->is_reverse_charge ?? false) || (($invoice->vat_exemption_type ?? 'none') !== 'none');
                $taxRates = [];
                if (!$isVatFree) {
                    foreach ($invoice->items as $it) {
                        $rate = (float)($it->tax_rate ?? $invoice->tax_rate ?? 0);
                        $taxableAmount = (float)($it->total ?? 0);
                        if (!isset($taxRates[$rate])) {
                            $taxRates[$rate] = ['base' => 0, 'amount' => 0];
                        }
                        $taxRates[$rate]['base'] += $taxableAmount;
                        $taxRates[$rate]['amount'] += $taxableAmount * $rate;
                    }
                    ksort($taxRates);
                }
            @endphp
            @if(!$isVatFree)
                @foreach($taxRates as $rate => $data)
                    @if($rate > 0)
                        <tr>
                            <td style="padding: 6px 10px; text-align: left; border-bottom: 1px solid #e5e7eb;">{{ number_format($rate * 100, 0) }}% Umsatzsteuer</td>
                            <td style="padding: 6px 10px; text-align: right; border-bottom: 1px solid #e5e7eb;">{{ number_format($data['amount'], 2, ',', '.') }} €</td>
                        </tr>
                    @endif
                @endforeach
            @endif
            <tr style="background-color: {{ $layoutSettings['colors']['primary'] ?? '#3b82f6' }}; color: white;">
                <td style="padding: 8px 10px; text-align: left; font-weight: 700; font-size: {{ $bodyFontSize + 1 }}px;">Rechnungsbetrag</td>
                <td style="padding: 8px 10px; text-align: right; font-weight: 700; font-size: {{ $bodyFontSize + 1 }}px;">{{ number_format($invoice->total, 2, ',', '.') }} €</td>
            </tr>
        </table>
    </div>

    {{-- Legal notices --}}
    @if($isSmallBusiness)
        <div style="margin-top: 12px; padding: 8px; background-color: #fef3c7; border-left: 3px solid #f59e0b; font-size: {{ $bodyFontSize }}px; line-height: 1.5;">
            <strong>Gemäß §19 UStG wird keine Umsatzsteuer ausgewiesen.</strong>
        </div>
    @endif

    @if($invoice->is_reverse_charge ?? false)
        <div style="margin-top: 12px; padding: 8px; background-color: #e0f2fe; border-left: 3px solid #38b2ac; font-size: {{ $bodyFontSize }}px; line-height: 1.5;">
            <strong>Hinweis zum Reverse-Charge-Verfahren:</strong> Die Umsatzsteuer schuldet der Leistungsempfänger.
            @if($invoice->buyer_vat_id)
                <br>USt-IdNr. des Leistungsempfängers: {{ $invoice->buyer_vat_id }}
            @endif
        </div>
    @endif

    @if(($invoice->vat_exemption_type ?? 'none') !== 'none')
        <div style="margin-top: 12px; padding: 8px; background-color: #e6fffa; border-left: 3px solid #319795; font-size: {{ $bodyFontSize }}px; line-height: 1.5;">
            <strong>Umsatzsteuerbefreiung:</strong>
            @if($invoice->vat_exemption_type === 'eu_intracommunity')
                Innergemeinschaftliche Lieferung gemäß §4 Nr. 1b UStG
            @elseif($invoice->vat_exemption_type === 'export')
                Ausfuhrlieferung gemäß §4 Nr. 1a UStG
            @else
                {{ $invoice->vat_exemption_reason ?? 'Gemäß §4 UStG' }}
            @endif
            @if($invoice->vat_exemption_reason && $invoice->vat_exemption_type === 'other')
                <br>Grund: {{ $invoice->vat_exemption_reason }}
            @endif
        </div>
    @endif

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

</div>
