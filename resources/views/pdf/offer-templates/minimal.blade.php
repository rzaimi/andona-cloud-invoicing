{{-- Minimal Template: Clean, no decorative elements, bottom-border only rows --}}
@php
    $ls        = $layoutSettings;
    $primary   = $ls['colors']['primary']   ?? '#6b7280';
    $secondary = $ls['colors']['secondary'] ?? '#6b7280';
    $textColor = $ls['colors']['text']      ?? '#111827';
    $accent    = $ls['colors']['accent']    ?? '#f9fafb';
    $fs        = $bodyFontSize;
    $fsH       = $headingFontSize;

    $logoRelPath = isset($snapshot['logo']) ? ltrim(preg_replace('#^storage/#', '', (string)$snapshot['logo']), '/') : null;
    $showLogo    = ($ls['branding']['show_logo'] ?? true) && $logoRelPath;
@endphp
<div class="container">

    {{-- ── HEADER ──────────────────────────────────────────────────────────── --}}
    <table style="width:100%; border-collapse:collapse; margin-bottom:8mm;">
        <tr>
            <td style="vertical-align:top;">
                <div style="font-size:{{ $fsH + 3 }}px; font-weight:600; color:{{ $textColor }}; margin-bottom:1mm;">{{ $snapshot['name'] ?? '' }}</div>
                @if($ls['content']['show_company_address'] ?? true)
                <div style="font-size:{{ $fs - 1 }}px; color:{{ $secondary }}; line-height:1.4;">
                    {{ $snapshot['address'] ?? '' }}
                    @if(($snapshot['postal_code'] ?? null) && ($snapshot['city'] ?? null)), {{ $snapshot['postal_code'] }} {{ $snapshot['city'] }}@endif
                </div>
                @endif
                @if($ls['content']['show_company_contact'] ?? true)
                <div style="font-size:{{ $fs - 1 }}px; color:{{ $secondary }};">
                    @if($snapshot['email'] ?? null){{ $snapshot['email'] }}@endif
                    @if(($snapshot['phone'] ?? null) && ($snapshot['email'] ?? null)) &middot; @endif
                    @if($snapshot['phone'] ?? null){{ $snapshot['phone'] }}@endif
                </div>
                @endif
            </td>
            @if($showLogo)
            <td style="text-align:right; vertical-align:top;">
                @if(isset($preview) && $preview)
                    <img src="{{ asset('storage/' . $logoRelPath) }}" alt="Logo" style="max-height:18mm; max-width:60mm;">
                @elseif(\Storage::disk('public')->exists($logoRelPath))
                    @php $lp = \Storage::disk('public')->path($logoRelPath); @endphp
                    <img src="data:{{ mime_content_type($lp) }};base64,{{ base64_encode(file_get_contents($lp)) }}" alt="Logo" style="max-height:18mm; max-width:60mm;">
                @endif
            </td>
            @endif
        </tr>
    </table>

    {{-- ── ADDRESS + OFFER INFO ────────────────────────────────────────────── --}}
    <table style="width:100%; border-collapse:collapse; margin-bottom:8mm;">
        <tr>
            <td style="width:52%; vertical-align:top; padding-right:8mm;">
                @php $customer = $offer->customer ?? null; @endphp
                @if($customer)
                <div class="din-5008-address">
                    @if($ls['content']['show_company_address'] ?? true)
                    <div class="sender-return-address">{{ $snapshot['name'] ?? '' }} &middot; {{ $snapshot['address'] ?? '' }} &middot; {{ $snapshot['postal_code'] ?? '' }} {{ $snapshot['city'] ?? '' }}</div>
                    @endif
                    <div style="font-weight:600; font-size:{{ $fs }}px; line-height:1.3; margin-bottom:1mm;">{{ $customer->name ?? '' }}</div>
                    @if($customer->contact_person ?? null)
                    <div style="font-size:{{ $fs }}px; margin-bottom:1mm;">{{ $customer->contact_person }}</div>
                    @endif
                    <div style="font-size:{{ $fs }}px; color:{{ $secondary }}; line-height:1.35;">
                        @if($customer->address ?? null)
                            {{ $customer->address }}<br>
                        @endif
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
                    <tr><td style="font-weight:600;">BV</td><td style="font-weight:600;">{{ $offer->bauvorhaben }}</td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- ── TITLE ────────────────────────────────────────────────────────────── --}}
    <div style="margin-bottom:4mm; border-bottom:1px solid #d1d5db; padding-bottom:2mm;">
        <span style="font-size:{{ $fsH + 3 }}px; font-weight:600; color:{{ $textColor }};">Angebot</span>
        <span style="font-size:{{ $fs }}px; color:{{ $secondary }}; margin-left:4mm;">Nr. {{ $offer->number ?? '' }}</span>
    </div>

    @if(($ls['content']['show_notes'] ?? true) && ($offer->notes ?? null))
    <div style="font-size:{{ $fs }}px; line-height:1.6; margin-bottom:5mm;">{{ $offer->notes }}</div>
    @else
    <div style="font-size:{{ $fs }}px; line-height:1.6; margin-bottom:5mm;">
        <div style="margin-bottom:2mm;">Sehr geehrte Damen und Herren,</div>
        <div>anbei erhalten Sie unser Angebot zu den unten genannten Positionen:</div>
    </div>
    @endif

    {{-- ── ITEMS TABLE (minimal: bottom border only) ───────────────────────── --}}
    @php
        $showItemCodes = $ls['content']['show_item_codes'] ?? true;
        $showRowNumber = $ls['content']['show_row_number'] ?? false;
        $showUnit      = $ls['content']['show_unit_column'] ?? true;
        $items         = $offer->items ?? [];
    @endphp
    <table class="items-table">
        <thead>
            <tr style="border-bottom:2px solid {{ $textColor }};">
                @if($showRowNumber)
                <th style="width:6%;">Pos.</th>
                @endif
                @if($showItemCodes)
                <th style="width:12%;">Nr.</th>
                @endif
                <th>Beschreibung</th>
                @if($showUnit)
                <th style="width:9%; text-align:center;">Einheit</th>
                @endif
                <th style="width:9%; text-align:right;">Menge</th>
                <th style="width:13%; text-align:right;">Preis</th>
                <th style="width:13%; text-align:right;">Betrag</th>
            </tr>
        </thead>
        <tbody>
        @foreach($items as $i => $item)
            @php
                $itemLine = is_object($item) ? ($item->description ?? $item->name ?? '') : ($item['description'] ?? $item['name'] ?? '');
                $qty      = is_object($item) ? ($item->quantity ?? 1) : ($item['quantity'] ?? 1);
                $price    = is_object($item) ? ($item->unit_price ?? $item->price ?? 0) : ($item['unit_price'] ?? $item['price'] ?? 0);
                $total    = is_object($item) ? ($item->total ?? $qty * $price) : ($item['total'] ?? $qty * $price);
                $unit     = is_object($item) ? ($item->unit ?? '') : ($item['unit'] ?? '');
                $discount = is_object($item) ? ($item->discount_percent ?? 0) : ($item['discount_percent'] ?? 0);
                $sku      = is_object($item) ? ($item->product->number ?? $item->product->sku ?? ($item->sku ?? '')) : ($item['sku'] ?? '');
                $desc     = is_object($item) ? ($item->long_description ?? '') : ($item['long_description'] ?? '');
            @endphp
            <tr style="border-bottom:1px solid #e5e7eb;">
                @if($showRowNumber)
                <td style="color:{{ $secondary }}; text-align:center;">{{ $i + 1 }}</td>
                @endif
                @if($showItemCodes)
                <td style="color:{{ $secondary }};">{{ $sku ?: '-' }}</td>
                @endif
                <td>
                    {{ $itemLine }}
                    @if($desc)
                    <div style="font-size:{{ $fs - 1 }}px; color:#9ca3af; margin-top:1px;">{{ $desc }}</div>
                    @endif
                    @if($discount > 0)
                    <div style="font-size:{{ $fs - 1 }}px; color:#dc2626;">Rabatt: {{ number_format((float)$discount, 0) }}%</div>
                    @endif
                </td>
                @if($showUnit)
                <td style="text-align:center; color:{{ $secondary }};">{{ $unit ?: 'Stk.' }}</td>
                @endif
                <td style="text-align:right; color:{{ $secondary }};">{{ number_format((float)$qty, 2, ',', '.') }}</td>
                <td style="text-align:right;">{{ number_format((float)$price, 2, ',', '.') }} &euro;</td>
                <td style="text-align:right;">{{ number_format((float)$total, 2, ',', '.') }} &euro;</td>
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
        foreach ($items as $it) {
            $totalDiscount += (float)(is_object($it) ? ($it->discount_amount ?? 0) : ($it['discount_amount'] ?? 0));
        }
    @endphp
    <table class="totals-outer">
        <tr>
            <td style="width:50%;"></td>
            <td style="width:50%;">
                <table style="width:100%; border-collapse:collapse; font-size:{{ $fs }}px;">
                    @if($totalDiscount > 0.001)
                    <tr style="border-bottom:1px solid #e5e7eb;">
                        <td style="padding:4px 0;">Zwischensumme</td>
                        <td style="padding:4px 0; text-align:right;">{{ number_format($subtotal + $totalDiscount, 2, ',', '.') }} &euro;</td>
                    </tr>
                    <tr style="border-bottom:1px solid #e5e7eb;">
                        <td style="padding:4px 0; color:#dc2626;">Rabatt</td>
                        <td style="padding:4px 0; text-align:right; color:#dc2626;">&minus;{{ number_format($totalDiscount, 2, ',', '.') }} &euro;</td>
                    </tr>
                    @endif
                    <tr style="border-bottom:1px solid #e5e7eb;">
                        <td style="padding:4px 0; color:{{ $secondary }};">Netto</td>
                        <td style="padding:4px 0; text-align:right;">{{ number_format($subtotal, 2, ',', '.') }} &euro;</td>
                    </tr>
                    <tr style="border-bottom:1px solid #e5e7eb;">
                        <td style="padding:4px 0; color:{{ $secondary }};">{{ number_format($taxRate * 100, 0) }}% USt.</td>
                        <td style="padding:4px 0; text-align:right;">{{ number_format($taxAmount, 2, ',', '.') }} &euro;</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0; font-weight:700; font-size:{{ $fs + 1 }}px; border-top:2px solid {{ $textColor }};">Gesamt</td>
                        <td style="padding:6px 0; text-align:right; font-weight:700; font-size:{{ $fs + 1 }}px; border-top:2px solid {{ $textColor }}; white-space:nowrap;">{{ number_format($offerTotal, 2, ',', '.') }} &euro;</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if(($ls['content']['show_payment_terms'] ?? true) && ($offer->terms_conditions ?? null))
    <div style="margin-top:6mm; font-size:{{ $fs - 1 }}px; color:{{ $secondary }}; line-height:1.5;">{{ $offer->terms_conditions }}</div>
    @endif

    <div style="margin-top:8mm; font-size:{{ $fs }}px;">
        <div style="margin-bottom:2mm;">Mit freundlichen Gr&uuml;&szlig;en</div>
        <div style="font-weight:600;">{{ $snapshot['name'] ?? '' }}</div>
    </div>

</div>
