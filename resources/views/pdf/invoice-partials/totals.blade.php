{{--
    Invoice totals table — discount rows, per-rate VAT breakdown, grand total, skonto rows.
    Required scope vars: $invoice, $dateFormat, $bodyFontSize, $layoutSettings
    Optional:           $totalRowBg        (background color for the grand-total row, default primary)
                        $totalRowTextColor (text color for the grand-total row, default white)
                        $tableWidth        (default '290px')
--}}
@php
    $totalRowBg        = $totalRowBg ?? ($layoutSettings['colors']['primary'] ?? '#1f2937');
    $totalRowTextColor = $totalRowTextColor ?? '#ffffff';
    $tableWidth        = $tableWidth ?? '290px';
    $borderColor       = '#e5e7eb';

    // Discount total
    $totalDiscount = 0;
    foreach ($invoice->items as $it) {
        $totalDiscount += (float)($it->discount_amount ?? 0);
    }

    // Per-rate VAT breakdown
    $vatBreakdown  = [];
    $isStandardVat = ($invoice->vat_regime ?? 'standard') === 'standard';
    if ($isStandardVat) {
        foreach ($invoice->items as $item) {
            $rate    = round((float)($item->tax_rate ?? $invoice->tax_rate ?? 0), 4);
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

    // Skonto
    $skontoAmount  = (float)($invoice->skonto_amount ?? 0);
    $hasSkonto     = !empty($invoice->skonto_percent) && !empty($invoice->skonto_days) && $skontoAmount > 0;
    $netAfterSkonto = $invoice->total - $skontoAmount;
@endphp
<table style="width: {{ $tableWidth }}; margin-left: auto; border-collapse: collapse; font-size: {{ $bodyFontSize }}px;">

    {{-- Discount rows --}}
    @if($totalDiscount > 0.0001)
        <tr>
            <td style="padding: 5px 10px; text-align: left; border-bottom: 1px solid {{ $borderColor }};">Bruttobetrag (vor Rabatt)</td>
            <td style="padding: 5px 10px; text-align: right; border-bottom: 1px solid {{ $borderColor }}; white-space: nowrap;">{{ number_format($invoice->subtotal + $totalDiscount, 2, ',', '.') }} €</td>
        </tr>
        <tr>
            <td style="padding: 5px 10px; text-align: left; border-bottom: 1px solid {{ $borderColor }}; color: #dc2626;">Rabatt</td>
            <td style="padding: 5px 10px; text-align: right; border-bottom: 1px solid {{ $borderColor }}; color: #dc2626; white-space: nowrap;">−{{ number_format($totalDiscount, 2, ',', '.') }} €</td>
        </tr>
    @endif

    {{-- Net row --}}
    <tr>
        <td style="padding: 5px 10px; text-align: left; border-bottom: 1px solid {{ $borderColor }};">Nettobetrag</td>
        <td style="padding: 5px 10px; text-align: right; border-bottom: 1px solid {{ $borderColor }}; white-space: nowrap;">{{ number_format($invoice->subtotal, 2, ',', '.') }} €</td>
    </tr>

    {{-- VAT row(s) --}}
    @if($isStandardVat)
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
                <td style="padding: 5px 10px; text-align: left; border-bottom: 1px solid {{ $borderColor }};">{{ number_format($invoice->tax_rate * 100, 0) }}% Umsatzsteuer</td>
                <td style="padding: 5px 10px; text-align: right; border-bottom: 1px solid {{ $borderColor }}; white-space: nowrap;">{{ number_format($invoice->tax_amount, 2, ',', '.') }} €</td>
            </tr>
        @endif
    @endif

    {{-- Grand total --}}
    <tr style="background-color: {{ $totalRowBg }}; color: {{ $totalRowTextColor }};">
        <td style="padding: 8px 10px; text-align: left; font-weight: 700; font-size: {{ $bodyFontSize + 1 }}px;">Gesamtbetrag (brutto)</td>
        <td style="padding: 8px 10px; text-align: right; font-weight: 700; font-size: {{ $bodyFontSize + 1 }}px; white-space: nowrap;">{{ number_format($invoice->total, 2, ',', '.') }} €</td>
    </tr>

    {{-- Skonto rows --}}
    @if($hasSkonto)
        <tr style="color: #16a34a;">
            <td style="padding: 5px 10px; text-align: left; font-style: italic; border-top: 2px solid #dcfce7;">
                Skonto {{ number_format($invoice->skonto_percent, 0) }}% bei Zahlung bis {{ formatInvoiceDate($invoice->skonto_due_date, $dateFormat ?? 'd.m.Y') }}
            </td>
            <td style="padding: 5px 10px; text-align: right; font-style: italic; border-top: 2px solid #dcfce7; white-space: nowrap;">
                −{{ number_format($skontoAmount, 2, ',', '.') }} €
            </td>
        </tr>
        <tr style="color: #16a34a; font-weight: 700;">
            <td style="padding: 5px 10px; text-align: left; border-bottom: 2px solid #16a34a;">Bei Skonto zahlen Sie</td>
            <td style="padding: 5px 10px; text-align: right; border-bottom: 2px solid #16a34a; white-space: nowrap;">{{ number_format($netAfterSkonto, 2, ',', '.') }} €</td>
        </tr>
    @endif

</table>
