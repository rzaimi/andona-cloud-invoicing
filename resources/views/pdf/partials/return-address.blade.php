{{--
    DIN 5008 return line above the recipient address.
    Einzelunternehmen: company name, then the Inhaber, address on the next line.
    Every other legal form stays on one line.
    Expects: $snapshot. Optional: $senderName, $ls, $returnColor, $returnBorder.
--}}
@php
    $ls = $ls ?? ($layoutSettings ?? []);
    if (!($ls['content']['show_company_address'] ?? true)) {
        $lines = [];
    } else {
        $companyName = trim((string) ($snapshot['name'] ?? ''));
        $inhaber = trim((string) ($snapshot['managing_director'] ?? ''));
        $street = trim((string) ($snapshot['address'] ?? ''));
        $cityLine = trim(($snapshot['postal_code'] ?? '').' '.($snapshot['city'] ?? ''));
        $addressLine = implode(' · ', array_filter([$street, $cityLine]));

        if (($snapshot['legal_form'] ?? null) === 'einzelunternehmen') {
            $identity = [];
            foreach ([$companyName, $inhaber] as $part) {
                if ($part === '') {
                    continue;
                }
                $seen = false;
                foreach ($identity as $existing) {
                    if (mb_strtolower($existing) === mb_strtolower($part)) {
                        $seen = true;
                        break;
                    }
                }
                if (! $seen) {
                    $identity[] = $part;
                }
            }
            $lines = array_values(array_filter([
                implode(' · ', $identity),
                $addressLine,
            ]));
        } else {
            $who = trim((string) ($senderName ?? ($snapshot['display_name'] ?? $companyName)));
            $lines = array_values(array_filter([
                implode(' · ', array_filter([$who, $street, $cityLine])),
            ]));
        }
    }
    $color = $returnColor ?? ($ls['colors']['secondary'] ?? '#6b7280');
    $rule = $returnBorder ?? '#d1d5db';
@endphp
@if(count($lines))
<div style="font-size:6.5pt; color:{{ $color }}; padding-bottom:0.4mm; margin-bottom:1mm; border-bottom:0.2mm solid {{ $rule }}; line-height:1.25;">
    @foreach($lines as $line)
        <div style="white-space:nowrap; overflow:hidden;">{{ $line }}</div>
    @endforeach
</div>
@endif
