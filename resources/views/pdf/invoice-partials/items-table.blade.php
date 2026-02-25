{{--
    Invoice items table — shared across all templates.
    Required scope: $invoice, $layoutSettings, $bodyFontSize
    Optional:       $tableHeaderBg        (background color for <thead> row)
                    $tableHeaderTextColor (text color for <thead> row, default white)
                    $tableHeaderStyle     (extra inline CSS on the header <tr>)
                    $tableOuterBorder     (outer border style, e.g. "1px solid #1f2937")
                    $altRowBg             (alternate row background color)
                    $cellPadding          (padding for td/th, default "8px 6px")
                    $showRowNumber        (bool, show a leading Pos. column — classic template)
--}}
@php
    $tableHeaderBg        = $tableHeaderBg        ?? null;
    $tableHeaderTextColor = $tableHeaderTextColor  ?? 'white';
    $tableHeaderStyle     = $tableHeaderStyle      ?? '';
    $tableOuterBorder     = $tableOuterBorder      ?? 'none';
    $altRowBg             = $altRowBg             ?? null;
    $cellPadding          = $cellPadding           ?? '8px 6px';
    $showRowNumber        = $layoutSettings['content']['show_row_number'] ?? ($showRowNumber ?? false);
    $showItemCodes        = $layoutSettings['content']['show_item_codes'] ?? false;
    $showBorderColor      = $layoutSettings['colors']['text'] ?? '#1f2937';

    $hasAnyDiscount = $invoice->items->contains(
        fn($item) => (float)($item->discount_amount ?? 0) > 0.0001
    );

    // Column widths depend on which optional columns appear
    // Base: Description | Qty | Unit | USt | Price | Total
    $colDesc    = $hasAnyDiscount ? '40%' : '50%';
    $colItemNo  = '12%';
    $colPos     = '5%';
    $colQty     = '9%';
    $colUst     = '6%';
    $colPrice   = '10%';
    $colDisc    = '10%';
    $colTotal   = '12%';

    $headerBgStyle = $tableHeaderBg
        ? "background-color: {$tableHeaderBg}; color: {$tableHeaderTextColor};"
        : "border-bottom: 2px solid {$showBorderColor};";
    $headerBgStyle .= ' ' . $tableHeaderStyle;
@endphp
<table style="width: 100%; border-collapse: collapse; margin: 10px 0; {{ $tableOuterBorder !== 'none' ? 'border: ' . $tableOuterBorder . ';' : '' }}">
    <thead>
        <tr style="{{ $headerBgStyle }}">
            @if($showRowNumber)
                <th style="padding: {{ $cellPadding }}; text-align: left; font-weight: 600; font-size: {{ $bodyFontSize }}px; border-right: 1px solid {{ $tableHeaderBg ? 'rgba(255,255,255,0.3)' : $showBorderColor }}; width: {{ $colPos }};">Nr.</th>
            @endif
            @if($showItemCodes)
                <th style="padding: {{ $cellPadding }}; text-align: left; font-weight: 600; font-size: {{ $bodyFontSize }}px; {{ $showRowNumber ? 'border-right: 1px solid ' . ($tableHeaderBg ? 'rgba(255,255,255,0.3)' : $showBorderColor) . ';' : '' }} width: {{ $colItemNo }};">PRODUKT-NR.</th>
            @endif
            <th style="padding: {{ $cellPadding }}; text-align: left; font-weight: 600; font-size: {{ $bodyFontSize }}px; {{ $showRowNumber ? 'border-right: 1px solid ' . ($tableHeaderBg ? 'rgba(255,255,255,0.3)' : $showBorderColor) . ';' : '' }} width: {{ $colDesc }};">LEISTUNG</th>
            <th style="padding: {{ $cellPadding }}; text-align: left; font-weight: 600; font-size: {{ $bodyFontSize }}px; {{ $showRowNumber ? 'border-right: 1px solid ' . ($tableHeaderBg ? 'rgba(255,255,255,0.3)' : $showBorderColor) . ';' : '' }} width: {{ $colQty }};">MENGE</th>
            <th style="padding: {{ $cellPadding }}; text-align: right; font-weight: 600; font-size: {{ $bodyFontSize }}px; {{ $showRowNumber ? 'border-right: 1px solid ' . ($tableHeaderBg ? 'rgba(255,255,255,0.3)' : $showBorderColor) . ';' : '' }} width: {{ $colUst }};">UST.</th>
            <th style="padding: {{ $cellPadding }}; text-align: right; font-weight: 600; font-size: {{ $bodyFontSize }}px; {{ $showRowNumber ? 'border-right: 1px solid ' . ($tableHeaderBg ? 'rgba(255,255,255,0.3)' : $showBorderColor) . ';' : '' }} width: {{ $colPrice }};">PREIS</th>
            @if($hasAnyDiscount)
                <th style="padding: {{ $cellPadding }}; text-align: right; font-weight: 600; font-size: {{ $bodyFontSize }}px; {{ $showRowNumber ? 'border-right: 1px solid ' . ($tableHeaderBg ? 'rgba(255,255,255,0.3)' : $showBorderColor) . ';' : '' }} width: {{ $colDisc }}; color: {{ $tableHeaderBg ? 'rgba(255,255,255,0.9)' : '#dc2626' }};">RABATT</th>
            @endif
            <th style="padding: {{ $cellPadding }}; text-align: right; font-weight: 600; font-size: {{ $bodyFontSize }}px; width: {{ $colTotal }};">GESAMT</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->items as $index => $item)
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
                @if($showRowNumber)
                    <td style="padding: {{ $cellPadding }}; {{ $cellBorder }}">{{ $index + 1 }}</td>
                @endif
                @if($showItemCodes)
                    <td style="padding: {{ $cellPadding }}; {{ $cellBorder }}">{{ $productCode ?: '-' }}</td>
                @endif
                <td style="padding: {{ $cellPadding }}; {{ $cellBorder }}">
                    <div>{{ $item->description }}</div>
                </td>
                <td style="padding: {{ $cellPadding }}; {{ $cellBorder }}">
                    {{ number_format($item->quantity, 2, ',', '.') }}
                    @if(($layoutSettings['content']['show_unit_column'] ?? true) && !empty($item->unit))
                        {{ $item->unit }}
                    @else
                        Std.
                    @endif
                </td>
                <td style="padding: {{ $cellPadding }}; text-align: right; {{ $cellBorder }}">
                    {{ number_format(($item->tax_rate ?? $invoice->tax_rate ?? 0) * 100, 0, ',', '.') }}%
                </td>
                <td style="padding: {{ $cellPadding }}; text-align: right; {{ $cellBorder }}">
                    {{ number_format($item->unit_price, 2, ',', '.') }} €
                </td>
                @if($hasAnyDiscount)
                    <td style="padding: {{ $cellPadding }}; text-align: right; {{ $cellBorder }} color: {{ $hasDiscount ? '#dc2626' : '#9ca3af' }};">
                        @if($hasDiscount)
                            @if($discountType === 'percentage')
                                −{{ number_format($discountValue, 0) }}%<br>
                                <span style="font-size: {{ $bodyFontSize - 1 }}px;">(−{{ number_format($discountAmount, 2, ',', '.') }} €)</span>
                            @elseif($discountType === 'fixed')
                                −{{ number_format($discountAmount, 2, ',', '.') }} €
                            @endif
                        @else
                            <span style="color: #d1d5db;">—</span>
                        @endif
                    </td>
                @endif
                <td style="padding: {{ $cellPadding }}; text-align: right; font-weight: 600;">
                    {{ number_format($item->total, 2, ',', '.') }} €
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
