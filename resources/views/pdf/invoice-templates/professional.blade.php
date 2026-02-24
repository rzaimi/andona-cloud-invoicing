{{-- Professional Template: Corporate design with colored header band - DISTINCTIVE FEATURE --}}
{{-- Template: professional --}}
<div class="container">
    {{-- Header Band: Colored background with company info - DISTINCTIVE: Full-width colored header --}}
    <div style="background: {{ $layoutSettings['colors']['primary'] ?? '#2563eb' }}; color: white; padding: 3mm; margin-bottom: 5mm; border-radius: 2mm;">
        @php
            $logoRelPath = isset($snapshot['logo']) ? ltrim(preg_replace('#^storage/#', '', (string)$snapshot['logo']), '/') : null;
        @endphp
        @if(($layoutSettings['branding']['show_logo'] ?? true) && $logoRelPath)
            @if(isset($preview) && $preview)
                <div style="margin-bottom: 3mm;">
                    <img src="{{ asset('storage/' . $logoRelPath) }}" alt="Logo" style="max-height: 20mm; max-width: 70mm; filter: brightness(0) invert(1);">
                </div>
            @elseif(\Storage::disk('public')->exists($logoRelPath))
                @php
                    $logoPath = \Storage::disk('public')->path($logoRelPath);
                    $logoData = base64_encode(file_get_contents($logoPath));
                    $logoMime = mime_content_type($logoPath);
                @endphp
                <div style="margin-bottom: 3mm;">
                    <img src="data:{{ $logoMime }};base64,{{ $logoData }}" alt="Logo" style="max-height: 20mm; max-width: 70mm; filter: brightness(0) invert(1);">
                </div>
            @endif
        @endif
        <div style="font-size: {{ $headingFontSize + 4 }}px; font-weight: 700; margin-bottom: 1mm;">{{ $snapshot['name'] ?? '' }}</div>
        @if($layoutSettings['content']['show_company_address'] ?? true)
            <div style="font-size: {{ $bodyFontSize }}px; color: rgba(255, 255, 255, 0.9); line-height: 1.5;">
                {{ $snapshot['address'] ?? '' }}
                @if(($snapshot['postal_code'] ?? null) && ($snapshot['city'] ?? null)), {{ $snapshot['postal_code'] }} {{ $snapshot['city'] }}@endif
            </div>
        @endif
    </div>

    {{-- DIN 5008 compliant layout: Address and Invoice Details side by side --}}
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 10mm;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 10mm;">
                @php $customer = $invoice->customer ?? null; @endphp
                @if($customer)
                    {{-- DIN 5008 Address Block --}}
                    <div class="din-5008-address">
                        {{-- Recipient address --}}
                        <div style="font-weight: 600; margin-bottom: 1mm; font-size: {{ $bodyFontSize }}px; line-height: 1.3;">
                            {{ $customer->name ?? 'Unbekannt' }}
                        </div>
                        @if(isset($customer->contact_person) && $customer->contact_person)
                            <div style="margin-bottom: 1mm; font-size: {{ $bodyFontSize }}px; line-height: 1.3;">{{ $customer->contact_person }}</div>
                        @endif
                        <div style="font-size: {{ $bodyFontSize }}px; line-height: 1.3;">
                            @if($customer->address)
                                {{ $customer->address }}<br>
                            @endif
                            @if($customer->postal_code && $customer->city)
                                {{ $customer->postal_code }} {{ $customer->city }}
                                @if($customer->country && $customer->country !== 'Deutschland')
                                    <br>{{ $customer->country }}
                                @endif
                            @endif
                        </div>
                    </div>
                @endif
            </td>
            <td style="width: 50%; vertical-align: top;">
                @include('pdf.invoice-partials.details')
            </td>
        </tr>
    </table>

    {{-- Invoice Title --}}
    <div style="margin-bottom: 8px;">
        @php
            $isCorrection = isset($invoice->is_correction) ? (bool)$invoice->is_correction : false;
        @endphp
        @php
            $invoiceTypeLabel = $isCorrection
                ? 'STORNORECHNUNG'
                : strtoupper(getReadableInvoiceType($invoice->invoice_type ?? 'standard', $invoice->sequence_number ?? null));
        @endphp
        <div style="font-size: {{ $headingFontSize + 4 }}px; font-weight: 700; color: {{ $isCorrection ? '#dc2626' : ($layoutSettings['colors']['primary'] ?? '#1f2937') }};">
            {{ $invoiceTypeLabel }} {{ $invoice->number }}
        </div>
        @if($isCorrection && isset($invoice->correctsInvoice) && $invoice->correctsInvoice)
            <div style="margin-top: 10px; padding: 10px; background-color: #fee2e2; border-left: 4px solid #dc2626; font-size: {{ $bodyFontSize }}px;">
                <div style="font-weight: 600; color: #991b1b; margin-bottom: 4px;">Storniert Rechnung:</div>
                <div style="color: #7f1d1d;">Nr. {{ $invoice->correctsInvoice->number }} vom {{ formatInvoiceDate($invoice->correctsInvoice->issue_date, $dateFormat ?? 'd.m.Y') }}</div>
                @if(isset($invoice->correction_reason) && $invoice->correction_reason)
                    <div style="margin-top: 6px; padding-top: 6px; border-top: 1px solid #dc2626;">
                        <strong>Grund:</strong> {{ $invoice->correction_reason }}
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Salutation and Introduction --}}
    <div style="margin-bottom: 10px; font-size: {{ $bodyFontSize }}px; line-height: 1.5;">
        <div style="margin-bottom: 4px;">Sehr geehrte Damen und Herren,</div>
        <div>vielen Dank für Ihren Auftrag und das damit verbundene Vertrauen! Hiermit stelle ich Ihnen die folgenden Leistungen in Rechnung:</div>
    </div>

    {{-- Items Table with colored header - DISTINCTIVE: Blue header matching band --}}
    @php
        $tableHeaderBg = $layoutSettings['colors']['primary'] ?? '#2563eb';
        $altRowBg      = $layoutSettings['colors']['accent'] ?? '#f9fafb';
        $cellPadding   = '10px 8px';
    @endphp
    @include('pdf.invoice-partials.items-table')

    {{-- Totals --}}
    <div style="margin-top: 10px;">
        @include('pdf.invoice-partials.totals')
    </div>

    {{-- Payment instructions --}}
    @include('pdf.invoice-partials.payment-terms')

    {{-- Closing --}}
    <div style="margin-top: 12px; font-size: {{ $bodyFontSize }}px;">
        <div style="margin-bottom: 4px;">Mit freundlichen Grüßen</div>
        <div style="font-weight: 600;">{{ $snapshot['name'] ?? '' }}</div>
    </div>

    {{-- Footer Style C: Multi-column structured (German Example 3) --}}
    @if($layoutSettings['branding']['show_footer'] ?? true)
    <div class="pdf-footer" style="margin-top: 15mm; border-top: 1px solid {{ $layoutSettings['colors']['accent'] ?? '#e5e7eb' }}; padding-top: 3mm;">
        <table style="width: 100%; font-size: 7pt; line-height: 1.5; color: {{ $layoutSettings['colors']['text'] ?? '#6b7280' }}; border-collapse: collapse;">
            <tr>
                {{-- Column 1: Address (22%) --}}
                <td style="width: 22%; vertical-align: top; padding-right: 5px;">
                    @if($snapshot['name'] ?? null)<div style="font-weight: 600; margin-bottom: 2px;">{{ $snapshot['name'] }}</div>@endif
                    @if($snapshot['address'] ?? null)<div>{{ $snapshot['address'] }}</div>@endif
                    @if(($snapshot['postal_code'] ?? null) && ($snapshot['city'] ?? null))
                        <div>{{ $snapshot['postal_code'] }} {{ $snapshot['city'] }}</div>
                    @endif
                </td>
                {{-- Column 2: Contact (24%) --}}
                <td style="width: 24%; vertical-align: top; padding-right: 5px;">
                    @if($snapshot['phone'] ?? null)<div><strong>FON</strong> {{ $snapshot['phone'] }}</div>@endif
                    @if($snapshot['email'] ?? null)<div><strong>MAIL</strong> {{ $snapshot['email'] }}</div>@endif
                    @if($snapshot['website'] ?? null)<div><strong>WEB</strong> {{ $snapshot['website'] }}</div>@endif
                </td>
                {{-- Column 3: Tax Info (18%) --}}
                <td style="width: 18%; vertical-align: top; padding-right: 5px;">
                    @if($snapshot['tax_number'] ?? null)<div><strong>Steuernr.</strong> {{ $snapshot['tax_number'] }}</div>@endif
                    @if($snapshot['vat_number'] ?? null)<div><strong>UST-ID</strong> {{ $snapshot['vat_number'] }}</div>@endif
                </td>
                {{-- Column 4: Banking (36%) --}}
                @if($layoutSettings['content']['show_bank_details'] ?? true)
                <td style="width: 36%; vertical-align: top;">
                    @if($snapshot['bank_name'] ?? null)<div><strong>BANK</strong> {{ $snapshot['bank_name'] }}</div>@endif
                    @if($snapshot['bank_iban'] ?? null)<div><strong>IBAN</strong> {{ $snapshot['bank_iban'] }}</div>@endif
                    @if($snapshot['bank_bic'] ?? null)<div><strong>BIC</strong> {{ $snapshot['bank_bic'] }}</div>@endif
                </td>
                @endif
            </tr>
        </table>
    </div>
    @endif

</div>
