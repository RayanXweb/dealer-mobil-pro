<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$auth->requireRole(['owner']);

$page_title = 'Advertising & Tracking';
include '../includes/admin-header.php';

$db = Database::getInstance();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    
    $scripts = [
        'google_analytics' => $_POST['google_analytics'] ?? '',
        'google_ads' => $_POST['google_ads'] ?? '',
        'google_ads_conversion' => $_POST['google_ads_conversion'] ?? '',
        'meta_pixel' => $_POST['meta_pixel'] ?? '',
        'custom_head' => $_POST['custom_head'] ?? '',
        'custom_body' => $_POST['custom_body'] ?? '',
        'custom_footer' => $_POST['custom_footer'] ?? '',
        'ga4_measurement_id' => $_POST['ga4_measurement_id'] ?? ''
    ];
    
    try {
        $db->beginTransaction();
        
        foreach ($scripts as $key => $value) {
            $stmt = $db->prepare("
                UPDATE website_settings SET setting_value = ? WHERE setting_key = ?
            ");
            $stmt->execute([$value, $key]);
            
            if ($stmt->rowCount() == 0) {
                $stmt = $db->prepare("
                    INSERT INTO website_settings (setting_key, setting_value) VALUES (?, ?)
                ");
                $stmt->execute([$key, $value]);
            }
        }
        
        $db->commit();
        
        logActivity($_SESSION['user_id'], 'update_advertising', 'Updated advertising settings');
        setFlash('success', 'Pengaturan advertising berhasil diperbarui');
        redirect(ADMIN_URL . 'advertising/');
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Update advertising error: " . $e->getMessage());
        $error = 'Gagal memperbarui pengaturan';
    }
}

// Get current settings
$settings = getWebsiteSettings();
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Advertising & Tracking Codes</h5>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= escape($error) ?></div>
        <?php endif; ?>
        
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Script ini akan disisipkan ke semua halaman website. Hanya Owner yang dapat mengubah.
        </div>
        
        <form method="POST">
            <?= csrfField() ?>
            
            <h6 class="border-bottom pb-2 mb-3">Google Analytics</h6>
            
            <div class="mb-3">
                <label class="form-label">GA4 Measurement ID</label>
                <input type="text" name="ga4_measurement_id" class="form-control" 
                       placeholder="G-XXXXXXXXXX" value="<?= escape($settings['ga4_measurement_id'] ?? '') ?>">
                <small class="text-muted">Contoh: G-ABCDEFGHIJ</small>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Google Analytics Script (Universal)</label>
                <textarea name="google_analytics" class="form-control" rows="4"><?= escape($settings['google_analytics'] ?? '') ?></textarea>
                <small class="text-muted">Script lengkap Google Analytics (UA atau GA4)</small>
            </div>
            
            <h6 class="border-bottom pb-2 mb-3 mt-4">Google Ads</h6>
            
            <div class="mb-3">
                <label class="form-label">Google Ads Script</label>
                <textarea name="google_ads" class="form-control" rows="4"><?= escape($settings['google_ads'] ?? '') ?></textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Google Ads Conversion Label</label>
                <input type="text" name="google_ads_conversion" class="form-control" 
                       value="<?= escape($settings['google_ads_conversion'] ?? '') ?>">
            </div>
            
            <h6 class="border-bottom pb-2 mb-3 mt-4">Meta Pixel</h6>
            
            <div class="mb-3">
                <label class="form-label">Meta Pixel Script</label>
                <textarea name="meta_pixel" class="form-control" rows="4"><?= escape($settings['meta_pixel'] ?? '') ?></textarea>
                <small class="text-muted">Script lengkap Meta Pixel (Facebook Pixel)</small>
            </div>
            
            <h6 class="border-bottom pb-2 mb-3 mt-4">Custom Scripts</h6>
            
            <div class="mb-3">
                <label class="form-label">Custom Head Script</label>
                <textarea name="custom_head" class="form-control" rows="3"><?= escape($settings['custom_head'] ?? '') ?></textarea>
                <small class="text-muted">Disisipkan di &lt;head&gt; tag</small>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Custom Body Script</label>
                <textarea name="custom_body" class="form-control" rows="3"><?= escape($settings['custom_body'] ?? '') ?></textarea>
                <small class="text-muted">Disisipkan di awal &lt;body&gt; tag</small>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Custom Footer Script</label>
                <textarea name="custom_footer" class="form-control" rows="3"><?= escape($settings['custom_footer'] ?? '') ?></textarea>
                <small class="text-muted">Disisipkan di akhir &lt;body&gt; tag</small>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Simpan Advertising
            </button>
        </form>
    </div>
</div>

<?php include '../includes/admin-footer.php'; ?>
