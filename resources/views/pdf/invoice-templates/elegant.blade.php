{{-- Elegant Template: Based on fourth screenshot - Blue company name design --}}
{{-- Template: elegant --}}
<div class="container">
    {{-- Header: Large blue company name - DISTINCTIVE FEATURE --}}
    <div style="margin-bottom: 8mm;">
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
        <div style="font-size: {{ $headingFontSize + 8 }}px; font-weight: 700; color: {{ $layoutSettings['colors']['primary'] ?? '#2563eb' }}; margin-bottom: 2mm; line-height: 1.2;">
            {{ $snapshot['name'] ?? '' }}
        </div>
        @if($layoutSettings['content']['show_company_address'] ?? true)
            <div style="font-size: {{ $bodyFontSize }}px; color: {{ $layoutSettings['colors']['text'] ?? '#1f2937' }}; line-height: 1.5;">
                {{ $snapshot['name'] ?? '' }} · {{ $snapshot['address'] ?? '' }} · {{ $snapshot['postal_code'] ?? '' }} {{ $snapshot['city'] ?? '' }}
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
        <div style="margin-bottom: 4px;">Sehr geehrte Damen und Herren.</div>
        <div>vielen Dank für Ihren Auftrag und das damit verbundene Vertrauen! Hiermit stelle ich Ihnen die folgenden Leistungen in Rechnung:</div>
    </div>

    {{-- Items Table --}}
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

    @if($layoutSettings['branding']['show_footer'] ?? true)
    @include('pdf.invoice-partials.footer')
    @endif

</div>

