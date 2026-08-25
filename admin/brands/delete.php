<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$auth->requireRole(['owner']);

$id = $_GET['id'] ?? 0;
if ($id <= 0) {
    setFlash('danger', 'ID brand tidak valid');
    redirect(ADMIN_URL . 'brands/');
}

$db = Database::getInstance();

// Check if brand has products
$stmt = $db->prepare("SELECT COUNT(*) as count FROM products WHERE brand_id = ?");
$stmt->execute([$id]);
$count = $stmt->fetch()['count'];

if ($count > 0) {
    setFlash('danger', 'Brand tidak dapat dihapus karena masih memiliki ' . $count . ' produk');
    redirect(ADMIN_URL . 'brands/');
}

// Get brand name and logo
$stmt = $db->prepare("SELECT name, logo FROM brands WHERE id = ?");
$stmt->execute([$id]);
$brand = $stmt->fetch();

if (!$brand) {
    setFlash('danger', 'Brand tidak ditemukan');
    redirect(ADMIN_URL . 'brands/');
}

// Delete logo
if (!empty($brand['logo'])) {
    $file = UPLOADS_PATH . 'brands/' . $brand['logo'];
    if (file_exists($file)) unlink($file);
}

// Delete brand
$stmt = $db->prepare("DELETE FROM brands WHERE id = ?");
$stmt->execute([$id]);

logActivity($_SESSION['user_id'], 'delete_brand', 'Deleted brand: ' . $brand['name']);
setFlash('success', 'Brand berhasil dihapus');
redirect(ADMIN_URL . 'brands/');
?>
