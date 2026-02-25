{{--
    Invoice details panel — right-side metadata block.
    Required scope vars: $invoice, $dateFormat, $bodyFontSize, $layoutSettings
    Optional:           $detailsAlign  (default 'right')
                        $detailsStyle  (extra inline CSS for the wrapper div)
--}}
@php
    $isCorrection  = isset($invoice->is_correction) && (bool)$invoice->is_correction;
    $invoiceType   = $invoice->invoice_type ?? 'standard';
    $showType      = !$isCorrection && $invoiceType !== 'standard';
    $skontoAmount  = (float)($invoice->skonto_amount ?? 0);
    $hasSkonto     = !empty($invoice->skonto_percent) && !empty($invoice->skonto_days) && $skontoAmount > 0;
    $detailsAlign  = $detailsAlign ?? 'right';
    $detailsStyle  = $detailsStyle ?? '';
@endphp
<div style="font-size: {{ $bodyFontSize }}px; line-height: 1.8; text-align: {{ $detailsAlign }}; {{ $detailsStyle }}">
    <div style="margin-bottom: 1mm;">
        <strong>Rechnungsnummer:</strong> {{ $invoice->number }}
    </div>

    @if($showType)
        <div style="margin-bottom: 1mm; color: {{ $layoutSettings['colors']['primary'] ?? '#3b82f6' }}; font-weight: 600;">
            {{ getReadableInvoiceType($invoiceType, $invoice->sequence_number ?? null) }}
        </div>
    @endif

    <div style="margin-bottom: 1mm;">
        <strong>Datum:</strong> {{ formatInvoiceDate($invoice->issue_date, $dateFormat ?? 'd.m.Y') }}
    </div>

    @if(!empty($invoice->service_date))
        <div style="margin-bottom: 1mm;">
            <strong>Leistungsdatum:</strong> {{ formatInvoiceDate($invoice->service_date, $dateFormat ?? 'd.m.Y') }}
        </div>
    @elseif(!empty($invoice->service_period_start) && !empty($invoice->service_period_end))
        <div style="margin-bottom: 1mm;">
            <strong>Leistungszeitraum:</strong>
            {{ formatInvoiceDate($invoice->service_period_start, $dateFormat ?? 'd.m.Y') }}–{{ formatInvoiceDate($invoice->service_period_end, $dateFormat ?? 'd.m.Y') }}
        </div>
    @endif

    <div style="margin-bottom: 1mm;">
        <strong>Fälligkeitsdatum:</strong> {{ formatInvoiceDate($invoice->due_date, $dateFormat ?? 'd.m.Y') }}
    </div>

    @if($hasSkonto)
        <div style="margin-bottom: 1mm; color: #16a34a; font-weight: 600;">
            <strong>Skonto bis:</strong> {{ formatInvoiceDate($invoice->skonto_due_date, $dateFormat ?? 'd.m.Y') }}
        </div>
    @endif

    @if(!empty($invoice->customer->number))
        <div style="margin-bottom: 1mm;">
            <strong>Kundennr.:</strong> {{ $invoice->customer->number }}
        </div>
    @endif

    @if(!empty($invoice->bauvorhaben) && ($layoutSettings['content']['show_bauvorhaben'] ?? true))
        <div style="margin-bottom: 1mm; font-weight: 600; color: {{ $layoutSettings['colors']['primary'] ?? '#3b82f6' }};">
            <strong>BV:</strong> {{ $invoice->bauvorhaben }}
        </div>
    @endif
</div>
