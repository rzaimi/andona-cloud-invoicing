{{--
    Invoice details panel — structured key/value table.
    Required scope: $invoice, $dateFormat, $bodyFontSize, $layoutSettings
    Optional variables (set by the calling template):
        $detailsBg          – background color for the table (default transparent)
        $detailsPad         – cell padding (default '4px 8px')
        $detailsLabelColor  – label text color (default #6b7280)
        $detailsBorderColor – row divider color (default #e5e7eb)
        $detailsTableStyle  – extra inline CSS on <table>
        $detailsFontSize    – override font size
--}}
@php
    $isCorrection = isset($invoice->is_correction) && (bool)$invoice->is_correction;
    $invoiceType  = $invoice->invoice_type ?? 'standard';
    $showType     = !$isCorrection && $invoiceType !== 'standard';
    $skontoAmount = (float)($invoice->skonto_amount ?? 0);
    $hasSkonto    = !empty($invoice->skonto_percent) && !empty($invoice->skonto_days) && $skontoAmount > 0;

    $dtBg   = $detailsBg         ?? 'transparent';
    $dtPad  = $detailsPad        ?? '4px 8px';
    $dtLC   = $detailsLabelColor ?? '#6b7280';
    $dtBC   = $detailsBorderColor ?? '#e5e7eb';
    $dtFS   = $detailsFontSize   ?? $bodyFontSize;
    $dtTSty = $detailsTableStyle  ?? '';
@endphp
<table style="width:100%; border-collapse:collapse; font-size:{{ $dtFS }}px; background:{{ $dtBg }}; {{ $dtTSty }}">
    <tr>
        <td style="padding:{{ $dtPad }}; color:{{ $dtLC }}; font-size:{{ $dtFS - 1 }}px; border-bottom:1px solid {{ $dtBC }}; white-space:nowrap; width:46%;">Rechnungsnr.</td>
        <td style="padding:{{ $dtPad }}; font-weight:700; border-bottom:1px solid {{ $dtBC }};">{{ $invoice->number }}</td>
    </tr>
    @if($showType)
    <tr>
        <td style="padding:{{ $dtPad }}; color:{{ $dtLC }}; font-size:{{ $dtFS - 1 }}px; border-bottom:1px solid {{ $dtBC }};">Art</td>
        <td style="padding:{{ $dtPad }}; font-weight:600; color:{{ $layoutSettings['colors']['primary'] ?? '#3b82f6' }}; border-bottom:1px solid {{ $dtBC }};">{{ getReadableInvoiceType($invoiceType, $invoice->sequence_number ?? null) }}</td>
    </tr>
    @endif
    <tr>
        <td style="padding:{{ $dtPad }}; color:{{ $dtLC }}; font-size:{{ $dtFS - 1 }}px; border-bottom:1px solid {{ $dtBC }};">Datum</td>
        <td style="padding:{{ $dtPad }}; font-weight:600; border-bottom:1px solid {{ $dtBC }};">{{ formatInvoiceDate($invoice->issue_date, $dateFormat ?? 'd.m.Y') }}</td>
    </tr>
    <tr>
        <td style="padding:{{ $dtPad }}; color:{{ $dtLC }}; font-size:{{ $dtFS - 1 }}px; border-bottom:1px solid {{ $dtBC }};">Fälligkeit</td>
        <td style="padding:{{ $dtPad }}; font-weight:600; border-bottom:1px solid {{ $dtBC }};">{{ formatInvoiceDate($invoice->due_date, $dateFormat ?? 'd.m.Y') }}</td>
    </tr>
    @if($hasSkonto)
    <tr>
        <td style="padding:{{ $dtPad }}; color:#16a34a; font-size:{{ $dtFS - 1 }}px; border-bottom:1px solid {{ $dtBC }};">Skonto bis</td>
        <td style="padding:{{ $dtPad }}; font-weight:600; color:#16a34a; border-bottom:1px solid {{ $dtBC }};">{{ formatInvoiceDate($invoice->skonto_due_date, $dateFormat ?? 'd.m.Y') }}</td>
    </tr>
    @endif
    @if(!empty($invoice->customer->number))
    <tr>
        <td style="padding:{{ $dtPad }}; color:{{ $dtLC }}; font-size:{{ $dtFS - 1 }}px; border-bottom:1px solid {{ $dtBC }};">Kundennr.</td>
        <td style="padding:{{ $dtPad }}; font-weight:600; border-bottom:1px solid {{ $dtBC }};">{{ $invoice->customer->number }}</td>
    </tr>
    @endif
    @if(!empty($invoice->service_date))
    <tr>
        <td style="padding:{{ $dtPad }}; color:{{ $dtLC }}; font-size:{{ $dtFS - 1 }}px; border-bottom:1px solid {{ $dtBC }};">Leistungsdatum</td>
        <td style="padding:{{ $dtPad }}; font-weight:600; border-bottom:1px solid {{ $dtBC }};">{{ formatInvoiceDate($invoice->service_date, $dateFormat ?? 'd.m.Y') }}</td>
    </tr>
    @elseif(!empty($invoice->service_period_start) && !empty($invoice->service_period_end))
    <tr>
        <td style="padding:{{ $dtPad }}; color:{{ $dtLC }}; font-size:{{ $dtFS - 1 }}px; border-bottom:1px solid {{ $dtBC }};">Leistungszeitraum</td>
        <td style="padding:{{ $dtPad }}; font-weight:600; border-bottom:1px solid {{ $dtBC }};">{{ formatInvoiceDate($invoice->service_period_start, $dateFormat ?? 'd.m.Y') }}–{{ formatInvoiceDate($invoice->service_period_end, $dateFormat ?? 'd.m.Y') }}</td>
    </tr>
    @endif
    @if(!empty($invoice->bauvorhaben) && ($layoutSettings['content']['show_bauvorhaben'] ?? true))
    <tr>
        <td style="padding:{{ $dtPad }}; color:{{ $dtLC }}; font-size:{{ $dtFS - 1 }}px;">BV</td>
        <td style="padding:{{ $dtPad }}; font-weight:700; color:{{ $layoutSettings['colors']['primary'] ?? '#3b82f6' }};">{{ $invoice->bauvorhaben }}</td>
    </tr>
    @endif
</table>
