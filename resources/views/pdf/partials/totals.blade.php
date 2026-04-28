{{--
    Shared totals table — works for invoices and offers. Callers set $invoice
    OR $offer in scope; the partial coerces to a generic $doc and branches
    invoice-only blocks (Skonto, Abschlag) via $docKind.

    Required: $invoice OR $offer, $dateFormat, $bodyFontSize, $layoutSettings.
    Optional: $totalRowBg, $totalRowTextColor, $tableWidth.
--}}
@php
    $doc     = $doc     ?? ($invoice ?? null) ?? ($offer ?? null);
    $docKind = $docKind ?? (isset($invoice) ? 'invoice' : 'offer');

    $totalRowBg        = $totalRowBg ?? ($layoutSettings['colors']['primary'] ?? '#1f2937');
    $totalRowTextColor = $totalRowTextColor ?? '#ffffff';
    $tableWidth        = $tableWidth ?? '290px';
    $borderColor       = '#e5e7eb';

    // Discount total
    $totalDiscount = 0;
    foreach ($doc->items as $it) {
        $totalDiscount += (float)($it->discount_amount ?? 0);
    }

    // Per-rate VAT breakdown
    $vatBreakdown  = [];
    $isStandardVat = ($doc->vat_regime ?? 'standard') === 'standard';
    if ($isStandardVat) {
        foreach ($doc->items as $item) {
            $rate    = round((float)($item->tax_rate ?? $doc->tax_rate ?? 0), 4);
            $rateKey = (string)$rate;
            if (!isset($vatBreakdown[$rateKey])) {
                $vatBreakdown[$rateKey] = ['rate' => $rate, 'net' => 0.0, 'tax' => 0.0];
            }
            $itemNet                        = (float)($item->total ?? 0);
            $vatBreakdown[$rateKey]['net'] += $itemNet;
            $vatBreakdown[$rateKey]['tax'] += $itemNet * $rate;
        }
        // Highest rate first (19 → 7 → 0)
        uasort($vatBreakdown, fn($a, $b) => $b['rate'] <=> $a['rate']);
    }
    $multipleRates = count($vatBreakdown) > 1;
    $singleRateVat = !$multipleRates && count($vatBreakdown) === 1 ? array_values($vatBreakdown)[0] : null;
    $grandTotalLabel = $isStandardVat ? 'Gesamtbetrag (brutto)' : 'Gesamtbetrag';

    // Skonto
    $skontoAmount   = (float)($doc->skonto_amount ?? 0);
    $hasSkonto      = !empty($doc->skonto_percent) && !empty($doc->skonto_days) && $skontoAmount > 0;
    $netAfterSkonto = $doc->total - $skontoAmount;
    $skontoColor    = $layoutSettings['colors']['skonto'] ?? '#16a34a';
@endphp
<table style="width: {{ $tableWidth }}; margin-left: auto; border-collapse: collapse; font-size: {{ $bodyFontSize }}px;">

    {{-- Discount rows --}}
    @if($totalDiscount > 0.0001)
        <tr>
            <td style="padding: 5px 10px; text-align: left; border-bottom: 1px solid {{ $borderColor }};">Bruttobetrag (vor Rabatt)</td>
            <td style="padding: 5px 10px; text-align: right; border-bottom: 1px solid {{ $borderColor }}; white-space: nowrap;">{{ number_format($doc->subtotal + $totalDiscount, 2, ',', '.') }} €</td>
        </tr>
        <tr>
            <td style="padding: 5px 10px; text-align: left; border-bottom: 1px solid {{ $borderColor }}; color: #dc2626;">Rabatt</td>
            <td style="padding: 5px 10px; text-align: right; border-bottom: 1px solid {{ $borderColor }}; color: #dc2626; white-space: nowrap;">-{{ number_format($totalDiscount, 2, ',', '.') }} €</td>
        </tr>
    @endif

    {{-- Net row --}}
    <tr>
        <td style="padding: 5px 10px; text-align: left; border-bottom: 1px solid {{ $borderColor }};">Nettobetrag</td>
        <td style="padding: 5px 10px; text-align: right; border-bottom: 1px solid {{ $borderColor }}; white-space: nowrap;">{{ number_format($doc->subtotal, 2, ',', '.') }} €</td>
    </tr>

    {{-- VAT row(s) --}}
    @if($isStandardVat && ($layoutSettings['content']['show_tax_breakdown'] ?? true))
        @if($multipleRates)
            @foreach($vatBreakdown as $rateKey => $vat)
                @if($vat['rate'] > 0.0001)
                    <tr>
                        <td style="padding: 5px 10px; text-align: left; border-bottom: 1px solid {{ $borderColor }};">
                            {{ number_format($vat['rate'] * 100, 0) }}% USt. auf {{ number_format($vat['net'], 2, ',', '.') }} €
                        </td>
                        <td style="padding: 5px 10px; text-align: right; border-bottom: 1px solid {{ $borderColor }}; white-space: nowrap;">
                            {{ number_format($vat['tax'], 2, ',', '.') }} €
                        </td>
                    </tr>
                @else
                    <tr>
                        <td style="padding: 5px 10px; text-align: left; border-bottom: 1px solid {{ $borderColor }}; color: #6b7280;">0% USt. (steuerfrei)</td>
                        <td style="padding: 5px 10px; text-align: right; border-bottom: 1px solid {{ $borderColor }}; color: #6b7280; white-space: nowrap;">0,00 €</td>
                    </tr>
                @endif
            @endforeach
        @else
            <tr>
                <td style="padding: 5px 10px; text-align: left; border-bottom: 1px solid {{ $borderColor }};">
                    {{ number_format(($singleRateVat['rate'] ?? (float)$doc->tax_rate) * 100, 0) }}% Umsatzsteuer
                </td>
                <td style="padding: 5px 10px; text-align: right; border-bottom: 1px solid {{ $borderColor }}; white-space: nowrap;">
                    {{ number_format($singleRateVat['tax'] ?? (float)$doc->tax_amount, 2, ',', '.') }} €
                </td>
            </tr>
        @endif
    @endif

    {{-- Grand total --}}
    <tr style="background-color: {{ $totalRowBg }}; color: {{ $totalRowTextColor }};">
        <td style="padding: 8px 10px; text-align: left; font-weight: 700; font-size: {{ $bodyFontSize + 1 }}px;">{{ $grandTotalLabel }}</td>
        <td style="padding: 8px 10px; text-align: right; font-weight: 700; font-size: {{ $bodyFontSize + 1 }}px; white-space: nowrap;">{{ number_format($doc->total, 2, ',', '.') }} €</td>
    </tr>

    {{-- Abschlag deductions (Schlussrechnung only) --}}
    @include('pdf.partials.abschlag-deductions')

    {{-- Skonto rows --}}
    @if($hasSkonto)
        <tr style="color: {{ $skontoColor }};">
            <td style="padding: 5px 10px; text-align: left; font-style: italic; border-top: 2px solid {{ $skontoColor }}33;">
                Skonto {{ number_format($doc->skonto_percent, 0) }}% bei Zahlung bis {{ formatInvoiceDate($doc->skonto_due_date, $dateFormat ?? 'd.m.Y') }}
            </td>
            <td style="padding: 5px 10px; text-align: right; font-style: italic; border-top: 2px solid {{ $skontoColor }}33; white-space: nowrap;">
                -{{ number_format($skontoAmount, 2, ',', '.') }} €
            </td>
        </tr>
        <tr style="color: {{ $skontoColor }}; font-weight: 700;">
            <td style="padding: 5px 10px; text-align: left; border-bottom: 2px solid {{ $skontoColor }};">Bei Skonto zahlen Sie</td>
            <td style="padding: 5px 10px; text-align: right; border-bottom: 2px solid {{ $skontoColor }}; white-space: nowrap;">{{ number_format($netAfterSkonto, 2, ',', '.') }} €</td>
        </tr>
    @endif

</table>
@include('pdf.partials.vat-regime-legal-notice', [
    'vat_regime' => $doc->vat_regime ?? 'standard',
    'fontSizePx' => $bodyFontSize,
    'mutedColor' => $layoutSettings['colors']['secondary'] ?? '#64748b',
    'tableWidth' => $tableWidth,
])
