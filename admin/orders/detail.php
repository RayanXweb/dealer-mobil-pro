<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$auth->requireRole(['owner', 'supervisor']);

$page_title = 'Detail Pesanan';
include '../includes/admin-header.php';

$db = Database::getInstance();

$id = $_GET['id'] ?? 0;
if ($id <= 0) {
    setFlash('danger', 'ID pesanan tidak valid');
    redirect(ADMIN_URL . 'orders/');
}

// Get order
$stmt = $db->prepare("
    SELECT o.*, c.full_name as customer_name, c.phone, c.email, c.address
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    WHERE o.id = ?
");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('danger', 'Pesanan tidak ditemukan');
    redirect(ADMIN_URL . 'orders/');
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
$stmt->execute([$id]);
$items = $stmt->fetchAll();

// Get transactions
$stmt = $db->prepare("SELECT * FROM transactions WHERE order_id = ?");
$stmt->execute([$id]);
$transactions = $stmt->fetchAll();

// Get marketing list for assignment
$stmt = $db->query("SELECT id, full_name FROM marketing WHERE status = 'active'");
$marketings = $stmt->fetchAll();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    validateCSRF();
    
    $status = $_POST['status'] ?? '';
    $marketingId = $_POST['marketing_id'] ?? null;
    $notes = trim($_POST['notes'] ?? '');
    
    if ($status) {
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("UPDATE orders SET status = ?, notes = ? WHERE id = ?");
            $stmt->execute([$status, $notes, $id]);
            
            if ($marketingId) {
                $stmt = $db->prepare("UPDATE orders SET marketing_id = ? WHERE id = ?");
                $stmt->execute([$marketingId, $id]);
            }
            
            // If order is completed, update product stock
            if ($status == 'completed') {
                $stmt = $db->prepare("
                    UPDATE products p 
                    JOIN order_items oi ON p.id = oi.product_id 
                    SET p.stock = p.stock - oi.quantity, p.status = 'sold'
                    WHERE oi.order_id = ?
                ");
                $stmt->execute([$id]);
            }
            
            $db->commit();
            
            logActivity($_SESSION['user_id'], 'update_order_status', 
                       'Updated order ' . $order['order_number'] . ' to ' . $status);
            setFlash('success', 'Status pesanan diperbarui');
            redirect(ADMIN_URL . 'orders/detail.php?id=' . $id);
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Update order status error: " . $e->getMessage());
            setFlash('danger', 'Gagal memperbarui status');
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Detail Pesanan #<?= escape($order['order_number']) ?></h4>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <!-- Order Status -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Status Pesanan</h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="update_status" value="1">
                    
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Menunggu</option>
                                <option value="processing" <?= $order['status'] == 'processing' ? 'selected' : '' ?>>Diproses</option>
                                <option value="waiting_payment" <?= $order['status'] == 'waiting_payment' ? 'selected' : '' ?>>Menunggu Pembayaran</option>
                                <option value="verified" <?= $order['status'] == 'verified' ? 'selected' : '' ?>>Diverifikasi</option>
                                <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>Selesai</option>
                                <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Dibatalkan</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Assign Marketing</label>
                            <select name="marketing_id" class="form-select">
                                <option value="">Pilih Marketing</option>
                                <?php foreach ($marketings as $m): ?>
                                    <option value="<?= $m['id'] ?>" <?= $order['marketing_id'] == $m['id'] ? 'selected' : '' ?>>
                                        <?= escape($m['full_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save me-1"></i> Update Status
                            </button>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="2"><?= escape($order['notes']) ?></textarea>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Order Items -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Item Pesanan</h6>
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
                <a href="<?= ADMIN_URL ?>customers/detail.php?id=<?= $order['customer_id'] ?>" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-user me-1"></i> Lihat Customer
                </a>
            </div>
        </div>
        
        <!-- Payment Info -->
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
                                <span class="badge bg-<?= getStatusColor($trx['payment_status']) ?>">
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

<?php include '../includes/admin-footer.php'; ?>
