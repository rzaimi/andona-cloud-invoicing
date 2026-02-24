{{--
    Payment instructions paragraph — dynamic text based on due date and skonto.
    Required scope: $invoice, $dateFormat, $bodyFontSize, $layoutSettings
--}}
@php
    $skontoAmount = (float)($invoice->skonto_amount ?? 0);
    $hasSkonto    = !empty($invoice->skonto_percent) && !empty($invoice->skonto_days) && $skontoAmount > 0;
    $dueDateFmt   = $invoice->due_date ? formatInvoiceDate($invoice->due_date, $dateFormat ?? 'd.m.Y') : null;
    $taxNote      = $settings['invoice_tax_note'] ?? null;
@endphp

@if($taxNote)
    <div style="margin-top: 10px; font-size: {{ $bodyFontSize }}px; line-height: 1.5; font-style: italic;">
        {{ $taxNote }}
    </div>
@endif

@if($layoutSettings['content']['show_payment_terms'] ?? true)
    <div style="margin-top: 16px; font-size: {{ $bodyFontSize }}px; line-height: 1.6;">
        @if($hasSkonto)
            Bitte überweisen Sie den Rechnungsbetrag von
            <strong>{{ number_format($invoice->total, 2, ',', '.') }} €</strong>
            bis spätestens{{ $dueDateFmt ? ' ' . $dueDateFmt : '' }}
            unter Angabe der Rechnungsnummer <strong>{{ $invoice->number }}</strong> auf das unten genannte Konto.
            Bei Zahlung bis <strong>{{ formatInvoiceDate($invoice->skonto_due_date, $dateFormat ?? 'd.m.Y') }}</strong>
            gewähren wir Ihnen {{ number_format($invoice->skonto_percent, 0) }}% Skonto —
            Sie zahlen dann nur <strong>{{ number_format($invoice->total - $skontoAmount, 2, ',', '.') }} €</strong>.
        @else
            Bitte überweisen Sie den Rechnungsbetrag von
            <strong>{{ number_format($invoice->total, 2, ',', '.') }} €</strong>
            bis spätestens{{ $dueDateFmt ? ' ' . $dueDateFmt : '' }}
            unter Angabe der Rechnungsnummer <strong>{{ $invoice->number }}</strong> auf das unten genannte Konto.
        @endif
    </div>
@endif
