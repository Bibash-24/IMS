<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/autoload.php';

use IMS\Core\Database;
use IMS\Security\InputValidator;
use IMS\Security\AuditLogger;

header('Content-Type: application/json; charset=UTF-8');

$pdo = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

switch ($method) {
    case 'GET':
        $sku = $_GET['sku'] ?? null;
        if ($sku) {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE sku = ?");
            $stmt->execute([InputValidator::sanitizeString($sku)]);
            $product = $stmt->fetch();
            echo json_encode(['success' => (bool)$product, 'data' => $product ?: null]);
        } else {
            $category = $_GET['category'] ?? null;
            if ($category) {
                $stmt = $pdo->prepare("SELECT * FROM products WHERE category = ? ORDER BY id DESC");
                $stmt->execute([InputValidator::sanitizeString($category)]);
            } else {
                $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
            }
            $products = $stmt->fetchAll();
            echo json_encode(['success' => true, 'count' => count($products), 'data' => $products]);
        }
        break;

    case 'POST':
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true) ?: $_POST;

        $name = InputValidator::sanitizeString($payload['name'] ?? '');
        $category = InputValidator::sanitizeString($payload['category'] ?? 'General');
        $sku = InputValidator::sanitizeString($payload['sku'] ?? ('SKU-' . strtoupper(substr(md5(uniqid()), 0, 8))));
        $price = InputValidator::validatePositiveFloat($payload['price'] ?? 0);
        $cost = InputValidator::validatePositiveFloat($payload['cost_price'] ?? 0);
        $qty = InputValidator::validatePositiveInt($payload['quantity'] ?? 0);
        $alertQty = InputValidator::validatePositiveInt($payload['alert_quantity'] ?? 10);

        if ($name === '' || $price === null || $qty === null) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing or invalid product parameters.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO products (name, category, sku, price, cost_price, quantity, alert_quantity) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $category, $sku, $price, $cost, $qty, $alertQty]);
        $newId = (int) $pdo->lastInsertId();

        AuditLogger::log('PRODUCT_CREATED', 'product', (string)$newId, ['sku' => $sku, 'price' => $price, 'qty' => $qty]);

        http_response_code(201);
        echo json_encode(['success' => true, 'product_id' => $newId, 'sku' => $sku]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
        break;
}
