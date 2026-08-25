<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

$auth->requireRole(['owner', 'supervisor']);

if (!isAjaxRequest()) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

validateCSRF();

$orderId = $_POST['order_id'] ?? 0;
$status = $_POST['status'] ?? '';
$notes = $_POST['notes'] ?? '';

if ($orderId <= 0 || empty($status)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$db = Database::getInstance();

try {
    $stmt = $db->prepare("
        UPDATE orders SET status = ?, notes = CONCAT(notes, '\n', ?) WHERE id = ?
    ");
    $stmt->execute([$status, $notes, $orderId]);
    
    logActivity($_SESSION['user_id'], 'update_order_status', 'Updated order #' . $orderId . ' to ' . $status);
    
    echo json_encode(['success' => true, 'message' => 'Status updated']);
    
} catch (Exception $e) {
    error_log("Update status error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Update failed']);
}
?>
