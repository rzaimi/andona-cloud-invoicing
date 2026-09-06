@if(!empty($girocodePng) && isset($message))
<div style="margin: 32px 0; padding: 20px; background: #fafafa; text-align: center;">
    <p style="margin: 0 0 12px; font-size: 14px; font-weight: 600; color: #1a1a1a;">Bequem per Banking-App zahlen</p>
    <img src="{{ $message->embedData($girocodePng, 'girocode.png', 'image/png') }}" alt="Girocode für SEPA-Überweisung" width="140" height="140" style="display: inline-block;">
    <p style="margin: 12px 0 0; font-size: 12px; color: #666;">QR-Code mit Ihrer Banking-App scannen – Empfänger, Betrag und Verwendungszweck sind bereits ausgefüllt.</p>
</div>
@endif
