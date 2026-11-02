{{--
    Offer letter after the totals: tax notice (depends on vat_regime),
    closing, and the customer's acceptance block.
    Expects: $doc, $fs, $ls, $snapshot. Optional: $senderName, $primary, $muted, $ink.
--}}
@php
    $fs      = $fs ?? ($bodyFontSize ?? 13);
    $ls      = $ls ?? ($layoutSettings ?? []);
    $primary = $primary ?? ($ls['colors']['primary'] ?? '#0e7490');
    $ink     = $ink ?? ($ls['colors']['text'] ?? '#0f172a');
    $muted   = $muted ?? ($ls['colors']['secondary'] ?? '#64748b');
    $regime  = $doc->vat_regime ?? 'standard';
    $sender  = $senderName ?? ($snapshot['display_name'] ?? $snapshot['name'] ?? '');
    $domesticReverseCharge = $regime === 'reverse_charge_domestic';
@endphp

@if($domesticReverseCharge)
<div style="margin-top:6mm; font-size:{{ $fs }}px; line-height:1.6; color:{{ $ink }};">
    Rechnung nach § 13b UStG. Die Umsatzsteuer für diese umsatzsteuerpflichtige Leistung schuldet der Auftraggeber.
</div>
@else
@include('pdf.partials.vat-regime-legal-notice', [
    'vat_regime'  => $regime,
    'fontSizePx'  => $fs + 1,
    'mutedColor'  => $ink,
    'align'       => 'left',
    'fullWidth'   => true,
])
@endif

@if(($ls['content']['show_notes'] ?? true) && !empty($doc->notes))
<div style="margin-top:5mm; font-size:{{ $fs - 1 }}px; color:{{ $muted }}; line-height:1.65; white-space:pre-wrap;">{{ $doc->notes }}</div>
@endif

@if($ls['content']['show_closing'] ?? true)
<div style="margin-top:6mm; font-size:{{ $fs }}px; line-height:1.7; color:{{ $ink }}; page-break-inside:avoid;">
    <div>Es gelten unsere allgemeinen Geschäftsbedingungen.</div>
    <div>Wenn Sie weitere Informationen benötigen, zögern Sie nicht, uns zu kontaktieren. Wir beraten Sie gerne.</div>
    <div style="margin-top:5mm;">Vielen Dank für Ihren Auftrag!</div>
    <div>Mit freundlichen Grüßen</div>
    @if(($ls['content']['show_signature'] ?? true) && $sender !== '')
    <div style="margin-top:4mm; font-size:{{ $fs + 1 }}px; font-weight:700; color:{{ $primary }};">{{ $sender }}</div>
    @endif
</div>
@endif

<table style="width:100%; border-collapse:collapse; margin-top:8mm; page-break-inside:avoid;">
    <tr>
        <td style="background:{{ $primary }}; color:#ffffff; padding:2.5mm 5mm; font-size:{{ max(9, $fs - 1) }}px; font-weight:700; letter-spacing:0.8px;">
            ANGEBOTSBESTÄTIGUNG
        </td>
    </tr>
    <tr>
        <td style="border:0.3mm solid {{ $primary }}; border-top:none; padding:4.5mm 5mm 5mm 5mm; font-size:{{ $fs }}px; line-height:1.6; color:{{ $ink }};">
            <div>Hiermit bestätige ich als Auftraggeber das Angebot und erteile den Auftrag.</div>
            <table style="width:100%; border-collapse:collapse; margin-top:7mm;">
                <tr>
                    <td style="width:38%; vertical-align:bottom; padding-right:8mm;">
                        <div style="border-bottom:0.4mm solid {{ $ink }}; width:100%; height:10mm; line-height:10mm;">&nbsp;</div>
                        <div style="margin-top:1.5mm; font-size:{{ max(8, $fs - 2) }}px; color:{{ $muted }}; letter-spacing:0.4px;">Datum</div>
                    </td>
                    <td style="width:62%; vertical-align:bottom;">
                        <div style="border-bottom:0.4mm solid {{ $ink }}; width:100%; height:10mm; line-height:10mm;">&nbsp;</div>
                        <div style="margin-top:1.5mm; font-size:{{ max(8, $fs - 2) }}px; color:{{ $muted }}; letter-spacing:0.4px;">Unterschrift Auftraggeber</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

@if(!empty($doc->terms_conditions))
<div style="margin-top:6mm; font-size:{{ $fs - 1 }}px; line-height:1.65; color:{{ $muted }};">
    {!! nl2br(e($doc->terms_conditions)) !!}
</div>
@endif
