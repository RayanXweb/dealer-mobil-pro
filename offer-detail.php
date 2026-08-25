<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

$auth->requireLogin();

$offerNumber = $_GET['offer'] ?? '';
if (empty($offerNumber)) {
    header('Location: ' . BASE_URL . 'offers.php');
    exit;
}

$db = Database::getInstance();

// Get offer
$stmt = $db->prepare("
    SELECT o.*, p.model, p.variant, p.year, b.name as brand_name,
           c.full_name as customer_name, c.phone, c.email,
           (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM offers o
    JOIN products p ON o.product_id = p.id
    JOIN brands b ON p.brand_id = b.id
    JOIN customers c ON o.customer_id = c.id
    WHERE o.offer_number = ? AND c.user_id = ?
");
$stmt->execute([$offerNumber, $_SESSION['user_id']]);
$offer = $stmt->fetch();

if (!$offer) {
    setFlash('danger', 'Penawaran tidak ditemukan');
    header('Location: ' . BASE_URL . 'offers.php');
    exit;
}

$page_title = 'Detail Penawaran #' . $offerNumber;
include 'includes/header.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="offers.php">Penawaran</a></li>
            <li class="breadcrumb-item active"><?= escape($offerNumber) ?></li>
        </ol>
    </nav>
    
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Detail Penawaran</h5>
                    <span class="badge bg-<?= getStatusBadge($offer['status']) ?> fs-6">
                        <?= getStatusLabel($offer['status']) ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="text-muted">Nomor Penawaran</small>
                            <p class="fw-bold"><?= escape($offer['offer_number']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">Tanggal</small>
                            <p class="fw-bold"><?= formatDate($offer['created_at']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">Mobil</small>
                            <p class="fw-bold"><?= escape($offer['brand_name']) ?> <?= escape($offer['model']) ?></p>
                            <p class="text-muted"><?= $offer['year'] ?> <?= !empty($offer['variant']) ? '| ' . escape($offer['variant']) : '' ?></p>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">Berlaku Sampai</small>
                            <p class="fw-bold"><?= formatDateOnly($offer['valid_until']) ?></p>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="text-muted">Harga Penawaran</small>
                            <p class="fw-bold"><?= formatCurrency($offer['price']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">Diskon</small>
                            <p class="fw-bold text-success">- <?= formatCurrency($offer['discount']) ?></p>
                        </div>
                        <div class="col-md-12">
                            <div class="border-top pt-3">
                                <small class="text-muted">Total Penawaran</small>
                                <h3 class="text-primary"><?= formatCurrency($offer['final_price']) ?></h3>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($offer['notes'])): ?>
                        <hr>
                        <div class="mt-3">
                            <small class="text-muted">Catatan</small>
                            <p class="bg-light p-3 rounded"><?= nl2br(escape($offer['notes'])) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Customer Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Informasi Pemesan</h6>
                </div>
                <div class="card-body">
                    <p><strong>Nama:</strong> <?= escape($offer['customer_name']) ?></p>
                    <p><strong>Email:</strong> <?= escape($offer['email']) ?></p>
                    <p><strong>WhatsApp:</strong> <?= escape($offer['phone']) ?></p>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Aksi</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>product-detail.php?id=<?= $offer['product_id'] ?>" 
                           class="btn btn-outline-primary">
                            <i class="fas fa-car me-1"></i> Lihat Mobil
                        </a>
                        
                        <a href="<?= getWhatsAppLink(getSetting('whatsapp'), 
                            'Halo, saya ingin menanyakan penawaran ' . $offer['offer_number'] . ' untuk ' . $offer['brand_name'] . ' ' . $offer['model']) ?>" 
                           class="btn btn-success" target="_blank">
                            <i class="fab fa-whatsapp me-1"></i> Tanya via WhatsApp
                        </a>
                        
                        <?php if ($offer['status'] == 'draft'): ?>
                            <button class="btn btn-primary" onclick="alert('Fitur ini akan segera tersedia')">
                                <i class="fas fa-paper-plane me-1"></i> Kirim Penawaran
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
