<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$auth->requireRole(['owner', 'supervisor', 'marketing']);

$page_title = 'Detail Penawaran';
include '../includes/admin-header.php';

$db = Database::getInstance();

$id = $_GET['id'] ?? 0;
if ($id <= 0) {
    setFlash('danger', 'ID penawaran tidak valid');
    redirect(ADMIN_URL . 'offers/');
}

// Get offer
$stmt = $db->prepare("
    SELECT o.*, c.full_name as customer_name, c.phone, c.email, c.address,
           p.model, p.variant, p.year, b.name as brand_name,
           m.full_name as marketing_name,
           (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM offers o
    JOIN customers c ON o.customer_id = c.id
    JOIN products p ON o.product_id = p.id
    JOIN brands b ON p.brand_id = b.id
    LEFT JOIN marketing m ON o.marketing_id = m.id
    WHERE o.id = ?
");
$stmt->execute([$id]);
$offer = $stmt->fetch();

if (!$offer) {
    setFlash('danger', 'Penawaran tidak ditemukan');
    redirect(ADMIN_URL . 'offers/');
}

// Get marketing list
$stmt = $db->query("SELECT id, full_name FROM marketing WHERE status = 'active'");
$marketings = $stmt->fetchAll();

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    
    $status = $_POST['status'] ?? '';
    $marketingId = $_POST['marketing_id'] ?? null;
    $notes = trim($_POST['notes'] ?? '');
    
    if ($status) {
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("
                UPDATE offers SET status = ?, notes = ?, marketing_id = ? WHERE id = ?
            ");
            $stmt->execute([$status, $notes, $marketingId, $id]);
            
            // If accepted, create order
            if ($status == 'accepted') {
                // Create order
                $orderNumber = generateOrderNumber();
                $stmt = $db->prepare("
                    INSERT INTO orders (order_number, customer_id, marketing_id, total_amount, final_amount, status, notes)
                    VALUES (?, ?, ?, ?, ?, 'pending', ?)
                ");
                $stmt->execute([
                    $orderNumber,
                    $offer['customer_id'],
                    $marketingId,
                    $offer['price'],
                    $offer['final_price'],
                    'Order from offer: ' . $offer['offer_number']
                ]);
                $orderId = $db->lastInsertId();
                
                // Add order item
                $stmt = $db->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price)
                    VALUES (?, ?, 1, ?)
                ");
                $stmt->execute([$orderId, $offer['product_id'], $offer['final_price']]);
                
                // Send notification
                sendNotification($offer['customer_id'], 'Penawaran Diterima', 
                                'Penawaran ' . $offer['offer_number'] . ' telah diterima. Pesanan ' . $orderNumber . ' telah dibuat.',
                                'success', BASE_URL . 'order-detail.php?order=' . $orderNumber);
            }
            
            $db->commit();
            
            logActivity($_SESSION['user_id'], 'update_offer', 
                       'Updated offer ' . $offer['offer_number'] . ' to ' . $status);
            setFlash('success', 'Penawaran berhasil diperbarui');
            redirect(ADMIN_URL . 'offers/detail.php?id=' . $id);
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Update offer error: " . $e->getMessage());
            setFlash('danger', 'Gagal memperbarui penawaran');
        }
    }
}

// WhatsApp message
$whatsappMessage = "Halo " . $offer['customer_name'] . ",\n\n";
$whatsappMessage .= "Berikut adalah penawaran dari AutoDealer:\n\n";
$whatsappMessage .= "Mobil: " . $offer['brand_name'] . " " . $offer['model'] . " (" . $offer['year'] . ")\n";
$whatsappMessage .= "Harga: " . formatCurrency($offer['price']) . "\n";
$whatsappMessage .= "Diskon: " . formatCurrency($offer['discount']) . "\n";
$whatsappMessage .= "Total: " . formatCurrency($offer['final_price']) . "\n\n";
$whatsappMessage .= "Penawaran berlaku sampai: " . formatDateOnly($offer['valid_until']) . "\n\n";
$whatsappMessage .= "Untuk informasi lebih lanjut, silakan hubungi marketing kami.\n\n";
$whatsappMessage .= "Terima kasih,\nAutoDealer";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Detail Penawaran #<?= escape($offer['offer_number']) ?></h4>
    <div>
        <a href="<?= getWhatsAppLink($offer['phone'], $whatsappMessage) ?>" 
           class="btn btn-success" target="_blank">
            <i class="fab fa-whatsapp me-1"></i> Kirim ke WhatsApp
        </a>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <!-- Offer Status -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Update Status</h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="draft" <?= $offer['status'] == 'draft' ? 'selected' : '' ?>>Draf</option>
                                <option value="sent" <?= $offer['status'] == 'sent' ? 'selected' : '' ?>>Dikirim</option>
                                <option value="accepted" <?= $offer['status'] == 'accepted' ? 'selected' : '' ?>>Diterima</option>
                                <option value="rejected" <?= $offer['status'] == 'rejected' ? 'selected' : '' ?>>Ditolak</option>
                                <option value="expired" <?= $offer['status'] == 'expired' ? 'selected' : '' ?>>Kadaluarsa</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Assign Marketing</label>
                            <select name="marketing_id" class="form-select">
                                <option value="">Pilih Marketing</option>
                                <?php foreach ($marketings as $m): ?>
                                    <option value="<?= $m['id'] ?>" <?= $offer['marketing_id'] == $m['id'] ? 'selected' : '' ?>>
                                        <?= escape($m['full_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save me-1"></i> Update
                            </button>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="2"><?= escape($offer['notes']) ?></textarea>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Offer Details -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Detail Penawaran</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-muted">Nomor Penawaran</small>
                        <p class="fw-bold"><?= escape($offer['offer_number']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">Tanggal</small>
                        <p class="fw-bold"><?= formatDate($offer['created_at']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">Mobil</small>
                        <p class="fw-bold"><?= escape($offer['brand_name']) ?> <?= escape($offer['model']) ?></p>
                        <p class="text-muted"><?= $offer['year'] ?> <?= !empty($offer['variant']) ? '| ' . escape($offer['variant']) : '' ?></p>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">Berlaku Sampai</small>
                        <p class="fw-bold"><?= formatDateOnly($offer['valid_until']) ?></p>
                    </div>
                </div>
                
                <hr>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-muted">Harga Penawaran</small>
                        <p class="fw-bold"><?= formatCurrency($offer['price']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">Diskon</small>
                        <p class="fw-bold text-success">- <?= formatCurrency($offer['discount']) ?></p>
                    </div>
                    <div class="col-md-12">
                        <div class="border-top pt-3">
                            <small class="text-muted">Total Penawaran</small>
                            <h3 class="text-primary"><?= formatCurrency($offer['final_price']) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Customer Info -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Informasi Customer</h6>
            </div>
            <div class="card-body">
                <p><strong>Nama:</strong> <?= escape($offer['customer_name']) ?></p>
                <p><strong>Email:</strong> <?= escape($offer['email']) ?></p>
                <p><strong>WhatsApp:</strong> <?= escape($offer['phone']) ?></p>
                <?php if (!empty($offer['address'])): ?>
                    <p><strong>Alamat:</strong> <?= nl2br(escape($offer['address'])) ?></p>
                <?php endif; ?>
                <a href="<?= ADMIN_URL ?>customers/detail.php?id=<?= $offer['customer_id'] ?>" 
                   class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-user me-1"></i> Lihat Customer
                </a>
            </div>
        </div>
        
        <!-- Marketing Info -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Marketing</h6>
            </div>
            <div class="card-body">
                <?php if ($offer['marketing_id']): ?>
                    <p><strong>Nama:</strong> <?= escape($offer['marketing_name']) ?></p>
                    <a href="<?= ADMIN_URL ?>marketing/dashboard.php?id=<?= $offer['marketing_id'] ?>" 
                       class="btn btn-sm btn-outline-info">
                        <i class="fas fa-chart-bar me-1"></i> Dashboard
                    </a>
                <?php else: ?>
                    <p class="text-muted">Belum diassign</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/admin-footer.php'; ?>
