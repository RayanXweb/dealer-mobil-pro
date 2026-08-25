<?php
// Admin specific functions

function logActivity($userId, $action, $description = '') {
    $db = Database::getInstance();
    $stmt = $db->prepare("
        INSERT INTO activity_logs (user_id, username, action, description, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $username = $_SESSION['user_name'] ?? 'Unknown';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    return $stmt->execute([$userId, $username, $action, $description, $ip, $userAgent]);
}

function getAdminStats() {
    $db = Database::getInstance();
    $stats = [];
    
    // Total products
    $stmt = $db->query("SELECT COUNT(*) as count FROM products WHERE status = 'available'");
    $stats['products'] = $stmt->fetch()['count'];
    
    // Total orders
    $stmt = $db->query("SELECT COUNT(*) as count FROM orders");
    $stats['orders'] = $stmt->fetch()['count'];
    
    // Pending orders
    $stmt = $db->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
    $stats['pending_orders'] = $stmt->fetch()['count'];
    
    // Total customers
    $stmt = $db->query("SELECT COUNT(*) as count FROM customers");
    $stats['customers'] = $stmt->fetch()['count'];
    
    // Today's revenue
    $stmt = $db->query("SELECT SUM(final_amount) as total FROM orders WHERE DATE(order_date) = CURDATE() AND status = 'completed'");
    $stats['today_revenue'] = $stmt->fetch()['total'] ?? 0;
    
    // Total revenue
    $stmt = $db->query("SELECT SUM(final_amount) as total FROM orders WHERE status = 'completed'");
    $stats['total_revenue'] = $stmt->fetch()['total'] ?? 0;
    
    // Unread notifications
    $stats['unread_notifications'] = getUnreadNotificationCount($_SESSION['user_id']);
    
    return $stats;
}

function getRecentActivity($limit = 10) {
    $db = Database::getInstance();
    $stmt = $db->prepare("
        SELECT * FROM activity_logs 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function getDashboardCharts() {
    $db = Database::getInstance();
    
    // Monthly sales
    $stmt = $db->prepare("
        SELECT DATE_FORMAT(order_date, '%Y-%m') as month, 
               COUNT(*) as count, 
               SUM(final_amount) as total
        FROM orders 
        WHERE status = 'completed' AND order_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY month
        ORDER BY month
    ");
    $stmt->execute();
    $monthlySales = $stmt->fetchAll();
    
    // Sales by brand
    $stmt = $db->prepare("
        SELECT b.name, COUNT(oi.id) as count, SUM(oi.price * oi.quantity) as total
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        JOIN products p ON oi.product_id = p.id
        JOIN brands b ON p.brand_id = b.id
        WHERE o.status = 'completed'
        GROUP BY b.id
        ORDER BY total DESC
        LIMIT 5
    ");
    $stmt->execute();
    $brandSales = $stmt->fetchAll();
    
    return [
        'monthly' => $monthlySales,
        'brands' => $brandSales
    ];
}

function getStatusColor($status) {
    $colors = [
        'pending' => 'warning',
        'processing' => 'info',
        'waiting_payment' => 'warning',
        'verified' => 'success',
        'completed' => 'success',
        'cancelled' => 'danger',
        'available' => 'success',
        'sold' => 'danger',
        'reserved' => 'warning',
        'inactive' => 'secondary',
        'active' => 'success',
        'suspended' => 'danger'
    ];
    return $colors[$status] ?? 'secondary';
}
