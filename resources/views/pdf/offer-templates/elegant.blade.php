{{-- Elegant: Centered sophisticated header with decorative rules, refined table --}}
@php
    $ls      = $layoutSettings;
    $primary = $ls['colors']['primary']   ?? '#374151';
    $accent  = $ls['colors']['accent']    ?? '#f9fafb';
    $textCol = $ls['colors']['text']      ?? '#1f2937';
    $secCol  = $ls['colors']['secondary'] ?? '#9ca3af';
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

{{-- ══ CENTERED HEADER ════════════════════════════════════════════════════ --}}
<div style="text-align:center; margin-bottom:7mm; padding-bottom:4mm; border-bottom:0.5px solid {{ $secCol }};">
    @if($showLogo)
        <div style="margin-bottom:3mm;"><img src="{{ $logoSrc }}" alt="Logo" style="max-height:22mm; max-width:70mm;"></div>
    @endif
    <table style="width:100%; border-collapse:collapse; margin-bottom:2mm;">
        <tr>
            <td style="border-bottom:1px solid {{ $secCol }}; height:1px; width:20%;"></td>
            <td style="text-align:center; padding:0 8px; white-space:nowrap;">
                <span style="font-size:{{ $fsH + 4 }}px; font-weight:700; color:{{ $textCol }}; letter-spacing:1.5px; text-transform:uppercase;">{{ $snapshot['name'] ?? '' }}</span>
            </td>
            <td style="border-bottom:1px solid {{ $secCol }}; height:1px; width:20%;"></td>
        </tr>
    </table>
    @if($ls['content']['show_company_address'] ?? true)
        <div style="font-size:{{ $fs - 1 }}px; color:{{ $secCol }}; line-height:1.5; margin-top:2mm;">
            @if($snapshot['address'] ?? null){{ $snapshot['address'] }}@endif
            @if(($snapshot['postal_code'] ?? null) && ($snapshot['city'] ?? null)) &ensp;·&ensp; {{ $snapshot['postal_code'] }} {{ $snapshot['city'] }}@endif
            @if($snapshot['email'] ?? null) &ensp;·&ensp; {{ $snapshot['email'] }}@endif
            @if($snapshot['website'] ?? null) &ensp;·&ensp; {{ $snapshot['website'] }}@endif
        </div>
    @endif
</div>

{{-- ══ ADDRESS BLOCK + STRUCTURED INFO TABLE ══════════════════════════════ --}}
<table style="width:100%; border-collapse:collapse; margin-bottom:8mm;">
    <tr>
        <td style="width:52%; vertical-align:top; padding-right:8mm;">
            @if($customer)
            <div class="din-5008-address">
                @if($ls['content']['show_company_address'] ?? true)
                    <div class="sender-return-address">{{ $snapshot['name'] ?? '' }} · {{ $snapshot['address'] ?? '' }} · {{ $snapshot['postal_code'] ?? '' }} {{ $snapshot['city'] ?? '' }}</div>
                @endif
                <div style="font-weight:700; font-size:{{ $fs }}px; line-height:1.3; margin-bottom:1mm;">{{ $customer->name ?? '' }}</div>
                @if($customer->contact_person ?? null)<div style="font-size:{{ $fs }}px; line-height:1.3; margin-bottom:1mm; color:{{ $secCol }};">{{ $customer->contact_person }}</div>@endif
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
            <table style="width:100%; border-collapse:collapse; border-left:2px solid {{ $secCol }}; font-size:{{ $fs - 1 }}px;">
                <tr><td style="padding:4px 0 4px 6px; color:{{ $secCol }}; border-bottom:1px solid #e5e7eb; width:46%;">Angebot Nr.</td><td style="padding:4px 6px; font-weight:700; border-bottom:1px solid #e5e7eb;">{{ $offer->number ?? '' }}</td></tr>
                <tr><td style="padding:4px 0 4px 6px; color:{{ $secCol }}; border-bottom:1px solid #e5e7eb;">Datum</td><td style="padding:4px 6px; font-weight:600; border-bottom:1px solid #e5e7eb;">{{ fmtOfferDate($offer->issue_date ?? $offer->created_at, $dateFormat) }}</td></tr>
                @if(($ls['content']['show_validity_period'] ?? true) && ($offer->validity_date ?? $offer->valid_until ?? null))
                <tr><td style="padding:4px 0 4px 6px; color:{{ $secCol }}; border-bottom:1px solid #e5e7eb;">Gültig bis</td><td style="padding:4px 6px; font-weight:600; border-bottom:1px solid #e5e7eb;">{{ fmtOfferDate($offer->validity_date ?? $offer->valid_until, $dateFormat) }}</td></tr>
                @endif
                @if(($ls['content']['show_customer_number'] ?? true) && $customer && !empty($customer->number ?? $customer->customer_number ?? null))
                <tr><td style="padding:4px 0 4px 6px; color:{{ $secCol }}; border-bottom:1px solid #e5e7eb;">Kunden-Nr.</td><td style="padding:4px 6px; font-weight:600; border-bottom:1px solid #e5e7eb;">{{ $customer->number ?? $customer->customer_number }}</td></tr>
                @endif
                @if(!empty($offer->bauvorhaben) && ($ls['content']['show_bauvorhaben'] ?? true))
                <tr><td style="padding:4px 0 4px 6px; color:{{ $secCol }};">BV</td><td style="padding:4px 6px; font-weight:700; color:{{ $primary }};">{{ $offer->bauvorhaben }}</td></tr>
                @endif
            </table>
        </td>
    </tr>
</table>

{{-- ══ CENTERED DOCUMENT TITLE ════════════════════════════════════════════ --}}
<div style="text-align:center; margin-bottom:6mm; padding-bottom:4mm;">
    <div style="font-size:{{ $fsH + 5 }}px; font-weight:300; color:{{ $textCol }}; letter-spacing:4px; text-transform:uppercase; margin-bottom:2mm;">ANGEBOT</div>
    <div style="font-size:{{ $fs }}px; color:{{ $secCol }}; letter-spacing:1px;">Nr.&ensp;{{ $offer->number ?? '' }}</div>
    <div style="border-bottom:0.5px solid {{ $secCol }}; margin-top:4mm;"></div>
</div>

{{-- ══ INTRO ═══════════════════════════════════════════════════════════════ --}}
@if(($ls['content']['show_notes'] ?? true) && ($offer->notes ?? null))
<div style="margin-bottom:5mm; font-size:{{ $fs }}px; line-height:1.7;">{{ $offer->notes }}</div>
@else
<div style="margin-bottom:5mm; font-size:{{ $fs }}px; line-height:1.7;">
    <div style="margin-bottom:3px;">Sehr geehrte Damen und Herren,</div>
    <div>vielen Dank für Ihr Interesse. Nachfolgend unterbreiten wir Ihnen unser Angebot:</div>
</div>
@endif

{{-- ══ ITEMS TABLE: light gray header, thin borders ════════════════════════ --}}
@php
    $tableHeaderBg        = $accent;
    $tableHeaderTextColor = $textCol;
    $tableHeaderStyle     = 'border-bottom:2px solid ' . $textCol . ';';
    $cellPadding          = '7px 7px';
@endphp
@include('pdf.offer-partials.items-table')

{{-- ══ TOTALS ═══════════════════════════════════════════════════════════════ --}}
<div style="margin-top:8px;">
    @php $totalRowBg = $primary; $tableWidth = '280px'; @endphp
    @include('pdf.offer-partials.totals')
</div>

@if(($ls['content']['show_payment_terms'] ?? true) && ($offer->terms_conditions ?? null))
<div style="margin-top:6mm; font-size:{{ $fs - 1 }}px; color:{{ $secCol }}; line-height:1.5;">{{ $offer->terms_conditions }}</div>
@endif

<div style="margin-top:8mm; font-size:{{ $fs }}px; text-align:center; border-top:0.5px solid {{ $secCol }}; padding-top:5mm;">
    <div style="margin-bottom:3px; color:{{ $secCol }};">Mit freundlichen Grüßen</div>
    <div style="font-weight:700; letter-spacing:0.5px;">{{ $snapshot['name'] ?? '' }}</div>
</div>

</div>
