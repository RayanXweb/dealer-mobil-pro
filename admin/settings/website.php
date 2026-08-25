<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$auth->requireRole(['owner']);

$page_title = 'Pengaturan Website';
include '../includes/admin-header.php';

$db = Database::getInstance();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    
    $settings = [
        'website_name' => $_POST['website_name'] ?? '',
        'website_tagline' => $_POST['website_tagline'] ?? '',
        'website_description' => $_POST['website_description'] ?? '',
        'website_phone' => $_POST['website_phone'] ?? '',
        'website_whatsapp' => $_POST['website_whatsapp'] ?? '',
        'website_email' => $_POST['website_email'] ?? '',
        'website_address' => $_POST['website_address'] ?? '',
        'website_opening_hours' => $_POST['website_opening_hours'] ?? '',
        'website_instagram' => $_POST['website_instagram'] ?? '',
        'website_facebook' => $_POST['website_facebook'] ?? '',
        'website_tiktok' => $_POST['website_tiktok'] ?? '',
        'website_youtube' => $_POST['website_youtube'] ?? '',
        'website_primary_color' => $_POST['website_primary_color'] ?? '#1a2332',
        'website_secondary_color' => $_POST['website_secondary_color'] ?? '#c9a84c',
        'website_currency' => $_POST['website_currency'] ?? 'Rp',
        'website_footer_text' => $_POST['website_footer_text'] ?? ''
    ];
    
    try {
        $db->beginTransaction();
        
        foreach ($settings as $key => $value) {
            $stmt = $db->prepare("
                UPDATE website_settings SET setting_value = ? WHERE setting_key = ?
            ");
            $stmt->execute([$value, $key]);
        }
        
        // Upload logo
        if (!empty($_FILES['logo']['tmp_name'])) {
            $uploadDir = UPLOADS_PATH . 'settings/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $result = uploadFile($_FILES['logo'], $uploadDir);
            if ($result['success']) {
                $stmt = $db->prepare("
                    UPDATE website_settings SET setting_value = ? WHERE setting_key = 'logo'
                ");
                $stmt->execute([$result['filename']]);
            }
        }
        
        // Upload favicon
        if (!empty($_FILES['favicon']['tmp_name'])) {
            $uploadDir = UPLOADS_PATH . 'settings/';
            $result = uploadFile($_FILES['favicon'], $uploadDir, ['ico', 'png', 'jpg', 'jpeg', 'gif', 'svg']);
            if ($result['success']) {
                $stmt = $db->prepare("
                    UPDATE website_settings SET setting_value = ? WHERE setting_key = 'favicon'
                ");
                $stmt->execute([$result['filename']]);
            }
        }
        
        $db->commit();
        
        logActivity($_SESSION['user_id'], 'update_settings', 'Updated website settings');
        setFlash('success', 'Pengaturan website berhasil diperbarui');
        redirect(ADMIN_URL . 'settings/website.php');
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Update settings error: " . $e->getMessage());
        $error = 'Gagal memperbarui pengaturan';
    }
}

// Get current settings
$settings = getWebsiteSettings();
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Pengaturan Website</h5>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= escape($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>
            
            <h6 class="border-bottom pb-2 mb-3">Informasi Umum</h6>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nama Website</label>
                    <input type="text" name="website_name" class="form-control" 
                           value="<?= escape($settings['website_name'] ?? 'AutoDealer') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Tagline</label>
                    <input type="text" name="website_tagline" class="form-control" 
                           value="<?= escape($settings['website_tagline'] ?? '') ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Deskripsi</label>
                <textarea name="website_description" class="form-control" rows="2"><?= escape($settings['website_description'] ?? '') ?></textarea>
            </div>
            
            <h6 class="border-bottom pb-2 mb-3 mt-4">Logo & Favicon</h6>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Logo Saat Ini</label>
                    <?php if (!empty($settings['logo'])): ?>
                        <div>
                            <img src="<?= UPLOADS_URL . 'settings/' . $settings['logo'] ?>" 
                                 alt="Logo" style="max-height: 80px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="logo" class="form-control mt-2" accept="image/*">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Favicon Saat Ini</label>
                    <?php if (!empty($settings['favicon'])): ?>
                        <div>
                            <img src="<?= UPLOADS_URL . 'settings/' . $settings['favicon'] ?>" 
                                 alt="Favicon" style="max-height: 40px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="favicon" class="form-control mt-2" accept="image/*">
                </div>
            </div>
            
            <h6 class="border-bottom pb-2 mb-3 mt-4">Kontak</h6>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nomor Telepon</label>
                    <input type="text" name="website_phone" class="form-control" 
                           value="<?= escape($settings['website_phone'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nomor WhatsApp</label>
                    <input type="text" name="website_whatsapp" class="form-control" 
                           value="<?= escape($settings['website_whatsapp'] ?? '') ?>">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="website_email" class="form-control" 
                           value="<?= escape($settings['website_email'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Jam Operasional</label>
                    <input type="text" name="website_opening_hours" class="form-control" 
                           value="<?= escape($settings['website_opening_hours'] ?? '') ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Alamat</label>
                <textarea name="website_address" class="form-control" rows="2"><?= escape($settings['website_address'] ?? '') ?></textarea>
            </div>
            
            <h6 class="border-bottom pb-2 mb-3 mt-4">Media Sosial</h6>
            
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Instagram</label>
                    <input type="text" name="website_instagram" class="form-control" 
                           value="<?= escape($settings['website_instagram'] ?? '') ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Facebook</label>
                    <input type="text" name="website_facebook" class="form-control" 
                           value="<?= escape($settings['website_facebook'] ?? '') ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">TikTok</label>
                    <input type="text" name="website_tiktok" class="form-control" 
                           value="<?= escape($settings['website_tiktok'] ?? '') ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">YouTube</label>
                    <input type="text" name="website_youtube" class="form-control" 
                           value="<?= escape($settings['website_youtube'] ?? '') ?>">
                </div>
            </div>
            
            <h6 class="border-bottom pb-2 mb-3 mt-4">Desain & Lainnya</h6>
            
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Warna Utama</label>
                    <input type="color" name="website_primary_color" class="form-control form-control-color" 
                           value="<?= escape($settings['website_primary_color'] ?? '#1a2332') ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Warna Sekunder</label>
                    <input type="color" name="website_secondary_color" class="form-control form-control-color" 
                           value="<?= escape($settings['website_secondary_color'] ?? '#c9a84c') ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Mata Uang</label>
                    <input type="text" name="website_currency" class="form-control" 
                           value="<?= escape($settings['website_currency'] ?? 'Rp') ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Footer Text</label>
                    <input type="text" name="website_footer_text" class="form-control" 
                           value="<?= escape($settings['website_footer_text'] ?? '') ?>">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Simpan Pengaturan
            </button>
        </form>
    </div>
</div>

<?php include '../includes/admin-footer.php'; ?>
