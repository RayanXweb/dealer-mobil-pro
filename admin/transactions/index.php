<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$auth->requireRole(['owner', 'supervisor']);

$page_title = 'Kelola Transaksi';
include '../includes/admin-header.php';

$db = Database::getInstance();

$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$params = [];
$where = '1=1';

if ($status) {
    $where .= ' AND t.payment_status = ?';
    $params[] = $status;
}

if ($search) {
    $where .= ' AND (t.transaction_number LIKE ? OR c.full_name LIKE ?)';
    $searchTerm = '%' . $search . '%';
    $params = array_merge($params, [$searchTerm, $searchTerm]);
}

$sql = "
    SELECT t.*, o.order_number, c.full_name as customer_name
    FROM transactions t
    JOIN orders o ON t.order_id = o.id
    JOIN customers c ON t.customer_id = c.id
    WHERE $where
    ORDER BY t.created_at DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Kelola Transaksi</h4>
</div>

<!-- Status Filter -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
            <a href="?status=" class="btn btn-outline-secondary <?= empty($status) ? 'active' : '' ?>">Semua</a>
            <a href="?status=pending" class="btn btn-outline-warning <?= $status == 'pending' ? 'active' : '' ?>">Pending</a>
            <a href="?status=paid" class="btn btn-outline-success <?= $status == 'paid' ? 'active' : '' ?>">Paid</a>
            <a href="?status=failed" class="btn btn-outline-danger <?= $status == 'failed' ? 'active' : '' ?>">Failed</a>
            <a href="?status=refunded" class="btn btn-outline-info <?= $status == 'refunded' ? 'active' : '' ?>">Refunded</a>
            <a href="?status=completed" class="btn btn-outline-primary <?= $status == 'completed' ? 'active' : '' ?>">Completed</a>
        </div>
    </div>
</div>

<!-- Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-10">
                <input type="text" name="search" class="form-control" 
                       placeholder="Cari transaksi/customer..." value="<?= escape($search) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-1"></i> Cari
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Transactions Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="8" class="text-center">Tidak ada transaksi</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $trx): ?>
                            <tr>
                                <td><strong><?= escape($trx['transaction_number']) ?></strong></td>
                                <td><?= escape($trx['order_number']) ?></td>
                                <td><?= escape($trx['customer_name']) ?></td>
                                <td><?= formatCurrency($trx['amount']) ?></td>
                                <td><?= ucfirst($trx['payment_method']) ?></td>
                                <td>
                                    <span class="badge bg-<?= getStatusColor($trx['payment_status']) ?>">
                                        <?= getStatusLabel($trx['payment_status']) ?>
                                    </span>
                                </td>
                                <td><?= formatDate($trx['created_at']) ?></td>
                                <td>
                                    <a href="detail.php?id=<?= $trx['id'] ?>" class="btn btn-sm btn-outline-primary">
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
