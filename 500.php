<?php
http_response_code(500);
require_once 'config/config.php';
$page_title = '500 - Server Error';
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center text-center">
        <div class="col-lg-6">
            <div class="error-page">
                <h1 class="display-1 fw-bold text-warning">500</h1>
                <h2 class="mb-3">Terjadi Kesalahan Server</h2>
                <p class="text-muted mb-4">
                    Maaf, terjadi kesalahan pada server. Tim kami telah diberitahu dan akan segera memperbaikinya.
                </p>
                <div class="d-flex gap-3 justify-content-center">
                    <a href="<?= BASE_URL ?>" class="btn btn-primary">
                        <i class="fas fa-home me-1"></i> Kembali ke Beranda
                    </a>
                    <a href="javascript:location.reload()" class="btn btn-outline-secondary">
                        <i class="fas fa-sync me-1"></i> Coba Lagi
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
