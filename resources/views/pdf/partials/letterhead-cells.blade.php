{{--
    Three letterhead cells: logo in the chosen column, optional text in
    the opposite column. A centered logo leaves both sides empty so the
    text cannot collide with it. First page only — continuation pages
    draw the logo from page-script.
    Expects: $logoCell, $logoPos, $ls. Optional: $letterheadColor, $fs.
--}}
@php
    $logoPos = match ($logoPos ?? 'top-left') {
        'left', 'top-left' => 'top-left',
        'center', 'top-center' => 'top-center',
        'right', 'top-right' => 'top-right',
        default => 'top-left',
    };

    $raw = trim((string) data_get($ls ?? [], 'branding.letterhead_text', ''));
    $parts = preg_split('/\R/u', $raw) ?: [];
    $lines = [];
    foreach ($parts as $part) {
        $part = trim((string) $part);
        if ($part === '') {
            continue;
        }
        $lines[] = $part;
        if (count($lines) === 4) {
            break;
        }
    }

    $textHtml = '';
    if ($lines !== [] && $logoPos !== 'top-center') {
        $align = $logoPos === 'top-right' ? 'left' : 'right';
        $color = $letterheadColor ?? '#64748b';
        $px = max(8, (int) ($fs ?? 10) - 1);
        $textHtml = '<div style="max-height:28mm; overflow:hidden; font-size:'.$px.'px; line-height:1.35; color:'.e($color).'; text-align:'.$align.';">'
            .implode('<br>', array_map('e', $lines))
            .'</div>';
    }

    [$colL, $colC, $colR] = match ($logoPos) {
        'top-center' => ['', $logoCell, ''],
        'top-right' => [$textHtml, '', $logoCell],
        default => [$logoCell, '', $textHtml],
    };
@endphp
<td style="width:40%; vertical-align:middle;">{!! $colL !!}</td>
<td style="width:20%; vertical-align:middle; text-align:center;">{!! $colC !!}</td>
<td style="width:40%; vertical-align:middle; text-align:right;">{!! $colR !!}</td>
