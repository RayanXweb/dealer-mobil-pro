<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

$id = $_GET['id'] ?? 0;

if ($id <= 0) {
    header('Location: ' . BASE_URL . 'products.php');
    exit;
}

$db = Database::getInstance();

// Get product
$stmt = $db->prepare("
    SELECT p.*, b.name as brand_name, b.slug as brand_slug
    FROM products p
    JOIN brands b ON p.brand_id = b.id
    WHERE p.id = ? AND p.status = 'available'
");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: ' . BASE_URL . 'products.php');
    exit;
}

// Update views
$stmt = $db->prepare("UPDATE products SET views = views + 1 WHERE id = ?");
$stmt->execute([$id]);

// Get product images
$stmt = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order");
$stmt->execute([$id]);
$images = $stmt->fetchAll();

// Get related products
$stmt = $db->prepare("
    SELECT p.*, b.name as brand_name,
    (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM products p
    JOIN brands b ON p.brand_id = b.id
    WHERE p.brand_id = ? AND p.id != ? AND p.status = 'available'
    LIMIT 4
");
$stmt->execute([$product['brand_id'], $id]);
$relatedProducts = $stmt->fetchAll();

$priceInfo = getProductPrice($product);

$page_title = $product['brand_name'] . ' ' . $product['model'];

include 'includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Beranda</a></li>
            <li class="breadcrumb-item"><a href="products.php">Mobil</a></li>
            <li class="breadcrumb-item active"><?= escape($product['brand_name']) ?> <?= escape($product['model']) ?></li>
        </ol>
    </nav>
    
    <div class="row g-4">
        <!-- Product Images -->
        <div class="col-lg-6">
            <div class="position-relative">
                <?php
                $primaryImage = '';
                foreach ($images as $img) {
                    if ($img['is_primary']) {
                        $primaryImage = $img['image_path'];
                        break;
                    }
                }
                if (empty($primaryImage) && !empty($images)) {
                    $primaryImage = $images[0]['image_path'];
                }
                $imagePath = !empty($primaryImage) ? UPLOADS_URL . 'products/' . $primaryImage : ASSETS_URL . 'images/no-image.jpg';
                ?>
                <img src="<?= $imagePath ?>" alt="<?= escape($product['model']) ?>" 
                     class="img-fluid rounded" id="mainImage" style="width: 100%; max-height: 500px; object-fit: cover;">
                
                <?php if ($product['is_promo'] && $product['promo_price'] > 0): ?>
                    <span class="badge bg-danger position-absolute top-0 end-0 m-3 px-4 py-2 fs-6">
                        <i class="fas fa-tag me-1"></i> PROMO
                    </span>
                <?php endif; ?>
            </div>
            
            <?php if (count($images) > 1): ?>
                <div class="row g-2 mt-3" id="thumbnailGallery">
                    <?php foreach ($images as $img): ?>
                        <div class="col-3">
                            <img src="<?= UPLOADS_URL . 'products/' . $img['image_path'] ?>" 
                                 class="img-fluid rounded thumbnail-img" 
                                 data-main="<?= UPLOADS_URL . 'products/' . $img['image_path'] ?>"
                                 style="height: 80px; object-fit: cover; cursor: pointer; border: 2px solid transparent;">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Product Info -->
        <div class="col-lg-6">
            <h1 class="display-6"><?= escape($product['brand_name']) ?> <?= escape($product['model']) ?></h1>
            <?php if (!empty($product['variant'])): ?>
                <h6 class="text-muted"><?= escape($product['variant']) ?></h6>
            <?php endif; ?>
            
            <div class="mt-3">
                <?php if ($priceInfo['discount'] > 0): ?>
                    <span class="text-muted text-decoration-line-through h5">
                        <?= formatCurrency($priceInfo['original']) ?>
                    </span>
                    <span class="text-danger display-6 fw-bold ms-2">
                        <?= formatCurrency($priceInfo['final']) ?>
                    </span>
                    <span class="badge bg-success ms-2 fs-6">-<?= $priceInfo['discount_percent'] ?>%</span>
                <?php else: ?>
                    <span class="display-6 fw-bold"><?= formatCurrency($priceInfo['final']) ?></span>
                <?php endif; ?>
            </div>
            
            <div class="d-flex gap-3 mt-3 flex-wrap">
                <span class="badge bg-<?= getStatusBadge($product['status']) ?> fs-6">
                    <?= getStatusLabel($product['status']) ?>
                </span>
                <?php if ($product['is_featured']): ?>
                    <span class="badge bg-primary fs-6">
                        <i class="fas fa-star me-1"></i> Unggulan
                    </span>
                <?php endif; ?>
            </div>
            
            <hr>
            
            <div class="row g-3">
                <div class="col-6">
                    <small class="text-muted">Tahun</small>
                    <p class="fw-bold"><?= $product['year'] ?></p>
                </div>
                <div class="col-6">
                    <small class="text-muted">Kilometer</small>
                    <p class="fw-bold"><?= number_format($product['mileage'] ?? 0) ?> km</p>
                </div>
                <div class="col-6">
                    <small class="text-muted">Transmisi</small>
                    <p class="fw-bold"><?= $product['transmission'] ?></p>
                </div>
                <div class="col-6">
                    <small class="text-muted">Bahan Bakar</small>
                    <p class="fw-bold"><?= $product['fuel_type'] ?></p>
                </div>
                <div class="col-6">
                    <small class="text-muted">Warna</small>
                    <p class="fw-bold"><?= escape($product['color'] ?? '-') ?></p>
                </div>
                <div class="col-6">
                    <small class="text-muted">Kondisi</small>
                    <p class="fw-bold"><?= $product['condition'] ?></p>
                </div>
                <div class="col-6">
                    <small class="text-muted">Stok</small>
                    <p class="fw-bold"><?= $product['stock'] > 0 ? $product['stock'] . ' unit' : 'Habis' ?></p>
                </div>
                <?php if (!empty($product['vin'])): ?>
                    <div class="col-12">
                        <small class="text-muted">VIN/Chassis</small>
                        <p class="fw-bold"><?= escape($product['vin']) ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <hr>
            
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-primary btn-lg add-to-cart" data-id="<?= $product['id'] ?>">
                    <i class="fas fa-cart-plus me-1"></i> Tambah ke Keranjang
                </button>
                <a href="make-offer.php?product=<?= $product['id'] ?>" class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-file-invoice me-1"></i> Buat Penawaran
                </a>
                <a href="<?= getWhatsAppLink(getSetting('whatsapp'), 'Halo, saya tertarik dengan ' . $product['brand_name'] . ' ' . $product['model'] . ' (' . $product['year'] . ')') ?>" 
                   class="btn btn-success btn-lg">
                    <i class="fab fa-whatsapp me-1"></i> Hubungi Marketing
                </a>
            </div>
        </div>
    </div>
    
    <!-- Description & Specifications -->
    <div class="row mt-5">
        <div class="col-lg-8">
            <h4>Deskripsi</h4>
            <p><?= nl2br(escape($product['description'] ?? 'Tidak ada deskripsi')) ?></p>
            
            <?php if (!empty($product['specifications'])): ?>
                <h4 class="mt-4">Spesifikasi</h4>
                <?php
                $specs = json_decode($product['specifications'], true);
                if ($specs && is_array($specs)):
                ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <tbody>
                                <?php foreach ($specs as $key => $value): ?>
                                    <tr>
                                        <th><?= ucfirst(str_replace('_', ' ', $key)) ?></th>
                                        <td><?= escape($value) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
        <div class="mt-5">
            <h4 class="mb-4">Mobil Lainnya dari <?= escape($product['brand_name']) ?></h4>
            <div class="row g-4">
                <?php foreach ($relatedProducts as $related): ?>
                    <div class="col-md-3">
                        <?php include 'includes/product-card.php'; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    // Thumbnail gallery
    $('.thumbnail-img').on('click', function() {
        var src = $(this).data('main');
        $('#mainImage').attr('src', src);
        $('.thumbnail-img').css('border-color', 'transparent');
        $(this).css('border-color', '#0d6efd');
    });
    
    // Add to cart
    $('.add-to-cart').on('click', function() {
        var productId = $(this).data('id');
        var button = $(this);
        
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menambahkan...');
        
        $.ajax({
            url: 'ajax/cart.php',
            method: 'POST',
            data: {
                action: 'add',
                product_id: productId,
                quantity: 1,
                csrf_token: '<?= generateCSRFToken() ?>'
            },
            success: function(response) {
                if (response.success) {
                    showToast('success', 'Berhasil ditambahkan ke keranjang');
                    // Update cart count
                    var badge = $('.navbar .badge');
                    if (badge.length) {
                        var count = parseInt(badge.text()) + 1;
                        badge.text(count);
                    }
                } else {
                    showToast('danger', response.message || 'Gagal menambahkan');
                }
            },
            error: function() {
                showToast('danger', 'Terjadi kesalahan');
            },
            complete: function() {
                button.prop('disabled', false).html('<i class="fas fa-cart-plus me-1"></i> Tambah ke Keranjang');
            }
        });
    });
});

function showToast(type, message) {
    var toast = $('<div class="toast align-items-center text-white bg-' + type + ' border-0" role="alert">' +
                  '<div class="d-flex"><div class="toast-body">' + message + '</div>' +
                  '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>');
    $('body').append(toast);
    var bsToast = new bootstrap.Toast(toast, { autohide: true, delay: 3000 });
    bsToast.show();
}
</script>

<?php include 'includes/footer.php'; ?>
