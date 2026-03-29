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

        // If already within limits and PNG, store directly without GD processing
        if ($ratio >= 1.0 && str_contains($mime, 'png')) {
            return $this->storeLogoFile($file->getRealPath(), $companyId);
        }

        // GD loads images fully uncompressed: width × height × 4 bytes per pixel.
        // We need memory for both the source and destination canvas plus 20 % headroom.
        $requiredBytes = (int) (($origW * $origH + $newW * $newH) * 4 * 1.2);
        $previousLimit = $this->raiseMemoryIfNeeded($requiredBytes);

        // Load source with GD depending on MIME
        $src = match (true) {
            str_contains($mime, 'png')  => @imagecreatefrompng($srcPath),
            str_contains($mime, 'gif')  => @imagecreatefromgif($srcPath),
            str_contains($mime, 'webp') => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($srcPath) : false,
            default                     => @imagecreatefromjpeg($srcPath),
        };

        if ($previousLimit !== null) {
            ini_set('memory_limit', $previousLimit);
        }

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
        imagepng($dst, $tmpPath, 6);

        imagedestroy($src);
        imagedestroy($dst);

        $stored = $this->storeLogoFile($tmpPath, $companyId);

        @unlink($tmpPath);

        return $stored;
    }

    /**
     * Delete existing logo files and store a new one from a local path.
     */
    private function storeLogoFile(string $localPath, string $companyId): string
    {
        $existing = Storage::disk('public')->files("tenants/{$companyId}/logo");
        foreach ($existing as $f) {
            Storage::disk('public')->delete($f);
        }

        return Storage::disk('public')->putFileAs(
            "tenants/{$companyId}/logo",
            new File($localPath),
            'logo.png'
        );
    }

    /**
     * Temporarily raise the PHP memory limit if the current free memory is
     * insufficient for the GD operation. Returns the previous limit string so
     * the caller can restore it, or null if no change was needed.
     */
    private function raiseMemoryIfNeeded(int $requiredBytes): ?string
    {
        $currentLimitStr = ini_get('memory_limit');
        $currentLimit    = $this->parseMemoryBytes($currentLimitStr);

        // -1 means unlimited
        if ($currentLimit === -1) {
            return null;
        }

        $freeMemory = $currentLimit - memory_get_usage(true);

        if ($freeMemory >= $requiredBytes) {
            return null;
        }

        // Round up to the next 64 MB boundary above what we need
        $needed      = memory_get_usage(true) + $requiredBytes;
        $newLimit    = (int) (ceil($needed / (64 * 1024 * 1024)) * 64 * 1024 * 1024);
        $newLimitStr = ($newLimit / (1024 * 1024)) . 'M';

        ini_set('memory_limit', $newLimitStr);

        return $currentLimitStr;
    }

    /**
     * Parse a PHP memory_limit string (e.g. "128M", "1G") into bytes.
     */
    private function parseMemoryBytes(string $limit): int
    {
        $limit = trim($limit);

        if ($limit === '-1') {
            return -1;
        }

        $unit  = strtolower(substr($limit, -1));
        $value = (int) $limit;

        return match ($unit) {
            'g'     => $value * 1024 * 1024 * 1024,
            'm'     => $value * 1024 * 1024,
            'k'     => $value * 1024,
            default => $value,
        };
    }
}
