{{--
    Payment instructions paragraph — dynamic text based on due date and skonto.
    Required scope: $invoice, $dateFormat, $bodyFontSize, $layoutSettings
--}}
@php
    $skontoAmount  = (float)($invoice->skonto_amount ?? 0);
    $hasSkonto     = !empty($invoice->skonto_percent) && !empty($invoice->skonto_days) && $skontoAmount > 0;
    $dueDateFmt    = $invoice->due_date ? formatInvoiceDate($invoice->due_date, $dateFormat ?? 'd.m.Y') : null;
    $taxNote       = $settings['invoice_tax_note'] ?? null;
    $invoiceFooter = trim($settings['invoice_footer'] ?? '');

    // For Abschlagsrechnung and Schlussrechnung the amount the customer actually
    // owes is the remaining balance after deducting prior Abschlags, not the
    // gross invoice total.
    $invoiceType    = $invoice->invoice_type ?? 'standard';
    $isAbschlagType = in_array($invoiceType, ['abschlagsrechnung', 'schlussrechnung']);
    $abschlagRefs   = collect($invoice->abschlag_refs ?? [])
                        ->filter(fn ($r) => !empty($r['invoice_id']) && isset($r['amount']));
    $abschlagSum    = $abschlagRefs->sum('amount');
    $remainingDue   = $isAbschlagType && $abschlagRefs->isNotEmpty()
                        ? max(0.0, (float)$invoice->total - $abschlagSum)
                        : (float)$invoice->total;

    $paymentAmountLabel = $isAbschlagType && $abschlagRefs->isNotEmpty()
        ? ($invoiceType === 'abschlagsrechnung' ? 'Verbleibender Abschlagsbetrag' : 'Verbleibenden Betrag')
        : 'Rechnungsbetrag';
@endphp

@if($taxNote)
    <div style="margin-top: 10px; font-size: {{ $bodyFontSize }}px; line-height: 1.5; font-style: italic;">
        {{ $taxNote }}
    </div>
@endif

@if($layoutSettings['content']['show_payment_terms'] ?? true)
    <div style="margin-top: 16px; font-size: {{ $bodyFontSize }}px; line-height: 1.6;">
        @if($hasSkonto)
            Bitte überweisen Sie den {{ $paymentAmountLabel }} von
            <strong>{{ number_format($remainingDue, 2, ',', '.') }} €</strong>
            bis spätestens{{ $dueDateFmt ? ' ' . $dueDateFmt : '' }}
            unter Angabe der Rechnungsnummer <strong>{{ $invoice->number }}</strong> auf das unten genannte Konto.
            Bei Zahlung bis <strong>{{ formatInvoiceDate($invoice->skonto_due_date, $dateFormat ?? 'd.m.Y') }}</strong>
            gewähren wir Ihnen {{ number_format($invoice->skonto_percent, 0) }}% Skonto —
            Sie zahlen dann nur <strong>{{ number_format($remainingDue - $skontoAmount, 2, ',', '.') }} €</strong>.
        @else
            Bitte überweisen Sie den {{ $paymentAmountLabel }} von
            <strong>{{ number_format($remainingDue, 2, ',', '.') }} €</strong>
            bis spätestens{{ $dueDateFmt ? ' ' . $dueDateFmt : '' }}
            unter Angabe der Rechnungsnummer <strong>{{ $invoice->number }}</strong> auf das unten genannte Konto.
        @endif
    </div>
@endif

@if($invoiceFooter)
    <div style="margin-top: 14px; font-size: {{ $bodyFontSize }}px; line-height: 1.6; color: #555;">
        {!! nl2br(e($invoiceFooter)) !!}
    </div>
@endif
