{{-- Creative (Clean): Light gray header band, gray info card, organized modern layout --}}
@php
    $ls      = $layoutSettings;
    $primary = $ls['colors']['primary']   ?? '#0f766e';
    $accent  = $ls['colors']['accent']    ?? '#f0fdf4';
    $textCol = $ls['colors']['text']      ?? '#1f2937';
    $secCol  = $ls['colors']['secondary'] ?? '#6b7280';
    $fs      = $bodyFontSize;
    $fsH     = $headingFontSize;
    $gray1   = '#f3f4f6';
    $gray2   = '#e5e7eb';

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

{{-- ══ GRAY HEADER BAND ════════════════════════════════════════════════════ --}}
<table style="width:100%; border-collapse:collapse; background-color:{{ $gray1 }}; border-bottom:3px solid {{ $primary }}; margin-bottom:5mm; padding:4mm 6mm;">
    <tr>
        @if($showLogo)
        <td style="padding:5mm 6mm; vertical-align:middle; width:38%;">
            <img src="{{ $logoSrc }}" alt="Logo" style="max-height:{{ $logoH }}; max-width:{{ $logoW }};">
        </td>
        @endif
        <td style="padding:5mm 6mm; vertical-align:middle; {{ $showLogo ? 'text-align:right;' : '' }}">
            <div style="font-size:{{ $fsH + 5 }}px; font-weight:800; color:{{ $textCol }}; line-height:1.15; margin-bottom:1mm;">{{ $snapshot['display_name'] ?? $snapshot['name'] ?? '' }}</div>
            @if($ls['content']['show_company_address'] ?? true)
                <div style="font-size:{{ $fs - 1 }}px; color:{{ $secCol }}; line-height:1.4;">
                    @if($snapshot['address'] ?? null){{ $snapshot['address'] }}@endif
                    @if(($snapshot['postal_code'] ?? null) && ($snapshot['city'] ?? null)) &ensp;·&ensp; {{ $snapshot['postal_code'] }} {{ $snapshot['city'] }}@endif
                    @if($snapshot['phone'] ?? null)<br>Tel. {{ $snapshot['phone'] }}@endif
                    @if($snapshot['email'] ?? null)@if($snapshot['phone'] ?? null) &ensp;·&ensp; @else<br>@endif{{ $snapshot['email'] }}@endif
                </div>
            @endif
        </td>
    </tr>
</table>

{{-- ══ ADDRESS + GRAY INFO CARD ════════════════════════════════════════════ --}}
<table style="width:100%; border-collapse:collapse; margin-bottom:5mm;">
    <tr>
        <td style="width:52%; vertical-align:top; padding-right:6mm;">
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
                    @if(isset($customer->vat_number) && $customer->vat_number)<br>USt-IdNr.: {{ $customer->vat_number }}@endif
                </div>
            </div>
            @endif
        </td>
        <td style="width:48%; vertical-align:top;">
            <table style="width:100%; border-collapse:collapse; background:{{ $gray1 }}; border-top:3px solid {{ $primary }}; font-size:{{ $fs - 1 }}px;">
                <tr><td style="padding:3px 8px; color:{{ $secCol }}; border-bottom:1px solid {{ $gray2 }}; width:44%;">Angebot Nr.</td><td style="padding:3px 8px; font-weight:700; border-bottom:1px solid {{ $gray2 }};">{{ $offer->number ?? '' }}</td></tr>
                <tr><td style="padding:3px 8px; color:{{ $secCol }}; border-bottom:1px solid {{ $gray2 }};">Datum</td><td style="padding:3px 8px; font-weight:600; border-bottom:1px solid {{ $gray2 }};">{{ fmtOfferDate($offer->issue_date ?? $offer->created_at, $dateFormat) }}</td></tr>
                @if(($ls['content']['show_validity_period'] ?? true) && ($offer->validity_date ?? $offer->valid_until ?? null))
                <tr><td style="padding:3px 8px; color:{{ $secCol }}; border-bottom:1px solid {{ $gray2 }};">Gültig bis</td><td style="padding:3px 8px; font-weight:600; border-bottom:1px solid {{ $gray2 }};">{{ fmtOfferDate($offer->validity_date ?? $offer->valid_until, $dateFormat) }}</td></tr>
                @endif
                @if(($ls['content']['show_customer_number'] ?? true) && $customer && !empty($customer->number ?? $customer->customer_number ?? null))
                <tr><td style="padding:3px 8px; color:{{ $secCol }}; border-bottom:1px solid {{ $gray2 }};">Kunden-Nr.</td><td style="padding:3px 8px; font-weight:600; border-bottom:1px solid {{ $gray2 }};">{{ $customer->number ?? $customer->customer_number }}</td></tr>
                @endif
                @if(!empty($offer->bauvorhaben) && ($ls['content']['show_bauvorhaben'] ?? true))
                <tr><td style="padding:3px 8px; color:{{ $secCol }};">BV</td><td style="padding:3px 8px; font-weight:700; color:{{ $primary }};">{{ $offer->bauvorhaben }}</td></tr>
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

{{-- ══ ITEMS TABLE: gray header, light alternating rows ════════════════════ --}}
@php
    $tableHeaderBg        = $gray1;
    $tableHeaderTextColor = $textCol;
    $tableHeaderStyle     = 'border-top:2px solid ' . $primary . '; border-bottom:2px solid ' . $primary . ';';
    $altRowBg             = $gray1;
    $cellPadding          = '6px 7px';
@endphp
@include('pdf.offer-partials.items-table')

{{-- ══ TOTALS ═══════════════════════════════════════════════════════════════ --}}
<div style="margin-top:8px;">
    @php $totalRowBg = $primary; $tableWidth = '290px'; @endphp
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
