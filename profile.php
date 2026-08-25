<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

$auth->requireLogin();

$page_title = 'Profil Saya';
$db = Database::getInstance();

// Get user data
$user = $auth->getCurrentUser();

// Get customer data
$stmt = $db->prepare("SELECT * FROM customers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$customer = $stmt->fetch();

if (!$customer) {
    // Create customer if not exists
    $stmt = $db->prepare("
        INSERT INTO customers (user_id, full_name, email, phone) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $user['first_name'] . ' ' . ($user['last_name'] ?? ''),
        $user['email'],
        $user['phone'] ?? ''
    ]);
    $customer = $db->lastInsertId();
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customer]);
    $customer = $stmt->fetch();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    
    $action = $_POST['action'] ?? 'profile';
    
    if ($action === 'profile') {
        // Update profile
        $fullName = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        
        if (empty($fullName) || empty($phone)) {
            $error = 'Nama dan nomor WhatsApp wajib diisi';
        } else {
            // Update user
            $nameParts = explode(' ', $fullName, 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';
            
            $stmt = $db->prepare("
                UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE id = ?
            ");
            $stmt->execute([$firstName, $lastName, $phone, $_SESSION['user_id']]);
            
            // Update customer
            $stmt = $db->prepare("
                UPDATE customers SET full_name = ?, phone = ?, address = ? WHERE user_id = ?
            ");
            $stmt->execute([$fullName, $phone, $address, $_SESSION['user_id']]);
            
            // Update session
            $_SESSION['user_name'] = $fullName;
            
            $success = 'Profil berhasil diperbarui';
            
            // Refresh data
            $user = $auth->getCurrentUser();
            $stmt = $db->prepare("SELECT * FROM customers WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $customer = $stmt->fetch();
        }
    } elseif ($action === 'password') {
        // Change password
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'Semua field password wajib diisi';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Password baru dan konfirmasi tidak cocok';
        } elseif (strlen($newPassword) < 8) {
            $error = 'Password minimal 8 karakter';
        } elseif (!password_verify($currentPassword, $user['password'])) {
            $error = 'Password saat ini salah';
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
            
            $success = 'Password berhasil diubah';
        }
    } elseif ($action === 'avatar') {
        // Upload avatar
        if (!empty($_FILES['avatar']['tmp_name'])) {
            $uploadDir = UPLOADS_PATH . 'users/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $result = uploadFile($_FILES['avatar'], $uploadDir, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
            if ($result['success']) {
                // Delete old avatar
                if (!empty($user['avatar']) && file_exists($uploadDir . $user['avatar'])) {
                    unlink($uploadDir . $user['avatar']);
                }
                
                $stmt = $db->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                $stmt->execute([$result['filename'], $_SESSION['user_id']]);
                
                $success = 'Foto profil berhasil diunggah';
                $user = $auth->getCurrentUser();
            } else {
                $error = $result['message'];
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="container py-4">
    <h1 class="mb-4">Profil Saya</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= escape($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= escape($success) ?></div>
    <?php endif; ?>
    
    <div class="row g-4">
        <div class="col-md-4">
            <!-- Avatar -->
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar-preview mb-3">
                        <?php if (!empty($user['avatar'])): ?>
                            <img src="<?= UPLOADS_URL . 'users/' . $user['avatar'] ?>" 
                                 alt="Avatar" class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
                        <?php else: ?>
                            <div class="avatar-placeholder" style="width: 120px; height: 120px; border-radius: 50%; background: #e9ecef; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="fas fa-user fa-4x text-muted"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="avatar">
                        <div class="mb-3">
                            <input type="file" name="avatar" class="form-control form-control-sm" accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-upload me-1"></i> Upload Foto
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="card mt-4">
                <div class="card-body">
                    <h6>Statistik</h6>
                    <hr>
                    
                    <?php
                    $stmt = $db->prepare("SELECT COUNT(*) as total FROM orders WHERE customer_id = ?");
                    $stmt->execute([$customer['id']]);
                    $orderCount = $stmt->fetch()['total'];
                    ?>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Total Pesanan</span>
                        <strong><?= $orderCount ?></strong>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <!-- Profile Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Informasi Profil</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="profile">
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap *</label>
                            <input type="text" name="full_name" class="form-control" 
                                   value="<?= escape($customer['full_name'] ?? '') ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?= escape($user['email']) ?>" readonly>
                            <small class="text-muted">Email tidak dapat diubah</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nomor WhatsApp *</label>
                            <input type="text" name="phone" class="form-control" 
                                   value="<?= escape($customer['phone'] ?? '') ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea name="address" class="form-control" rows="3"><?= escape($customer['address'] ?? '') ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Update Profil
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Ganti Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="password">
                        
                        <div class="mb-3">
                            <label class="form-label">Password Saat Ini *</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password Baru *</label>
                            <input type="password" name="new_password" class="form-control" required minlength="8">
                            <small class="text-muted">Minimal 8 karakter</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Password Baru *</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key me-1"></i> Ganti Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
