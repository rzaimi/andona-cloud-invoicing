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
    $numberLbl = $docKind === 'offer' ? 'Angebotsnr.:' : 'Rechnungsnr.:';
    $dueLbl    = $docKind === 'offer' ? 'Gültig bis:'  : 'Fälligkeit:';
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
    $valueInk     = $layoutSettings['colors']['text'] ?? '#1f2937';
    // Fixed width, 30% narrower than the column the table used to fill.
    // Long values such as Bauvorhaben wrap inside the value column.
    $rule         = "border-bottom:1px solid {$dtBC};";
    $labelCell    = "padding:{$dtPad}; padding-right:2mm; color:{$dtLC}; font-size:{$dtFS}px; font-weight:400; white-space:nowrap; width:24mm; vertical-align:top; {$rule}";
    $valueCell    = "padding:{$dtPad}; padding-left:0; color:{$valueInk}; font-size:{$dtFS}px; font-weight:700; width:36mm; vertical-align:top; word-wrap:break-word; {$rule}";
@endphp
<table align="right" style="width:60mm; table-layout:fixed; margin-left:auto; border-collapse:collapse; font-size:{{ $dtFS }}px; background:{{ $dtBg }}; {{ $dtTSty }}">
    <tr>
        <td style="{{ $labelCell }}">{{ $numberLbl }}</td>
        <td style="{{ $valueCell }}">{{ $doc->number }}</td>
    </tr>
    @if($showType)
    <tr>
        <td style="{{ $labelCell }}">Art:</td>
        <td style="{{ $valueCell }}">{{ getReadableInvoiceType($invoiceType, $doc->sequence_number ?? null) }}</td>
    </tr>
    @endif
    <tr>
        <td style="{{ $labelCell }}">Datum:</td>
        <td style="{{ $valueCell }}">{{ formatInvoiceDate($doc->issue_date, $dateFormat ?? 'd.m.Y') }}</td>
    </tr>
    @if($dueValue)
    <tr>
        <td style="{{ $labelCell }}">{{ $dueLbl }}</td>
        <td style="{{ $valueCell }}">{{ formatInvoiceDate($dueValue, $dateFormat ?? 'd.m.Y') }}</td>
    </tr>
    @endif
    @if($hasSkonto)
    <tr>
        <td style="{{ $labelCell }} color:{{ $skontoColor }};">Skonto bis:</td>
        <td style="{{ $valueCell }} color:{{ $skontoColor }};">{{ formatInvoiceDate($doc->skonto_due_date, $dateFormat ?? 'd.m.Y') }}</td>
    </tr>
    @endif
    @if(!empty($doc->customer->number) && ($layoutSettings['content']['show_customer_number'] ?? true))
    <tr>
        <td style="{{ $labelCell }}">Kundennr.:</td>
        <td style="{{ $valueCell }}">{{ $doc->customer->number }}</td>
    </tr>
    @endif
    @if(!empty($doc->service_date))
    <tr>
        <td style="{{ $labelCell }}">Leistungsdatum:</td>
        <td style="{{ $valueCell }}">{{ formatInvoiceDate($doc->service_date, $dateFormat ?? 'd.m.Y') }}</td>
    </tr>
    @elseif(!empty($doc->service_period_start) && !empty($doc->service_period_end))
    <tr>
        <td style="{{ $labelCell }}">Leistungszeitraum:</td>
        <td style="{{ $valueCell }}">{{ formatInvoiceDate($doc->service_period_start, $dateFormat ?? 'd.m.Y') }}–{{ formatInvoiceDate($doc->service_period_end, $dateFormat ?? 'd.m.Y') }}</td>
    </tr>
    @endif
    @if(!empty($doc->bauvorhaben) && ($layoutSettings['content']['show_bauvorhaben'] ?? true))
    <tr>
        <td style="{{ $labelCell }}">BV:</td>
        <td style="{{ $valueCell }}">{{ $doc->bauvorhaben }}</td>
    </tr>
    @endif
    @if(!empty($doc->auftragsnummer) && ($layoutSettings['content']['show_auftragsnummer'] ?? true))
    <tr>
        <td style="{{ $labelCell }} border-bottom:none;">Auftragsnr.:</td>
        <td style="{{ $valueCell }} border-bottom:none;">{{ $doc->auftragsnummer }}</td>
    </tr>
    @endif
</table>
