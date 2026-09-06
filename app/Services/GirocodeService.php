<?php

namespace App\Services;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Log;

/**
 * EPC-QR-Code ("Girocode") für SEPA-Überweisungen: mit der Banking-App
 * scannen und Empfänger, IBAN, Betrag und Verwendungszweck sind vorausgefüllt.
 */
class GirocodeService
{
    /**
     * EPC069-12 payload, version 002 (BIC optional within the EEA).
     */
    public function payload(string $accountHolder, string $iban, float $amount, string $reference): ?string
    {
        $iban = strtoupper(preg_replace('/\s+/', '', $iban) ?? '');
        $accountHolder = trim($accountHolder);
        $reference = trim($reference);

        if ($iban === '' || $accountHolder === '' || $amount < 0.01 || $amount > 999999999.99) {
            return null;
        }

        return implode("\n", [
            'BCD',
            '002',
            '1', // UTF-8
            'SCT',
            '', // BIC (optional in version 002)
            mb_substr($accountHolder, 0, 70),
            $iban,
            'EUR'.number_format($amount, 2, '.', ''),
            '', // purpose code
            '', // structured reference
            mb_substr($reference, 0, 140),
        ]);
    }

    /**
     * Binary PNG of the Girocode, or null when the data is unusable or the
     * GD extension is missing. Callers treat null as "no QR in this mail".
     */
    public function png(string $accountHolder, string $iban, float $amount, string $reference): ?string
    {
        $payload = $this->payload($accountHolder, $iban, $amount, $reference);

        if ($payload === null || ! extension_loaded('gd')) {
            return null;
        }

        try {
            $options = new QROptions([
                'outputInterface' => QRGdImagePNG::class,
                'outputBase64' => false,
                // The EPC spec caps error correction at level M.
                'eccLevel' => EccLevel::M,
                'scale' => 4,
                'quietzoneSize' => 2,
            ]);

            return (new QRCode($options))->render($payload);
        } catch (\Throwable $e) {
            Log::warning('Girocode generation failed', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
