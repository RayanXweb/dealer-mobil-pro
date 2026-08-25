<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

$auth->requireLogin();

$orderNumber = $_GET['order'] ?? '';
if (empty($orderNumber)) {
    header('Location: ' . BASE_URL . 'orders.php');
    exit;
}

$db = Database::getInstance();

// Get order
$stmt = $db->prepare("
    SELECT o.*, c.full_name as customer_name, c.phone, c.email, c.address
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    WHERE o.order_number = ? AND c.user_id = ?
");
$stmt->execute([$orderNumber, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('danger', 'Pesanan tidak ditemukan');
    header('Location: ' . BASE_URL . 'orders.php');
    exit;
}

// Get order items
$stmt = $db->prepare("
    SELECT oi.*, p.model, p.variant, b.name as brand_name,
           (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN brands b ON p.brand_id = b.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order['id']]);
$items = $stmt->fetchAll();

// Get transactions
$stmt = $db->prepare("SELECT * FROM transactions WHERE order_id = ?");
$stmt->execute([$order['id']]);
$transactions = $stmt->fetchAll();

$page_title = 'Detail Pesanan #' . $orderNumber;
include 'includes/header.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="orders.php">Pesanan</a></li>
            <li class="breadcrumb-item active"><?= escape($orderNumber) ?></li>
        </ol>
    </nav>
    
    <div class="row g-4">
        <div class="col-lg-8">
            <!-- Order Status -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">Status Pesanan</h5>
                            <span class="badge bg-<?= getStatusBadge($order['status']) ?> fs-6">
                                <?= getStatusLabel($order['status']) ?>
                            </span>
                        </div>
                        <div class="text-end">
                            <small class="text-muted">Tanggal Pesanan</small>
                            <p class="mb-0"><?= formatDate($order['order_date']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Order Items -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Detail Pesanan</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($items as $item): ?>
                        <div class="d-flex align-items-center border-bottom pb-3 mb-3">
                            <img src="<?= !empty($item['primary_image']) ? UPLOADS_URL . 'products/' . $item['primary_image'] : ASSETS_URL . 'images/no-image.jpg' ?>" 
                                 alt="<?= escape($item['model']) ?>" 
                                 style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                            <div class="ms-3 flex-grow-1">
                                <h6 class="mb-0"><?= escape($item['brand_name']) ?> <?= escape($item['model']) ?></h6>
                                <?php if (!empty($item['variant'])): ?>
                                    <small class="text-muted"><?= escape($item['variant']) ?></small>
                                <?php endif; ?>
                                <div class="d-flex gap-3 mt-1">
                                    <small class="text-muted">Qty: <?= $item['quantity'] ?></small>
                                    <small class="text-muted">Harga: <?= formatCurrency($item['price']) ?></small>
                                </div>
                            </div>
                            <div class="text-end">
                                <strong><?= formatCurrency($item['price'] * $item['quantity']) ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="border-top pt-3">
                        <div class="d-flex justify-content-between">
                            <span>Subtotal</span>
                            <span><?= formatCurrency($order['total_amount']) ?></span>
                        </div>
                        <?php if ($order['discount'] > 0): ?>
                            <div class="d-flex justify-content-between text-success">
                                <span>Diskon</span>
                                <span>- <?= formatCurrency($order['discount']) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between fw-bold fs-5 mt-2">
                            <span>Total</span>
                            <span><?= formatCurrency($order['final_amount']) ?></span>
                        </div>
                    </div>
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
                    <p><strong>Nama:</strong> <?= escape($order['customer_name']) ?></p>
                    <p><strong>Email:</strong> <?= escape($order['email']) ?></p>
                    <p><strong>WhatsApp:</strong> <?= escape($order['phone']) ?></p>
                    <p><strong>Alamat:</strong> <?= nl2br(escape($order['address'])) ?></p>
                    <?php if (!empty($order['notes'])): ?>
                        <p><strong>Catatan:</strong><br><?= nl2br(escape($order['notes'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Payment Method -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Metode Pembayaran</h6>
                </div>
                <div class="card-body">
                    <p><strong>Metode:</strong> <?= ucfirst($order['payment_method']) ?></p>
                    <?php if ($order['payment_method'] == 'credit'): ?>
                        <p><strong>DP:</strong> <?= formatCurrency($order['down_payment']) ?></p>
                        <p><strong>Tenor:</strong> <?= $order['installment_tenor'] ?> bulan</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Transactions -->
            <?php if (!empty($transactions)): ?>
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Transaksi</h6>
                    </div>
                    <div class="card-body">
                        <?php foreach ($transactions as $trx): ?>
                            <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                                <div>
                                    <small class="text-muted"><?= escape($trx['transaction_number']) ?></small>
                                    <br>
                                    <span class="badge bg-<?= getStatusBadge($trx['payment_status']) ?>">
                                        <?= getStatusLabel($trx['payment_status']) ?>
                                    </span>
                                </div>
                                <div class="text-end">
                                    <strong><?= formatCurrency($trx['amount']) ?></strong>
                                    <br>
                                    <small class="text-muted"><?= formatDateOnly($trx['transaction_date']) ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
