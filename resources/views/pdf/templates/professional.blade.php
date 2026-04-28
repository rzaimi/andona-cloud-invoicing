{{-- Professional: corporate navy aesthetic, structured sections,
     clear visual hierarchy. DIN 5008 Form B compliant: 40mm
     letterhead → address window at 45mm × 20mm from left.
     Shared by invoice + offer via $docKind. --}}
@php
    $docKind = $docKind ?? 'invoice';
    $doc     = $doc     ?? ($invoice ?? $offer);
    $invoice = $invoice ?? $doc;

    $ls      = $layoutSettings;
    $primary = $ls['colors']['primary']   ?? '#1e3a8a';   // navy-800 default
    $accent  = $ls['colors']['accent']    ?? '#dbeafe';   // navy-50 tint
    $ink     = $ls['colors']['text']      ?? '#0f172a';
    $muted   = $ls['colors']['secondary'] ?? '#64748b';
    $hair    = '#cbd5e1';
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

    // Heading
    if ($docKind === 'invoice') {
        $isCorrection  = isset($doc->is_correction) && (bool)$doc->is_correction;
        $docHeadingRaw = $isCorrection
            ? 'Stornorechnung'
            : getReadableInvoiceType($doc->invoice_type ?? 'standard', $doc->sequence_number ?? null);
    } else {
        $isCorrection  = false;
        $docHeadingRaw = 'Angebot';
    }

    $customer = $doc->customer ?? null;
    $bankIban = $snapshot['bank_iban'] ?? null;
    $bankBic  = $snapshot['bank_bic']  ?? null;
    $bankName = $snapshot['bank_name'] ?? null;

    // Items-table styling — solid navy header, subtle row tint, navy total
    $tableHeaderBg        = $primary;
    $tableHeaderTextColor = '#ffffff';
    $tableHeaderStyle     = '';
    $altRowBg             = $accent;
    $cellPadding          = '7px 8px';
    $showRowNumber        = $ls['content']['show_row_number'] ?? false;
    $totalRowBg           = $primary;
    $totalRowTextColor    = '#ffffff';
    $tableWidth           = '290px';

    // Logo position + size from layout settings — uniform handling
    // across all templates so the layout editor's Logo-Position and
    // Logo-Größe options apply consistently.
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

{{-- LETTERHEAD: 40mm tall, navy bottom rule (DIN 5008 Form B).
     Logo only — position driven by the Logo-Position layout setting. --}}
<div style="height:40mm; padding:5mm 20mm; box-sizing:border-box; border-bottom:0.6mm solid {{ $primary }};">
    <table style="width:100%; height:100%; border-collapse:collapse;">
    <tr>
        <td style="width:40%; vertical-align:middle;">{!! $colL !!}</td>
        <td style="width:20%; vertical-align:middle; text-align:center;">{!! $colC !!}</td>
        <td style="width:40%; vertical-align:middle; text-align:right;">{!! $colR !!}</td>
    </tr>
    </table>
</div>

{{-- DIN 5008 address window + meta block. Meta sits inside a tinted
     panel for the corporate "info card" look. --}}
<table style="width:100%; border-collapse:collapse; margin-top:5mm;">
<tr>
    <td style="width:85mm; padding:0 0 0 20mm; vertical-align:top;">
        <div style="height:40mm; overflow:hidden;">
            @if($customer)
            <div style="font-size:{{ $fs }}px; line-height:1.5;">
                @if($customer->name ?? null)<div style="font-weight:700;">{{ $customer->name }}</div>@endif
                @if($customer->contact_person ?? null)<div style="color:{{ $muted }};">{{ $customer->contact_person }}</div>@endif
                @if($customer->address ?? null)<div>{{ $customer->address }}</div>@endif
                @if(($customer->postal_code ?? null) || ($customer->city ?? null))<div>{{ trim(($customer->postal_code ?? '').' '.($customer->city ?? '')) }}</div>@endif
                @if(($customer->country ?? null) && $customer->country !== 'DE')<div>{{ $customer->country }}</div>@endif
            </div>
            @endif
        </div>
    </td>
    <td style="vertical-align:top; padding:0 20mm 0 6mm;">
        <div style="background:{{ $accent }}; border-left:1.5mm solid {{ $primary }}; padding:3mm 4mm;">
            @include('pdf.partials.details', [
                'detailsLabelColor'  => $muted,
                'detailsBorderColor' => $hair,
                'detailsPad'         => '0.8mm 0',
                'detailsFontSize'    => $fs - 1,
            ])
        </div>
    </td>
</tr>
</table>

{{-- Rest of body content with 20mm horizontal insets --}}
<div style="padding:0 20mm;">

{{-- Subject — solid navy block, uppercase --}}
@if($ls['content']['show_subject'] ?? true)
<div style="margin-top:9mm; padding:3mm 5mm; background:{{ $primary }}; color:#ffffff;">
    <span style="font-size:{{ $fs + 2 }}px; font-weight:700; text-transform:uppercase; letter-spacing:0.7px;">{{ strtoupper($docHeadingRaw) }}</span>
    <span style="font-size:{{ $fs + 2 }}px; font-weight:400; opacity:0.85; margin-left:4mm;">{{ $doc->number }}{{ ($doc->title ?? null) ? ' · '.$doc->title : '' }}</span>
</div>
@endif

{{-- Salutation --}}
@if($ls['content']['show_salutation'] ?? true)
<div style="margin-top:6mm; font-size:{{ $fs }}px; line-height:1.7;">
    @if($doc->salutation ?? null)
        {!! nl2br(e($doc->salutation)) !!}
    @elseif($docKind === 'offer')
        Sehr geehrte Damen und Herren,<br><br>
        anbei übermitteln wir Ihnen unser Angebot. Wir freuen uns auf eine erfolgreiche Zusammenarbeit.
    @else
        Sehr geehrte Damen und Herren,<br><br>
        für die nachstehend aufgeführten Leistungen erlauben wir uns, folgende Rechnung zu stellen:
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
{{-- Bank info: tinted box with column labels (corporate look) --}}
@if(($ls['content']['show_bank_details'] ?? true) && ($bankIban || $bankBic))
<table style="width:100%; border-collapse:collapse; margin-top:8mm; page-break-inside:avoid;">
<tr><td style="background:{{ $primary }}; color:#ffffff; padding:2mm 4mm; font-size:{{ $fs - 1 }}px; font-weight:700; text-transform:uppercase; letter-spacing:0.7px;">
    Zahlungsdetails
</td></tr>
<tr><td style="background:{{ $accent }}; padding:3mm 4mm; font-size:{{ $fs - 1 }}px; line-height:1.7;">
    <table style="width:100%; border-collapse:collapse;">
    <tr>
        @if($bankIban)<td style="vertical-align:top; padding-right:4mm;"><div style="color:{{ $muted }}; font-size:{{ $fs - 2 }}px; text-transform:uppercase; letter-spacing:0.4px;">IBAN</div><div style="font-weight:700;">{{ $bankIban }}</div></td>@endif
        @if($bankBic)<td style="vertical-align:top; padding-right:4mm; width:24%;"><div style="color:{{ $muted }}; font-size:{{ $fs - 2 }}px; text-transform:uppercase; letter-spacing:0.4px;">BIC</div><div style="font-weight:700;">{{ $bankBic }}</div></td>@endif
        @if($bankName)<td style="vertical-align:top; padding-right:4mm;"><div style="color:{{ $muted }}; font-size:{{ $fs - 2 }}px; text-transform:uppercase; letter-spacing:0.4px;">Bank</div><div style="font-weight:700;">{{ $bankName }}</div></td>@endif
        <td style="vertical-align:top; width:22%;"><div style="color:{{ $muted }}; font-size:{{ $fs - 2 }}px; text-transform:uppercase; letter-spacing:0.4px;">Verwendungszweck</div><div style="font-weight:700;">{{ $doc->number }}</div></td>
    </tr>
    </table>
</td></tr>
</table>
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
<div style="margin-top:6mm; padding:3mm 4mm; background:{{ $accent }}; border-left:1.5mm solid {{ $primary }}; font-size:{{ $fs - 1 }}px; color:{{ $ink }}; line-height:1.7; page-break-inside:avoid;">
    <div style="color:{{ $primary }}; font-weight:700; text-transform:uppercase; letter-spacing:0.7px; font-size:{{ $fs - 2 }}px; margin-bottom:1.5mm;">Geschäftsbedingungen</div>
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
        Wir bedanken uns für Ihr Vertrauen und stehen für Rückfragen jederzeit zur Verfügung.<br><br>
        Mit freundlichen Grüßen
    @endif
    @if($ls['content']['show_signature'] ?? true)
    <div style="margin-top:3mm; font-size:{{ $fs + 1 }}px; font-weight:700; color:{{ $primary }};">
        {{ $snapshot['display_name'] ?? $snapshot['name'] ?? '' }}
    </div>
    @endif
</div>
@endif

</div>{{-- /content --}}
</div>{{-- footer is rendered as a direct child of <body> in invoice.blade.php so DomPDF's position:fixed extractor picks it up on every page --}}
