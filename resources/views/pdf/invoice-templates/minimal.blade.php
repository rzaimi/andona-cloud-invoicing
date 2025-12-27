{{-- Minimal Template: Ultra-clean, minimal design with focus on content --}}
{{-- Template: minimal - DISTINCTIVE: No borders, minimal spacing, ultra-clean --}}
<div class="container">
    {{-- Header: Logo and sender address top left, minimal spacing --}}
    <div style="margin-bottom: 12px;">
        @if(($layoutSettings['branding']['show_logo'] ?? true) && ($snapshot['logo'] ?? null) && \Storage::disk('public')->exists($snapshot['logo']))
            @php
                $logoPath = \Storage::disk('public')->path($snapshot['logo']);
                $logoData = base64_encode(file_get_contents($logoPath));
                $logoMime = mime_content_type($logoPath);
            @endphp
            <div style="margin-bottom: 6px;">
                <img src="data:{{ $logoMime }};base64,{{ $logoData }}" alt="Logo" style="max-height: 50px; max-width: 180px;">
            </div>
        @endif
        @if($layoutSettings['content']['show_company_address'] ?? true)
            <div style="font-size: {{ $bodyFontSize }}px; color: {{ $layoutSettings['colors']['text'] ?? '#1f2937' }}; line-height: 1.4;">
                {{ $snapshot['name'] ?? '' }}<br>
                @if($snapshot['address'] ?? null){{ $snapshot['address'] }}@endif
                @if(($snapshot['postal_code'] ?? null) && ($snapshot['city'] ?? null)), {{ $snapshot['postal_code'] }} {{ $snapshot['city'] }}@endif
            </div>
        @endif
    </div>

    {{-- Customer number below address if needed (address is handled in invoice.blade.php) --}}
    @if(($layoutSettings['content']['show_customer_number'] ?? true) && isset($invoice->customer->number) && $invoice->customer->number && !($layoutSettings['content']['use_din_5008_address'] ?? true))
        <div style="margin-bottom: 6px; font-size: {{ $bodyFontSize }}px;">
            <strong>Kundennummer:</strong> {{ $invoice->customer->number }}
        </div>
    @endif

    {{-- Invoice Title - Minimal styling --}}
    <div style="margin-bottom: 10px;">
        @php
            $isCorrection = isset($invoice->is_correction) ? (bool)$invoice->is_correction : false;
        @endphp
        <div style="font-size: {{ $headingFontSize + 2 }}px; font-weight: 600; color: {{ $isCorrection ? '#dc2626' : ($layoutSettings['colors']['text'] ?? '#1f2937') }};">
            {{ $isCorrection ? 'STORNORECHNUNG' : 'Rechnung' }} {{ $invoice->number }}
        </div>
        @if($isCorrection && isset($invoice->correctsInvoice) && $invoice->correctsInvoice)
            <div style="margin-top: 8px; padding: 8px; background-color: #fee2e2; border-left: 3px solid #dc2626; font-size: {{ $bodyFontSize }}px;">
                <div style="font-weight: 600; color: #991b1b; margin-bottom: 3px;">Storniert Rechnung:</div>
                <div style="color: #7f1d1d;">Nr. {{ $invoice->correctsInvoice->number }} vom {{ \Carbon\Carbon::parse($invoice->correctsInvoice->issue_date)->format('d.m.Y') }}</div>
                @if(isset($invoice->correction_reason) && $invoice->correction_reason)
                    <div style="margin-top: 4px; padding-top: 4px; border-top: 1px solid #dc2626;">
                        <strong>Grund:</strong> {{ $invoice->correction_reason }}
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Salutation and Introduction - Minimal --}}
    <div style="margin-bottom: 12px; font-size: {{ $bodyFontSize }}px; line-height: 1.5;">
        <div style="margin-bottom: 6px;">Sehr geehrte Damen und Herren,</div>
        <div>vielen Dank für Ihren Auftrag und das damit verbundene Vertrauen! Hiermit stelle ich Ihnen die folgenden Leistungen in Rechnung:</div>
    </div>

    {{-- Items Table - Minimal borders - DISTINCTIVE: Very thin borders --}}
    <table style="width: 100%; border-collapse: collapse; margin: 8px 0;">
        <thead>
            <tr style="border-bottom: 0.5px solid #d1d5db;">
                <th style="padding: 6px 4px; text-align: left; font-weight: 600; font-size: {{ $bodyFontSize }}px;">Leistung</th>
                <th style="padding: 6px 4px; text-align: left; font-weight: 600;">Umfang</th>
                <th style="padding: 6px 4px; text-align: right; font-weight: 600;">Preis</th>
                <th style="padding: 6px 4px; text-align: right; font-weight: 600;">Gesamt</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 6px 4px;">{{ $item->description }}</td>
                    <td style="padding: 6px 4px;">
                        {{ number_format($item->quantity, 2, ',', '.') }}
                        @if($layoutSettings['content']['show_unit_column'] ?? true && isset($item->unit) && $item->unit)
                            {{ $item->unit }}
                        @else
                            Std.
                        @endif
                    </td>
                    <td style="padding: 6px 4px; text-align: right;">à {{ number_format($item->unit_price, 2, ',', '.') }} €</td>
                    <td style="padding: 6px 4px; text-align: right; font-weight: 600;">{{ number_format($item->total, 2, ',', '.') }} €</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals - Minimal styling --}}
    <div style="text-align: right; margin-top: 8px;">
        <table style="width: 260px; margin-left: auto; border-collapse: collapse;">
            <tr>
                <td style="padding: 4px 8px; text-align: left; border-bottom: 1px solid #e5e7eb;">Gesamtbetrag (netto)</td>
                <td style="padding: 4px 8px; text-align: right; border-bottom: 1px solid #e5e7eb;">{{ number_format($invoice->subtotal, 2, ',', '.') }} €</td>
            </tr>
            <tr>
                <td style="padding: 4px 8px; text-align: left; border-bottom: 1px solid #e5e7eb;">{{ number_format($invoice->tax_rate * 100, 0) }}% Umsatzsteuer</td>
                <td style="padding: 4px 8px; text-align: right; border-bottom: 1px solid #e5e7eb;">{{ number_format($invoice->tax_amount, 2, ',', '.') }} €</td>
            </tr>
            <tr>
                <td style="padding: 6px 8px; text-align: left; font-weight: 700; font-size: {{ $bodyFontSize + 1 }}px;">Gesamtbetrag (brutto)</td>
                <td style="padding: 6px 8px; text-align: right; font-weight: 700; font-size: {{ $bodyFontSize + 1 }}px;">{{ number_format($invoice->total, 2, ',', '.') }} €</td>
            </tr>
        </table>
    </div>

    {{-- Payment Instructions - Minimal --}}
    @if($layoutSettings['content']['show_payment_terms'] ?? true)
        <div style="margin-top: 10px; font-size: {{ $bodyFontSize }}px; line-height: 1.4;">
            Bitte überweisen Sie den Rechnungsbetrag unter Angabe der Rechnungsnummer auf das unten angegebene Konto. Der Rechnungsbetrag ist sofort fällig.
        </div>
    @endif

    {{-- Closing - Minimal --}}
    <div style="margin-top: 10px; font-size: {{ $bodyFontSize }}px;">
        <div style="margin-bottom: 3px;">Mit freundlichen Grüßen</div>
        <div style="font-weight: 600;">{{ $snapshot['name'] ?? '' }}</div>
    </div>

</div>

