{{-- Modern Template: Left accent bar + colored table header --}}
@php
    $ls         = $layoutSettings;
    $primary    = $ls['colors']['primary']   ?? '#3b82f6';
    $secondary  = $ls['colors']['secondary'] ?? '#374151';
    $accent     = $ls['colors']['accent']    ?? '#eff6ff';
    $textColor  = $ls['colors']['text']      ?? '#1f2937';
    $fs         = $bodyFontSize;
    $fsH        = $headingFontSize;

    $logoRelPath = isset($snapshot['logo']) ? ltrim(preg_replace('#^storage/#', '', (string)$snapshot['logo']), '/') : null;
    $showLogo    = ($ls['branding']['show_logo'] ?? true) && $logoRelPath;
    $logoPos     = $ls['branding']['logo_position'] ?? 'left';
@endphp
<div class="container">

    {{-- ── HEADER: left accent bar ────────────────────────────────────────── --}}
    <table style="width:100%; border-collapse:collapse; margin-bottom:8mm; padding-left:3mm; border-left:4px solid {{ $primary }};">
        <tr>
            <td style="vertical-align:top; padding-left:4mm;">
                @if($showLogo && $logoPos === 'left')
                    @if(isset($preview) && $preview)
                        <img src="{{ asset('storage/' . $logoRelPath) }}" alt="Logo" style="max-height:18mm; max-width:65mm; margin-bottom:3mm; display:block;">
                    @elseif(\Storage::disk('public')->exists($logoRelPath))
                        @php $lp = \Storage::disk('public')->path($logoRelPath); @endphp
                        <img src="data:{{ mime_content_type($lp) }};base64,{{ base64_encode(file_get_contents($lp)) }}" alt="Logo" style="max-height:18mm; max-width:65mm; margin-bottom:3mm; display:block;">
                    @endif
                @endif
                <div style="font-size:{{ $fsH + 4 }}px; font-weight:700; color:{{ $primary }}; margin-bottom:1mm;">{{ $snapshot['name'] ?? '' }}</div>
                @if($ls['content']['show_company_address'] ?? true)
                    <div style="font-size:{{ $fs - 1 }}px; color:{{ $secondary }}; line-height:1.4;">
                        {{ $snapshot['address'] ?? '' }}@if(($snapshot['postal_code'] ?? null) && ($snapshot['city'] ?? null)), {{ $snapshot['postal_code'] }} {{ $snapshot['city'] }}@endif
                    </div>
                @endif
                @if($ls['content']['show_company_contact'] ?? true)
                    <div style="font-size:{{ $fs - 1 }}px; color:{{ $secondary }}; margin-top:1mm;">
                        @if($snapshot['phone'] ?? null)
                    Tel. {{ $snapshot['phone'] }}
                    @if($snapshot['email'] ?? null) &middot; @endif
                @endif
                        @if($snapshot['email'] ?? null){{ $snapshot['email'] }}@endif
                    </div>
                @endif
            </td>
            @if($showLogo && $logoPos === 'right')
            <td style="text-align:right; vertical-align:top;">
                @if(isset($preview) && $preview)
                    <img src="{{ asset('storage/' . $logoRelPath) }}" alt="Logo" style="max-height:20mm; max-width:65mm;">
                @elseif(\Storage::disk('public')->exists($logoRelPath))
                    @php $lp = \Storage::disk('public')->path($logoRelPath); @endphp
                    <img src="data:{{ mime_content_type($lp) }};base64,{{ base64_encode(file_get_contents($lp)) }}" alt="Logo" style="max-height:20mm; max-width:65mm;">
                @endif
            </td>
            @endif
        </tr>
    </table>
    @if($ls['branding']['show_header_line'] ?? true)
    <div style="border-top:2px solid {{ $primary }}; margin-bottom:6mm;"></div>
    @endif

    {{-- ── ADDRESS + OFFER INFO (DIN 5008) ────────────────────────────────── --}}
    <table style="width:100%; border-collapse:collapse; margin-bottom:8mm;">
        <tr>
            <td style="width:52%; vertical-align:top; padding-right:8mm;">
                @php $customer = $offer->customer ?? null; @endphp
                @if($customer)
                <div class="din-5008-address">
                    @if($ls['content']['show_company_address'] ?? true)
                    <div class="sender-return-address">{{ $snapshot['name'] ?? '' }} · {{ $snapshot['address'] ?? '' }} · {{ $snapshot['postal_code'] ?? '' }} {{ $snapshot['city'] ?? '' }}</div>
                    @endif
                    <div style="font-weight:600; font-size:{{ $fs }}px; line-height:1.3; margin-bottom:1mm;">{{ $customer->name ?? '' }}</div>
                    @if($customer->contact_person ?? null)<div style="font-size:{{ $fs }}px; line-height:1.3; margin-bottom:1mm;">{{ $customer->contact_person }}</div>@endif
                    <div style="font-size:{{ $fs }}px; color:{{ $secondary }}; line-height:1.35;">
                        @if($customer->address ?? null){{ $customer->address }}<br>@endif
                        @if(($customer->postal_code ?? null) && ($customer->city ?? null))
                            {{ $customer->postal_code }} {{ $customer->city }}
                            @if(($customer->country ?? null) && $customer->country !== 'Deutschland')
                                <br>{{ $customer->country }}
                            @endif
                        @endif
                    </div>
                </div>
                @endif
            </td>
            <td style="width:48%; vertical-align:top;">
                <table class="offer-info-table">
                    <tr>
                        <td>Angebot Nr.</td>
                        <td>{{ $offer->number ?? '' }}</td>
                    </tr>
                    <tr>
                        <td>Datum</td>
                        <td>{{ fmtOfferDate($offer->issue_date ?? $offer->created_at, $dateFormat) }}</td>
                    </tr>
                    @if($ls['content']['show_customer_number'] ?? true)
                    <tr>
                        <td>Kunden-Nr.</td>
                        <td>{{ $customer->customer_number ?? ($customer->number ?? '') }}</td>
                    </tr>
                    @endif
                    @if(($ls['content']['show_validity_period'] ?? true) && ($offer->validity_date ?? $offer->valid_until ?? null))
                    <tr>
                        <td>Gültig bis</td>
                        <td>{{ fmtOfferDate($offer->validity_date ?? $offer->valid_until, $dateFormat) }}</td>
                    </tr>
                    @endif
                    @if(($ls['content']['show_tax_number'] ?? true) && ($snapshot['tax_number'] ?? null))
                    <tr>
                        <td>Steuernummer</td>
                        <td>{{ $snapshot['tax_number'] }}</td>
                    </tr>
                    @endif
                    @if($snapshot['vat_number'] ?? null)
                    <tr>
                        <td>USt-IdNr.</td>
                        <td>{{ $snapshot['vat_number'] }}</td>
                    </tr>
                    @endif
                    @if(!empty($offer->bauvorhaben) && ($ls['content']['show_bauvorhaben'] ?? true))
                    <tr>
                        <td style="font-weight:600; color:{{ $primary }};">BV</td>
                        <td style="font-weight:600; color:{{ $primary }};">{{ $offer->bauvorhaben }}</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- ── TITLE ────────────────────────────────────────────────────────────── --}}
    <div style="margin-bottom:4mm;">
        <div style="font-size:{{ $fsH + 4 }}px; font-weight:700; color:{{ $primary }};">Angebot {{ $offer->number ?? '' }}</div>
    </div>

    {{-- ── INTRO TEXT ──────────────────────────────────────────────────────── --}}
    @if(($ls['content']['show_notes'] ?? true) && ($offer->notes ?? null))
    <div style="font-size:{{ $fs }}px; line-height:1.6; margin-bottom:5mm;">{{ $offer->notes }}</div>
    @else
    <div style="font-size:{{ $fs }}px; line-height:1.6; margin-bottom:5mm;">
        <div style="margin-bottom:2mm;">Sehr geehrte Damen und Herren,</div>
        <div>vielen Dank für Ihr Interesse. Hiermit unterbreiten wir Ihnen gerne folgendes Angebot:</div>
    </div>
    @endif

    {{-- ── ITEMS TABLE ─────────────────────────────────────────────────────── --}}
    @php
        $showItemCodes = $ls['content']['show_item_codes'] ?? true;
        $showRowNumber = $ls['content']['show_row_number'] ?? false;
        $showUnit      = $ls['content']['show_unit_column'] ?? true;
        $items         = $offer->items ?? [];
    @endphp
    <table class="items-table">
        <thead>
            <tr style="background-color:{{ $primary }}; color:white;">
                @if($showRowNumber)
                <th style="width:6%;">Pos.</th>
                @endif
                @if($showItemCodes)<th style="width:12%;">Produkt-Nr.</th>@endif
                <th>Beschreibung</th>
                @if($showUnit)<th style="width:9%; text-align:center;">Einheit</th>@endif
                <th style="width:9%; text-align:right;">Anzahl</th>
                <th style="width:13%; text-align:right;">Preis</th>
                <th style="width:13%; text-align:right;">Summe</th>
            </tr>
        </thead>
        <tbody>
        @foreach($items as $i => $item)
            @php
                $rowBg    = ($i % 2 === 0) ? 'white' : ($accent);
                $itemLine = is_object($item) ? ($item->description ?? $item->name ?? '') : ($item['description'] ?? $item['name'] ?? '');
                $qty      = is_object($item) ? ($item->quantity ?? 1) : ($item['quantity'] ?? 1);
                $price    = is_object($item) ? ($item->unit_price ?? $item->price ?? 0) : ($item['unit_price'] ?? $item['price'] ?? 0);
                $total    = is_object($item) ? ($item->total ?? $qty * $price) : ($item['total'] ?? $qty * $price);
                $unit     = is_object($item) ? ($item->unit ?? '') : ($item['unit'] ?? '');
                $discount = is_object($item) ? ($item->discount_percent ?? 0) : ($item['discount_percent'] ?? 0);
                $sku      = is_object($item) ? ($item->product->number ?? $item->product->sku ?? ($item->sku ?? '')) : ($item['sku'] ?? '');
            @endphp
            <tr style="background-color:{{ $rowBg }};">
                @if($showRowNumber)
                <td style="text-align:center;">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</td>
                @endif
                @if($showItemCodes)<td style="color:#6b7280;">{{ $sku ?: '–' }}</td>@endif
                <td>
                    <div style="font-weight:600;">{{ $itemLine }}</div>
                    @php $desc = is_object($item) ? ($item->long_description ?? '') : ($item['long_description'] ?? ''); @endphp
                    @if($desc)<div style="font-size:{{ $fs - 1 }}px; color:#6b7280; margin-top:1px;">{{ $desc }}</div>@endif
                </td>
                @if($showUnit)<td style="text-align:center;">{{ $unit ?: 'Stk.' }}</td>@endif
                <td style="text-align:right;">{{ number_format((float)$qty, 2, ',', '.') }}</td>
                <td style="text-align:right;">
                    {{ number_format((float)$price, 2, ',', '.') }} €
                    @if($discount > 0)<div style="color:#dc2626; font-size:{{ $fs - 1 }}px;">−{{ number_format((float)$discount, 0) }}%</div>@endif
                </td>
                <td style="text-align:right; font-weight:600;">{{ number_format((float)$total, 2, ',', '.') }} €</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    {{-- ── TOTALS ──────────────────────────────────────────────────────────── --}}
    @php
        $subtotal    = (float)($offer->subtotal ?? 0);
        $taxAmount   = (float)($offer->tax_amount ?? 0);
        $total       = (float)($offer->total ?? 0);
        $taxRate     = (float)($offer->tax_rate ?? 0);

        $totalDiscount = 0;
        foreach ($items as $it) {
            $totalDiscount += (float)(is_object($it) ? ($it->discount_amount ?? 0) : ($it['discount_amount'] ?? 0));
        }

        $vatBreakdown = [];
        if ($ls['content']['show_tax_breakdown'] ?? true) {
            foreach ($items as $it) {
                $rate = round((float)(is_object($it) ? ($it->tax_rate ?? $taxRate) : ($it['tax_rate'] ?? $taxRate)), 4);
                $k    = (string)$rate;
                $itTotal = (float)(is_object($it) ? ($it->total ?? 0) : ($it['total'] ?? 0));
                if (!isset($vatBreakdown[$k])) $vatBreakdown[$k] = ['rate' => $rate, 'net' => 0.0, 'tax' => 0.0];
                $vatBreakdown[$k]['net'] += $itTotal;
                $vatBreakdown[$k]['tax'] += $itTotal * $rate;
            }
            uasort($vatBreakdown, fn($a, $b) => $b['rate'] <=> $a['rate']);
        }
        $multipleRates = count($vatBreakdown) > 1;
    @endphp
    <table class="totals-outer">
        <tr>
            <td style="width:50%;"></td>
            <td style="width:50%;">
                <table class="totals-inner">
                    @if($totalDiscount > 0.001)
                    <tr><td>Bruttobetrag</td><td>{{ number_format($subtotal + $totalDiscount, 2, ',', '.') }} €</td></tr>
                    <tr><td style="color:#dc2626;">Rabatt</td><td style="color:#dc2626;">−{{ number_format($totalDiscount, 2, ',', '.') }} €</td></tr>
                    @endif
                    <tr><td>Nettobetrag</td><td>{{ number_format($subtotal, 2, ',', '.') }} €</td></tr>
                    @if($multipleRates)
                        @foreach($vatBreakdown as $vat)
                        <tr><td>{{ number_format($vat['rate'] * 100, 0) }}% MwSt.</td><td>{{ number_format($vat['tax'], 2, ',', '.') }} €</td></tr>
                        @endforeach
                    @else
                    <tr><td>{{ number_format($taxRate * 100, 0) }}% MwSt.</td><td>{{ number_format($taxAmount, 2, ',', '.') }} €</td></tr>
                    @endif
                    <tr class="total-row" style="background-color:{{ $primary }}; color:white;">
                        <td style="border-color:{{ $primary }}; font-size:{{ $fs + 1 }}px;">GESAMTBETRAG</td>
                        <td style="border-color:{{ $primary }}; font-size:{{ $fs + 1 }}px;">{{ number_format($total, 2, ',', '.') }} €</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ── TERMS / CLOSING ─────────────────────────────────────────────────── --}}
    @if(($ls['content']['show_payment_terms'] ?? true) && ($offer->terms_conditions ?? null))
    <div style="margin-top:6mm; font-size:{{ $fs - 1 }}px; color:{{ $secondary }}; line-height:1.5;">{{ $offer->terms_conditions }}</div>
    @endif

    <div style="margin-top:8mm; font-size:{{ $fs }}px;">
        <div style="margin-bottom:2mm;">Mit freundlichen Grüßen</div>
        <div style="font-weight:600;">{{ $snapshot['name'] ?? '' }}</div>
    </div>

</div>
