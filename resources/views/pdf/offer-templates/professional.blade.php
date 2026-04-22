{{-- Professional: Split two-tone header — company brand left, offer identity right --}}
@php
    $ls      = $layoutSettings;
    $primary = $ls['colors']['primary']   ?? '#1a3c5e';
    $accent  = $ls['colors']['accent']    ?? '#eef2f7';
    $textCol = $ls['colors']['text']      ?? '#1f2937';
    $secCol  = $ls['colors']['secondary'] ?? '#6b7280';
    $fs      = $bodyFontSize;
    $fsH     = $headingFontSize;

    $logoRelPath = isset($snapshot['logo']) ? ltrim(preg_replace('#^storage/#', '', (string)$snapshot['logo']), '/') : null;
    $logoSrc = null;
    if ($logoRelPath) {
        if (isset($preview) && $preview) {
            $logoSrc = asset('storage/' . $logoRelPath);
        } elseif (\Storage::disk('public')->exists($logoRelPath)) {
            $lp = \Storage::disk('public')->path($logoRelPath);
            $logoSrc = 'data:' . mime_content_type($lp) . ';base64,' . base64_encode(file_get_contents($lp));
        }
    }
    $showLogo = !empty($logoSrc);
    $customer = $offer->customer ?? null;
    $logoH    = match($ls['branding']['logo_size'] ?? 'medium') { 'small' => '14mm', 'large' => '30mm', default => '22mm' };
    $logoW    = match($ls['branding']['logo_size'] ?? 'medium') { 'small' => '48mm', 'large' => '90mm', default => '68mm' };
@endphp
<div class="container">

{{-- ══ SPLIT HEADER ════════════════════════════════════════════════════════ --}}
<table style="width:100%; border-collapse:collapse; margin-bottom:6mm;">
    <tr>
        <td style="width:60%; background-color:{{ $primary }}; padding:5mm 7mm; vertical-align:middle;">
            @if($showLogo)
                <div style="margin-bottom:3mm;"><img src="{{ $logoSrc }}" alt="Logo" style="max-height:{{ $logoH }}; max-width:{{ $logoW }};"></div>
            @endif
            <div style="color:white; font-size:{{ $fsH + 5 }}px; font-weight:800; line-height:1.2; margin-bottom:2mm;">{{ $snapshot['display_name'] ?? $snapshot['name'] ?? '' }}</div>
            @if($ls['content']['show_company_address'] ?? true)
                <div style="color:rgba(255,255,255,0.72); font-size:{{ $fs - 1 }}px; line-height:1.45;">
                    @if($snapshot['address'] ?? null){{ $snapshot['address'] }}@endif
                    @if(($snapshot['postal_code'] ?? null) && ($snapshot['city'] ?? null)) · {{ $snapshot['postal_code'] }} {{ $snapshot['city'] }}@endif
                    @if($snapshot['phone'] ?? null)<br>Tel. {{ $snapshot['phone'] }}@endif
                    @if($snapshot['email'] ?? null)@if($snapshot['phone'] ?? null) &nbsp;·&nbsp; @else<br>@endif{{ $snapshot['email'] }}@endif
                </div>
            @endif
        </td>
        <td style="width:40%; background-color:{{ $accent }}; padding:5mm 7mm; vertical-align:middle; text-align:right; border-left:4px solid white;">
            <div style="font-size:26px; font-weight:800; color:{{ $primary }}; letter-spacing:1px; margin-bottom:4mm; line-height:1;">ANGEBOT</div>
            <div style="font-size:{{ $fs }}px; color:{{ $textCol }}; line-height:1.8;">
                <span style="color:{{ $secCol }}; font-size:{{ $fs - 1 }}px; display:inline-block; min-width:22mm; text-align:left;">Nr.</span>
                <strong>{{ $offer->number ?? '' }}</strong><br>
                <span style="color:{{ $secCol }}; font-size:{{ $fs - 1 }}px; display:inline-block; min-width:22mm; text-align:left;">Datum</span>
                {{ fmtOfferDate($offer->issue_date ?? $offer->created_at, $dateFormat) }}<br>
                @if($customer && !empty($customer->number ?? $customer->customer_number ?? null))
                    <span style="color:{{ $secCol }}; font-size:{{ $fs - 1 }}px; display:inline-block; min-width:22mm; text-align:left;">Kunden-Nr.</span>
                    {{ $customer->number ?? $customer->customer_number }}<br>
                @endif
                @if(($ls['content']['show_validity_period'] ?? true) && ($offer->validity_date ?? $offer->valid_until ?? null))
                    <span style="color:{{ $secCol }}; font-size:{{ $fs - 1 }}px; display:inline-block; min-width:22mm; text-align:left;">Gültig bis</span>
                    {{ fmtOfferDate($offer->validity_date ?? $offer->valid_until, $dateFormat) }}<br>
                @endif
                @if(!empty($offer->bauvorhaben) && ($ls['content']['show_bauvorhaben'] ?? true))
                    <span style="color:{{ $secCol }}; font-size:{{ $fs - 1 }}px; display:inline-block; min-width:22mm; text-align:left;">BV</span>
                    <strong>{{ $offer->bauvorhaben }}</strong>
                @endif
            </div>
        </td>
    </tr>
</table>

{{-- ══ DIN 5008 ADDRESS BLOCK ═════════════════════════════════════════════ --}}
@if($customer)
<div style="margin-bottom:5mm;">
    <div class="din-5008-address">
        @if($ls['content']['show_company_address'] ?? true)
            <div class="sender-return-address">{{ $snapshot['display_name'] ?? $snapshot['name'] ?? '' }} · {{ $snapshot['address'] ?? '' }} · {{ $snapshot['postal_code'] ?? '' }} {{ $snapshot['city'] ?? '' }}</div>
        @endif
        <div style="font-weight:700; font-size:{{ $fs }}px; line-height:1.3; margin-bottom:1mm;">{{ $customer->name ?? '' }}</div>
        @if($customer->contact_person ?? null)<div style="font-size:{{ $fs }}px; line-height:1.3; margin-bottom:1mm;">{{ $customer->contact_person }}</div>@endif
        <div style="font-size:{{ $fs }}px; line-height:1.35;">
            @if($customer->address ?? null){{ $customer->address }}<br>@endif
            @if(($customer->postal_code ?? null) && ($customer->city ?? null))
                {{ $customer->postal_code }} {{ $customer->city }}
                @if(($customer->country ?? null) && $customer->country !== 'Deutschland')<br>{{ $customer->country }}@endif
            @endif
        </div>
    </div>
</div>
@endif

{{-- ══ INTRO ═══════════════════════════════════════════════════════════════ --}}
@if(($ls['content']['show_notes'] ?? true) && ($offer->notes ?? null))
<div style="margin-bottom:6mm; font-size:{{ $fs }}px; line-height:1.6;">{{ $offer->notes }}</div>
@else
<div style="margin-bottom:6mm; font-size:{{ $fs }}px; line-height:1.6;">
    <div style="margin-bottom:3px;">Sehr geehrte Damen und Herren,</div>
    <div>anbei übermitteln wir Ihnen unser Angebot. Wir freuen uns auf eine erfolgreiche Zusammenarbeit:</div>
</div>
@endif

{{-- ══ ITEMS TABLE ═════════════════════════════════════════════════════════ --}}
@php $tableHeaderBg = $primary; $altRowBg = $accent; $cellPadding = '6px 7px'; @endphp
@include('pdf.offer-partials.items-table')

{{-- ══ TOTALS ═══════════════════════════════════════════════════════════════ --}}
<div style="margin-top:8px;">
    @php $totalRowBg = $primary; $tableWidth = '300px'; @endphp
    @include('pdf.offer-partials.totals')
</div>

@if(($ls['content']['show_payment_terms'] ?? true) && ($offer->terms_conditions ?? null))
<div style="margin-top:6mm; font-size:{{ $fs - 1 }}px; color:{{ $secCol }}; line-height:1.5;">{{ $offer->terms_conditions }}</div>
@endif

<div style="margin-top:6mm; font-size:{{ $fs }}px; page-break-inside:avoid;">
    <div style="margin-bottom:3px;">Mit freundlichen Grüßen</div>
    <div style="font-weight:600;">{{ $snapshot['name'] ?? '' }}</div>
</div>

</div>
