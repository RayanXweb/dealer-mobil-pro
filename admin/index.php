<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

$auth->requireRole(['owner', 'supervisor', 'marketing']);

$page_title = 'Dashboard';
include 'includes/admin-header.php';

$db = Database::getInstance();

// Statistics
$stats = [];

// Total products
$stmt = $db->query("SELECT COUNT(*) as total FROM products WHERE status = 'available'");
$stats['products'] = $stmt->fetch()['total'];

// Total orders
$stmt = $db->query("SELECT COUNT(*) as total FROM orders");
$stats['orders'] = $stmt->fetch()['total'];

// Pending orders
$stmt = $db->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
$stats['pending_orders'] = $stmt->fetch()['total'];

// Total customers
$stmt = $db->query("SELECT COUNT(*) as total FROM customers");
$stats['customers'] = $stmt->fetch()['total'];

// Total revenue
$stmt = $db->query("SELECT SUM(final_amount) as total FROM orders WHERE status = 'completed'");
$stats['revenue'] = $stmt->fetch()['total'] ?? 0;

// Monthly sales chart
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

// Recent orders
$stmt = $db->prepare("
    SELECT o.*, c.full_name as customer_name
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    ORDER BY o.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recentOrders = $stmt->fetchAll();
?>

<div class="row">
    <!-- Stats Cards -->
    <div class="col-md-3">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body">
                <h6 class="card-title">Total Mobil</h6>
                <h2 class="mb-0"><?= number_format($stats['products']) ?></h2>
                <small><i class="fas fa-car me-1"></i> Mobil tersedia</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stat-card bg-success text-white">
            <div class="card-body">
                <h6 class="card-title">Total Pesanan</h6>
                <h2 class="mb-0"><?= number_format($stats['orders']) ?></h2>
                <small><i class="fas fa-shopping-bag me-1"></i> <?= number_format($stats['pending_orders']) ?> pending</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stat-card bg-info text-white">
            <div class="card-body">
                <h6 class="card-title">Total Customer</h6>
                <h2 class="mb-0"><?= number_format($stats['customers']) ?></h2>
                <small><i class="fas fa-users me-1"></i> Customer terdaftar</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stat-card bg-warning text-white">
            <div class="card-body">
                <h6 class="card-title">Total Penjualan</h6>
                <h2 class="mb-0"><?= formatCurrency($stats['revenue']) ?></h2>
                <small><i class="fas fa-money-bill me-1"></i> Omzet keseluruhan</small>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row mt-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Penjualan Bulanan</h5>
            </div>
            <div class="card-body">
                <canvas id="monthlySalesChart" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Penjualan per Brand</h5>
            </div>
            <div class="card-body">
                <canvas id="brandSalesChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Pesanan Terbaru</h5>
                <a href="orders/" class="btn btn-sm btn-primary">Lihat Semua</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td><?= escape($order['order_number']) ?></td>
                                    <td><?= escape($order['customer_name']) ?></td>
                                    <td><?= formatCurrency($order['final_amount']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= getStatusBadge($order['status']) ?>">
                                            <?= getStatusLabel($order['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= formatDate($order['created_at']) ?></td>
                                    <td>
                                        <a href="orders/detail.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($recentOrders)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">Belum ada pesanan</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
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
            legend: {
                display: false
            }
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

// Brand Sales Chart
const ctx2 = document.getElementById('brandSalesChart').getContext('2d');
new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($brandSales, 'name')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($brandSales, 'count')) ?>,
            backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1']
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

<?php include 'includes/admin-footer.php'; ?>
