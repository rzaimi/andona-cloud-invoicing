{{-- DIN 5008 compliant address block for German envelope windows --}}
{{-- Position: 4.5cm from top, 2cm from left, max 8.5cm × 4.5cm --}}
{{-- This ensures the address aligns perfectly with envelope window when folded --}}
{{-- Works with both invoices and offers --}}
@php
    $customer = $invoice->customer ?? $offer->customer ?? null;
@endphp
@if($customer)
    <div class="din-5008-address">
        <div style="font-weight: 600; margin-bottom: 3px; font-size: {{ $bodyFontSize }}px; line-height: 1.2;">
            {{ $customer->name ?? 'Unbekannt' }}
        </div>
        @if(isset($customer->contact_person) && $customer->contact_person)
            <div style="margin-bottom: 2px; font-size: {{ $bodyFontSize }}px; line-height: 1.2;">{{ $customer->contact_person }}</div>
        @endif
        <div style="font-size: {{ $bodyFontSize }}px; line-height: 1.2;">
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

