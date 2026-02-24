{{-- Modern Template: Modern layout with colored left border - DISTINCTIVE FEATURE --}}
{{-- Template: modern --}}
<div class="container">
    {{-- Header: Logo and company name with colored left border accent --}}
    <div style="margin-bottom: 8mm; padding-left: 3mm; border-left: 4px solid {{ $layoutSettings['colors']['primary'] ?? '#3b82f6' }};">
        @php
            $logoRelPath = isset($snapshot['logo']) ? ltrim(preg_replace('#^storage/#', '', (string)$snapshot['logo']), '/') : null;
        @endphp
        @if(($layoutSettings['branding']['show_logo'] ?? true) && $logoRelPath)
            @if(isset($preview) && $preview)
                <div style="margin-bottom: 3mm;">
                    <img src="{{ asset('storage/' . $logoRelPath) }}" alt="Logo" style="max-height: 20mm; max-width: 70mm;">
                </div>
            @elseif(\Storage::disk('public')->exists($logoRelPath))
                @php
                    $logoPath = \Storage::disk('public')->path($logoRelPath);
                    $logoData = base64_encode(file_get_contents($logoPath));
                    $logoMime = mime_content_type($logoPath);
                @endphp
                <div style="margin-bottom: 3mm;">
                    <img src="data:{{ $logoMime }};base64,{{ $logoData }}" alt="Logo" style="max-height: 20mm; max-width: 70mm;">
                </div>
            @endif
        @endif
        <div style="font-size: {{ $headingFontSize + 6 }}px; font-weight: 700; color: {{ $layoutSettings['colors']['primary'] ?? '#3b82f6' }}; margin-bottom: 2mm;">
            {{ $snapshot['name'] ?? '' }}
        </div>
        @if($layoutSettings['content']['show_company_address'] ?? true)
            <div style="font-size: {{ $bodyFontSize }}px; color: {{ $layoutSettings['colors']['text'] ?? '#6b7280' }}; line-height: 1.5;">
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
                        <div style="font-size: {{ $bodyFontSize }}px; color: {{ $layoutSettings['colors']['text'] ?? '#6b7280' }}; line-height: 1.3;">
                            @if($customer->address)
                                {{ $customer->address }}<br>
                            @endif
                            @if($customer->postal_code && $customer->city)
                                {{ $customer->postal_code }} {{ $customer->city }}
                                @if($customer->country && $customer->country !== 'Deutschland')
                                    <br>{{ $customer->country }}
                                @endif
                            @endif
                            @if(isset($invoice->customer->vat_number) && $invoice->customer->vat_number)
                                <br>USt-IdNr.: {{ $invoice->customer->vat_number }}
                            @endif
                        </div>
                    </div>
                @endif
            </td>
            <td style="width: 50%; vertical-align: top;">
                @php $detailsStyle = 'padding: 3mm; background-color: ' . ($layoutSettings['colors']['accent'] ?? '#f3f4f6') . '; border-radius: 2mm;'; @endphp
                @include('pdf.invoice-partials.details')
            </td>
        </tr>
    </table>

    {{-- Invoice Title --}}
    <div style="margin-bottom: 15px;">
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
    <div style="margin-bottom: 15px; font-size: {{ $bodyFontSize }}px; line-height: 1.6;">
        <div style="margin-bottom: 4px;">Sehr geehrte Damen und Herren,</div>
        <div>vielen Dank für Ihren Auftrag und das damit verbundene Vertrauen! Hiermit stelle ich Ihnen die folgenden Leistungen in Rechnung:</div>
    </div>

    {{-- Items Table with colored header - DISTINCTIVE: Blue header --}}
    @php
        $tableHeaderBg = $layoutSettings['colors']['primary'] ?? '#3b82f6';
        $altRowBg      = $layoutSettings['colors']['accent'] ?? '#f9fafb';
        $cellPadding   = '10px 8px';
    @endphp
    @include('pdf.invoice-partials.items-table')

    {{-- VAT Regime Note --}}
    @php $vatNote = getVatRegimeNote($invoice->vat_regime ?? 'standard'); @endphp
    @if($vatNote)
        <div style="margin-top: 10px; font-size: {{ $bodyFontSize }}px; font-style: italic;">
            {{ $vatNote }}
        </div>
    @endif

    {{-- Totals --}}
    <div style="margin-top: 15px;">
        @include('pdf.invoice-partials.totals')
    </div>

    {{-- Payment instructions --}}
    @include('pdf.invoice-partials.payment-terms')

    {{-- Closing --}}
    <div style="margin-top: 20px; font-size: {{ $bodyFontSize }}px;">
        <div style="margin-bottom: 4px;">Mit freundlichen Grüßen</div>
        <div style="font-weight: 600;">{{ $snapshot['name'] ?? '' }}</div>
    </div>

    {{-- Footer Style B: Compact single-line (German Example 2 & 4) --}}
    @if($layoutSettings['branding']['show_footer'] ?? true)
    <div class="pdf-footer" style="margin-top: 15mm; border-top: 1px solid {{ $layoutSettings['colors']['accent'] ?? '#e5e7eb' }}; padding-top: 3mm;">
        <div style="font-size: 7pt; line-height: 1.5; color: {{ $layoutSettings['colors']['text'] ?? '#6b7280' }}; text-align: left;">
            {{ $snapshot['name'] ?? '' }} · {{ $snapshot['address'] ?? '' }} · {{ $snapshot['postal_code'] ?? '' }} {{ $snapshot['city'] ?? '' }} 
            FON {{ $snapshot['phone'] ?? '' }} MAIL {{ $snapshot['email'] ?? '' }} @if($snapshot['website'] ?? null)WEB {{ $snapshot['website'] }}@endif
            @if($layoutSettings['content']['show_bank_details'] ?? true)
                BANK {{ $snapshot['bank_name'] ?? '' }} 
            @endif
            IBAN {{ $snapshot['bank_iban'] ?? '' }} @if($snapshot['bank_bic'] ?? null)BIC {{ $snapshot['bank_bic'] }}@endif @if($snapshot['tax_number'] ?? null)STEUERNUMMER {{ $snapshot['tax_number'] }}@endif @if($snapshot['vat_number'] ?? null)UST-ID {{ $snapshot['vat_number'] }}@endif
        </div>
    </div>
    @endif

</div>
