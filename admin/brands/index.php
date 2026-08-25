<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$auth->requireRole(['owner']);

$page_title = 'Kelola Brand';
include '../includes/admin-header.php';

$db = Database::getInstance();

// Handle delete
if (isset($_GET['delete'])) {
    validateCSRF();
    $id = (int)$_GET['delete'];
    
    // Check if brand has products
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM products WHERE brand_id = ?");
    $stmt->execute([$id]);
    $count = $stmt->fetch()['count'];
    
    if ($count > 0) {
        setFlash('danger', 'Brand tidak dapat dihapus karena masih memiliki produk');
    } else {
        // Delete logo
        $stmt = $db->prepare("SELECT logo FROM brands WHERE id = ?");
        $stmt->execute([$id]);
        $brand = $stmt->fetch();
        if ($brand && !empty($brand['logo'])) {
            $file = UPLOADS_PATH . 'brands/' . $brand['logo'];
            if (file_exists($file)) unlink($file);
        }
        
        $stmt = $db->prepare("DELETE FROM brands WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'Brand berhasil dihapus');
    }
    redirect(ADMIN_URL . 'brands/');
}

// Get brands
$stmt = $db->query("SELECT * FROM brands ORDER BY name");
$brands = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Kelola Brand</h4>
    <a href="create.php" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Tambah Brand
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Logo</th>
                        <th>Nama</th>
                        <th>Slug</th>
                        <th>Status</th>
                        <th>Jumlah Produk</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($brands)): ?>
                        <tr>
                            <td colspan="7" class="text-center">Belum ada brand</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($brands as $index => $brand): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td>
                                    <?php if (!empty($brand['logo'])): ?>
                                        <img src="<?= UPLOADS_URL . 'brands/' . $brand['logo'] ?>" 
                                             alt="<?= escape($brand['name']) ?>" 
                                             style="height: 40px; width: auto;">
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= escape($brand['name']) ?></td>
                                <td><?= escape($brand['slug']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $brand['status'] == 'active' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($brand['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $stmt = $db->prepare("SELECT COUNT(*) as count FROM products WHERE brand_id = ?");
                                    $stmt->execute([$brand['id']]);
                                    $count = $stmt->fetch()['count'];
                                    echo $count;
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit.php?id=<?= $brand['id'] ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-outline-danger" onclick="confirmDelete(<?= $brand['id'] ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
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
                Apakah Anda yakin ingin menghapus brand ini?
                <?php if (!empty($brand) && isset($brand['id'])): ?>
                    <br><small class="text-danger">Brand dengan produk tidak dapat dihapus.</small>
                <?php endif; ?>
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
