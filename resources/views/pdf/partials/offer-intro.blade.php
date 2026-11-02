{{--
    Opening of an offer, placed directly under the "Angebot" heading.
    Expects: $fs, $ls. Optional: $ink.
--}}
@php
    $fs   = $fs ?? ($bodyFontSize ?? 13);
    $ls   = $ls ?? ($layoutSettings ?? []);
    $ink  = $ink ?? ($ls['colors']['text'] ?? '#1f2937');
@endphp
@if($ls['content']['show_salutation'] ?? true)
<div style="margin-top:6mm; margin-bottom:5mm; font-size:{{ $fs }}px; line-height:1.7; color:{{ $ink }};">
    Sehr geehrte Damen und Herren,<br><br>
    vielen Dank für Ihre Anfrage und dem damit verbundenen Interesse an unserem Unternehmen.<br>
    Wir möchten Ihnen folgendes Angebot unterbreiten:
</div>
@endif
