<?php
// error_log.php - Temporary file to check PHP errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Checking PHP Configuration</h1>";

// Check PHP version
echo "<p>PHP Version: " . phpversion() . "</p>";

// Check if config files exist
$files = [
    'config/config.php',
    'config/database.php',
    'includes/auth.php',
    'includes/functions.php',
    'includes/security.php'
];

echo "<h2>Checking Required Files:</h2>";
foreach ($files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<p style='color:green'>✅ $file - OK</p>";
    } else {
        echo "<p style='color:red'>❌ $file - MISSING</p>";
    }
}

// Check database connection
echo "<h2>Checking Database:</h2>";
try {
    require_once 'config/config.php';
    $db = Database::getInstance();
    $stmt = $db->query("SELECT 1");
    echo "<p style='color:green'>✅ Database connection - OK</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Database error: " . $e->getMessage() . "</p>";
}

// Check .htaccess
echo "<h2>Checking .htaccess:</h2>";
if (file_exists(__DIR__ . '/.htaccess')) {
    echo "<p style='color:green'>✅ .htaccess exists</p>";
    // Temporarily rename .htaccess to test
    echo "<p>💡 Try renaming .htaccess to .htaccess.bak to test if it's causing the error</p>";
} else {
    echo "<p style='color:orange'>⚠️ .htaccess not found</p>";
}

// Check uploads directory
echo "<h2>Checking Uploads:</h2>";
$dirs = ['uploads', 'uploads/products', 'uploads/brands', 'uploads/users', 'uploads/payments', 'uploads/settings'];
foreach ($dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        echo "<p style='color:green'>✅ $dir - OK</p>";
    } else {
        echo "<p style='color:orange'>⚠️ $dir - Creating...</p>";
        mkdir($path, 0777, true);
        echo "<p style='color:green'>✅ $dir - Created</p>";
    }
}

echo "<h2>PHP Extensions:</h2>";
$extensions = ['pdo', 'pdo_mysql', 'mysqli', 'gd', 'zip', 'json'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p style='color:green'>✅ $ext - Loaded</p>";
    } else {
        echo "<p style='color:red'>❌ $ext - NOT Loaded</p>";
    }
}

echo "<h2>Next Steps:</h2>";
echo "<ol>
    <li>Check config/.env file exists and has correct database credentials</li>
    <li>Try renaming .htaccess to .htaccess.bak</li>
    <li>Check file permissions (should be 644 for files, 755 for directories)</li>
    <li>Check PHP error log at: " . ini_get('error_log') . "</li>
</ol>";
