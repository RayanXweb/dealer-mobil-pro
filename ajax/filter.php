<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Only AJAX requests
if (!isAjaxRequest()) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Get filters from request
$filters = [
    'keyword' => $_GET['keyword'] ?? '',
    'brand' => $_GET['brand'] ?? '',
    'min_price' => $_GET['min_price'] ?? '',
    'max_price' => $_GET['max_price'] ?? '',
    'transmission' => $_GET['transmission'] ?? '',
    'fuel_type' => $_GET['fuel_type'] ?? '',
    'condition' => $_GET['condition'] ?? '',
    'year' => $_GET['year'] ?? '',
    'is_promo' => $_GET['promo'] ?? '',
    'is_featured' => $_GET['featured'] ?? ''
];

$page = (int)($_GET['page'] ?? 1);
$limit = (int)($_GET['limit'] ?? 12);
$offset = ($page - 1) * $limit;

// Get products
$products = getProducts($filters, $limit, $offset);
$total = getProductCount($filters);
$totalPages = ceil($total / $limit);

// Format products for JSON
$formattedProducts = [];
foreach ($products as $product) {
    $priceInfo = getProductPrice($product);
    $primaryImage = $product['primary_image'] ?? '';
    
    $formattedProducts[] = [
        'id' => $product['id'],
        'brand_name' => $product['brand_name'],
        'model' => $product['model'],
        'variant' => $product['variant'] ?? '',
        'year' => $product['year'],
        'price' => $product['price'],
        'promo_price' => $product['promo_price'],
        'is_promo' => (bool)$product['is_promo'],
        'is_featured' => (bool)$product['is_featured'],
        'status' => $product['status'],
        'transmission' => $product['transmission'],
        'original_price' => $priceInfo['original'],
        'final_price' => $priceInfo['final'],
        'discount' => $priceInfo['discount'],
        'discount_percent' => $priceInfo['discount_percent'],
        'primary_image' => !empty($primaryImage) ? UPLOADS_URL . 'products/' . $primaryImage : ASSETS_URL . 'images/no-image.jpg'
    ];
}

echo json_encode([
    'success' => true,
    'products' => $formattedProducts,
    'total' => $total,
    'page' => $page,
    'total_pages' => $totalPages,
    'pagination' => [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_items' => $total,
        'items_per_page' => $limit
    ]
]);
