<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$auth->requireRole(['owner', 'supervisor']);

$page_title = 'Dashboard Marketing';
include '../includes/admin-header.php';

$db = Database::getInstance();

$id = $_GET['id'] ?? 0;
if ($id <= 0) {
    setFlash('danger', 'ID marketing tidak valid');
    redirect(ADMIN_URL . 'marketing/');
}

// Get marketing data
$stmt = $db->prepare("SELECT * FROM marketing WHERE id = ?");
$stmt->execute([$id]);
$marketing = $stmt->fetch();

if (!$marketing) {
    setFlash('danger', 'Marketing tidak ditemukan');
    redirect(ADMIN_URL . 'marketing/');
}

// Get statistics
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_customers,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_customers
    FROM customers WHERE marketing_id = ?
");
$stmt->execute([$id]);
$customerStats = $stmt->fetch();

$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
        SUM(CASE WHEN status = 'completed' THEN final_amount ELSE 0 END) as total_revenue
    FROM orders WHERE marketing_id = ?
");
$stmt->execute([$id]);
$orderStats = $stmt->fetch();

$stmt = $db->prepare("
    SELECT COUNT(*) as total_offers,
           SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_offers,
           SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_offers
    FROM offers WHERE marketing_id = ?
");
$stmt->execute([$id]);
$offerStats = $stmt->fetch();

// Monthly sales chart
$stmt = $db->prepare("
    SELECT DATE_FORMAT(order_date, '%Y-%m') as month, 
           COUNT(*) as count, 
           SUM(final_amount) as total
    FROM orders 
    WHERE marketing_id = ? AND status = 'completed' 
          AND order_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month
");
$stmt->execute([$id]);
$monthlySales = $stmt->fetchAll();

// Recent customers
$stmt = $db->prepare("
    SELECT * FROM customers 
    WHERE marketing_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$id]);
$recentCustomers = $stmt->fetchAll();

// Recent orders
$stmt = $db->prepare("
    SELECT o.*, c.full_name as customer_name 
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    WHERE o.marketing_id = ? 
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$stmt->execute([$id]);
$recentOrders = $stmt->fetchAll();

// Achievement rate
$achievementRate = 0;
if ($marketing['target_sales'] > 0) {
    $achievementRate = ($orderStats['total_revenue'] / $marketing['target_sales']) * 100;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Dashboard Marketing: <?= escape($marketing['full_name']) ?></h4>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i> Kembali
    </a>
</div>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body">
                <h6 class="card-title">Total Customer</h6>
                <h2 class="mb-0"><?= number_format($customerStats['total_customers'] ?? 0) ?></h2>
                <small>Aktif: <?= number_format($customerStats['active_customers'] ?? 0) ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-success text-white">
            <div class="card-body">
                <h6 class="card-title">Total Penjualan</h6>
                <h2 class="mb-0"><?= formatCurrency($orderStats['total_revenue'] ?? 0) ?></h2>
                <small><?= number_format($orderStats['completed_orders'] ?? 0) ?> pesanan selesai</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-info text-white">
            <div class="card-body">
                <h6 class="card-title">Penawaran</h6>
                <h2 class="mb-0"><?= number_format($offerStats['total_offers'] ?? 0) ?></h2>
                <small>Diterima: <?= number_format($offerStats['accepted_offers'] ?? 0) ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-warning text-white">
            <div class="card-body">
                <h6 class="card-title">Target Pencapaian</h6>
                <h2 class="mb-0"><?= number_format($achievementRate, 1) ?>%</h2>
                <small>Target: <?= formatCurrency($marketing['target_sales'] ?? 0) ?></small>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Penjualan Bulanan</h6>
            </div>
            <div class="card-body">
                <canvas id="monthlySalesChart" height="250"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Status Pesanan</h6>
            </div>
            <div class="card-body">
                <canvas id="orderStatusChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Data -->
<div class="row g-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Customer Terbaru</h6>
                <a href="<?= ADMIN_URL ?>customers/?marketing=<?= $id ?>" class="btn btn-sm btn-primary">
                    Lihat Semua
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($recentCustomers)): ?>
                    <p class="text-muted">Belum ada customer</p>
                <?php else: ?>
                    <?php foreach ($recentCustomers as $customer): ?>
                        <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                            <div>
                                <strong><?= escape($customer['full_name']) ?></strong>
                                <br>
                                <small class="text-muted"><?= escape($customer['email']) ?></small>
                            </div>
                            <div class="text-end">
                                <small class="text-muted"><?= formatDateOnly($customer['created_at']) ?></small>
                                <br>
                                <span class="badge bg-<?= $customer['status'] == 'active' ? 'success' : 'danger' ?>">
                                    <?= ucfirst($customer['status']) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Pesanan Terbaru</h6>
                <a href="<?= ADMIN_URL ?>orders/?marketing=<?= $id ?>" class="btn btn-sm btn-primary">
                    Lihat Semua
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($recentOrders)): ?>
                    <p class="text-muted">Belum ada pesanan</p>
                <?php else: ?>
                    <?php foreach ($recentOrders as $order): ?>
                        <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                            <div>
                                <strong><?= escape($order['order_number']) ?></strong>
                                <br>
                                <small class="text-muted"><?= escape($order['customer_name']) ?></small>
                            </div>
                            <div class="text-end">
                                <span class="fw-bold"><?= formatCurrency($order['final_amount']) ?></span>
                                <br>
                                <span class="badge bg-<?= getStatusColor($order['status']) ?>">
                                    <?= getStatusLabel($order['status']) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Monthly Sales Chart
const ctx1 = document.getElementById('monthlySalesChart').getContext('2d');
new Chart(ctx1, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($monthlySales, 'month')) ?>,
        datasets: [{
            label: 'Total Penjualan',
            data: <?= json_encode(array_column($monthlySales, 'total')) ?>,
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13, 110, 253, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'Rp ' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// Order Status Chart
const ctx2 = document.getElementById('orderStatusChart').getContext('2d');
new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: ['Pending', 'Selesai', 'Dibatalkan'],
        datasets: [{
            data: [
                <?= $orderStats['pending_orders'] ?? 0 ?>,
                <?= $orderStats['completed_orders'] ?? 0 ?>,
                <?= $orderStats['cancelled_orders'] ?? 0 ?>
            ],
            backgroundColor: ['#ffc107', '#198754', '#dc3545']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>

<?php include '../includes/admin-footer.php'; ?>
