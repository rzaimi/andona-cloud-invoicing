{{-- Elegant Template: Generous spacing, centered logo, refined typography --}}
@php
    $ls        = $layoutSettings;
    $primary   = $ls['colors']['primary']   ?? '#7c3aed';
    $secondary = $ls['colors']['secondary'] ?? '#6b7280';
    $accent    = $ls['colors']['accent']    ?? '#f5f3ff';
    $textColor = $ls['colors']['text']      ?? '#1f2937';
    $fs        = $bodyFontSize;
    $fsH       = $headingFontSize;

    $logoRelPath = isset($snapshot['logo']) ? ltrim(preg_replace('#^storage/#', '', (string)$snapshot['logo']), '/') : null;
    $showLogo    = ($ls['branding']['show_logo'] ?? true) && $logoRelPath;
    $logoPos     = $ls['branding']['logo_position'] ?? 'center';
@endphp
<div class="container">

    {{-- ── HEADER: centered or side logo ─────────────────────────────────── --}}
    @if($logoPos === 'center')
    <div style="text-align:center; margin-bottom:6mm;">
        @if($showLogo)
            @if(isset($preview) && $preview)
                <img src="{{ asset('storage/' . $logoRelPath) }}" alt="Logo" style="max-height:22mm; max-width:70mm; margin-bottom:3mm; display:inline-block;">
            @elseif(\Storage::disk('public')->exists($logoRelPath))
                @php $lp = \Storage::disk('public')->path($logoRelPath); @endphp
                <img src="data:{{ mime_content_type($lp) }};base64,{{ base64_encode(file_get_contents($lp)) }}" alt="Logo" style="max-height:22mm; max-width:70mm; margin-bottom:3mm; display:inline-block;">
            @endif
        @endif
        <div style="font-size:{{ $fsH + 4 }}px; font-weight:300; letter-spacing:3px; text-transform:uppercase; color:{{ $textColor }}; margin-bottom:1mm;">{{ $snapshot['name'] ?? '' }}</div>
        @if($ls['content']['show_company_address'] ?? true)
        <div style="font-size:{{ $fs - 1 }}px; color:{{ $secondary }}; letter-spacing:1px;">
            {{ $snapshot['address'] ?? '' }}@if(($snapshot['postal_code'] ?? null) && ($snapshot['city'] ?? null)), {{ $snapshot['postal_code'] }} {{ $snapshot['city'] }}@endif
        </div>
        @endif
        @if($ls['content']['show_company_contact'] ?? true)
        <div style="font-size:{{ $fs - 1 }}px; color:{{ $secondary }}; letter-spacing:0.5px; margin-top:1mm;">
                    @if($snapshot['email'] ?? null)
                        {{ $snapshot['email'] }}
                        @if($snapshot['phone'] ?? null)
                            &middot; {{ $snapshot['phone'] }}
                        @endif
                    @endif
        </div>
        @endif
    </div>
    @else
    <table style="width:100%; border-collapse:collapse; margin-bottom:6mm;">
        <tr>
            <td style="vertical-align:top;">
                <div style="font-size:{{ $fsH + 4 }}px; font-weight:300; letter-spacing:3px; text-transform:uppercase; color:{{ $textColor }};">{{ $snapshot['name'] ?? '' }}</div>
                @if($ls['content']['show_company_address'] ?? true)
                <div style="font-size:{{ $fs - 1 }}px; color:{{ $secondary }}; margin-top:1mm; letter-spacing:0.5px;">
                    {{ $snapshot['address'] ?? '' }}@if(($snapshot['postal_code'] ?? null) && ($snapshot['city'] ?? null)), {{ $snapshot['postal_code'] }} {{ $snapshot['city'] }}@endif
                </div>
                @endif
            </td>
            @if($showLogo)
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
    @endif

    {{-- Decorative divider --}}
    @if($ls['branding']['show_header_line'] ?? true)
    <table style="width:100%; border-collapse:collapse; margin-bottom:8mm;">
        <tr>
            <td style="border-bottom:1px solid {{ $secondary }}; width:35%;"></td>
            <td style="border-bottom:3px solid {{ $primary }}; width:30%;"></td>
            <td style="border-bottom:1px solid {{ $secondary }}; width:35%;"></td>
        </tr>
    </table>
    @endif

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
                    <div style="font-weight:600; font-size:{{ $fs }}px; line-height:1.3; margin-bottom:1mm;">{{ $customer->name ?? '' }}</div>
                    @if($customer->contact_person ?? null)<div style="font-size:{{ $fs }}px; margin-bottom:1mm;">{{ $customer->contact_person }}</div>@endif
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
                    <tr><td>Angebot</td><td>{{ $offer->number ?? '' }}</td></tr>
                    <tr><td>Datum</td><td>{{ fmtOfferDate($offer->issue_date ?? $offer->created_at, $dateFormat) }}</td></tr>
                    @if($ls['content']['show_customer_number'] ?? true)
                    <tr><td>Kunden-Nr.</td><td>{{ $customer->customer_number ?? ($customer->number ?? '') }}</td></tr>
                    @endif
                    @if(($ls['content']['show_validity_period'] ?? true) && ($offer->validity_date ?? $offer->valid_until ?? null))
                    <tr><td>Gültig bis</td><td>{{ fmtOfferDate($offer->validity_date ?? $offer->valid_until, $dateFormat) }}</td></tr>
                    @endif
                    @if(($ls['content']['show_tax_number'] ?? true) && ($snapshot['tax_number'] ?? null))
                    <tr><td>Steuernr.</td><td>{{ $snapshot['tax_number'] }}</td></tr>
                    @endif
                    @if(!empty($offer->bauvorhaben) && ($ls['content']['show_bauvorhaben'] ?? true))
                    <tr><td style="font-weight:600; color:{{ $primary }};">BV</td><td style="font-weight:600; color:{{ $primary }};">{{ $offer->bauvorhaben }}</td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- ── TITLE ────────────────────────────────────────────────────────────── --}}
    <div style="margin-bottom:5mm; padding-bottom:3mm; border-bottom:1px solid {{ $accent }};">
        <div style="font-size:{{ $fsH + 4 }}px; font-weight:300; letter-spacing:2px; text-transform:uppercase; color:{{ $primary }};">Angebot</div>
        <div style="font-size:{{ $fs }}px; color:{{ $secondary }}; letter-spacing:0.5px;">Nr. {{ $offer->number ?? '' }}</div>
    </div>

    @if(($ls['content']['show_notes'] ?? true) && ($offer->notes ?? null))
    <div style="font-size:{{ $fs }}px; line-height:1.7; margin-bottom:6mm; font-style:italic; color:{{ $secondary }};">{{ $offer->notes }}</div>
    @else
    <div style="font-size:{{ $fs }}px; line-height:1.7; margin-bottom:6mm;">
        <div style="margin-bottom:2mm;">Sehr geehrte Damen und Herren,</div>
        <div>wir freuen uns, Ihnen nachfolgendes Angebot unterbreiten zu dürfen:</div>
    </div>
    @endif

    {{-- ── ITEMS TABLE ─────────────────────────────────────────────────────── --}}
    @php
        $showItemCodes = $ls['content']['show_item_codes'] ?? true;
        $showRowNumber = $ls['content']['show_row_number'] ?? false;
        $showUnit      = $ls['content']['show_unit_column'] ?? true;
        $items         = $offer->items ?? [];
    @endphp
    <table class="items-table" style="border-top:2px solid {{ $primary }};">
        <thead>
            <tr style="border-bottom:1px solid {{ $secondary }};">
                @if($showRowNumber)
                <th style="width:6%; color:{{ $primary }};">Pos.</th>
                @endif
                @if($showItemCodes)<th style="width:12%; color:{{ $primary }};">Nr.</th>@endif
                <th style="color:{{ $primary }};">Beschreibung</th>
                @if($showUnit)<th style="width:9%; text-align:center; color:{{ $primary }};">Einheit</th>@endif
                <th style="width:9%; text-align:right; color:{{ $primary }};">Anzahl</th>
                <th style="width:13%; text-align:right; color:{{ $primary }};">Preis</th>
                <th style="width:13%; text-align:right; color:{{ $primary }};">Summe</th>
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
            <tr style="background-color:{{ $rowBg }}; border-bottom:1px solid #e5e7eb;">
                @if($showRowNumber)
                <td style="text-align:center; color:{{ $secondary }};">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</td>
                @endif
                @if($showItemCodes)<td style="color:#9ca3af;">{{ $sku ?: '–' }}</td>@endif
                <td>
                    <div style="font-weight:500;">{{ $itemLine }}</div>
                    @php $desc = is_object($item) ? ($item->long_description ?? '') : ($item['long_description'] ?? ''); @endphp
                    @if($desc)<div style="font-size:{{ $fs - 1 }}px; color:#9ca3af;">{{ $desc }}</div>@endif
                    @if($discount > 0)<div style="font-size:{{ $fs - 1 }}px; color:#dc2626;">Rabatt {{ number_format((float)$discount, 0) }}%</div>@endif
                </td>
                @if($showUnit)<td style="text-align:center; color:{{ $secondary }};">{{ $unit ?: 'Stk.' }}</td>@endif
                <td style="text-align:right; color:{{ $secondary }};">{{ number_format((float)$qty, 2, ',', '.') }}</td>
                <td style="text-align:right;">{{ number_format((float)$price, 2, ',', '.') }} €</td>
                <td style="text-align:right; font-weight:600; color:{{ $textColor }};">{{ number_format((float)$total, 2, ',', '.') }} €</td>
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
                <table style="width:100%; border-collapse:collapse; font-size:{{ $fs }}px; border:1px solid {{ $accent }}; border-radius:2mm;">
                    @if($totalDiscount > 0.001)
                    <tr style="border-bottom:1px solid {{ $accent }};"><td style="padding:5px 10px; color:{{ $secondary }};">Zwischensumme</td><td style="padding:5px 10px; text-align:right; white-space:nowrap;">{{ number_format($subtotal + $totalDiscount, 2, ',', '.') }} €</td></tr>
                    <tr style="border-bottom:1px solid {{ $accent }};"><td style="padding:5px 10px; color:#dc2626;">Rabatt</td><td style="padding:5px 10px; text-align:right; color:#dc2626; white-space:nowrap;">−{{ number_format($totalDiscount, 2, ',', '.') }} €</td></tr>
                    @endif
                    <tr style="border-bottom:1px solid {{ $accent }};"><td style="padding:5px 10px; color:{{ $secondary }};">Nettobetrag</td><td style="padding:5px 10px; text-align:right; white-space:nowrap;">{{ number_format($subtotal, 2, ',', '.') }} €</td></tr>
                    <tr style="border-bottom:2px solid {{ $primary }};"><td style="padding:5px 10px; color:{{ $secondary }};">{{ number_format($taxRate * 100, 0) }}% USt.</td><td style="padding:5px 10px; text-align:right; white-space:nowrap;">{{ number_format($taxAmount, 2, ',', '.') }} €</td></tr>
                    <tr>
                        <td style="padding:8px 10px; font-weight:600; font-size:{{ $fs + 1 }}px; color:{{ $primary }}; letter-spacing:1px; text-transform:uppercase;">Gesamtbetrag</td>
                        <td style="padding:8px 10px; text-align:right; font-weight:700; font-size:{{ $fs + 1 }}px; color:{{ $primary }}; white-space:nowrap;">{{ number_format($offerTotal, 2, ',', '.') }} €</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if(($ls['content']['show_payment_terms'] ?? true) && ($offer->terms_conditions ?? null))
    <div style="margin-top:6mm; font-size:{{ $fs - 1 }}px; color:{{ $secondary }}; line-height:1.5; font-style:italic;">{{ $offer->terms_conditions }}</div>
    @endif

    <div style="margin-top:10mm; font-size:{{ $fs }}px; letter-spacing:0.5px;">
        <div style="margin-bottom:2mm;">Mit freundlichen Grüßen</div>
        <div style="font-weight:500; color:{{ $primary }};">{{ $snapshot['name'] ?? '' }}</div>
    </div>

</div>
