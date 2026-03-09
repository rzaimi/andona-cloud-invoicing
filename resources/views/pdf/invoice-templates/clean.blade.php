{{-- Clean: Light gray header section, white cards for address and info, very light alternating rows --}}
{{-- DISTINCTIVE: Full-width gray header band, info in gray-background card, crisp organized layout --}}
@php
    $ls      = $layoutSettings;
    $primary = $ls['colors']['primary']   ?? '#0f766e';
    $accent  = $ls['colors']['accent']    ?? '#f0fdf4';
    $textCol = $ls['colors']['text']      ?? '#1f2937';
    $secCol  = $ls['colors']['secondary'] ?? '#6b7280';
    $fs      = $bodyFontSize;
    $fsH     = $headingFontSize;
    $gray1   = '#f3f4f6';
    $gray2   = '#e5e7eb';

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
    $customer = $invoice->customer ?? null;
@endphp
<div class="container">

{{-- ══ GRAY HEADER BAND: logo left, company info right ══════════════════ --}}
<table style="width:100%; border-collapse:collapse; background-color:{{ $gray1 }}; border-bottom:3px solid {{ $primary }}; margin-bottom:7mm; padding:5mm 6mm;">
    <tr>
        @if($showLogo)
        <td style="padding:5mm 6mm; vertical-align:middle; width:38%;">
            <img src="{{ $logoSrc }}" alt="Logo" style="max-height:20mm; max-width:62mm;">
        </td>
        @endif
        <td style="padding:5mm 6mm; vertical-align:middle; {{ $showLogo ? 'text-align:right;' : '' }}">
            <div style="font-size:{{ $fsH + 5 }}px; font-weight:800; color:{{ $textCol }}; line-height:1.15; margin-bottom:1mm;">
                {{ $snapshot['name'] ?? '' }}
            </div>
            @if($ls['content']['show_company_address'] ?? true)
                <div style="font-size:{{ $fs - 1 }}px; color:{{ $secCol }}; line-height:1.4;">
                    @if($snapshot['address'] ?? null){{ $snapshot['address'] }}@endif
                    @if(($snapshot['postal_code'] ?? null) && ($snapshot['city'] ?? null)) &ensp;·&ensp; {{ $snapshot['postal_code'] }} {{ $snapshot['city'] }}@endif
                    @if($snapshot['phone'] ?? null)<br>Tel. {{ $snapshot['phone'] }}@endif
                    @if($snapshot['email'] ?? null)@if($snapshot['phone'] ?? null) &ensp;·&ensp; @else<br>@endif{{ $snapshot['email'] }}@endif
                </div>
            @endif
        </td>
    </tr>
</table>

{{-- ══ ADDRESS + INFO PANEL (gray background) ════════════════════════════ --}}
<table style="width:100%; border-collapse:collapse; margin-bottom:8mm;">
    <tr>
        <td style="width:52%; vertical-align:top; padding-right:6mm;">
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
                    @if(isset($customer->vat_number) && $customer->vat_number)
                        <br>USt-IdNr.: {{ $customer->vat_number }}
                    @endif
                </div>
            </div>
            @endif
        </td>
        <td style="width:48%; vertical-align:top;">
            {{-- Gray info card --}}
            @php
                $detailsBg          = $gray1;
                $detailsPad         = '5px 10px';
                $detailsLabelColor  = $secCol;
                $detailsBorderColor = $gray2;
                $detailsTableStyle  = 'border-top:3px solid ' . $primary . ';';
            @endphp
            @include('pdf.invoice-partials.details')
        </td>
    </tr>
</table>

{{-- ══ DOCUMENT TITLE with accent underline ════════════════════════════════ --}}
<div style="margin-bottom:5mm; padding-bottom:3mm; border-bottom:3px solid {{ $primary }};">
    <div style="font-size:{{ $fsH + 5 }}px; font-weight:800; color:{{ $isCorrection ? '#dc2626' : $textCol }};">
        {{ $isCorrection ? 'STORNORECHNUNG' : $invoiceTypeLabel }}
        <span style="font-size:{{ $fs + 1 }}px; font-weight:400; color:{{ $secCol }}; margin-left:4px;">{{ $invoice->number }}</span>
    </div>
    @if($isCorrection && isset($invoice->correctsInvoice) && $invoice->correctsInvoice)
        <div style="margin-top:6px; padding:8px 12px; background:#fee2e2; border-left:4px solid #dc2626; font-size:{{ $fs }}px;">
            <div style="font-weight:600; color:#991b1b; margin-bottom:3px;">Storniert Rechnung:</div>
            <div style="color:#7f1d1d;">Nr. {{ $invoice->correctsInvoice->number }} vom {{ formatInvoiceDate($invoice->correctsInvoice->issue_date, $dateFormat ?? 'd.m.Y') }}</div>
            @if(!empty($invoice->correction_reason))
                <div style="margin-top:4px; padding-top:4px; border-top:1px solid #dc2626;"><strong>Grund:</strong> {{ $invoice->correction_reason }}</div>
            @endif
        </div>
    @endif
</div>

{{-- ══ INTRO ═══════════════════════════════════════════════════════════════ --}}
<div style="margin-bottom:5mm; font-size:{{ $fs }}px; line-height:1.6;">
    <div style="margin-bottom:3px;">Sehr geehrte Damen und Herren,</div>
    <div>vielen Dank für Ihren Auftrag. Hiermit stellen wir Ihnen folgende Leistungen in Rechnung:</div>
</div>

{{-- ══ ITEMS TABLE: very light alternating rows ═══════════════════════════ --}}
@php
    $tableHeaderBg        = $gray1;
    $tableHeaderTextColor = $textCol;
    $tableHeaderStyle     = 'border-top:2px solid ' . $primary . '; border-bottom:2px solid ' . $primary . ';';
    $altRowBg             = $gray1;
    $cellPadding          = '8px 7px';
@endphp
@include('pdf.invoice-partials.items-table')

{{-- VAT note --}}
@php $vatNote = getVatRegimeNote($invoice->vat_regime ?? 'standard'); @endphp
@if($vatNote)
    <div style="margin-top:6px; font-size:{{ $fs }}px; font-style:italic; color:{{ $secCol }};">{{ $vatNote }}</div>
@endif

{{-- ══ TOTALS: clean bordered box ══════════════════════════════════════════ --}}
<div style="margin-top:8px;">
    @php $tableWidth = '290px'; $totalRowBg = $primary; @endphp
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
