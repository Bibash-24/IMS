<?php
declare(strict_types=1);

namespace IMS\Services;

/**
 * Barcode & QR Code Cryptographic Label Engine
 * Generates Code-128/EAN-13 barcodes, SVG label vectors, and verifiable product QR signatures.
 */
class BarcodeQrService
{
    /**
     * Compute standard EAN-13 check digit using Modulo-10 algorithm.
     */
    public function calculateEan13CheckDigit(string $twelveDigits): int
    {
        $digits = preg_replace('/[^\d]/', '', $twelveDigits);
        if (strlen($digits) !== 12) {
            throw new \InvalidArgumentException("EAN-13 base requires exactly 12 numeric digits.");
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $val = (int) $digits[$i];
            // Odd index (0-based: 1, 3, 5...) multiplied by 3, even index by 1
            $sum += ($i % 2 === 1) ? ($val * 3) : $val;
        }

        $mod = $sum % 10;
        return ($mod === 0) ? 0 : (10 - $mod);
    }

    /**
     * Generate complete 13-digit EAN barcode string.
     */
    public function generateEan13(string $twelveDigits): string
    {
        $check = $this->calculateEan13CheckDigit($twelveDigits);
        return $twelveDigits . $check;
    }

    /**
     * Render an SVG vector representation of a Code-128 pseudo-barcode.
     */
    public function renderSvgBarcode(string $code, int $width = 240, int $height = 70): string
    {
        $hash = hash('sha256', $code);
        $bars = '';
        $x = 10;
        $barWidth = 2;

        for ($i = 0; $i < 48; $i++) {
            $char = hexdec($hash[$i % strlen($hash)]);
            $isBlack = ($char % 2 === 0);
            if ($isBlack) {
                $bars .= "<rect x=\"{$x}\" y=\"10\" width=\"{$barWidth}\" height=\"40\" fill=\"#1e293b\" />";
            }
            $x += $barWidth + 1;
        }

        $safeText = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
    <rect width="100%" height="100%" fill="#ffffff" rx="4" />
    {$bars}
    <text x="{$width}" y="62" text-anchor="middle" font-family="monospace" font-size="11" fill="#334155">{$safeText}</text>
</svg>
SVG;
    }

    /**
     * Generate signed QR code JSON payload for product authenticity verification.
     */
    public function generateSignedQrPayload(array $product, string $signingSecret = 'ims_crypto_key_2026'): array
    {
        $sku = $product['sku'] ?? 'SKU-UNKNOWN';
        $price = (float) ($product['price'] ?? 0.0);
        $timestamp = time();

        $message = "SKU:{$sku}|PRICE:{$price}|TS:{$timestamp}";
        $signature = hash_hmac('sha256', $message, $signingSecret);

        return [
            'sku' => $sku,
            'name' => $product['name'] ?? '',
            'price' => $price,
            'timestamp' => $timestamp,
            'verification_seal' => substr($signature, 0, 16),
            'qr_data_uri' => "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($message)
        ];
    }
}
