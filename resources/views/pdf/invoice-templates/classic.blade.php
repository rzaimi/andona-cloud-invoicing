{{-- Classic: Dark full-width letterhead band + centered title + fully bordered info table --}}
{{-- DISTINCTIVE: Navy/dark header band, logo right in band, centered uppercase title, bordered grid info panel --}}
@php
    $ls      = $layoutSettings;
    $primary = $ls['colors']['primary']   ?? '#1a2e44';
    $accent  = $ls['colors']['accent']    ?? '#f3f4f6';
    $textCol = $ls['colors']['text']      ?? '#1f2937';
    $secCol  = $ls['colors']['secondary'] ?? '#374151';
    $fs      = $bodyFontSize;
    $fsH     = $headingFontSize;

    // Logo
    $logoRelPath = isset($snapshot['logo']) ? ltrim(preg_replace('#^storage/#', '', (string)$snapshot['logo']), '/') : null;
    $logoSrc = null;
    if ($logoRelPath && ($ls['branding']['show_logo'] ?? true)) {
        if (isset($preview) && $preview) {
            $logoSrc = asset('storage/' . $logoRelPath);
        } elseif (\Storage::disk('public')->exists($logoRelPath)) {
            $lp = \Storage::disk('public')->path($logoRelPath);
            $logoSrc = 'data:' . mime_content_type($lp) . ';base64,' . base64_encode(file_get_contents($lp));
        }
    }
    $showLogo = !empty($logoSrc);

    $isCorrection     = isset($invoice->is_correction) && (bool)$invoice->is_correction;
    $invoiceTypeLabel = $isCorrection
        ? 'STORNORECHNUNG'
        : strtoupper(getReadableInvoiceType($invoice->invoice_type ?? 'standard', $invoice->sequence_number ?? null));
    $customer         = $invoice->customer ?? null;
    $skontoAmount     = (float)($invoice->skonto_amount ?? 0);
    $hasSkonto        = !empty($invoice->skonto_percent) && !empty($invoice->skonto_days) && $skontoAmount > 0;
@endphp
<div class="container">

{{-- ══ DARK LETTERHEAD BAND ═══════════════════════════════════════════════ --}}
<table style="width:100%; border-collapse:collapse; background-color:{{ $primary }}; margin-bottom:6mm;">
    <tr>
        <td style="padding:6mm 7mm; vertical-align:middle;">
            <div style="color:white; font-size:{{ $fsH + 6 }}px; font-weight:800; line-height:1.15; letter-spacing:0.5px;">
                {{ $snapshot['name'] ?? '' }}
            </div>
            @if($ls['content']['show_company_address'] ?? true)
                <div style="color:rgba(255,255,255,0.65); font-size:{{ $fs - 1 }}px; margin-top:2mm; line-height:1.4;">
                    @if($snapshot['address'] ?? null){{ $snapshot['address'] }} &ensp;·&ensp; @endif
                    @if(($snapshot['postal_code'] ?? null) && ($snapshot['city'] ?? null)){{ $snapshot['postal_code'] }} {{ $snapshot['city'] }}@endif
                    @if($snapshot['phone'] ?? null) &ensp;·&ensp; Tel. {{ $snapshot['phone'] }}@endif
                    @if($snapshot['email'] ?? null) &ensp;·&ensp; {{ $snapshot['email'] }}@endif
                </div>
            @endif
        </td>
        @if($showLogo)
        <td style="padding:6mm 7mm; text-align:right; vertical-align:middle; width:35%;">
            <img src="{{ $logoSrc }}" alt="Logo" style="max-height:20mm; max-width:60mm;">
        </td>
        @endif
    </tr>
</table>

{{-- ══ CENTERED DOCUMENT TITLE ════════════════════════════════════════════ --}}
<div style="text-align:center; margin-bottom:6mm; padding-bottom:4mm; border-bottom:2px solid {{ $textCol }};">
    <div style="font-size:{{ $fsH + 7 }}px; font-weight:800; color:{{ $isCorrection ? '#dc2626' : $textCol }}; letter-spacing:2px; text-transform:uppercase;">
        {{ $isCorrection ? 'STORNORECHNUNG' : $invoiceTypeLabel }}
    </div>
    <div style="font-size:{{ $fs + 1 }}px; color:{{ $secCol }}; margin-top:4px; letter-spacing:0.5px;">
        Nummer: <strong>{{ $invoice->number }}</strong>
    </div>
</div>

{{-- ══ ADDRESS BLOCK (left) + BORDERED INFO TABLE (right) ════════════════ --}}
<table style="width:100%; border-collapse:collapse; margin-bottom:8mm;">
    <tr>
        <td style="width:52%; vertical-align:top; padding-right:8mm;">
            @if($customer)
            <div class="din-5008-address">
                @if($ls['content']['show_company_address'] ?? true)
                    <div class="sender-return-address">
                        {{ $snapshot['name'] ?? '' }} · {{ $snapshot['address'] ?? '' }} · {{ $snapshot['postal_code'] ?? '' }} {{ $snapshot['city'] ?? '' }}
                    </div>
                @endif
                <div style="font-weight:700; font-size:{{ $fs }}px; line-height:1.3; margin-bottom:1mm;">{{ $customer->name ?? '' }}</div>
                @if($customer->contact_person ?? null)
                    <div style="font-size:{{ $fs }}px; line-height:1.3; margin-bottom:1mm;">{{ $customer->contact_person }}</div>
                @endif
                <div style="font-size:{{ $fs }}px; line-height:1.35;">
                    @if($customer->address){{ $customer->address }}<br>@endif
                    @if($customer->postal_code && $customer->city)
                        {{ $customer->postal_code }} {{ $customer->city }}
                        @if($customer->country && $customer->country !== 'Deutschland')<br>{{ $customer->country }}@endif
                    @endif
                </div>
            </div>
            @endif
        </td>
        <td style="width:48%; vertical-align:top;">
            {{-- Fully bordered info grid --}}
            <table style="width:100%; border-collapse:collapse; border:1.5px solid {{ $textCol }}; font-size:{{ $fs - 1 }}px;">
                <tr>
                    <td style="padding:5px 8px; color:{{ $secCol }}; border-bottom:1px solid {{ $textCol }}; border-right:1px solid {{ $textCol }}; width:44%;">Rechnungsnr.</td>
                    <td style="padding:5px 8px; font-weight:700; border-bottom:1px solid {{ $textCol }};">{{ $invoice->number }}</td>
                </tr>
                <tr>
                    <td style="padding:5px 8px; color:{{ $secCol }}; border-bottom:1px solid {{ $textCol }}; border-right:1px solid {{ $textCol }};">Datum</td>
                    <td style="padding:5px 8px; font-weight:600; border-bottom:1px solid {{ $textCol }};">{{ formatInvoiceDate($invoice->issue_date, $dateFormat ?? 'd.m.Y') }}</td>
                </tr>
                <tr>
                    <td style="padding:5px 8px; color:{{ $secCol }}; border-bottom:1px solid {{ $textCol }}; border-right:1px solid {{ $textCol }};">Fälligkeit</td>
                    <td style="padding:5px 8px; font-weight:600; border-bottom:1px solid {{ $textCol }};">{{ formatInvoiceDate($invoice->due_date, $dateFormat ?? 'd.m.Y') }}</td>
                </tr>
                @if($hasSkonto)
                <tr>
                    <td style="padding:5px 8px; color:#16a34a; border-bottom:1px solid {{ $textCol }}; border-right:1px solid {{ $textCol }};">Skonto bis</td>
                    <td style="padding:5px 8px; font-weight:600; color:#16a34a; border-bottom:1px solid {{ $textCol }};">{{ formatInvoiceDate($invoice->skonto_due_date, $dateFormat ?? 'd.m.Y') }}</td>
                </tr>
                @endif
                @if($customer && !empty($customer->number))
                <tr>
                    <td style="padding:5px 8px; color:{{ $secCol }}; border-bottom:1px solid {{ $textCol }}; border-right:1px solid {{ $textCol }};">Kundennr.</td>
                    <td style="padding:5px 8px; font-weight:600; border-bottom:1px solid {{ $textCol }};">{{ $customer->number }}</td>
                </tr>
                @endif
                @if(!empty($invoice->service_date))
                <tr>
                    <td style="padding:5px 8px; color:{{ $secCol }}; border-bottom:1px solid {{ $textCol }}; border-right:1px solid {{ $textCol }};">Leistungsdatum</td>
                    <td style="padding:5px 8px; font-weight:600; border-bottom:1px solid {{ $textCol }};">{{ formatInvoiceDate($invoice->service_date, $dateFormat ?? 'd.m.Y') }}</td>
                </tr>
                @elseif(!empty($invoice->service_period_start) && !empty($invoice->service_period_end))
                <tr>
                    <td style="padding:5px 8px; color:{{ $secCol }}; border-bottom:1px solid {{ $textCol }}; border-right:1px solid {{ $textCol }};">Leistungszeitraum</td>
                    <td style="padding:5px 8px; font-weight:600; border-bottom:1px solid {{ $textCol }};">{{ formatInvoiceDate($invoice->service_period_start, $dateFormat ?? 'd.m.Y') }}–{{ formatInvoiceDate($invoice->service_period_end, $dateFormat ?? 'd.m.Y') }}</td>
                </tr>
                @endif
                @if(!empty($invoice->bauvorhaben) && ($ls['content']['show_bauvorhaben'] ?? true))
                <tr>
                    <td style="padding:5px 8px; color:{{ $secCol }}; border-right:1px solid {{ $textCol }};">BV</td>
                    <td style="padding:5px 8px; font-weight:700; color:{{ $primary }};">{{ $invoice->bauvorhaben }}</td>
                </tr>
                @endif
            </table>
        </td>
    </tr>
</table>

{{-- Storno notice --}}
@if($isCorrection && isset($invoice->correctsInvoice) && $invoice->correctsInvoice)
    <div style="margin-bottom:5mm; padding:8px 12px; background:#fee2e2; border:2px solid #dc2626; font-size:{{ $fs }}px;">
        <div style="font-weight:600; color:#991b1b; margin-bottom:3px;">Storniert Rechnung:</div>
        <div style="color:#7f1d1d;">Nr. {{ $invoice->correctsInvoice->number }} vom {{ formatInvoiceDate($invoice->correctsInvoice->issue_date, $dateFormat ?? 'd.m.Y') }}</div>
        @if(!empty($invoice->correction_reason))
            <div style="margin-top:4px; padding-top:4px; border-top:1px solid #dc2626;"><strong>Grund:</strong> {{ $invoice->correction_reason }}</div>
        @endif
    </div>
@endif

{{-- ══ INTRO ═══════════════════════════════════════════════════════════════ --}}
<div style="margin-bottom:5mm; font-size:{{ $fs }}px; line-height:1.6;">
    <div style="margin-bottom:3px;">Sehr geehrte Damen und Herren,</div>
    <div>vielen Dank für Ihren Auftrag. Hiermit stellen wir Ihnen folgende Leistungen in Rechnung:</div>
</div>

{{-- ══ ITEMS TABLE: full borders, position numbers, alternating rows ═══════ --}}
@php
    $tableHeaderBg    = $textCol;
    $tableOuterBorder = '1.5px solid ' . $textCol;
    $altRowBg         = $accent;
    $cellPadding      = '7px 8px';
    $showRowNumber    = true;
@endphp
@include('pdf.invoice-partials.items-table')

{{-- VAT note --}}
@php $vatNote = getVatRegimeNote($invoice->vat_regime ?? 'standard'); @endphp
@if($vatNote)
    <div style="margin-top:6px; font-size:{{ $fs }}px; font-style:italic;">{{ $vatNote }}</div>
@endif

{{-- ══ TOTALS: bordered box ════════════════════════════════════════════════ --}}
<div style="margin-top:8px;">
    @php $tableWidth = '290px'; $totalRowBg = $textCol; @endphp
    @include('pdf.invoice-partials.totals')
</div>

{{-- ══ PAYMENT ═════════════════════════════════════════════════════════════ --}}
@include('pdf.invoice-partials.payment-terms')

{{-- ══ CLOSING ═════════════════════════════════════════════════════════════ --}}
<div style="margin-top:8mm; font-size:{{ $fs }}px;">
    <div style="margin-bottom:3px;">Mit freundlichen Grüßen</div>
    <div style="font-weight:600;">{{ $snapshot['name'] ?? '' }}</div>
</div>

@if($ls['branding']['show_footer'] ?? true)
    @include('pdf.invoice-partials.footer')
@endif
</div>
