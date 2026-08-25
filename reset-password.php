<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

$page_title = 'Reset Password';

if ($auth->isLoggedIn()) {
    header('Location: ' . BASE_URL);
    exit;
}

$token = $_GET['token'] ?? '';
$db = Database::getInstance();

if (empty($token)) {
    setFlash('danger', 'Token reset tidak valid');
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

// Verify token
$stmt = $db->prepare("
    SELECT * FROM password_resets 
    WHERE token = ? AND expires_at > NOW() AND used = 0
");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    setFlash('danger', 'Token reset tidak valid atau telah kadaluarsa');
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if (empty($password) || empty($confirm)) {
        $error = 'Password wajib diisi';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter';
    } elseif ($password !== $confirm) {
        $error = 'Password tidak cocok';
    } else {
        try {
            $db->beginTransaction();
            
            // Update password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$hashedPassword, $reset['email']]);
            
            // Mark token as used
            $stmt = $db->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $stmt->execute([$token]);
            
            $db->commit();
            
            $success = 'Password berhasil direset! Silakan login dengan password baru.';
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Reset password error: " . $e->getMessage());
            $error = 'Gagal mereset password';
        }
    }
}

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4">Reset Password</h3>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= escape($error) ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= escape($success) ?></div>
                        <p class="text-center">
                            <a href="login.php" class="btn btn-primary">Login Sekarang</a>
                        </p>
                    <?php else: ?>
                        <form method="POST">
                            <?= csrfField() ?>
                            
                            <div class="mb-3">
                                <label class="form-label">Password Baru *</label>
                                <input type="password" name="password" class="form-control" required minlength="8">
                                <small class="text-muted">Minimal 8 karakter</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Konfirmasi Password Baru *</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-key me-1"></i> Reset Password
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
