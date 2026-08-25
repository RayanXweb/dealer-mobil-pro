<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$auth->requireRole(['owner', 'supervisor', 'marketing']);

$page_title = 'Kelola Penawaran';
include '../includes/admin-header.php';

$db = Database::getInstance();

$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$params = [];
$where = '1=1';

if ($status) {
    $where .= ' AND o.status = ?';
    $params[] = $status;
}

if ($search) {
    $where .= ' AND (o.offer_number LIKE ? OR c.full_name LIKE ? OR p.model LIKE ?)';
    $searchTerm = '%' . $search . '%';
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

// If marketing, only show their offers
if ($auth->hasRole('marketing')) {
    $stmt = $db->prepare("SELECT id FROM marketing WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $marketing = $stmt->fetch();
    if ($marketing) {
        $where .= ' AND o.marketing_id = ?';
        $params[] = $marketing['id'];
    }
}

$sql = "
    SELECT o.*, c.full_name as customer_name, c.phone,
           p.model, p.variant, b.name as brand_name,
           m.full_name as marketing_name
    FROM offers o
    JOIN customers c ON o.customer_id = c.id
    JOIN products p ON o.product_id = p.id
    JOIN brands b ON p.brand_id = b.id
    LEFT JOIN marketing m ON o.marketing_id = m.id
    WHERE $where
    ORDER BY o.created_at DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$offers = $stmt->fetchAll();

// Get status counts
$stmt = $db->query("SELECT status, COUNT(*) as count FROM offers GROUP BY status");
$statusCounts = [];
while ($row = $stmt->fetch()) {
    $statusCounts[$row['status']] = $row['count'];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Kelola Penawaran</h4>
    <a href="create.php" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Buat Penawaran
    </a>
</div>

<!-- Status Filter -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
            <a href="?status=" class="btn btn-outline-secondary <?= empty($status) ? 'active' : '' ?>">
                Semua (<?= array_sum($statusCounts) ?>)
            </a>
            <a href="?status=draft" class="btn btn-outline-secondary <?= $status == 'draft' ? 'active' : '' ?>">
                Draf (<?= $statusCounts['draft'] ?? 0 ?>)
            </a>
            <a href="?status=sent" class="btn btn-outline-info <?= $status == 'sent' ? 'active' : '' ?>">
                Dikirim (<?= $statusCounts['sent'] ?? 0 ?>)
            </a>
            <a href="?status=accepted" class="btn btn-outline-success <?= $status == 'accepted' ? 'active' : '' ?>">
                Diterima (<?= $statusCounts['accepted'] ?? 0 ?>)
            </a>
            <a href="?status=rejected" class="btn btn-outline-danger <?= $status == 'rejected' ? 'active' : '' ?>">
                Ditolak (<?= $statusCounts['rejected'] ?? 0 ?>)
            </a>
            <a href="?status=expired" class="btn btn-outline-warning <?= $status == 'expired' ? 'active' : '' ?>">
                Kadaluarsa (<?= $statusCounts['expired'] ?? 0 ?>)
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
                       placeholder="Cari penawaran/customer/mobil..." value="<?= escape($search) ?>">
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

<!-- Offers Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Offer ID</th>
                        <th>Customer</th>
                        <th>Mobil</th>
                        <th>Harga</th>
                        <th>Total</th>
                        <th>Marketing</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($offers)): ?>
                        <tr>
                            <td colspan="9" class="text-center">Belum ada penawaran</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($offers as $offer): ?>
                            <tr>
                                <td><strong><?= escape($offer['offer_number']) ?></strong></td>
                                <td>
                                    <?= escape($offer['customer_name']) ?><br>
                                    <small class="text-muted"><?= escape($offer['phone']) ?></small>
                                </td>
                                <td>
                                    <?= escape($offer['brand_name']) ?> <?= escape($offer['model']) ?>
                                    <?php if (!empty($offer['variant'])): ?>
                                        <br><small class="text-muted"><?= escape($offer['variant']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= formatCurrency($offer['price']) ?></td>
                                <td><strong><?= formatCurrency($offer['final_price']) ?></strong></td>
                                <td><?= escape($offer['marketing_name'] ?? '-') ?></td>
                                <td>
                                    <span class="badge bg-<?= getStatusColor($offer['status']) ?>">
                                        <?= getStatusLabel($offer['status']) ?>
                                    </span>
                                </td>
                                <td><?= formatDate($offer['created_at']) ?></td>
                                <td>
                                    <a href="detail.php?id=<?= $offer['id'] ?>" class="btn btn-sm btn-outline-primary">
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
