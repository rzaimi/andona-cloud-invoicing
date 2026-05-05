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
<table style="width: 100%; border-collapse: collapse; margin: 0 0 10px 0; page-break-inside: auto; {{ $tableOuterBorder !== 'none' ? 'border: ' . $tableOuterBorder . ';' : '' }}">
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
        {{--
            DomPDF never splits a <tr> mid-row.  The only reliable workaround is
            to render each LINE of the description as its own <tr> (one line tall).
            Pricing columns (Qty, USt, Price, Total) appear on the FIRST line row
            only; continuation rows have empty cells in those columns.
            Because every row is now a single text line it always fits on one page,
            so DomPDF naturally breaks between rows at page boundaries.
        --}}
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
                $rowBg       = ($altRowBg && $index % 2 === 1) ? "background-color: {$altRowBg};" : '';
                $borderStyle = $tableOuterBorder !== 'none'
                    ? "border-bottom: 1px solid {$showBorderColor};"
                    : 'border-bottom: 1px solid #e5e7eb;';
                $noBorder    = 'border-bottom: none;';
                $cellBorder  = $tableOuterBorder !== 'none'
                    ? "border-right: 1px solid {$showBorderColor};"
                    : '';

                // Split description into individual lines; keep empty lines as spacers.
                $descLines = preg_split('/\r?\n/', $item->description ?? '');
                $lineCount  = count($descLines);
                $prefix     = $inlineRowNumber ? ($index + 1) . '. ' : '';
            @endphp

            {{-- One <tr> per description line --}}
            @foreach($descLines as $lineIdx => $descLine)
                @php
                    $isFirstLine = ($lineIdx === 0);
                    $isLastLine  = ($lineIdx === $lineCount - 1);
                    // Border only on the last row of the item
                    $trBorder    = $isLastLine ? $borderStyle : $noBorder;
                    // Continuation lines get slightly reduced top padding
                    $linePad     = $isFirstLine ? $cellPadding : '1px 7px';
                @endphp
                <tr style="{{ $trBorder }} {{ $rowBg }}">
                    {{-- Optional row-number column — only on first line --}}
                    @if($showRowNumber && !$inlineRowNumber)
                        <td style="padding: {{ $linePad }}; {{ $cellBorder }} vertical-align: top;">
                            {{ $isFirstLine ? ($index + 1) : '' }}
                        </td>
                    @endif

                    {{-- Optional item-code column — only on first line --}}
                    @if($showItemCodes)
                        <td style="padding: {{ $linePad }}; white-space: nowrap; {{ $cellBorder }} vertical-align: top;">
                            {{ $isFirstLine ? ($productCode ?: '-') : '' }}
                        </td>
                    @endif

                    {{-- Description cell: one line of text --}}
                    <td style="padding: {{ $linePad }}; {{ $cellBorder }} vertical-align: top;">
                        {{ ($isFirstLine ? $prefix : '') }}{{ $descLine }}
                    </td>

                    {{-- Pricing columns: only on the first line row --}}
                    @if($isFirstLine)
                        <td style="padding: {{ $cellPadding }}; {{ $cellBorder }} vertical-align: top;">
                            {{ number_format($item->quantity, 2, ',', '.') }}
                            @if(($layoutSettings['content']['show_unit_column'] ?? true) && !empty($item->unit))
                                {{ $item->unit }}
                            @else
                                Std.
                            @endif
                        </td>
                        @if($showUstColumn)
                        <td style="padding: {{ $cellPadding }}; text-align: right; {{ $cellBorder }} vertical-align: top;">
                            {{ number_format(($item->tax_rate ?? $doc->tax_rate ?? 0) * 100, 0, ',', '.') }}%
                        </td>
                        @endif
                        <td style="padding: {{ $cellPadding }}; text-align: right; white-space: nowrap; {{ $cellBorder }} vertical-align: top;">
                            {{ number_format($item->unit_price, 2, ',', '.') }} €
                        </td>
                        @if($hasAnyDiscount)
                        <td style="padding: {{ $cellPadding }}; text-align: right; {{ $cellBorder }} vertical-align: top; color: {{ $hasDiscount ? '#dc2626' : '#9ca3af' }};">
                            @if($hasDiscount)
                                @if($discountType === 'percentage')
                                    -{{ number_format($discountValue, 0) }}%
                                    (-{{ number_format($discountAmount, 2, ',', '.') }} €)
                                @elseif($discountType === 'fixed')
                                    -{{ number_format($discountAmount, 2, ',', '.') }} €
                                @endif
                            @else
                                <span style="color: #d1d5db;">—</span>
                            @endif
                        </td>
                        @endif
                        <td style="padding: {{ $cellPadding }}; text-align: right; font-weight: 600; white-space: nowrap; vertical-align: top;">
                            {{ number_format($item->total, 2, ',', '.') }} €
                        </td>
                    @else
                        {{-- Empty cells to keep column structure intact --}}
                        <td style="padding: {{ $linePad }}; {{ $cellBorder }}"></td>
                        @if($showUstColumn)<td style="{{ $cellBorder }}"></td>@endif
                        <td style="{{ $cellBorder }}"></td>
                        @if($hasAnyDiscount)<td style="{{ $cellBorder }}"></td>@endif
                        <td></td>
                    @endif
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>
