<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$auth->requireRole(['owner', 'supervisor', 'marketing']);

$page_title = 'Buat Penawaran';
include '../includes/admin-header.php';

$db = Database::getInstance();

// Get products
$stmt = $db->prepare("
    SELECT p.*, b.name as brand_name 
    FROM products p 
    JOIN brands b ON p.brand_id = b.id 
    WHERE p.status = 'available'
    ORDER BY b.name, p.model
");
$stmt->execute();
$products = $stmt->fetchAll();

// Get customers
$stmt = $db->query("SELECT id, full_name, email, phone FROM customers WHERE status = 'active' ORDER BY full_name");
$customers = $stmt->fetchAll();

// Get marketing list (for assignment)
$stmt = $db->query("SELECT id, full_name FROM marketing WHERE status = 'active'");
$marketings = $stmt->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    
    $customerId = $_POST['customer_id'] ?? 0;
    $productId = $_POST['product_id'] ?? 0;
    $marketingId = $_POST['marketing_id'] ?? null;
    $price = str_replace(['.', ','], '', $_POST['price'] ?? 0);
    $discount = str_replace(['.', ','], '', $_POST['discount'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    $validUntil = $_POST['valid_until'] ?? date('Y-m-d', strtotime('+7 days'));
    
    if (empty($customerId) || empty($productId) || empty($price)) {
        $error = 'Customer, produk, dan harga wajib diisi';
    } else {
        $finalPrice = $price - $discount;
        $offerNumber = generateOfferNumber();
        
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("
                INSERT INTO offers (
                    offer_number, customer_id, product_id, marketing_id,
                    price, discount, final_price, notes, status, valid_until
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?)
            ");
            $stmt->execute([
                $offerNumber,
                $customerId,
                $productId,
                $marketingId,
                $price,
                $discount,
                $finalPrice,
                $notes,
                $validUntil
            ]);
            
            $db->commit();
            
            logActivity($_SESSION['user_id'], 'create_offer', 'Created offer: ' . $offerNumber);
            setFlash('success', 'Penawaran berhasil dibuat: ' . $offerNumber);
            redirect(ADMIN_URL . 'offers/detail.php?id=' . $db->lastInsertId());
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Create offer error: " . $e->getMessage());
            $error = 'Gagal membuat penawaran';
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Buat Penawaran Baru</h5>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= escape($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <?= csrfField() ?>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Customer *</label>
                    <select name="customer_id" class="form-select" required>
                        <option value="">Pilih Customer</option>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?= $customer['id'] ?>">
                                <?= escape($customer['full_name']) ?> (<?= escape($customer['email']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Mobil *</label>
                    <select name="product_id" class="form-select" required>
                        <option value="">Pilih Mobil</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?= $product['id'] ?>" data-price="<?= $product['price'] ?>">
                                <?= escape($product['brand_name']) ?> <?= escape($product['model']) ?> 
                                (<?= $product['year'] ?>) - <?= formatCurrency($product['price']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Assign Marketing</label>
                    <select name="marketing_id" class="form-select">
                        <option value="">Pilih Marketing</option>
                        <?php foreach ($marketings as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= escape($m['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Berlaku Sampai</label>
                    <input type="date" name="valid_until" class="form-control" 
                           value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Harga Penawaran *</label>
                    <input type="text" name="price" class="form-control currency" 
                           id="offerPrice" required>
                    <small class="text-muted">Harga yang ditawarkan ke customer</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Diskon</label>
                    <input type="text" name="discount" class="form-control currency" 
                           id="discount" value="0" onchange="calculateTotal()">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-info">
                        <strong>Total Penawaran:</strong> 
                        <span id="totalDisplay" class="fs-4">Rp 0</span>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Catatan</label>
                <textarea name="notes" class="form-control" rows="3"></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Buat Penawaran
            </button>
            <a href="index.php" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Auto-fill price from product
    $('select[name="product_id"]').on('change', function() {
        var price = $(this).find('option:selected').data('price');
        if (price) {
            $('#offerPrice').val(parseInt(price).toLocaleString('id-ID'));
            calculateTotal();
        }
    });
    
    // Currency formatting
    $('.currency').on('input', function() {
        var value = $(this).val().replace(/[^0-9]/g, '');
        if (value) {
            $(this).val(parseInt(value).toLocaleString('id-ID'));
        }
        calculateTotal();
    });
});

function calculateTotal() {
    var price = parseInt($('#offerPrice').val().replace(/[^0-9]/g, '')) || 0;
    var discount = parseInt($('#discount').val().replace(/[^0-9]/g, '')) || 0;
    var total = price - discount;
    
    $('#totalDisplay').text('Rp ' + total.toLocaleString('id-ID'));
    
    // Also set a hidden input for form submission
    if (!$('#totalInput').length) {
        $('form').append('<input type="hidden" id="totalInput" name="total" value="' + total + '">');
    } else {
        $('#totalInput').val(total);
    }
}
</script>

<?php include '../includes/admin-footer.php'; ?>
