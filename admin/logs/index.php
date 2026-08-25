<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$auth->requireRole(['owner']);

$page_title = 'Activity Logs';
include '../includes/admin-header.php';

$db = Database::getInstance();

$limit = 50;
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';
$action = $_GET['action'] ?? '';

$params = [];
$where = '1=1';

if ($search) {
    $where .= ' AND (username LIKE ? OR action LIKE ? OR description LIKE ?)';
    $searchTerm = '%' . $search . '%';
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

if ($action) {
    $where .= ' AND action = ?';
    $params[] = $action;
}

// Get total count
$countSql = "SELECT COUNT(*) as total FROM activity_logs WHERE $where";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$totalPages = ceil($total / $limit);

// Get logs
$sql = "SELECT * FROM activity_logs WHERE $where ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get unique actions for filter
$stmt = $db->query("SELECT DISTINCT action FROM activity_logs ORDER BY action");
$actions = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Activity Logs</h4>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" 
                       placeholder="Cari user/action..." value="<?= escape($search) ?>">
            </div>
            <div class="col-md-4">
                <select name="action" class="form-select">
                    <option value="">Semua Aksi</option>
                    <?php foreach ($actions as $a): ?>
                        <option value="<?= escape($a['action']) ?>" <?= $action == $a['action'] ? 'selected' : '' ?>>
                            <?= escape($a['action']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Logs Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Aksi</th>
                        <th>Deskripsi</th>
                        <th>IP</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="5" class="text-center">Tidak ada log</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= escape($log['username'] ?? 'System') ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?= escape($log['action']) ?></span>
                                </td>
                                <td><?= escape($log['description']) ?></td>
                                <td><?= escape($log['ip_address']) ?></td>
                                <td><?= formatDate($log['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav>
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&action=<?= urlencode($action) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/admin-footer.php'; ?>
