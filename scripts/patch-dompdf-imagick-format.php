<?php

/**
 * DomPDF calls Imagick::setFormat('PNG') before readImage() while embedding
 * transparent or indexed PNGs (invoice logos). ImageMagick 7 and coder
 * policies throw ImagickException "Unable to set format" from that call,
 * which aborts queued invoice emails. Reading the file is enough; its PNG
 * header selects the coder. Re-applied after every composer install.
 */
$path = dirname(__DIR__).'/vendor/dompdf/dompdf/lib/Cpdf.php';

if (! is_file($path)) {
    fwrite(STDERR, "dompdf Cpdf.php not found, skipping imagick format patch\n");
    exit(0);
}

$contents = file_get_contents($path);

$patched = <<<'PHP'
            $imagick = new \Imagick();
            $imagick->setRegistry('temporary-path', $this->tmp);
            // ImageMagick 7 / coder policies throw "Unable to set format" when
            // setFormat() runs before an image is loaded. readImage() detects PNG.
            try {
                $imagick->setFormat('PNG');
            } catch (\ImagickException) {
                $imagick->clear();
                $imagick = new \Imagick();
                $imagick->setRegistry('temporary-path', $this->tmp);
            }
            $imagick->readImage($file);
PHP;

if (str_contains($contents, $patched)) {
    exit(0);
}

$original = <<<'PHP'
            $imagick = new \Imagick();
            $imagick->setRegistry('temporary-path', $this->tmp);
            $imagick->setFormat('PNG');
            $imagick->readImage($file);
PHP;

if (! str_contains($contents, $original)) {
    fwrite(STDERR, "dompdf imagick format patch target not found in Cpdf.php\n");
    exit(1);
}

file_put_contents($path, str_replace($original, $patched, $contents));
