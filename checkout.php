<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

$auth->requireLogin();

$page_title = 'Checkout';

$db = Database::getInstance();

// Get cart items
$stmt = $db->prepare("
    SELECT c.id as cart_id, ci.*, p.model, p.brand_id, p.price as original_price, p.promo_price, p.is_promo,
           b.name as brand_name,
           (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM carts c
    JOIN cart_items ci ON c.id = ci.cart_id
    JOIN products p ON ci.product_id = p.id
    JOIN brands b ON p.brand_id = b.id
    WHERE c.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    header('Location: ' . BASE_URL . 'cart.php');
    exit;
}

// Calculate total
$total = 0;
foreach ($cartItems as &$item) {
    $priceInfo = getProductPrice($item);
    $item['price'] = $priceInfo['final'];
    $item['subtotal'] = $item['price'] * $item['quantity'];
    $total += $item['subtotal'];
}

// Get customer data
$stmt = $db->prepare("SELECT * FROM customers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$customer = $stmt->fetch();

// Get bank accounts from settings
$stmt = $db->prepare("SELECT setting_key, setting_value FROM website_settings WHERE setting_key LIKE 'bank_%'");
$stmt->execute();
$banks = [];
while ($row = $stmt->fetch()) {
    $banks[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    
    $orderData = [
        'customer_id' => $customer['id'],
        'payment_method' => $_POST['payment_method'] ?? 'cash',
        'down_payment' => $_POST['down_payment'] ?? 0,
        'installment_tenor' => $_POST['tenor'] ?? 12,
        'notes' => $_POST['notes'] ?? '',
        'shipping_address' => $_POST['address'] ?? $customer['address'] ?? ''
    ];
    
    try {
        $db->beginTransaction();
        
        // Create order
        $orderNumber = generateOrderNumber();
        $stmt = $db->prepare("
            INSERT INTO orders (order_number, customer_id, total_amount, discount, final_amount, 
                               payment_method, down_payment, installment_tenor, status, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
        ");
        $stmt->execute([
            $orderNumber,
            $customer['id'],
            $total,
            0,
            $total,
            $orderData['payment_method'],
            $orderData['down_payment'],
            $orderData['installment_tenor'],
            $orderData['notes']
        ]);
        
        $orderId = $db->lastInsertId();
        
        // Add order items
        foreach ($cartItems as $item) {
            $stmt = $db->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
        }
        
        // Clear cart
        $stmt = $db->prepare("DELETE FROM cart_items WHERE cart_id = ?");
        $stmt->execute([$cartItems[0]['cart_id']]);
        
        // Create transaction
        if ($orderData['payment_method'] === 'transfer') {
            $transactionNumber = generateTransactionNumber();
            $stmt = $db->prepare("
                INSERT INTO transactions (transaction_number, order_id, customer_id, amount, 
                                         payment_method, payment_status)
                VALUES (?, ?, ?, ?, 'transfer', 'pending')
            ");
            $stmt->execute([
                $transactionNumber,
                $orderId,
                $customer['id'],
                $total
            ]);
        }
        
        $db->commit();
        
        // Send notification
        sendNotification($_SESSION['user_id'], 'Order Created', 
                        'Pesanan Anda ' . $orderNumber . ' telah dibuat', 'success');
        
        $_SESSION['last_order'] = $orderNumber;
        header('Location: ' . BASE_URL . 'order-detail.php?order=' . $orderNumber);
        exit;
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Checkout error: " . $e->getMessage());
        $error = 'Checkout failed. Please try again.';
    }
}

include 'includes/header.php';
?>

<div class="container py-4">
    <h1 class="mb-4">Checkout</h1>
    
    <div class="row">
        <div class="col-lg-8">
            <form method="POST" id="checkoutForm">
                <?= csrfField() ?>
                
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Informasi Pemesan</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" value="<?= escape($customer['full_name']) ?>" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?= escape($customer['email']) ?>" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">WhatsApp</label>
                                <input type="text" class="form-control" value="<?= escape($customer['phone']) ?>" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Alamat</label>
                                <input type="text" name="address" class="form-control" 
                                       value="<?= escape($_POST['address'] ?? $customer['address'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-control" rows="2"><?= escape($_POST['notes'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Metode Pembayaran</h5>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" 
                                       id="payment_cash" value="cash" checked>
                                <label class="form-check-label" for="payment_cash">
                                    Tunai
                                </label>
                            </div>
                            
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" 
                                       id="payment_credit" value="credit">
                                <label class="form-check-label" for="payment_credit">
                                    Kredit
                                </label>
                            </div>
                            
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" 
                                       id="payment_transfer" value="transfer">
                                <label class="form-check-label" for="payment_transfer">
                                    Transfer Bank
                                </label>
                            </div>
                        </div>
                        
                        <div id="creditFields" style="display: none;">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">DP</label>
                                    <input type="number" name="down_payment" class="form-control" 
                                           min="0" step="100000" value="0">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Tenor (Bulan)</label>
                                    <select name="tenor" class="form-select">
                                        <option value="12">12 Bulan</option>
                                        <option value="24">24 Bulan</option>
                                        <option value="36">36 Bulan</option>
                                        <option value="48">48 Bulan</option>
                                        <option value="60">60 Bulan</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div id="transferFields" style="display: none;">
                            <h6>Rekening Bank</h6>
                            <?php if (empty($banks)): ?>
                                <p class="text-muted">Belum ada rekening bank yang terdaftar</p>
                            <?php else: ?>
                                <ul class="list-unstyled">
                                    <?php foreach ($banks as $bank): ?>
                                        <li><strong><?= str_replace('bank_', '', $bank['setting_key']) ?>:</strong> <?= escape($bank['setting_value']) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Ringkasan Pesanan</h5>
                    <hr>
                    
                    <?php foreach ($cartItems as $item): ?>
                        <div class="d-flex justify-content-between small mb-2">
                            <span><?= escape($item['brand_name']) ?> <?= escape($item['model']) ?> x<?= $item['quantity'] ?></span>
                            <span><?= formatCurrency($item['subtotal']) ?></span>
                        </div>
                    <?php endforeach; ?>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <strong>Total</strong>
                        <strong><?= formatCurrency($total) ?></strong>
                    </div>
                    
                    <button type="submit" form="checkoutForm" class="btn btn-primary w-100 mt-3">
                        <i class="fas fa-check me-1"></i> Buat Pesanan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('input[name="payment_method"]').on('change', function() {
        var value = $(this).val();
        $('#creditFields').hide();
        $('#transferFields').hide();
        
        if (value === 'credit') {
            $('#creditFields').show();
        } else if (value === 'transfer') {
            $('#transferFields').show();
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
