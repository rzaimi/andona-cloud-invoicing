{{-- Modern: full-bleed teal letterhead band, italic serif title,
     airy whitespace, minimal items table.
     DIN 5008 Form B compliant: 40mm letterhead → address window at
     45mm from page top × 20mm from page left.
     Shared by invoice + offer via $docKind. --}}
@php
    $docKind        = $docKind        ?? 'invoice';
    $doc            = $doc            ?? ($invoice ?? $offer);
    $invoice        = $invoice        ?? $doc;

    $ls       = $layoutSettings;
    $primary  = $ls['colors']['primary']   ?? '#0e7490';   // teal-700 default
    $accent   = $ls['colors']['accent']    ?? '#5eead4';   // teal-300
    $ink      = $ls['colors']['text']      ?? '#0f172a';
    $muted    = $ls['colors']['secondary'] ?? '#64748b';
    $hair     = '#e2e8f0';
    $fs       = $bodyFontSize;
    $heading  = $ls['fonts']['heading'] ?? 'DejaVu Serif';

    // Logo (inlined as base64 data URI)
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

    // Heading
    if ($docKind === 'invoice') {
        $isCorrection = isset($doc->is_correction) && (bool)$doc->is_correction;
        $docHeading   = $isCorrection
            ? 'Stornorechnung'
            : getReadableInvoiceType($doc->invoice_type ?? 'standard', $doc->sequence_number ?? null);
    } else {
        $isCorrection = false;
        $docHeading   = 'Angebot';
    }

    $customer = $doc->customer ?? null;
    $bankIban = $snapshot['bank_iban'] ?? null;
    $bankBic  = $snapshot['bank_bic']  ?? null;
    $bankName = $snapshot['bank_name'] ?? null;

    // Items-table styling — flat with header underline only, primary
    // accent for the grand-total row
    $tableHeaderBg        = null;
    $tableHeaderTextColor = $muted;
    $tableHeaderStyle     = "border-bottom:1px solid {$hair};";
    $altRowBg             = null;
    $cellPadding          = '6px 6px';
    $showRowNumber        = $ls['content']['show_row_number'] ?? false;
    $totalRowBg           = $primary;
    $totalRowTextColor    = '#ffffff';
    $tableWidth           = '300px';

    // Logo position + size from layout settings — same handling pattern
    // as minimal so the layout editor's Logo-Position / Logo-Größe
    // options apply uniformly across all templates.
    $logoPos  = $ls['branding']['logo_position'] ?? 'top-left';
    $logoH    = match($ls['branding']['logo_size'] ?? 'medium') { 'small' => '14mm', 'large' => '28mm', default => '22mm' };
    $logoW    = match($ls['branding']['logo_size'] ?? 'medium') { 'small' => '50mm', 'large' => '90mm', default => '70mm' };
    $logoCell = $showLogo ? '<img src="'.e($logoSrc).'" alt="Logo" style="max-height:'.$logoH.'; max-width:'.$logoW.'; display:block;">' : '';
    [$colL, $colC, $colR] = match($logoPos) {
        'top-center' => ['', $logoCell, ''],
        'top-right'  => ['', '', $logoCell],
        default      => [$logoCell, '', ''],
    };
@endphp

<div class="container" style="padding:0; font-family:{{ $ls['fonts']['body'] ?? 'DejaVu Sans' }},sans-serif; font-size:{{ $fs }}px; color:{{ $ink }}; line-height:1.55;">

{{-- LETTERHEAD: full-width teal band, exactly 40mm tall (DIN 5008).
     Logo only — position (left/center/right) driven by the layout
     setting Logo-Position. --}}
<div style="height:40mm; padding:5mm 20mm; box-sizing:border-box;">
    <table style="width:100%; height:100%; border-collapse:collapse;">
    <tr>
        <td style="width:40%; vertical-align:middle;">{!! $colL !!}</td>
        <td style="width:20%; vertical-align:middle; text-align:center;">{!! $colC !!}</td>
        <td style="width:40%; vertical-align:middle; text-align:right;">{!! $colR !!}</td>
    </tr>
    </table>
</div>

{{-- DIN 5008 address window + meta block --}}
<table style="width:100%; border-collapse:collapse; margin-top:5mm;">
<tr>
    <td style="width:85mm; padding:0 0 0 20mm; vertical-align:top;">
        <div style="height:40mm; overflow:hidden;">
            @if($customer)
            <div style="font-size:{{ $fs }}px; line-height:1.5;">
                @if($customer->name ?? null)<div style="font-weight:600;">{{ $customer->name }}</div>@endif
                @if($customer->contact_person ?? null)<div style="color:{{ $muted }};">{{ $customer->contact_person }}</div>@endif
                @if($customer->address ?? null)<div>{{ $customer->address }}</div>@endif
                @if(($customer->postal_code ?? null) || ($customer->city ?? null))<div>{{ trim(($customer->postal_code ?? '').' '.($customer->city ?? '')) }}</div>@endif
                @if(($customer->country ?? null) && $customer->country !== 'DE')<div>{{ $customer->country }}</div>@endif
            </div>
            @endif
        </div>
    </td>
    <td style="vertical-align:top; padding:0 20mm 0 6mm;">
        @include('pdf.partials.details', [
            'detailsLabelColor'  => $muted,
            'detailsBorderColor' => $hair,
            'detailsPad'         => '0.6mm 0',
            'detailsFontSize'    => $fs - 1,
        ])
    </td>
</tr>
</table>

{{-- Rest of body content with 20mm horizontal insets --}}
<div style="padding:0 20mm;">

{{-- Title block --}}
@if($ls['content']['show_subject'] ?? true)
<div style="margin-top:12mm; padding-bottom:3mm; border-bottom:0.5mm solid {{ $primary }};">
    <span style="font-family:{{ $heading }},DejaVu Serif,serif; font-style:italic; font-size:{{ $fs + 11 }}px; color:{{ $primary }}; letter-spacing:-0.3px;">{{ $docHeading }}</span>
    <span style="font-size:{{ $fs + 2 }}px; color:{{ $muted }}; margin-left:4mm;">{{ $doc->number }}{{ ($doc->title ?? null) ? ' · '.$doc->title : '' }}</span>
</div>
@endif

{{-- Salutation --}}
@if($ls['content']['show_salutation'] ?? true)
<div style="margin-top:7mm; font-size:{{ $fs }}px; line-height:1.7;">
    @if($doc->salutation ?? null)
        {!! nl2br(e($doc->salutation)) !!}
    @else
        Sehr geehrte Damen und Herren,<br><br>
        @if($docKind === 'offer')
            anbei unser Angebot zu Ihrer Anfrage. Wir freuen uns auf Ihre Rückmeldung.
        @else
            vielen Dank für Ihren Auftrag. Hiermit stellen wir Ihnen die folgenden Leistungen in Rechnung:
        @endif
    @endif
</div>
@endif

{{-- Items --}}
<div style="margin-top:6mm;">
@include('pdf.partials.items-table')
</div>

{{-- Totals --}}
<div style="margin-top:4mm;">
@include('pdf.partials.totals')
</div>

@if($docKind === 'invoice')
{{-- Bank info: tinted strip with primary accent --}}
@if(($ls['content']['show_bank_details'] ?? true) && ($bankIban || $bankBic))
<div style="margin-top:8mm; padding:4mm 5mm; background:{{ $accent }}1a; border-left:1.2mm solid {{ $primary }}; font-size:{{ $fs - 1 }}px; color:{{ $ink }}; line-height:1.7;">
    <div style="color:{{ $primary }}; font-size:{{ $fs - 2 }}px; font-weight:700; text-transform:uppercase; letter-spacing:0.6px; margin-bottom:1.5mm;">Zahlungsdetails</div>
    @if($bankIban)<span style="margin-right:6mm;"><span style="color:{{ $muted }};">IBAN</span> <strong>{{ $bankIban }}</strong></span>@endif
    @if($bankBic)<span style="margin-right:6mm;"><span style="color:{{ $muted }};">BIC</span> <strong>{{ $bankBic }}</strong></span>@endif
    @if($bankName)<span style="margin-right:6mm;"><span style="color:{{ $muted }};">Bank</span> <strong>{{ $bankName }}</strong></span>@endif
    <span><span style="color:{{ $muted }};">VWZ</span> <strong>{{ $doc->number }}</strong></span>
</div>
@endif

{{-- Payment terms --}}
@if($ls['content']['show_payment_terms'] ?? true)
<div style="margin-top:5mm; font-size:{{ $fs - 1 }}px; color:{{ $muted }}; line-height:1.7; page-break-inside:avoid;">
    @include('pdf.partials.payment-terms')
</div>
@endif
@endif

{{-- Geschäftsbedingungen (offer) --}}
@if($docKind === 'offer' && !empty($doc->terms_conditions))
<div style="margin-top:6mm; font-size:{{ $fs - 1 }}px; line-height:1.7; color:{{ $muted }}; page-break-inside:avoid;">
    <div style="color:{{ $primary }}; font-weight:700; text-transform:uppercase; letter-spacing:0.6px; font-size:{{ $fs - 2 }}px; margin-bottom:1.5mm;">Geschäftsbedingungen</div>
    {!! nl2br(e($doc->terms_conditions)) !!}
</div>
@endif

{{-- Notes --}}
@if(($ls['content']['show_notes'] ?? true) && !empty($doc->notes))
<div style="margin-top:5mm; font-size:{{ $fs - 1 }}px; color:{{ $muted }}; line-height:1.65; white-space:pre-wrap;">{{ $doc->notes }}</div>
@endif

{{-- Closing --}}
@if($ls['content']['show_closing'] ?? true)
<div style="margin-top:8mm; font-size:{{ $fs }}px; line-height:1.7; page-break-inside:avoid;">
    @if($doc->closing ?? null)
        {!! nl2br(e($doc->closing)) !!}
    @else
        Mit freundlichen Grüßen
    @endif
    @if($ls['content']['show_signature'] ?? true)
    <div style="margin-top:3mm; font-family:{{ $heading }},DejaVu Serif,serif; font-style:italic; font-size:{{ $fs + 2 }}px; color:{{ $primary }};">
        {{ $snapshot['display_name'] ?? $snapshot['name'] ?? '' }}
    </div>
    @endif
</div>
@endif

</div>{{-- /content --}}
</div>{{-- footer is rendered as a direct child of <body> in invoice.blade.php so DomPDF's position:fixed extractor picks it up on every page --}}
