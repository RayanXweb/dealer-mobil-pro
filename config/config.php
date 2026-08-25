<?php
// Configuration file
session_start();

// Error reporting - enable for development
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Define base path
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/autodealer/');
define('ADMIN_URL', BASE_URL . 'admin/');
define('ASSETS_URL', BASE_URL . 'assets/');
define('UPLOADS_URL', BASE_URL . 'uploads/');
define('UPLOADS_PATH', BASE_PATH . '/uploads/');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Load environment variables
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'autodealer');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Security
define('SALT', getenv('SALT') ?: 'default_salt_change_this');
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_TIMEOUT', 3600);

// Upload limits
define('MAX_FILE_SIZE', 5 * 1024 * 1024);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Generate CSRF token if not exists
if (empty($_SESSION[CSRF_TOKEN_NAME])) {
    $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
}

// Auto-include required files
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/security.php';
?>
