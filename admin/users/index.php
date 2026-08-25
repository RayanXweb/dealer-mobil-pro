<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$auth->requireRole(['owner']);

$page_title = 'Kelola Users';
include '../includes/admin-header.php';

$db = Database::getInstance();

$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';

$params = [];
$where = '1=1';

if ($search) {
    $where .= ' AND (username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)';
    $searchTerm = '%' . $search . '%';
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if ($role) {
    $where .= ' AND role = ?';
    $params[] = $role;
}

$sql = "SELECT * FROM users WHERE $where ORDER BY created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Kelola Users</h4>
    <a href="create.php" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Tambah User
    </a>
</div>

<!-- Role Filter -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
            <a href="?role=" class="btn btn-outline-secondary <?= empty($role) ? 'active' : '' ?>">Semua</a>
            <a href="?role=owner" class="btn btn-outline-danger <?= $role == 'owner' ? 'active' : '' ?>">Owner</a>
            <a href="?role=supervisor" class="btn btn-outline-warning <?= $role == 'supervisor' ? 'active' : '' ?>">Supervisor</a>
            <a href="?role=marketing" class="btn btn-outline-info <?= $role == 'marketing' ? 'active' : '' ?>">Marketing</a>
            <a href="?role=customer" class="btn btn-outline-secondary <?= $role == 'customer' ? 'active' : '' ?>">Customer</a>
        </div>
    </div>
</div>

<!-- Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-10">
                <input type="text" name="search" class="form-control" 
                       placeholder="Cari user..." value="<?= escape($search) ?>">
                <?php if ($role): ?>
                    <input type="hidden" name="role" value="<?= escape($role) ?>">
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

<!-- Users Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" class="text-center">Belum ada user</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $index => $user): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= escape($user['first_name'] . ' ' . ($user['last_name'] ?? '')) ?></td>
                                <td><?= escape($user['email']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $user['role'] == 'owner' ? 'danger' : ($user['role'] == 'supervisor' ? 'warning' : ($user['role'] == 'marketing' ? 'info' : 'secondary')) ?>">
                                        <?= strtoupper($user['role']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $user['status'] == 'active' ? 'success' : 'danger' ?>">
                                        <?= ucfirst($user['status']) ?>
                                    </span>
                                </td>
                                <td><?= $user['last_login'] ? formatDate($user['last_login']) : '-' ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit.php?id=<?= $user['id'] ?>" class="btn btn-outline-primary">
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
