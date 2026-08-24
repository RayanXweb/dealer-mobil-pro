<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isAjaxRequest()) {
    die(json_encode(['success' => false, 'message' => 'Invalid request']));
}

validateCSRF();

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action'];

switch ($action) {
    case 'add':
        $productId = $_POST['product_id'] ?? 0;
        $quantity = $_POST['quantity'] ?? 1;
        
        if ($productId <= 0) {
            $response['message'] = 'Invalid product';
            break;
        }
        
        // Get or create cart
        $cartId = getCartId();
        
        // Check if item exists
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT id, quantity FROM cart_items 
            WHERE cart_id = ? AND product_id = ?
        ");
        $stmt->execute([$cartId, $productId]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $newQuantity = $existing['quantity'] + $quantity;
            $stmt = $db->prepare("
                UPDATE cart_items SET quantity = ? WHERE id = ?
            ");
            $stmt->execute([$newQuantity, $existing['id']]);
        } else {
            // Get product price
            $stmt = $db->prepare("
                SELECT price, promo_price, is_promo FROM products WHERE id = ?
            ");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            
            if (!$product) {
                $response['message'] = 'Product not found';
                break;
            }
            
            $priceInfo = getProductPrice($product);
            $price = $priceInfo['final'];
            
            $stmt = $db->prepare("
                INSERT INTO cart_items (cart_id, product_id, quantity, price) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$cartId, $productId, $quantity, $price]);
        }
        
        $response = ['success' => true, 'message' => 'Added to cart'];
        break;
        
    case 'update':
        $itemId = $_POST['item_id'] ?? 0;
        $quantity = $_POST['quantity'] ?? 1;
        
        if ($itemId <= 0 || $quantity <= 0) {
            $response['message'] = 'Invalid parameters';
            break;
        }
        
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        $stmt->execute([$quantity, $itemId]);
        
        $response = ['success' => true, 'message' => 'Cart updated'];
        break;
        
    case 'remove':
        $itemId = $_POST['item_id'] ?? 0;
        
        if ($itemId <= 0) {
            $response['message'] = 'Invalid item';
            break;
        }
        
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM cart_items WHERE id = ?");
        $stmt->execute([$itemId]);
        
        $response = ['success' => true, 'message' => 'Item removed'];
        break;
}

echo json_encode($response);

function getCartId() {
    global $auth;
    
    if ($auth->isLoggedIn()) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id FROM carts WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $cart = $stmt->fetch();
        
        if ($cart) {
            return $cart['id'];
        }
        
        $stmt = $db->prepare("INSERT INTO carts (user_id) VALUES (?)");
        $stmt->execute([$_SESSION['user_id']]);
        return $db->lastInsertId();
    } else {
        if (!isset($_SESSION['cart_id'])) {
            $db = Database::getInstance();
            $stmt = $db->prepare("INSERT INTO carts (session_id) VALUES (?)");
            $stmt->execute([session_id()]);
            $_SESSION['cart_id'] = $db->lastInsertId();
        }
        return $_SESSION['cart_id'];
    }
}
?>
