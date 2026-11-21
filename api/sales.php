<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/autoload.php';

use IMS\Services\PosCheckoutService;

header('Content-Type: application/json; charset=UTF-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed. Use POST for order checkout.']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true) ?: $_POST;

$cart = $payload['items'] ?? [];
$customer = trim($payload['customer_name'] ?? 'Walk-in Customer');
$paymentMethod = trim($payload['payment_method'] ?? 'cash');
$discount = (float) ($payload['discount_percent'] ?? 0.0);

$posService = new PosCheckoutService();
$result = $posService->executeCheckout($cart, $customer, $paymentMethod, $discount);

if ($result['success']) {
    http_response_code(200);
    echo json_encode($result);
} else {
    http_response_code(400);
    echo json_encode($result);
}
