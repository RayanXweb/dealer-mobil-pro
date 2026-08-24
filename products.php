<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Build filters
$filters = [];
$filters['keyword'] = $_GET['keyword'] ?? '';
$filters['brand'] = $_GET['brand'] ?? '';
$filters['min_price'] = $_GET['min_price'] ?? '';
$filters['max_price'] = $_GET['max_price'] ?? '';
$filters['transmission'] = $_GET['transmission'] ?? '';
$filters['fuel_type'] = $_GET['fuel_type'] ?? '';
$filters['condition'] = $_GET['condition'] ?? '';
$filters['year'] = $_GET['year'] ?? '';
$filters['is_promo'] = $_GET['promo'] ?? '';
$filters['is_featured'] = $_GET['featured'] ?? '';

// Get products
$products = getProducts($filters, $limit, $offset);
$totalProducts = getProductCount($filters);
$totalPages = ceil($totalProducts / $limit);

// Get brands for filter
$db = Database::getInstance();
$stmt = $db->prepare("SELECT * FROM brands WHERE status = 'active' ORDER BY name");
$stmt->execute();
$brands = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container py-4">
    <h1 class="mb-4">Katalog Mobil</h1>
    
    <!-- Filter Section -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form id="filterForm" method="GET" action="products.php">
                <div class="row g-3">
                    <div class="col-md-3">
                        <input type="text" name="keyword" class="form-control" 
                               placeholder="Cari mobil..." value="<?= escape($filters['keyword']) ?>">
                    </div>
                    <div class="col-md-2">
                        <select name="brand" class="form-select">
                            <option value="">Semua Brand</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?= $brand['slug'] ?>" 
                                    <?= $filters['brand'] == $brand['slug'] ? 'selected' : '' ?>>
                                    <?= escape($brand['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="transmission" class="form-select">
                            <option value="">Transmisi</option>
                            <option value="Manual" <?= $filters['transmission'] == 'Manual' ? 'selected' : '' ?>>Manual</option>
                            <option value="Automatic" <?= $filters['transmission'] == 'Automatic' ? 'selected' : '' ?>>Automatic</option>
                            <option value="CVT" <?= $filters['transmission'] == 'CVT' ? 'selected' : '' ?>>CVT</option>
                            <option value="DCT" <?= $filters['transmission'] == 'DCT' ? 'selected' : '' ?>>DCT</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="fuel_type" class="form-select">
                            <option value="">Bahan Bakar</option>
                            <option value="Bensin" <?= $filters['fuel_type'] == 'Bensin' ? 'selected' : '' ?>>Bensin</option>
                            <option value="Diesel" <?= $filters['fuel_type'] == 'Diesel' ? 'selected' : '' ?>>Diesel</option>
                            <option value="Electric" <?= $filters['fuel_type'] == 'Electric' ? 'selected' : '' ?>>Electric</option>
                            <option value="Hybrid" <?= $filters['fuel_type'] == 'Hybrid' ? 'selected' : '' ?>>Hybrid</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group">
                            <input type="number" name="min_price" class="form-control" 
                                   placeholder="Min Harga" value="<?= escape($filters['min_price']) ?>">
                            <span class="input-group-text">-</span>
                            <input type="number" name="max_price" class="form-control" 
                                   placeholder="Max Harga" value="<?= escape($filters['max_price']) ?>">
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Filter
                        </button>
                        <a href="products.php" class="btn btn-outline-secondary">
                            <i class="fas fa-undo me-1"></i>Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Results -->
    <div class="row g-4" id="productGrid">
        <?php if (empty($products)): ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-car fa-4x text-muted mb-3"></i>
                <h4>Tidak ada mobil ditemukan</h4>
                <p class="text-muted">Coba ubah filter pencarian Anda</p>
            </div>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
                <div class="col-md-4 col-lg-3">
                    <?php include 'includes/product-card.php'; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <nav aria-label="Product pagination" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?>&<?= http_build_query($filters) ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query($filters) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?>&<?= http_build_query($filters) ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
