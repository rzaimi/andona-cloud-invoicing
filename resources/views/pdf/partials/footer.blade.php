{{--
    Shared 5-column footer for invoices and offers.
    Expects: $snapshot (array), $layoutSettings (array)
--}}
@php
    $ls        = $layoutSettings ?? [];
    $lineColor = $ls['colors']['primary']   ?? '#1e40af';
    $fgColor   = $ls['colors']['secondary'] ?? '#374151';
    $showBank  = $ls['content']['show_bank_details'] ?? true;
    $showReg   = $ls['content']['show_company_registration'] ?? true;
@endphp
<div class="pdf-footer" style="
    position: fixed;
    bottom: 0; left: 0; right: 0;
    width: 100%;
    padding: 5mm 8mm 4mm 8mm;
    background-color: white;
    border-top: 1.5px solid {{ $lineColor }};
    z-index: 1000;
">
    <table style="width:100%; border-collapse:collapse; font-size:8pt; line-height:1.5; color:{{ $fgColor }};">
        <tr>

            {{-- ── Col 1: Company name + legal form + address ──────────── --}}
            <td style="width:19%; vertical-align:top; padding-right:4mm;">
                @if($snapshot['name'] ?? null)
                    <div style="font-weight:bold; color:#111827; font-size:8.5pt;">
                        {{ $snapshot['name'] }}
                    </div>
                @endif
                {{-- Rechtsform (Pflichtangabe HGB §37a / GmbHG §35a) --}}
                @if($snapshot['legal_form_label'] ?? null)
                    <div>{{ $snapshot['legal_form_label'] }}</div>
                @endif
                @if($snapshot['address'] ?? null)
                    <div>{{ $snapshot['address'] }}</div>
                @endif
                @if(($snapshot['postal_code'] ?? null) || ($snapshot['city'] ?? null))
                    <div>{{ trim(($snapshot['postal_code'] ?? '') . ' ' . ($snapshot['city'] ?? '')) }}</div>
                @endif
            </td>

            {{-- ── Col 2: Kontakt (Tel, Fax, Email, Web) ───────────────── --}}
            <td style="width:21%; vertical-align:top; padding-right:4mm;">
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
            <td style="width:18%; vertical-align:top; padding-right:4mm;">
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
            <td style="width:26%; vertical-align:top; padding-right:4mm;">
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

            {{-- ── Col 5: Amtsgericht + Geschäftsführer / Inhaber ───────── --}}
            @if($showReg)
            <td style="width:16%; vertical-align:top; padding-right:8mm;">
                @if($snapshot['commercial_register'] ?? null)
                    <div style="font-weight:bold; color:#111827;">Amtsgericht:</div>
                    <div>{{ $snapshot['commercial_register'] }}</div>
                @endif
                @if($snapshot['managing_director'] ?? null)
                    {{-- Role label (Inhaber / Geschäftsführer / Vorstand /
                         Gesellschafter) is derived from legal_form.
                         Falls back to "Inhaber" if no legal form is set yet. --}}
                    <div style="font-weight:bold; color:#111827; margin-top:2px;">
                        {{ ($snapshot['manager_title'] ?? null) ?: 'Inhaber' }}:
                    </div>
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
