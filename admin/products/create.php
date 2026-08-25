<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$auth->requireRole(['owner', 'supervisor']);

$page_title = 'Tambah Produk';
include '../includes/admin-header.php';

$db = Database::getInstance();

// Get brands
$stmt = $db->query("SELECT * FROM brands WHERE status = 'active' ORDER BY name");
$brands = $stmt->fetchAll();

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
    
    // Validate
    if (empty($data['brand_id']) || empty($data['model']) || empty($data['price'])) {
        $error = 'Brand, model, dan harga wajib diisi';
    } else {
        try {
            $db->beginTransaction();
            
            // Generate slug
            $slug = generateSlug($data['model'] . '-' . $data['year']);
            
            // Check duplicate slug
            $stmt = $db->prepare("SELECT id FROM products WHERE slug = ?");
            $stmt->execute([$slug]);
            if ($stmt->fetch()) {
                $slug .= '-' . uniqid();
            }
            
            // Insert product
            $stmt = $db->prepare("
                INSERT INTO products (
                    brand_id, model, variant, slug, year, price, promo_price, 
                    mileage, transmission, fuel_type, color, `condition`, stock, vin,
                    description, specifications, is_featured, is_promo, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
                $data['status']
            ]);
            
            $productId = $db->lastInsertId();
            
            // Upload images
            if (!empty($_FILES['images']['name'][0])) {
                $uploadDir = UPLOADS_PATH . 'products/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $isPrimary = true;
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
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute([$productId, $result['filename'], $isPrimary ? 1 : 0]);
                        $isPrimary = false;
                    }
                }
            }
            
            $db->commit();
            
            logActivity($_SESSION['user_id'], 'create_product', 'Created product: ' . $data['model']);
            setFlash('success', 'Produk berhasil ditambahkan');
            redirect(ADMIN_URL . 'products/');
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Create product error: " . $e->getMessage());
            $error = 'Gagal menambahkan produk: ' . $e->getMessage();
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Tambah Produk Baru</h5>
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
                                <option value="<?= $brand['id'] ?>"><?= escape($brand['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Model *</label>
                        <input type="text" name="model" class="form-control" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Variant</label>
                        <input type="text" name="variant" class="form-control">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Tahun</label>
                        <input type="number" name="year" class="form-control" value="<?= date('Y') ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Warna</label>
                        <input type="text" name="color" class="form-control">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Harga *</label>
                        <input type="text" name="price" class="form-control currency" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Harga Promo</label>
                        <input type="text" name="promo_price" class="form-control currency">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Kilometer</label>
                        <input type="text" name="mileage" class="form-control number">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Transmisi</label>
                        <select name="transmission" class="form-select">
                            <option value="Manual">Manual</option>
                            <option value="Automatic">Automatic</option>
                            <option value="CVT">CVT</option>
                            <option value="DCT">DCT</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Bahan Bakar</label>
                        <select name="fuel_type" class="form-select">
                            <option value="Bensin">Bensin</option>
                            <option value="Diesel">Diesel</option>
                            <option value="Electric">Electric</option>
                            <option value="Hybrid">Hybrid</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Kondisi</label>
                        <select name="condition" class="form-select">
                            <option value="Baru">Baru</option>
                            <option value="Bekas">Bekas</option>
                            <option value="Reconditioned">Reconditioned</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Stok</label>
                        <input type="number" name="stock" class="form-control" value="1" min="0">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">VIN/Chassis</label>
                        <input type="text" name="vin" class="form-control">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="available">Tersedia</option>
                            <option value="sold">Terjual</option>
                            <option value="reserved">Dipesan</option>
                            <option value="inactive">Tidak Aktif</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_featured" class="form-check-input" id="isFeatured">
                        <label class="form-check-label" for="isFeatured">Mobil Unggulan</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_promo" class="form-check-input" id="isPromo">
                        <label class="form-check-label" for="isPromo">Mobil Promo</label>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Deskripsi</label>
                <textarea name="description" class="form-control" rows="4"></textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Spesifikasi (JSON)</label>
                <textarea name="specifications" class="form-control" rows="4" 
                          placeholder='{"mesin": "1500cc", "tenaga": "100 HP", "torsi": "130 Nm"}'></textarea>
                <small class="text-muted">Format JSON: {"key": "value", "key2": "value2"}</small>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Foto Produk</label>
                <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
                <small class="text-muted">Upload multiple images. First image will be primary.</small>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Simpan Produk
            </button>
            <a href="index.php" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Currency formatter
    $('.currency').on('input', function() {
        var value = $(this).val().replace(/[^0-9]/g, '');
        if (value) {
            $(this).val(parseInt(value).toLocaleString('id-ID'));
        }
    });
    
    // Number formatter
    $('.number').on('input', function() {
        var value = $(this).val().replace(/[^0-9]/g, '');
        $(this).val(value);
    });
});
</script>

<?php include '../includes/admin-footer.php'; ?>
