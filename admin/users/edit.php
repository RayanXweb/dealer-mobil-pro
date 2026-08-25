<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$auth->requireRole(['owner']);

$page_title = 'Edit User';
include '../includes/admin-header.php';

$db = Database::getInstance();

$id = $_GET['id'] ?? 0;
if ($id <= 0) {
    setFlash('danger', 'ID user tidak valid');
    redirect(ADMIN_URL . 'users/');
}

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('danger', 'User tidak ditemukan');
    redirect(ADMIN_URL . 'users/');
}

// Cannot edit self if not owner
if ($id == $_SESSION['user_id']) {
    setFlash('warning', 'Anda tidak dapat mengedit akun sendiri di sini');
    redirect(ADMIN_URL . 'users/');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'customer';
    $status = $_POST['status'] ?? 'active';
    $password = $_POST['password'] ?? '';
    
    if (empty($firstName) || empty($email)) {
        $error = 'Nama dan email wajib diisi';
    } else {
        try {
            $db->beginTransaction();
            
            // Check email duplicate
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                throw new Exception('Email sudah digunakan');
            }
            
            $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, role = ?, status = ?";
            $params = [$firstName, $lastName, $email, $role, $status];
            
            if (!empty($password)) {
                if (strlen($password) < 8) {
                    throw new Exception('Password minimal 8 karakter');
                }
                $sql .= ", password = ?";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            $db->commit();
            
            logActivity($_SESSION['user_id'], 'update_user', 'Updated user: ' . $email);
            setFlash('success', 'User berhasil diperbarui');
            redirect(ADMIN_URL . 'users/');
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Edit User: <?= escape($user['email']) ?></h5>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= escape($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <?= csrfField() ?>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nama Depan *</label>
                    <input type="text" name="first_name" class="form-control" 
                           value="<?= escape($user['first_name']) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nama Belakang</label>
                    <input type="text" name="last_name" class="form-control" 
                           value="<?= escape($user['last_name']) ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Email *</label>
                <input type="email" name="email" class="form-control" 
                       value="<?= escape($user['email']) ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Password (Kosongkan jika tidak diubah)</label>
                <input type="password" name="password" class="form-control" minlength="8">
                <small class="text-muted">Minimal 8 karakter</small>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select">
                    <option value="customer" <?= $user['role'] == 'customer' ? 'selected' : '' ?>>Customer</option>
                    <option value="marketing" <?= $user['role'] == 'marketing' ? 'selected' : '' ?>>Marketing</option>
                    <option value="supervisor" <?= $user['role'] == 'supervisor' ? 'selected' : '' ?>>Supervisor</option>
                    <option value="owner" <?= $user['role'] == 'owner' ? 'selected' : '' ?>>Owner</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" <?= $user['status'] == 'active' ? 'selected' : '' ?>>Aktif</option>
                    <option value="inactive" <?= $user['status'] == 'inactive' ? 'selected' : '' ?>>Tidak Aktif</option>
                    <option value="suspended" <?= $user['status'] == 'suspended' ? 'selected' : '' ?>>Ditangguhkan</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Update User
            </button>
            <a href="index.php" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</div>

<?php include '../includes/admin-footer.php'; ?>
