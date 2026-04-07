{{--
    Mandatory / typical tax wording on German invoices & offers (non-standard USt regimes).
    Expects: $vat_regime (string), optional $fontSizePx (int), $mutedColor (hex), $align (e.g. 'right' for totals block)
--}}
@php
    $regime = $vat_regime ?? 'standard';
    $fs     = max(8, (int) ($fontSizePx ?? 12) - 1);
    $muted  = $mutedColor ?? '#64748b';
    $ta     = $align ?? 'right';

    // Standard formulations aligned with the UI labels (invoices / offers).
    $lines = match ($regime) {
        'small_business' => [
            'Gemäß § 19 UStG wird keine Umsatzsteuer berechnet.',
        ],
        'reverse_charge' => [
            'Steuerschuldnerschaft des Leistungsempfängers gemäß § 13b UStG (Reverse Charge).',
            'Die Umsatzsteuer ist vom Leistungsempfänger zu entrichten.',
        ],
        'reverse_charge_domestic' => [
            'Die Umsatzsteuer schuldet der Leistungsempfänger gemäß § 13b UStG (Steuerschuldnerschaft des Leistungsempfängers).',
        ],
        'intra_community' => [
            'Steuerfreie innergemeinschaftliche Lieferung gemäß § 4 Nr. 1b UStG i. V. m. § 6a UStG.',
        ],
        'export' => [
            'Steuerfreie Ausfuhrlieferung gemäß § 4 Nr. 1a UStG.',
        ],
        default => [],
    };
@endphp
@if(count($lines))
<div style="margin-top:8px; max-width:{{ $tableWidth ?? '290px' }}; margin-left:auto; text-align:{{ $ta }}; font-size:{{ $fs }}px; line-height:1.45; color:{{ $muted }};">
    @foreach($lines as $line)
        <div>{{ $line }}</div>
    @endforeach
</div>
@endif
