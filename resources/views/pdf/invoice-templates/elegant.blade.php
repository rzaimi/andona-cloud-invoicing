{{-- Elegant: Dark forest-green header with gold accents (from invoice-elegant.html) --}}
@php
    $ls      = $layoutSettings;
    $primary = $ls['colors']['primary']   ?? '#0d4a3a';
    $g2      = '#166d52';
    $gold    = '#c8a84b';
    $pale    = '#f0fdf7';
    $ink     = $ls['colors']['text']      ?? '#0f2d23';
    $mid     = $ls['colors']['secondary'] ?? '#2d6a53';
    $soft    = $ls['colors']['secondary'] ?? '#6aae8f';
    $border  = '#c6e8d8';
    $bg      = $ls['colors']['accent']    ?? '#f7fdf9';
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
    $tableHeaderStyle     = "border-top:0.5mm solid {$primary}; border-bottom:0.3mm solid {$g2};";
    $altRowBg             = $pale;
    $cellPadding          = '7px 8px';
    $showRowNumber        = $ls['content']['show_row_number'] ?? false;
    $totalRowBg           = $primary;
    $totalRowTextColor    = $gold;
    $tableWidth           = '288px';
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
    $alignL = 'left';
    $alignC = 'center';
    $alignR = 'right';
@endphp
<div style="background-color:{{ $primary }}; padding:7mm 22mm;">
<table style="width:100%; border-collapse:collapse;">
<tr>
    <td style="width:40%; vertical-align:middle; text-align:{{ $alignL }};">{!! $colL !!}</td>
    <td style="width:20%; vertical-align:middle; text-align:{{ $alignC }};">{!! $colC !!}</td>
    <td style="width:40%; vertical-align:middle; text-align:{{ $alignR }};">{!! $colR !!}</td>
</tr>
</table>
</div>

{{-- Gold rule --}}
<div style="height:0.5mm; background-color:{{ $gold }};"></div>

{{-- CONTENT --}}
<div style="padding:0 22mm;">

{{-- Address zone + info rows --}}
<table style="width:100%; border-collapse:collapse; margin-top:5.5mm;">
<tr>
    <td style="width:86mm; vertical-align:top; padding-right:6mm;">
        @if($ls['content']['show_company_address'] ?? true)
        @php
            $retParts = array_filter([$snapshot['display_name'] ?? $snapshot['name'] ?? null, $snapshot['address'] ?? null, trim(($snapshot['postal_code'] ?? '').' '.($snapshot['city'] ?? '')) ?: null]);
            $retLine  = implode(' · ', $retParts);
        @endphp
        <div style="font-size:7pt; color:{{ $soft }}; border-bottom:0.2mm solid {{ $border }}; padding-bottom:1.5mm; margin-bottom:2mm; font-weight:300; letter-spacing:0.3px; line-height:1;">
            {{ $retLine }}
        </div>
        @endif
        @if($customer)
        <div style="font-size:{{ $fs }}px; line-height:1.6; font-weight:300;">
            @if($customer->name ?? null)<strong style="font-weight:500;">{{ $customer->name }}</strong><br>@endif
            @if($customer->contact_person ?? null){{ $customer->contact_person }}<br>@endif
            @if($customer->address ?? null){{ $customer->address }}<br>@endif
            @if(($customer->postal_code ?? null) || ($customer->city ?? null)){{ $customer->postal_code ?? '' }} {{ $customer->city ?? '' }}
            @if(($customer->country ?? null) && $customer->country !== 'DE')<br>{{ $customer->country }}@endif
            @endif
        </div>
        @endif
    </td>
    {{-- Titled info rows --}}
    <td style="width:56mm; vertical-align:top;">
        <div style="font-size:7pt; text-transform:uppercase; letter-spacing:1.5px; color:{{ $soft }}; font-weight:500; border-bottom:0.3mm solid {{ $primary }}; padding-bottom:1mm; margin-bottom:2mm;">
            Rechnungsdetails
        </div>
        @include('pdf.invoice-partials.details', [
            'detailsLabelColor'  => $soft,
            'detailsBorderColor' => $border,
            'detailsPad'         => '0.5mm 0',
            'detailsFontSize'    => $fs - 1,
        ])
    </td>
</tr>
</table>

{{-- Subject --}}
@if($ls['content']['show_subject'] ?? true)
<div style="margin-top:8mm; padding-bottom:3mm; border-bottom:0.3mm solid {{ $primary }};">
    <div style="font-size:{{ $fs + 5 }}px; font-weight:500; color:{{ $primary }}; letter-spacing:0.3px;">
        {{ $invoiceTypeLabel }} {{ $invoice->number }}{{ ($invoice->title ?? null) ? ' – '.$invoice->title : '' }}
    </div>
</div>
@endif

{{-- Salutation --}}
@if($ls['content']['show_salutation'] ?? true)
<div style="margin-top:5mm; font-size:{{ $fs }}px; line-height:1.75; font-weight:300;">
    @if($invoice->salutation ?? null)
        {!! nl2br(e($invoice->salutation)) !!}
    @else
        Sehr geehrte Damen und Herren,<br><br>
        für die erbrachten Leistungen erlauben wir uns, folgendes Honorar zu berechnen.
    @endif
</div>
@endif

{{-- Items --}}
@include('pdf.invoice-partials.items-table')

{{-- Totals --}}
@include('pdf.invoice-partials.totals')

{{-- Payment boxes --}}
<table style="width:100%; border-collapse:collapse; margin-top:7mm; page-break-inside:avoid;">
<tr>
    <td style="width:50%; padding-right:2.5mm; vertical-align:top;">
        <table style="width:100%; border-collapse:collapse; border:1px solid {{ $border }};">
        <tr><td style="background-color:{{ $primary }}; color:white; padding:2mm 3mm; font-size:{{ $fs - 1 }}px; font-weight:500; letter-spacing:0.5px;">Bankverbindung</td></tr>
        <tr><td style="padding:3mm; background:{{ $pale }}; font-size:{{ $fs - 1 }}px; line-height:1.7; font-weight:300;">
            @if($snapshot['name'] ?? null)<div><span style="color:{{ $soft }};">Kontoinhaber: </span><strong style="font-weight:500; color:{{ $ink }};">{{ $snapshot['display_name'] ?? $snapshot['name'] }}</strong></div>@endif
            @if($bankIban)<div><span style="color:{{ $soft }};">IBAN: </span><strong style="font-weight:500; color:{{ $ink }};">{{ $bankIban }}</strong></div>@endif
            @if($bankBic)<div><span style="color:{{ $soft }};">BIC: </span><strong style="font-weight:500; color:{{ $ink }};">{{ $bankBic }}</strong></div>@endif
            @if($bankName)<div><span style="color:{{ $soft }};">Bank: </span><strong style="font-weight:500; color:{{ $ink }};">{{ $bankName }}</strong></div>@endif
            <div style="margin-top:1.5mm;"><span style="color:{{ $soft }};">Verwendungszweck: </span><strong style="font-weight:500; color:{{ $ink }};">{{ $invoice->number }}</strong></div>
        </td></tr>
        </table>
    </td>
    <td style="width:50%; padding-left:2.5mm; vertical-align:top;">
        <table style="width:100%; border-collapse:collapse; border:1px solid {{ $border }};">
        <tr><td style="background-color:{{ $primary }}; color:white; padding:2mm 3mm; font-size:{{ $fs - 1 }}px; font-weight:500; letter-spacing:0.5px;">Zahlungsbedingungen</td></tr>
        <tr><td style="padding:3mm; background:{{ $pale }}; font-size:{{ $fs - 1 }}px; line-height:1.7; font-weight:300;">
            @include('pdf.invoice-partials.payment-terms')
        </td></tr>
        </table>
    </td>
</tr>
</table>

{{-- Closing --}}
@if($ls['content']['show_closing'] ?? true)
<div style="margin-top:7mm; font-size:{{ $fs }}px; line-height:1.8; font-weight:300; page-break-inside:avoid;">
    @if($invoice->closing ?? null)
        {!! nl2br(e($invoice->closing)) !!}
    @else
        Wir danken Ihnen für das entgegengebrachte Vertrauen und freuen uns auf die weitere Zusammenarbeit.<br><br>
        Mit freundlichen Grüßen
    @endif
    @if($ls['content']['show_signature'] ?? true)
    <div style="margin-top:4mm; font-size:{{ $fs + 2 }}px; font-weight:500; color:{{ $primary }};">
        {{ $snapshot['name'] ?? '' }}
    </div>
    @endif
</div>
@endif

</div>{{-- /content --}}
@include('pdf.invoice-partials.footer')
</div>
