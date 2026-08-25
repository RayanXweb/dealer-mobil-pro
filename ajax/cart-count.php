<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$db = Database::getInstance();
$count = 0;

if ($auth->isLoggedIn()) {
    $stmt = $db->prepare("
        SELECT SUM(ci.quantity) as total 
        FROM cart_items ci 
        JOIN carts c ON ci.cart_id = c.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    $count = $result['total'] ?? 0;
} elseif (isset($_SESSION['cart_id'])) {
    $stmt = $db->prepare("
        SELECT SUM(quantity) as total 
        FROM cart_items 
        WHERE cart_id = ?
    ");
    $stmt->execute([$_SESSION['cart_id']]);
    $result = $stmt->fetch();
    $count = $result['total'] ?? 0;
}

echo json_encode(['count' => (int)$count]);
