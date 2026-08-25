<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

$auth->requireLogin();

$page_title = 'Buat Penawaran';

$productId = $_GET['product'] ?? 0;
if ($productId <= 0) {
    setFlash('danger', 'Produk tidak valid');
    header('Location: ' . BASE_URL . 'products.php');
    exit;
}

$db = Database::getInstance();

// Get product
$stmt = $db->prepare("
    SELECT p.*, b.name as brand_name 
    FROM products p 
    JOIN brands b ON p.brand_id = b.id 
    WHERE p.id = ? AND p.status = 'available'
");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('danger', 'Produk tidak ditemukan');
    header('Location: ' . BASE_URL . 'products.php');
    exit;
}

// Get customer
$stmt = $db->prepare("SELECT * FROM customers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$customer = $stmt->fetch();

$priceInfo = getProductPrice($product);
$price = $priceInfo['final'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    
    $offerPrice = str_replace(['.', ','], '', $_POST['offer_price'] ?? 0);
    $discount = str_replace(['.', ','], '', $_POST['discount'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    
    if ($offerPrice <= 0) {
        $error = 'Harga penawaran wajib diisi';
    } else {
        $finalPrice = $offerPrice - $discount;
        $offerNumber = generateOfferNumber();
        
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("
                INSERT INTO offers (
                    offer_number, customer_id, product_id, price, discount, 
                    final_price, notes, status, valid_until
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'draft', DATE_ADD(NOW(), INTERVAL 7 DAY))
            ");
            $stmt->execute([
                $offerNumber,
                $customer['id'],
                $productId,
                $offerPrice,
                $discount,
                $finalPrice,
                $notes
            ]);
            
            $db->commit();
            
            // Send notification to admin
            sendNotification(1, 'Penawaran Baru', 
                            'Customer ' . $customer['full_name'] . ' membuat penawaran untuk ' . $product['brand_name'] . ' ' . $product['model'],
                            'info', ADMIN_URL . 'offers/detail.php?offer=' . $offerNumber);
            
            setFlash('success', 'Penawaran berhasil dibuat. Nomor penawaran: ' . $offerNumber);
            header('Location: ' . BASE_URL . 'offer-detail.php?offer=' . $offerNumber);
            exit;
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Create offer error: " . $e->getMessage());
            $error = 'Gagal membuat penawaran';
        }
    }
}

include 'includes/header.php';
?>

<div class="container py-4">
    <h1 class="mb-4">Buat Penawaran</h1>
    
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= escape($error) ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <?= csrfField() ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Produk</label>
                            <p class="fw-bold"><?= escape($product['brand_name']) ?> <?= escape($product['model']) ?> (<?= $product['year'] ?>)</p>
                            <p class="text-muted">Harga: <?= formatCurrency($price) ?></p>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Harga Penawaran *</label>
                                <input type="text" name="offer_price" class="form-control currency" 
                                       value="<?= number_format($price, 0, ',', '.') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Diskon</label>
                                <input type="text" name="discount" class="form-control currency" value="0">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-1"></i> Kirim Penawaran
                        </button>
                        <a href="product-detail.php?id=<?= $productId ?>" class="btn btn-secondary">Batal</a>
                    </form>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-body">
                    <h6>Info Penting:</h6>
                    <ul class="small text-muted">
                        <li>Penawaran akan diproses oleh marketing kami</li>
                        <li>Anda akan mendapat notifikasi via email/WhatsApp</li>
                        <li>Penawaran berlaku 7 hari</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <img src="<?= !empty($product['primary_image']) ? UPLOADS_URL . 'products/' . $product['primary_image'] : ASSETS_URL . 'images/no-image.jpg' ?>" 
                         alt="<?= escape($product['model']) ?>" class="img-fluid rounded mb-3">
                    <h6><?= escape($product['brand_name']) ?></h6>
                    <h5><?= escape($product['model']) ?></h5>
                    <p class="text-muted"><?= $product['year'] ?> | <?= $product['transmission'] ?></p>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-body">
                    <a href="<?= getWhatsAppLink(getSetting('whatsapp'), 'Halo, saya ingin membuat penawaran untuk ' . $product['brand_name'] . ' ' . $product['model']) ?>" 
                       class="btn btn-success w-100">
                        <i class="fab fa-whatsapp me-1"></i> Tanya via WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.currency').on('input', function() {
        var value = $(this).val().replace(/[^0-9]/g, '');
        if (value) {
            $(this).val(parseInt(value).toLocaleString('id-ID'));
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
