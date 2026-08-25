<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isAjaxRequest()) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$query = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? 'all';

if (strlen($query) < 2) {
    echo json_encode(['success' => true, 'results' => []]);
    exit;
}

$db = Database::getInstance();
$results = [];

$searchTerm = '%' . $query . '%';

if ($type == 'all' || $type == 'products') {
    // Search products
    $stmt = $db->prepare("
        SELECT p.id, p.model, p.variant, p.year, p.price, b.name as brand_name,
               (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
        FROM products p
        JOIN brands b ON p.brand_id = b.id
        WHERE p.status = 'available' AND (p.model LIKE ? OR p.variant LIKE ? OR b.name LIKE ?)
        LIMIT 5
    ");
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $products = $stmt->fetchAll();
    
    foreach ($products as $product) {
        $results[] = [
            'type' => 'product',
            'id' => $product['id'],
            'title' => $product['brand_name'] . ' ' . $product['model'],
            'subtitle' => $product['year'] . ' | ' . formatCurrency($product['price']),
            'image' => !empty($product['primary_image']) ? UPLOADS_URL . 'products/' . $product['primary_image'] : ASSETS_URL . 'images/no-image.jpg',
            'url' => BASE_URL . 'product-detail.php?id=' . $product['id']
        ];
    }
}

if ($type == 'all' || $type == 'orders') {
    // Search orders (only if logged in)
    if ($auth->isLoggedIn()) {
        $stmt = $db->prepare("
            SELECT o.order_number, o.final_amount, o.status, o.order_date
            FROM orders o
            JOIN customers c ON o.customer_id = c.id
            WHERE c.user_id = ? AND o.order_number LIKE ?
            LIMIT 3
        ");
        $stmt->execute([$_SESSION['user_id'], $searchTerm]);
        $orders = $stmt->fetchAll();
        
        foreach ($orders as $order) {
            $results[] = [
                'type' => 'order',
                'title' => $order['order_number'],
                'subtitle' => formatCurrency($order['final_amount']) . ' | ' . getStatusLabel($order['status']),
                'url' => BASE_URL . 'order-detail.php?order=' . $order['order_number']
            ];
        }
    }
}

echo json_encode([
    'success' => true,
    'results' => $results,
    'total' => count($results)
]);
