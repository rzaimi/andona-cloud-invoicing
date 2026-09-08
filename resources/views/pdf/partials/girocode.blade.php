{{-- Girocode (EPC-QR): scan with a banking app to pre-fill the SEPA transfer.
     Rendered only for invoices, when the layout enables it, bank data exists,
     and something is still owed. --}}
@php
    $qrHolder = $bankHolder ?: ($snapshot['name'] ?? '');
    $qrOpenAmount = method_exists($doc, 'getRemainingBalance') ? $doc->getRemainingBalance() : (float) ($doc->total ?? 0);
    $girocodePngData = null;

    $qrIsInvoice = ($docKind ?? 'invoice') === 'invoice';

    if ($qrIsInvoice && ($ls['content']['show_payment_qr'] ?? true) && $bankIban && $qrHolder && $qrOpenAmount > 0) {
        $girocodePngData = app(\App\Services\GirocodeService::class)->png(
            (string) $qrHolder,
            (string) $bankIban,
            round($qrOpenAmount, 2),
            'Rechnung '.$doc->number
        );
    }
@endphp
@if($girocodePngData)
<table style="margin-top:5mm; page-break-inside:avoid; border-collapse:collapse;">
    <tr>
        <td style="vertical-align:middle; padding-right:4mm;">
            <img src="data:image/png;base64,{{ base64_encode($girocodePngData) }}" style="width:22mm; height:22mm;" alt="Girocode">
        </td>
        <td style="vertical-align:middle; font-size:{{ ($fs ?? 9) - 1 }}px; color:{{ $soft ?? '#666' }}; line-height:1.6;">
            <strong style="color:{{ $primary ?? '#111' }}; font-weight:500;">Bezahlen per Girocode</strong><br>
            QR-Code mit Ihrer Banking-App scannen &ndash; Empf&auml;nger, Betrag und
            Verwendungszweck sind bereits ausgef&uuml;llt.
        </td>
    </tr>
</table>
@endif
