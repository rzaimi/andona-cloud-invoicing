{{-- Professional: Navy split header + 2×3 info grid (from invoice-professionell.html) --}}
@php
    $ls      = $layoutSettings;
    $primary = $ls['colors']['primary']   ?? '#0d2240';
    $bg      = $ls['colors']['accent']    ?? '#f7f9fc';
    $border  = '#e1e7ef';
    $ink     = $ls['colors']['text']      ?? '#1e293b';
    $mid     = $ls['colors']['secondary'] ?? '#64748b';
    $soft    = $ls['colors']['secondary'] ?? '#94a3b8';
    $lblue   = '#60a5fa';
    $fs      = $bodyFontSize;

    // Logo
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

    $isCorrection     = isset($invoice->is_correction) && (bool)$invoice->is_correction;
    $invoiceTypeLabel = $isCorrection
        ? 'STORNORECHNUNG'
        : strtoupper(getReadableInvoiceType($invoice->invoice_type ?? 'standard', $invoice->sequence_number ?? null));
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

    $tableHeaderBg        = $primary;
    $tableHeaderTextColor = '#ffffff';
    $altRowBg             = $bg;
    $cellPadding          = '7px 8px';
    $showRowNumber        = $ls['content']['show_row_number'] ?? false;
    $totalRowBg           = $primary;
    $totalRowTextColor    = '#ffffff';
    $tableWidth           = '290px';
@endphp

<div class="container" style="padding:0; font-family:{{ $ls['fonts']['body'] ?? 'DejaVu Sans' }},sans-serif; font-size:{{ $fs }}px; color:{{ $ink }};">

{{-- HEADER: logo --}}
@php
    $logoPos  = $ls['branding']['logo_position'] ?? 'top-left';
    $logoH    = match($ls['branding']['logo_size'] ?? 'medium') { 'small' => '12mm', 'large' => '28mm', default => '20mm' };
    $logoW    = match($ls['branding']['logo_size'] ?? 'medium') { 'small' => '42mm', 'large' => '84mm', default => '64mm' };
    $logoCell = $showLogo ? '<img src="'.e($logoSrc).'" alt="Logo" style="max-height:'.$logoH.'; max-width:'.$logoW.'; display:block;">' : '';
    [$colL, $colC, $colR] = match($logoPos) {
        'top-center' => ['', $logoCell, ''],
        'top-right'  => ['', '', $logoCell],
        default      => [$logoCell, '', ''],
    };
@endphp
<table style="width:100%; border-collapse:collapse; border-bottom:1px solid {{ $border }};">
<tr>
    <td style="padding:6mm 0 6mm 22mm; vertical-align:middle; width:40%;">{!! $colL !!}</td>
    <td style="padding:6mm 0; vertical-align:middle; text-align:center; width:20%;">{!! $colC !!}</td>
    <td style="padding:6mm 22mm 6mm 0; vertical-align:middle; width:40%; text-align:right;">{!! $colR !!}</td>
</tr>
</table>

{{-- CONTENT --}}
<div style="padding:0 18mm 0 22mm;">

{{-- Address zone + info grid --}}
<table style="width:100%; border-collapse:collapse; margin-top:5.5mm;">
<tr>
    <td style="width:85mm; vertical-align:top; padding-right:6mm;">
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
        <div style="font-size:{{ $fs }}px; line-height:1.55;">
            @if($customer->name ?? null)<strong>{{ $customer->name }}</strong><br>@endif
            @if($customer->contact_person ?? null){{ $customer->contact_person }}<br>@endif
            @if($customer->address ?? null){{ $customer->address }}<br>@endif
            @if(($customer->postal_code ?? null) || ($customer->city ?? null)){{ $customer->postal_code ?? '' }} {{ $customer->city ?? '' }}
            @if(($customer->country ?? null) && $customer->country !== 'DE')<br>{{ $customer->country }}@endif
            @endif
        </div>
        @endif
    </td>
    {{-- Info panel --}}
    <td style="vertical-align:top;">
        <div style="background:{{ $bg }}; border:1px solid {{ $border }}; padding:0 1mm;">
            @include('pdf.invoice-partials.details', [
                'detailsBg'          => $bg,
                'detailsLabelColor'  => $soft,
                'detailsBorderColor' => $border,
                'detailsPad'         => '1.5mm 2.5mm',
                'detailsFontSize'    => $fs - 1,
                'detailsTableStyle'  => 'border:none;',
            ])
        </div>
    </td>
</tr>
</table>

{{-- Subject bar --}}
@if($ls['content']['show_subject'] ?? true)
<div style="margin-top:7mm; padding:3mm 4mm; background-color:{{ $primary }};">
    <div style="font-size:{{ $fs + 3 }}px; font-weight:700; color:white; text-transform:uppercase; letter-spacing:0.5px;">
        {{ $invoiceTypeLabel }} {{ $invoice->number }}{{ ($invoice->title ?? null) ? ' – '.$invoice->title : '' }}
    </div>
</div>
@endif

{{-- Salutation --}}
@if($ls['content']['show_salutation'] ?? true)
<div style="margin-top:5mm; font-size:{{ $fs }}px; line-height:1.65;">
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
        <table style="width:100%; border-collapse:collapse; border:1px solid {{ $border }};">
        <tr><td style="background-color:{{ $primary }}; color:white; padding:2mm 3mm; font-size:{{ $fs - 1 }}px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">Zahlungsdetails</td></tr>
        <tr><td style="padding:3mm; background:{{ $bg }}; font-size:{{ $fs - 1 }}px; line-height:1.65;">
            @if($snapshot['name'] ?? null)<div><span style="color:{{ $soft }};">Kontoinhaber: </span><strong>{{ $snapshot['name'] }}</strong></div>@endif
            @if($bankIban)<div><span style="color:{{ $soft }};">IBAN: </span><strong>{{ $bankIban }}</strong></div>@endif
            @if($bankBic)<div><span style="color:{{ $soft }};">BIC: </span><strong>{{ $bankBic }}</strong></div>@endif
            @if($bankName)<div><span style="color:{{ $soft }};">Bank: </span><strong>{{ $bankName }}</strong></div>@endif
            <div style="margin-top:1mm;"><span style="color:{{ $soft }};">Verwendungszweck: </span><strong>{{ $invoice->number }}</strong></div>
        </td></tr>
        </table>
    </td>
    <td style="width:50%; padding-left:2mm; vertical-align:top;">
        <table style="width:100%; border-collapse:collapse; border:1px solid {{ $border }};">
        <tr><td style="background-color:{{ $primary }}; color:white; padding:2mm 3mm; font-size:{{ $fs - 1 }}px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">Konditionen</td></tr>
        <tr><td style="padding:3mm; background:{{ $bg }}; font-size:{{ $fs - 1 }}px; line-height:1.65;">
            @include('pdf.invoice-partials.payment-terms')
        </td></tr>
        </table>
    </td>
</tr>
</table>

{{-- Closing --}}
@if($ls['content']['show_closing'] ?? true)
<div style="margin-top:7mm; font-size:{{ $fs }}px; line-height:1.7;">
    @if($invoice->closing ?? null)
        {!! nl2br(e($invoice->closing)) !!}
    @else
        Wir danken Ihnen für das entgegengebrachte Vertrauen und stehen für Rückfragen jederzeit zur Verfügung.<br><br>
        Mit freundlichen Grüßen
    @endif
    @if($ls['content']['show_signature'] ?? true)
    <div style="margin-top:4mm; font-size:{{ $fs + 2 }}px; font-weight:700; color:{{ $primary }};">
        {{ $snapshot['name'] ?? '' }}
    </div>
    @endif
</div>
@endif

</div>{{-- /content --}}
@include('pdf.invoice-partials.footer')
</div>
