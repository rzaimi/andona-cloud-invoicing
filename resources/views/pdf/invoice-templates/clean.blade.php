{{-- Clean Template: Based on first screenshot - Simple, professional German invoice --}}
{{-- Template: clean --}}
<div class="container">
    {{-- Header: Logo and sender address top left with subtle background --}}
    <div style="margin-bottom: 15px; padding: 12px; background-color: #f9fafb; border-radius: 4px;">
        @if(($layoutSettings['branding']['show_logo'] ?? true) && ($snapshot['logo'] ?? null) && \Storage::disk('public')->exists($snapshot['logo']))
            @php
                $logoPath = \Storage::disk('public')->path($snapshot['logo']);
                $logoData = base64_encode(file_get_contents($logoPath));
                $logoMime = mime_content_type($logoPath);
            @endphp
            <div style="margin-bottom: 8px;">
                <img src="data:{{ $logoMime }};base64,{{ $logoData }}" alt="Logo" style="max-height: 60px; max-width: 200px;">
            </div>
        @endif
        @if($layoutSettings['content']['show_company_address'] ?? true)
            <div style="font-size: {{ $bodyFontSize }}px; color: {{ $layoutSettings['colors']['text'] ?? '#1f2937' }}; line-height: 1.5;">
                {{ $snapshot['name'] ?? '' }}<br>
                @if($snapshot['address'] ?? null){{ $snapshot['address'] }}@endif
                @if(($snapshot['postal_code'] ?? null) && ($snapshot['city'] ?? null)), {{ $snapshot['postal_code'] }} {{ $snapshot['city'] }}@endif
            </div>
        @endif
    </div>

    {{-- Customer number below address if needed (address is handled in invoice.blade.php) --}}
    @if(($layoutSettings['content']['show_customer_number'] ?? true) && isset($invoice->customer->number) && $invoice->customer->number && !($layoutSettings['content']['use_din_5008_address'] ?? true))
        <div style="margin-bottom: 8px; font-size: {{ $bodyFontSize }}px;">
            <strong>Kundennummer:</strong> {{ $invoice->customer->number }}
        </div>
    @endif

    {{-- Invoice Title --}}
    <div style="margin-bottom: 15px;">
        @php
            $isCorrection = isset($invoice->is_correction) ? (bool)$invoice->is_correction : false;
        @endphp
        <div style="font-size: {{ $headingFontSize + 4 }}px; font-weight: 700; color: {{ $isCorrection ? '#dc2626' : ($layoutSettings['colors']['primary'] ?? '#1f2937') }};">
            {{ $isCorrection ? 'STORNORECHNUNG' : 'Rechnung' }} {{ $invoice->number }}
        </div>
        @if($isCorrection && isset($invoice->correctsInvoice) && $invoice->correctsInvoice)
            <div style="margin-top: 10px; padding: 10px; background-color: #fee2e2; border-left: 4px solid #dc2626; font-size: {{ $bodyFontSize }}px;">
                <div style="font-weight: 600; color: #991b1b; margin-bottom: 4px;">Storniert Rechnung:</div>
                <div style="color: #7f1d1d;">Nr. {{ $invoice->correctsInvoice->number }} vom {{ formatInvoiceDate($invoice->correctsInvoice->issue_date, $dateFormat) }}</div>
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

    {{-- Items Table --}}
    <table style="width: 100%; border-collapse: collapse; margin: 15px 0;">
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
                    <td style="padding: 8px 6px; text-align: right;">à {{ number_format($item->unit_price, 2, ',', '.') }} €</td>
                    <td style="padding: 8px 6px; text-align: right;">{{ number_format($item->total, 2, ',', '.') }} €</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <div style="text-align: right; margin-top: 15px;">
        <table style="width: 280px; margin-left: auto; border-collapse: collapse;">
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
    @if($layoutSettings['content']['show_payment_terms'] ?? true)
        <div style="margin-top: 20px; font-size: {{ $bodyFontSize }}px; line-height: 1.6;">
            Bitte überweisen Sie den Rechnungsbetrag unter Angabe der Rechnungsnummer auf das unten angegebene Konto. Der Rechnungsbetrag ist sofort fällig.
        </div>
    @endif

    {{-- Closing --}}
    <div style="margin-top: 20px; font-size: {{ $bodyFontSize }}px;">
        <div style="margin-bottom: 4px;">Mit freundlichen Grüßen</div>
        <div style="font-weight: 600;">{{ $snapshot['name'] ?? '' }}</div>
    </div>

</div>
