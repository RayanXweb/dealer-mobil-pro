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

$transactionId = $_POST['transaction_id'] ?? 0;
$action = $_POST['action'] ?? '';

if ($transactionId <= 0 || empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$db = Database::getInstance();

try {
    $db->beginTransaction();
    
    if ($action == 'verify') {
        $status = 'paid';
        $message = 'Pembayaran diverifikasi';
    } elseif ($action == 'reject') {
        $status = 'failed';
        $message = 'Pembayaran ditolak';
    } else {
        throw new Exception('Invalid action');
    }
    
    // Update transaction
    $stmt = $db->prepare("UPDATE transactions SET payment_status = ? WHERE id = ?");
    $stmt->execute([$status, $transactionId]);
    
    // Get order id
    $stmt = $db->prepare("SELECT order_id FROM transactions WHERE id = ?");
    $stmt->execute([$transactionId]);
    $trx = $stmt->fetch();
    
    if ($trx && $status == 'paid') {
        $stmt = $db->prepare("UPDATE orders SET status = 'verified' WHERE id = ?");
        $stmt->execute([$trx['order_id']]);
    }
    
    $db->commit();
    
    logActivity($_SESSION['user_id'], 'verify_payment', 'Transaction #' . $transactionId . ' ' . $message);
    
    echo json_encode(['success' => true, 'message' => $message]);
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("Verify payment error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Operation failed']);
}
?>
