<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$auth->requireRole(['owner', 'supervisor']);

$page_title = 'Detail Transaksi';
include '../includes/admin-header.php';

$db = Database::getInstance();

$id = $_GET['id'] ?? 0;
if ($id <= 0) {
    setFlash('danger', 'ID transaksi tidak valid');
    redirect(ADMIN_URL . 'transactions/');
}

// Get transaction
$stmt = $db->prepare("
    SELECT t.*, o.order_number, c.full_name as customer_name, c.phone, c.email
    FROM transactions t
    JOIN orders o ON t.order_id = o.id
    JOIN customers c ON t.customer_id = c.id
    WHERE t.id = ?
");
$stmt->execute([$id]);
$transaction = $stmt->fetch();

if (!$transaction) {
    setFlash('danger', 'Transaksi tidak ditemukan');
    redirect(ADMIN_URL . 'transactions/');
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    validateCSRF();
    
    $status = $_POST['status'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    
    if ($status) {
        try {
            $stmt = $db->prepare("
                UPDATE transactions SET payment_status = ?, notes = CONCAT(notes, '\n', ?) WHERE id = ?
            ");
            $stmt->execute([$status, $notes, $id]);
            
            // If payment is verified, update order status
            if ($status == 'paid' || $status == 'completed') {
                $stmt = $db->prepare("UPDATE orders SET status = 'verified' WHERE id = ?");
                $stmt->execute([$transaction['order_id']]);
            }
            
            logActivity($_SESSION['user_id'], 'update_transaction', 
                       'Updated transaction ' . $transaction['transaction_number'] . ' to ' . $status);
            setFlash('success', 'Status transaksi diperbarui');
            redirect(ADMIN_URL . 'transactions/detail.php?id=' . $id);
            
        } catch (Exception $e) {
            error_log("Update transaction error: " . $e->getMessage());
            setFlash('danger', 'Gagal memperbarui status');
        }
    }
}

// Handle delete payment proof
if (isset($_GET['delete_proof'])) {
    validateCSRF();
    
    $stmt = $db->prepare("SELECT payment_proof FROM transactions WHERE id = ?");
    $stmt->execute([$id]);
    $trx = $stmt->fetch();
    
    if ($trx && !empty($trx['payment_proof'])) {
        $file = UPLOADS_PATH . 'payments/' . $trx['payment_proof'];
        if (file_exists($file)) unlink($file);
        
        $stmt = $db->prepare("UPDATE transactions SET payment_proof = NULL WHERE id = ?");
        $stmt->execute([$id]);
        
        setFlash('success', 'Bukti pembayaran dihapus');
    }
    redirect(ADMIN_URL . 'transactions/detail.php?id=' . $id);
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Detail Transaksi #<?= escape($transaction['transaction_number']) ?></h4>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Informasi Transaksi</h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="update_status" value="1">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="pending" <?= $transaction['payment_status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="paid" <?= $transaction['payment_status'] == 'paid' ? 'selected' : '' ?>>Paid</option>
                                <option value="partial" <?= $transaction['payment_status'] == 'partial' ? 'selected' : '' ?>>Partial</option>
                                <option value="failed" <?= $transaction['payment_status'] == 'failed' ? 'selected' : '' ?>>Failed</option>
                                <option value="refunded" <?= $transaction['payment_status'] == 'refunded' ? 'selected' : '' ?>>Refunded</option>
                                <option value="completed" <?= $transaction['payment_status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save me-1"></i> Update Status
                            </button>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="2"><?= escape($transaction['notes']) ?></textarea>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0">Detail</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Order ID:</strong> <?= escape($transaction['order_number']) ?></p>
                        <p><strong>Customer:</strong> <?= escape($transaction['customer_name']) ?></p>
                        <p><strong>Email:</strong> <?= escape($transaction['email']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Amount:</strong> <?= formatCurrency($transaction['amount']) ?></p>
                        <p><strong>Method:</strong> <?= ucfirst($transaction['payment_method']) ?></p>
                        <p><strong>Date:</strong> <?= formatDate($transaction['transaction_date']) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Payment Proof -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Bukti Pembayaran</h6>
            </div>
            <div class="card-body text-center">
                <?php if (!empty($transaction['payment_proof'])): ?>
                    <img src="<?= UPLOADS_URL . 'payments/' . $transaction['payment_proof'] ?>" 
                         alt="Payment Proof" class="img-fluid rounded mb-3" style="max-height: 300px;">
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="<?= UPLOADS_URL . 'payments/' . $transaction['payment_proof'] ?>" 
                           target="_blank" class="btn btn-sm btn-primary">
                            <i class="fas fa-external-link-alt me-1"></i> Lihat Full
                        </a>
                        <a href="?id=<?= $id ?>&delete_proof=1&csrf_token=<?= generateCSRFToken() ?>" 
                           class="btn btn-sm btn-danger" onclick="return confirm('Hapus bukti pembayaran?')">
                            <i class="fas fa-trash me-1"></i> Hapus
                        </a>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Belum ada bukti pembayaran</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/admin-footer.php'; ?>
