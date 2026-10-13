<?php

/**
 * DomPDF prefers Imagick for transparent or indexed PNGs (invoice logos).
 * On hosts whose ImageMagick has no PNG coder, that throws
 * "NoDecodeDelegateForThisImageFormat `PNG'" and aborts queued invoice emails.
 * Skip Imagick in that case so DomPDF's existing GD path handles the image.
 * Re-applied after every composer install.
 */
$path = dirname(__DIR__).'/vendor/dompdf/dompdf/lib/Cpdf.php';

if (! is_file($path)) {
    fwrite(STDERR, "dompdf Cpdf.php not found, skipping imagick format patch\n");
    exit(0);
}

$contents = file_get_contents($path);
$changed = false;

$formatPatched = <<<'PHP'
            $imagick = new \Imagick();
            $imagick->setRegistry('temporary-path', $this->tmp);
            // ImageMagick 7 throws "Unable to set format" when setFormat() runs
            // before an image is loaded. readImage() detects PNG from the file.
            try {
                $imagick->setFormat('PNG');
            } catch (\ImagickException) {
                $imagick->clear();
                $imagick = new \Imagick();
                $imagick->setRegistry('temporary-path', $this->tmp);
            }
            $imagick->readImage($file);
PHP;

$formatOriginal = <<<'PHP'
            $imagick = new \Imagick();
            $imagick->setRegistry('temporary-path', $this->tmp);
            $imagick->setFormat('PNG');
            $imagick->readImage($file);
PHP;

if (! str_contains($contents, $formatPatched) && str_contains($contents, $formatOriginal)) {
    $contents = str_replace($formatOriginal, $formatPatched, $contents);
    $changed = true;
}

$gdFallback = <<<'PHP'
        // andobill-imagick-png-gd-fallback: ImageMagick without a PNG coder throws
        // NoDecodeDelegateForThisImageFormat. The else branch below uses GD.
        elseif (extension_loaded("imagick") && (static function () {
            static $canReadPng = null;
            if ($canReadPng !== null) {
                return $canReadPng;
            }
            try {
                $probe = new \Imagick();
                $probe->newImage(1, 1, '#FFFFFF', 'png');
                $blob = $probe->getImageBlob();
                $probe->clear();
                $reader = new \Imagick();
                $reader->readImageBlob($blob);
                $reader->clear();
                $canReadPng = true;
            } catch (\Throwable) {
                $canReadPng = false;
            }

            return $canReadPng;
        })()) {
PHP;

$gdFallbackOriginal = '        elseif (extension_loaded("imagick")) {';

if (! str_contains($contents, 'andobill-imagick-png-gd-fallback')) {
    if (! str_contains($contents, $gdFallbackOriginal)) {
        fwrite(STDERR, "dompdf imagick format patch target not found in Cpdf.php\n");
        exit(1);
    }

    $contents = str_replace($gdFallbackOriginal, $gdFallback, $contents);
    $changed = true;
}

if ($changed) {
    file_put_contents($path, $contents);
}
