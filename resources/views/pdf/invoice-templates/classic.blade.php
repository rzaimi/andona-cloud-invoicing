{{-- Classic: Serif-inspired top ornament + double-rule header (from invoice-klassisch.html) --}}
@php
    $ls      = $layoutSettings;
    $primary = $ls['colors']['primary'] ?? '#1a1a1a';
    $dark    = '#2c2c2c';
    $light   = '#f5f5f0';
    $border  = '#cccccc';
    $mid     = '#5a5a5a';
    $soft    = '#8a8a8a';
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
    $tableHeaderStyle     = "border-top:2px solid {$dark}; border-bottom:1px solid {$dark};";
    $altRowBg             = $light;
    $cellPadding          = '6px 8px';
    $showRowNumber        = $ls['content']['show_row_number'] ?? false;
    $totalRowBg           = $primary;
    $totalRowTextColor    = '#ffffff';
    $tableWidth           = '290px';
@endphp

<div class="container" style="padding:0; font-family:{{ $ls['fonts']['body'] ?? 'DejaVu Sans' }},sans-serif; font-size:{{ $fs }}px; color:{{ $primary }};">

{{-- Top ornament bar --}}
<div style="height:8mm; background-color:{{ $dark }}; display:block;"></div>

{{-- HEADER: logo left, doc title right, double-rule below --}}
<table style="width:100%; border-collapse:collapse; padding:6mm 22mm; border-bottom:2px solid {{ $border }};">
<tr>
    <td style="padding:6mm 0 6mm 22mm; vertical-align:top;">
        @if($showLogo)
            <img src="{{ $logoSrc }}" alt="Logo" style="max-height:14mm; max-width:48mm; display:block;">
        @else
            <div style="font-size:{{ $fs + 10 }}px; font-weight:600; color:{{ $primary }}; letter-spacing:-0.5px; line-height:1;">
                {{ $snapshot['name'] ?? '' }}
            </div>
        @endif
        @if($showLogo && ($ls['content']['show_company_address'] ?? true))
        <div style="font-size:{{ $fs - 2 }}px; color:{{ $mid }}; margin-top:2mm; line-height:1.5;">
            {{ $snapshot['address'] ?? '' }}@if($snapshot['postal_code'] ?? null) &middot; {{ $snapshot['postal_code'] }} {{ $snapshot['city'] ?? '' }}@endif
            @if($snapshot['phone'] ?? null)<br>{{ $snapshot['phone'] }}@endif
            @if($snapshot['email'] ?? null)<br>{{ $snapshot['email'] }}@endif
        </div>
        @endif
    </td>
    <td style="padding:6mm 22mm 6mm 0; vertical-align:top; text-align:right;">
        <div style="font-size:{{ $fs + 7 }}px; font-weight:600; color:{{ $isCorrection ? '#dc2626' : $primary }}; text-transform:uppercase; letter-spacing:1px; line-height:1.1;">
            {{ $invoiceTypeLabel }}
        </div>
        <div style="font-size:{{ $fs - 1 }}px; color:{{ $mid }}; margin-top:1mm;">{{ $invoice->number }}</div>
        @if($issueDateFmt)<div style="font-size:{{ $fs - 1 }}px; font-weight:500; color:{{ $primary }}; margin-top:0.5mm;">{{ $issueDateFmt }}</div>@endif
    </td>
</tr>
</table>

{{-- CONTENT --}}
<div style="padding:0 22mm;">

{{-- Address zone + meta block --}}
<table style="width:100%; border-collapse:collapse; margin-top:5mm;">
<tr>
    <td style="width:85mm; vertical-align:top; padding-right:8mm;">
        @if($ls['content']['show_company_address'] ?? true)
        @php
            $retParts = array_filter([$snapshot['name'] ?? null, $snapshot['address'] ?? null, trim(($snapshot['postal_code'] ?? '').' '.($snapshot['city'] ?? '')) ?: null]);
            $retLine  = implode(' · ', $retParts);
        @endphp
        <div style="font-size:7pt; color:{{ $soft }}; border-bottom:0.25mm solid {{ $border }}; padding-bottom:1.5mm; margin-bottom:2mm; line-height:1;">
            {{ $retLine }}
        </div>
        @endif
        @if($customer)
        <div style="font-size:{{ $fs }}px; line-height:1.6;">
            @if($customer->company ?? null)<strong>{{ $customer->company }}</strong><br>@endif
            @if(($customer->salutation ?? null) || ($customer->name ?? null))<strong>{{ trim(($customer->salutation ?? '').' '.($customer->name ?? '')) }}</strong><br>@endif
            @if($customer->address ?? null){{ $customer->address }}<br>@endif
            @if(($customer->postal_code ?? null) || ($customer->city ?? null)){{ $customer->postal_code ?? '' }} {{ $customer->city ?? '' }}
            @if(($customer->country ?? null) && $customer->country !== 'DE')<br>{{ $customer->country }}@endif
            @endif
        </div>
        @endif
    </td>
    {{-- Meta block --}}
    <td style="vertical-align:top;">
        <table style="width:100%; border-collapse:collapse; border:1px solid {{ $border }};">
        <tr>
            <td colspan="2" style="background-color:{{ $dark }}; color:white; font-size:{{ $fs - 2 }}px; font-weight:600; text-transform:uppercase; letter-spacing:1px; padding:1.5mm 3mm;">
                Rechnungsdetails
            </td>
        </tr>
        <tr>
            <td style="padding:1.2mm 3mm; font-size:{{ $fs - 1 }}px; color:{{ $soft }}; border-bottom:0.15mm solid {{ $border }}; width:50%;">Rechnungsdatum</td>
            <td style="padding:1.2mm 3mm; font-size:{{ $fs - 1 }}px; font-weight:500; border-bottom:0.15mm solid {{ $border }};">{{ $issueDateFmt ?? '–' }}</td>
        </tr>
        <tr>
            <td style="padding:1.2mm 3mm; font-size:{{ $fs - 1 }}px; color:{{ $soft }}; border-bottom:0.15mm solid {{ $border }};">Lieferdatum</td>
            <td style="padding:1.2mm 3mm; font-size:{{ $fs - 1 }}px; font-weight:500; border-bottom:0.15mm solid {{ $border }};">{{ $issueDateFmt ?? '–' }}</td>
        </tr>
        <tr>
            <td style="padding:1.2mm 3mm; font-size:{{ $fs - 1 }}px; color:{{ $soft }}; border-bottom:0.15mm solid {{ $border }};">Kundennr.</td>
            <td style="padding:1.2mm 3mm; font-size:{{ $fs - 1 }}px; font-weight:500; border-bottom:0.15mm solid {{ $border }};">{{ isset($customer->customer_number) ? $customer->customer_number : '–' }}</td>
        </tr>
        @if($hasPeriod)
        <tr>
            <td style="padding:1.2mm 3mm; font-size:{{ $fs - 1 }}px; color:{{ $soft }}; border-bottom:0.15mm solid {{ $border }};">Leistungszeitraum</td>
            <td style="padding:1.2mm 3mm; font-size:{{ $fs - 1 }}px; font-weight:500; border-bottom:0.15mm solid {{ $border }};">{{ $periodStr }}</td>
        </tr>
        @endif
        <tr>
            <td style="padding:1.2mm 3mm; font-size:{{ $fs - 1 }}px; color:{{ $soft }};">Zahlungsziel</td>
            <td style="padding:1.2mm 3mm; font-size:{{ $fs - 1 }}px; font-weight:500;">{{ $dueDateFmt ?? '–' }}</td>
        </tr>
        </table>
    </td>
</tr>
</table>

{{-- Subject --}}
@if($ls['content']['show_subject'] ?? true)
<div style="margin-top:8mm;">
    <div style="font-size:{{ $fs + 2 }}px; font-weight:600; color:{{ $primary }};">
        {{ $invoiceTypeLabel }} Nr. {{ $invoice->number }}{{ ($invoice->title ?? null) ? ' – '.$invoice->title : '' }}
    </div>
    @if($hasPeriod)
    <div style="font-size:{{ $fs - 2 }}px; color:{{ $mid }}; margin-top:1mm;">Leistungszeitraum: {{ $periodStr }}</div>
    @endif
</div>
@endif

{{-- Salutation --}}
@if($ls['content']['show_salutation'] ?? true)
<div style="margin-top:5mm; font-size:{{ $fs }}px; line-height:1.7;">
    @if($invoice->salutation ?? null)
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
<table style="width:100%; border-collapse:collapse; margin-top:7mm;">
<tr>
    <td style="width:50%; padding-right:2mm; vertical-align:top;">
        <div style="padding:3mm 4mm; border:1px solid {{ $border }};">
            <div style="font-size:{{ $fs + 1 }}px; font-weight:600; color:{{ $primary }}; border-bottom:0.5mm solid {{ $primary }}; padding-bottom:1.5mm; margin-bottom:2mm;">Bankverbindung</div>
            @if($snapshot['name'] ?? null)<div style="font-size:{{ $fs - 1 }}px; line-height:1.6;"><span style="color:{{ $soft }};">Kontoinhaber: </span><strong>{{ $snapshot['name'] }}</strong></div>@endif
            @if($bankIban)<div style="font-size:{{ $fs - 1 }}px; line-height:1.6;"><span style="color:{{ $soft }};">IBAN: </span><strong>{{ $bankIban }}</strong></div>@endif
            @if($bankBic)<div style="font-size:{{ $fs - 1 }}px; line-height:1.6;"><span style="color:{{ $soft }};">BIC: </span><strong>{{ $bankBic }}</strong></div>@endif
            @if($bankName)<div style="font-size:{{ $fs - 1 }}px; line-height:1.6;"><span style="color:{{ $soft }};">Bank: </span><strong>{{ $bankName }}</strong></div>@endif
            <div style="font-size:{{ $fs - 1 }}px; line-height:1.6; margin-top:1.5mm;"><span style="color:{{ $soft }};">Verwendungszweck: </span><strong>{{ $invoice->number }}</strong></div>
        </div>
    </td>
    <td style="width:50%; padding-left:2mm; vertical-align:top;">
        <div style="padding:3mm 4mm; border:1px solid {{ $border }};">
            <div style="font-size:{{ $fs + 1 }}px; font-weight:600; color:{{ $primary }}; border-bottom:0.5mm solid {{ $primary }}; padding-bottom:1.5mm; margin-bottom:2mm;">Zahlungsbedingungen</div>
            <div style="font-size:{{ $fs - 1 }}px; line-height:1.6;">
                @include('pdf.invoice-partials.payment-terms')
            </div>
        </div>
    </td>
</tr>
</table>

{{-- Closing --}}
@if($ls['content']['show_closing'] ?? true)
<div style="margin-top:7mm; font-size:{{ $fs }}px; line-height:1.75;">
    @if($invoice->closing ?? null)
        {!! nl2br(e($invoice->closing)) !!}
    @else
        Wir danken Ihnen für das entgegengebrachte Vertrauen und stehen für Rückfragen jederzeit zur Verfügung.<br><br>
        Mit freundlichen Grüßen
    @endif
    @if($ls['content']['show_signature'] ?? true)
    <div style="margin-top:4mm; font-weight:600; font-size:{{ $fs + 1 }}px;">
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
