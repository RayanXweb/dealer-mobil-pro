<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$auth->requireRole(['owner', 'supervisor']);

$page_title = 'Kelola Marketing';
include '../includes/admin-header.php';

$db = Database::getInstance();

// Handle delete
if (isset($_GET['delete'])) {
    validateCSRF();
    $id = (int)$_GET['delete'];
    
    // Check if marketing has orders
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM orders WHERE marketing_id = ?");
    $stmt->execute([$id]);
    $count = $stmt->fetch()['count'];
    
    if ($count > 0) {
        setFlash('danger', 'Marketing tidak dapat dihapus karena masih memiliki ' . $count . ' pesanan');
    } else {
        $stmt = $db->prepare("DELETE FROM marketing WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'Marketing berhasil dihapus');
    }
    redirect(ADMIN_URL . 'marketing/');
}

// Get marketing list
$stmt = $db->prepare("
    SELECT m.*, 
           (SELECT COUNT(*) FROM customers WHERE marketing_id = m.id) as total_customers,
           (SELECT COUNT(*) FROM orders WHERE marketing_id = m.id AND status = 'completed') as total_sales,
           (SELECT SUM(final_amount) FROM orders WHERE marketing_id = m.id AND status = 'completed') as total_revenue
    FROM marketing m
    ORDER BY m.created_at DESC
");
$stmt->execute();
$marketings = $stmt->fetchAll();

// Get status counts
$stmt = $db->query("SELECT status, COUNT(*) as count FROM marketing GROUP BY status");
$statusCounts = [];
while ($row = $stmt->fetch()) {
    $statusCounts[$row['status']] = $row['count'];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Kelola Marketing</h4>
    <a href="create.php" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Tambah Marketing
    </a>
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

<!-- Marketing Table -->
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
                        <th>Target</th>
                        <th>Customer</th>
                        <th>Penjualan</th>
                        <th>Omzet</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($marketings)): ?>
                        <tr>
                            <td colspan="10" class="text-center">Belum ada marketing</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($marketings as $index => $m): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= escape($m['full_name']) ?></td>
                                <td><?= escape($m['email']) ?></td>
                                <td><?= escape($m['phone'] ?? '-') ?></td>
                                <td><?= number_format($m['target_sales'] ?? 0) ?></td>
                                <td><?= number_format($m['total_customers'] ?? 0) ?></td>
                                <td><?= number_format($m['total_sales'] ?? 0) ?></td>
                                <td><?= formatCurrency($m['total_revenue'] ?? 0) ?></td>
                                <td>
                                    <span class="badge bg-<?= $m['status'] == 'active' ? 'success' : 'danger' ?>">
                                        <?= ucfirst($m['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="dashboard.php?id=<?= $m['id'] ?>" class="btn btn-outline-info">
                                            <i class="fas fa-chart-bar"></i>
                                        </a>
                                        <a href="edit.php?id=<?= $m['id'] ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($auth->hasRole('owner')): ?>
                                            <button class="btn btn-outline-danger" onclick="confirmDelete(<?= $m['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
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

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Apakah Anda yakin ingin menghapus marketing ini?
                <br><small class="text-danger">Marketing dengan pesanan tidak dapat dihapus.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <a href="#" id="deleteConfirmBtn" class="btn btn-danger">Hapus</a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    document.getElementById('deleteConfirmBtn').href = '?delete=' + id + '&csrf_token=<?= generateCSRFToken() ?>';
    modal.show();
}
</script>

<?php include '../includes/admin-footer.php'; ?>
