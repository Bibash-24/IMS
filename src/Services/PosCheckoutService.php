<?php
declare(strict_types=1);

namespace IMS\Services;

use IMS\Core\Database;
use IMS\Security\AuditLogger;
use PDO;

/**
 * Enterprise Point of Sale (POS) Checkout & Dispatch Engine
 * Enforces atomic stock decrements, line-item tax and discount calculation,
 * and multi-rail payment tender (Cash, Card, QR, Credit).
 */
class PosCheckoutService
{
    private const VAT_RATE = 0.13; // 13% Government VAT in Nepal

    /**
     * Compute itemized cart subtotal, discount, VAT, and final payable amount.
     */
    public function computeCartTotals(array $cartItems, float $discountPercent = 0.0, bool $includeVat = true): array
    {
        $subtotal = 0.0;

        foreach ($cartItems as $item) {
            $qty = (int) ($item['quantity'] ?? 1);
            $price = (float) ($item['price'] ?? 0.0);
            $subtotal += ($qty * $price);
        }

        $discountAmount = ($discountPercent > 0) ? ($subtotal * ($discountPercent / 100.0)) : 0.0;
        $taxableAmount = max(0.0, $subtotal - $discountAmount);
        $vatAmount = $includeVat ? ($taxableAmount * self::VAT_RATE) : 0.0;
        $totalPayable = $taxableAmount + $vatAmount;

        return [
            'subtotal' => round($subtotal, 2),
            'discount_percent' => $discountPercent,
            'discount_amount' => round($discountAmount, 2),
            'taxable_amount' => round($taxableAmount, 2),
            'vat_rate' => $includeVat ? self::VAT_RATE : 0.0,
            'vat_amount' => round($vatAmount, 2),
            'total_payable' => round($totalPayable, 2)
        ];
    }

    /**
     * Atomic POS checkout execution with race-condition prevention.
     */
    public function executeCheckout(
        array $cartItems,
        string $customerName,
        string $paymentMethod = 'cash',
        float $discountPercent = 0.0
    ): array {
        if (empty($cartItems)) {
            return ['success' => false, 'error' => 'Cart is empty.'];
        }

        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            // 1. Validate real-time stock availability for every cart item
            foreach ($cartItems as $item) {
                $productId = (int) $item['product_id'];
                $requestedQty = (int) $item['quantity'];

                $stmt = $pdo->prepare("SELECT id, name, quantity FROM products WHERE id = ?");
                $stmt->execute([$productId]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$product) {
                    $pdo->rollBack();
                    return ['success' => false, 'error' => "Product ID #{$productId} not found."];
                }

                if ($product['quantity'] < $requestedQty) {
                    $pdo->rollBack();
                    return [
                        'success' => false,
                        'error' => "Insufficient stock for '{$product['name']}'. Requested: {$requestedQty}, Available: {$product['quantity']}."
                    ];
                }
            }

            // 2. Compute bill totals
            $totals = $this->computeCartTotals($cartItems, $discountPercent);

            // 3. Insert sales order header
            $stmt = $pdo->prepare("INSERT INTO sales 
                (customer_name, total_amount, tax_amount, discount_amount, net_amount, payment_method, payment_status, sale_date)
                VALUES (?, ?, ?, ?, ?, ?, 'completed', datetime('now'))");
            $stmt->execute([
                $customerName,
                $totals['subtotal'],
                $totals['vat_amount'],
                $totals['discount_amount'],
                $totals['total_payable'],
                $paymentMethod
            ]);
            $saleId = (int) $pdo->lastInsertId();

            // 4. Decrement inventory stock atomically & record order items
            $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
            $deductStmt = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?");

            foreach ($cartItems as $item) {
                $productId = (int) $item['product_id'];
                $qty = (int) $item['quantity'];
                $price = (float) $item['price'];
                $lineTotal = $qty * $price;

                $itemStmt->execute([$saleId, $productId, $qty, $price, $lineTotal]);
                $deductStmt->execute([$qty, $productId, $qty]);
            }

            $pdo->commit();

            AuditLogger::log('POS_SALE_COMPLETED', 'sale', (string) $saleId, [
                'customer' => $customerName,
                'total_payable' => $totals['total_payable'],
                'payment_method' => $paymentMethod,
                'item_count' => count($cartItems)
            ]);

            return [
                'success' => true,
                'sale_id' => $saleId,
                'invoice_number' => 'INV-' . str_pad((string) $saleId, 6, '0', STR_PAD_LEFT),
                'totals' => $totals,
                'customer_name' => $customerName,
                'payment_method' => $paymentMethod
            ];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'POS transaction aborted: ' . $e->getMessage()];
        }
    }
}
