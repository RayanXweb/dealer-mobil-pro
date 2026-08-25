<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$auth->requireRole(['owner', 'supervisor']);

$page_title = 'Detail Customer';
include '../includes/admin-header.php';

$db = Database::getInstance();

$id = $_GET['id'] ?? 0;
if ($id <= 0) {
    setFlash('danger', 'ID customer tidak valid');
    redirect(ADMIN_URL . 'customers/');
}

$stmt = $db->prepare("
    SELECT c.*, m.full_name as marketing_name, u.avatar
    FROM customers c
    LEFT JOIN marketing m ON c.marketing_id = m.id
    LEFT JOIN users u ON c.user_id = u.id
    WHERE c.id = ?
");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    setFlash('danger', 'Customer tidak ditemukan');
    redirect(ADMIN_URL . 'customers/');
}

// Get orders
$stmt = $db->prepare("
    SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC LIMIT 10
");
$stmt->execute([$id]);
$orders = $stmt->fetchAll();

// Get offers
$stmt = $db->prepare("
    SELECT o.*, p.model, b.name as brand_name 
    FROM offers o
    JOIN products p ON o.product_id = p.id
    JOIN brands b ON p.brand_id = b.id
    WHERE o.customer_id = ?
    ORDER BY o.created_at DESC LIMIT 5
");
$stmt->execute([$id]);
$offers = $stmt->fetchAll();

// Handle status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    validateCSRF();
    
    $newStatus = $customer['status'] == 'active' ? 'inactive' : 'active';
    $stmt = $db->prepare("UPDATE customers SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $id]);
    
    logActivity($_SESSION['user_id'], 'update_customer', 
               'Toggled customer ' . $customer['full_name'] . ' to ' . $newStatus);
    setFlash('success', 'Status customer diperbarui');
    redirect(ADMIN_URL . 'customers/detail.php?id=' . $id);
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Detail Customer</h4>
    <div>
        <a href="edit.php?id=<?= $id ?>" class="btn btn-warning">
            <i class="fas fa-edit me-1"></i> Edit
        </a>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Profile -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="avatar-circle mx-auto mb-3" style="width: 100px; height: 100px; border-radius: 50%; background: #e9ecef; display: flex; align-items: center; justify-content: center; font-size: 40px; color: #6c757d;">
                    <?php if (!empty($customer['avatar'])): ?>
                        <img src="<?= UPLOADS_URL . 'users/' . $customer['avatar'] ?>" 
                             alt="Avatar" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
                
                <h5><?= escape($customer['full_name']) ?></h5>
                <p class="text-muted"><?= escape($customer['email']) ?></p>
                
                <span class="badge bg-<?= $customer['status'] == 'active' ? 'success' : 'danger' ?> fs-6">
                    <?= ucfirst($customer['status']) ?>
                </span>
                
                <hr>
                
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="toggle_status" value="1">
                    <button type="submit" class="btn btn-<?= $customer['status'] == 'active' ? 'danger' : 'success' ?> w-100">
                        <?= $customer['status'] == 'active' ? 'Nonaktifkan' : 'Aktifkan' ?> Customer
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Info -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Informasi</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Nama:</strong> <?= escape($customer['full_name']) ?></p>
                        <p><strong>Email:</strong> <?= escape($customer['email']) ?></p>
                        <p><strong>WhatsApp:</strong> <?= escape($customer['phone']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Marketing:</strong> <?= escape($customer['marketing_name'] ?? '-') ?></p>
                        <p><strong>Bergabung:</strong> <?= formatDate($customer['created_at']) ?></p>
                        <p><strong>Source:</strong> <?= escape($customer['source'] ?? '-') ?></p>
                    </div>
                </div>
                <?php if (!empty($customer['address'])): ?>
                    <p><strong>Alamat:</strong></p>
                    <p><?= nl2br(escape($customer['address'])) ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Orders -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Pesanan Terbaru</h6>
                <a href="<?= ADMIN_URL ?>orders/?customer=<?= $customer['id'] ?>" class="btn btn-sm btn-primary">
                    Lihat Semua
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($orders)): ?>
                    <p class="text-muted">Belum ada pesanan</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?= escape($order['order_number']) ?></td>
                                        <td><?= formatCurrency($order['final_amount']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= getStatusColor($order['status']) ?>">
                                                <?= getStatusLabel($order['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= formatDateOnly($order['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Offers -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Penawaran Terbaru</h6>
            </div>
            <div class="card-body">
                <?php if (empty($offers)): ?>
                    <p class="text-muted">Belum ada penawaran</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Offer ID</th>
                                    <th>Mobil</th>
                                    <th>Harga</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($offers as $offer): ?>
                                    <tr>
                                        <td><?= escape($offer['offer_number']) ?></td>
                                        <td><?= escape($offer['brand_name']) ?> <?= escape($offer['model']) ?></td>
                                        <td><?= formatCurrency($offer['final_price']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= getStatusColor($offer['status']) ?>">
                                                <?= getStatusLabel($offer['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/admin-footer.php'; ?>
