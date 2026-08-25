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
        c.email as customer_email,
        m.full_name as marketing_name,
        b.name as brand_name,
        p.model as product_model,
        oi.quantity,
        oi.price as unit_price,
        (oi.price * oi.quantity) as subtotal,
        o.discount,
        o.final_amount as total,
        o.payment_method,
        o.status
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

// Create Excel using PhpSpreadsheet
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set title
$sheet->setCellValue('A1', 'LAPORAN PENJUALAN');
$sheet->setCellValue('A2', 'Periode: ' . date('d/m/Y', strtotime($date_from)) . ' - ' . date('d/m/Y', strtotime($date_to)));
$sheet->mergeCells('A1:L1');
$sheet->mergeCells('A2:L2');

// Summary
$sheet->setCellValue('A4', 'Total Pesanan:');
$sheet->setCellValue('B4', $summary['total_orders']);
$sheet->setCellValue('D4', 'Total Unit:');
$sheet->setCellValue('E4', $summary['total_units']);
$sheet->setCellValue('G4', 'Total Revenue:');
$sheet->setCellValue('H4', $summary['total_revenue']);

// Headers
$headers = [
    'A6' => 'Order ID',
    'B6' => 'Tanggal',
    'C6' => 'Customer',
    'D6' => 'WhatsApp',
    'E6' => 'Email',
    'F6' => 'Marketing',
    'G6' => 'Brand',
    'H6' => 'Model',
    'I6' => 'Qty',
    'J6' => 'Harga/Unit',
    'K6' => 'Subtotal',
    'L6' => 'Total'
];

foreach ($headers as $cell => $value) {
    $sheet->setCellValue($cell, $value);
}

// Style headers
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A2332']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
];
$sheet->getStyle('A6:L6')->applyFromArray($headerStyle);

// Data
$row = 7;
foreach ($data as $item) {
    $sheet->setCellValue('A' . $row, $item['order_number']);
    $sheet->setCellValue('B' . $row, date('d/m/Y', strtotime($item['order_date'])));
    $sheet->setCellValue('C' . $row, $item['customer_name']);
    $sheet->setCellValue('D' . $row, $item['customer_phone']);
    $sheet->setCellValue('E' . $row, $item['customer_email']);
    $sheet->setCellValue('F' . $row, $item['marketing_name'] ?? '-');
    $sheet->setCellValue('G' . $row, $item['brand_name']);
    $sheet->setCellValue('H' . $row, $item['product_model']);
    $sheet->setCellValue('I' . $row, $item['quantity']);
    $sheet->setCellValue('J' . $row, $item['unit_price']);
    $sheet->setCellValue('K' . $row, $item['subtotal']);
    $sheet->setCellValue('L' . $row, $item['total']);
    $row++;
}

// Auto-size columns
foreach (range('A', 'L') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Number formatting
$sheet->getStyle('J7:L' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0');

// Borders
$borderStyle = [
    'borders' => [
        'allBorders' => ['borderStyle' => Border::BORDER_THIN]
    ]
];
$sheet->getStyle('A6:L' . ($row - 1))->applyFromArray($borderStyle);

// Set filename
$filename = 'Laporan_Penjualan_' . date('Ymd_His') . '.xlsx';

// Clean output buffer
ob_clean();

// Send headers
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Write file
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
