<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

$settings = getWebsiteSettings();

// Get featured products
$db = Database::getInstance();
$stmt = $db->prepare("
    SELECT p.*, b.name as brand_name, b.slug as brand_slug,
    (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM products p
    JOIN brands b ON p.brand_id = b.id
    WHERE p.status = 'available' AND p.is_featured = 1
    ORDER BY p.created_at DESC
    LIMIT 6
");
$stmt->execute();
$featuredProducts = $stmt->fetchAll();

// Get promo products
$stmt = $db->prepare("
    SELECT p.*, b.name as brand_name, b.slug as brand_slug,
    (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM products p
    JOIN brands b ON p.brand_id = b.id
    WHERE p.status = 'available' AND p.is_promo = 1
    ORDER BY p.created_at DESC
    LIMIT 6
");
$stmt->execute();
$promoProducts = $stmt->fetchAll();

// Get brands
$stmt = $db->prepare("SELECT * FROM brands WHERE status = 'active' ORDER BY name");
$stmt->execute();
$brands = $stmt->fetchAll();

include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-overlay"></div>
    <div class="container position-relative" style="z-index: 2;">
        <div class="row align-items-center min-vh-100">
            <div class="col-lg-7 text-white">
                <h1 class="display-2 fw-bold mb-4" data-aos="fade-up">
                    Temukan Mobil<br>Impian Anda
                </h1>
                <p class="lead mb-4" data-aos="fade-up" data-aos-delay="100">
                    <?= escape($settings['tagline'] ?? 'Koleksi mobil premium dengan harga terbaik') ?>
                </p>
                <div class="d-flex gap-3 flex-wrap" data-aos="fade-up" data-aos-delay="200">
                    <a href="products.php" class="btn btn-primary btn-lg px-5 py-3">
                        <i class="fas fa-car me-2"></i>Lihat Mobil
                    </a>
                    <a href="<?= getWhatsAppLink($settings['whatsapp'] ?? '', 'Halo, saya tertarik dengan mobil di AutoDealer') ?>" 
                       class="btn btn-outline-light btn-lg px-5 py-3">
                        <i class="fab fa-whatsapp me-2"></i>Hubungi Marketing
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Brands Section -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Brand Kami</h2>
        <div class="row g-4 justify-content-center">
            <?php foreach ($brands as $brand): ?>
                <div class="col-4 col-md-2 text-center">
                    <a href="products.php?brand=<?= $brand['slug'] ?>" class="text-decoration-none">
                        <?php if ($brand['logo']): ?>
                            <img src="<?= UPLOADS_URL . 'brands/' . $brand['logo'] ?>" 
                                 alt="<?= escape($brand['name']) ?>" 
                                 class="img-fluid mb-2" style="max-height: 60px; filter: grayscale(100%); opacity: 0.7; transition: all 0.3s;">
                        <?php else: ?>
                            <div class="brand-placeholder bg-white p-3 rounded shadow-sm">
                                <i class="fas fa-car fa-2x text-muted"></i>
                            </div>
                        <?php endif; ?>
                        <p class="text-muted small"><?= escape($brand['name']) ?></p>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products -->
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Mobil Unggulan</h2>
            <a href="products.php?featured=1" class="btn btn-outline-primary">Lihat Semua</a>
        </div>
        <div class="row g-4">
            <?php foreach ($featuredProducts as $product): ?>
                <div class="col-md-4 col-lg-4">
                    <?php include 'includes/product-card.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Promo Products -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Mobil Promo</h2>
            <a href="products.php?promo=1" class="btn btn-outline-primary">Lihat Semua</a>
        </div>
        <div class="row g-4">
            <?php foreach ($promoProducts as $product): ?>
                <div class="col-md-4 col-lg-4">
                    <?php include 'includes/product-card.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Advantages -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">Keunggulan Dealer Kami</h2>
        <div class="row g-4">
            <div class="col-md-3 text-center">
                <div class="feature-icon mb-3">
                    <i class="fas fa-shield-alt fa-3x text-primary"></i>
                </div>
                <h5>Mobil Terjamin</h5>
                <p class="text-muted">Semua mobil dalam kondisi prima dan terjamin</p>
            </div>
            <div class="col-md-3 text-center">
                <div class="feature-icon mb-3">
                    <i class="fas fa-handshake fa-3x text-primary"></i>
                </div>
                <h5>Harga Terbaik</h5>
                <p class="text-muted">Harga kompetitif dengan promo menarik</p>
            </div>
            <div class="col-md-3 text-center">
                <div class="feature-icon mb-3">
                    <i class="fas fa-headset fa-3x text-primary"></i>
                </div>
                <h5>Layanan Profesional</h5>
                <p class="text-muted">Tim marketing siap membantu Anda</p>
            </div>
            <div class="col-md-3 text-center">
                <div class="feature-icon mb-3">
                    <i class="fas fa-credit-card fa-3x text-primary"></i>
                </div>
                <h5>Pembayaran Mudah</h5>
                <p class="text-muted">Pilihan pembayaran tunai, kredit, dan transfer</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section py-5 text-white" style="background: linear-gradient(135deg, #1a2332, #2c3e50);">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="display-5">Siap Membeli Mobil Impian?</h2>
                <p class="lead mb-0">Hubungi kami sekarang dan dapatkan penawaran terbaik</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="<?= getWhatsAppLink($settings['whatsapp'] ?? '', 'Halo, saya ingin konsultasi tentang mobil') ?>" 
                   class="btn btn-success btn-lg px-5">
                    <i class="fab fa-whatsapp me-2"></i>Chat Now
                </a>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
