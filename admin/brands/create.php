<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$auth->requireRole(['owner']);

$page_title = 'Tambah Brand';
include '../includes/admin-header.php';

$db = Database::getInstance();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';
    
    if (empty($name)) {
        $error = 'Nama brand wajib diisi';
    } else {
        // Generate slug
        if (empty($slug)) {
            $slug = generateSlug($name);
        } else {
            $slug = generateSlug($slug);
        }
        
        // Check duplicate
        $stmt = $db->prepare("SELECT id FROM brands WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetch()) {
            $slug .= '-' . uniqid();
        }
        
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("
                INSERT INTO brands (name, slug, description, status) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$name, $slug, $description, $status]);
            
            $brandId = $db->lastInsertId();
            
            // Upload logo
            if (!empty($_FILES['logo']['tmp_name'])) {
                $uploadDir = UPLOADS_PATH . 'brands/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $result = uploadFile($_FILES['logo'], $uploadDir, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
                if ($result['success']) {
                    $stmt = $db->prepare("UPDATE brands SET logo = ? WHERE id = ?");
                    $stmt->execute([$result['filename'], $brandId]);
                }
            }
            
            $db->commit();
            
            logActivity($_SESSION['user_id'], 'create_brand', 'Created brand: ' . $name);
            setFlash('success', 'Brand berhasil ditambahkan');
            redirect(ADMIN_URL . 'brands/');
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Create brand error: " . $e->getMessage());
            $error = 'Gagal menambahkan brand';
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Tambah Brand Baru</h5>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= escape($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>
            
            <div class="mb-3">
                <label class="form-label">Nama Brand *</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Slug (URL-friendly)</label>
                <input type="text" name="slug" class="form-control" placeholder="Auto-generated from name">
                <small class="text-muted">Kosongkan untuk generate otomatis</small>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Logo</label>
                <input type="file" name="logo" class="form-control" accept="image/*">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Deskripsi</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active">Aktif</option>
                    <option value="inactive">Tidak Aktif</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Simpan Brand
            </button>
            <a href="index.php" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>

<?php include '../includes/admin-footer.php'; ?>
