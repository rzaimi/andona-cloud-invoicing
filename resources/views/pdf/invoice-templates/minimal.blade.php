{{-- Minimal: No decoration – logo top-right, content only, thin lines --}}
{{-- DISTINCTIVE: Right-aligned company block, address left / plain info right, borderless table --}}
@php
    $ls      = $layoutSettings;
    $primary = $ls['colors']['primary']   ?? '#111827';
    $accent  = $ls['colors']['accent']    ?? '#f9fafb';
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

{{-- ══ MINIMAL HEADER: logo right, company name below logo (right-aligned) ══ --}}
<table style="width:100%; border-collapse:collapse; margin-bottom:5mm; border-bottom:1px solid #d1d5db; padding-bottom:4mm;">
    <tr>
        <td style="vertical-align:bottom;">
            @if($ls['content']['show_company_address'] ?? true)
                <div style="font-size:{{ $fs - 1 }}px; color:{{ $secCol }}; line-height:1.5;">
                    @if($snapshot['address'] ?? null){{ $snapshot['address'] }},&ensp;@endif
                    @if(($snapshot['postal_code'] ?? null) && ($snapshot['city'] ?? null)){{ $snapshot['postal_code'] }} {{ $snapshot['city'] }}@endif
                    @if($snapshot['phone'] ?? null) &ensp;|&ensp; {{ $snapshot['phone'] }}@endif
                    @if($snapshot['email'] ?? null) &ensp;|&ensp; {{ $snapshot['email'] }}@endif
                </div>
                <div style="font-size:{{ $fsH + 3 }}px; font-weight:700; color:{{ $textCol }}; margin-top:1mm;">
                    {{ $snapshot['name'] ?? '' }}
                </div>
            @endif
        </td>
        @if($showLogo)
        <td style="text-align:right; vertical-align:top; width:40%;">
            <img src="{{ $logoSrc }}" alt="Logo" style="max-height:24mm; max-width:70mm;">
        </td>
        @endif
    </tr>
</table>

{{-- ══ ADDRESS BLOCK + PLAIN INFO (two columns) ═══════════════════════════ --}}
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
            {{-- Plain info, no box, only thin row lines --}}
            @php
                $detailsBg         = 'transparent';
                $detailsPad        = '3px 0';
                $detailsLabelColor = $secCol;
                $detailsBorderColor = '#e5e7eb';
                $detailsTableStyle = '';
            @endphp
            @include('pdf.invoice-partials.details')
        </td>
    </tr>
</table>

{{-- ══ DOCUMENT TITLE: simple large text ══════════════════════════════════ --}}
<div style="margin-bottom:5mm; padding-bottom:3mm; border-bottom:1px solid #d1d5db;">
    <div style="font-size:{{ $fsH + 5 }}px; font-weight:700; color:{{ $isCorrection ? '#dc2626' : $textCol }}; letter-spacing:0.5px;">
        {{ $isCorrection ? 'Stornorechnung' : ucwords(strtolower($invoiceTypeLabel)) }}
    </div>
    @if($isCorrection && isset($invoice->correctsInvoice) && $invoice->correctsInvoice)
        <div style="margin-top:6px; padding:7px 10px; background:#fee2e2; border-left:3px solid #dc2626; font-size:{{ $fs }}px;">
            <strong style="color:#991b1b;">Storniert Rechnung:</strong>
            Nr. {{ $invoice->correctsInvoice->number }} vom {{ formatInvoiceDate($invoice->correctsInvoice->issue_date, $dateFormat ?? 'd.m.Y') }}
            @if(!empty($invoice->correction_reason))
                <br><span style="color:#7f1d1d;"><strong>Grund:</strong> {{ $invoice->correction_reason }}</span>
            @endif
        </div>
    @endif
</div>

{{-- ══ INTRO ═══════════════════════════════════════════════════════════════ --}}
<div style="margin-bottom:5mm; font-size:{{ $fs }}px; line-height:1.6; color:{{ $textCol }};">
    <div style="margin-bottom:3px;">Sehr geehrte Damen und Herren,</div>
    <div>vielen Dank für Ihren Auftrag. Hiermit stellen wir Ihnen folgende Leistungen in Rechnung:</div>
</div>

{{-- ══ ITEMS TABLE: header underline only, very minimal ════════════════════ --}}
@php
    $tableHeaderBg = null;
    $tableHeaderStyle = 'border-bottom: 2px solid ' . $textCol . '; color: ' . $textCol . ';';
    $cellPadding   = '6px 5px';
@endphp
@include('pdf.invoice-partials.items-table')

{{-- VAT note --}}
@php $vatNote = getVatRegimeNote($invoice->vat_regime ?? 'standard'); @endphp
@if($vatNote)
    <div style="margin-top:6px; font-size:{{ $fs }}px; font-style:italic; color:{{ $secCol }};">{{ $vatNote }}</div>
@endif

{{-- ══ TOTALS: minimal, no outer border ═══════════════════════════════════ --}}
<div style="margin-top:8px;">
    @php $tableWidth = '270px'; $totalRowBg = $textCol; @endphp
    @include('pdf.invoice-partials.totals')
</div>

{{-- ══ PAYMENT ═════════════════════════════════════════════════════════════ --}}
@include('pdf.invoice-partials.payment-terms')

{{-- ══ CLOSING ═════════════════════════════════════════════════════════════ --}}
<div style="margin-top:8mm; font-size:{{ $fs }}px; color:{{ $textCol }};">
    <div style="margin-bottom:3px;">Mit freundlichen Grüßen</div>
    <div style="font-weight:600;">{{ $snapshot['name'] ?? '' }}</div>
</div>

@if($ls['branding']['show_footer'] ?? true)
    @include('pdf.invoice-partials.footer')
@endif
</div>
