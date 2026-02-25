{{-- Creative (Kreativ) Template: Bold diagonal header, vibrant accent colors --}}
@php
    $ls        = $layoutSettings;
    $primary   = $ls['colors']['primary']   ?? '#dc2626';
    $secondary = $ls['colors']['secondary'] ?? '#374151';
    $accent    = $ls['colors']['accent']    ?? '#fef2f2';
    $textColor = $ls['colors']['text']      ?? '#1f2937';
    $fs        = $bodyFontSize;
    $fsH       = $headingFontSize;

    $logoRelPath = isset($snapshot['logo']) ? ltrim(preg_replace('#^storage/#', '', (string)$snapshot['logo']), '/') : null;
    $showLogo    = ($ls['branding']['show_logo'] ?? true) && $logoRelPath;
    $logoPos     = $ls['branding']['logo_position'] ?? 'right';
@endphp
<div style="margin:0; padding:0;">

    {{-- ── BOLD TWO-TONE HEADER ────────────────────────────────────────────── --}}
    <div style="background-color:{{ $primary }}; padding:6mm {{ min($layoutSettings['layout']['margin_right'] ?? 20, 20) }}mm 0 {{ min($layoutSettings['layout']['margin_left'] ?? 20, 20) }}mm;">
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="vertical-align:bottom; padding-bottom:5mm;">
                    <div style="font-size:{{ $fsH + 8 }}px; font-weight:900; color:white; letter-spacing:1px; text-transform:uppercase; line-height:1;">{{ $snapshot['name'] ?? '' }}</div>
                    @if($ls['content']['show_company_address'] ?? true)
                    <div style="font-size:{{ $fs - 1 }}px; color:rgba(255,255,255,0.8); margin-top:2mm; letter-spacing:0.5px;">
                        {{ $snapshot['address'] ?? '' }}@if(($snapshot['postal_code'] ?? null) && ($snapshot['city'] ?? null)), {{ $snapshot['postal_code'] }} {{ $snapshot['city'] }}@endif
                    </div>
                    @endif
                </td>
                @if($showLogo)
                <td style="text-align:right; vertical-align:bottom; padding-bottom:5mm;">
                    @if(isset($preview) && $preview)
                        <img src="{{ asset('storage/' . $logoRelPath) }}" alt="Logo" style="max-height:20mm; max-width:65mm; filter:brightness(0) invert(1); opacity:0.9;">
                    @elseif(\Storage::disk('public')->exists($logoRelPath))
                        @php $lp = \Storage::disk('public')->path($logoRelPath); @endphp
                        <img src="data:{{ mime_content_type($lp) }};base64,{{ base64_encode(file_get_contents($lp)) }}" alt="Logo" style="max-height:20mm; max-width:65mm;">
                    @endif
                </td>
                @endif
            </tr>
        </table>
    </div>
    {{-- Accent stripe --}}
    <div style="background-color:{{ $textColor }}; height:2mm; margin-bottom:8mm;"></div>

<div class="container" style="padding-top:0 !important;">

    {{-- ── ADDRESS + OFFER INFO ────────────────────────────────────────────── --}}
    <table style="width:100%; border-collapse:collapse; margin-bottom:8mm;">
        <tr>
            <td style="width:52%; vertical-align:top; padding-right:8mm;">
                @php $customer = $offer->customer ?? null; @endphp
                @if($customer)
                <div class="din-5008-address">
                    @if($ls['content']['show_company_address'] ?? true)
                    <div class="sender-return-address">{{ $snapshot['name'] ?? '' }} · {{ $snapshot['address'] ?? '' }} · {{ $snapshot['postal_code'] ?? '' }} {{ $snapshot['city'] ?? '' }}</div>
                    @endif
                    <div style="font-weight:700; font-size:{{ $fs }}px; line-height:1.3; margin-bottom:1mm;">{{ $customer->name ?? '' }}</div>
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
                {{-- Bold bordered info box --}}
                <table style="width:100%; border-collapse:collapse; border:2px solid {{ $primary }}; font-size:{{ $fs - 1 }}px;">
                    <tr style="background-color:{{ $primary }}; color:white;">
                        <td style="padding:4px 7px; font-weight:700; text-transform:uppercase; letter-spacing:1px;" colspan="2">Angebot</td>
                    </tr>
                    <tr style="border-bottom:1px solid {{ $accent }};"><td style="padding:4px 7px; color:{{ $secondary }}; width:42%;">Nummer</td><td style="padding:4px 7px; font-weight:700;">{{ $offer->number ?? '' }}</td></tr>
                    <tr style="border-bottom:1px solid {{ $accent }};"><td style="padding:4px 7px; color:{{ $secondary }};">Datum</td><td style="padding:4px 7px; font-weight:600;">{{ fmtOfferDate($offer->issue_date ?? $offer->created_at, $dateFormat) }}</td></tr>
                    @if($ls['content']['show_customer_number'] ?? true)
                    <tr style="border-bottom:1px solid {{ $accent }};"><td style="padding:4px 7px; color:{{ $secondary }};">Kunden-Nr.</td><td style="padding:4px 7px; font-weight:600;">{{ $customer->customer_number ?? ($customer->number ?? '') }}</td></tr>
                    @endif
                    @if(($ls['content']['show_validity_period'] ?? true) && ($offer->validity_date ?? $offer->valid_until ?? null))
                    <tr style="border-bottom:1px solid {{ $accent }};"><td style="padding:4px 7px; color:{{ $secondary }};">Gültig bis</td><td style="padding:4px 7px; font-weight:600; color:{{ $primary }};">{{ fmtOfferDate($offer->validity_date ?? $offer->valid_until, $dateFormat) }}</td></tr>
                    @endif
                    @if(($ls['content']['show_tax_number'] ?? true) && ($snapshot['tax_number'] ?? null))
                    <tr style="border-bottom:1px solid {{ $accent }};"><td style="padding:4px 7px; color:{{ $secondary }};">Steuernr.</td><td style="padding:4px 7px;">{{ $snapshot['tax_number'] }}</td></tr>
                    @endif
                    @if(!empty($offer->bauvorhaben) && ($ls['content']['show_bauvorhaben'] ?? true))
                    <tr style="background-color:{{ $accent }};"><td style="padding:4px 7px; font-weight:700; color:{{ $primary }};">BV</td><td style="padding:4px 7px; font-weight:700; color:{{ $primary }};">{{ $offer->bauvorhaben }}</td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    @if(($ls['content']['show_notes'] ?? true) && ($offer->notes ?? null))
    <div style="font-size:{{ $fs }}px; line-height:1.6; margin-bottom:5mm; padding:3mm 4mm; background-color:{{ $accent }}; border-left:4px solid {{ $primary }};">{{ $offer->notes }}</div>
    @else
    <div style="font-size:{{ $fs }}px; line-height:1.6; margin-bottom:5mm;">
        <div style="margin-bottom:2mm;">Sehr geehrte Damen und Herren,</div>
        <div>nachfolgend finden Sie unser Angebot für Sie:</div>
    </div>
    @endif

    {{-- ── ITEMS TABLE ─────────────────────────────────────────────────────── --}}
    @php
        $showItemCodes = $ls['content']['show_item_codes'] ?? true;
        $showUnit      = $ls['content']['show_unit_column'] ?? true;
        $items         = $offer->items ?? [];
    @endphp
    <table class="items-table" style="border:2px solid {{ $primary }};">
        <thead>
            <tr style="background-color:{{ $primary }}; color:white;">
                <th style="width:6%;">#</th>
                @if($showItemCodes)<th style="width:12%;">Produkt-Nr.</th>@endif
                <th>Beschreibung</th>
                @if($showUnit)<th style="width:9%; text-align:center;">Einheit</th>@endif
                <th style="width:9%; text-align:right;">Menge</th>
                <th style="width:13%; text-align:right;">Preis</th>
                <th style="width:13%; text-align:right;">Betrag</th>
            </tr>
        </thead>
        <tbody>
        @foreach($items as $i => $item)
            @php
                $rowBg    = ($i % 2 === 0) ? 'white' : $accent;
                $itemLine = is_object($item) ? ($item->description ?? $item->name ?? '') : ($item['description'] ?? $item['name'] ?? '');
                $qty      = is_object($item) ? ($item->quantity ?? 1) : ($item['quantity'] ?? 1);
                $price    = is_object($item) ? ($item->unit_price ?? $item->price ?? 0) : ($item['unit_price'] ?? $item['price'] ?? 0);
                $total    = is_object($item) ? ($item->total ?? $qty * $price) : ($item['total'] ?? $qty * $price);
                $unit     = is_object($item) ? ($item->unit ?? '') : ($item['unit'] ?? '');
                $discount = is_object($item) ? ($item->discount_percent ?? 0) : ($item['discount_percent'] ?? 0);
                $sku      = is_object($item) ? ($item->product->number ?? $item->product->sku ?? ($item->sku ?? '')) : ($item['sku'] ?? '');
            @endphp
            <tr style="background-color:{{ $rowBg }}; border-bottom:1px solid {{ $accent }};">
                <td style="text-align:center; font-weight:700; color:{{ $primary }};">{{ $i + 1 }}</td>
                @if($showItemCodes)<td style="color:#9ca3af; font-size:{{ $fs - 1 }}px;">{{ $sku ?: '–' }}</td>@endif
                <td>
                    <div style="font-weight:700;">{{ $itemLine }}</div>
                    @php $desc = is_object($item) ? ($item->long_description ?? '') : ($item['long_description'] ?? ''); @endphp
                    @if($desc)<div style="font-size:{{ $fs - 1 }}px; color:#9ca3af;">{{ $desc }}</div>@endif
                    @if($discount > 0)<div style="font-size:{{ $fs - 1 }}px; color:#dc2626; font-weight:600;">RABATT −{{ number_format((float)$discount, 0) }}%</div>@endif
                </td>
                @if($showUnit)<td style="text-align:center; color:{{ $secondary }};">{{ $unit ?: 'Stk.' }}</td>@endif
                <td style="text-align:right; color:{{ $secondary }};">{{ number_format((float)$qty, 2, ',', '.') }}</td>
                <td style="text-align:right;">{{ number_format((float)$price, 2, ',', '.') }} €</td>
                <td style="text-align:right; font-weight:700; color:{{ $primary }};">{{ number_format((float)$total, 2, ',', '.') }} €</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    {{-- ── TOTALS ──────────────────────────────────────────────────────────── --}}
    @php
        $subtotal      = (float)($offer->subtotal ?? 0);
        $taxAmount     = (float)($offer->tax_amount ?? 0);
        $offerTotal    = (float)($offer->total ?? 0);
        $taxRate       = (float)($offer->tax_rate ?? 0);
        $totalDiscount = 0;
        foreach ($items as $it) { $totalDiscount += (float)(is_object($it) ? ($it->discount_amount ?? 0) : ($it['discount_amount'] ?? 0)); }
    @endphp
    <table class="totals-outer">
        <tr>
            <td style="width:50%;"></td>
            <td style="width:50%;">
                <table style="width:100%; border-collapse:collapse; font-size:{{ $fs }}px; border:2px solid {{ $primary }};">
                    @if($totalDiscount > 0.001)
                    <tr style="border-bottom:1px solid {{ $accent }};"><td style="padding:5px 10px; color:{{ $secondary }};">Nettobetrag</td><td style="padding:5px 10px; text-align:right; white-space:nowrap;">{{ number_format($subtotal + $totalDiscount, 2, ',', '.') }} €</td></tr>
                    <tr style="border-bottom:1px solid {{ $accent }};"><td style="padding:5px 10px; color:#dc2626; font-weight:700;">RABATT</td><td style="padding:5px 10px; text-align:right; color:#dc2626; font-weight:700; white-space:nowrap;">−{{ number_format($totalDiscount, 2, ',', '.') }} €</td></tr>
                    @endif
                    <tr style="border-bottom:1px solid {{ $accent }};"><td style="padding:5px 10px; color:{{ $secondary }};">Netto</td><td style="padding:5px 10px; text-align:right; white-space:nowrap;">{{ number_format($subtotal, 2, ',', '.') }} €</td></tr>
                    <tr style="border-bottom:2px solid {{ $primary }};"><td style="padding:5px 10px; color:{{ $secondary }};">{{ number_format($taxRate * 100, 0) }}% MwSt.</td><td style="padding:5px 10px; text-align:right; white-space:nowrap;">{{ number_format($taxAmount, 2, ',', '.') }} €</td></tr>
                    <tr style="background-color:{{ $primary }}; color:white;">
                        <td style="padding:8px 10px; font-weight:900; font-size:{{ $fs + 2 }}px; text-transform:uppercase; letter-spacing:1px;">Gesamt</td>
                        <td style="padding:8px 10px; text-align:right; font-weight:900; font-size:{{ $fs + 2 }}px; white-space:nowrap;">{{ number_format($offerTotal, 2, ',', '.') }} €</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if(($ls['content']['show_payment_terms'] ?? true) && ($offer->terms_conditions ?? null))
    <div style="margin-top:6mm; font-size:{{ $fs - 1 }}px; color:{{ $secondary }}; line-height:1.5; padding:3mm 4mm; background-color:{{ $accent }}; border-left:3px solid {{ $primary }};">{{ $offer->terms_conditions }}</div>
    @endif

    <div style="margin-top:8mm; font-size:{{ $fs }}px;">
        <div style="margin-bottom:2mm;">Mit freundlichen Grüßen</div>
        <div style="font-weight:900; text-transform:uppercase; letter-spacing:1px; color:{{ $primary }};">{{ $snapshot['name'] ?? '' }}</div>
    </div>

</div>
</div>
