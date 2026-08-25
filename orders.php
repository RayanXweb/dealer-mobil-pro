<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

$auth->requireLogin();

$page_title = 'Pesanan Saya';

$db = Database::getInstance();

// Get customer
$stmt = $db->prepare("SELECT id FROM customers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$customer = $stmt->fetch();

if (!$customer) {
    setFlash('warning', 'Silakan lengkapi profil Anda terlebih dahulu');
    header('Location: ' . BASE_URL . 'profile.php');
    exit;
}

// Get orders
$stmt = $db->prepare("
    SELECT o.*, 
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
    FROM orders o
    WHERE o.customer_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$customer['id']]);
$orders = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container py-4">
    <h1 class="mb-4">Pesanan Saya</h1>
    
    <?php if (empty($orders)): ?>
        <div class="text-center py-5">
            <i class="fas fa-box fa-4x text-muted mb-3"></i>
            <h4>Belum ada pesanan</h4>
            <p class="text-muted">Anda belum melakukan pemesanan mobil</p>
            <a href="products.php" class="btn btn-primary">
                <i class="fas fa-car me-1"></i> Lihat Mobil
            </a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($orders as $order): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="card-subtitle text-muted"><?= escape($order['order_number']) ?></h6>
                                <span class="badge bg-<?= getStatusBadge($order['status']) ?>">
                                    <?= getStatusLabel($order['status']) ?>
                                </span>
                            </div>
                            
                            <p class="card-text">
                                <strong><?= formatCurrency($order['final_amount']) ?></strong>
                            </p>
                            
                            <div class="d-flex justify-content-between small text-muted">
                                <span><i class="fas fa-calendar me-1"></i> <?= formatDateOnly($order['order_date']) ?></span>
                                <span><i class="fas fa-box me-1"></i> <?= $order['item_count'] ?> item</span>
                            </div>
                            
                            <div class="mt-3">
                                <a href="order-detail.php?order=<?= $order['order_number'] ?>" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="fas fa-eye me-1"></i> Detail
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
