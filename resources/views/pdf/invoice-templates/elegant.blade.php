{{-- Elegant: Centered sophisticated header with decorative rules, refined table --}}
{{-- DISTINCTIVE: Logo centered, company name flanked by horizontal rules, centered document title --}}
@php
    $ls      = $layoutSettings;
    $primary = $ls['colors']['primary']   ?? '#374151';
    $accent  = $ls['colors']['accent']    ?? '#f9fafb';
    $textCol = $ls['colors']['text']      ?? '#1f2937';
    $secCol  = $ls['colors']['secondary'] ?? '#9ca3af';
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

{{-- ══ CENTERED HEADER ════════════════════════════════════════════════════ --}}
<div style="text-align:center; margin-bottom:7mm; padding-bottom:4mm; border-bottom:0.5px solid {{ $secCol }};">
    @if($showLogo)
        <div style="margin-bottom:3mm;">
            <img src="{{ $logoSrc }}" alt="Logo" style="max-height:22mm; max-width:70mm;">
        </div>
    @endif
    {{-- Company name flanked by decorative rules (table-based) --}}
    <table style="width:100%; border-collapse:collapse; margin-bottom:2mm;">
        <tr>
            <td style="border-bottom:1px solid {{ $secCol }}; height:1px; width:20%;"></td>
            <td style="text-align:center; padding:0 8px; white-space:nowrap;">
                <span style="font-size:{{ $fsH + 4 }}px; font-weight:700; color:{{ $textCol }}; letter-spacing:1.5px; text-transform:uppercase;">
                    {{ $snapshot['name'] ?? '' }}
                </span>
            </td>
            <td style="border-bottom:1px solid {{ $secCol }}; height:1px; width:20%;"></td>
        </tr>
    </table>
    @if($ls['content']['show_company_address'] ?? true)
        <div style="font-size:{{ $fs - 1 }}px; color:{{ $secCol }}; line-height:1.5; margin-top:2mm;">
            @if($snapshot['address'] ?? null){{ $snapshot['address'] }}@endif
            @if(($snapshot['postal_code'] ?? null) && ($snapshot['city'] ?? null)) &ensp;·&ensp; {{ $snapshot['postal_code'] }} {{ $snapshot['city'] }}@endif
            @if($snapshot['email'] ?? null) &ensp;·&ensp; {{ $snapshot['email'] }}@endif
            @if($snapshot['website'] ?? null) &ensp;·&ensp; {{ $snapshot['website'] }}@endif
        </div>
    @endif
</div>

{{-- ══ ADDRESS BLOCK + STRUCTURED INFO TABLE ══════════════════════════════ --}}
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
                    <div style="font-size:{{ $fs }}px; line-height:1.3; margin-bottom:1mm; color:{{ $secCol }};">{{ $customer->contact_person }}</div>
                @endif
                <div style="font-size:{{ $fs }}px; line-height:1.35; color:{{ $textCol }};">
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
            {{-- Refined info table, no outer border, gentle row separators --}}
            @php
                $detailsBg          = 'transparent';
                $detailsPad         = '4px 0 4px 6px';
                $detailsLabelColor  = $secCol;
                $detailsBorderColor = '#e5e7eb';
                $detailsFontSize    = $fs - 1;
                $detailsTableStyle  = 'border-left:2px solid ' . $secCol . ';';
            @endphp
            @include('pdf.invoice-partials.details')
        </td>
    </tr>
</table>

{{-- ══ CENTERED DOCUMENT TITLE with decorative rule ═══════════════════════ --}}
<div style="text-align:center; margin-bottom:6mm; padding-bottom:4mm;">
    <div style="font-size:{{ $fsH + 5 }}px; font-weight:300; color:{{ $isCorrection ? '#dc2626' : $textCol }}; letter-spacing:4px; text-transform:uppercase; margin-bottom:2mm;">
        {{ $isCorrection ? 'STORNORECHNUNG' : $invoiceTypeLabel }}
    </div>
    <div style="font-size:{{ $fs }}px; color:{{ $secCol }}; letter-spacing:1px;">Nr.&ensp;{{ $invoice->number }}</div>
    <div style="border-bottom:0.5px solid {{ $secCol }}; margin-top:4mm;"></div>
</div>

{{-- Storno notice --}}
@if($isCorrection && isset($invoice->correctsInvoice) && $invoice->correctsInvoice)
    <div style="margin-bottom:5mm; padding:8px 12px; background:#fee2e2; border-left:3px solid #dc2626; font-size:{{ $fs }}px;">
        <div style="font-weight:600; color:#991b1b; margin-bottom:3px;">Storniert Rechnung:</div>
        <div style="color:#7f1d1d;">Nr. {{ $invoice->correctsInvoice->number }} vom {{ formatInvoiceDate($invoice->correctsInvoice->issue_date, $dateFormat ?? 'd.m.Y') }}</div>
        @if(!empty($invoice->correction_reason))
            <div style="margin-top:4px; padding-top:4px; border-top:1px solid #dc2626;"><strong>Grund:</strong> {{ $invoice->correction_reason }}</div>
        @endif
    </div>
@endif

{{-- ══ INTRO ═══════════════════════════════════════════════════════════════ --}}
<div style="margin-bottom:5mm; font-size:{{ $fs }}px; line-height:1.7; color:{{ $textCol }};">
    <div style="margin-bottom:3px;">Sehr geehrte Damen und Herren,</div>
    <div>vielen Dank für Ihren Auftrag. Hiermit stellen wir Ihnen folgende Leistungen in Rechnung:</div>
</div>

{{-- ══ ITEMS TABLE: light gray header, no outer border ════════════════════ --}}
@php
    $tableHeaderBg        = $accent;
    $tableHeaderTextColor = $textCol;
    $tableHeaderStyle     = 'border-bottom: 2px solid ' . $textCol . ';';
    $cellPadding          = '7px 7px';
@endphp
@include('pdf.invoice-partials.items-table')

{{-- VAT note --}}
@php $vatNote = getVatRegimeNote($invoice->vat_regime ?? 'standard'); @endphp
@if($vatNote)
    <div style="margin-top:6px; font-size:{{ $fs }}px; font-style:italic; color:{{ $secCol }};">{{ $vatNote }}</div>
@endif

{{-- ══ TOTALS ═══════════════════════════════════════════════════════════════ --}}
<div style="margin-top:8px;">
    @php $tableWidth = '280px'; $totalRowBg = $primary; @endphp
    @include('pdf.invoice-partials.totals')
</div>

{{-- ══ PAYMENT ═════════════════════════════════════════════════════════════ --}}
@include('pdf.invoice-partials.payment-terms')

{{-- ══ CLOSING ═════════════════════════════════════════════════════════════ --}}
<div style="margin-top:8mm; font-size:{{ $fs }}px; color:{{ $textCol }}; text-align:center; border-top:0.5px solid {{ $secCol }}; padding-top:5mm;">
    <div style="margin-bottom:3px; color:{{ $secCol }};">Mit freundlichen Grüßen</div>
    <div style="font-weight:700; letter-spacing:0.5px;">{{ $snapshot['name'] ?? '' }}</div>
</div>

@if($ls['branding']['show_footer'] ?? true)
    @include('pdf.invoice-partials.footer')
@endif
</div>
