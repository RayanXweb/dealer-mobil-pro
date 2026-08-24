<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

$page_title = 'Register';
$error = '';
$success = '';

if ($auth->isLoggedIn()) {
    header('Location: ' . BASE_URL);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    
    $data = [
        'first_name' => $_POST['first_name'] ?? '',
        'last_name' => $_POST['last_name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'address' => $_POST['address'] ?? ''
    ];
    
    if (empty($data['first_name']) || empty($data['email']) || empty($data['password'])) {
        $error = 'Nama, email, dan password wajib diisi';
    } else {
        $result = $auth->register($data);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4">Daftar Akun</h3>
                    
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
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nama Depan *</label>
                                    <input type="text" name="first_name" class="form-control" 
                                           required value="<?= escape($_POST['first_name'] ?? '') ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nama Belakang</label>
                                    <input type="text" name="last_name" class="form-control" 
                                           value="<?= escape($_POST['last_name'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" 
                                       required value="<?= escape($_POST['email'] ?? '') ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Nomor WhatsApp</label>
                                <input type="text" name="phone" class="form-control" 
                                       value="<?= escape($_POST['phone'] ?? '') ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Password *</label>
                                <input type="password" name="password" class="form-control" required 
                                       minlength="8">
                                <small class="text-muted">Minimal 8 karakter</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Konfirmasi Password *</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Alamat</label>
                                <textarea name="address" class="form-control" rows="2"><?= escape($_POST['address'] ?? '') ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-user-plus me-1"></i> Daftar
                            </button>
                        </form>
                        
                        <hr>
                        
                        <p class="text-center mb-0">
                            Sudah punya akun? <a href="login.php">Login</a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
