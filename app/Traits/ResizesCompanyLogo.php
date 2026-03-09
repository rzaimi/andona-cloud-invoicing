<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

/**
 * Resizes and normalises a company logo before storing it.
 *
 * Keeps the logo within 600 × 300 px (never upscales) and saves as PNG so
 * DomPDF / ImageMagick never has to handle a large raw upload — preventing
 * the "cache resources exhausted" error when generating PDFs.
 */
trait ResizesCompanyLogo
{
    /**
     * Process an uploaded logo, resize it to at most 600 × 300 px,
     * store it under tenants/{companyId}/logo/logo.png and return
     * the relative storage path.
     */
    protected function processAndStoreLogo(UploadedFile $file, string $companyId): string
    {
        $maxW    = 600;
        $maxH    = 300;
        $srcPath = $file->getRealPath();
        $mime    = $file->getMimeType() ?? 'image/jpeg';

        [$origW, $origH] = @getimagesize($srcPath) ?: [0, 0];

        // If we can't determine dimensions (corrupt / unsupported), fall back to raw store
        if (!$origW || !$origH) {
            return $file->store("tenants/{$companyId}/logo", 'public');
        }

        // Never upscale; only shrink when the image exceeds the limit
        $ratio = min($maxW / $origW, $maxH / $origH, 1.0);
        $newW  = (int) round($origW * $ratio);
        $newH  = (int) round($origH * $ratio);

        // Load source with GD depending on MIME
        $src = match (true) {
            str_contains($mime, 'png')  => @imagecreatefrompng($srcPath),
            str_contains($mime, 'gif')  => @imagecreatefromgif($srcPath),
            str_contains($mime, 'webp') => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($srcPath) : false,
            default                     => @imagecreatefromjpeg($srcPath),
        };

        // GD failed to load the image — store the original as-is
        if (!$src) {
            return $file->store("tenants/{$companyId}/logo", 'public');
        }

        // Create destination canvas with full alpha support
        $dst = imagecreatetruecolor($newW, $newH);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
        imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        // Write to a temporary PNG file
        $tmpPath = tempnam(sys_get_temp_dir(), 'logo_') . '.png';
        imagepng($dst, $tmpPath, 6); // level 6 = good compression/speed balance

        imagedestroy($src);
        imagedestroy($dst);

        // Delete the existing logo file to avoid stale files
        $existingFiles = Storage::disk('public')->files("tenants/{$companyId}/logo");
        foreach ($existingFiles as $existing) {
            Storage::disk('public')->delete($existing);
        }

        // Store from the temp file
        $stored = Storage::disk('public')->putFileAs(
            "tenants/{$companyId}/logo",
            new File($tmpPath),
            'logo.png'
        );

        @unlink($tmpPath);

        return $stored;
    }
}
