    </main>

<!-- Footer -->
<footer class="text-white pt-5 pb-3" style="background: #1a2332;">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <h5 class="mb-3">
                    <?php if (!empty(getSetting('logo'))): ?>
                        <img src="<?= UPLOADS_URL . 'settings/' . getSetting('logo') ?>" 
                             alt="<?= escape($settings['website_name'] ?? 'AutoDealer') ?>" 
                             height="40">
                    <?php else: ?>
                        <i class="fas fa-car me-2"></i>
                        <?= escape($settings['website_name'] ?? 'AutoDealer') ?>
                    <?php endif; ?>
                </h5>
                <p class="text-muted small"><?= escape($settings['tagline'] ?? 'Premium Car Dealer') ?></p>
                <div class="social-icons mt-3">
                    <?php if (!empty($settings['instagram'])): ?>
                        <a href="https://instagram.com/<?= escape($settings['instagram']) ?>" class="text-muted me-3" target="_blank">
                            <i class="fab fa-instagram fa-lg"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($settings['facebook'])): ?>
                        <a href="https://facebook.com/<?= escape($settings['facebook']) ?>" class="text-muted me-3" target="_blank">
                            <i class="fab fa-facebook fa-lg"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($settings['tiktok'])): ?>
                        <a href="https://tiktok.com/@<?= escape($settings['tiktok']) ?>" class="text-muted me-3" target="_blank">
                            <i class="fab fa-tiktok fa-lg"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($settings['youtube'])): ?>
                        <a href="https://youtube.com/<?= escape($settings['youtube']) ?>" class="text-muted" target="_blank">
                            <i class="fab fa-youtube fa-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-4">
                <h5>Kontak</h5>
                <ul class="list-unstyled text-muted small">
                    <?php if (!empty($settings['phone'])): ?>
                        <li class="mb-2">
                            <i class="fas fa-phone me-2"></i> <?= escape($settings['phone']) ?>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($settings['whatsapp'])): ?>
                        <li class="mb-2">
                            <i class="fab fa-whatsapp me-2"></i> <?= escape($settings['whatsapp']) ?>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($settings['email'])): ?>
                        <li class="mb-2">
                            <i class="fas fa-envelope me-2"></i> <?= escape($settings['email']) ?>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($settings['address'])): ?>
                        <li class="mb-2">
                            <i class="fas fa-map-marker-alt me-2"></i> <?= escape($settings['address']) ?>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="col-md-4">
                <h5>Jam Operasional</h5>
                <p class="text-muted small">
                    <?= nl2br(escape($settings['opening_hours'] ?? 'Senin - Sabtu: 08:00 - 20:00')) ?>
                </p>
                <a href="cek-pesanan.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-search me-1"></i> Cek Pesanan
                </a>
            </div>
        </div>
        
        <hr class="border-secondary mt-4">
        <div class="text-center text-muted small">
            <?= escape($settings['footer_text'] ?? '© 2026 AutoDealer. All Rights Reserved.') ?>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery (for AJAX) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- AOS Animation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>

<!-- Custom JS -->
<script src="<?= ASSETS_URL ?>js/frontend.js?v=<?= time() ?>"></script>

<?php
// Body scripts
$stmt = $db->prepare("SELECT script_code FROM advertisements WHERE position = 'body' AND is_active = 1");
$stmt->execute();
$bodyScripts = $stmt->fetchAll();
foreach ($bodyScripts as $script) {
    echo $script['script_code'] . "\n";
}
?>

<!-- Footer scripts -->
<?php
$stmt = $db->prepare("SELECT script_code FROM advertisements WHERE position = 'footer' AND is_active = 1");
$stmt->execute();
$footerScripts = $stmt->fetchAll();
foreach ($footerScripts as $script) {
    echo $script['script_code'] . "\n";
}
?>

</body>
</html>
