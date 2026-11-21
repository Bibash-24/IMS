<?php
declare(strict_types=1);

namespace IMS\Services;

use IMS\Core\Database;
use IMS\Security\AuditLogger;
use PDO;

/**
 * Purchase Order & Supplier Procurement Lifecycle Service
 * Manages procurement orders, receiving inspection, inventory incrementing,
 * and Weighted Average Cost (WAC) updating.
 */
class PurchaseOrderService
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_DISPATCHED = 'dispatched';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Create an inbound procurement order with itemized batches.
     */
    public function createPurchaseOrder(int $supplierId, string $invoiceNo, array $items): array
    {
        if (empty($items)) {
            return ['success' => false, 'error' => 'Purchase order must contain at least one item.'];
        }

        $totalAmount = 0.0;
        foreach ($items as $item) {
            $qty = (int) ($item['quantity'] ?? 0);
            $cost = (float) ($item['unit_cost'] ?? 0.0);
            if ($qty <= 0 || $cost < 0) {
                return ['success' => false, 'error' => 'Invalid quantity or unit cost specified.'];
            }
            $totalAmount += ($qty * $cost);
        }

        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("INSERT INTO purchases 
                (supplier_id, invoice_no, total_amount, status, payment_status, purchase_date)
                VALUES (?, ?, ?, ?, 'unpaid', datetime('now'))");
            $stmt->execute([$supplierId, $invoiceNo, $totalAmount, self::STATUS_APPROVED]);
            $purchaseId = (int) $pdo->lastInsertId();

            $pdo->commit();

            AuditLogger::log('PURCHASE_CREATED', 'purchase', (string) $purchaseId, [
                'supplier_id' => $supplierId,
                'invoice_no' => $invoiceNo,
                'total_amount' => $totalAmount,
                'item_count' => count($items)
            ]);

            return [
                'success' => true,
                'purchase_id' => $purchaseId,
                'total_amount' => $totalAmount,
                'status' => self::STATUS_APPROVED
            ];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Process stock arrival and apply Weighted Average Cost (WAC) formula:
     * New Cost = ((Old Qty * Old Cost) + (Received Qty * Inbound Cost)) / (Old Qty + Received Qty)
     */
    public function receivePurchaseItems(int $purchaseId, array $receivedItems): array
    {
        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $updatedProducts = [];

            foreach ($receivedItems as $item) {
                $productId = (int) $item['product_id'];
                $receivedQty = (int) $item['quantity'];
                $unitCost = (float) $item['unit_cost'];

                // Fetch existing product record
                $stmt = $pdo->prepare("SELECT quantity, cost_price FROM products WHERE id = ?");
                $stmt->execute([$productId]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$product) {
                    continue;
                }

                $oldQty = (int) $product['quantity'];
                $oldCost = (float) $product['cost_price'];

                $newQty = $oldQty + $receivedQty;
                $newCost = ($newQty > 0)
                    ? (($oldQty * $oldCost) + ($receivedQty * $unitCost)) / $newQty
                    : $unitCost;

                // Update product in catalog
                $upStmt = $pdo->prepare("UPDATE products SET quantity = ?, cost_price = ? WHERE id = ?");
                $upStmt->execute([$newQty, round($newCost, 2), $productId]);

                $updatedProducts[] = [
                    'product_id' => $productId,
                    'old_quantity' => $oldQty,
                    'new_quantity' => $newQty,
                    'new_weighted_cost' => round($newCost, 2)
                ];
            }

            // Update purchase status to received
            $statStmt = $pdo->prepare("UPDATE purchases SET status = ? WHERE id = ?");
            $statStmt->execute([self::STATUS_RECEIVED, $purchaseId]);

            $pdo->commit();

            AuditLogger::log('PURCHASE_RECEIVED', 'purchase', (string) $purchaseId, [
                'updated_products' => $updatedProducts
            ]);

            return [
                'success' => true,
                'status' => self::STATUS_RECEIVED,
                'updated_items' => $updatedProducts
            ];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'Failed to process purchase receipt: ' . $e->getMessage()];
        }
    }
}
