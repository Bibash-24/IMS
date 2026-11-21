<?php
declare(strict_types=1);

namespace IMS\Tests;

use IMS\Services\PurchaseOrderService;
use IMS\Core\Database;
use PDO;

class PurchaseOrderTest extends TestCase
{
    private PurchaseOrderService $purchaseService;

    public function setUp(): void
    {
        parent::setUp();
        $this->purchaseService = new PurchaseOrderService();
    }

    public function testCreatePurchaseOrderSuccess(): void
    {
        $items = [
            ['product_id' => 1, 'quantity' => 5, 'unit_cost' => 150000.0],
            ['product_id' => 2, 'quantity' => 10, 'unit_cost' => 10000.0]
        ];

        $result = $this->purchaseService->createPurchaseOrder(1, 'PO-2026-001', $items);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('purchase_id', $result);
        $this->assertFloatEquals(850000.0, $result['total_amount']);
        $this->assertEquals(PurchaseOrderService::STATUS_APPROVED, $result['status']);
    }

    public function testReceivePurchaseRecalculatesWeightedAverageCost(): void
    {
        $pdo = Database::getInstance();

        // Product 1 initially: 12 units @ 160,000.0 cost
        // Receive 8 units @ 140,000.0 cost
        // New Cost = ((12 * 160000) + (8 * 140000)) / (12 + 8)
        //          = (1,920,000 + 1,120,000) / 20 = 3,040,000 / 20 = 152,000.0
        $received = [
            ['product_id' => 1, 'quantity' => 8, 'unit_cost' => 140000.0]
        ];

        $result = $this->purchaseService->receivePurchaseItems(1, $received);

        $this->assertTrue($result['success']);
        $this->assertEquals(PurchaseOrderService::STATUS_RECEIVED, $result['status']);

        // Check updated product in database
        $stmt = $pdo->prepare("SELECT quantity, cost_price FROM products WHERE id = 1");
        $stmt->execute();
        $p = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals(20, (int) $p['quantity']); // 12 + 8
        $this->assertFloatEquals(152000.0, (float) $p['cost_price']);
    }
}
