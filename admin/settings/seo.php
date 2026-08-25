<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$auth->requireRole(['owner']);

$page_title = 'Pengaturan SEO';
include '../includes/admin-header.php';

$db = Database::getInstance();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    
    $settings = [
        'meta_title' => $_POST['meta_title'] ?? '',
        'meta_description' => $_POST['meta_description'] ?? '',
        'meta_keywords' => $_POST['meta_keywords'] ?? '',
        'meta_author' => $_POST['meta_author'] ?? '',
        'og_image' => $_POST['og_image'] ?? '',
        'google_verification' => $_POST['google_verification'] ?? '',
        'robots' => $_POST['robots'] ?? 'index, follow',
        'sitemap_enabled' => isset($_POST['sitemap_enabled']) ? '1' : '0'
    ];
    
    try {
        $db->beginTransaction();
        
        foreach ($settings as $key => $value) {
            $stmt = $db->prepare("
                UPDATE website_settings SET setting_value = ? WHERE setting_key = ?
            ");
            $stmt->execute([$value, $key]);
            
            if ($stmt->rowCount() == 0) {
                // Insert if not exists
                $stmt = $db->prepare("
                    INSERT INTO website_settings (setting_key, setting_value) VALUES (?, ?)
                ");
                $stmt->execute([$key, $value]);
            }
        }
        
        $db->commit();
        
        logActivity($_SESSION['user_id'], 'update_seo', 'Updated SEO settings');
        setFlash('success', 'Pengaturan SEO berhasil diperbarui');
        redirect(ADMIN_URL . 'settings/seo.php');
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Update SEO error: " . $e->getMessage());
        $error = 'Gagal memperbarui pengaturan SEO';
    }
}

// Get current settings
$settings = getWebsiteSettings();
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Pengaturan SEO</h5>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= escape($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <?= csrfField() ?>
            
            <h6 class="border-bottom pb-2 mb-3">Meta Tags</h6>
            
            <div class="mb-3">
                <label class="form-label">Meta Title</label>
                <input type="text" name="meta_title" class="form-control" 
                       value="<?= escape($settings['meta_title'] ?? '') ?>">
                <small class="text-muted">Title untuk halaman utama</small>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Meta Description</label>
                <textarea name="meta_description" class="form-control" rows="2"><?= escape($settings['meta_description'] ?? '') ?></textarea>
                <small class="text-muted">Deskripsi untuk SEO, maksimal 160 karakter</small>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Meta Keywords</label>
                <input type="text" name="meta_keywords" class="form-control" 
                       value="<?= escape($settings['meta_keywords'] ?? '') ?>">
                <small class="text-muted">Kata kunci dipisahkan dengan koma</small>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Meta Author</label>
                <input type="text" name="meta_author" class="form-control" 
                       value="<?= escape($settings['meta_author'] ?? '') ?>">
            </div>
            
            <h6 class="border-bottom pb-2 mb-3 mt-4">Open Graph</h6>
            
            <div class="mb-3">
                <label class="form-label">OG Image URL</label>
                <input type="url" name="og_image" class="form-control" 
                       value="<?= escape($settings['og_image'] ?? '') ?>">
                <small class="text-muted">Gambar yang akan ditampilkan saat share di social media</small>
            </div>
            
            <h6 class="border-bottom pb-2 mb-3 mt-4">Google & Crawlers</h6>
            
            <div class="mb-3">
                <label class="form-label">Google Verification</label>
                <input type="text" name="google_verification" class="form-control" 
                       value="<?= escape($settings['google_verification'] ?? '') ?>">
                <small class="text-muted">Kode verifikasi Google Search Console</small>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Robots</label>
                <select name="robots" class="form-select">
                    <option value="index, follow" <?= ($settings['robots'] ?? '') == 'index, follow' ? 'selected' : '' ?>>Index, Follow</option>
                    <option value="index, nofollow" <?= ($settings['robots'] ?? '') == 'index, nofollow' ? 'selected' : '' ?>>Index, NoFollow</option>
                    <option value="noindex, follow" <?= ($settings['robots'] ?? '') == 'noindex, follow' ? 'selected' : '' ?>>NoIndex, Follow</option>
                    <option value="noindex, nofollow" <?= ($settings['robots'] ?? '') == 'noindex, nofollow' ? 'selected' : '' ?>>NoIndex, NoFollow</option>
                </select>
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" name="sitemap_enabled" class="form-check-input" id="sitemapEnabled" 
                       <?= ($settings['sitemap_enabled'] ?? '0') == '1' ? 'checked' : '' ?>>
                <label class="form-check-label" for="sitemapEnabled">Aktifkan Sitemap</label>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Simpan Pengaturan SEO
            </button>
        </form>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h6 class="mb-0">Preview SEO</h6>
    </div>
    <div class="card-body">
        <div class="seo-preview">
            <h5 style="color: #1a0dab;"><?= escape($settings['meta_title'] ?? 'AutoDealer') ?></h5>
            <p style="color: #006621;"><?= BASE_URL ?></p>
            <p style="color: #545454;"><?= escape($settings['meta_description'] ?? '') ?></p>
        </div>
    </div>
</div>

<?php include '../includes/admin-footer.php'; ?>
