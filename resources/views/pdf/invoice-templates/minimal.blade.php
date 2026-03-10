{{-- Minimal: Thin top rule, bare typography, plain meta rows (from invoice-minimal.html) --}}
@php
    $ls      = $layoutSettings;
    $primary = $ls['colors']['primary'] ?? '#111111';
    $mid     = '#555555';
    $soft    = '#999999';
    $border  = '#e0e0e0';
    $bg      = '#fafafa';
    $white   = '#ffffff';
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

    // Items table: no header bg, only rules
    $tableHeaderBg        = null;
    $tableHeaderTextColor = $mid;
    $tableHeaderStyle     = "border-top:0.5mm solid {$primary}; border-bottom:0.2mm solid {$primary};";
    $altRowBg             = null;
    $cellPadding          = '6px 5px';
    $showRowNumber        = $ls['content']['show_row_number'] ?? false;
    $totalRowBg           = $primary;
    $totalRowTextColor    = '#ffffff';
    $tableWidth           = '280px';
@endphp

<div class="container" style="padding:0; font-family:{{ $ls['fonts']['body'] ?? 'DejaVu Sans' }},sans-serif; font-size:{{ $fs }}px; color:{{ $primary }};">

{{-- Thin top rule --}}
<div style="height:0.5mm; background-color:{{ $primary }}; width:100%;"></div>

{{-- HEADER: logo position-aware, minimal style --}}
@php
    $logoPos = $ls['branding']['logo_position'] ?? 'top-left';

    $logoCell = $showLogo
        ? '<img src="'.e($logoSrc).'" alt="Logo" style="max-height:14mm; max-width:48mm; display:block;">'
          . (($ls['content']['show_company_address'] ?? true) ? '<div style="font-size:'.($fs-2).'px; color:'.$soft.'; margin-top:1mm; font-weight:300;">'.e($snapshot['address'] ?? '').($snapshot['postal_code'] ?? null ? ' &middot; '.e($snapshot['postal_code']).' '.e($snapshot['city'] ?? '') : '').'</div>' : '')
        : '<div style="font-size:'.($fs+8).'px; color:'.$primary.'; letter-spacing:-0.5px; line-height:1;">'.e($snapshot['name'] ?? '').'</div>';

    $docCell = '<div style="text-align:right;">'
        . '<div style="font-size:'.($fs-1).'px; font-weight:300; color:'.$soft.'; text-transform:uppercase; letter-spacing:3px;">'.e($isCorrection ? 'STORNORECHNUNG' : strtoupper($invoiceTypeLabel)).'</div>'
        . '<div style="font-size:'.($fs+1).'px; font-weight:600; color:'.$primary.'; margin-top:1mm; letter-spacing:-0.3px;">'.e($invoice->number).'</div>'
        . '</div>';

    [$colL, $colC, $colR] = match($logoPos) {
        'top-center' => ['',        $logoCell, $docCell],
        'top-right'  => [$docCell,  '',        $logoCell],
        default      => [$logoCell, '',        $docCell],
    };
@endphp
<table style="width:100%; border-collapse:collapse; border-bottom:0.2mm solid {{ $border }};">
<tr>
    <td style="padding:8mm 0 8mm 22mm; vertical-align:bottom; width:40%;">{!! $colL !!}</td>
    <td style="padding:8mm 0; vertical-align:bottom; text-align:center; width:20%;">{!! $colC !!}</td>
    <td style="padding:8mm 22mm 8mm 0; vertical-align:bottom; width:40%;">{!! $colR !!}</td>
</tr>
</table>

{{-- CONTENT --}}
<div style="padding:0 22mm;">

{{-- Address zone + meta list --}}
<table style="width:100%; border-collapse:collapse; margin-top:6mm;">
<tr>
    <td style="width:88mm; vertical-align:top; padding-right:6mm;">
        @if($ls['content']['show_company_address'] ?? true)
        @php
            $retParts = array_filter([$snapshot['name'] ?? null, $snapshot['address'] ?? null, trim(($snapshot['postal_code'] ?? '').' '.($snapshot['city'] ?? '')) ?: null]);
            $retLine  = implode(' · ', $retParts);
        @endphp
        <div style="font-size:7pt; color:{{ $soft }}; border-bottom:0.2mm solid {{ $border }}; padding-bottom:1.5mm; margin-bottom:2mm; line-height:1;">
            {{ $retLine }}
        </div>
        @endif
        @if($customer)
        <div style="font-size:{{ $fs }}px; line-height:1.6;">
            @if($customer->name ?? null)<strong style="font-weight:500;">{{ $customer->name }}</strong><br>@endif
            @if($customer->contact_person ?? null){{ $customer->contact_person }}<br>@endif
            @if($customer->address ?? null){{ $customer->address }}<br>@endif
            @if(($customer->postal_code ?? null) || ($customer->city ?? null)){{ $customer->postal_code ?? '' }} {{ $customer->city ?? '' }}
            @if(($customer->country ?? null) && $customer->country !== 'DE')<br>{{ $customer->country }}@endif
            @endif
        </div>
        @endif
    </td>
    {{-- Plain meta list --}}
    <td style="width:58mm; vertical-align:top;">
        <table style="width:100%; border-collapse:collapse; font-size:{{ $fs - 1 }}px;">
        <tr>
            <td style="padding:1.2mm 0; border-bottom:0.15mm solid {{ $border }}; color:{{ $soft }}; font-weight:300;">Rechnungsdatum</td>
            <td style="padding:1.2mm 0; border-bottom:0.15mm solid {{ $border }}; font-weight:500; text-align:right;">{{ $issueDateFmt ?? '–' }}</td>
        </tr>
        <tr>
            <td style="padding:1.2mm 0; border-bottom:0.15mm solid {{ $border }}; color:{{ $soft }}; font-weight:300;">Lieferdatum</td>
            <td style="padding:1.2mm 0; border-bottom:0.15mm solid {{ $border }}; font-weight:500; text-align:right;">{{ $issueDateFmt ?? '–' }}</td>
        </tr>
        <tr>
            <td style="padding:1.2mm 0; border-bottom:0.15mm solid {{ $border }}; color:{{ $soft }}; font-weight:300;">Kundennr.</td>
            <td style="padding:1.2mm 0; border-bottom:0.15mm solid {{ $border }}; font-weight:500; text-align:right;">{{ isset($customer->number) ? $customer->number : '–' }}</td>
        </tr>
        @if($hasPeriod)
        <tr>
            <td style="padding:1.2mm 0; border-bottom:0.15mm solid {{ $border }}; color:{{ $soft }}; font-weight:300;">Leistungszeitraum</td>
            <td style="padding:1.2mm 0; border-bottom:0.15mm solid {{ $border }}; font-weight:500; text-align:right;">{{ $periodStr }}</td>
        </tr>
        @endif
        <tr>
            <td style="padding:1.2mm 0; color:{{ $soft }}; font-weight:300;">Zahlungsziel</td>
            <td style="padding:1.2mm 0; font-weight:500; text-align:right;">{{ $dueDateFmt ?? '–' }}</td>
        </tr>
        </table>
    </td>
</tr>
</table>

{{-- Subject --}}
@if($ls['content']['show_subject'] ?? true)
<div style="margin-top:9mm;">
    <div style="font-size:{{ $fs + 2 }}px; color:{{ $primary }}; font-style:italic;">
        {{ $invoiceTypeLabel }} {{ $invoice->number }}{{ ($invoice->title ?? null) ? ' – '.$invoice->title : '' }}
    </div>
    @if($hasPeriod)
    <div style="font-size:{{ $fs - 2 }}px; color:{{ $soft }}; margin-top:1mm; font-weight:300;">Leistungszeitraum: {{ $periodStr }}</div>
    @endif
</div>
@endif

{{-- Salutation --}}
@if($ls['content']['show_salutation'] ?? true)
<div style="margin-top:5mm; font-size:{{ $fs }}px; line-height:1.7; font-weight:300;">
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

{{-- Payment: two sections side by side --}}
<table style="width:100%; border-collapse:collapse; margin-top:8mm;">
<tr>
    <td style="width:50%; padding-right:3mm; vertical-align:top; border-top:0.5mm solid {{ $primary }}; padding-top:3mm;">
        <div style="font-size:{{ $fs - 2 }}px; text-transform:uppercase; letter-spacing:1.2px; color:{{ $soft }}; margin-bottom:2mm; font-weight:500;">Bankverbindung</div>
        @if($bankIban)<div style="font-size:{{ $fs - 1 }}px; line-height:1.6;"><span style="color:{{ $soft }}; font-weight:300;">IBAN </span><strong style="font-weight:500;">{{ $bankIban }}</strong></div>@endif
        @if($bankBic)<div style="font-size:{{ $fs - 1 }}px; line-height:1.6;"><span style="color:{{ $soft }}; font-weight:300;">BIC </span><strong style="font-weight:500;">{{ $bankBic }}</strong></div>@endif
        @if($bankName)<div style="font-size:{{ $fs - 1 }}px; line-height:1.6;"><span style="color:{{ $soft }}; font-weight:300;">Bank </span><strong style="font-weight:500;">{{ $bankName }}</strong></div>@endif
        <div style="font-size:{{ $fs - 1 }}px; line-height:1.6; margin-top:1mm;"><span style="color:{{ $soft }}; font-weight:300;">Verwendungszweck </span><strong style="font-weight:500;">{{ $invoice->number }}</strong></div>
    </td>
    <td style="width:50%; padding-left:3mm; vertical-align:top; border-top:0.2mm solid {{ $border }}; padding-top:3mm;">
        <div style="font-size:{{ $fs - 2 }}px; text-transform:uppercase; letter-spacing:1.2px; color:{{ $soft }}; margin-bottom:2mm; font-weight:500;">Zahlungsbedingungen</div>
        <div style="font-size:{{ $fs - 1 }}px; font-weight:300; line-height:1.6;">
            @include('pdf.invoice-partials.payment-terms')
        </div>
    </td>
</tr>
</table>

{{-- Closing --}}
@if($ls['content']['show_closing'] ?? true)
<div style="margin-top:8mm; font-size:{{ $fs }}px; line-height:1.7; font-weight:300;">
    @if($invoice->closing ?? null)
        {!! nl2br(e($invoice->closing)) !!}
    @else
        Für Rückfragen stehe ich Ihnen jederzeit gerne zur Verfügung.<br><br>
        Mit freundlichen Grüßen
    @endif
    @if($ls['content']['show_signature'] ?? true)
    <div style="margin-top:4mm; font-weight:500; font-size:{{ $fs }}px;">
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
