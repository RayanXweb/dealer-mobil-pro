<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$auth->requireRole(['owner']);

$page_title = 'Tambah User';
include '../includes/admin-header.php';

$db = Database::getInstance();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'customer';
    $status = $_POST['status'] ?? 'active';
    
    if (empty($firstName) || empty($email) || empty($password)) {
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
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                $db->beginTransaction();
                
                $stmt = $db->prepare("
                    INSERT INTO users (first_name, last_name, email, password, role, status) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$firstName, $lastName, $email, $hashedPassword, $role, $status]);
                
                $userId = $db->lastInsertId();
                
                // Create customer record if role is customer
                if ($role == 'customer') {
                    $stmt = $db->prepare("
                        INSERT INTO customers (user_id, full_name, email, status) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$userId, $firstName . ' ' . $lastName, $email, $status]);
                }
                
                // Create marketing record if role is marketing
                if ($role == 'marketing') {
                    $stmt = $db->prepare("
                        INSERT INTO marketing (user_id, full_name, email, status) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$userId, $firstName . ' ' . $lastName, $email, $status]);
                }
                
                $db->commit();
                
                logActivity($_SESSION['user_id'], 'create_user', 'Created user: ' . $email);
                setFlash('success', 'User berhasil ditambahkan');
                redirect(ADMIN_URL . 'users/');
                
            } catch (Exception $e) {
                $db->rollBack();
                error_log("Create user error: " . $e->getMessage());
                $error = 'Gagal menambahkan user';
            }
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Tambah User Baru</h5>
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
                    <input type="text" name="first_name" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nama Belakang</label>
                    <input type="text" name="last_name" class="form-control">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Email *</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Password *</label>
                <input type="password" name="password" class="form-control" required minlength="8">
                <small class="text-muted">Minimal 8 karakter</small>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select">
                    <option value="customer">Customer</option>
                    <option value="marketing">Marketing</option>
                    <option value="supervisor">Supervisor</option>
                    <option value="owner">Owner</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active">Aktif</option>
                    <option value="inactive">Tidak Aktif</option>
                    <option value="suspended">Ditangguhkan</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Simpan User
            </button>
            <a href="index.php" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>

<?php include '../includes/admin-footer.php'; ?>
