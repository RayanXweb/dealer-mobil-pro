<?php
// Installation script
$installed = false;

// Check if already installed
if (file_exists(__DIR__ . '/config/.env') && file_exists(__DIR__ . '/config/installed')) {
    die('AutoDealer is already installed. Please delete config/installed to reinstall.');
}

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = $_POST['step'] ?? 1;
    
    switch ($step) {
        case 2:
            // Database configuration
            $dbHost = $_POST['db_host'] ?? 'localhost';
            $dbName = $_POST['db_name'] ?? 'autodealer';
            $dbUser = $_POST['db_user'] ?? '';
            $dbPass = $_POST['db_pass'] ?? '';
            
            if (empty($dbUser) || empty($dbName)) {
                $error = 'Database name and user are required';
            } else {
                try {
                    $dsn = "mysql:host=$dbHost;charset=utf8mb4";
                    $pdo = new PDO($dsn, $dbUser, $dbPass);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Create database if not exists
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName`");
                    
                    // Save .env file
                    $envContent = "DB_HOST=$dbHost\nDB_NAME=$dbName\nDB_USER=$dbUser\nDB_PASS=$dbPass\nSALT=" . bin2hex(random_bytes(32));
                    file_put_contents(__DIR__ . '/config/.env', $envContent);
                    
                    $success = 'Database configuration saved';
                    $step = 3;
                    
                } catch (PDOException $e) {
                    $error = 'Database connection failed: ' . $e->getMessage();
                }
            }
            break;
            
        case 3:
            // Import database
            try {
                $db = Database::getInstance();
                
                // Import SQL
                $sql = file_get_contents(__DIR__ . '/database.sql');
                $db->getConnection()->exec($sql);
                
                // Create admin user
                $adminEmail = $_POST['admin_email'] ?? 'admin@autodealer.com';
                $adminPassword = $_POST['admin_password'] ?? 'Admin@123';
                $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("
                    INSERT INTO users (email, password, first_name, role, status) 
                    VALUES (?, ?, 'Administrator', 'owner', 'active')
                ");
                $stmt->execute([$adminEmail, $hashedPassword]);
                
                // Update website settings
                $settings = [
                    'website_name' => $_POST['website_name'] ?? 'AutoDealer',
                    'website_email' => $adminEmail,
                    'website_phone' => $_POST['website_phone'] ?? '+6281234567890',
                    'website_whatsapp' => $_POST['website_whatsapp'] ?? '+6281234567890'
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $db->prepare("UPDATE website_settings SET setting_value = ? WHERE setting_key = ?");
                    $stmt->execute([$value, $key]);
                }
                
                // Mark as installed
                file_put_contents(__DIR__ . '/config/installed', date('Y-m-d H:i:s'));
                
                $success = 'Installation completed!';
                $step = 4;
                
            } catch (Exception $e) {
                $error = 'Installation failed: ' . $e->getMessage();
            }
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoDealer - Installation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-car me-2"></i> AutoDealer Installation</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>
                        
                        <?php if ($step == 1): ?>
                            <!-- Step 1: Welcome -->
                            <h5>Welcome to AutoDealer Installation</h5>
                            <p>This installer will guide you through the installation process.</p>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Please ensure your server meets the requirements:
                                <ul class="mt-2 mb-0">
                                    <li>PHP 8.2 or higher</li>
                                    <li>MySQL 8.0 or higher</li>
                                    <li>PDO MySQL extension enabled</li>
                                </ul>
                            </div>
                            
                            <form method="POST">
                                <input type="hidden" name="step" value="2">
                                <button type="submit" class="btn btn-primary">Next: Database Configuration</button>
                            </form>
                            
                        <?php elseif ($step == 2): ?>
                            <!-- Step 2: Database Configuration -->
                            <h5>Database Configuration</h5>
                            <p>Enter your MySQL database credentials.</p>
                            
                            <form method="POST">
                                <input type="hidden" name="step" value="2">
                                
                                <div class="mb-3">
                                    <label class="form-label">Database Host</label>
                                    <input type="text" name="db_host" class="form-control" value="localhost" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Database Name</label>
                                    <input type="text" name="db_name" class="form-control" value="autodealer" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Database Username</label>
                                    <input type="text" name="db_user" class="form-control" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Database Password</label>
                                    <input type="password" name="db_pass" class="form-control">
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Next: Install</button>
                            </form>
                            
                        <?php elseif ($step == 3): ?>
                            <!-- Step 3: Admin Configuration -->
                            <h5>Admin Configuration</h5>
                            <p>Create the administrator account.</p>
                            
                            <form method="POST">
                                <input type="hidden" name="step" value="3">
                                
                                <div class="mb-3">
                                    <label class="form-label">Website Name</label>
                                    <input type="text" name="website_name" class="form-control" value="AutoDealer" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Admin Email</label>
                                    <input type="email" name="admin_email" class="form-control" value="admin@autodealer.com" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Admin Password</label>
                                    <input type="password" name="admin_password" class="form-control" value="Admin@123" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Website Phone</label>
                                    <input type="text" name="website_phone" class="form-control" value="+6281234567890">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">WhatsApp Number</label>
                                    <input type="text" name="website_whatsapp" class="form-control" value="+6281234567890">
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Install Now</button>
                            </form>
                            
                        <?php elseif ($step == 4): ?>
                            <!-- Step 4: Completed -->
                            <div class="text-center">
                                <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                                <h4 class="mt-3">Installation Complete!</h4>
                                <p>AutoDealer has been successfully installed.</p>
                                
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    For security, please delete the <strong>install.php</strong> file.
                                </div>
                                
                                <div class="d-flex gap-3 justify-content-center mt-4">
                                    <a href="/" class="btn btn-primary">Go to Website</a>
                                    <a href="/admin" class="btn btn-success">Go to Admin</a>
                                </div>
                                
                                <div class="mt-4">
                                    <p class="text-muted small">
                                        Admin Login: <?= htmlspecialchars($_POST['admin_email'] ?? 'admin@autodealer.com') ?><br>
                                        Password: <?= htmlspecialchars($_POST['admin_password'] ?? 'Admin@123') ?>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
