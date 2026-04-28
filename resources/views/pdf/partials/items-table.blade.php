{{--
    Shared items table — works for both invoices and offers.
    Callers set $invoice or $offer in scope; the partial coerces to a generic
    $doc. Pass $docKind explicitly if you need different branching.

    Required: $invoice OR $offer in scope, $layoutSettings, $bodyFontSize.
    Optional: $tableHeaderBg / $tableHeaderTextColor / $tableHeaderStyle /
              $tableOuterBorder / $altRowBg / $cellPadding / $showRowNumber /
              $inlineRowNumber (prefix "1." to the description cell instead
              of rendering a dedicated NR column — typewriter-style layouts).
--}}
@php
    $doc     = $doc     ?? ($invoice ?? null) ?? ($offer ?? null);
    $docKind = $docKind ?? (isset($invoice) ? 'invoice' : 'offer');

    $inlineRowNumber      = $inlineRowNumber ?? false;
    $tableHeaderBg        = $tableHeaderBg        ?? null;
    $tableHeaderTextColor = $tableHeaderTextColor  ?? 'white';
    $tableHeaderStyle     = $tableHeaderStyle      ?? '';
    $tableOuterBorder     = $tableOuterBorder      ?? 'none';
    $altRowBg             = $altRowBg             ?? null;
    $cellPadding          = $cellPadding           ?? '7px 8px';
    $showRowNumber        = $layoutSettings['content']['show_row_number'] ?? ($showRowNumber ?? false);
    $showItemCodes        = $layoutSettings['content']['show_item_codes'] ?? false;
    $showBorderColor      = $layoutSettings['colors']['text'] ?? '#1f2937';
    // Show USt. column only for standard VAT invoices. For §19, §13b,
    // intra-community and export regimes the tax column must stay hidden.
    $showUstColumn        = ($doc->vat_regime ?? 'standard') === 'standard';

    $hasAnyDiscount = $doc->items->contains(
        fn($item) => (float)($item->discount_amount ?? 0) > 0.0001
    );

    // Column widths depend on which optional columns appear
    // Base: Description | Qty | Unit | [USt] | Price | Total
    $colDesc    = $hasAnyDiscount ? '33%' : '43%';
    if (!$showUstColumn) {
        $colDesc = $hasAnyDiscount ? '39%' : '49%';
    }
    $colItemNo  = '16%';
    $colPos     = '5%';
    $colQty     = '9%';
    $colUst     = '6%';
    $colPrice   = '10%';
    $colDisc    = '10%';
    $colTotal   = '15%';

    $headerBgStyle = $tableHeaderBg
        ? "background-color: {$tableHeaderBg}; color: {$tableHeaderTextColor};"
        : "border-bottom: 2px solid {$showBorderColor};";
    $headerBgStyle .= ' ' . $tableHeaderStyle;
@endphp
<table style="width: 100%; border-collapse: collapse; margin: 0 0 10px 0; {{ $tableOuterBorder !== 'none' ? 'border: ' . $tableOuterBorder . ';' : '' }}">
    <thead>
        <tr style="{{ $headerBgStyle }}">
            @if($showRowNumber && !$inlineRowNumber)
                <th style="padding: {{ $cellPadding }}; text-align: left; font-weight: 600; font-size: {{ $bodyFontSize }}px; border-right: 1px solid {{ $tableHeaderBg ? 'rgba(255,255,255,0.3)' : $showBorderColor }}; width: {{ $colPos }};">NR.</th>
            @endif
            @if($showItemCodes)
                <th style="padding: {{ $cellPadding }}; text-align: left; font-weight: 600; font-size: {{ $bodyFontSize }}px; white-space: nowrap; {{ $showRowNumber ? 'border-right: 1px solid ' . ($tableHeaderBg ? 'rgba(255,255,255,0.3)' : $showBorderColor) . ';' : '' }} width: {{ $colItemNo }};">PRODUKT-NR.</th>
            @endif
            <th style="padding: {{ $cellPadding }}; text-align: left; font-weight: 600; font-size: {{ $bodyFontSize }}px; {{ $showRowNumber ? 'border-right: 1px solid ' . ($tableHeaderBg ? 'rgba(255,255,255,0.3)' : $showBorderColor) . ';' : '' }} width: {{ $colDesc }};">LEISTUNG</th>
            <th style="padding: {{ $cellPadding }}; text-align: left; font-weight: 600; font-size: {{ $bodyFontSize }}px; {{ $showRowNumber ? 'border-right: 1px solid ' . ($tableHeaderBg ? 'rgba(255,255,255,0.3)' : $showBorderColor) . ';' : '' }} width: {{ $colQty }};">MENGE</th>
            @if($showUstColumn)
            <th style="padding: {{ $cellPadding }}; text-align: right; font-weight: 600; font-size: {{ $bodyFontSize }}px; {{ $showRowNumber ? 'border-right: 1px solid ' . ($tableHeaderBg ? 'rgba(255,255,255,0.3)' : $showBorderColor) . ';' : '' }} width: {{ $colUst }};">UST.</th>
            @endif
            <th style="padding: {{ $cellPadding }}; text-align: right; font-weight: 600; font-size: {{ $bodyFontSize }}px; {{ $showRowNumber ? 'border-right: 1px solid ' . ($tableHeaderBg ? 'rgba(255,255,255,0.3)' : $showBorderColor) . ';' : '' }} width: {{ $colPrice }};">PREIS</th>
            @if($hasAnyDiscount)
                <th style="padding: {{ $cellPadding }}; text-align: right; font-weight: 600; font-size: {{ $bodyFontSize }}px; {{ $showRowNumber ? 'border-right: 1px solid ' . ($tableHeaderBg ? 'rgba(255,255,255,0.3)' : $showBorderColor) . ';' : '' }} width: {{ $colDisc }}; color: {{ $tableHeaderBg ? 'rgba(255,255,255,0.9)' : '#dc2626' }};">RABATT</th>
            @endif
            <th style="padding: {{ $cellPadding }}; text-align: right; font-weight: 600; font-size: {{ $bodyFontSize }}px; width: {{ $colTotal }};">GESAMT</th>
        </tr>
    </thead>
    <tbody>
        @foreach($doc->items as $index => $item)
            @php
                $discountAmount = (float)($item->discount_amount ?? 0);
                $hasDiscount    = $discountAmount > 0.0001;
                $discountType   = $item->discount_type ?? null;
                $discountValue  = $item->discount_value ?? null;
                $productCode    = data_get($item, 'product.number')
                    ?? data_get($item, 'product.sku')
                    ?? data_get($item, 'product_number')
                    ?? data_get($item, 'product_sku');
                $rowBg = ($altRowBg && $index % 2 === 1) ? "background-color: {$altRowBg};" : '';
                $borderStyle = $tableOuterBorder !== 'none'
                    ? "border-bottom: 1px solid {$showBorderColor};"
                    : 'border-bottom: 1px solid #e5e7eb;';
                $cellBorder = $tableOuterBorder !== 'none'
                    ? "border-right: 1px solid {$showBorderColor};"
                    : '';
            @endphp
            <tr style="{{ $borderStyle }} {{ $rowBg }}">
                @if($showRowNumber && !$inlineRowNumber)
                    <td style="padding: {{ $cellPadding }}; {{ $cellBorder }}">{{ $index + 1 }}</td>
                @endif
                @if($showItemCodes)
                    <td style="padding: {{ $cellPadding }}; white-space: nowrap; {{ $cellBorder }}">{{ $productCode ?: '-' }}</td>
                @endif
                <td style="padding: {{ $cellPadding }}; {{ $cellBorder }}">
                    <div style="white-space:pre-wrap;">{{ $inlineRowNumber ? ($index + 1) . '. ' : '' }}{!! nl2br(e($item->description)) !!}</div>
                </td>
                <td style="padding: {{ $cellPadding }}; {{ $cellBorder }}">
                    {{ number_format($item->quantity, 2, ',', '.') }}
                    @if(($layoutSettings['content']['show_unit_column'] ?? true) && !empty($item->unit))
                        {{ $item->unit }}
                    @else
                        Std.
                    @endif
                </td>
                @if($showUstColumn)
                <td style="padding: {{ $cellPadding }}; text-align: right; {{ $cellBorder }}">
                    {{ number_format(($item->tax_rate ?? $doc->tax_rate ?? 0) * 100, 0, ',', '.') }}%
                </td>
                @endif
                <td style="padding: {{ $cellPadding }}; text-align: right; white-space: nowrap; {{ $cellBorder }}">
                    {{ number_format($item->unit_price, 2, ',', '.') }} €
                </td>
                @if($hasAnyDiscount)
                    <td style="padding: {{ $cellPadding }}; text-align: right; {{ $cellBorder }} color: {{ $hasDiscount ? '#dc2626' : '#9ca3af' }};">
                        @if($hasDiscount)
                            @if($discountType === 'percentage')
                                -{{ number_format($discountValue, 0) }}%<br>
                                <span style="font-size: {{ $bodyFontSize - 1 }}px;">(-{{ number_format($discountAmount, 2, ',', '.') }} €)</span>
                            @elseif($discountType === 'fixed')
                                -{{ number_format($discountAmount, 2, ',', '.') }} €
                            @endif
                        @else
                            <span style="color: #d1d5db;">—</span>
                        @endif
                    </td>
                @endif
                <td style="padding: {{ $cellPadding }}; text-align: right; font-weight: 600; white-space: nowrap;">
                    {{ number_format($item->total, 2, ',', '.') }} €
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
