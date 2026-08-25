<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

$auth->requireLogin();

$page_title = 'Checkout Berhasil';

$orderNumber = $_SESSION['last_order'] ?? '';
if (empty($orderNumber)) {
    header('Location: ' . BASE_URL . 'orders.php');
    exit;
}

$db = Database::getInstance();

$stmt = $db->prepare("
    SELECT o.*, c.full_name as customer_name 
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    WHERE o.order_number = ? AND c.user_id = ?
");
$stmt->execute([$orderNumber, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: ' . BASE_URL . 'orders.php');
    exit;
}

// Clear last order from session
unset($_SESSION['last_order']);

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 text-center">
            <div class="card shadow">
                <div class="card-body p-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                    </div>
                    
                    <h2 class="mb-3">Pesanan Berhasil!</h2>
                    <p class="text-muted">
                        Terima kasih telah melakukan pemesanan di AutoDealer.
                    </p>
                    
                    <div class="alert alert-info">
                        <strong>Nomor Pesanan:</strong> <?= escape($order['order_number']) ?>
                    </div>
                    
                    <div class="row g-3 mt-3">
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6>Total</h6>
                                    <h5><?= formatCurrency($order['final_amount']) ?></h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6>Metode</h6>
                                    <h5><?= ucfirst($order['payment_method']) ?></h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6>Status</h6>
                                    <h5><span class="badge bg-<?= getStatusBadge($order['status']) ?>"><?= getStatusLabel($order['status']) ?></span></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <p class="text-muted small">
                            <?php if ($order['payment_method'] == 'transfer'): ?>
                                Silakan lakukan pembayaran ke rekening yang tertera dan upload bukti pembayaran.
                                <br>
                                <a href="order-detail.php?order=<?= $order['order_number'] ?>" class="btn btn-warning mt-2">
                                    <i class="fas fa-upload me-1"></i> Upload Bukti Pembayaran
                                </a>
                            <?php else: ?>
                                Tim marketing kami akan segera menghubungi Anda.
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <a href="order-detail.php?order=<?= $order['order_number'] ?>" class="btn btn-primary">
                            <i class="fas fa-eye me-1"></i> Lihat Pesanan
                        </a>
                        <a href="products.php" class="btn btn-outline-secondary">
                            <i class="fas fa-car me-1"></i> Lihat Mobil Lain
                        </a>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-home me-1"></i> Kembali ke Beranda
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <a href="<?= getWhatsAppLink(getSetting('whatsapp'), 'Halo, saya sudah melakukan pemesanan dengan nomor ' . $order['order_number']) ?>" 
                   class="btn btn-success" target="_blank">
                    <i class="fab fa-whatsapp me-1"></i> Chat dengan Marketing
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
