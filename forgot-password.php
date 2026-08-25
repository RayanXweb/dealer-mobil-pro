<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

$page_title = 'Lupa Password';

if ($auth->isLoggedIn()) {
    header('Location: ' . BASE_URL);
    exit;
}

$error = '';
$success = '';
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Email wajib diisi';
    } elseif (!validateEmail($email)) {
        $error = 'Email tidak valid';
    } else {
        // Check if user exists
        $stmt = $db->prepare("SELECT id, email FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $stmt = $db->prepare("
                INSERT INTO password_resets (email, token, expires_at) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE token = ?, expires_at = ?
            ");
            $stmt->execute([$email, $token, $expires, $token, $expires]);
            
            // Send reset email
            $resetLink = BASE_URL . 'reset-password.php?token=' . $token;
            $message = "Halo,\n\n";
            $message .= "Klik link berikut untuk reset password Anda:\n";
            $message .= $resetLink . "\n\n";
            $message .= "Link ini berlaku 1 jam.\n\n";
            $message .= "Jika Anda tidak meminta reset password, abaikan email ini.";
            
            $subject = "Reset Password - AutoDealer";
            $headers = "From: " . getSetting('website_email', 'noreply@autodealer.com') . "\r\n";
            $headers .= "Reply-To: " . getSetting('website_email', 'noreply@autodealer.com') . "\r\n";
            
            if (mail($email, $subject, $message, $headers)) {
                $success = 'Link reset password telah dikirim ke email Anda.';
            } else {
                $error = 'Gagal mengirim email. Silakan coba lagi.';
            }
        } else {
            $error = 'Email tidak terdaftar';
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
                    <h3 class="text-center mb-4">Lupa Password</h3>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= escape($error) ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= escape($success) ?></div>
                        <p class="text-center">
                            <a href="login.php" class="btn btn-primary">Kembali ke Login</a>
                        </p>
                    <?php else: ?>
                        <p class="text-muted text-center mb-4">
                            Masukkan email Anda dan kami akan mengirimkan link reset password.
                        </p>
                        
                        <form method="POST">
                            <?= csrfField() ?>
                            
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?= escape($_POST['email'] ?? '') ?>" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-paper-plane me-1"></i> Kirim Link Reset
                            </button>
                        </form>
                        
                        <hr>
                        
                        <p class="text-center mb-0">
                            <a href="login.php">Kembali ke Login</a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
