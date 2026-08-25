<?php
$user = $auth->getCurrentUser();
$role = $user['role'];
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));

function isActive($page, $dir = '') {
    global $currentPage, $currentDir;
    if ($dir) {
        return $currentDir == $dir ? 'active' : '';
    }
    return $currentPage == $page ? 'active' : '';
}

function isActiveParent($dirs) {
    global $currentDir;
    return in_array($currentDir, $dirs) ? 'active' : '';
}
?>

<nav class="admin-sidebar">
    <div class="sidebar-user text-center py-3">
        <div class="avatar-circle mx-auto mb-2">
            <?php if (!empty($user['avatar'])): ?>
                <img src="<?= UPLOADS_URL . 'users/' . $user['avatar'] ?>" alt="Avatar" class="rounded-circle" width="50">
            <?php else: ?>
                <i class="fas fa-user fa-2x"></i>
            <?php endif; ?>
        </div>
        <h6 class="mb-0"><?= escape($user['first_name'] ?? $user['username'] ?? 'User') ?></h6>
        <small class="text-muted">
            <span class="badge bg-<?= $role == 'owner' ? 'danger' : ($role == 'supervisor' ? 'warning' : 'info') ?>">
                <?= strtoupper($role) ?>
            </span>
        </small>
    </div>
    
    <ul class="nav nav-pills flex-column">
        <!-- Dashboard -->
        <li class="nav-item">
            <a class="nav-link <?= isActive('index.php', '') ?>" href="<?= ADMIN_URL ?>">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
        </li>
        
        <!-- Products -->
        <li class="nav-item">
            <a class="nav-link <?= isActiveParent(['products']) ?>" href="#productsMenu" data-bs-toggle="collapse">
                <i class="fas fa-car me-2"></i> Produk
                <i class="fas fa-chevron-down float-end"></i>
            </a>
            <div class="collapse <?= isActiveParent(['products']) ? 'show' : '' ?>" id="productsMenu">
                <ul class="nav flex-column ms-3">
                    <li class="nav-item">
                        <a class="nav-link <?= isActive('index.php', 'products') ?>" href="<?= ADMIN_URL ?>products/">
                            <i class="fas fa-list me-1"></i> Semua Produk
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= isActive('create.php', 'products') ?>" href="<?= ADMIN_URL ?>products/create.php">
                            <i class="fas fa-plus me-1"></i> Tambah Produk
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= isActive('index.php', 'brands') ?>" href="<?= ADMIN_URL ?>brands/">
                            <i class="fas fa-tags me-1"></i> Brand
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        
        <!-- Orders -->
        <li class="nav-item">
            <a class="nav-link <?= isActiveParent(['orders']) ?>" href="<?= ADMIN_URL ?>orders/">
                <i class="fas fa-shopping-bag me-2"></i> Pesanan
                <?php
                // Get pending orders count
                $db = Database::getInstance();
                $stmt = $db->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
                $pending = $stmt->fetch()['count'] ?? 0;
                if ($pending > 0):
                ?>
                <span class="badge bg-danger float-end"><?= $pending ?></span>
                <?php endif; ?>
            </a>
        </li>
        
        <!-- Transactions -->
        <li class="nav-item">
            <a class="nav-link <?= isActiveParent(['transactions']) ?>" href="<?= ADMIN_URL ?>transactions/">
                <i class="fas fa-money-bill-wave me-2"></i> Transaksi
            </a>
        </li>
        
        <!-- Customers -->
        <li class="nav-item">
            <a class="nav-link <?= isActiveParent(['customers']) ?>" href="<?= ADMIN_URL ?>customers/">
                <i class="fas fa-users me-2"></i> Customer
            </a>
        </li>
        
        <!-- Marketing -->
        <li class="nav-item">
            <a class="nav-link <?= isActiveParent(['marketing']) ?>" href="<?= ADMIN_URL ?>marketing/">
                <i class="fas fa-user-tie me-2"></i> Marketing
            </a>
        </li>
        
        <!-- Offers -->
        <li class="nav-item">
            <a class="nav-link <?= isActiveParent(['offers']) ?>" href="<?= ADMIN_URL ?>offers/">
                <i class="fas fa-file-invoice me-2"></i> Penawaran
            </a>
        </li>
        
        <!-- Reports -->
        <li class="nav-item">
            <a class="nav-link <?= isActive('index.php', 'reports') ?>" href="<?= ADMIN_URL ?>reports/">
                <i class="fas fa-chart-bar me-2"></i> Laporan
            </a>
        </li>
        
        <!-- Owner Only -->
        <?php if ($role == 'owner'): ?>
            <!-- Advertising -->
            <li class="nav-item">
                <a class="nav-link <?= isActive('index.php', 'advertising') ?>" href="<?= ADMIN_URL ?>advertising/">
                    <i class="fas fa-ad me-2"></i> Advertising
                </a>
            </li>
            
            <!-- Users -->
            <li class="nav-item">
                <a class="nav-link <?= isActiveParent(['users']) ?>" href="<?= ADMIN_URL ?>users/">
                    <i class="fas fa-user-cog me-2"></i> Users
                </a>
            </li>
            
            <!-- Settings -->
            <li class="nav-item">
                <a class="nav-link <?= isActiveParent(['settings']) ?>" href="#settingsMenu" data-bs-toggle="collapse">
                    <i class="fas fa-cog me-2"></i> Pengaturan
                    <i class="fas fa-chevron-down float-end"></i>
                </a>
                <div class="collapse <?= isActiveParent(['settings']) ? 'show' : '' ?>" id="settingsMenu">
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item">
                            <a class="nav-link <?= isActive('website.php', 'settings') ?>" href="<?= ADMIN_URL ?>settings/website.php">
                                <i class="fas fa-globe me-1"></i> Website
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= isActive('seo.php', 'settings') ?>" href="<?= ADMIN_URL ?>settings/seo.php">
                                <i class="fas fa-search me-1"></i> SEO
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            
            <!-- Activity Logs -->
            <li class="nav-item">
                <a class="nav-link <?= isActive('index.php', 'logs') ?>" href="<?= ADMIN_URL ?>logs/">
                    <i class="fas fa-history me-2"></i> Activity Logs
                </a>
            </li>
        <?php endif; ?>
        
        <!-- Logout -->
        <li class="nav-item mt-3">
            <a class="nav-link text-danger" href="<?= BASE_URL ?>logout.php">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </li>
    </ul>
</nav>
