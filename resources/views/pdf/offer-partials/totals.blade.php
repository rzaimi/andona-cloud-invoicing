{{--
    Offer totals table — discount rows, per-rate VAT breakdown, grand total.
    Required scope: $offer, $fs (bodyFontSize), $ls (layoutSettings)
    Optional:
        $totalRowBg        – grand total row background (default: primary)
        $totalRowTextColor – grand total row text color (default: white)
        $tableWidth        – table width (default: '290px')
        $totalsTableStyle  – extra CSS on the outer <table>
--}}
@php
    $items         = $offer->items ?? [];
    $subtotal      = (float)($offer->subtotal ?? 0);
    $taxAmount     = (float)($offer->tax_amount ?? 0);
    $offerTotal    = (float)($offer->total ?? 0);
    $taxRate       = (float)($offer->tax_rate ?? 0);
    $vatRegime     = $offer->vat_regime ?? 'standard';
    $isStandardVat = $vatRegime === 'standard';

    $totalDiscount = 0;
    foreach ($items as $it) {
        $totalDiscount += (float)(is_object($it) ? ($it->discount_amount ?? 0) : ($it['discount_amount'] ?? 0));
    }

    // Per-rate VAT breakdown
    $vatBreakdown = [];
    if ($isStandardVat) {
        foreach ($items as $item) {
            $rate = round((float)(is_object($item) ? ($item->tax_rate ?? $taxRate) : ($item['tax_rate'] ?? $taxRate)), 4);
            $k    = (string)$rate;
            $net  = (float)(is_object($item) ? ($item->total ?? 0) : ($item['total'] ?? 0));
            if (!isset($vatBreakdown[$k])) $vatBreakdown[$k] = ['rate' => $rate, 'net' => 0.0, 'tax' => 0.0];
            $vatBreakdown[$k]['net'] += $net;
            $vatBreakdown[$k]['tax'] += $net * $rate;
        }
        uasort($vatBreakdown, fn($a, $b) => $b['rate'] <=> $a['rate']);
    }
    $multipleRates = count($vatBreakdown) > 1;

    $trBg    = $totalRowBg        ?? ($ls['colors']['primary'] ?? '#1f2937');
    $trColor = $totalRowTextColor ?? '#ffffff';
    $tWidth  = $tableWidth        ?? '290px';
    $tStyle  = $totalsTableStyle  ?? '';
    $bc      = '#e5e7eb';
@endphp
<table style="width:{{ $tWidth }}; margin-left:auto; border-collapse:collapse; font-size:{{ $fs }}px; {{ $tStyle }}">

    @if($totalDiscount > 0.0001)
    <tr>
        <td style="padding:5px 10px; border-bottom:1px solid {{ $bc }};">Nettobetrag (vor Rabatt)</td>
        <td style="padding:5px 10px; text-align:right; border-bottom:1px solid {{ $bc }}; white-space:nowrap;">{{ number_format($subtotal + $totalDiscount, 2, ',', '.') }} €</td>
    </tr>
    <tr>
        <td style="padding:5px 10px; color:#dc2626; border-bottom:1px solid {{ $bc }};">Rabatt</td>
        <td style="padding:5px 10px; text-align:right; color:#dc2626; border-bottom:1px solid {{ $bc }}; white-space:nowrap;">-{{ number_format($totalDiscount, 2, ',', '.') }} €</td>
    </tr>
    @endif

    <tr>
        <td style="padding:5px 10px; border-bottom:1px solid {{ $bc }};">Nettobetrag</td>
        <td style="padding:5px 10px; text-align:right; border-bottom:1px solid {{ $bc }}; white-space:nowrap;">{{ number_format($subtotal, 2, ',', '.') }} €</td>
    </tr>

    @if($isStandardVat)
        @if($multipleRates)
            @foreach($vatBreakdown as $vat)
                @if($vat['rate'] > 0.0001)
                <tr>
                    <td style="padding:5px 10px; border-bottom:1px solid {{ $bc }};">{{ number_format($vat['rate'] * 100, 0) }}% USt. auf {{ number_format($vat['net'], 2, ',', '.') }} €</td>
                    <td style="padding:5px 10px; text-align:right; border-bottom:1px solid {{ $bc }}; white-space:nowrap;">{{ number_format($vat['tax'], 2, ',', '.') }} €</td>
                </tr>
                @else
                <tr>
                    <td style="padding:5px 10px; border-bottom:1px solid {{ $bc }}; color:#6b7280;">0% USt. (steuerfrei)</td>
                    <td style="padding:5px 10px; text-align:right; border-bottom:1px solid {{ $bc }}; white-space:nowrap;">0,00 €</td>
                </tr>
                @endif
            @endforeach
        @else
        <tr>
            <td style="padding:5px 10px; border-bottom:1px solid {{ $bc }};">{{ number_format($taxRate * 100, 0) }}% Umsatzsteuer</td>
            <td style="padding:5px 10px; text-align:right; border-bottom:1px solid {{ $bc }}; white-space:nowrap;">{{ number_format($taxAmount, 2, ',', '.') }} €</td>
        </tr>
        @endif
    @endif

    <tr style="background-color:{{ $trBg }}; color:{{ $trColor }};">
        <td style="padding:8px 10px; font-weight:700; font-size:{{ $fs + 1 }}px;">Gesamtbetrag (brutto)</td>
        <td style="padding:8px 10px; text-align:right; font-weight:700; font-size:{{ $fs + 1 }}px; white-space:nowrap;">{{ number_format($offerTotal, 2, ',', '.') }} €</td>
    </tr>

</table>
@include('pdf.partials.vat-regime-legal-notice', [
    'vat_regime' => $offer->vat_regime ?? 'standard',
    'fontSizePx' => $fs,
    'mutedColor' => $ls['colors']['secondary'] ?? '#64748b',
    'tableWidth' => $tWidth,
])
