{{--
    Shared 5-column footer for invoices and offers.
    Expects: $snapshot (array), $layoutSettings (array)
--}}
@php
    $ls        = $layoutSettings ?? [];
    $lineColor = $ls['colors']['primary']   ?? '#1e40af';
    $fgColor   = $ls['colors']['secondary'] ?? '#374151';
    $showLine  = $ls['branding']['show_footer_line'] ?? true;
    $showBank  = $ls['content']['show_bank_details'] ?? true;
    $showReg   = $ls['content']['show_company_registration'] ?? true;
@endphp
<div class="pdf-footer" style="
    position: fixed;
    bottom: 0; left: 0; right: 0;
    width: 100%;
    padding: 4px 8mm 3px 8mm;
    background-color: white;
    border-top: {{ $showLine ? '1.5px solid ' . $lineColor : '1px solid #e5e7eb' }};
    z-index: 1000;
">
    <table style="width:100%; border-collapse:collapse; font-size:6.5pt; line-height:1.35; color:{{ $fgColor }};">
        <tr>

            {{-- ── Col 1: Company name + address ───────────────────────── --}}
            <td style="width:19%; vertical-align:top; padding-right:3px;">
                @if($snapshot['name'] ?? null)
                    <div style="font-weight:bold; color:#111827;">{{ $snapshot['name'] }}</div>
                @endif
                @if($snapshot['address'] ?? null)
                    <div>{{ $snapshot['address'] }}</div>
                @endif
                @if(($snapshot['postal_code'] ?? null) || ($snapshot['city'] ?? null))
                    <div>{{ trim(($snapshot['postal_code'] ?? '') . ' ' . ($snapshot['city'] ?? '')) }}</div>
                @endif
            </td>

            {{-- ── Col 2: Kontakt (Tel, Fax, Email, Web) ───────────────── --}}
            <td style="width:21%; vertical-align:top; padding-right:3px;">
                <div style="font-weight:bold; color:#111827;">Kontakt:</div>
                @if($snapshot['phone'] ?? null)
                    <div>Tel.: {{ $snapshot['phone'] }}</div>
                @endif
                @if($snapshot['fax'] ?? null)
                    <div>Fax: {{ $snapshot['fax'] }}</div>
                @endif
                @if($snapshot['email'] ?? null)
                    <div>{{ $snapshot['email'] }}</div>
                @endif
                @if($snapshot['website'] ?? null)
                    <div>{{ $snapshot['website'] }}</div>
                @endif
            </td>

            {{-- ── Col 3: Ust-IdNr., Finanzamt, St.Nr. ────────────────── --}}
            <td style="width:18%; vertical-align:top; padding-right:3px;">
                @if($snapshot['vat_number'] ?? null)
                    <div><span style="font-weight:bold;">Ust-IdNr.:</span> {{ $snapshot['vat_number'] }}</div>
                @endif
                @if($snapshot['tax_office'] ?? null)
                    <div><span style="font-weight:bold;">Finanzamt:</span> {{ $snapshot['tax_office'] }}</div>
                @endif
                @if($snapshot['tax_number'] ?? null)
                    <div><span style="font-weight:bold;">St.Nr.:</span> {{ $snapshot['tax_number'] }}</div>
                @endif
            </td>

            {{-- ── Col 4: Bankverbindung ────────────────────────────────── --}}
            @if($showBank)
            <td style="width:27%; vertical-align:top; padding-right:3px;">
                <div style="font-weight:bold; color:#111827;">Bankverbindung:</div>
                @if($snapshot['bank_name'] ?? null)
                    <div>{{ $snapshot['bank_name'] }}</div>
                @endif
                @if($snapshot['bank_bic'] ?? null)
                    <div>BIC: {{ $snapshot['bank_bic'] }}</div>
                @endif
                @if($snapshot['bank_iban'] ?? null)
                    <div style="word-break:break-all;">IBAN: {{ $snapshot['bank_iban'] }}</div>
                @endif
            </td>
            @endif

            {{-- ── Col 5: Amtsgericht + Geschäftsführer ────────────────── --}}
            @if($showReg)
            <td style="width:15%; vertical-align:top;">
                @if($snapshot['commercial_register'] ?? null)
                    <div style="font-weight:bold; color:#111827;">Amtsgericht:</div>
                    <div>{{ $snapshot['commercial_register'] }}</div>
                @endif
                @if($snapshot['managing_director'] ?? null)
                    <div style="font-weight:bold; color:#111827; margin-top:2px;">Gesch&auml;ftsf&uuml;hrer:</div>
                    <div>{{ $snapshot['managing_director'] }}</div>
                @endif
                @if(!empty($ls['content']['custom_footer_text'] ?? ''))
                    <div style="margin-top:2px;">{{ $ls['content']['custom_footer_text'] }}</div>
                @endif
            </td>
            @endif

        </tr>
    </table>
</div>
