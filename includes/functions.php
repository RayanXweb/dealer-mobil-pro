<?php
// Helper functions

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function escape($input) {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

function generateSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

function generateOrderNumber() {
    return 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function generateOfferNumber() {
    return 'OFF-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function generateTransactionNumber() {
    return 'TRX-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function formatCurrency($amount) {
    $settings = getWebsiteSettings();
    $currency = $settings['currency'] ?? 'Rp';
    return $currency . ' ' . number_format($amount, 0, ',', '.');
}

function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}

function formatDateOnly($date) {
    return date('d/m/Y', strtotime($date));
}

function timeAgo($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) return $diff . ' detik yang lalu';
    if ($diff < 3600) return floor($diff / 60) . ' menit yang lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam yang lalu';
    if ($diff < 604800) return floor($diff / 86400) . ' hari yang lalu';
    if ($diff < 2592000) return floor($diff / 604800) . ' minggu yang lalu';
    if ($diff < 31536000) return floor($diff / 2592000) . ' bulan yang lalu';
    return floor($diff / 31536000) . ' tahun yang lalu';
}

function getStatusBadge($status) {
    $badges = [
        'available' => 'success',
        'sold' => 'danger',
        'reserved' => 'warning',
        'inactive' => 'secondary',
        'pending' => 'warning',
        'processing' => 'info',
        'waiting_payment' => 'warning',
        'verified' => 'success',
        'completed' => 'success',
        'cancelled' => 'danger',
        'active' => 'success',
        'inactive' => 'secondary',
        'suspended' => 'danger',
        'draft' => 'secondary',
        'sent' => 'info',
        'accepted' => 'success',
        'rejected' => 'danger',
        'expired' => 'warning'
    ];
    
    return $badges[$status] ?? 'secondary';
}

function getStatusLabel($status) {
    $labels = [
        'available' => 'Tersedia',
        'sold' => 'Terjual',
        'reserved' => 'Dipesan',
        'inactive' => 'Tidak Aktif',
        'pending' => 'Menunggu',
        'processing' => 'Diproses',
        'waiting_payment' => 'Menunggu Pembayaran',
        'verified' => 'Diverifikasi',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
        'active' => 'Aktif',
        'inactive' => 'Tidak Aktif',
        'suspended' => 'Ditangguhkan',
        'draft' => 'Draf',
        'sent' => 'Dikirim',
        'accepted' => 'Diterima',
        'rejected' => 'Ditolak',
        'expired' => 'Kadaluarsa'
    ];
    
    return $labels[$status] ?? $status;
}

function uploadFile($file, $targetDir, $allowedTypes = null, $maxSize = null) {
    if ($allowedTypes === null) {
        $allowedTypes = ALLOWED_EXTENSIONS;
    }
    if ($maxSize === null) {
        $maxSize = MAX_FILE_SIZE;
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload failed'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File too large'];
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ALLOWED_MIME_TYPES)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    $filename = uniqid() . '.' . $extension;
    $targetPath = $targetDir . '/' . $filename;
    
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $filename, 'path' => $targetPath];
    }
    
    return ['success' => false, 'message' => 'Failed to move file'];
}

function getWebsiteSettings() {
    static $settings = null;
    
    if ($settings === null) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT setting_key, setting_value FROM website_settings");
        $stmt->execute();
        $rows = $stmt->fetchAll();
        
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    return $settings;
}

function getSetting($key, $default = '') {
    $settings = getWebsiteSettings();
    return $settings[$key] ?? $default;
}

function getWhatsAppLink($phone, $message = '') {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strpos($phone, '0') === 0) {
        $phone = '62' . substr($phone, 1);
    }
    if (strpos($phone, '+') !== 0) {
        $phone = '+' . $phone;
    }
    $message = urlencode($message);
    return "https://wa.me/{$phone}?text={$message}";
}

function getProductPrice($product) {
    if (!empty($product['promo_price']) && $product['promo_price'] > 0 && $product['is_promo']) {
        return [
            'original' => $product['price'],
            'final' => $product['promo_price'],
            'discount' => $product['price'] - $product['promo_price'],
            'discount_percent' => round((($product['price'] - $product['promo_price']) / $product['price']) * 100)
        ];
    }
    return [
        'original' => $product['price'],
        'final' => $product['price'],
        'discount' => 0,
        'discount_percent' => 0
    ];
}

function getProducts($filters = [], $limit = 12, $offset = 0) {
    $db = Database::getInstance();
    $params = [];
    $where = ['p.status = "available"'];
    
    if (!empty($filters['keyword'])) {
        $where[] = '(p.model LIKE ? OR p.variant LIKE ? OR b.name LIKE ?)';
        $keyword = '%' . $filters['keyword'] . '%';
        $params = array_merge($params, [$keyword, $keyword, $keyword]);
    }
    
    if (!empty($filters['brand'])) {
        $where[] = 'p.brand_id = ?';
        $params[] = $filters['brand'];
    }
    
    if (!empty($filters['min_price'])) {
        $where[] = 'p.price >= ?';
        $params[] = $filters['min_price'];
    }
    
    if (!empty($filters['max_price'])) {
        $where[] = 'p.price <= ?';
        $params[] = $filters['max_price'];
    }
    
    if (!empty($filters['transmission'])) {
        $where[] = 'p.transmission = ?';
        $params[] = $filters['transmission'];
    }
    
    if (!empty($filters['fuel_type'])) {
        $where[] = 'p.fuel_type = ?';
        $params[] = $filters['fuel_type'];
    }
    
    if (!empty($filters['condition'])) {
        $where[] = 'p.condition = ?';
        $params[] = $filters['condition'];
    }
    
    if (!empty($filters['year'])) {
        $where[] = 'p.year = ?';
        $params[] = $filters['year'];
    }
    
    if (isset($filters['is_promo']) && $filters['is_promo'] === '1') {
        $where[] = 'p.is_promo = 1';
    }
    
    if (isset($filters['is_featured']) && $filters['is_featured'] === '1') {
        $where[] = 'p.is_featured = 1';
    }
    
    $whereClause = implode(' AND ', $where);
    
    $sql = "SELECT p.*, b.name as brand_name, b.slug as brand_slug,
            (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
            FROM products p
            JOIN brands b ON p.brand_id = b.id
            WHERE {$whereClause}
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll();
}

function getProductCount($filters = []) {
    $db = Database::getInstance();
    $params = [];
    $where = ['p.status = "available"'];
    
    // Same filters as getProducts but without limit/offset
    
    $whereClause = implode(' AND ', $where);
    $sql = "SELECT COUNT(*) as total FROM products p JOIN brands b ON p.brand_id = b.id WHERE {$whereClause}";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    
    return $result['total'] ?? 0;
}

function sendNotification($userId, $title, $message, $type = 'info', $link = '') {
    $db = Database::getInstance();
    $stmt = $db->prepare("
        INSERT INTO notifications (user_id, title, message, type, link) 
        VALUES (?, ?, ?, ?, ?)
    ");
    return $stmt->execute([$userId, $title, $message, $type, $link]);
}

function getNotifications($userId, $limit = 10) {
    $db = Database::getInstance();
    $stmt = $db->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll();
}

function getUnreadNotificationCount($userId) {
    $db = Database::getInstance();
    $stmt = $db->prepare("
        SELECT COUNT(*) as count FROM notifications 
        WHERE user_id = ? AND is_read = 0
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return $result['count'] ?? 0;
}
?>
