<?php
declare(strict_types=1);

namespace IMS\Tests;

use IMS\Services\BarcodeQrService;

class BarcodeQrTest extends TestCase
{
    private BarcodeQrService $barcodeService;

    public function setUp(): void
    {
        parent::setUp();
        $this->barcodeService = new BarcodeQrService();
    }

    public function testEan13CheckDigitCalculation(): void
    {
        // Standard barcode: 400638133393 -> check digit is 1
        $check = $this->barcodeService->calculateEan13CheckDigit('400638133393');
        $this->assertEquals(1, $check);

        $fullBarcode = $this->barcodeService->generateEan13('400638133393');
        $this->assertEquals('4006381333931', $fullBarcode);
    }

    public function testRenderSvgBarcodeContainsElements(): void
    {
        $svg = $this->barcodeService->renderSvgBarcode('SKU-DELL-15', 260, 80);

        $this->assertContains('<svg', $svg);
        $this->assertContains('</svg>', $svg);
        $this->assertContains('SKU-DELL-15', $svg);
        $this->assertContains('<rect', $svg);
    }

    public function testSignedQrPayloadIntegrity(): void
    {
        $product = [
            'sku' => 'SKU-SAM-4K32',
            'name' => 'Samsung 32-inch 4K Monitor',
            'price' => 48000.0
        ];

        $payload = $this->barcodeService->generateSignedQrPayload($product, 'secret_key_123');

        $this->assertArrayHasKey('sku', $payload);
        $this->assertArrayHasKey('verification_seal', $payload);
        $this->assertArrayHasKey('qr_data_uri', $payload);
        $this->assertEquals('SKU-SAM-4K32', $payload['sku']);
        $this->assertTrue(strlen($payload['verification_seal']) === 16);
    }
}
