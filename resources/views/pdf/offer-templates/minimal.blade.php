{{-- Minimal: No decoration – logo top-right, company below, thin separator lines only --}}
@php
    $ls      = $layoutSettings;
    $primary = $ls['colors']['primary']   ?? '#111827';
    $accent  = $ls['colors']['accent']    ?? '#f9fafb';
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
@endphp
<div class="container">

{{-- ══ MINIMAL HEADER ══════════════════════════════════════════════════════ --}}
<table style="width:100%; border-collapse:collapse; margin-bottom:5mm; border-bottom:1px solid #d1d5db; padding-bottom:4mm;">
    <tr>
        <td style="vertical-align:bottom;">
            @if($ls['content']['show_company_address'] ?? true)
                <div style="font-size:{{ $fs - 1 }}px; color:{{ $secCol }}; line-height:1.5;">
                    @if($snapshot['address'] ?? null){{ $snapshot['address'] }},&ensp;@endif
                    @if(($snapshot['postal_code'] ?? null) && ($snapshot['city'] ?? null)){{ $snapshot['postal_code'] }} {{ $snapshot['city'] }}@endif
                    @if($snapshot['phone'] ?? null) &ensp;|&ensp; {{ $snapshot['phone'] }}@endif
                    @if($snapshot['email'] ?? null) &ensp;|&ensp; {{ $snapshot['email'] }}@endif
                </div>
                <div style="font-size:{{ $fsH + 3 }}px; font-weight:700; color:{{ $textCol }}; margin-top:1mm;">{{ $snapshot['name'] ?? '' }}</div>
            @endif
        </td>
        @if($showLogo)
        <td style="text-align:right; vertical-align:top; width:40%;">
            <img src="{{ $logoSrc }}" alt="Logo" style="max-height:24mm; max-width:70mm;">
        </td>
        @endif
    </tr>
</table>

{{-- ══ ADDRESS BLOCK + PLAIN INFO ════════════════════════════════════════ --}}
<table style="width:100%; border-collapse:collapse; margin-bottom:8mm;">
    <tr>
        <td style="width:52%; vertical-align:top; padding-right:8mm;">
            @if($customer)
            <div class="din-5008-address">
                @if($ls['content']['show_company_address'] ?? true)
                    <div class="sender-return-address">{{ $snapshot['name'] ?? '' }} · {{ $snapshot['address'] ?? '' }} · {{ $snapshot['postal_code'] ?? '' }} {{ $snapshot['city'] ?? '' }}</div>
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
            <table style="width:100%; border-collapse:collapse; font-size:{{ $fs - 1 }}px;">
                <tr><td style="padding:3px 0; color:{{ $secCol }}; border-bottom:1px solid #e5e7eb; width:46%;">Angebot Nr.</td><td style="padding:3px 0 3px 6px; font-weight:700; border-bottom:1px solid #e5e7eb;">{{ $offer->number ?? '' }}</td></tr>
                <tr><td style="padding:3px 0; color:{{ $secCol }}; border-bottom:1px solid #e5e7eb;">Datum</td><td style="padding:3px 0 3px 6px; font-weight:600; border-bottom:1px solid #e5e7eb;">{{ fmtOfferDate($offer->issue_date ?? $offer->created_at, $dateFormat) }}</td></tr>
                @if(($ls['content']['show_validity_period'] ?? true) && ($offer->validity_date ?? $offer->valid_until ?? null))
                <tr><td style="padding:3px 0; color:{{ $secCol }}; border-bottom:1px solid #e5e7eb;">Gültig bis</td><td style="padding:3px 0 3px 6px; font-weight:600; border-bottom:1px solid #e5e7eb;">{{ fmtOfferDate($offer->validity_date ?? $offer->valid_until, $dateFormat) }}</td></tr>
                @endif
                @if(($ls['content']['show_customer_number'] ?? true) && $customer && !empty($customer->number ?? $customer->customer_number ?? null))
                <tr><td style="padding:3px 0; color:{{ $secCol }}; border-bottom:1px solid #e5e7eb;">Kunden-Nr.</td><td style="padding:3px 0 3px 6px; font-weight:600; border-bottom:1px solid #e5e7eb;">{{ $customer->number ?? $customer->customer_number }}</td></tr>
                @endif
                @if(!empty($offer->bauvorhaben) && ($ls['content']['show_bauvorhaben'] ?? true))
                <tr><td style="padding:3px 0; color:{{ $secCol }};">BV</td><td style="padding:3px 0 3px 6px; font-weight:700; color:{{ $primary }};">{{ $offer->bauvorhaben }}</td></tr>
                @endif
            </table>
        </td>
    </tr>
</table>

{{-- ══ DOCUMENT TITLE ══════════════════════════════════════════════════════ --}}
<div style="margin-bottom:5mm; padding-bottom:3mm; border-bottom:1px solid #d1d5db;">
    <div style="font-size:{{ $fsH + 5 }}px; font-weight:700; color:{{ $textCol }}; letter-spacing:0.5px;">Angebot</div>
</div>

{{-- ══ INTRO ═══════════════════════════════════════════════════════════════ --}}
@if(($ls['content']['show_notes'] ?? true) && ($offer->notes ?? null))
<div style="margin-bottom:5mm; font-size:{{ $fs }}px; line-height:1.6;">{{ $offer->notes }}</div>
@else
<div style="margin-bottom:5mm; font-size:{{ $fs }}px; line-height:1.6; color:{{ $textCol }};">
    <div style="margin-bottom:3px;">Sehr geehrte Damen und Herren,</div>
    <div>vielen Dank für Ihr Interesse. Nachfolgend erhalten Sie unser Angebot:</div>
</div>
@endif

{{-- ══ ITEMS TABLE: header underline only ═════════════════════════════════ --}}
@php $tableHeaderStyle = 'border-bottom:2px solid ' . $textCol . '; color:' . $textCol . ';'; $cellPadding = '6px 5px'; @endphp
@include('pdf.offer-partials.items-table')

{{-- ══ TOTALS ═══════════════════════════════════════════════════════════════ --}}
<div style="margin-top:8px;">
    @php $totalRowBg = $textCol; $tableWidth = '270px'; @endphp
    @include('pdf.offer-partials.totals')
</div>

@if(($ls['content']['show_payment_terms'] ?? true) && ($offer->terms_conditions ?? null))
<div style="margin-top:6mm; font-size:{{ $fs - 1 }}px; color:{{ $secCol }}; line-height:1.5;">{{ $offer->terms_conditions }}</div>
@endif

<div style="margin-top:8mm; font-size:{{ $fs }}px; color:{{ $textCol }};">
    <div style="margin-bottom:3px;">Mit freundlichen Grüßen</div>
    <div style="font-weight:600;">{{ $snapshot['name'] ?? '' }}</div>
</div>

</div>
