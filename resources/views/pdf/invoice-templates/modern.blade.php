{{-- Modern: White header with bold accent bar + colored info box + vivid table header --}}
{{-- DISTINCTIVE: Bold left-border accent on header, company name in primary color, colored details panel --}}
@php
    $ls      = $layoutSettings;
    $primary = $ls['colors']['primary']   ?? '#2563eb';
    $accent  = $ls['colors']['accent']    ?? '#eff6ff';
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
    $customer = $invoice->customer ?? null;
@endphp
<div class="container">

{{-- ══ HEADER: logo + company name with bold left accent border ═══════════ --}}
<div style="border-left:7px solid {{ $primary }}; padding-left:6mm; margin-bottom:7mm; padding-bottom:3mm; border-bottom:1px solid #e5e7eb;">
    @if($showLogo)
        <div style="margin-bottom:3mm;">
            <img src="{{ $logoSrc }}" alt="Logo" style="max-height:22mm; max-width:65mm;">
        </div>
    @endif
    <div style="font-size:{{ $fsH + 6 }}px; font-weight:800; color:{{ $primary }}; line-height:1.1; margin-bottom:2mm;">
        {{ $snapshot['name'] ?? '' }}
    </div>
    @if($ls['content']['show_company_address'] ?? true)
        <div style="font-size:{{ $fs - 1 }}px; color:{{ $secCol }}; line-height:1.45;">
            @if($snapshot['address'] ?? null){{ $snapshot['address'] }}@endif
            @if(($snapshot['postal_code'] ?? null) && ($snapshot['city'] ?? null)) &nbsp;·&nbsp; {{ $snapshot['postal_code'] }} {{ $snapshot['city'] }}@endif
            @if($snapshot['phone'] ?? null) &nbsp;·&nbsp; Tel. {{ $snapshot['phone'] }}@endif
            @if($snapshot['email'] ?? null) &nbsp;·&nbsp; {{ $snapshot['email'] }}@endif
        </div>
    @endif
</div>

{{-- ══ ADDRESS BLOCK + COLORED INFO PANEL ════════════════════════════════ --}}
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
                    @if(isset($customer->vat_number) && $customer->vat_number)
                        <br>USt-IdNr.: {{ $customer->vat_number }}
                    @endif
                </div>
            </div>
            @endif
        </td>
        <td style="width:48%; vertical-align:top;">
            {{-- Colored info panel with left accent border --}}
            @php
                $detailsBg         = $accent;
                $detailsPad        = '5px 10px';
                $detailsLabelColor = $primary;
                $detailsBorderColor = 'rgba(37,99,235,0.2)';
                $detailsTableStyle = 'border-left:4px solid ' . $primary . '; padding:2mm;';
            @endphp
            @include('pdf.invoice-partials.details')
        </td>
    </tr>
</table>

{{-- ══ DOCUMENT TITLE ══════════════════════════════════════════════════════ --}}
<div style="margin-bottom:5mm;">
    <div style="font-size:{{ $fsH + 5 }}px; font-weight:800; color:{{ $isCorrection ? '#dc2626' : $primary }}; line-height:1.1;">
        {{ $isCorrection ? 'STORNORECHNUNG' : $invoiceTypeLabel }} <span style="font-size:{{ $fsH + 1 }}px; font-weight:400; color:{{ $secCol }};">{{ $invoice->number }}</span>
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

{{-- ══ ITEMS TABLE: colored header, alternating rows ═════════════════════ --}}
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
    @php $tableWidth = '295px'; $totalRowBg = $primary; @endphp
    @include('pdf.invoice-partials.totals')
</div>

{{-- ══ PAYMENT ═════════════════════════════════════════════════════════════ --}}
@include('pdf.invoice-partials.payment-terms')

{{-- ══ CLOSING ═════════════════════════════════════════════════════════════ --}}
<div style="margin-top:8mm; font-size:{{ $fs }}px; border-left:7px solid {{ $primary }}; padding-left:5mm;">
    <div style="margin-bottom:3px;">Mit freundlichen Grüßen</div>
    <div style="font-weight:600;">{{ $snapshot['name'] ?? '' }}</div>
</div>

@if($ls['branding']['show_footer'] ?? true)
    @include('pdf.invoice-partials.footer')
@endif
</div>
