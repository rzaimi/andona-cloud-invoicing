{{--
    Offer items table — shared across all offer templates.
    Required scope: $offer (or $items), $ls (layoutSettings), $fs (bodyFontSize)
    Optional:
        $tableHeaderBg          – thead background color (null = no fill)
        $tableHeaderTextColor   – thead text color (default white if bg set, else $textCol)
        $tableHeaderStyle       – extra inline CSS on thead <tr>
        $tableOuterBorder       – outer border CSS (e.g. '1.5px solid #1f2937'), default none
        $altRowBg               – alternating row background
        $cellPadding            – td/th padding (default '7px 8px')
        $showRowNumber          – bool, show Pos. column
        $thBorderColor          – border between header cells (default transparent if bg, else $textCol)
--}}
@php
    $items              = $offer->items ?? [];
    $showItemCodes      = $ls['content']['show_item_codes'] ?? true;
    $showRowNum         = $showRowNumber ?? ($ls['content']['show_row_number'] ?? true);
    $showUnit           = $ls['content']['show_unit_column'] ?? true;

    $thBg               = $tableHeaderBg ?? null;
    $thTextColor        = $tableHeaderTextColor ?? ($thBg ? 'white' : ($textCol ?? '#1f2937'));
    $thExtraStyle       = $tableHeaderStyle ?? '';
    $outerBorder        = $tableOuterBorder ?? 'none';
    $altBg              = $altRowBg ?? null;
    $cp                 = $cellPadding ?? '7px 8px';
    $showBorderCol      = $textCol ?? '#1f2937';
    $thBordCol          = $thBorderColor ?? ($thBg ? 'rgba(255,255,255,0.25)' : $showBorderCol);

    $headerCss = $thBg
        ? "background-color:{$thBg}; color:{$thTextColor};"
        : "border-bottom:2px solid {$showBorderCol}; color:{$thTextColor};";
    $headerCss .= ' ' . $thExtraStyle;

    $hasAnyDiscount = false;
    foreach ($items as $it) {
        if ((float)(is_object($it) ? ($it->discount_amount ?? 0) : ($it['discount_amount'] ?? 0)) > 0.0001) {
            $hasAnyDiscount = true;
            break;
        }
    }
@endphp
<table style="width:100%; border-collapse:collapse; margin:8px 0; {{ $outerBorder !== 'none' ? 'border:' . $outerBorder . ';' : '' }}">
    <thead>
        <tr style="{{ $headerCss }}">
            @if($showRowNum)
                <th style="padding:{{ $cp }}; text-align:center; width:6%; font-weight:600; font-size:{{ $fs }}px; border-right:1px solid {{ $thBordCol }};">Pos.</th>
            @endif
            @if($showItemCodes)
                <th style="padding:{{ $cp }}; text-align:left; width:12%; font-weight:600; font-size:{{ $fs }}px; white-space:nowrap; border-right:1px solid {{ $thBordCol }};">Produkt-Nr.</th>
            @endif
            <th style="padding:{{ $cp }}; text-align:left; font-weight:600; font-size:{{ $fs }}px; border-right:1px solid {{ $thBordCol }};">Beschreibung</th>
            @if($showUnit)
                <th style="padding:{{ $cp }}; text-align:center; width:9%; font-weight:600; font-size:{{ $fs }}px; border-right:1px solid {{ $thBordCol }};">Einheit</th>
            @endif
            <th style="padding:{{ $cp }}; text-align:right; width:9%; font-weight:600; font-size:{{ $fs }}px; border-right:1px solid {{ $thBordCol }};">Anzahl</th>
            @if($hasAnyDiscount)
                <th style="padding:{{ $cp }}; text-align:right; width:12%; font-weight:600; font-size:{{ $fs }}px; border-right:1px solid {{ $thBordCol }}; {{ $thBg ? '' : 'color:#dc2626;' }}">Rabatt</th>
            @endif
            <th style="padding:{{ $cp }}; text-align:right; width:12%; font-weight:600; font-size:{{ $fs }}px; border-right:1px solid {{ $thBordCol }};">Preis</th>
            <th style="padding:{{ $cp }}; text-align:right; width:13%; font-weight:600; font-size:{{ $fs }}px;">Summe</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $i => $item)
            @php
                $isObj        = is_object($item);
                $rowBg        = ($altBg && $i % 2 === 1) ? "background-color:{$altBg};" : '';
                $borderBottom = $outerBorder !== 'none'
                    ? "border-bottom:1px solid {$showBorderCol};"
                    : 'border-bottom:1px solid #e5e7eb;';
                $cellBorder   = $outerBorder !== 'none'
                    ? "border-right:1px solid {$showBorderCol};"
                    : '';
                $itemDesc     = $isObj ? ($item->description ?? $item->name ?? '') : ($item['description'] ?? $item['name'] ?? '');
                $itemLongDesc = $isObj ? ($item->long_description ?? '') : ($item['long_description'] ?? '');
                $qty          = $isObj ? ($item->quantity ?? 1) : ($item['quantity'] ?? 1);
                $price        = $isObj ? ($item->unit_price ?? $item->price ?? 0) : ($item['unit_price'] ?? $item['price'] ?? 0);
                $itemTotal    = $isObj ? ($item->total ?? (float)$qty * (float)$price) : ($item['total'] ?? (float)$qty * (float)$price);
                $unit         = $isObj ? ($item->unit ?? '') : ($item['unit'] ?? '');
                $discount     = $isObj ? ($item->discount_percent ?? 0) : ($item['discount_percent'] ?? 0);
                $discAmount   = $isObj ? ($item->discount_amount ?? 0) : ($item['discount_amount'] ?? 0);
                $sku          = $isObj
                    ? ($item->product->number ?? $item->product->sku ?? ($item->sku ?? ''))
                    : ($item['sku'] ?? '');
            @endphp
            <tr style="{{ $borderBottom }} {{ $rowBg }}">
                @if($showRowNum)
                    <td style="padding:{{ $cp }}; text-align:center; {{ $cellBorder }}">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</td>
                @endif
                @if($showItemCodes)
                    <td style="padding:{{ $cp }}; color:#6b7280; {{ $cellBorder }}">{{ $sku ?: '–' }}</td>
                @endif
                <td style="padding:{{ $cp }}; {{ $cellBorder }}">
                    <div style="font-weight:600; white-space:pre-wrap;">{!! nl2br(e($itemDesc)) !!}</div>
                    @if($itemLongDesc)
                        <div style="font-size:{{ $fs - 1 }}px; color:#6b7280; margin-top:1px; white-space:pre-wrap;">{!! nl2br(e($itemLongDesc)) !!}</div>
                    @endif
                </td>
                @if($showUnit)
                    <td style="padding:{{ $cp }}; text-align:center; {{ $cellBorder }}">{{ $unit ?: 'Stk.' }}</td>
                @endif
                <td style="padding:{{ $cp }}; text-align:right; {{ $cellBorder }}">{{ number_format((float)$qty, 2, ',', '.') }}</td>
                @if($hasAnyDiscount)
                    <td style="padding:{{ $cp }}; text-align:right; {{ $cellBorder }} color:{{ $discAmount > 0 ? '#dc2626' : '#9ca3af' }};">
                        @if($discAmount > 0.0001)
                            @if($discount > 0)
                                -{{ number_format((float)$discount, 0) }}%<br>
                                <span style="font-size:{{ $fs - 1 }}px;">(-{{ number_format((float)$discAmount, 2, ',', '.') }} €)</span>
                            @else
                                -{{ number_format((float)$discAmount, 2, ',', '.') }} €
                            @endif
                        @else
                            <span style="color:#d1d5db;">—</span>
                        @endif
                    </td>
                @endif
                <td style="padding:{{ $cp }}; text-align:right; {{ $cellBorder }}">{{ number_format((float)$price, 2, ',', '.') }} €</td>
                <td style="padding:{{ $cp }}; text-align:right; font-weight:600;">{{ number_format((float)$itemTotal, 2, ',', '.') }} €</td>
            </tr>
        @endforeach
    </tbody>
</table>
