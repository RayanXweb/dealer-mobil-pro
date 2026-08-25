<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$auth->requireRole(['owner', 'supervisor']);

$page_title = 'Laporan';
include '../includes/admin-header.php';

$db = Database::getInstance();

// Filters
$period = $_GET['period'] ?? 'month';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$marketing_id = $_GET['marketing_id'] ?? '';
$brand_id = $_GET['brand_id'] ?? '';

// Get marketing list
$stmt = $db->query("SELECT id, full_name FROM marketing WHERE status = 'active'");
$marketings = $stmt->fetchAll();

// Get brands
$stmt = $db->query("SELECT id, name FROM brands WHERE status = 'active'");
$brands = $stmt->fetchAll();

// Build where clause
$where = "o.status = 'completed'";
$params = [];

if ($date_from && $date_to) {
    $where .= " AND DATE(o.order_date) BETWEEN ? AND ?";
    $params[] = $date_from;
    $params[] = $date_to;
}

if ($marketing_id) {
    $where .= " AND o.marketing_id = ?";
    $params[] = $marketing_id;
}

if ($brand_id) {
    $where .= " AND EXISTS (SELECT 1 FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = o.id AND p.brand_id = ?)";
    $params[] = $brand_id;
}

// Summary stats
$sql = "
    SELECT 
        COUNT(DISTINCT o.id) as total_orders,
        SUM(o.final_amount) as total_revenue,
        COUNT(DISTINCT o.customer_id) as total_customers,
        SUM(oi.quantity) as total_units,
        AVG(o.final_amount) as avg_order_value
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE $where
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$summary = $stmt->fetch();

// Daily sales chart
$sql = "
    SELECT 
        DATE(o.order_date) as date,
        COUNT(*) as count,
        SUM(o.final_amount) as total
    FROM orders o
    WHERE $where
    GROUP BY DATE(o.order_date)
    ORDER BY date
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$dailySales = $stmt->fetchAll();

// Sales by brand
$sql = "
    SELECT 
        b.name as brand_name,
        COUNT(DISTINCT o.id) as orders,
        SUM(oi.quantity) as units,
        SUM(oi.price * oi.quantity) as total
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    JOIN brands b ON p.brand_id = b.id
    WHERE $where
    GROUP BY b.id
    ORDER BY total DESC
    LIMIT 10
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$brandSales = $stmt->fetchAll();

// Sales by marketing
$sql = "
    SELECT 
        m.full_name as marketing_name,
        COUNT(DISTINCT o.id) as orders,
        SUM(o.final_amount) as total
    FROM orders o
    LEFT JOIN marketing m ON o.marketing_id = m.id
    WHERE $where
    GROUP BY o.marketing_id
    ORDER BY total DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$marketingSales = $stmt->fetchAll();

// Payment methods
$sql = "
    SELECT 
        payment_method,
        COUNT(*) as count,
        SUM(final_amount) as total
    FROM orders o
    WHERE $where
    GROUP BY payment_method
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$paymentMethods = $stmt->fetchAll();

// Recent transactions
$sql = "
    SELECT o.order_number, o.final_amount, o.order_date, o.payment_method,
           c.full_name as customer_name, m.full_name as marketing_name
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    LEFT JOIN marketing m ON o.marketing_id = m.id
    WHERE $where
    ORDER BY o.order_date DESC
    LIMIT 20
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$recentOrders = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Laporan Penjualan</h4>
    <div>
        <a href="export-excel.php?<?= $_SERVER['QUERY_STRING'] ?>" class="btn btn-success">
            <i class="fas fa-file-excel me-1"></i> Export Excel
        </a>
        <a href="export-pdf.php?<?= $_SERVER['QUERY_STRING'] ?>" class="btn btn-danger">
            <i class="fas fa-file-pdf me-1"></i> Export PDF
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-2">
                <select name="period" class="form-select">
                    <option value="today" <?= $period == 'today' ? 'selected' : '' ?>>Hari Ini</option>
                    <option value="week" <?= $period == 'week' ? 'selected' : '' ?>>Minggu Ini</option>
                    <option value="month" <?= $period == 'month' ? 'selected' : '' ?>>Bulan Ini</option>
                    <option value="year" <?= $period == 'year' ? 'selected' : '' ?>>Tahun Ini</option>
                    <option value="custom" <?= $period == 'custom' ? 'selected' : '' ?>>Custom</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" value="<?= escape($date_from) ?>">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control" value="<?= escape($date_to) ?>">
            </div>
            <div class="col-md-2">
                <select name="marketing_id" class="form-select">
                    <option value="">Semua Marketing</option>
                    <?php foreach ($marketings as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= $marketing_id == $m['id'] ? 'selected' : '' ?>>
                            <?= escape($m['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="brand_id" class="form-select">
                    <option value="">Semua Brand</option>
                    <?php foreach ($brands as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= $brand_id == $b['id'] ? 'selected' : '' ?>>
                            <?= escape($b['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body">
                <h6 class="card-title">Total Penjualan</h6>
                <h2 class="mb-0"><?= formatCurrency($summary['total_revenue'] ?? 0) ?></h2>
                <small><i class="fas fa-shopping-bag me-1"></i> <?= number_format($summary['total_orders'] ?? 0) ?> pesanan</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-success text-white">
            <div class="card-body">
                <h6 class="card-title">Total Unit Terjual</h6>
                <h2 class="mb-0"><?= number_format($summary['total_units'] ?? 0) ?></h2>
                <small><i class="fas fa-car me-1"></i> Unit terjual</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-info text-white">
            <div class="card-body">
                <h6 class="card-title">Total Customer</h6>
                <h2 class="mb-0"><?= number_format($summary['total_customers'] ?? 0) ?></h2>
                <small><i class="fas fa-users me-1"></i> Customer unik</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card bg-warning text-white">
            <div class="card-body">
                <h6 class="card-title">Rata-rata Order</h6>
                <h2 class="mb-0"><?= formatCurrency($summary['avg_order_value'] ?? 0) ?></h2>
                <small><i class="fas fa-chart-line me-1"></i> Per pesanan</small>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Penjualan Harian</h6>
            </div>
            <div class="card-body">
                <canvas id="dailySalesChart" height="250"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Metode Pembayaran</h6>
            </div>
            <div class="card-body">
                <canvas id="paymentChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Penjualan per Brand</h6>
            </div>
            <div class="card-body">
                <canvas id="brandChart" height="250"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Penjualan per Marketing</h6>
            </div>
            <div class="card-body">
                <canvas id="marketingChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="card">
    <div class="card-header">
        <h6 class="mb-0">Transaksi Terbaru</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Marketing</th>
                        <th>Metode</th>
                        <th>Total</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td><?= escape($order['order_number']) ?></td>
                            <td><?= escape($order['customer_name']) ?></td>
                            <td><?= escape($order['marketing_name'] ?? '-') ?></td>
                            <td><?= ucfirst($order['payment_method']) ?></td>
                            <td><?= formatCurrency($order['final_amount']) ?></td>
                            <td><?= formatDate($order['order_date']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Daily Sales Chart
const ctx1 = document.getElementById('dailySalesChart').getContext('2d');
new Chart(ctx1, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($dailySales, 'date')) ?>,
        datasets: [{
            label: 'Total Penjualan',
            data: <?= json_encode(array_column($dailySales, 'total')) ?>,
            backgroundColor: 'rgba(13, 110, 253, 0.5)',
            borderColor: '#0d6efd',
            borderWidth: 1
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

// Payment Chart
const ctx2 = document.getElementById('paymentChart').getContext('2d');
new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($paymentMethods, 'payment_method')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($paymentMethods, 'total')) ?>,
            backgroundColor: ['#0d6efd', '#198754', '#ffc107']
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

// Brand Chart
const ctx3 = document.getElementById('brandChart').getContext('2d');
new Chart(ctx3, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($brandSales, 'brand_name')) ?>,
        datasets: [{
            label: 'Penjualan',
            data: <?= json_encode(array_column($brandSales, 'total')) ?>,
            backgroundColor: 'rgba(25, 135, 84, 0.5)',
            borderColor: '#198754',
            borderWidth: 1
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

// Marketing Chart
const ctx4 = document.getElementById('marketingChart').getContext('2d');
new Chart(ctx4, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($marketingSales, 'marketing_name')) ?>,
        datasets: [{
            label: 'Penjualan',
            data: <?= json_encode(array_column($marketingSales, 'total')) ?>,
            backgroundColor: 'rgba(255, 193, 7, 0.5)',
            borderColor: '#ffc107',
            borderWidth: 1
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
</script>

<?php include '../includes/admin-footer.php'; ?>
