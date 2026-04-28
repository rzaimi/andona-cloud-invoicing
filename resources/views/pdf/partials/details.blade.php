{{--
    Document details panel — key/value table. Works for invoices AND offers.
    Required scope: $invoice OR $offer, $dateFormat, $bodyFontSize, $layoutSettings
    Optional variables (set by the calling template):
        $detailsBg          – background color for the table (default transparent)
        $detailsPad         – cell padding (default '4px 8px')
        $detailsLabelColor  – label text color (default #6b7280)
        $detailsBorderColor – row divider color (default #e5e7eb)
        $detailsTableStyle  – extra inline CSS on <table>
        $detailsFontSize    – override font size
--}}
@php
    // Coerce to a generic $doc so the partial works for both invoices and
    // offers. Invoice-only rows (Art, Skonto bis) are gated by $docKind.
    $doc       = $doc     ?? ($invoice ?? $offer);
    $docKind   = $docKind ?? (isset($invoice) ? 'invoice' : 'offer');
    $numberLbl = $docKind === 'offer' ? 'Angebotsnr.' : 'Rechnungsnr.';
    $dueLbl    = $docKind === 'offer' ? 'Gültig bis'  : 'Fälligkeit';
    $dueValue  = $docKind === 'offer' ? ($doc->valid_until ?? null) : ($doc->due_date ?? null);

    $isCorrection = $docKind === 'invoice' && isset($doc->is_correction) && (bool)$doc->is_correction;
    $invoiceType  = $doc->invoice_type ?? 'standard';
    $showType     = $docKind === 'invoice' && !$isCorrection && $invoiceType !== 'standard';
    $skontoAmount = (float)($doc->skonto_amount ?? 0);
    $hasSkonto    = $docKind === 'invoice' && !empty($doc->skonto_percent) && !empty($doc->skonto_days) && $skontoAmount > 0;

    $dtBg         = $detailsBg         ?? 'transparent';
    $dtPad        = $detailsPad        ?? '2px 6px';
    $dtLC         = $detailsLabelColor ?? '#6b7280';
    $dtBC         = $detailsBorderColor ?? '#e5e7eb';
    $dtFS         = $detailsFontSize   ?? $bodyFontSize;
    $dtTSty       = $detailsTableStyle  ?? '';
    $skontoColor  = $layoutSettings['colors']['skonto'] ?? '#16a34a';
@endphp
<table style="width:100%; border-collapse:collapse; font-size:{{ $dtFS }}px; background:{{ $dtBg }}; {{ $dtTSty }}">
    <tr>
        <td style="padding:{{ $dtPad }}; color:{{ $dtLC }}; font-size:{{ $dtFS - 1 }}px; border-bottom:1px solid {{ $dtBC }}; white-space:nowrap; width:46%;">{{ $numberLbl }}</td>
        <td style="padding:{{ $dtPad }}; font-weight:700; border-bottom:1px solid {{ $dtBC }};">{{ $doc->number }}</td>
    </tr>
    @if($showType)
    <tr>
        <td style="padding:{{ $dtPad }}; color:{{ $dtLC }}; font-size:{{ $dtFS - 1 }}px; border-bottom:1px solid {{ $dtBC }};">Art</td>
        <td style="padding:{{ $dtPad }}; font-weight:600; color:{{ $layoutSettings['colors']['primary'] ?? '#3b82f6' }}; border-bottom:1px solid {{ $dtBC }};">{{ getReadableInvoiceType($invoiceType, $doc->sequence_number ?? null) }}</td>
    </tr>
    @endif
    <tr>
        <td style="padding:{{ $dtPad }}; color:{{ $dtLC }}; font-size:{{ $dtFS - 1 }}px; border-bottom:1px solid {{ $dtBC }};">Datum</td>
        <td style="padding:{{ $dtPad }}; font-weight:600; border-bottom:1px solid {{ $dtBC }};">{{ formatInvoiceDate($doc->issue_date, $dateFormat ?? 'd.m.Y') }}</td>
    </tr>
    @if($dueValue)
    <tr>
        <td style="padding:{{ $dtPad }}; color:{{ $dtLC }}; font-size:{{ $dtFS - 1 }}px; border-bottom:1px solid {{ $dtBC }};">{{ $dueLbl }}</td>
        <td style="padding:{{ $dtPad }}; font-weight:600; border-bottom:1px solid {{ $dtBC }};">{{ formatInvoiceDate($dueValue, $dateFormat ?? 'd.m.Y') }}</td>
    </tr>
    @endif
    @if($hasSkonto)
    <tr>
        <td style="padding:{{ $dtPad }}; color:{{ $skontoColor }}; font-size:{{ $dtFS - 1 }}px; border-bottom:1px solid {{ $dtBC }};">Skonto bis</td>
        <td style="padding:{{ $dtPad }}; font-weight:600; color:{{ $skontoColor }}; border-bottom:1px solid {{ $dtBC }};">{{ formatInvoiceDate($doc->skonto_due_date, $dateFormat ?? 'd.m.Y') }}</td>
    </tr>
    @endif
    @if(!empty($doc->customer->number))
    <tr>
        <td style="padding:{{ $dtPad }}; color:{{ $dtLC }}; font-size:{{ $dtFS - 1 }}px; border-bottom:1px solid {{ $dtBC }};">Kundennr.</td>
        <td style="padding:{{ $dtPad }}; font-weight:600; border-bottom:1px solid {{ $dtBC }};">{{ $doc->customer->number }}</td>
    </tr>
    @endif
    @if(!empty($doc->service_date))
    <tr>
        <td style="padding:{{ $dtPad }}; color:{{ $dtLC }}; font-size:{{ $dtFS - 1 }}px; border-bottom:1px solid {{ $dtBC }};">Leistungsdatum</td>
        <td style="padding:{{ $dtPad }}; font-weight:600; border-bottom:1px solid {{ $dtBC }};">{{ formatInvoiceDate($doc->service_date, $dateFormat ?? 'd.m.Y') }}</td>
    </tr>
    @elseif(!empty($doc->service_period_start) && !empty($doc->service_period_end))
    <tr>
        <td style="padding:{{ $dtPad }}; color:{{ $dtLC }}; font-size:{{ $dtFS - 1 }}px; border-bottom:1px solid {{ $dtBC }};">Leistungszeitraum</td>
        <td style="padding:{{ $dtPad }}; font-weight:600; border-bottom:1px solid {{ $dtBC }};">{{ formatInvoiceDate($doc->service_period_start, $dateFormat ?? 'd.m.Y') }}–{{ formatInvoiceDate($doc->service_period_end, $dateFormat ?? 'd.m.Y') }}</td>
    </tr>
    @endif
    @if(!empty($doc->bauvorhaben) && ($layoutSettings['content']['show_bauvorhaben'] ?? true))
    <tr>
        <td style="padding:{{ $dtPad }}; color:{{ $dtLC }}; font-size:{{ $dtFS - 1 }}px; border-bottom:1px solid {{ $dtBC }};">BV</td>
        <td style="padding:{{ $dtPad }}; font-weight:700; color:{{ $layoutSettings['colors']['primary'] ?? '#3b82f6' }}; border-bottom:1px solid {{ $dtBC }};">{{ $doc->bauvorhaben }}</td>
    </tr>
    @endif
    @if(!empty($doc->auftragsnummer) && ($layoutSettings['content']['show_auftragsnummer'] ?? true))
    <tr>
        <td style="padding:{{ $dtPad }}; color:{{ $dtLC }}; font-size:{{ $dtFS - 1 }}px;">Auftragsnr.</td>
        <td style="padding:{{ $dtPad }}; font-weight:600;">{{ $doc->auftragsnummer }}</td>
    </tr>
    @endif
</table>
