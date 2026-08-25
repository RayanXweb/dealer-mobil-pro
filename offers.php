<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

$auth->requireLogin();

$page_title = 'Penawaran Saya';

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

// Get offers
$stmt = $db->prepare("
    SELECT o.*, p.model, p.variant, b.name as brand_name,
           (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM offers o
    JOIN products p ON o.product_id = p.id
    JOIN brands b ON p.brand_id = b.id
    WHERE o.customer_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$customer['id']]);
$offers = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container py-4">
    <h1 class="mb-4">Penawaran Saya</h1>
    
    <?php if (empty($offers)): ?>
        <div class="text-center py-5">
            <i class="fas fa-file-invoice fa-4x text-muted mb-3"></i>
            <h4>Belum ada penawaran</h4>
            <p class="text-muted">Anda belum membuat penawaran untuk mobil apapun</p>
            <a href="products.php" class="btn btn-primary">
                <i class="fas fa-car me-1"></i> Lihat Mobil
            </a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($offers as $offer): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="card-subtitle text-muted"><?= escape($offer['offer_number']) ?></h6>
                                <span class="badge bg-<?= getStatusBadge($offer['status']) ?>">
                                    <?= getStatusLabel($offer['status']) ?>
                                </span>
                            </div>
                            
                            <div class="d-flex align-items-center mb-2">
                                <img src="<?= !empty($offer['primary_image']) ? UPLOADS_URL . 'products/' . $offer['primary_image'] : ASSETS_URL . 'images/no-image.jpg' ?>" 
                                     alt="<?= escape($offer['model']) ?>" 
                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                <div class="ms-2">
                                    <h6 class="mb-0"><?= escape($offer['brand_name']) ?> <?= escape($offer['model']) ?></h6>
                                    <?php if (!empty($offer['variant'])): ?>
                                        <small class="text-muted"><?= escape($offer['variant']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-2">
                                <span class="text-muted">Harga Penawaran</span>
                                <strong><?= formatCurrency($offer['price']) ?></strong>
                            </div>
                            
                            <?php if ($offer['discount'] > 0): ?>
                                <div class="d-flex justify-content-between text-success">
                                    <span class="text-muted">Diskon</span>
                                    <span>- <?= formatCurrency($offer['discount']) ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between border-top pt-2 mt-2">
                                <span class="text-muted">Total</span>
                                <strong class="text-primary"><?= formatCurrency($offer['final_price']) ?></strong>
                            </div>
                            
                            <div class="mt-3">
                                <a href="offer-detail.php?offer=<?= $offer['offer_number'] ?>" class="btn btn-outline-primary btn-sm w-100">
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
