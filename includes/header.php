<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escape(getSetting('website_name', 'AutoDealer')) ?> - <?= $page_title ?? 'Premium Car Dealer' ?></title>
    
    <!-- Meta -->
    <meta name="description" content="<?= escape(getSetting('meta_description', 'Find your dream car at AutoDealer')) ?>">
    <meta name="keywords" content="<?= escape(getSetting('meta_keywords', 'car dealer, premium cars, auto dealer')) ?>">
    <meta name="robots" content="<?= escape(getSetting('robots', 'index, follow')) ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= escape(getSetting('website_name', 'AutoDealer')) ?>">
    <meta property="og:description" content="<?= escape(getSetting('meta_description', 'Find your dream car at AutoDealer')) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= BASE_URL ?>">
    
    <!-- Favicon -->
    <link rel="icon" href="<?= !empty(getSetting('favicon')) ? UPLOADS_URL . 'settings/' . getSetting('favicon') : ASSETS_URL . 'images/favicon.ico' ?>">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- AOS Animation -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/frontend.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>css/responsive.css?v=<?= time() ?>">
    
    <!-- Advertising Scripts -->
    <?php
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT script_code FROM advertisements WHERE position = 'head' AND is_active = 1");
    $stmt->execute();
    $headScripts = $stmt->fetchAll();
    foreach ($headScripts as $script) {
        echo $script['script_code'] . "\n";
    }
    ?>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <?php
    // Display flash messages
    displayFlash();
    ?>
    
    <main>
