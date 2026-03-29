{{-- Minimal: Ultra-clean, whitespace-focused, bare typography --}}
@php
    $ls      = $layoutSettings;
    $primary = $ls['colors']['primary']   ?? '#111111';
    $soft    = $ls['colors']['secondary'] ?? '#888888';
    $border  = '#dedede';
    $fs      = $bodyFontSize;

    // Logo
    $logoRelPath = isset($snapshot['logo']) ? ltrim(preg_replace('#^storage/#', '', (string)$snapshot['logo']), '/') : null;
    $logoSrc = null;
    if ($logoRelPath) {
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
        ? 'Stornorechnung'
        : getReadableInvoiceType($invoice->invoice_type ?? 'standard', $invoice->sequence_number ?? null);
    $customer     = $invoice->customer ?? null;
    $bankIban     = $snapshot['bank_iban'] ?? null;
    $bankBic      = $snapshot['bank_bic']  ?? null;
    $bankName     = $snapshot['bank_name'] ?? null;

    // Logo position
    $logoPos  = $ls['branding']['logo_position'] ?? 'top-left';
    $logoCell = $showLogo ? '<img src="'.e($logoSrc).'" alt="Logo" style="max-height:12mm; max-width:45mm; display:block;">' : '';
    [$colL, $colC, $colR] = match($logoPos) {
        'top-center' => ['', $logoCell, ''],
        'top-right'  => ['', '', $logoCell],
        default      => [$logoCell, '', ''],
    };

    // Table style vars for items-table partial
    $tableHeaderBg        = null;
    $tableHeaderTextColor = $soft;
    $tableHeaderStyle     = "border-top:0.4mm solid {$primary}; border-bottom:0.2mm solid {$border};";
    $altRowBg             = null;
    $cellPadding          = '5px 4px';
    $showRowNumber        = $ls['content']['show_row_number'] ?? false;
    $totalRowBg           = $primary;
    $totalRowTextColor    = '#ffffff';
    $tableWidth           = '270px';
@endphp

<div class="container" style="padding:0; font-family:{{ $ls['fonts']['body'] ?? 'DejaVu Sans' }},sans-serif; font-size:{{ $fs }}px; color:{{ $primary }};">

{{-- Thin accent top bar --}}
<div style="height:0.4mm; background:{{ $primary }}; width:100%;"></div>

{{-- HEADER --}}
<div style="padding:6mm 20mm 5mm 20mm;">
    <table style="width:100%; border-collapse:collapse;">
    <tr>
        <td style="width:40%; vertical-align:bottom;">{!! $colL !!}</td>
        <td style="width:20%; vertical-align:bottom; text-align:center;">{!! $colC !!}</td>
        <td style="width:40%; vertical-align:bottom; text-align:right;">{!! $colR !!}</td>
    </tr>
    </table>
</div>

{{-- ADDRESS + META --}}
<div style="padding:0 20mm;">
<table style="width:100%; border-collapse:collapse; margin-top:3mm;">
<tr>
    {{-- Customer address --}}
    <td style="width:90mm; vertical-align:top; padding-right:6mm;">
        @if($ls['content']['show_company_address'] ?? true)
        @php
            $retParts = array_filter([$snapshot['name'] ?? null, $snapshot['address'] ?? null, trim(($snapshot['postal_code'] ?? '').' '.($snapshot['city'] ?? '')) ?: null]);
        @endphp
        <div style="font-size:6.5pt; color:{{ $soft }}; padding-bottom:1.5mm; margin-bottom:2.5mm; border-bottom:0.2mm solid {{ $border }}; line-height:1;">
            {{ implode(' · ', $retParts) }}
        </div>
        @endif
        @if($customer)
        <div style="font-size:{{ $fs }}px; line-height:1.7;">
            @if($customer->name ?? null)<span style="font-weight:600;">{{ $customer->name }}</span><br>@endif
            @if($customer->contact_person ?? null)<span style="color:{{ $soft }};">{{ $customer->contact_person }}</span><br>@endif
            @if($customer->address ?? null){{ $customer->address }}<br>@endif
            @if(($customer->postal_code ?? null) || ($customer->city ?? null)){{ trim(($customer->postal_code ?? '').' '.($customer->city ?? '')) }}@endif
            @if(($customer->country ?? null) && $customer->country !== 'DE')<br>{{ $customer->country }}@endif
        </div>
        @endif
    </td>
    {{-- Invoice meta --}}
    <td style="width:55mm; vertical-align:top;">
        @include('pdf.invoice-partials.details', [
            'detailsLabelColor'  => $soft,
            'detailsBorderColor' => $border,
            'detailsPad'         => '1.5mm 0',
            'detailsFontSize'    => $fs - 1,
        ])
    </td>
</tr>
</table>

{{-- Title --}}
<div style="margin-top:8mm; padding-bottom:2mm; border-bottom:0.2mm solid {{ $border }};">
    <span style="font-size:{{ $fs + 3 }}px; font-weight:600; letter-spacing:-0.3px;">{{ $invoiceTypeLabel }}</span>
    <span style="font-size:{{ $fs + 1 }}px; color:{{ $soft }}; margin-left:3mm;">{{ $invoice->number }}{{ ($invoice->title ?? null) ? ' – '.$invoice->title : '' }}</span>
</div>

{{-- Salutation --}}
@if($ls['content']['show_salutation'] ?? true)
@if($invoice->salutation ?? null)
<div style="margin-top:5mm; font-size:{{ $fs }}px; line-height:1.7;">{!! nl2br(e($invoice->salutation)) !!}</div>
@endif
@endif

{{-- Items --}}
<div style="margin-top:5mm;">
@include('pdf.invoice-partials.items-table')
</div>

{{-- Totals --}}
<div style="margin-top:4mm;">
@include('pdf.invoice-partials.totals')
</div>

{{-- Bank info (single compact row) --}}
@if(($ls['content']['show_bank_details'] ?? true) && ($bankIban || $bankBic))
<div style="margin-top:7mm; padding-top:3mm; border-top:0.2mm solid {{ $border }}; font-size:{{ $fs - 1 }}px; color:{{ $soft }}; line-height:1.8;">
    @if($bankIban)<span style="margin-right:5mm;"><strong style="color:{{ $primary }}; font-weight:500;">IBAN</strong> {{ $bankIban }}</span>@endif
    @if($bankBic)<span style="margin-right:5mm;"><strong style="color:{{ $primary }}; font-weight:500;">BIC</strong> {{ $bankBic }}</span>@endif
    @if($bankName)<span><strong style="color:{{ $primary }}; font-weight:500;">Bank</strong> {{ $bankName }}</span>@endif
    <span style="margin-left:5mm;"><strong style="color:{{ $primary }}; font-weight:500;">VWZ</strong> {{ $invoice->number }}</span>
</div>
@endif

{{-- Notes --}}
@if(($ls['content']['show_notes'] ?? true) && !empty($invoice->notes))
<div style="margin-top:5mm; font-size:{{ $fs - 1 }}px; color:{{ $soft }}; line-height:1.6; white-space:pre-wrap;">{{ $invoice->notes }}</div>
@endif

{{-- Payment terms (single line) --}}
@if($ls['content']['show_payment_terms'] ?? true)
<div style="margin-top:4mm; font-size:{{ $fs - 1 }}px; color:{{ $soft }};">
    @include('pdf.invoice-partials.payment-terms')
</div>
@endif

</div>{{-- /content --}}
@include('pdf.invoice-partials.footer')
</div>
