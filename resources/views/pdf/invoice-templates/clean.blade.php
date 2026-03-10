{{-- Clean/Kreativ: Purple badge header + date strip + accent subject (from invoice-kreativ.html) --}}
@php
    $ls      = $layoutSettings;
    $primary = $ls['colors']['primary'] ?? '#7c3aed';
    $p2      = '#a855f7';
    $p4      = '#f5f3ff';
    $ink     = '#1c1033';
    $mid     = '#6b21a8';
    $soft    = '#a78bfa';
    $border  = '#e9d5ff';
    $bg      = '#faf5ff';
    $dark    = '#1c1033';
    $fs      = $bodyFontSize;

    // Logo
    $logoRelPath = isset($snapshot['logo']) ? ltrim(preg_replace('#^storage/#', '', (string)$snapshot['logo']), '/') : null;
    $logoSrc = null;
    if ($logoRelPath && ($ls['branding']['show_logo'] ?? true)) {
        if (isset($preview) && $preview) {
            $logoSrc = asset('storage/' . $logoRelPath);
        } elseif (\Storage::disk('public')->exists($logoRelPath)) {
            $lp = \Storage::disk('public')->path($logoRelPath);
            $logoSrc = 'data:' . mime_content_type($lp) . ';base64,' . base64_encode(file_get_contents($lp));
        }
    }
    $showLogo = !empty($logoSrc);

    $isCorrection     = isset($invoice->is_correction) && (bool)$invoice->is_correction;
    $invoiceTypeLabel = $isCorrection
        ? 'Stornorechnung'
        : getReadableInvoiceType($invoice->invoice_type ?? 'standard', $invoice->sequence_number ?? null);
    $customer         = $invoice->customer ?? null;
    $skontoAmount     = (float)($invoice->skonto_amount ?? 0);
    $hasSkonto        = !empty($invoice->skonto_percent) && !empty($invoice->skonto_days) && $skontoAmount > 0;

    $issueDateFmt = $invoice->issue_date ? formatInvoiceDate($invoice->issue_date, $dateFormat ?? 'd.m.Y') : null;
    $dueDateFmt   = $invoice->due_date   ? formatInvoiceDate($invoice->due_date,   $dateFormat ?? 'd.m.Y') : null;

    $servicePeriodStart = $invoice->service_period_start ?? null;
    $servicePeriodEnd   = $invoice->service_period_end   ?? null;
    $hasPeriod = $servicePeriodStart || $servicePeriodEnd;
    $periodStr = '';
    if ($hasPeriod) {
        $periodStr = trim(
            ($servicePeriodStart ? formatInvoiceDate($servicePeriodStart, $dateFormat ?? 'd.m.Y') : '') .
            ($servicePeriodStart && $servicePeriodEnd ? ' – ' : '') .
            ($servicePeriodEnd   ? formatInvoiceDate($servicePeriodEnd,   $dateFormat ?? 'd.m.Y') : '')
        );
    }

    $bankIban = $snapshot['bank_iban'] ?? null;
    $bankBic  = $snapshot['bank_bic']  ?? null;
    $bankName = $snapshot['bank_name'] ?? null;

    $tableHeaderBg        = null;
    $tableHeaderTextColor = $primary;
    $tableHeaderStyle     = "border-bottom:2px solid {$primary};";
    $altRowBg             = null;
    $cellPadding          = '7px 8px';
    $showRowNumber        = $ls['content']['show_row_number'] ?? false;
    $totalRowBg           = $primary;
    $totalRowTextColor    = '#ffffff';
    $tableWidth           = '288px';
@endphp

<div class="container" style="padding:0; font-family:{{ $ls['fonts']['body'] ?? 'DejaVu Sans' }},sans-serif; font-size:{{ $fs }}px; color:{{ $ink }}; overflow:hidden;">

{{-- HEADER: white, logo left + badge right --}}
<table style="width:100%; border-collapse:collapse;">
<tr>
    <td style="padding:7mm 0 7mm 20mm; vertical-align:middle;">
        @if($showLogo)
            <img src="{{ $logoSrc }}" alt="Logo" style="max-height:14mm; max-width:48mm; display:block;">
        @endif
        @if(!$showLogo || ($ls['content']['show_company_address'] ?? true))
        <div style="font-size:{{ $fs + 3 }}px; font-weight:800; color:{{ $dark }}; letter-spacing:-1px; line-height:1;">
            {{ $snapshot['name'] ?? '' }}
        </div>
        <div style="font-size:{{ $fs - 2 }}px; color:{{ $soft }}; margin-top:1.5mm; font-weight:400;">
            {{ $snapshot['address'] ?? '' }}@if($snapshot['postal_code'] ?? null) &middot; {{ $snapshot['postal_code'] }} {{ $snapshot['city'] ?? '' }}@endif
        </div>
        @endif
    </td>
    <td style="padding:7mm 20mm 7mm 0; vertical-align:middle; text-align:right;">
        {{-- Badge --}}
        <div style="display:inline-block; background-color:{{ $isCorrection ? '#dc2626' : $primary }}; color:white; padding:2mm 5mm; font-size:{{ $fs - 1 }}px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">
            {{ $isCorrection ? 'STORNO' : strtoupper($invoiceTypeLabel) }}
        </div>
        <div style="font-size:{{ $fs - 2 }}px; color:{{ $soft }}; text-align:right; margin-top:1.5mm; font-weight:500;">{{ $invoice->number }}</div>
    </td>
</tr>
</table>

{{-- Date strip: light purple band --}}
<table style="width:100%; border-collapse:collapse; background-color:{{ $p4 }}; border:1px solid {{ $border }}; margin:0 20mm;">
<tr>
    <td style="padding:2.5mm 4mm; vertical-align:top;">
        <div style="font-size:6pt; text-transform:uppercase; letter-spacing:1.2px; color:{{ $soft }}; font-weight:600;">Rechnungsnr.</div>
        <div style="font-size:{{ $fs - 1 }}px; font-weight:600; color:{{ $dark }}; margin-top:0.3mm;">{{ $invoice->number }}</div>
    </td>
    <td style="padding:2.5mm 4mm; vertical-align:top;">
        <div style="font-size:6pt; text-transform:uppercase; letter-spacing:1.2px; color:{{ $soft }}; font-weight:600;">Datum</div>
        <div style="font-size:{{ $fs - 1 }}px; font-weight:600; color:{{ $dark }}; margin-top:0.3mm;">{{ $issueDateFmt ?? '–' }}</div>
    </td>
    <td style="padding:2.5mm 4mm; vertical-align:top;">
        <div style="font-size:6pt; text-transform:uppercase; letter-spacing:1.2px; color:{{ $soft }}; font-weight:600;">Zahlungsziel</div>
        <div style="font-size:{{ $fs - 1 }}px; font-weight:600; color:{{ $dark }}; margin-top:0.3mm;">{{ $dueDateFmt ?? '–' }}</div>
    </td>
    @if($hasPeriod)
    <td style="padding:2.5mm 4mm; vertical-align:top;">
        <div style="font-size:6pt; text-transform:uppercase; letter-spacing:1.2px; color:{{ $soft }}; font-weight:600;">Leistungszeitraum</div>
        <div style="font-size:{{ $fs - 1 }}px; font-weight:600; color:{{ $dark }}; margin-top:0.3mm;">{{ $periodStr }}</div>
    </td>
    @endif
</tr>
</table>

{{-- CONTENT --}}
<div style="padding:0 20mm;">

{{-- Address zone --}}
<table style="width:100%; border-collapse:collapse; margin-top:5mm;">
<tr>
    <td style="width:86mm; vertical-align:top; padding-right:6mm;">
        @if($ls['content']['show_company_address'] ?? true)
        <div style="font-size:7pt; color:{{ $soft }}; border-bottom:0.25mm solid {{ $border }}; padding-bottom:1.5mm; margin-bottom:2mm; line-height:1;">
            {{ $snapshot['name'] ?? '' }}@if($snapshot['address'] ?? null) &middot; {{ $snapshot['address'] }}@endif@if($snapshot['postal_code'] ?? null) &middot; {{ $snapshot['postal_code'] }} {{ $snapshot['city'] ?? '' }}@endif
        </div>
        @endif
        @if($customer)
        <div style="font-size:{{ $fs }}px; line-height:1.55;">
            @if($customer->company)<strong>{{ $customer->company }}</strong><br>@endif
            @if($customer->salutation || $customer->name)<strong>{{ trim(($customer->salutation ?? '').' '.($customer->name ?? '')) }}</strong><br>@endif
            @if($customer->address){{ $customer->address }}<br>@endif
            @if($customer->postal_code || $customer->city){{ $customer->postal_code ?? '' }} {{ $customer->city ?? '' }}
            @if($customer->country && $customer->country !== 'DE')<br>{{ $customer->country }}@endif
            @endif
        </div>
        @endif
    </td>
    {{-- Info rows right --}}
    <td style="width:54mm; vertical-align:top;">
        <table style="width:100%; border-collapse:collapse; font-size:{{ $fs - 1 }}px;">
        <tr>
            <td style="padding:1.2mm 0; border-bottom:0.2mm solid {{ $border }}; color:{{ $soft }};">Rechnungsnr.</td>
            <td style="padding:1.2mm 0; border-bottom:0.2mm solid {{ $border }}; font-weight:600; color:{{ $dark }}; text-align:right;">{{ $invoice->number }}</td>
        </tr>
        @if($customer?->customer_number)
        <tr>
            <td style="padding:1.2mm 0; border-bottom:0.2mm solid {{ $border }}; color:{{ $soft }};">Kundennr.</td>
            <td style="padding:1.2mm 0; border-bottom:0.2mm solid {{ $border }}; font-weight:600; color:{{ $dark }}; text-align:right;">{{ $customer->customer_number }}</td>
        </tr>
        @endif
        <tr>
            <td style="padding:1.2mm 0; color:{{ $soft }};">Zahlungsziel</td>
            <td style="padding:1.2mm 0; font-weight:600; color:{{ $dark }}; text-align:right;">{{ $dueDateFmt ?? '–' }}</td>
        </tr>
        </table>
    </td>
</tr>
</table>

{{-- Subject with left accent bar --}}
@if($ls['content']['show_subject'] ?? true)
<table style="width:100%; border-collapse:collapse; margin-top:7mm;">
<tr>
    <td style="width:3mm; background-color:{{ $primary }}; vertical-align:top;"></td>
    <td style="padding:2mm 0 2mm 3mm; vertical-align:middle;">
        <div style="font-size:{{ $fs + 2 }}px; font-weight:700; color:{{ $dark }}; letter-spacing:-0.3px;">
            {{ $invoiceTypeLabel }} {{ $invoice->number }}{{ $invoice->title ? ' – '.$invoice->title : '' }}
        </div>
        @if($hasPeriod)
        <div style="font-size:{{ $fs - 2 }}px; color:{{ $soft }}; margin-top:0.5mm;">Leistungszeitraum: {{ $periodStr }}</div>
        @endif
    </td>
</tr>
</table>
@endif

{{-- Salutation --}}
@if($ls['content']['show_salutation'] ?? true)
<div style="margin-top:5mm; font-size:{{ $fs }}px; line-height:1.65; font-weight:300;">
    @if($invoice->salutation)
        {!! nl2br(e($invoice->salutation)) !!}
    @else
        Sehr geehrte Damen und Herren,<br><br>
        für die erbrachten Leistungen erlauben wir uns, folgende Rechnung zu stellen.
    @endif
</div>
@endif

{{-- Items --}}
@include('pdf.invoice-partials.items-table')

{{-- Totals --}}
@include('pdf.invoice-partials.totals')

{{-- Payment boxes --}}
<table style="width:100%; border-collapse:collapse; margin-top:6mm;">
<tr>
    <td style="width:50%; padding-right:2mm; vertical-align:top;">
        <table style="width:100%; border-collapse:collapse; border:1px solid {{ $border }};">
        <tr><td style="background-color:{{ $primary }}; color:white; padding:2mm 3mm; font-size:{{ $fs - 2 }}px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">Bankverbindung</td></tr>
        <tr><td style="padding:3mm; background:{{ $p4 }}; font-size:{{ $fs - 1 }}px; line-height:1.65;">
            @if($snapshot['name'] ?? null)<div><span style="color:{{ $soft }}; font-weight:300;">Inhaberin: </span><strong style="font-weight:600; color:{{ $dark }};">{{ $snapshot['name'] }}</strong></div>@endif
            @if($bankIban)<div><span style="color:{{ $soft }}; font-weight:300;">IBAN: </span><strong style="font-weight:600; color:{{ $dark }};">{{ $bankIban }}</strong></div>@endif
            @if($bankBic)<div><span style="color:{{ $soft }}; font-weight:300;">BIC: </span><strong style="font-weight:600; color:{{ $dark }};">{{ $bankBic }}</strong></div>@endif
            @if($bankName)<div><span style="color:{{ $soft }}; font-weight:300;">Bank: </span><strong style="font-weight:600; color:{{ $dark }};">{{ $bankName }}</strong></div>@endif
            <div style="margin-top:1mm;"><span style="color:{{ $soft }}; font-weight:300;">Verwendung: </span><strong style="font-weight:600; color:{{ $dark }};">{{ $invoice->number }}</strong></div>
        </td></tr>
        </table>
    </td>
    <td style="width:50%; padding-left:2mm; vertical-align:top;">
        <table style="width:100%; border-collapse:collapse; border:1px solid {{ $border }};">
        <tr><td style="background-color:{{ $primary }}; color:white; padding:2mm 3mm; font-size:{{ $fs - 2 }}px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">Zahlungsbedingungen</td></tr>
        <tr><td style="padding:3mm; background:{{ $p4 }}; font-size:{{ $fs - 1 }}px; line-height:1.65;">
            @include('pdf.invoice-partials.payment-terms')
        </td></tr>
        </table>
    </td>
</tr>
</table>

{{-- Closing --}}
@if($ls['content']['show_closing'] ?? true)
<div style="margin-top:7mm; font-size:{{ $fs }}px; line-height:1.7; font-weight:300;">
    @if($invoice->closing)
        {!! nl2br(e($invoice->closing)) !!}
    @else
        Ich freue mich auf die weitere Zusammenarbeit.<br><br>
        Mit freundlichen Grüßen
    @endif
    @if($ls['content']['show_signature'] ?? true)
    <div style="margin-top:3mm; font-size:{{ $fs + 2 }}px; font-weight:700; color:{{ $dark }};">
        {{ $snapshot['name'] ?? '' }}
    </div>
    @endif
</div>
@endif

</div>{{-- /content --}}
@if($ls['branding']['show_footer'] ?? true)
@include('pdf.invoice-partials.footer')
@endif
</div>
