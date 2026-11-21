<?php
declare(strict_types=1);

namespace IMS\Tests;

use IMS\Services\PosCheckoutService;
use IMS\Core\Database;
use PDO;

class PosCheckoutTest extends TestCase
{
    private PosCheckoutService $pos;

    public function setUp(): void
    {
        parent::setUp();
        $this->pos = new PosCheckoutService();
    }

    public function testComputeCartTotalsStandardVat(): void
    {
        $cart = [
            ['product_id' => 1, 'price' => 1000.0, 'quantity' => 2], // 2,000
            ['product_id' => 2, 'price' => 500.0, 'quantity' => 4]   // 2,000
        ];

        // Subtotal = 4,000. 10% discount = 400. Taxable = 3,600. VAT 13% = 468. Total = 4,068
        $totals = $this->pos->computeCartTotals($cart, 10.0, true);

        $this->assertFloatEquals(4000.0, $totals['subtotal']);
        $this->assertFloatEquals(400.0, $totals['discount_amount']);
        $this->assertFloatEquals(3600.0, $totals['taxable_amount']);
        $this->assertFloatEquals(468.0, $totals['vat_amount']);
        $this->assertFloatEquals(4068.0, $totals['total_payable']);
    }

    public function testExecuteCheckoutAtomicStockDecrement(): void
    {
        $pdo = Database::getInstance();

        // Seed product with initial 10 units
        $stmt = $pdo->prepare("SELECT quantity FROM products WHERE id = 1");
        $stmt->execute();
        $initialQty = (int) $stmt->fetchColumn();

        $cart = [
            ['product_id' => 1, 'price' => 185000.0, 'quantity' => 2]
        ];

        $result = $this->pos->executeCheckout($cart, 'Apex Technologies Pvt Ltd', 'fonepay', 5.0);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('sale_id', $result);
        $this->assertArrayHasKey('invoice_number', $result);

        // Verify database stock decremented atomically by 2
        $stmt->execute();
        $finalQty = (int) $stmt->fetchColumn();
        $this->assertEquals($initialQty - 2, $finalQty);
    }

    public function testCheckoutRejectsInsufficientStock(): void
    {
        $cart = [
            ['product_id' => 1, 'price' => 185000.0, 'quantity' => 9999] // Exceeds available 12
        ];

        $result = $this->pos->executeCheckout($cart, 'Jane Doe', 'cash');

        $this->assertFalse($result['success']);
        $this->assertContains('Insufficient stock', $result['error']);
    }

    public function testCheckoutRejectsEmptyCart(): void
    {
        $result = $this->pos->executeCheckout([], 'John Doe');
        $this->assertFalse($result['success']);
        $this->assertEquals('Cart is empty.', $result['error']);
    }
}
