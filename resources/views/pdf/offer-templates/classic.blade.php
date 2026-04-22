{{-- Classic: Dark full-width letterhead band + centered title + fully bordered info table --}}
@php
    $ls      = $layoutSettings;
    $primary = $ls['colors']['primary']   ?? '#1a2e44';
    $accent  = $ls['colors']['accent']    ?? '#f3f4f6';
    $textCol = $ls['colors']['text']      ?? '#1f2937';
    $secCol  = $ls['colors']['secondary'] ?? '#374151';
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

{{-- ══ DARK LETTERHEAD BAND ═══════════════════════════════════════════════ --}}
<table style="width:100%; border-collapse:collapse; background-color:{{ $primary }}; margin-bottom:4mm;">
    <tr>
        <td style="padding:6mm 7mm; vertical-align:middle;">
            <div style="color:white; font-size:{{ $fsH + 6 }}px; font-weight:800; line-height:1.15; letter-spacing:0.5px;">{{ $snapshot['display_name'] ?? $snapshot['name'] ?? '' }}</div>
            @if($ls['content']['show_company_address'] ?? true)
                <div style="color:rgba(255,255,255,0.65); font-size:{{ $fs - 1 }}px; margin-top:2mm; line-height:1.4;">
                    @if($snapshot['address'] ?? null){{ $snapshot['address'] }} &ensp;·&ensp; @endif
                    @if(($snapshot['postal_code'] ?? null) && ($snapshot['city'] ?? null)){{ $snapshot['postal_code'] }} {{ $snapshot['city'] }}@endif
                    @if($snapshot['phone'] ?? null) &ensp;·&ensp; Tel. {{ $snapshot['phone'] }}@endif
                    @if($snapshot['email'] ?? null) &ensp;·&ensp; {{ $snapshot['email'] }}@endif
                </div>
            @endif
        </td>
        @if($showLogo)
        <td style="padding:6mm 7mm; text-align:right; vertical-align:middle; width:35%;">
            <img src="{{ $logoSrc }}" alt="Logo" style="max-height:{{ $logoH }}; max-width:{{ $logoW }};">
        </td>
        @endif
    </tr>
</table>

{{-- ══ ADDRESS BLOCK (left) + BORDERED INFO TABLE (right) ════════════════ --}}
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
            <table style="width:100%; border-collapse:collapse; border:1.5px solid {{ $textCol }}; font-size:{{ $fs - 1 }}px;">
                <tr>
                    <td style="padding:3px 7px; color:{{ $secCol }}; border-bottom:1px solid {{ $textCol }}; border-right:1px solid {{ $textCol }}; width:44%;">Angebot Nr.</td>
                    <td style="padding:3px 7px; font-weight:700; border-bottom:1px solid {{ $textCol }};">{{ $offer->number ?? '' }}</td>
                </tr>
                <tr>
                    <td style="padding:3px 7px; color:{{ $secCol }}; border-bottom:1px solid {{ $textCol }}; border-right:1px solid {{ $textCol }};">Datum</td>
                    <td style="padding:3px 7px; font-weight:600; border-bottom:1px solid {{ $textCol }};">{{ fmtOfferDate($offer->issue_date ?? $offer->created_at, $dateFormat) }}</td>
                </tr>
                @if(($ls['content']['show_validity_period'] ?? true) && ($offer->validity_date ?? $offer->valid_until ?? null))
                <tr>
                    <td style="padding:3px 7px; color:{{ $secCol }}; border-bottom:1px solid {{ $textCol }}; border-right:1px solid {{ $textCol }};">Gültig bis</td>
                    <td style="padding:3px 7px; font-weight:600; border-bottom:1px solid {{ $textCol }};">{{ fmtOfferDate($offer->validity_date ?? $offer->valid_until, $dateFormat) }}</td>
                </tr>
                @endif
                @if(($ls['content']['show_customer_number'] ?? true) && $customer && !empty($customer->number ?? $customer->customer_number ?? null))
                <tr>
                    <td style="padding:3px 7px; color:{{ $secCol }}; border-bottom:1px solid {{ $textCol }}; border-right:1px solid {{ $textCol }};">Kunden-Nr.</td>
                    <td style="padding:3px 7px; font-weight:600; border-bottom:1px solid {{ $textCol }};">{{ $customer->number ?? $customer->customer_number }}</td>
                </tr>
                @endif
                @if(($ls['content']['show_tax_number'] ?? true) && ($snapshot['tax_number'] ?? null))
                <tr>
                    <td style="padding:3px 7px; color:{{ $secCol }}; border-bottom:1px solid {{ $textCol }}; border-right:1px solid {{ $textCol }};">Steuernummer</td>
                    <td style="padding:3px 7px; font-weight:600; border-bottom:1px solid {{ $textCol }};">{{ $snapshot['tax_number'] }}</td>
                </tr>
                @endif
                @if(!empty($offer->bauvorhaben) && ($ls['content']['show_bauvorhaben'] ?? true))
                <tr>
                    <td style="padding:3px 7px; color:{{ $secCol }}; border-right:1px solid {{ $textCol }};">BV</td>
                    <td style="padding:3px 7px; font-weight:700; color:{{ $primary }};">{{ $offer->bauvorhaben }}</td>
                </tr>
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
    <div>vielen Dank für Ihr Interesse. Nachfolgend erhalten Sie unser Angebot:</div>
</div>
@endif

{{-- ══ ITEMS TABLE: full borders, position numbers, alternating rows ═══════ --}}
@php
    $tableHeaderBg    = $textCol;
    $tableOuterBorder = '1.5px solid ' . $textCol;
    $altRowBg         = $accent;
    $cellPadding      = '6px 7px';
    $showRowNumber    = true;
@endphp
@include('pdf.offer-partials.items-table')

{{-- ══ TOTALS ═══════════════════════════════════════════════════════════════ --}}
<div style="margin-top:8px;">
    @php $totalRowBg = $textCol; $tableWidth = '290px'; @endphp
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
