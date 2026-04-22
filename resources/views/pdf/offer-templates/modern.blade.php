{{-- Modern: White header with bold accent border + colored info box + vivid table header --}}
@php
    $ls      = $layoutSettings;
    $primary = $ls['colors']['primary']   ?? '#2563eb';
    $accent  = $ls['colors']['accent']    ?? '#eff6ff';
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

{{-- ══ HEADER with bold left accent border ════════════════════════════════ --}}
<div style="border-left:7px solid {{ $primary }}; padding-left:6mm; margin-bottom:5mm; padding-bottom:3mm; border-bottom:1px solid #e5e7eb;">
    @if($showLogo)
        <div style="margin-bottom:3mm;"><img src="{{ $logoSrc }}" alt="Logo" style="max-height:{{ $logoH }}; max-width:{{ $logoW }};"></div>
    @endif
    <div style="font-size:{{ $fsH + 6 }}px; font-weight:800; color:{{ $primary }}; line-height:1.1; margin-bottom:2mm;">{{ $snapshot['display_name'] ?? $snapshot['name'] ?? '' }}</div>
    @if($ls['content']['show_company_address'] ?? true)
        <div style="font-size:{{ $fs - 1 }}px; color:{{ $secCol }}; line-height:1.45;">
            @if($snapshot['address'] ?? null){{ $snapshot['address'] }}@endif
            @if(($snapshot['postal_code'] ?? null) && ($snapshot['city'] ?? null)) &nbsp;·&nbsp; {{ $snapshot['postal_code'] }} {{ $snapshot['city'] }}@endif
            @if($snapshot['phone'] ?? null) &nbsp;·&nbsp; Tel. {{ $snapshot['phone'] }}@endif
            @if($snapshot['email'] ?? null) &nbsp;·&nbsp; {{ $snapshot['email'] }}@endif
        </div>
    @endif
</div>

{{-- ══ ADDRESS BLOCK + COLORED INFO PANEL ════════════════════════════════ --}}
<table style="width:100%; border-collapse:collapse; margin-bottom:5mm;">
    <tr>
        <td style="width:52%; vertical-align:top; padding-right:8mm;">
            @if($customer)
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
            @endif
        </td>
        <td style="width:48%; vertical-align:top;">
            <table style="width:100%; border-collapse:collapse; background:{{ $accent }}; border-left:4px solid {{ $primary }}; font-size:{{ $fs - 1 }}px;">
                <tr><td style="padding:3px 8px; color:{{ $primary }}; border-bottom:1px solid rgba(37,99,235,0.2); width:44%;">Angebot Nr.</td><td style="padding:3px 8px; font-weight:700; border-bottom:1px solid rgba(37,99,235,0.2);">{{ $offer->number ?? '' }}</td></tr>
                <tr><td style="padding:3px 8px; color:{{ $primary }}; border-bottom:1px solid rgba(37,99,235,0.2);">Datum</td><td style="padding:3px 8px; font-weight:600; border-bottom:1px solid rgba(37,99,235,0.2);">{{ fmtOfferDate($offer->issue_date ?? $offer->created_at, $dateFormat) }}</td></tr>
                @if(($ls['content']['show_validity_period'] ?? true) && ($offer->validity_date ?? $offer->valid_until ?? null))
                <tr><td style="padding:3px 8px; color:{{ $primary }}; border-bottom:1px solid rgba(37,99,235,0.2);">Gültig bis</td><td style="padding:3px 8px; font-weight:600; border-bottom:1px solid rgba(37,99,235,0.2);">{{ fmtOfferDate($offer->validity_date ?? $offer->valid_until, $dateFormat) }}</td></tr>
                @endif
                @if(($ls['content']['show_customer_number'] ?? true) && $customer && !empty($customer->number ?? $customer->customer_number ?? null))
                <tr><td style="padding:3px 8px; color:{{ $primary }}; border-bottom:1px solid rgba(37,99,235,0.2);">Kunden-Nr.</td><td style="padding:3px 8px; font-weight:600; border-bottom:1px solid rgba(37,99,235,0.2);">{{ $customer->number ?? $customer->customer_number }}</td></tr>
                @endif
                @if(!empty($offer->bauvorhaben) && ($ls['content']['show_bauvorhaben'] ?? true))
                <tr><td style="padding:3px 8px; color:{{ $primary }};">BV</td><td style="padding:3px 8px; font-weight:700; color:{{ $primary }};">{{ $offer->bauvorhaben }}</td></tr>
                @endif
            </table>
        </td>
    </tr>
</table>

{{-- ══ INTRO ═══════════════════════════════════════════════════════════════ --}}
@if(($ls['content']['show_notes'] ?? true) && ($offer->notes ?? null))
<div style="margin-bottom:5mm; font-size:{{ $fs }}px; line-height:1.6;">{{ $offer->notes }}</div>
@else
<div style="margin-bottom:5mm; font-size:{{ $fs }}px; line-height:1.6;">
    <div style="margin-bottom:3px;">Sehr geehrte Damen und Herren,</div>
    <div>anbei übermitteln wir Ihnen unser Angebot. Wir freuen uns auf eine erfolgreiche Zusammenarbeit:</div>
</div>
@endif

{{-- ══ ITEMS TABLE ═════════════════════════════════════════════════════════ --}}
@php $tableHeaderBg = $primary; $altRowBg = $accent; $cellPadding = '6px 7px'; @endphp
@include('pdf.offer-partials.items-table')

{{-- ══ TOTALS ═══════════════════════════════════════════════════════════════ --}}
<div style="margin-top:8px;">
    @php $totalRowBg = $primary; $tableWidth = '295px'; @endphp
    @include('pdf.offer-partials.totals')
</div>

@if(($ls['content']['show_payment_terms'] ?? true) && ($offer->terms_conditions ?? null))
<div style="margin-top:6mm; font-size:{{ $fs - 1 }}px; color:{{ $secCol }}; line-height:1.5;">{{ $offer->terms_conditions }}</div>
@endif

<div style="margin-top:6mm; font-size:{{ $fs }}px; border-left:7px solid {{ $primary }}; padding-left:5mm; page-break-inside:avoid;">
    <div style="margin-bottom:3px;">Mit freundlichen Grüßen</div>
    <div style="font-weight:600;">{{ $snapshot['name'] ?? '' }}</div>
</div>

</div>
