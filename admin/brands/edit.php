<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$auth->requireRole(['owner']);

$page_title = 'Edit Brand';
include '../includes/admin-header.php';

$db = Database::getInstance();

$id = $_GET['id'] ?? 0;
if ($id <= 0) {
    setFlash('danger', 'ID brand tidak valid');
    redirect(ADMIN_URL . 'brands/');
}

$stmt = $db->prepare("SELECT * FROM brands WHERE id = ?");
$stmt->execute([$id]);
$brand = $stmt->fetch();

if (!$brand) {
    setFlash('danger', 'Brand tidak ditemukan');
    redirect(ADMIN_URL . 'brands/');
}

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
        if (empty($slug)) {
            $slug = generateSlug($name);
        } else {
            $slug = generateSlug($slug);
        }
        
        // Check duplicate
        $stmt = $db->prepare("SELECT id FROM brands WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $id]);
        if ($stmt->fetch()) {
            $slug .= '-' . uniqid();
        }
        
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("
                UPDATE brands SET name = ?, slug = ?, description = ?, status = ? WHERE id = ?
            ");
            $stmt->execute([$name, $slug, $description, $status, $id]);
            
            // Upload new logo
            if (!empty($_FILES['logo']['tmp_name'])) {
                $uploadDir = UPLOADS_PATH . 'brands/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                // Delete old logo
                if (!empty($brand['logo']) && file_exists($uploadDir . $brand['logo'])) {
                    unlink($uploadDir . $brand['logo']);
                }
                
                $result = uploadFile($_FILES['logo'], $uploadDir, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
                if ($result['success']) {
                    $stmt = $db->prepare("UPDATE brands SET logo = ? WHERE id = ?");
                    $stmt->execute([$result['filename'], $id]);
                }
            }
            
            $db->commit();
            
            logActivity($_SESSION['user_id'], 'update_brand', 'Updated brand: ' . $name);
            setFlash('success', 'Brand berhasil diperbarui');
            redirect(ADMIN_URL . 'brands/');
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Update brand error: " . $e->getMessage());
            $error = 'Gagal memperbarui brand';
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Edit Brand: <?= escape($brand['name']) ?></h5>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= escape($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>
            
            <div class="mb-3">
                <label class="form-label">Nama Brand *</label>
                <input type="text" name="name" class="form-control" value="<?= escape($brand['name']) ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Slug (URL-friendly)</label>
                <input type="text" name="slug" class="form-control" value="<?= escape($brand['slug']) ?>">
                <small class="text-muted">Kosongkan untuk generate otomatis</small>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Logo Saat Ini</label>
                <?php if (!empty($brand['logo'])): ?>
                    <div>
                        <img src="<?= UPLOADS_URL . 'brands/' . $brand['logo'] ?>" 
                             alt="<?= escape($brand['name']) ?>" style="max-height: 80px;">
                    </div>
                <?php else: ?>
                    <p class="text-muted">Tidak ada logo</p>
                <?php endif; ?>
                <input type="file" name="logo" class="form-control mt-2" accept="image/*">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Deskripsi</label>
                <textarea name="description" class="form-control" rows="3"><?= escape($brand['description']) ?></textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" <?= $brand['status'] == 'active' ? 'selected' : '' ?>>Aktif</option>
                    <option value="inactive" <?= $brand['status'] == 'inactive' ? 'selected' : '' ?>>Tidak Aktif</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Update Brand
            </button>
            <a href="index.php" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</div>

<?php include '../includes/admin-footer.php'; ?>
