<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$auth->requireRole(['owner', 'supervisor']);

$page_title = 'Kelola Pesanan';
include '../includes/admin-header.php';

$db = Database::getInstance();

// Filters
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$params = [];
$where = '1=1';

if ($status) {
    $where .= ' AND o.status = ?';
    $params[] = $status;
}

if ($search) {
    $where .= ' AND (o.order_number LIKE ? OR c.full_name LIKE ? OR c.email LIKE ?)';
    $searchTerm = '%' . $search . '%';
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

if ($date_from) {
    $where .= ' AND DATE(o.order_date) >= ?';
    $params[] = $date_from;
}

if ($date_to) {
    $where .= ' AND DATE(o.order_date) <= ?';
    $params[] = $date_to;
}

// Get orders
$sql = "
    SELECT o.*, c.full_name as customer_name, c.phone,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    WHERE $where
    ORDER BY o.created_at DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get status counts
$stmt = $db->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
$statusCounts = [];
while ($row = $stmt->fetch()) {
    $statusCounts[$row['status']] = $row['count'];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Kelola Pesanan</h4>
</div>

<!-- Status Filter -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
            <a href="?status=" class="btn btn-outline-secondary <?= empty($status) ? 'active' : '' ?>">
                Semua (<?= array_sum($statusCounts) ?>)
            </a>
            <?php
            $statuses = ['pending' => 'warning', 'processing' => 'info', 'waiting_payment' => 'warning', 
                        'verified' => 'success', 'completed' => 'success', 'cancelled' => 'danger'];
            foreach ($statuses as $key => $color):
                $count = $statusCounts[$key] ?? 0;
            ?>
                <a href="?status=<?= $key ?>" class="btn btn-outline-<?= $color ?> <?= $status == $key ? 'active' : '' ?>">
                    <?= getStatusLabel($key) ?> (<?= $count ?>)
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" 
                       placeholder="Cari order/customer..." value="<?= escape($search) ?>">
            </div>
            <div class="col-md-3">
                <input type="date" name="date_from" class="form-control" value="<?= escape($date_from) ?>">
            </div>
            <div class="col-md-3">
                <input type="date" name="date_to" class="form-control" value="<?= escape($date_to) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-1"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Orders Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="7" class="text-center">Tidak ada pesanan</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong><?= escape($order['order_number']) ?></strong></td>
                                <td>
                                    <?= escape($order['customer_name']) ?><br>
                                    <small class="text-muted"><?= escape($order['phone']) ?></small>
                                </td>
                                <td><?= $order['item_count'] ?></td>
                                <td><?= formatCurrency($order['final_amount']) ?></td>
                                <td>
                                    <span class="badge bg-<?= getStatusBadge($order['status']) ?>">
                                        <?= getStatusLabel($order['status']) ?>
                                    </span>
                                </td>
                                <td><?= formatDate($order['created_at']) ?></td>
                                <td>
                                    <a href="detail.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/admin-footer.php'; ?>
