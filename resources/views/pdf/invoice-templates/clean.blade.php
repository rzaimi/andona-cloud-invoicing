{{-- Clean/Kreativ: Purple badge header + date strip + accent subject (from invoice-kreativ.html) --}}
@php
    $ls      = $layoutSettings;
    $primary = $ls['colors']['primary']   ?? '#7c3aed';
    $p2      = '#a855f7';
    $p4      = $ls['colors']['accent']    ?? '#f5f3ff';
    $ink     = $ls['colors']['text']      ?? '#1c1033';
    $mid     = $ls['colors']['secondary'] ?? '#6b21a8';
    $soft    = $ls['colors']['secondary'] ?? '#a78bfa';
    $border  = '#e9d5ff';
    $bg      = $ls['colors']['accent']    ?? '#faf5ff';
    $dark    = $ls['colors']['text']      ?? '#1c1033';
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
<table style="width:100%; border-collapse:collapse;">
<tr>
    <td style="padding:7mm 0 7mm 20mm; vertical-align:middle; width:40%;">{!! $colL !!}</td>
    <td style="padding:7mm 0; vertical-align:middle; text-align:center; width:20%;">{!! $colC !!}</td>
    <td style="padding:7mm 20mm 7mm 0; vertical-align:middle; width:40%;">{!! $colR !!}</td>
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
    {{-- Info rows right --}}
    <td style="width:54mm; vertical-align:top;">
        @include('pdf.invoice-partials.details', [
            'detailsLabelColor'  => $soft,
            'detailsBorderColor' => $border,
            'detailsPad'         => '0.5mm 0',
            'detailsFontSize'    => $fs - 1,
        ])
    </td>
</tr>
</table>

{{-- Subject with left accent bar --}}
@if($ls['content']['show_subject'] ?? true)
<table style="width:100%; border-collapse:collapse; margin-top:7mm;">
<tr>
    <td style="width:3mm; background-color:{{ $primary }}; vertical-align:top;"></td>
    <td style="padding:2mm 0 2mm 3mm; vertical-align:middle;">
        <div style="font-size:{{ $fs + 5 }}px; font-weight:700; color:{{ $dark }}; letter-spacing:-0.3px;">
            {{ $invoiceTypeLabel }} {{ $invoice->number }}{{ ($invoice->title ?? null) ? ' – '.$invoice->title : '' }}
        </div>
    </td>
</tr>
</table>
@endif

{{-- Salutation --}}
@if($ls['content']['show_salutation'] ?? true)
<div style="margin-top:5mm; font-size:{{ $fs }}px; line-height:1.65; font-weight:300;">
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
<table style="width:100%; border-collapse:collapse; margin-top:6mm; page-break-inside:avoid;">
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
<div style="margin-top:7mm; font-size:{{ $fs }}px; line-height:1.7; font-weight:300; page-break-inside:avoid;">
    @if($invoice->closing ?? null)
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
@include('pdf.invoice-partials.footer')
</div>
