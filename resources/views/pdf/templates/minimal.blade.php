{{-- Minimal: Ultra-clean, whitespace-focused, bare typography.
     Shared by invoice + offer via $docKind. --}}
@php
    // Document-kind aware helpers. Entrypoint sets $doc / $docKind; we
    // alias $invoice = $doc so 90% of the legacy template logic keeps
    // working on both paths.
    $docKind        = $docKind        ?? 'invoice';
    $doc            = $doc            ?? ($invoice ?? $offer);
    $invoice        = $invoice        ?? $doc;
    $docNumberLabel = $docNumberLabel ?? ($docKind === 'offer' ? 'Angebotsnr.' : 'Rechnungsnr.');
    $docDateLabel   = $docDateLabel   ?? ($docKind === 'offer' ? 'Gültig bis'  : 'Zahlungsziel');
    $docDateValue   = $docDateValue   ?? ($docKind === 'offer' ? ($doc->valid_until ?? null) : ($doc->due_date ?? null));

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

    // Heading: invoice-specific correction/type vs plain "Angebot"
    if ($docKind === 'invoice') {
        $isCorrection = isset($doc->is_correction) && (bool)$doc->is_correction;
        $docHeading   = $isCorrection
            ? 'Stornorechnung'
            : getReadableInvoiceType($doc->invoice_type ?? 'standard', $doc->sequence_number ?? null);
    } else {
        $isCorrection = false;
        $docHeading   = 'Angebot';
    }

    $customer  = $doc->customer ?? null;
    $bankIban  = $snapshot['bank_iban'] ?? null;
    $bankBic   = $snapshot['bank_bic']  ?? null;
    $bankName  = $snapshot['bank_name'] ?? null;

    // Logo position
    $logoPos  = $ls['branding']['logo_position'] ?? 'top-left';
    $logoH    = match($ls['branding']['logo_size'] ?? 'medium') { 'small' => '10mm', 'large' => '24mm', default => '16mm' };
    $logoW    = match($ls['branding']['logo_size'] ?? 'medium') { 'small' => '38mm', 'large' => '72mm', default => '54mm' };
    $logoCell = $showLogo ? '<img src="'.e($logoSrc).'" alt="Logo" style="max-height:'.$logoH.'; max-width:'.$logoW.'; display:block;">' : '';
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
    $cellPadding          = '6px 5px';
    $showRowNumber        = $ls['content']['show_row_number'] ?? false;
    $totalRowBg           = $primary;
    $totalRowTextColor    = '#ffffff';
    $tableWidth           = '270px';
@endphp

<div class="container" style="padding:0; font-family:{{ $ls['fonts']['body'] ?? 'DejaVu Sans' }},sans-serif; font-size:{{ $fs }}px; color:{{ $primary }};">

{{-- LETTERHEAD: 40mm tall so that with @page margin-top:5mm the
     customer address window below starts at exactly 45mm from the
     physical page top — DIN 5008 Form B envelope-window position,
     regardless of which logo_size is configured. --}}
<div style="height:40mm; padding:5mm 20mm 3mm 20mm; box-sizing:border-box;">
    <table style="width:100%; height:100%; border-collapse:collapse;">
    <tr>
        <td style="width:40%; vertical-align:middle;">{!! $colL !!}</td>
        <td style="width:20%; vertical-align:middle; text-align:center;">{!! $colC !!}</td>
        <td style="width:40%; vertical-align:middle; text-align:right;">{!! $colR !!}</td>
    </tr>
    </table>
</div>

{{-- DIN 5008 Form B address window + meta info side-by-side.
     Window position: 20mm from left, 85mm × 45mm.
     Customer address text must fit within this rectangle so it shows
     through standard window envelopes (DL, C5/6). --}}
<table style="width:100%; border-collapse:collapse;">
<tr>
    {{-- Customer address (DIN 5008 window) --}}
    <td style="width:85mm; padding:0 0 0 20mm; vertical-align:top;">
        <div style="height:40mm; overflow:hidden;">
            @if($ls['content']['show_company_address'] ?? true)
            @php
                // $senderName is set by invoice.blade.php / offer.blade.php:
                // company name for GmbH/UG/AG etc., managing_director name
                // for Einzelunternehmen and Freiberufler.
                $retParts = array_filter([$senderName ?? null, $snapshot['address'] ?? null, trim(($snapshot['postal_code'] ?? '').' '.($snapshot['city'] ?? '')) ?: null]);
            @endphp
            <div style="font-size:6.5pt; color:{{ $soft }}; padding-bottom:1.5mm; margin-bottom:2.5mm; border-bottom:0.2mm solid {{ $border }}; line-height:1;">
                {{ implode(' · ', $retParts) }}
            </div>
            @endif
            @if($customer)
            <div style="font-size:{{ $fs }}px; line-height:1.5;">
                @if($customer->name ?? null)<span style="font-weight:600;">{{ $customer->name }}</span><br>@endif
                @if($customer->contact_person ?? null)<span style="color:{{ $soft }};">{{ $customer->contact_person }}</span><br>@endif
                @if($customer->address ?? null){{ $customer->address }}<br>@endif
                @if(($customer->postal_code ?? null) || ($customer->city ?? null)){{ trim(($customer->postal_code ?? '').' '.($customer->city ?? '')) }}@endif
                @if(($customer->country ?? null) && $customer->country !== 'DE')<br>{{ $customer->country }}@endif
            </div>
            @endif
        </div>
    </td>
    {{-- Meta info on the right (Rechnungsnr., Datum, BV, etc.) --}}
    <td style="vertical-align:top; padding:0 20mm 0 6mm;">
        @include('pdf.partials.details', [
            'detailsLabelColor'  => $soft,
            'detailsBorderColor' => $border,
            'detailsPad'         => '0.5mm 0',
            'detailsFontSize'    => $fs - 1,
        ])
    </td>
</tr>
</table>

{{-- Wrap the rest of the body content (after the address window) in a
     left/right padded block so subject, items, totals etc. share the
     same 20mm horizontal insets used on page 1. --}}
<div style="padding:0 20mm;">

{{-- Title --}}
<div style="margin-top:8mm; padding-bottom:2mm; border-bottom:0.2mm solid {{ $border }};">
    <span style="font-size:{{ $fs + 5 }}px; font-weight:600; letter-spacing:-0.3px;">{{ $docHeading }}</span>
    <span style="font-size:{{ $fs + 3 }}px; color:{{ $soft }}; margin-left:3mm;">{{ $doc->number }}{{ ($doc->title ?? null) ? ' – '.$doc->title : '' }}</span>
</div>

{{-- Salutation --}}
@if($ls['content']['show_salutation'] ?? true)
@if($doc->salutation ?? null)
<div style="margin-top:5mm; font-size:{{ $fs }}px; line-height:1.7;">{!! nl2br(e($doc->salutation)) !!}</div>
@endif
@endif

{{-- Items --}}
<div>
@include('pdf.partials.items-table')
</div>

{{-- Totals --}}
<div style="margin-top:4mm;">
@include('pdf.partials.totals')
</div>

@if($docKind === 'invoice')
{{-- Bank info (single compact row) --}}
@if(($ls['content']['show_bank_details'] ?? true) && ($bankIban || $bankBic))
<div style="margin-top:7mm; padding-top:3mm; border-top:0.2mm solid {{ $border }}; font-size:{{ $fs - 1 }}px; color:{{ $soft }}; line-height:1.8;">
    @if($bankIban)<span style="margin-right:5mm;"><strong style="color:{{ $primary }}; font-weight:500;">IBAN</strong> {{ $bankIban }}</span>@endif
    @if($bankBic)<span style="margin-right:5mm;"><strong style="color:{{ $primary }}; font-weight:500;">BIC</strong> {{ $bankBic }}</span>@endif
    @if($bankName)<span><strong style="color:{{ $primary }}; font-weight:500;">Bank</strong> {{ $bankName }}</span>@endif
    <span style="margin-left:5mm;"><strong style="color:{{ $primary }}; font-weight:500;">VWZ</strong> {{ $doc->number }}</span>
</div>
@endif
@endif

{{-- Notes --}}
@if(($ls['content']['show_notes'] ?? true) && !empty($doc->notes))
<div style="margin-top:5mm; font-size:{{ $fs - 1 }}px; color:{{ $soft }}; line-height:1.6; white-space:pre-wrap;">{{ $doc->notes }}</div>
@endif

@if($docKind === 'invoice')
{{-- Payment terms (single line) --}}
@if($ls['content']['show_payment_terms'] ?? true)
<div style="margin-top:4mm; font-size:{{ $fs - 1 }}px; color:{{ $soft }}; page-break-inside:avoid;">
    @include('pdf.partials.payment-terms')
</div>
@endif
@endif

@if($docKind === 'offer' && !empty($doc->terms_conditions))
<div style="margin-top:5mm; font-size:{{ $fs - 1 }}px; color:{{ $soft }}; line-height:1.65; page-break-inside:avoid;">
    <strong style="color:{{ $primary }}; font-weight:500;">Geschäftsbedingungen:</strong> {!! nl2br(e($doc->terms_conditions)) !!}
</div>
@endif

</div>{{-- /content --}}
</div>{{-- footer is rendered as a direct child of <body> in invoice.blade.php so DomPDF's position:fixed extractor picks it up on every page --}}
