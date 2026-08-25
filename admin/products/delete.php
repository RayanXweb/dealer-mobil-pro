<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$auth->requireRole(['owner']);

$id = $_GET['id'] ?? 0;
if ($id <= 0) {
    setFlash('danger', 'ID produk tidak valid');
    redirect(ADMIN_URL . 'products/');
}

$db = Database::getInstance();

// Get product
$stmt = $db->prepare("SELECT model FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('danger', 'Produk tidak ditemukan');
    redirect(ADMIN_URL . 'products/');
}

try {
    $db->beginTransaction();
    
    // Delete images
    $stmt = $db->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
    $stmt->execute([$id]);
    $images = $stmt->fetchAll();
    
    foreach ($images as $img) {
        $file = UPLOADS_PATH . 'products/' . $img['image_path'];
        if (file_exists($file)) unlink($file);
    }
    
    // Delete product
    $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    
    $db->commit();
    
    logActivity($_SESSION['user_id'], 'delete_product', 'Deleted product: ' . $product['model']);
    setFlash('success', 'Produk berhasil dihapus');
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("Delete product error: " . $e->getMessage());
    setFlash('danger', 'Gagal menghapus produk');
}

redirect(ADMIN_URL . 'products/');
?>
