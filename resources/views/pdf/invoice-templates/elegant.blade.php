{{-- Elegant Template: Based on fourth screenshot - Blue company name design --}}
{{-- Template: elegant --}}
<div class="container">
    {{-- Header: Large blue company name top left - DISTINCTIVE FEATURE --}}
    <div style="margin-bottom: 10px;">
        @if(($layoutSettings['branding']['show_logo'] ?? true) && ($snapshot['logo'] ?? null) && \Storage::disk('public')->exists($snapshot['logo']))
            @php
                $logoPath = \Storage::disk('public')->path($snapshot['logo']);
                $logoData = base64_encode(file_get_contents($logoPath));
                $logoMime = mime_content_type($logoPath);
            @endphp
            <div style="margin-bottom: 10px;">
                <img src="data:{{ $logoMime }};base64,{{ $logoData }}" alt="Logo" style="max-height: 60px; max-width: 200px;">
            </div>
        @endif
        <div style="font-size: {{ $headingFontSize + 8 }}px; font-weight: 700; color: {{ $layoutSettings['colors']['primary'] ?? '#2563eb' }}; margin-bottom: 6px; line-height: 1.2;">
            {{ $snapshot['name'] ?? '' }}
        </div>
        @if($layoutSettings['content']['show_company_address'] ?? true)
            <div style="font-size: {{ $bodyFontSize }}px; color: {{ $layoutSettings['colors']['text'] ?? '#1f2937' }}; line-height: 1.5;">
                {{ $snapshot['name'] ?? '' }} - {{ $snapshot['address'] ?? '' }}-{{ $snapshot['postal_code'] ?? '' }} {{ $snapshot['city'] ?? '' }}
            </div>
        @endif
    </div>

    {{-- Invoice Details Right --}}
    <div style="text-align: right; font-size: {{ $bodyFontSize }}px; margin-bottom: 10px;">
        <div style="margin-bottom: 4px;"><strong>RECHNUNGSNR.:</strong> {{ $invoice->number }}</div>
        <div style="margin-bottom: 4px;"><strong>DATUM:</strong> {{ \Carbon\Carbon::parse($invoice->issue_date)->format('d. F Y') }}</div>
        <div style="margin-bottom: 4px;"><strong>LEISTUNGSDATUM:</strong> {{ \Carbon\Carbon::parse($invoice->issue_date)->format('d. F Y') }}</div>
        @if(isset($invoice->customer->number) && $invoice->customer->number)
            <div style="margin-bottom: 4px;"><strong>KUNDENNR.:</strong> {{ $invoice->customer->number }}</div>
        @endif
        @if(isset($invoice->customer->contact_person) && $invoice->customer->contact_person)
            <div><strong>ANSPRECHPARTNER:</strong> {{ $invoice->customer->contact_person }}</div>
        @endif
    </div>

    {{-- Customer number below address if needed (address is handled in invoice.blade.php) --}}
    @if(($layoutSettings['content']['show_customer_number'] ?? true) && isset($invoice->customer->number) && $invoice->customer->number && !($layoutSettings['content']['use_din_5008_address'] ?? true))
        <div style="margin-bottom: 8px; font-size: {{ $bodyFontSize }}px;">
            <strong>Kundennummer:</strong> {{ $invoice->customer->number }}
        </div>
    @endif

    {{-- Invoice Title --}}
    <div style="margin-bottom: 8px;">
        @php
            $isCorrection = isset($invoice->is_correction) ? (bool)$invoice->is_correction : false;
        @endphp
        <div style="font-size: {{ $headingFontSize + 4 }}px; font-weight: 700; color: {{ $isCorrection ? '#dc2626' : ($layoutSettings['colors']['primary'] ?? '#2563eb') }};">
            {{ $isCorrection ? 'STORNORECHNUNG' : 'Rechnung' }} {{ $invoice->number }}
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
    <table style="width: 100%; border-collapse: collapse; margin: 10px 0;">
        <thead>
            <tr style="border-bottom: 2px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};">
                <th style="padding: 8px 6px; text-align: left; font-weight: 600; font-size: {{ $bodyFontSize }}px;">LEISTUNG</th>
                <th style="padding: 8px 6px; text-align: left; font-weight: 600;">UMFANG</th>
                <th style="padding: 8px 6px; text-align: right; font-weight: 600;">PREIS</th>
                <th style="padding: 8px 6px; text-align: right; font-weight: 600;">GESAMT</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
                @php
                    $discountAmount = (float)($item->discount_amount ?? 0);
                    $hasDiscount = $discountAmount > 0.0001;
                    $baseTotal = (float)($item->quantity ?? 0) * (float)($item->unit_price ?? 0);
                    $discountType = $item->discount_type ?? null;
                    $discountValue = $item->discount_value ?? null;
                @endphp
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 8px 6px;">{{ $item->description }}</td>
                    <td style="padding: 8px 6px;">
                        {{ number_format($item->quantity, 2, ',', '.') }}
                        @if($layoutSettings['content']['show_unit_column'] ?? true && isset($item->unit) && $item->unit)
                            {{ $item->unit }}
                        @else
                            Std.
                        @endif
                    </td>
                    <td style="padding: 8px 6px; text-align: right;">{{ number_format($item->unit_price, 2, ',', '.') }} €</td>
                    <td style="padding: 8px 6px; text-align: right;">
                        <div>{{ number_format($item->total, 2, ',', '.') }} €</div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <div style="text-align: right; margin-top: 10px;">
        @php
            $totalDiscount = 0;
            foreach ($invoice->items as $it) {
                $totalDiscount += (float)($it->discount_amount ?? 0);
            }
        @endphp
        <table style="width: 280px; margin-left: auto; border-collapse: collapse;">
            @if($totalDiscount > 0.0001)
                <tr>
                    <td style="padding: 6px 10px; text-align: left; border-bottom: 1px solid #e5e7eb;">Zwischensumme (vor Rabatt)</td>
                    <td style="padding: 6px 10px; text-align: right; border-bottom: 1px solid #e5e7eb;">{{ number_format($invoice->subtotal + $totalDiscount, 2, ',', '.') }} €</td>
                </tr>
                <tr>
                    <td style="padding: 6px 10px; text-align: left; border-bottom: 1px solid #e5e7eb;">Gesamtrabatt</td>
                    <td style="padding: 6px 10px; text-align: right; border-bottom: 1px solid #e5e7eb; color: #dc2626;">-{{ number_format($totalDiscount, 2, ',', '.') }} €</td>
                </tr>
            @endif
            <tr>
                <td style="padding: 6px 10px; text-align: left; border-bottom: 1px solid #e5e7eb;">Gesamtbetrag (netto)</td>
                <td style="padding: 6px 10px; text-align: right; border-bottom: 1px solid #e5e7eb;">{{ number_format($invoice->subtotal, 2, ',', '.') }} €</td>
            </tr>
            <tr>
                <td style="padding: 6px 10px; text-align: left; border-bottom: 1px solid #e5e7eb;">{{ number_format($invoice->tax_rate * 100, 0) }}% Umsatzsteuer</td>
                <td style="padding: 6px 10px; text-align: right; border-bottom: 1px solid #e5e7eb;">{{ number_format($invoice->tax_amount, 2, ',', '.') }} €</td>
            </tr>
            <tr>
                <td style="padding: 8px 10px; text-align: left; font-weight: 700; font-size: {{ $bodyFontSize + 1 }}px;">Gesamtbetrag (brutto)</td>
                <td style="padding: 8px 10px; text-align: right; font-weight: 700; font-size: {{ $bodyFontSize + 1 }}px;">{{ number_format($invoice->total, 2, ',', '.') }} €</td>
            </tr>
        </table>
    </div>

    {{-- Payment Instructions --}}
    @php
        $taxNote = $settings['invoice_tax_note'] ?? null;
    @endphp
    @if($taxNote)
        <div style="margin-top: 12px; font-size: {{ $bodyFontSize ?? 11 }}px; line-height: 1.5;">
            {{ $taxNote }}
        </div>
    @endif
    @if($layoutSettings['content']['show_payment_terms'] ?? true)
        <div style="margin-top: 12px; font-size: {{ $bodyFontSize }}px; line-height: 1.5;">
            Bitte überweisen Sie den Rechnungsbetrag unter Angabe der Rechnungsnummer auf das unten angegebene Konto. Der Rechnungsbetrag ist sofort fällig.
        </div>
    @endif

    {{-- Closing --}}
    <div style="margin-top: 12px; font-size: {{ $bodyFontSize }}px;">
        <div style="margin-bottom: 4px;">Mit freundlichen Grüßen</div>
        <div style="font-weight: 600;">{{ $snapshot['name'] ?? '' }}</div>
    </div>

</div>

