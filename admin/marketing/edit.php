<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$auth->requireRole(['owner']);

$page_title = 'Edit Marketing';
include '../includes/admin-header.php';

$db = Database::getInstance();

$id = $_GET['id'] ?? 0;
if ($id <= 0) {
    setFlash('danger', 'ID marketing tidak valid');
    redirect(ADMIN_URL . 'marketing/');
}

$stmt = $db->prepare("SELECT * FROM marketing WHERE id = ?");
$stmt->execute([$id]);
$marketing = $stmt->fetch();

if (!$marketing) {
    setFlash('danger', 'Marketing tidak ditemukan');
    redirect(ADMIN_URL . 'marketing/');
}

// Get user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$marketing['user_id']]);
$user = $stmt->fetch();

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
    
    if (empty($fullName) || empty($email)) {
        $error = 'Nama dan email wajib diisi';
    } else {
        try {
            $db->beginTransaction();
            
            // Update marketing
            $stmt = $db->prepare("
                UPDATE marketing SET 
                    full_name = ?, email = ?, phone = ?, 
                    target_sales = ?, commission_rate = ?, status = ? 
                WHERE id = ?
            ");
            $stmt->execute([$fullName, $email, $phone, $targetSales, $commissionRate, $status, $id]);
            
            // Update user
            $sql = "UPDATE users SET first_name = ?, email = ?, status = ?";
            $params = [$fullName, $email, $status];
            
            if (!empty($password)) {
                if (strlen($password) < 8) {
                    throw new Exception('Password minimal 8 karakter');
                }
                $sql .= ", password = ?";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $marketing['user_id'];
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            $db->commit();
            
            logActivity($_SESSION['user_id'], 'update_marketing', 'Updated marketing: ' . $fullName);
            setFlash('success', 'Marketing berhasil diperbarui');
            redirect(ADMIN_URL . 'marketing/');
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Edit Marketing: <?= escape($marketing['full_name']) ?></h5>
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
                    <input type="text" name="full_name" class="form-control" 
                           value="<?= escape($marketing['full_name']) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?= escape($marketing['email']) ?>" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">WhatsApp</label>
                    <input type="text" name="phone" class="form-control" 
                           value="<?= escape($marketing['phone']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Password (Kosongkan jika tidak diubah)</label>
                    <input type="password" name="password" class="form-control" minlength="8">
                    <small class="text-muted">Minimal 8 karakter</small>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Target Penjualan</label>
                    <input type="text" name="target_sales" class="form-control currency" 
                           value="<?= number_format($marketing['target_sales'] ?? 0, 0, ',', '.') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Komisi (%)</label>
                    <input type="number" name="commission_rate" class="form-control" step="0.01" 
                           value="<?= $marketing['commission_rate'] ?? 0 ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" <?= $marketing['status'] == 'active' ? 'selected' : '' ?>>Aktif</option>
                    <option value="inactive" <?= $marketing['status'] == 'inactive' ? 'selected' : '' ?>>Tidak Aktif</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Update Marketing
            </button>
            <a href="index.php" class="btn btn-secondary">Kembali</a>
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
