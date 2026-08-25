<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$auth->requireRole(['owner', 'supervisor']);

$page_title = 'Kelola Customer';
include '../includes/admin-header.php';

$db = Database::getInstance();

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$params = [];
$where = '1=1';

if ($search) {
    $where .= ' AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)';
    $searchTerm = '%' . $search . '%';
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

if ($status) {
    $where .= ' AND status = ?';
    $params[] = $status;
}

$sql = "
    SELECT c.*, 
           (SELECT COUNT(*) FROM orders WHERE customer_id = c.id) as total_orders,
           (SELECT SUM(final_amount) FROM orders WHERE customer_id = c.id AND status = 'completed') as total_spent
    FROM customers c
    WHERE $where
    ORDER BY c.created_at DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

// Get customer count by status
$stmt = $db->query("SELECT status, COUNT(*) as count FROM customers GROUP BY status");
$statusCounts = [];
while ($row = $stmt->fetch()) {
    $statusCounts[$row['status']] = $row['count'];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Kelola Customer</h4>
</div>

<!-- Status Filter -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
            <a href="?status=" class="btn btn-outline-secondary <?= empty($status) ? 'active' : '' ?>">
                Semua (<?= array_sum($statusCounts) ?>)
            </a>
            <a href="?status=active" class="btn btn-outline-success <?= $status == 'active' ? 'active' : '' ?>">
                Aktif (<?= $statusCounts['active'] ?? 0 ?>)
            </a>
            <a href="?status=inactive" class="btn btn-outline-danger <?= $status == 'inactive' ? 'active' : '' ?>">
                Tidak Aktif (<?= $statusCounts['inactive'] ?? 0 ?>)
            </a>
        </div>
    </div>
</div>

<!-- Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-10">
                <input type="text" name="search" class="form-control" 
                       placeholder="Cari customer..." value="<?= escape($search) ?>">
                <?php if ($status): ?>
                    <input type="hidden" name="status" value="<?= escape($status) ?>">
                <?php endif; ?>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-1"></i> Cari
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Customers Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>WhatsApp</th>
                        <th>Total Pesanan</th>
                        <th>Total Belanja</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="8" class="text-center">Belum ada customer</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $index => $customer): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= escape($customer['full_name']) ?></td>
                                <td><?= escape($customer['email']) ?></td>
                                <td><?= escape($customer['phone']) ?></td>
                                <td><?= $customer['total_orders'] ?? 0 ?></td>
                                <td><?= formatCurrency($customer['total_spent'] ?? 0) ?></td>
                                <td>
                                    <span class="badge bg-<?= $customer['status'] == 'active' ? 'success' : 'danger' ?>">
                                        <?= ucfirst($customer['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="detail.php?id=<?= $customer['id'] ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?= $customer['id'] ?>" class="btn btn-outline-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
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
