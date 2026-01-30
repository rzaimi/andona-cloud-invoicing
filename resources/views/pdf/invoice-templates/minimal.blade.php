{{-- Minimal Template: Ultra-clean, minimal design with focus on content --}}
{{-- Template: minimal - DISTINCTIVE: No borders, minimal spacing, ultra-clean --}}

<div class="container">
    @php
        $logoPosition = $layoutSettings['branding']['logo_position'] ?? 'top-left';
        $logoAlign = $logoPosition === 'top-right' ? 'right' : ($logoPosition === 'top-center' ? 'center' : 'left');

        $companyInfoPosition = $layoutSettings['branding']['company_info_position'] ?? 'top-left';
        $companyAlign = $companyInfoPosition === 'top-right' ? 'right' : ($companyInfoPosition === 'top-center' ? 'center' : 'left');

        $snapshot = $invoice->getCompanySnapshot();
    @endphp

    {{-- Header order: 1) Logo (aligned by settings) 2) Company address 3) Receiver address (DIN 5008 positioned) --}}
    <div>
        @php
            $logoRelPath = isset($snapshot['logo']) ? ltrim(preg_replace('#^storage/#', '', (string)$snapshot['logo']), '/') : null;
        @endphp
        @if(($layoutSettings['branding']['show_logo'] ?? true) && $logoRelPath)
            @if(isset($preview) && $preview)
                <div style="margin-bottom: 6px; text-align: {{ $logoAlign }};">
                    <img src="{{ asset('storage/' . $logoRelPath) }}" alt="Logo" style="max-height: 80px; max-width: 260px;">
                </div>
            @elseif(\Storage::disk('public')->exists($logoRelPath))
                @php
                    $logoPath = \Storage::disk('public')->path($logoRelPath);
                    $logoData = base64_encode(file_get_contents($logoPath));
                    $logoMime = mime_content_type($logoPath);
                @endphp
                <div style="margin-bottom: 6px; text-align: {{ $logoAlign }};">
                    <img src="data:{{ $logoMime }};base64,{{ $logoData }}" alt="Logo" style="max-height: 80px; max-width: 260px;">
                </div>
            @endif
        @endif
    </div>

    {{-- Space for DIN 5008 address window (receiver address is rendered globally in invoice.blade.php) --}}
    <div style="height: 60px;"></div>
    <table style="width: 100%;">
        <tr>
            <td>
                @php $customer = $invoice->customer ?? null; @endphp
                @if($customer)
                    <div class="din-5008-address">
                        @if($layoutSettings['content']['show_company_address'] ?? true)
                            <div style="font-size: 11px; color: {{ $layoutSettings['colors']['text'] ?? '#1f2937' }}; line-height: 1.4; text-align: {{ $companyAlign }}; padding-bottom: 5px;">
                                {{ $snapshot['name'] ?? '' }}
                                @if($snapshot['address'] ?? null){{ $snapshot['address'] }}@endif
                                @if(($snapshot['postal_code'] ?? null) && ($snapshot['city'] ?? null)), {{ $snapshot['postal_code'] }} {{ $snapshot['city'] }}@endif
                            </div>
                        @endif
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
            </td>
            <td>
                {{-- Invoice Details (German standard fields) --}}
                <div style="text-align: right; font-size: {{ $bodyFontSize }}px; margin-bottom: 10px;">
                    {{-- Customer number below address if needed --}}
                    @if(($layoutSettings['content']['show_customer_number'] ?? true) && isset($invoice->customer->number) && $invoice->customer->number)
                    <div style="margin-bottom: 3px;"><strong>Kundennummer:</strong> {{ $invoice->customer->number }}</div>
                    @endif
                    <div style="margin-bottom: 3px;"><strong>Rechnungsdatum:</strong> {{ formatInvoiceDate($invoice->issue_date, $dateFormat ?? 'd.m.Y') }}</div>
                    @if(isset($invoice->service_date) && $invoice->service_date)
                        <div style="margin-bottom: 3px;"><strong>Leistungsdatum:</strong> {{ formatInvoiceDate($invoice->service_date, $dateFormat ?? 'd.m.Y') }}</div>
                    @else
                        <div style="margin-bottom: 3px;"><strong>Leistungsdatum:</strong> entspricht Rechnungsdatum</div>
                    @endif
                    <div style="margin-bottom: 3px;"><strong>Fälligkeitsdatum:</strong> {{ formatInvoiceDate($invoice->due_date, $dateFormat ?? 'd.m.Y') }}</div>
                </div>
            </td>
        </tr>
    </table>
    <div style="height: 15px;"></div>
    {{-- Invoice Title - Minimal styling --}}
    <div style="margin-bottom: 10px;">
        @php
            $isCorrection = isset($invoice->is_correction) ? (bool)$invoice->is_correction : false;
        @endphp
        <div style="font-size: {{ $headingFontSize + 2 }}px; font-weight: 600; color: {{ $isCorrection ? '#dc2626' : ($layoutSettings['colors']['text'] ?? '#1f2937') }};">
            {{ $isCorrection ? 'STORNORECHNUNG' : 'Rechnung' }} {{ $invoice->number }}
        </div>
        @if($isCorrection && isset($invoice->correctsInvoice) && $invoice->correctsInvoice)
            <div style="margin-top: 8px; padding: 8px; background-color: #fee2e2; border-left: 3px solid #dc2626; font-size: {{ $bodyFontSize }}px;">
                <div style="font-weight: 600; color: #991b1b; margin-bottom: 3px;">Storniert Rechnung:</div>
                <div style="color: #7f1d1d;">Nr. {{ $invoice->correctsInvoice->number }} vom {{ formatInvoiceDate($invoice->correctsInvoice->issue_date, $dateFormat ?? 'd.m.Y') }}</div>
                @if(isset($invoice->correction_reason) && $invoice->correction_reason)
                    <div style="margin-top: 4px; padding-top: 4px; border-top: 1px solid #dc2626;">
                        <strong>Grund:</strong> {{ $invoice->correction_reason }}
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Salutation and Introduction - Minimal --}}
    <div style="margin-bottom: 12px; font-size: {{ $bodyFontSize }}px; line-height: 1.5;">
        <div style="margin-bottom: 6px;">Sehr geehrte Damen und Herren,</div>
        <div>vielen Dank für Ihren Auftrag und das damit verbundene Vertrauen! Hiermit stelle ich Ihnen die folgenden Leistungen in Rechnung:</div>
    </div>

    {{-- Items Table - Minimal borders - DISTINCTIVE: Very thin borders --}}
    <table style="width: 100%; border-collapse: collapse; margin: 2px 0;">
        <thead>
            <tr style="border-bottom: 0.5px solid #d1d5db;">
                @if($layoutSettings['content']['show_item_codes'] ?? false)
                    <th style="padding: 6px 4px; text-align: left; font-weight: 600; font-size: {{ $bodyFontSize }}px; width: 12%;">Produkt-Nr.</th>
                @endif
                <th style="padding: 6px 4px; text-align: left; font-weight: 600; font-size: {{ $bodyFontSize }}px; width: {{ ($layoutSettings['content']['show_item_codes'] ?? false) ? '45%' : '55%' }};">Leistung</th>
                <th style="padding: 6px 4px; text-align: left; font-weight: 600; width: 9%;">Menge</th>
                <th style="padding: 6px 4px; text-align: right; font-weight: 600; width: 6%;">USt.</th>
                <th style="padding: 6px 4px; text-align: right; font-weight: 600; width: 10%;">Preis</th>
                <th style="padding: 6px 4px; text-align: right; font-weight: 600; width: 12%;">Gesamt</th>
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
                        <td style="padding: 6px 4px;">{{ $productCode ?: '-' }}</td>
                    @endif
                    <td style="padding: 6px 4px;">{{ $item->description }}</td>
                    <td style="padding: 6px 4px;">
                        {{ number_format($item->quantity, 2, ',', '.') }}
                        @if($layoutSettings['content']['show_unit_column'] ?? true && isset($item->unit) && $item->unit)
                            {{ $item->unit }}
                        @else
                            Std.
                        @endif
                    </td>
                    @php
                        $itemTaxRate = (float)($item->tax_rate ?? $invoice->tax_rate ?? 0);
                    @endphp
                    <td style="padding: 6px 4px; text-align: right;">{{ number_format($itemTaxRate * 100, 0, ',', '.') }}%</td>
                    <td style="padding: 6px 4px; text-align: right;">{{ number_format($item->unit_price, 2, ',', '.') }} €</td>
                    <td style="padding: 6px 4px; text-align: right; font-weight: 600;">
                        <div>{{ number_format($item->total, 2, ',', '.') }} €</div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals - Minimal styling --}}
    <div style="text-align: right; margin-top: 8px;">
        @php
            $totalDiscount = 0;
            foreach ($invoice->items as $it) {
                $totalDiscount += (float)($it->discount_amount ?? 0);
            }
        @endphp
        <table style="width: 260px; margin-left: auto; border-collapse: collapse;">
            @if($totalDiscount > 0.0001)
                <tr>
                    <td style="padding: 4px 8px; text-align: left; border-bottom: 1px solid #e5e7eb;">Zwischensumme (vor Rabatt)</td>
                    <td style="padding: 4px 8px; text-align: right; border-bottom: 1px solid #e5e7eb;">{{ number_format($invoice->subtotal + $totalDiscount, 2, ',', '.') }} €</td>
                </tr>
                <tr>
                    <td style="padding: 4px 8px; text-align: left; border-bottom: 1px solid #e5e7eb;">Gesamtrabatt</td>
                    <td style="padding: 4px 8px; text-align: right; border-bottom: 1px solid #e5e7eb; color: #dc2626;">-{{ number_format($totalDiscount, 2, ',', '.') }} €</td>
                </tr>
            @endif
            <tr>
                <td style="padding: 4px 8px; text-align: left; border-bottom: 1px solid #e5e7eb;">Gesamtbetrag (netto)</td>
                <td style="padding: 4px 8px; text-align: right; border-bottom: 1px solid #e5e7eb;">{{ number_format($invoice->subtotal, 2, ',', '.') }} €</td>
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
                            <td style="padding: 4px 8px; text-align: left; border-bottom: 1px solid #e5e7eb;">{{ number_format($rate * 100, 0) }}% Umsatzsteuer</td>
                            <td style="padding: 4px 8px; text-align: right; border-bottom: 1px solid #e5e7eb;">{{ number_format($data['amount'], 2, ',', '.') }} €</td>
                        </tr>
                    @endif
                @endforeach
            @endif
            <tr>
                <td style="padding: 6px 8px; text-align: left; font-weight: 700; font-size: {{ $bodyFontSize + 1 }}px;">Gesamtbetrag (brutto)</td>
                <td style="padding: 6px 8px; text-align: right; font-weight: 700; font-size: {{ $bodyFontSize + 1 }}px;">{{ number_format($invoice->total, 2, ',', '.') }} €</td>
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

    {{-- Payment Instructions - Minimal --}}
    @php
        $taxNote = $settings['invoice_tax_note'] ?? null;
    @endphp
    @if($taxNote)
        <div style="margin-top: 12px; font-size: {{ $bodyFontSize ?? 11 }}px; line-height: 1.5;">
            {{ $taxNote }}
        </div>
    @endif
    @if($layoutSettings['content']['show_payment_terms'] ?? true)
        <div style="margin-top: 10px; font-size: {{ $bodyFontSize }}px; line-height: 1.4;">
            Bitte überweisen Sie den Rechnungsbetrag unter Angabe der Rechnungsnummer auf das unten angegebene Konto. Der Rechnungsbetrag ist sofort fällig.
        </div>
    @endif

    {{-- Closing - Minimal --}}
    <div style="margin-top: 10px; font-size: {{ $bodyFontSize }}px;">
        <div style="margin-bottom: 3px;">Mit freundlichen Grüßen</div>
        <div style="font-weight: 600;">{{ $snapshot['name'] ?? '' }}</div>
    </div>

</div>

