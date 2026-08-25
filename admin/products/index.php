<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$auth->requireRole(['owner', 'supervisor']);

$page_title = 'Kelola Produk';
include '../includes/admin-header.php';

$db = Database::getInstance();

// Handle delete
if (isset($_GET['delete']) && $auth->hasRole('owner')) {
    validateCSRF();
    $id = (int)$_GET['delete'];
    
    // Delete product images
    $stmt = $db->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
    $stmt->execute([$id]);
    $images = $stmt->fetchAll();
    foreach ($images as $img) {
        $file = UPLOADS_PATH . 'products/' . $img['image_path'];
        if (file_exists($file)) unlink($file);
    }
    
    $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    
    setFlash('success', 'Produk berhasil dihapus');
    redirect(ADMIN_URL . 'products/');
}

// Get products with pagination
$page = $_GET['page'] ?? 1;
$limit = 15;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';

$params = [];
$where = '1=1';

if ($search) {
    $where .= ' AND (p.model LIKE ? OR p.variant LIKE ? OR b.name LIKE ?)';
    $searchTerm = '%' . $search . '%';
    $params = [$searchTerm, $searchTerm, $searchTerm];
}

// Count total
$countSql = "SELECT COUNT(*) as total FROM products p JOIN brands b ON p.brand_id = b.id WHERE $where";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$totalPages = ceil($total / $limit);

// Get products
$sql = "SELECT p.*, b.name as brand_name 
        FROM products p 
        JOIN brands b ON p.brand_id = b.id 
        WHERE $where 
        ORDER BY p.created_at DESC 
        LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Kelola Produk</h4>
    <a href="create.php" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Tambah Produk
    </a>
</div>

<!-- Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-10">
                <input type="text" name="search" class="form-control" 
                       placeholder="Cari produk..." value="<?= escape($search) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-1"></i> Cari
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Products Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Foto</th>
                        <th>Brand</th>
                        <th>Model</th>
                        <th>Harga</th>
                        <th>Status</th>
                        <th>Featured</th>
                        <th>Promo</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="9" class="text-center">Belum ada produk</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $index => $product): ?>
                            <tr>
                                <td><?= $offset + $index + 1 ?></td>
                                <td>
                                    <?php
                                    $stmt = $db->prepare("SELECT image_path FROM product_images WHERE product_id = ? AND is_primary = 1 LIMIT 1");
                                    $stmt->execute([$product['id']]);
                                    $img = $stmt->fetch();
                                    $imgPath = $img ? UPLOADS_URL . 'products/' . $img['image_path'] : ASSETS_URL . 'images/no-image.jpg';
                                    ?>
                                    <img src="<?= $imgPath ?>" alt="<?= escape($product['model']) ?>" 
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                </td>
                                <td><?= escape($product['brand_name']) ?></td>
                                <td><?= escape($product['model']) ?></td>
                                <td><?= formatCurrency($product['price']) ?></td>
                                <td>
                                    <span class="badge bg-<?= getStatusBadge($product['status']) ?>">
                                        <?= getStatusLabel($product['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($product['is_featured']): ?>
                                        <i class="fas fa-star text-warning"></i>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($product['is_promo'] && $product['promo_price'] > 0): ?>
                                        <span class="badge bg-danger">Promo</span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit.php?id=<?= $product['id'] ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>product-detail.php?id=<?= $product['id'] ?>" 
                                           class="btn btn-outline-info" target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($auth->hasRole('owner')): ?>
                                            <button class="btn btn-outline-danger" onclick="confirmDelete(<?= $product['id'] ?>)">
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
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav>
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Apakah Anda yakin ingin menghapus produk ini? Tindakan ini tidak dapat dibatalkan.
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
