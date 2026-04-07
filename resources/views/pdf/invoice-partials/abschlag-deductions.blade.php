{{--
    Abschlag deduction rows for Schlussrechnung.
    Rendered inside the totals table after the grand-total row.
    Required scope: $invoice, $bodyFontSize, $layoutSettings
    Only outputs rows when:
      - invoice_type === 'schlussrechnung'
      - abschlag_refs is a non-empty array
--}}
@php
    $refs = collect($invoice->abschlag_refs ?? [])
        ->filter(fn ($r) => !empty($r['invoice_id']) && isset($r['amount']));

    if ($refs->isEmpty() || ($invoice->invoice_type ?? '') !== 'schlussrechnung') {
        return; // nothing to render
    }

    $abschlagTotal    = $refs->sum('amount');
    $remainingAmount  = max(0.0, (float)$invoice->total - $abschlagTotal);
    $primaryColor     = $layoutSettings['colors']['primary'] ?? '#1f2937';
    $borderColor      = '#e5e7eb';
    $fs               = $bodyFontSize;
@endphp

{{-- Section divider --}}
<tr>
    <td colspan="2" style="padding: 0; height: 6px;"></td>
</tr>
<tr>
    <td colspan="2" style="padding: 4px 10px; font-size: {{ $fs - 1 }}px; font-weight: 600; color: {{ $primaryColor }}; border-top: 1px solid {{ $primaryColor }}; letter-spacing: 0.3px;">
        Abzüglich geleisteter Abschlagszahlungen
    </td>
</tr>

@foreach($refs as $ref)
<tr>
    <td style="padding: 3px 10px 3px 16px; text-align: left; border-bottom: 1px solid {{ $borderColor }}; font-size: {{ $fs - 1 }}px; color: #374151;">
        ./. {{ $ref['number'] ?? '–' }}
        @if(!empty($ref['date']))
            <span style="color: #9ca3af; font-size: {{ $fs - 2 }}px;"> ({{ \Carbon\Carbon::parse($ref['date'])->format('d.m.Y') }})</span>
        @endif
    </td>
    <td style="padding: 3px 10px; text-align: right; border-bottom: 1px solid {{ $borderColor }}; white-space: nowrap; font-size: {{ $fs - 1 }}px; color: #374151;">
        -{{ number_format((float)$ref['amount'], 2, ',', '.') }} €
    </td>
</tr>
@endforeach

{{-- Remaining amount row --}}
<tr style="background-color: {{ $primaryColor }}; color: #ffffff;">
    <td style="padding: 8px 10px; text-align: left; font-weight: 700; font-size: {{ $fs + 1 }}px;">
        Verbleibender Betrag
    </td>
    <td style="padding: 8px 10px; text-align: right; font-weight: 700; font-size: {{ $fs + 1 }}px; white-space: nowrap;">
        {{ number_format($remainingAmount, 2, ',', '.') }} €
    </td>
</tr>
