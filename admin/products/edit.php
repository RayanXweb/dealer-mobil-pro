<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$auth->requireRole(['owner', 'supervisor']);

$page_title = 'Edit Produk';
include '../includes/admin-header.php';

$db = Database::getInstance();

$id = $_GET['id'] ?? 0;
if ($id <= 0) {
    setFlash('danger', 'ID produk tidak valid');
    redirect(ADMIN_URL . 'products/');
}

// Get product
$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('danger', 'Produk tidak ditemukan');
    redirect(ADMIN_URL . 'products/');
}

// Get brands
$stmt = $db->query("SELECT * FROM brands WHERE status = 'active' ORDER BY name");
$brands = $stmt->fetchAll();

// Get product images
$stmt = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order");
$stmt->execute([$id]);
$images = $stmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    
    $data = [
        'brand_id' => $_POST['brand_id'] ?? 0,
        'model' => trim($_POST['model'] ?? ''),
        'variant' => trim($_POST['variant'] ?? ''),
        'year' => $_POST['year'] ?? date('Y'),
        'price' => str_replace(['.', ','], '', $_POST['price'] ?? 0),
        'promo_price' => str_replace(['.', ','], '', $_POST['promo_price'] ?? 0),
        'mileage' => str_replace(['.', ','], '', $_POST['mileage'] ?? 0),
        'transmission' => $_POST['transmission'] ?? 'Manual',
        'fuel_type' => $_POST['fuel_type'] ?? 'Bensin',
        'color' => $_POST['color'] ?? '',
        'condition' => $_POST['condition'] ?? 'Baru',
        'stock' => (int)($_POST['stock'] ?? 1),
        'vin' => trim($_POST['vin'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'specifications' => $_POST['specifications'] ?? '',
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        'is_promo' => isset($_POST['is_promo']) ? 1 : 0,
        'status' => $_POST['status'] ?? 'available'
    ];
    
    if (empty($data['brand_id']) || empty($data['model']) || empty($data['price'])) {
        $error = 'Brand, model, dan harga wajib diisi';
    } else {
        try {
            $db->beginTransaction();
            
            // Generate slug
            $slug = generateSlug($data['model'] . '-' . $data['year']);
            $stmt = $db->prepare("SELECT id FROM products WHERE slug = ? AND id != ?");
            $stmt->execute([$slug, $id]);
            if ($stmt->fetch()) {
                $slug .= '-' . uniqid();
            }
            
            // Update product
            $stmt = $db->prepare("
                UPDATE products SET 
                    brand_id = ?, model = ?, variant = ?, slug = ?, year = ?, 
                    price = ?, promo_price = ?, mileage = ?, transmission = ?, 
                    fuel_type = ?, color = ?, `condition` = ?, stock = ?, vin = ?,
                    description = ?, specifications = ?, is_featured = ?, 
                    is_promo = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['brand_id'],
                $data['model'],
                $data['variant'],
                $slug,
                $data['year'],
                $data['price'],
                $data['promo_price'],
                $data['mileage'],
                $data['transmission'],
                $data['fuel_type'],
                $data['color'],
                $data['condition'],
                $data['stock'],
                $data['vin'],
                $data['description'],
                $data['specifications'],
                $data['is_featured'],
                $data['is_promo'],
                $data['status'],
                $id
            ]);
            
            // Upload new images
            if (!empty($_FILES['images']['name'][0])) {
                $uploadDir = UPLOADS_PATH . 'products/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                    if (empty($tmpName)) continue;
                    
                    $file = [
                        'name' => $_FILES['images']['name'][$key],
                        'tmp_name' => $tmpName,
                        'error' => $_FILES['images']['error'][$key],
                        'size' => $_FILES['images']['size'][$key]
                    ];
                    
                    $result = uploadFile($file, $uploadDir);
                    if ($result['success']) {
                        $stmt = $db->prepare("
                            INSERT INTO product_images (product_id, image_path, is_primary) 
                            VALUES (?, ?, 0)
                        ");
                        $stmt->execute([$id, $result['filename']]);
                    }
                }
            }
            
            $db->commit();
            
            logActivity($_SESSION['user_id'], 'update_product', 'Updated product: ' . $data['model']);
            setFlash('success', 'Produk berhasil diperbarui');
            redirect(ADMIN_URL . 'products/');
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Update product error: " . $e->getMessage());
            $error = 'Gagal memperbarui produk: ' . $e->getMessage();
        }
    }
}

// Handle image deletion
if (isset($_GET['delete_image'])) {
    validateCSRF();
    $imageId = (int)$_GET['delete_image'];
    
    $stmt = $db->prepare("SELECT image_path FROM product_images WHERE id = ? AND product_id = ?");
    $stmt->execute([$imageId, $id]);
    $image = $stmt->fetch();
    
    if ($image) {
        $file = UPLOADS_PATH . 'products/' . $image['image_path'];
        if (file_exists($file)) unlink($file);
        
        $stmt = $db->prepare("DELETE FROM product_images WHERE id = ?");
        $stmt->execute([$imageId]);
        
        setFlash('success', 'Gambar berhasil dihapus');
    }
    redirect(ADMIN_URL . 'products/edit.php?id=' . $id);
}

// Handle set primary image
if (isset($_GET['set_primary'])) {
    validateCSRF();
    $imageId = (int)$_GET['set_primary'];
    
    $stmt = $db->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?");
    $stmt->execute([$id]);
    
    $stmt = $db->prepare("UPDATE product_images SET is_primary = 1 WHERE id = ? AND product_id = ?");
    $stmt->execute([$imageId, $id]);
    
    setFlash('success', 'Gambar utama diperbarui');
    redirect(ADMIN_URL . 'products/edit.php?id=' . $id);
}
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Edit Produk: <?= escape($product['model']) ?></h5>
        <a href="<?= BASE_URL ?>product-detail.php?id=<?= $product['id'] ?>" target="_blank" class="btn btn-sm btn-info">
            <i class="fas fa-eye me-1"></i> Lihat di Frontend
        </a>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= escape($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Brand *</label>
                        <select name="brand_id" class="form-select" required>
                            <option value="">Pilih Brand</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?= $brand['id'] ?>" <?= $brand['id'] == $product['brand_id'] ? 'selected' : '' ?>>
                                    <?= escape($brand['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Model *</label>
                        <input type="text" name="model" class="form-control" value="<?= escape($product['model']) ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Variant</label>
                        <input type="text" name="variant" class="form-control" value="<?= escape($product['variant']) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Tahun</label>
                        <input type="number" name="year" class="form-control" value="<?= $product['year'] ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Warna</label>
                        <input type="text" name="color" class="form-control" value="<?= escape($product['color']) ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Harga *</label>
                        <input type="text" name="price" class="form-control currency" 
                               value="<?= number_format($product['price'], 0, ',', '.') ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Harga Promo</label>
                        <input type="text" name="promo_price" class="form-control currency" 
                               value="<?= $product['promo_price'] ? number_format($product['promo_price'], 0, ',', '.') : '' ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Kilometer</label>
                        <input type="text" name="mileage" class="form-control number" 
                               value="<?= $product['mileage'] ? number_format($product['mileage'], 0, ',', '.') : '' ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Transmisi</label>
                        <select name="transmission" class="form-select">
                            <option value="Manual" <?= $product['transmission'] == 'Manual' ? 'selected' : '' ?>>Manual</option>
                            <option value="Automatic" <?= $product['transmission'] == 'Automatic' ? 'selected' : '' ?>>Automatic</option>
                            <option value="CVT" <?= $product['transmission'] == 'CVT' ? 'selected' : '' ?>>CVT</option>
                            <option value="DCT" <?= $product['transmission'] == 'DCT' ? 'selected' : '' ?>>DCT</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Bahan Bakar</label>
                        <select name="fuel_type" class="form-select">
                            <option value="Bensin" <?= $product['fuel_type'] == 'Bensin' ? 'selected' : '' ?>>Bensin</option>
                            <option value="Diesel" <?= $product['fuel_type'] == 'Diesel' ? 'selected' : '' ?>>Diesel</option>
                            <option value="Electric" <?= $product['fuel_type'] == 'Electric' ? 'selected' : '' ?>>Electric</option>
                            <option value="Hybrid" <?= $product['fuel_type'] == 'Hybrid' ? 'selected' : '' ?>>Hybrid</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Kondisi</label>
                        <select name="condition" class="form-select">
                            <option value="Baru" <?= $product['condition'] == 'Baru' ? 'selected' : '' ?>>Baru</option>
                            <option value="Bekas" <?= $product['condition'] == 'Bekas' ? 'selected' : '' ?>>Bekas</option>
                            <option value="Reconditioned" <?= $product['condition'] == 'Reconditioned' ? 'selected' : '' ?>>Reconditioned</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Stok</label>
                        <input type="number" name="stock" class="form-control" value="<?= $product['stock'] ?>" min="0">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">VIN/Chassis</label>
                        <input type="text" name="vin" class="form-control" value="<?= escape($product['vin']) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="available" <?= $product['status'] == 'available' ? 'selected' : '' ?>>Tersedia</option>
                            <option value="sold" <?= $product['status'] == 'sold' ? 'selected' : '' ?>>Terjual</option>
                            <option value="reserved" <?= $product['status'] == 'reserved' ? 'selected' : '' ?>>Dipesan</option>
                            <option value="inactive" <?= $product['status'] == 'inactive' ? 'selected' : '' ?>>Tidak Aktif</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_featured" class="form-check-input" id="isFeatured" 
                               <?= $product['is_featured'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="isFeatured">Mobil Unggulan</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_promo" class="form-check-input" id="isPromo" 
                               <?= $product['is_promo'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="isPromo">Mobil Promo</label>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Deskripsi</label>
                <textarea name="description" class="form-control" rows="4"><?= escape($product['description']) ?></textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Spesifikasi (JSON)</label>
                <textarea name="specifications" class="form-control" rows="4"><?= escape($product['specifications']) ?></textarea>
                <small class="text-muted">Format JSON: {"mesin": "1500cc", "tenaga": "100 HP"}</small>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Tambah Foto</label>
                <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
                <small class="text-muted">Upload multiple images. New images will be appended.</small>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Update Produk
            </button>
            <a href="index.php" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</div>

<!-- Existing Images -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">Foto Produk (<?= count($images) ?>)</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php if (empty($images)): ?>
                <div class="col-12 text-center text-muted">Belum ada foto</div>
            <?php else: ?>
                <?php foreach ($images as $img): ?>
                    <div class="col-3">
                        <div class="position-relative">
                            <img src="<?= UPLOADS_URL . 'products/' . $img['image_path'] ?>" 
                                 class="img-fluid rounded" style="height: 150px; width: 100%; object-fit: cover;">
                            <?php if ($img['is_primary']): ?>
                                <span class="badge bg-primary position-absolute top-0 start-0 m-1">Utama</span>
                            <?php endif; ?>
                            <div class="btn-group btn-group-sm position-absolute bottom-0 start-0 end-0 m-1">
                                <?php if (!$img['is_primary']): ?>
                                    <a href="?id=<?= $id ?>&set_primary=<?= $img['id'] ?>&csrf_token=<?= generateCSRFToken() ?>" 
                                       class="btn btn-success btn-sm">
                                        <i class="fas fa-star"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="?id=<?= $id ?>&delete_image=<?= $img['id'] ?>&csrf_token=<?= generateCSRFToken() ?>" 
                                   class="btn btn-danger btn-sm" onclick="return confirm('Hapus gambar ini?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.currency').on('input', function() {
        var value = $(this).val().replace(/[^0-9]/g, '');
        if (value) {
            $(this).val(parseInt(value).toLocaleString('id-ID'));
        }
    });
    
    $('.number').on('input', function() {
        var value = $(this).val().replace(/[^0-9]/g, '');
        $(this).val(value);
    });
});
</script>

<?php include '../includes/admin-footer.php'; ?>
