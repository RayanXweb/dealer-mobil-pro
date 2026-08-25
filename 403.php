<?php
http_response_code(403);
require_once 'config/config.php';
$page_title = '403 - Akses Ditolak';
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center text-center">
        <div class="col-lg-6">
            <div class="error-page">
                <h1 class="display-1 fw-bold text-danger">403</h1>
                <h2 class="mb-3">Akses Ditolak</h2>
                <p class="text-muted mb-4">
                    Anda tidak memiliki izin untuk mengakses halaman ini.
                </p>
                <div class="d-flex gap-3 justify-content-center">
                    <a href="<?= BASE_URL ?>" class="btn btn-primary">
                        <i class="fas fa-home me-1"></i> Kembali ke Beranda
                    </a>
                    <?php if ($auth->isLoggedIn()): ?>
                        <a href="<?= ADMIN_URL ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-secondary">
                            <i class="fas fa-sign-in-alt me-1"></i> Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
