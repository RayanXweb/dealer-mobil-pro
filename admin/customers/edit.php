<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$auth->requireRole(['owner', 'supervisor']);

$page_title = 'Edit Customer';
include '../includes/admin-header.php';

$db = Database::getInstance();

$id = $_GET['id'] ?? 0;
if ($id <= 0) {
    setFlash('danger', 'ID customer tidak valid');
    redirect(ADMIN_URL . 'customers/');
}

$stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    setFlash('danger', 'Customer tidak ditemukan');
    redirect(ADMIN_URL . 'customers/');
}

// Get marketing list
$stmt = $db->query("SELECT id, full_name FROM marketing WHERE status = 'active'");
$marketings = $stmt->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $marketingId = $_POST['marketing_id'] ?? null;
    
    if (empty($fullName) || empty($email)) {
        $error = 'Nama dan email wajib diisi';
    } else {
        try {
            $stmt = $db->prepare("
                UPDATE customers SET 
                    full_name = ?, phone = ?, email = ?, address = ?, 
                    status = ?, marketing_id = ? 
                WHERE id = ?
            ");
            $stmt->execute([$fullName, $phone, $email, $address, $status, $marketingId, $id]);
            
            logActivity($_SESSION['user_id'], 'update_customer', 'Updated customer: ' . $fullName);
            setFlash('success', 'Customer berhasil diperbarui');
            redirect(ADMIN_URL . 'customers/detail.php?id=' . $id);
            
        } catch (Exception $e) {
            error_log("Update customer error: " . $e->getMessage());
            $error = 'Gagal memperbarui customer';
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Edit Customer: <?= escape($customer['full_name']) ?></h5>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= escape($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <?= csrfField() ?>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nama Lengkap *</label>
                    <input type="text" name="full_name" class="form-control" 
                           value="<?= escape($customer['full_name']) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?= escape($customer['email']) ?>" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">WhatsApp</label>
                    <input type="text" name="phone" class="form-control" 
                           value="<?= escape($customer['phone']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Assign Marketing</label>
                    <select name="marketing_id" class="form-select">
                        <option value="">Pilih Marketing</option>
                        <?php foreach ($marketings as $m): ?>
                            <option value="<?= $m['id'] ?>" <?= $customer['marketing_id'] == $m['id'] ? 'selected' : '' ?>>
                                <?= escape($m['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Alamat</label>
                <textarea name="address" class="form-control" rows="3"><?= escape($customer['address']) ?></textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" <?= $customer['status'] == 'active' ? 'selected' : '' ?>>Aktif</option>
                    <option value="inactive" <?= $customer['status'] == 'inactive' ? 'selected' : '' ?>>Tidak Aktif</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Update Customer
            </button>
            <a href="detail.php?id=<?= $id ?>" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</div>

<?php include '../includes/admin-footer.php'; ?>
