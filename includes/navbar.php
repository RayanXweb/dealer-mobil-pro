<?php
$settings = getWebsiteSettings();
$isLoggedIn = $auth->isLoggedIn();
$user = $auth->getCurrentUser();
$cartCount = 0;

// Get cart count
if ($isLoggedIn) {
    $db = Database::getInstance();
    $stmt = $db->prepare("
        SELECT SUM(ci.quantity) as total 
        FROM cart_items ci 
        JOIN carts c ON ci.cart_id = c.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    $cartCount = $result['total'] ?? 0;
} elseif (isset($_SESSION['cart_id'])) {
    $db = Database::getInstance();
    $stmt = $db->prepare("
        SELECT SUM(quantity) as total 
        FROM cart_items 
        WHERE cart_id = ?
    ");
    $stmt->execute([$_SESSION['cart_id']]);
    $result = $stmt->fetch();
    $cartCount = $result['total'] ?? 0;
}
?>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background: linear-gradient(135deg, #1a2332, #2c3e50);">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>">
            <?php if (!empty(getSetting('logo'))): ?>
                <img src="<?= UPLOADS_URL . 'settings/' . getSetting('logo') ?>" 
                     alt="<?= escape($settings['website_name'] ?? 'AutoDealer') ?>" 
                     height="40">
            <?php else: ?>
                <i class="fas fa-car me-2"></i>
                <?= escape($settings['website_name'] ?? 'AutoDealer') ?>
            <?php endif; ?>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" 
                       href="<?= BASE_URL ?>">
                        <i class="fas fa-home me-1"></i> Beranda
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : '' ?>" 
                       href="<?= BASE_URL ?>products.php">
                        <i class="fas fa-car me-1"></i> Mobil
                    </a>
                </li>
                
                <?php if ($isLoggedIn && $user['role'] != 'customer'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= ADMIN_URL ?>">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item">
                        <a class="nav-link position-relative <?= basename($_SERVER['PHP_SELF']) == 'cart.php' ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>cart.php">
                            <i class="fas fa-shopping-cart me-1"></i>
                            <?php if ($cartCount > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $cartCount ?>
                                    <span class="visually-hidden">items in cart</span>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?= escape($user['first_name']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>profile.php">
                                    <i class="fas fa-user me-2"></i> Profil
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>orders.php">
                                    <i class="fas fa-list me-2"></i> Pesanan
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>offers.php">
                                    <i class="fas fa-file-invoice me-2"></i> Penawaran
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?= BASE_URL ?>logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>login.php">
                            <i class="fas fa-sign-in-alt me-1"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary btn-sm ms-lg-2 <?= basename($_SERVER['PHP_SELF']) == 'register.php' ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>register.php">
                            <i class="fas fa-user-plus me-1"></i> Daftar
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- WhatsApp Floating Button -->
<a href="<?= getWhatsAppLink($settings['whatsapp'] ?? '', 'Halo, saya butuh informasi tentang mobil di AutoDealer') ?>" 
   class="whatsapp-float" target="_blank">
    <i class="fab fa-whatsapp"></i>
    <span class="visually-hidden">Chat WhatsApp</span>
</a>

<style>
.whatsapp-float {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: #25D366;
    color: white;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    z-index: 1000;
    box-shadow: 0 4px 20px rgba(37, 211, 102, 0.4);
    transition: all 0.3s ease;
    text-decoration: none;
}
.whatsapp-float:hover {
    transform: scale(1.1);
    color: white;
    box-shadow: 0 6px 30px rgba(37, 211, 102, 0.6);
}
</style>
