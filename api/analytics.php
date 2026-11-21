<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/autoload.php';

use IMS\Core\Database;
use IMS\Services\InventoryAnalyticsService;

header('Content-Type: application/json; charset=UTF-8');

$pdo = Database::getInstance();
$analytics = new InventoryAnalyticsService();

// Fetch products for ABC analysis
$stmt = $pdo->query("SELECT id, name, quantity, price, cost_price FROM products");
$products = $stmt->fetchAll();

$abcClassification = $analytics->performAbcClassification($products);

// Metrics calculations
$totalValuation = 0.0;
$stockoutItems = [];

foreach ($products as $p) {
    $totalValuation += ((float)$p['price'] * (int)$p['quantity']);
    if ((int)$p['quantity'] <= (int)($p['alert_quantity'] ?? 5)) {
        $stockoutItems[] = $p;
    }
}

echo json_encode([
    'success' => true,
    'total_valuation' => round($totalValuation, 2),
    'product_count' => count($products),
    'low_stock_count' => count($stockoutItems),
    'low_stock_items' => $stockoutItems,
    'abc_summary' => $abcClassification
]);
