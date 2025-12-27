{{-- Classic Template: Traditional German invoice layout with structured design --}}
{{-- Template: classic - DISTINCTIVE: Full borders, dark header, boxed totals --}}
<div class="container">
    {{-- Header: Logo and sender address top left, invoice details top right --}}
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 1px solid {{ $layoutSettings['colors']['accent'] ?? '#e5e7eb' }};">
        {{-- Left: Sender with logo --}}
        <div style="flex: 1;">
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
                    @if(($snapshot['postal_code'] ?? null) && ($snapshot['city'] ?? null)) {{ $snapshot['postal_code'] }} {{ $snapshot['city'] }}@endif
                </div>
            @endif
        </div>
        
        {{-- Right: Invoice details --}}
        <div style="text-align: right; font-size: {{ $bodyFontSize }}px;">
            <div style="margin-bottom: 4px;"><strong>Rechnungsnummer:</strong> {{ $invoice->number }}</div>
            <div style="margin-bottom: 4px;"><strong>Rechnungsdatum:</strong> {{ \Carbon\Carbon::parse($invoice->issue_date)->format('d.m.Y') }}</div>
            <div style="margin-bottom: 4px;"><strong>Fälligkeitsdatum:</strong> {{ \Carbon\Carbon::parse($invoice->due_date)->format('d.m.Y') }}</div>
            @if(isset($invoice->customer->number) && $invoice->customer->number)
                <div style="margin-bottom: 4px;"><strong>Kundennummer:</strong> {{ $invoice->customer->number }}</div>
            @endif
            @if(isset($invoice->customer->contact_person) && $invoice->customer->contact_person)
                <div><strong>Ansprechpartner:</strong> {{ $invoice->customer->contact_person }}</div>
            @endif
        </div>
    </div>

    {{-- Customer number below address if needed (address is handled in invoice.blade.php) --}}
    @if(($layoutSettings['content']['show_customer_number'] ?? true) && isset($invoice->customer->number) && $invoice->customer->number && !($layoutSettings['content']['use_din_5008_address'] ?? true))
        <div style="margin-bottom: 8px; font-size: {{ $bodyFontSize }}px;">
            <strong>Kundennummer:</strong> {{ $invoice->customer->number }}
        </div>
    @endif

    {{-- Invoice Title - Classic centered style --}}
    <div style="text-align: center; margin-bottom: 10px;">
        @php
            $isCorrection = isset($invoice->is_correction) ? (bool)$invoice->is_correction : false;
        @endphp
        <div style="font-size: {{ $headingFontSize + 6 }}px; font-weight: 700; color: {{ $isCorrection ? '#dc2626' : ($layoutSettings['colors']['text'] ?? '#1f2937') }}; text-transform: uppercase; letter-spacing: 1px;">
            {{ $isCorrection ? 'STORNORECHNUNG' : 'RECHNUNG' }}
        </div>
        <div style="font-size: {{ $bodyFontSize + 1 }}px; color: {{ $layoutSettings['colors']['text'] ?? '#6b7280' }}; margin-top: 4px;">
            Nr. {{ $invoice->number }}
        </div>
        @if($isCorrection && isset($invoice->correctsInvoice) && $invoice->correctsInvoice)
            <div style="margin-top: 10px; padding: 10px; background-color: #fee2e2; border: 2px solid #dc2626; border-radius: 4px; font-size: {{ $bodyFontSize }}px; display: inline-block;">
                <div style="font-weight: 600; color: #991b1b; margin-bottom: 4px;">Storniert Rechnung:</div>
                <div style="color: #7f1d1d;">Nr. {{ $invoice->correctsInvoice->number }} vom {{ \Carbon\Carbon::parse($invoice->correctsInvoice->issue_date)->format('d.m.Y') }}</div>
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

    {{-- Items Table - Classic bordered style --}}
    <table style="width: 100%; border-collapse: collapse; margin: 10px 0; border: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};">
        <thead>
            <tr style="background-color: {{ $layoutSettings['colors']['text'] ?? '#1f2937' }}; color: white;">
                <th style="padding: 8px 6px; text-align: left; font-weight: 600; font-size: {{ $bodyFontSize }}px; border-right: 1px solid white;">Nr.</th>
                <th style="padding: 8px 6px; text-align: left; font-weight: 600; border-right: 1px solid white;">Leistung</th>
                <th style="padding: 8px 6px; text-align: center; font-weight: 600; border-right: 1px solid white;">Umfang</th>
                <th style="padding: 8px 6px; text-align: right; font-weight: 600; border-right: 1px solid white;">Preis</th>
                <th style="padding: 8px 6px; text-align: right; font-weight: 600;">Gesamt</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
                <tr style="border-bottom: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};">
                    <td style="padding: 8px 6px; border-right: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};">{{ $index + 1 }}</td>
                    <td style="padding: 8px 6px; border-right: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};">{{ $item->description }}</td>
                    <td style="padding: 8px 6px; text-align: center; border-right: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};">
                        {{ number_format($item->quantity, 2, ',', '.') }}
                        @if($layoutSettings['content']['show_unit_column'] ?? true && isset($item->unit) && $item->unit)
                            {{ $item->unit }}
                        @else
                            Std.
                        @endif
                    </td>
                    <td style="padding: 8px 6px; text-align: right; border-right: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};">à {{ number_format($item->unit_price, 2, ',', '.') }} €</td>
                    <td style="padding: 8px 6px; text-align: right; font-weight: 600;">{{ number_format($item->total, 2, ',', '.') }} €</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals - Classic boxed style --}}
    <div style="text-align: right; margin-top: 10px;">
        <table style="width: 300px; margin-left: auto; border-collapse: collapse; border: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};">
            <tr>
                <td style="padding: 6px 10px; text-align: left; border-bottom: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};">Gesamtbetrag (netto)</td>
                <td style="padding: 6px 10px; text-align: right; border-bottom: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};">{{ number_format($invoice->subtotal, 2, ',', '.') }} €</td>
            </tr>
            <tr>
                <td style="padding: 6px 10px; text-align: left; border-bottom: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};">{{ number_format($invoice->tax_rate * 100, 0) }}% Umsatzsteuer</td>
                <td style="padding: 6px 10px; text-align: right; border-bottom: 1px solid {{ $layoutSettings['colors']['text'] ?? '#1f2937' }};">{{ number_format($invoice->tax_amount, 2, ',', '.') }} €</td>
            </tr>
            <tr style="background-color: {{ $layoutSettings['colors']['primary'] ?? '#1f2937' }}; color: white;">
                <td style="padding: 8px 10px; font-weight: 700; font-size: {{ $bodyFontSize + 1 }}px;">Rechnungsbetrag</td>
                <td style="padding: 8px 10px; text-align: right; font-weight: 700; font-size: {{ $bodyFontSize + 1 }}px;">{{ number_format($invoice->total, 2, ',', '.') }} €</td>
            </tr>
        </table>
    </div>

    {{-- Payment Instructions --}}
    @if($layoutSettings['content']['show_payment_terms'] ?? true)
        <div style="margin-top: 12px; padding: 8px; background-color: {{ $layoutSettings['colors']['accent'] ?? '#f9fafb' }}; border-left: 4px solid {{ $layoutSettings['colors']['primary'] ?? '#1f2937' }}; font-size: {{ $bodyFontSize }}px; line-height: 1.5;">
            <strong>Zahlungsbedingungen:</strong> Der Rechnungsbetrag ist innerhalb von {{ \Carbon\Carbon::parse($invoice->due_date)->diffInDays(\Carbon\Carbon::parse($invoice->issue_date)) }} Tagen nach Rechnungseingang fällig und ohne Abzug auf das unten angegebene Konto zu überweisen.
        </div>
    @endif

    {{-- Closing --}}
    <div style="margin-top: 12px; font-size: {{ $bodyFontSize }}px;">
        <div style="margin-bottom: 4px;">Mit freundlichen Grüßen</div>
        <div style="font-weight: 600;">{{ $snapshot['name'] ?? '' }}</div>
    </div>

</div>

