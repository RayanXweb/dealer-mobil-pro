<?php
// Application Constants

// Define base paths
define('BASE_PATH', dirname(__DIR__));
define('UPLOADS_PATH', BASE_PATH . '/uploads/');

// File upload limits
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
define('ALLOWED_MIME_TYPES', [
    'image/jpeg', 
    'image/png', 
    'image/gif', 
    'image/webp', 
    'image/svg+xml'
]);

// Pagination
define('PRODUCTS_PER_PAGE', 12);
define('ADMIN_PER_PAGE', 25);

// Order status
define('ORDER_STATUSES', [
    'pending' => 'Menunggu',
    'processing' => 'Diproses',
    'waiting_payment' => 'Menunggu Pembayaran',
    'verified' => 'Diverifikasi',
    'completed' => 'Selesai',
    'cancelled' => 'Dibatalkan'
]);

// Payment status
define('PAYMENT_STATUSES', [
    'pending' => 'Pending',
    'paid' => 'Paid',
    'partial' => 'Partial',
    'failed' => 'Failed',
    'refunded' => 'Refunded',
    'completed' => 'Completed'
]);

// Product status
define('PRODUCT_STATUSES', [
    'available' => 'Tersedia',
    'sold' => 'Terjual',
    'reserved' => 'Dipesan',
    'inactive' => 'Tidak Aktif'
]);

// User roles
define('USER_ROLES', [
    'owner' => 'Owner',
    'supervisor' => 'Supervisor',
    'marketing' => 'Marketing',
    'customer' => 'Customer'
]);

// Cache settings
define('CACHE_ENABLED', false);
define('CACHE_DURATION', 3600); // 1 hour
