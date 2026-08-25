<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$auth->requireRole(['owner']);

$page_title = 'Tambah Marketing';
include '../includes/admin-header.php';

$db = Database::getInstance();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $targetSales = str_replace(['.', ','], '', $_POST['target_sales'] ?? 0);
    $commissionRate = str_replace(',', '.', $_POST['commission_rate'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    
    if (empty($fullName) || empty($email) || empty($password)) {
        $error = 'Nama, email, dan password wajib diisi';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter';
    } else {
        // Check if email exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email sudah terdaftar';
        } else {
            try {
                $db->beginTransaction();
                
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Create user
                $stmt = $db->prepare("
                    INSERT INTO users (email, password, first_name, role, status) 
                    VALUES (?, ?, ?, 'marketing', ?)
                ");
                $stmt->execute([$email, $hashedPassword, $fullName, $status]);
                $userId = $db->lastInsertId();
                
                // Create marketing record
                $stmt = $db->prepare("
                    INSERT INTO marketing (user_id, full_name, email, phone, target_sales, commission_rate, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$userId, $fullName, $email, $phone, $targetSales, $commissionRate, $status]);
                
                $db->commit();
                
                logActivity($_SESSION['user_id'], 'create_marketing', 'Created marketing: ' . $fullName);
                setFlash('success', 'Marketing berhasil ditambahkan');
                redirect(ADMIN_URL . 'marketing/');
                
            } catch (Exception $e) {
                $db->rollBack();
                error_log("Create marketing error: " . $e->getMessage());
                $error = 'Gagal menambahkan marketing';
            }
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Tambah Marketing Baru</h5>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= escape($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <?= csrfField() ?>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nama Lengkap *</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">WhatsApp</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Password *</label>
                    <input type="password" name="password" class="form-control" required minlength="8">
                    <small class="text-muted">Minimal 8 karakter</small>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Target Penjualan</label>
                    <input type="text" name="target_sales" class="form-control currency" value="0">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Komisi (%)</label>
                    <input type="number" name="commission_rate" class="form-control" step="0.01" value="0">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active">Aktif</option>
                    <option value="inactive">Tidak Aktif</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Simpan Marketing
            </button>
            <a href="index.php" class="btn btn-secondary">Batal</a>
        </form>
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
});
</script>

<?php include '../includes/admin-footer.php'; ?>
