{{-- Classic Template: Traditional German layout, dark borders, centered title --}}
@php
    $ls        = $layoutSettings;
    $primary   = $ls['colors']['primary']   ?? '#1f2937';
    $secondary = $ls['colors']['secondary'] ?? '#374151';
    $accent    = $ls['colors']['accent']    ?? '#f3f4f6';
    $textColor = $ls['colors']['text']      ?? '#1f2937';
    $fs        = $bodyFontSize;
    $fsH       = $headingFontSize;

    $logoRelPath = isset($snapshot['logo']) ? ltrim(preg_replace('#^storage/#', '', (string)$snapshot['logo']), '/') : null;
    $showLogo    = ($ls['branding']['show_logo'] ?? true) && $logoRelPath;
    $logoPos     = $ls['branding']['logo_position'] ?? 'left';
@endphp
<div class="container">

    {{-- ── HEADER: company left, logo right ──────────────────────────────── --}}
    <table style="width:100%; border-collapse:collapse; border-bottom:2px solid {{ $textColor }}; padding-bottom:5mm; margin-bottom:6mm;">
        <tr>
            <td style="vertical-align:top;">
                <div style="font-size:{{ $fsH + 5 }}px; font-weight:700; color:{{ $textColor }}; margin-bottom:2mm;">{{ $snapshot['name'] ?? '' }}</div>
                @if($ls['content']['show_company_address'] ?? true)
                <div style="font-size:{{ $fs - 1 }}px; color:{{ $secondary }}; line-height:1.4;">
                    @if($snapshot['address'] ?? null){{ $snapshot['address'] }}<br>@endif
                    @if(($snapshot['postal_code'] ?? null) && ($snapshot['city'] ?? null)){{ $snapshot['postal_code'] }} {{ $snapshot['city'] }}@endif
                </div>
                @endif
                @if($ls['content']['show_company_contact'] ?? true)
                <div style="font-size:{{ $fs - 1 }}px; color:{{ $secondary }}; margin-top:1mm;">
                    @if($snapshot['phone'] ?? null)Tel. {{ $snapshot['phone'] }} · @endif
                    @if($snapshot['email'] ?? null){{ $snapshot['email'] }}@endif
                </div>
                @endif
            </td>
            @if($showLogo)
            <td style="text-align:right; vertical-align:top;">
                @if(isset($preview) && $preview)
                    <img src="{{ asset('storage/' . $logoRelPath) }}" alt="Logo" style="max-height:22mm; max-width:65mm;">
                @elseif(\Storage::disk('public')->exists($logoRelPath))
                    @php $lp = \Storage::disk('public')->path($logoRelPath); @endphp
                    <img src="data:{{ mime_content_type($lp) }};base64,{{ base64_encode(file_get_contents($lp)) }}" alt="Logo" style="max-height:22mm; max-width:65mm;">
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
                    <div class="sender-return-address">{{ $snapshot['name'] ?? '' }} · {{ $snapshot['address'] ?? '' }} · {{ $snapshot['postal_code'] ?? '' }} {{ $snapshot['city'] ?? '' }}</div>
                    @endif
                    <div style="font-weight:600; font-size:{{ $fs }}px; line-height:1.3; margin-bottom:1mm;">{{ $customer->name ?? '' }}</div>
                    @if($customer->contact_person ?? null)<div style="font-size:{{ $fs }}px; line-height:1.3; margin-bottom:1mm;">{{ $customer->contact_person }}</div>@endif
                    <div style="font-size:{{ $fs }}px; line-height:1.35;">
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
                    <tr><td>Angebot Nr.</td><td>{{ $offer->number ?? '' }}</td></tr>
                    <tr><td>Datum</td><td>{{ fmtOfferDate($offer->issue_date ?? $offer->created_at, $dateFormat) }}</td></tr>
                    @if($ls['content']['show_customer_number'] ?? true)
                    <tr><td>Kunden-Nr.</td><td>{{ $customer->customer_number ?? ($customer->number ?? '') }}</td></tr>
                    @endif
                    @if(($ls['content']['show_validity_period'] ?? true) && ($offer->validity_date ?? $offer->valid_until ?? null))
                    <tr><td>Gültig bis</td><td>{{ fmtOfferDate($offer->validity_date ?? $offer->valid_until, $dateFormat) }}</td></tr>
                    @endif
                    @if(($ls['content']['show_tax_number'] ?? true) && ($snapshot['tax_number'] ?? null))
                    <tr><td>Steuernummer</td><td>{{ $snapshot['tax_number'] }}</td></tr>
                    @endif
                    @if($snapshot['vat_number'] ?? null)
                    <tr><td>USt-IdNr.</td><td>{{ $snapshot['vat_number'] }}</td></tr>
                    @endif
                    @if(!empty($offer->bauvorhaben) && ($ls['content']['show_bauvorhaben'] ?? true))
                    <tr><td style="font-weight:700;">BV</td><td style="font-weight:700;">{{ $offer->bauvorhaben }}</td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- ── TITLE (centered, uppercase) ────────────────────────────────────── --}}
    <div style="text-align:center; margin-bottom:5mm; border-bottom:1px solid {{ $textColor }}; padding-bottom:3mm;">
        <div style="font-size:{{ $fsH + 5 }}px; font-weight:700; text-transform:uppercase; letter-spacing:2px; color:{{ $textColor }};">Angebot</div>
        <div style="font-size:{{ $fs }}px; color:{{ $secondary }}; margin-top:2px;">Nr. {{ $offer->number ?? '' }}</div>
    </div>

    {{-- ── INTRO TEXT ──────────────────────────────────────────────────────── --}}
    @if(($ls['content']['show_notes'] ?? true) && ($offer->notes ?? null))
    <div style="font-size:{{ $fs }}px; line-height:1.6; margin-bottom:5mm;">{{ $offer->notes }}</div>
    @else
    <div style="font-size:{{ $fs }}px; line-height:1.6; margin-bottom:5mm;">
        <div style="margin-bottom:2mm;">Sehr geehrte Damen und Herren,</div>
        <div>vielen Dank für Ihr Interesse. Nachfolgend erhalten Sie unser Angebot:</div>
    </div>
    @endif

    {{-- ── ITEMS TABLE ─────────────────────────────────────────────────────── --}}
    @php
        $showItemCodes = $ls['content']['show_item_codes'] ?? true;
        $showRowNumber = $ls['content']['show_row_number'] ?? true;
        $showUnit      = $ls['content']['show_unit_column'] ?? true;
        $items         = $offer->items ?? [];
        $border        = '1px solid ' . $textColor;
    @endphp
    <table class="items-table" style="border:{{ $border }};">
        <thead>
            <tr style="background-color:{{ $textColor }}; color:white;">
                @if($showRowNumber)
                <th style="border-right:{{ $border }}; width:6%;">Pos.</th>
                @endif
                @if($showItemCodes)<th style="border-right:{{ $border }}; width:12%;">Produkt-Nr.</th>@endif
                <th style="border-right:{{ $border }};">Beschreibung</th>
                @if($showUnit)<th style="border-right:{{ $border }}; width:9%; text-align:center;">Einheit</th>@endif
                <th style="border-right:{{ $border }}; width:9%; text-align:right;">Anzahl</th>
                <th style="border-right:{{ $border }}; width:13%; text-align:right;">Preis</th>
                <th style="width:13%; text-align:right;">Summe</th>
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
            <tr style="background-color:{{ $rowBg }}; border-top:{{ $border }};">
                @if($showRowNumber)
                <td style="text-align:center; border-right:{{ $border }};">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</td>
                @endif
                @if($showItemCodes)<td style="border-right:{{ $border }}; color:#6b7280;">{{ $sku ?: '–' }}</td>@endif
                <td style="border-right:{{ $border }};">
                    <div style="font-weight:600;">{{ $itemLine }}</div>
                    @php $desc = is_object($item) ? ($item->long_description ?? '') : ($item['long_description'] ?? ''); @endphp
                    @if($desc)<div style="font-size:{{ $fs - 1 }}px; color:#6b7280;">{{ $desc }}</div>@endif
                </td>
                @if($showUnit)<td style="text-align:center; border-right:{{ $border }};">{{ $unit ?: 'Stk.' }}</td>@endif
                <td style="text-align:right; border-right:{{ $border }};">{{ number_format((float)$qty, 2, ',', '.') }}</td>
                <td style="text-align:right; border-right:{{ $border }};">
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
                <table style="width:100%; border-collapse:collapse; border:{{ $border }}; font-size:{{ $fs }}px;">
                    @if($totalDiscount > 0.001)
                    <tr style="border-bottom:{{ $border }};"><td style="padding:5px 8px;">Nettobetrag (vor Rabatt)</td><td style="padding:5px 8px; text-align:right; white-space:nowrap;">{{ number_format($subtotal + $totalDiscount, 2, ',', '.') }} €</td></tr>
                    <tr style="border-bottom:{{ $border }};"><td style="padding:5px 8px; color:#dc2626;">Rabatt</td><td style="padding:5px 8px; text-align:right; color:#dc2626; white-space:nowrap;">−{{ number_format($totalDiscount, 2, ',', '.') }} €</td></tr>
                    @endif
                    <tr style="border-bottom:{{ $border }};"><td style="padding:5px 8px;">Nettobetrag</td><td style="padding:5px 8px; text-align:right; white-space:nowrap;">{{ number_format($subtotal, 2, ',', '.') }} €</td></tr>
                    <tr style="border-bottom:{{ $border }};"><td style="padding:5px 8px;">{{ number_format($taxRate * 100, 0) }}% Umsatzsteuer</td><td style="padding:5px 8px; text-align:right; white-space:nowrap;">{{ number_format($taxAmount, 2, ',', '.') }} €</td></tr>
                    <tr style="background-color:{{ $textColor }}; color:white;">
                        <td style="padding:7px 8px; font-weight:700; font-size:{{ $fs + 1 }}px;">Gesamtbetrag (brutto)</td>
                        <td style="padding:7px 8px; text-align:right; font-weight:700; font-size:{{ $fs + 1 }}px; white-space:nowrap;">{{ number_format($offerTotal, 2, ',', '.') }} €</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if(($ls['content']['show_payment_terms'] ?? true) && ($offer->terms_conditions ?? null))
    <div style="margin-top:6mm; font-size:{{ $fs - 1 }}px; color:{{ $secondary }}; line-height:1.5;">{{ $offer->terms_conditions }}</div>
    @endif

    <div style="margin-top:8mm; font-size:{{ $fs }}px;">
        <div style="margin-bottom:2mm;">Mit freundlichen Grüßen</div>
        <div style="font-weight:600;">{{ $snapshot['name'] ?? '' }}</div>
    </div>

</div>
