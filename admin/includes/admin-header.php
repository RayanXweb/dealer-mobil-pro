<?php
// Admin Header
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

// Check if user is logged in and has admin role
if (!$auth->isLoggedIn() || !in_array($auth->getCurrentUser()['role'], ['owner', 'supervisor', 'marketing'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$user = $auth->getCurrentUser();
$unreadNotifications = getUnreadNotificationCount($_SESSION['user_id']);
$notifications = getNotifications($_SESSION['user_id'], 5);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escape($page_title ?? 'Dashboard') ?> - AutoDealer Admin</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/admin.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/responsive.css?v=<?= time() ?>">
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= ADMIN_URL ?>">
                <i class="fas fa-car me-2"></i>
                <span class="fw-bold">AutoDealer</span>
                <span class="badge bg-primary ms-2">Admin</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav ms-auto align-items-center">
                    <!-- Notifications -->
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-bell fa-lg"></i>
                            <?php if ($unreadNotifications > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $unreadNotifications ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" style="min-width: 300px;">
                            <li><h6 class="dropdown-header">Notifikasi</h6></li>
                            <?php if (empty($notifications)): ?>
                                <li><span class="dropdown-item text-muted">Tidak ada notifikasi</span></li>
                            <?php else: ?>
                                <?php foreach ($notifications as $notif): ?>
                                    <li>
                                        <a class="dropdown-item <?= $notif['is_read'] ? '' : 'fw-bold' ?>" 
                                           href="<?= $notif['link'] ?: '#' ?>">
                                            <div class="d-flex justify-content-between">
                                                <span><?= escape($notif['title']) ?></span>
                                                <small class="text-muted"><?= timeAgo($notif['created_at']) ?></small>
                                            </div>
                                            <small class="text-muted"><?= escape($notif['message']) ?></small>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center" href="<?= ADMIN_URL ?>notifications/">
                                <i class="fas fa-eye me-1"></i> Lihat Semua
                            </a></li>
                        </ul>
                    </li>
                    
                    <!-- User Menu -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle fa-lg me-1"></i>
                            <?= escape($user['first_name'] ?? $user['username'] ?? 'User') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="<?= ADMIN_URL ?>profile.php">
                                    <i class="fas fa-user me-2"></i> Profil
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= ADMIN_URL ?>settings/">
                                    <i class="fas fa-cog me-2"></i> Pengaturan
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
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Sidebar + Content Wrapper -->
    <div class="admin-wrapper">
        <?php include 'admin-sidebar.php'; ?>
        
        <div class="admin-content">
            <div class="container-fluid py-4">
                <?php displayFlash(); ?>
