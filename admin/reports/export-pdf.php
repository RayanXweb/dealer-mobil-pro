<?php
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$auth->requireRole(['owner', 'supervisor']);

// Get filters
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$marketing_id = $_GET['marketing_id'] ?? '';
$brand_id = $_GET['brand_id'] ?? '';

$db = Database::getInstance();

// Build query
$where = "o.status = 'completed' AND DATE(o.order_date) BETWEEN ? AND ?";
$params = [$date_from, $date_to];

if ($marketing_id) {
    $where .= " AND o.marketing_id = ?";
    $params[] = $marketing_id;
}

if ($brand_id) {
    $where .= " AND EXISTS (SELECT 1 FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = o.id AND p.brand_id = ?)";
    $params[] = $brand_id;
}

// Get data
$sql = "
    SELECT 
        o.order_number,
        DATE(o.order_date) as order_date,
        c.full_name as customer_name,
        c.phone as customer_phone,
        m.full_name as marketing_name,
        b.name as brand_name,
        p.model as product_model,
        oi.quantity,
        oi.price as unit_price,
        (oi.price * oi.quantity) as subtotal,
        o.discount,
        o.final_amount as total,
        o.payment_method
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    JOIN brands b ON p.brand_id = b.id
    LEFT JOIN marketing m ON o.marketing_id = m.id
    WHERE $where
    ORDER BY o.order_date DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();

// Get summary
$summary = [
    'total_orders' => 0,
    'total_revenue' => 0,
    'total_units' => 0
];

foreach ($data as $row) {
    $summary['total_orders']++;
    $summary['total_revenue'] += $row['total'];
    $summary['total_units'] += $row['quantity'];
}

// Get website settings
$settings = getWebsiteSettings();
$websiteName = $settings['website_name'] ?? 'AutoDealer';

// Generate PDF using Dompdf
require_once '../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isHtml5ParserEnabled', true);
$dompdf = new Dompdf($options);

// Build HTML content
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { text-align: center; border-bottom: 2px solid #1a2332; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #1a2332; }
        .header h3 { margin: 0; color: #666; font-weight: normal; }
        .summary { margin-bottom: 20px; }
        .summary table { width: 100%; }
        .summary td { padding: 5px 10px; }
        .summary .label { font-weight: bold; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data th { background: #1a2332; color: #fff; padding: 8px; text-align: left; }
        table.data td { padding: 6px 8px; border-bottom: 1px solid #ddd; }
        table.data tr:nth-child(even) { background: #f9f9f9; }
        .footer { text-align: center; margin-top: 30px; color: #999; font-size: 10px; border-top: 1px solid #ddd; padding-top: 10px; }
        .total-row { font-weight: bold; background: #e9ecef !important; }
    </style>
</head>
<body>
    <div class="header">
        <h1>' . $websiteName . '</h1>
        <h3>Laporan Penjualan</h3>
        <p>Periode: ' . date('d/m/Y', strtotime($date_from)) . ' - ' . date('d/m/Y', strtotime($date_to)) . '</p>
    </div>
    
    <div class="summary">
        <table>
            <tr>
                <td class="label">Total Pesanan:</td>
                <td>' . number_format($summary['total_orders']) . '</td>
                <td class="label">Total Unit:</td>
                <td>' . number_format($summary['total_units']) . '</td>
                <td class="label">Total Revenue:</td>
                <td>Rp ' . number_format($summary['total_revenue'], 0, ',', '.') . '</td>
            </tr>
        </table>
    </div>
    
    <table class="data">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Tanggal</th>
                <th>Customer</th>
                <th>Marketing</th>
                <th>Brand</th>
                <th>Model</th>
                <th>Qty</th>
                <th>Harga</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>';

if (empty($data)) {
    $html .= '<tr><td colspan="9" style="text-align: center;">Tidak ada data</td></tr>';
} else {
    foreach ($data as $item) {
        $html .= '
            <tr>
                <td>' . $item['order_number'] . '</td>
                <td>' . date('d/m/Y', strtotime($item['order_date'])) . '</td>
                <td>' . $item['customer_name'] . '</td>
                <td>' . ($item['marketing_name'] ?? '-') . '</td>
                <td>' . $item['brand_name'] . '</td>
                <td>' . $item['product_model'] . '</td>
                <td style="text-align: center;">' . $item['quantity'] . '</td>
                <td style="text-align: right;">Rp ' . number_format($item['unit_price'], 0, ',', '.') . '</td>
                <td style="text-align: right;">Rp ' . number_format($item['total'], 0, ',', '.') . '</td>
            </tr>';
    }
}

$html .= '
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="6" style="text-align: right;">Total:</td>
                <td style="text-align: center;">' . number_format($summary['total_units']) . '</td>
                <td></td>
                <td style="text-align: right;">Rp ' . number_format($summary['total_revenue'], 0, ',', '.') . '</td>
            </tr>
        </tfoot>
    </table>
    
    <div class="footer">
        Dicetak pada: ' . date('d/m/Y H:i:s') . '<br>
        ' . $websiteName . ' - All Rights Reserved
    </div>
</body>
</html>';

// Load HTML
$dompdf->loadHtml($html);

// Set paper size
$dompdf->setPaper('A4', 'landscape');

// Render PDF
$dompdf->render();

// Set filename
$filename = 'Laporan_Penjualan_' . date('Ymd_His') . '.pdf';

// Output PDF
$dompdf->stream($filename, ['Attachment' => true]);
exit;
