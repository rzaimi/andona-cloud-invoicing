{{-- Classic Template: Traditional German invoice layout with structured design --}}
{{-- Template: classic - DISTINCTIVE: Full borders, dark header, boxed totals --}}
<div class="container">
    {{-- Header: Logo and company info with border --}}
    <div style="margin-bottom: 5mm; padding-bottom: 2mm; border-bottom: 1px solid {{ $layoutSettings['colors']['accent'] ?? '#e5e7eb' }};">
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
        @if($layoutSettings['content']['show_company_address'] ?? true)
            <div style="font-size: {{ $bodyFontSize }}px; color: {{ $layoutSettings['colors']['text'] ?? '#1f2937' }}; line-height: 1.5;">
                {{ $snapshot['name'] ?? '' }}<br>
                @if($snapshot['address'] ?? null){{ $snapshot['address'] }}<br>@endif
                @if(($snapshot['postal_code'] ?? null) && ($snapshot['city'] ?? null)){{ $snapshot['postal_code'] }} {{ $snapshot['city'] }}@endif
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

    {{-- Invoice Title - Classic centered style --}}
    <div style="text-align: center; margin-bottom: 10px;">
        @php
            $isCorrection = isset($invoice->is_correction) ? (bool)$invoice->is_correction : false;
            $invoiceTypeLabel = $isCorrection
                ? 'STORNORECHNUNG'
                : strtoupper(getReadableInvoiceType($invoice->invoice_type ?? 'standard', $invoice->sequence_number ?? null));
        @endphp
        <div style="font-size: {{ $headingFontSize + 6 }}px; font-weight: 700; color: {{ $isCorrection ? '#dc2626' : ($layoutSettings['colors']['text'] ?? '#1f2937') }}; text-transform: uppercase; letter-spacing: 1px;">
            {{ $invoiceTypeLabel }}
        </div>
        <div style="font-size: {{ $bodyFontSize + 1 }}px; color: {{ $layoutSettings['colors']['text'] ?? '#6b7280' }}; margin-top: 4px;">
            Nr. {{ $invoice->number }}
        </div>
        @if($isCorrection && isset($invoice->correctsInvoice) && $invoice->correctsInvoice)
            <div style="margin-top: 10px; padding: 10px; background-color: #fee2e2; border: 2px solid #dc2626; border-radius: 4px; font-size: {{ $bodyFontSize }}px; display: inline-block;">
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

    {{-- Items Table - Classic bordered style --}}
    @php
        $tableHeaderBg    = $layoutSettings['colors']['text'] ?? '#1f2937';
        $tableOuterBorder = '1px solid ' . ($layoutSettings['colors']['text'] ?? '#1f2937');
    @endphp
    @include('pdf.invoice-partials.items-table')

    {{-- Totals - Classic boxed style with VAT breakdown and skonto --}}
    <div style="margin-top: 10px;">
        @php
            $totalDiscount = 0;
            foreach ($invoice->items as $it) {
                $totalDiscount += (float)($it->discount_amount ?? 0);
            }
            $classicBorder  = $layoutSettings['colors']['text'] ?? '#1f2937';
            $isStandardVat  = ($invoice->vat_regime ?? 'standard') === 'standard';
            $vatBreakdownCl = [];
            if ($isStandardVat) {
                foreach ($invoice->items as $item) {
                    $rate = round((float)($item->tax_rate ?? $invoice->tax_rate ?? 0), 4);
                    $k    = (string)$rate;
                    if (!isset($vatBreakdownCl[$k])) $vatBreakdownCl[$k] = ['rate' => $rate, 'net' => 0.0, 'tax' => 0.0];
                    $vatBreakdownCl[$k]['net'] += (float)($item->total ?? 0);
                    $vatBreakdownCl[$k]['tax'] += (float)($item->total ?? 0) * $rate;
                }
                uasort($vatBreakdownCl, fn($a, $b) => $b['rate'] <=> $a['rate']);
            }
            $multipleRatesCl = count($vatBreakdownCl) > 1;
            $skontoAmountCl  = (float)($invoice->skonto_amount ?? 0);
            $hasSkontoC      = !empty($invoice->skonto_percent) && !empty($invoice->skonto_days) && $skontoAmountCl > 0;
        @endphp
        <table style="width: 300px; margin-left: auto; border-collapse: collapse; border: 1px solid {{ $classicBorder }}; font-size: {{ $bodyFontSize }}px;">
            @if($totalDiscount > 0.0001)
                <tr>
                    <td style="padding: 6px 10px; text-align: left; border-bottom: 1px solid {{ $classicBorder }};">Bruttobetrag (vor Rabatt)</td>
                    <td style="padding: 6px 10px; text-align: right; border-bottom: 1px solid {{ $classicBorder }}; white-space: nowrap;">{{ number_format($invoice->subtotal + $totalDiscount, 2, ',', '.') }} €</td>
                </tr>
                <tr>
                    <td style="padding: 6px 10px; text-align: left; border-bottom: 1px solid {{ $classicBorder }}; color: #dc2626;">Rabatt</td>
                    <td style="padding: 6px 10px; text-align: right; border-bottom: 1px solid {{ $classicBorder }}; color: #dc2626; white-space: nowrap;">−{{ number_format($totalDiscount, 2, ',', '.') }} €</td>
                </tr>
            @endif
            <tr>
                <td style="padding: 6px 10px; text-align: left; border-bottom: 1px solid {{ $classicBorder }};">Nettobetrag</td>
                <td style="padding: 6px 10px; text-align: right; border-bottom: 1px solid {{ $classicBorder }}; white-space: nowrap;">{{ number_format($invoice->subtotal, 2, ',', '.') }} €</td>
            </tr>
            @if($isStandardVat)
                @if($multipleRatesCl)
                    @foreach($vatBreakdownCl as $vat)
                        @if($vat['rate'] > 0.0001)
                            <tr>
                                <td style="padding: 6px 10px; text-align: left; border-bottom: 1px solid {{ $classicBorder }};">{{ number_format($vat['rate'] * 100, 0) }}% USt. auf {{ number_format($vat['net'], 2, ',', '.') }} €</td>
                                <td style="padding: 6px 10px; text-align: right; border-bottom: 1px solid {{ $classicBorder }}; white-space: nowrap;">{{ number_format($vat['tax'], 2, ',', '.') }} €</td>
                            </tr>
                        @else
                            <tr>
                                <td style="padding: 6px 10px; text-align: left; border-bottom: 1px solid {{ $classicBorder }}; color: #6b7280;">0% USt. (steuerfrei)</td>
                                <td style="padding: 6px 10px; text-align: right; border-bottom: 1px solid {{ $classicBorder }}; white-space: nowrap;">0,00 €</td>
                            </tr>
                        @endif
                    @endforeach
                @else
                    <tr>
                        <td style="padding: 6px 10px; text-align: left; border-bottom: 1px solid {{ $classicBorder }};">{{ number_format($invoice->tax_rate * 100, 0) }}% Umsatzsteuer</td>
                        <td style="padding: 6px 10px; text-align: right; border-bottom: 1px solid {{ $classicBorder }}; white-space: nowrap;">{{ number_format($invoice->tax_amount, 2, ',', '.') }} €</td>
                    </tr>
                @endif
            @endif
            <tr style="background-color: {{ $layoutSettings['colors']['primary'] ?? '#1f2937' }}; color: white;">
                <td style="padding: 8px 10px; font-weight: 700; font-size: {{ $bodyFontSize + 1 }}px;">Gesamtbetrag (brutto)</td>
                <td style="padding: 8px 10px; text-align: right; font-weight: 700; font-size: {{ $bodyFontSize + 1 }}px; white-space: nowrap;">{{ number_format($invoice->total, 2, ',', '.') }} €</td>
            </tr>
            @if($hasSkontoC)
                <tr style="color: #16a34a;">
                    <td style="padding: 6px 10px; text-align: left; font-style: italic; border-top: 2px solid #dcfce7;">
                        Skonto {{ number_format($invoice->skonto_percent, 0) }}% bis {{ formatInvoiceDate($invoice->skonto_due_date, $dateFormat ?? 'd.m.Y') }}
                    </td>
                    <td style="padding: 6px 10px; text-align: right; font-style: italic; border-top: 2px solid #dcfce7; white-space: nowrap;">
                        −{{ number_format($skontoAmountCl, 2, ',', '.') }} €
                    </td>
                </tr>
                <tr style="color: #16a34a; font-weight: 700;">
                    <td style="padding: 6px 10px; border-bottom: 2px solid #16a34a;">Bei Skonto zahlen Sie</td>
                    <td style="padding: 6px 10px; text-align: right; border-bottom: 2px solid #16a34a; white-space: nowrap;">{{ number_format($invoice->total - $skontoAmountCl, 2, ',', '.') }} €</td>
                </tr>
            @endif
        </table>
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

