{{-- Modern: Blue left sidebar + blue info band below header (from invoice-modern.html) --}}
@php
    $ls      = $layoutSettings;
    $primary = $ls['colors']['primary']   ?? '#2563eb';
    $dark    = $ls['colors']['text']      ?? '#0f172a';
    $mid     = $ls['colors']['secondary'] ?? '#475569';
    $light   = $ls['colors']['secondary'] ?? '#94a3b8';
    $bg      = $ls['colors']['accent']    ?? '#f8fafc';
    $border  = '#e2e8f0';
    $white   = '#ffffff';
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

    $tableHeaderBg        = $dark;
    $tableHeaderTextColor = '#ffffff';
    $altRowBg             = $bg;
    $cellPadding          = '7px 8px';
    $showRowNumber        = $ls['content']['show_row_number'] ?? false;
    $totalRowBg           = $primary;
    $totalRowTextColor    = '#ffffff';
    $tableWidth           = '290px';
@endphp

{{-- Full-page left blue stripe via fixed positioning --}}
<div style="position:fixed; left:0; top:0; width:6mm; height:297mm; background-color:{{ $primary }};"></div>

<div class="container" style="padding:0; font-family:{{ $ls['fonts']['body'] ?? 'DejaVu Sans' }},sans-serif; font-size:{{ $fs }}px; color:{{ $dark }}; margin-left:6mm;">

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
    <td style="padding:7mm 0 7mm 16mm; vertical-align:middle; width:40%;">{!! $colL !!}</td>
    <td style="padding:7mm 0; vertical-align:middle; text-align:center; width:20%;">{!! $colC !!}</td>
    <td style="padding:7mm 18mm 7mm 0; vertical-align:middle; width:40%;">{!! $colR !!}</td>
</tr>
</table>

{{-- Info band: blue strip with key metadata --}}
<table style="width:100%; border-collapse:collapse; background-color:{{ $primary }};">
<tr>
    <td style="padding:3mm 4mm 3mm 16mm; vertical-align:top;">
        <div style="font-size:6pt; text-transform:uppercase; letter-spacing:1.2px; color:rgba(255,255,255,0.55); margin-bottom:0.5mm;">Rechnungsnr.</div>
        <div style="font-size:{{ $fs - 1 }}px; color:white; font-weight:700;">{{ $invoice->number }}</div>
    </td>
    <td style="padding:3mm 4mm; vertical-align:top;">
        <div style="font-size:6pt; text-transform:uppercase; letter-spacing:1.2px; color:rgba(255,255,255,0.55); margin-bottom:0.5mm;">Datum</div>
        <div style="font-size:{{ $fs - 1 }}px; color:white; font-weight:700;">{{ $issueDateFmt ?? '–' }}</div>
    </td>
    <td style="padding:3mm 4mm; vertical-align:top;">
        <div style="font-size:6pt; text-transform:uppercase; letter-spacing:1.2px; color:rgba(255,255,255,0.55); margin-bottom:0.5mm;">Zahlungsziel</div>
        <div style="font-size:{{ $fs - 1 }}px; color:white; font-weight:700;">{{ $dueDateFmt ?? '–' }}</div>
    </td>
    <td style="padding:3mm 4mm; vertical-align:top;">
        <div style="font-size:6pt; text-transform:uppercase; letter-spacing:1.2px; color:rgba(255,255,255,0.55); margin-bottom:0.5mm;">Kundennr.</div>
        <div style="font-size:{{ $fs - 1 }}px; color:white; font-weight:700;">{{ isset($customer->number) ? $customer->number : '–' }}</div>
    </td>
    @if($hasPeriod)
    <td style="padding:3mm 16mm 3mm 4mm; vertical-align:top;">
        <div style="font-size:6pt; text-transform:uppercase; letter-spacing:1.2px; color:rgba(255,255,255,0.55); margin-bottom:0.5mm;">Leistungszeitraum</div>
        <div style="font-size:{{ $fs - 1 }}px; color:white; font-weight:700;">{{ $periodStr }}</div>
    </td>
    @else
    <td style="padding:3mm 16mm 3mm 4mm; vertical-align:top;"></td>
    @endif
</tr>
</table>

{{-- CONTENT --}}
<div style="padding:0 18mm 0 16mm;">

{{-- Address zone --}}
<table style="width:100%; border-collapse:collapse; margin-top:5mm;">
<tr>
    <td style="width:85mm; vertical-align:top; padding-right:6mm;">
        @if($ls['content']['show_company_address'] ?? true)
        @php
            $retParts = array_filter([$snapshot['display_name'] ?? $snapshot['name'] ?? null, $snapshot['address'] ?? null, trim(($snapshot['postal_code'] ?? '').' '.($snapshot['city'] ?? '')) ?: null]);
            $retLine  = implode(' · ', $retParts);
        @endphp
        <div style="font-size:7pt; color:{{ $light }}; border-bottom:0.25mm solid {{ $border }}; padding-bottom:1.5mm; margin-bottom:2mm; line-height:1;">
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
    {{-- Info rows (right of address) --}}
    <td style="vertical-align:top;">
        <div style="padding:3mm; background:{{ $bg }}; border:1px solid {{ $border }};">
            @include('pdf.invoice-partials.details', [
                'detailsLabelColor'  => $light,
                'detailsBorderColor' => $border,
                'detailsPad'         => '0.5mm 0',
                'detailsFontSize'    => $fs - 1,
                'detailsTableStyle'  => 'border:none;',
            ])
        </div>
    </td>
</tr>
</table>

{{-- Subject --}}
@if($ls['content']['show_subject'] ?? true)
<div style="margin-top:8mm; padding-bottom:3mm; border-bottom:2px solid {{ $primary }};">
    <div style="font-size:{{ $fs + 5 }}px; font-weight:700; color:{{ $dark }}; letter-spacing:-0.3px;">
        {{ $invoiceTypeLabel }} {{ $invoice->number }}{{ ($invoice->title ?? null) ? ' – '.$invoice->title : '' }}
    </div>
</div>
@endif

{{-- Salutation --}}
@if($ls['content']['show_salutation'] ?? true)
<div style="margin-top:4mm; font-size:{{ $fs }}px; line-height:1.65;">
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

{{-- Payment box --}}
<table style="width:100%; border-collapse:collapse; margin-top:7mm; border:1.5px solid {{ $primary }}; page-break-inside:avoid;">
<tr>
    <td colspan="2" style="background-color:{{ $primary }}; color:white; padding:2mm 4mm; font-size:{{ $fs - 2 }}px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">
        Zahlungsinformationen
    </td>
</tr>
<tr>
    <td style="width:50%; padding:3mm 4mm; background:{{ $bg }}; vertical-align:top; font-size:{{ $fs - 1 }}px;">
        <table style="width:100%; border-collapse:collapse;">
            <tr><td><div style="font-size:6pt; text-transform:uppercase; letter-spacing:0.8px; color:{{ $light }};">Kontoinhaber</div><div style="font-size:{{ $fs - 1 }}px; font-weight:700; color:{{ $dark }}; margin-top:0.5mm;">{{ $snapshot['display_name'] ?? $snapshot['name'] ?? '' }}</div></td></tr>
        </table>
        @if($bankIban)<div style="margin-top:2mm;"><div style="font-size:6pt; text-transform:uppercase; letter-spacing:0.8px; color:{{ $light }};">IBAN</div><div style="font-size:{{ $fs - 1 }}px; font-weight:700; color:{{ $dark }}; margin-top:0.5mm;">{{ $bankIban }}</div></div>@endif
        @if($bankBic)<div style="margin-top:2mm;"><div style="font-size:6pt; text-transform:uppercase; letter-spacing:0.8px; color:{{ $light }};">BIC</div><div style="font-size:{{ $fs - 1 }}px; font-weight:700; color:{{ $dark }}; margin-top:0.5mm;">{{ $bankBic }}</div></div>@endif
    </td>
    <td style="width:50%; padding:3mm 4mm; background:{{ $bg }}; vertical-align:top; border-left:1px solid {{ $border }};">
        <div style="font-size:6pt; text-transform:uppercase; letter-spacing:0.8px; color:{{ $light }};">Verwendungszweck</div>
        <div style="font-size:{{ $fs - 1 }}px; font-weight:700; color:{{ $dark }}; margin-top:0.5mm; margin-bottom:3mm;">{{ $invoice->number }}</div>
        <div style="font-size:{{ $fs - 1 }}px; color:{{ $mid }}; line-height:1.6;">
            @include('pdf.invoice-partials.payment-terms')
        </div>
    </td>
</tr>
</table>

{{-- Closing --}}
@if($ls['content']['show_closing'] ?? true)
<div style="margin-top:7mm; font-size:{{ $fs }}px; line-height:1.7; page-break-inside:avoid;">
    @if($invoice->closing ?? null)
        {!! nl2br(e($invoice->closing)) !!}
    @else
        Wir bitten um Überweisung des Rechnungsbetrags bis zum genannten Zahlungsziel.<br><br>
        Mit freundlichen Grüßen
    @endif
    @if($ls['content']['show_signature'] ?? true)
    <div style="margin-top:4mm; font-weight:700; font-size:{{ $fs + 1 }}px; color:{{ $dark }};">
        {{ $snapshot['name'] ?? '' }}
    </div>
    @endif
</div>
@endif

</div>{{-- /content --}}
@include('pdf.invoice-partials.footer')
</div>
