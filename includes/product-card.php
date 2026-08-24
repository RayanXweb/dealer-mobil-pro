<?php
$priceInfo = getProductPrice($product);
$primaryImage = $product['primary_image'] ?? '';
$imagePath = !empty($primaryImage) ? UPLOADS_URL . 'products/' . $primaryImage : ASSETS_URL . 'images/no-image.jpg';
?>

<div class="product-card card h-100 shadow-sm border-0 overflow-hidden">
    <div class="position-relative">
        <img src="<?= $imagePath ?>" alt="<?= escape($product['model']) ?>" 
             class="card-img-top product-image" style="height: 200px; object-fit: cover;">
        
        <?php if ($product['is_promo'] && $product['promo_price'] > 0): ?>
            <span class="badge bg-danger position-absolute top-0 end-0 m-2 px-3 py-2">
                <i class="fas fa-tag me-1"></i> PROMO
            </span>
        <?php endif; ?>
        
        <?php if ($product['is_featured']): ?>
            <span class="badge bg-primary position-absolute top-0 start-0 m-2 px-3 py-2">
                <i class="fas fa-star me-1"></i> UNGGULAN
            </span>
        <?php endif; ?>
    </div>
    
    <div class="card-body">
        <h6 class="text-muted small mb-1"><?= escape($product['brand_name']) ?></h6>
        <h5 class="card-title mb-2"><?= escape($product['model']) ?></h5>
        <?php if (!empty($product['variant'])): ?>
            <small class="text-muted"><?= escape($product['variant']) ?></small>
        <?php endif; ?>
        
        <div class="d-flex justify-content-between align-items-center mt-2">
            <div>
                <?php if ($priceInfo['discount'] > 0): ?>
                    <span class="text-muted text-decoration-line-through small">
                        <?= formatCurrency($priceInfo['original']) ?>
                    </span><br>
                    <span class="text-danger fw-bold fs-5">
                        <?= formatCurrency($priceInfo['final']) ?>
                    </span>
                    <span class="badge bg-success ms-2">-<?= $priceInfo['discount_percent'] ?>%</span>
                <?php else: ?>
                    <span class="fw-bold fs-5"><?= formatCurrency($priceInfo['final']) ?></span>
                <?php endif; ?>
            </div>
            <span class="badge bg-<?= getStatusBadge($product['status']) ?>">
                <?= getStatusLabel($product['status']) ?>
            </span>
        </div>
        
        <div class="d-flex gap-3 mt-3 text-muted small">
            <span><i class="fas fa-calendar me-1"></i> <?= $product['year'] ?></span>
            <span><i class="fas fa-road me-1"></i> <?= number_format($product['mileage'] ?? 0) ?> km</span>
            <span><i class="fas fa-cog me-1"></i> <?= $product['transmission'] ?></span>
        </div>
    </div>
    
    <div class="card-footer bg-transparent border-0 pt-0 pb-3">
        <a href="product-detail.php?id=<?= $product['id'] ?>" class="btn btn-outline-primary w-100">
            <i class="fas fa-eye me-1"></i> Detail
        </a>
    </div>
</div>

<style>
.product-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;
}
.product-card .card-img-top {
    transition: transform 0.5s ease;
}
.product-card:hover .card-img-top {
    transform: scale(1.05);
}
</style>
