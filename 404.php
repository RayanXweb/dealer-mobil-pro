<?php
http_response_code(404);
require_once 'config/config.php';
$page_title = '404 - Halaman Tidak Ditemukan';
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center text-center">
        <div class="col-lg-6">
            <div class="error-page">
                <h1 class="display-1 fw-bold text-primary">404</h1>
                <h2 class="mb-3">Halaman Tidak Ditemukan</h2>
                <p class="text-muted mb-4">
                    Maaf, halaman yang Anda cari tidak ditemukan atau telah dipindahkan.
                </p>
                <div class="d-flex gap-3 justify-content-center">
                    <a href="<?= BASE_URL ?>" class="btn btn-primary">
                        <i class="fas fa-home me-1"></i> Kembali ke Beranda
                    </a>
                    <a href="javascript:history.back()" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
