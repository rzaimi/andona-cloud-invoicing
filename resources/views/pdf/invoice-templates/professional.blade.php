{{-- Professional: Split two-tone header — company brand left, document identity right --}}
{{-- DISTINCTIVE: Primary-color left panel (logo + company) + light-accent right panel (title + meta) --}}
@php
    $ls      = $layoutSettings;
    $primary = $ls['colors']['primary']   ?? '#1a3c5e';
    $accent  = $ls['colors']['accent']    ?? '#eef2f7';
    $textCol = $ls['colors']['text']      ?? '#1f2937';
    $secCol  = $ls['colors']['secondary'] ?? '#6b7280';
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

{{-- ══ SPLIT HEADER ════════════════════════════════════════════════════════ --}}
<table style="width:100%; border-collapse:collapse; margin-bottom:9mm;">
    <tr>
        {{-- Left cell: company branding on primary color --}}
        <td style="width:60%; background-color:{{ $primary }}; padding:7mm 8mm; vertical-align:middle;">
            @if($showLogo)
                <div style="margin-bottom:3mm;">
                    <img src="{{ $logoSrc }}" alt="Logo" style="max-height:18mm; max-width:58mm;">
                </div>
            @endif
            <div style="color:white; font-size:{{ $fsH + 5 }}px; font-weight:800; line-height:1.2; margin-bottom:2mm;">
                {{ $snapshot['name'] ?? '' }}
            </div>
            @if($ls['content']['show_company_address'] ?? true)
                <div style="color:rgba(255,255,255,0.72); font-size:{{ $fs - 1 }}px; line-height:1.45;">
                    @if($snapshot['address'] ?? null){{ $snapshot['address'] }}@endif
                    @if(($snapshot['postal_code'] ?? null) && ($snapshot['city'] ?? null)) · {{ $snapshot['postal_code'] }} {{ $snapshot['city'] }}@endif
                    @if($snapshot['phone'] ?? null)<br>Tel. {{ $snapshot['phone'] }}@endif
                    @if($snapshot['email'] ?? null)@if($snapshot['phone'] ?? null) &nbsp;·&nbsp; @else<br>@endif{{ $snapshot['email'] }}@endif
                </div>
            @endif
        </td>
        {{-- Right cell: document identity on light accent --}}
        <td style="width:40%; background-color:{{ $accent }}; padding:7mm 8mm; vertical-align:middle; text-align:right; border-left:4px solid white;">
            <div style="font-size:26px; font-weight:800; color:{{ $isCorrection ? '#dc2626' : $primary }}; letter-spacing:1px; margin-bottom:4mm; line-height:1;">
                {{ $isCorrection ? 'STORNO' : $invoiceTypeLabel }}
            </div>
            <div style="font-size:{{ $fs }}px; color:{{ $textCol }}; line-height:1.8;">
                <span style="color:{{ $secCol }}; font-size:{{ $fs - 1 }}px; display:inline-block; min-width:22mm; text-align:left;">Nr.</span>
                <strong>{{ $invoice->number }}</strong><br>
                <span style="color:{{ $secCol }}; font-size:{{ $fs - 1 }}px; display:inline-block; min-width:22mm; text-align:left;">Datum</span>
                {{ formatInvoiceDate($invoice->issue_date, $dateFormat ?? 'd.m.Y') }}<br>
                <span style="color:{{ $secCol }}; font-size:{{ $fs - 1 }}px; display:inline-block; min-width:22mm; text-align:left;">Fälligkeit</span>
                {{ formatInvoiceDate($invoice->due_date, $dateFormat ?? 'd.m.Y') }}<br>
                @if($hasSkonto)
                    <span style="color:#16a34a; font-size:{{ $fs - 1 }}px; display:inline-block; min-width:22mm; text-align:left;">Skonto bis</span>
                    <span style="color:#16a34a; font-weight:600;">{{ formatInvoiceDate($invoice->skonto_due_date, $dateFormat ?? 'd.m.Y') }}</span><br>
                @endif
                @if($customer && !empty($customer->number))
                    <span style="color:{{ $secCol }}; font-size:{{ $fs - 1 }}px; display:inline-block; min-width:22mm; text-align:left;">Kunden-Nr.</span>
                    {{ $customer->number }}<br>
                @endif
                @if(!empty($invoice->service_date))
                    <span style="color:{{ $secCol }}; font-size:{{ $fs - 1 }}px; display:inline-block; min-width:22mm; text-align:left;">Leistung</span>
                    {{ formatInvoiceDate($invoice->service_date, $dateFormat ?? 'd.m.Y') }}<br>
                @elseif(!empty($invoice->service_period_start) && !empty($invoice->service_period_end))
                    <span style="color:{{ $secCol }}; font-size:{{ $fs - 1 }}px; display:inline-block; min-width:22mm; text-align:left;">Zeitraum</span>
                    {{ formatInvoiceDate($invoice->service_period_start, $dateFormat ?? 'd.m.Y') }}–{{ formatInvoiceDate($invoice->service_period_end, $dateFormat ?? 'd.m.Y') }}<br>
                @endif
                @if(!empty($invoice->bauvorhaben) && ($ls['content']['show_bauvorhaben'] ?? true))
                    <span style="color:{{ $secCol }}; font-size:{{ $fs - 1 }}px; display:inline-block; min-width:22mm; text-align:left;">BV</span>
                    <strong>{{ $invoice->bauvorhaben }}</strong>
                @endif
            </div>
        </td>
    </tr>
</table>

{{-- ══ DIN 5008 ADDRESS BLOCK ═════════════════════════════════════════════ --}}
@if($customer)
<div style="margin-bottom:9mm;">
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
            @if(isset($customer->vat_number) && $customer->vat_number)
                <br>USt-IdNr.: {{ $customer->vat_number }}
            @endif
        </div>
    </div>
</div>
@endif

{{-- Storno notice --}}
@if($isCorrection && isset($invoice->correctsInvoice) && $invoice->correctsInvoice)
    <div style="margin-bottom:6mm; padding:8px 12px; background:#fee2e2; border-left:4px solid #dc2626; font-size:{{ $fs }}px;">
        <div style="font-weight:600; color:#991b1b; margin-bottom:3px;">Storniert Rechnung:</div>
        <div style="color:#7f1d1d;">Nr. {{ $invoice->correctsInvoice->number }} vom {{ formatInvoiceDate($invoice->correctsInvoice->issue_date, $dateFormat ?? 'd.m.Y') }}</div>
        @if(!empty($invoice->correction_reason))
            <div style="margin-top:4px; padding-top:4px; border-top:1px solid #dc2626;"><strong>Grund:</strong> {{ $invoice->correction_reason }}</div>
        @endif
    </div>
@endif

{{-- ══ INTRO ═══════════════════════════════════════════════════════════════ --}}
<div style="margin-bottom:6mm; font-size:{{ $fs }}px; line-height:1.6;">
    <div style="margin-bottom:3px;">Sehr geehrte Damen und Herren,</div>
    <div>vielen Dank für Ihren Auftrag. Hiermit stellen wir Ihnen folgende Leistungen in Rechnung:</div>
</div>

{{-- ══ ITEMS TABLE ═════════════════════════════════════════════════════════ --}}
@php
    $tableHeaderBg = $primary;
    $altRowBg      = $accent;
    $cellPadding   = '8px 8px';
@endphp
@include('pdf.invoice-partials.items-table')

{{-- VAT note --}}
@php $vatNote = getVatRegimeNote($invoice->vat_regime ?? 'standard'); @endphp
@if($vatNote)
    <div style="margin-top:6px; font-size:{{ $fs }}px; font-style:italic;">{{ $vatNote }}</div>
@endif

{{-- ══ TOTALS ═══════════════════════════════════════════════════════════════ --}}
<div style="margin-top:8px;">
    @php $tableWidth = '300px'; $totalRowBg = $primary; @endphp
    @include('pdf.invoice-partials.totals')
</div>

{{-- ══ PAYMENT ═════════════════════════════════════════════════════════════ --}}
@include('pdf.invoice-partials.payment-terms')

{{-- ══ CLOSING ═════════════════════════════════════════════════════════════ --}}
<div style="margin-top:10mm; font-size:{{ $fs }}px;">
    <div style="margin-bottom:3px;">Mit freundlichen Grüßen</div>
    <div style="font-weight:600;">{{ $snapshot['name'] ?? '' }}</div>
</div>

@if($ls['branding']['show_footer'] ?? true)
    @include('pdf.invoice-partials.footer')
@endif
</div>
