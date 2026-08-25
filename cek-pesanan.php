<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

$page_title = 'Cek Pesanan';

$orderNumber = $_GET['order'] ?? '';
$email = $_GET['email'] ?? '';
$result = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderNumber = trim($_POST['order_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (empty($orderNumber) || empty($email)) {
        $error = 'Nomor pesanan dan email wajib diisi';
    } else {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT o.*, c.full_name as customer_name, c.phone
            FROM orders o
            JOIN customers c ON o.customer_id = c.id
            WHERE o.order_number = ? AND c.email = ?
        ");
        $stmt->execute([$orderNumber, $email]);
        $result = $stmt->fetch();
        
        if (!$result) {
            $error = 'Pesanan tidak ditemukan. Periksa kembali nomor pesanan dan email Anda.';
        }
    }
}

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4">
                        <i class="fas fa-search me-2 text-primary"></i>
                        Cek Status Pesanan
                    </h3>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= escape($error) ?></div>
                    <?php endif; ?>
                    
                    <?php if ($result): ?>
                        <!-- Order Result -->
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            Pesanan ditemukan!
                        </div>
                        
                        <div class="order-detail">
                            <div class="row mb-3">
                                <div class="col-6">
                                    <small class="text-muted">Nomor Pesanan</small>
                                    <p class="fw-bold"><?= escape($result['order_number']) ?></p>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Status</small>
                                    <p>
                                        <span class="badge bg-<?= getStatusBadge($result['status']) ?> fs-6">
                                            <?= getStatusLabel($result['status']) ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-6">
                                    <small class="text-muted">Customer</small>
                                    <p><?= escape($result['customer_name']) ?></p>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Total</small>
                                    <p class="fw-bold"><?= formatCurrency($result['final_amount']) ?></p>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-6">
                                    <small class="text-muted">Metode Pembayaran</small>
                                    <p><?= ucfirst($result['payment_method']) ?></p>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Tanggal</small>
                                    <p><?= formatDate($result['order_date']) ?></p>
                                </div>
                            </div>
                            
                            <?php if (!empty($result['notes'])): ?>
                                <div class="mb-3">
                                    <small class="text-muted">Catatan</small>
                                    <p><?= nl2br(escape($result['notes'])) ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-grid gap-2">
                                <a href="order-detail.php?order=<?= $result['order_number'] ?>" class="btn btn-primary">
                                    <i class="fas fa-eye me-1"></i> Lihat Detail Lengkap
                                </a>
                            </div>
                        </div>
                        
                    <?php else: ?>
                        <!-- Search Form -->
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Nomor Pesanan *</label>
                                <input type="text" name="order_number" class="form-control" 
                                       placeholder="Contoh: ORD-20260825-0001" 
                                       value="<?= escape($orderNumber) ?>" required>
                                <small class="text-muted">Masukkan nomor pesanan yang Anda terima</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" 
                                       placeholder="email@example.com" 
                                       value="<?= escape($email) ?>" required>
                                <small class="text-muted">Email yang digunakan saat pemesanan</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i> Cek Pesanan
                            </button>
                        </form>
                        
                        <hr>
                        
                        <p class="text-center text-muted small">
                            <i class="fas fa-info-circle me-1"></i>
                            Nomor pesanan dikirim ke email Anda saat checkout
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!$result): ?>
                <div class="text-center mt-4">
                    <a href="products.php" class="btn btn-outline-primary">
                        <i class="fas fa-car me-1"></i> Lihat Mobil
                    </a>
                    <a href="login.php" class="btn btn-outline-secondary ms-2">
                        <i class="fas fa-user me-1"></i> Login untuk lihat semua pesanan
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
