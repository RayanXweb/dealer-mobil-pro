<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

$page_title = 'Keranjang Belanja';

// Get cart items
$cartItems = [];
$total = 0;

if ($auth->isLoggedIn()) {
    $db = Database::getInstance();
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
} elseif (isset($_SESSION['cart_id'])) {
    $db = Database::getInstance();
    $stmt = $db->prepare("
        SELECT ci.*, p.model, p.brand_id, p.price as original_price, p.promo_price, p.is_promo,
               b.name as brand_name,
               (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        JOIN brands b ON p.brand_id = b.id
        WHERE ci.cart_id = ?
    ");
    $stmt->execute([$_SESSION['cart_id']]);
    $cartItems = $stmt->fetchAll();
}

// Calculate totals
foreach ($cartItems as &$item) {
    $priceInfo = getProductPrice($item);
    $item['price'] = $priceInfo['final'];
    $item['subtotal'] = $item['price'] * $item['quantity'];
    $total += $item['subtotal'];
}

include 'includes/header.php';
?>

<div class="container py-4">
    <h1 class="mb-4">Keranjang Belanja</h1>
    
    <?php if (empty($cartItems)): ?>
        <div class="text-center py-5">
            <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
            <h4>Keranjang kosong</h4>
            <p class="text-muted">Belum ada mobil yang ditambahkan ke keranjang</p>
            <a href="products.php" class="btn btn-primary">
                <i class="fas fa-car me-1"></i> Lihat Mobil
            </a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-8">
                <?php foreach ($cartItems as $item): ?>
                    <div class="card mb-3 cart-item" data-item-id="<?= $item['id'] ?>">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <?php
                                    $imagePath = !empty($item['primary_image']) ? 
                                        UPLOADS_URL . 'products/' . $item['primary_image'] : 
                                        ASSETS_URL . 'images/no-image.jpg';
                                    ?>
                                    <img src="<?= $imagePath ?>" alt="<?= escape($item['model']) ?>" 
                                         class="img-fluid rounded" style="max-height: 80px;">
                                </div>
                                <div class="col-md-4">
                                    <h6 class="mb-0"><?= escape($item['brand_name']) ?></h6>
                                    <h5 class="mb-0"><?= escape($item['model']) ?></h5>
                                    <small class="text-muted"><?= formatCurrency($item['price']) ?></small>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group">
                                        <button class="btn btn-outline-secondary btn-sm quantity-decrease">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number" class="form-control form-control-sm text-center quantity-input" 
                                               value="<?= $item['quantity'] ?>" min="1" max="10">
                                        <button class="btn btn-outline-secondary btn-sm quantity-increase">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-2 text-end">
                                    <h6><?= formatCurrency($item['subtotal']) ?></h6>
                                </div>
                                <div class="col-md-1 text-end">
                                    <button class="btn btn-danger btn-sm remove-item" data-id="<?= $item['id'] ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Ringkasan Belanja</h5>
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span><?= formatCurrency($total) ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Diskon</span>
                            <span class="text-success">- <?= formatCurrency(0) ?></span>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total</strong>
                            <strong><?= formatCurrency($total) ?></strong>
                        </div>
                        
                        <a href="checkout.php" class="btn btn-primary w-100">
                            <i class="fas fa-shopping-bag me-1"></i> Checkout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    // Update quantity
    $('.quantity-input').on('change', function() {
        var itemId = $(this).closest('.cart-item').data('item-id');
        var quantity = $(this).val();
        updateCartItem(itemId, quantity);
    });
    
    // Increase quantity
    $('.quantity-increase').on('click', function() {
        var input = $(this).closest('.input-group').find('.quantity-input');
        var value = parseInt(input.val()) + 1;
        input.val(value).trigger('change');
    });
    
    // Decrease quantity
    $('.quantity-decrease').on('click', function() {
        var input = $(this).closest('.input-group').find('.quantity-input');
        var value = parseInt(input.val()) - 1;
        if (value >= 1) {
            input.val(value).trigger('change');
        }
    });
    
    // Remove item
    $('.remove-item').on('click', function() {
        var itemId = $(this).data('id');
        if (confirm('Hapus item dari keranjang?')) {
            $.ajax({
                url: 'ajax/cart.php',
                method: 'POST',
                data: {
                    action: 'remove',
                    item_id: itemId,
                    csrf_token: '<?= generateCSRFToken() ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.message || 'Gagal menghapus item');
                    }
                }
            });
        }
    });
});

function updateCartItem(itemId, quantity) {
    $.ajax({
        url: 'ajax/cart.php',
        method: 'POST',
        data: {
            action: 'update',
            item_id: itemId,
            quantity: quantity,
            csrf_token: '<?= generateCSRFToken() ?>'
        },
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.message || 'Gagal update keranjang');
            }
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>
