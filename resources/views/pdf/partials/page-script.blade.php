{{--
    Page number on every page, and the company logo on pages after the
    first when branding.logo_on_every_page is on. Drawn from page_script
    so the logo is not a position:fixed element (those repeat on page 1
    and would double the letterhead).
    Expects: $snapshot, $layoutSettings, and the metrics set by
    invoice.blade.php / offer.blade.php.
--}}
@php
    $logoOnEveryPage = (bool) ($logoOnEveryPage ?? ($layoutSettings['branding']['logo_on_every_page'] ?? false));
    $logoPos = $layoutSettings['branding']['logo_position'] ?? 'top-left';
    $logoPos = match ($logoPos) {
        'left', 'top-left' => 'top-left',
        'center', 'top-center' => 'top-center',
        'right', 'top-right' => 'top-right',
        default => 'top-left',
    };

    $logoHmm = $continuationLogoHmm ?? 16;
    $logoWmm = $continuationLogoWmm ?? 54;
    $mmToPt = 72 / 25.4;
    $logoMaxW = round($logoWmm * $mmToPt, 2);
    $logoMaxH = round($logoHmm * $mmToPt, 2);

    $logoRelPath = isset($snapshot['logo'])
        ? ltrim((string) preg_replace('#^storage/#', '', (string) $snapshot['logo']), '/')
        : '';
    $logoAbsPath = ($logoOnEveryPage && $logoRelPath !== '' && \Storage::disk('public')->exists($logoRelPath))
        ? \Storage::disk('public')->path($logoRelPath)
        : '';
@endphp
<script type="text/php">
if (isset($pdf)) {
    $continuationLogo = [
        'enabled' => {{ $logoOnEveryPage ? 'true' : 'false' }},
        'path' => {!! json_encode($logoAbsPath, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!},
        'maxW' => {{ $logoMaxW }},
        'maxH' => {{ $logoMaxH }},
        'pos' => {!! json_encode($logoPos) !!},
        'hideFooterExceptLast' => {{ ($footerLastPageOnly ?? false) ? 'true' : 'false' }},
    ];
    $pdf->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) use ($continuationLogo) {
        if (!empty($continuationLogo['enabled']) && $pageNumber > 1 && $continuationLogo['path'] !== '' && is_file($continuationLogo['path'])) {
            $drawW = $continuationLogo['maxW'];
            $drawH = $continuationLogo['maxH'];
            $info = @getimagesize($continuationLogo['path']);
            if (is_array($info) && !empty($info[0]) && !empty($info[1])) {
                // Match the letterhead <img>: CSS pixels at 96dpi, capped by
                // the configured max width and height, never enlarged.
                $pxToPt = 72 / 96;
                $naturalW = $info[0] * $pxToPt;
                $naturalH = $info[1] * $pxToPt;
                $scale = min(1, $continuationLogo['maxW'] / $naturalW, $continuationLogo['maxH'] / $naturalH);
                $drawW = $naturalW * $scale;
                $drawH = $naturalH * $scale;
            }
            $pageW = $canvas->get_width();
            $inset = 20 * 72 / 25.4;
            if ($continuationLogo['pos'] === 'top-center') {
                $x = ($pageW - $drawW) / 2;
            } elseif ($continuationLogo['pos'] === 'top-right') {
                $x = $pageW - $drawW - $inset;
            } else {
                $x = $inset;
            }
            $y = 4 * 72 / 25.4;
            $canvas->image($continuationLogo['path'], $x, $y, $drawW, $drawH);
        }

        // The footer is position:fixed, so DomPDF paints it at the bottom of
        // every page. Cover that band on every page except the last.
        if (!empty($continuationLogo['hideFooterExceptLast']) && $pageNumber < $pageCount) {
            // Body reserves 35mm and the page margin adds 5mm, so the
            // fixed footer — including its top rule — sits in this band.
            $cover = 40 * 72 / 25.4;
            $canvas->filled_rectangle(0, $canvas->get_height() - $cover, $canvas->get_width(), $cover, [1, 1, 1]);
        }

        $font = $fontMetrics->get_font('DejaVu Sans', 'normal');
        $size = 6.5;
        $text = "Seite {$pageNumber} / {$pageCount}";
        $width = $fontMetrics->get_text_width($text, $font, $size);
        $x = $canvas->get_width() - $width - 23;
        $y = $canvas->get_height() - 12;
        $canvas->text($x, $y, $text, $font, $size, [0.4, 0.4, 0.4]);
    });
}
</script>
