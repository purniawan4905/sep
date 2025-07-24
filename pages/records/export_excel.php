<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Hindari spasi/output sebelum header
ob_clean();

/* ================= FILTERING ================= */
$where = "WHERE 1 = 1";
$params = [];
$search = $_GET['search'] ?? '';

if (!empty($_GET['masuk_dari']) && !empty($_GET['masuk_sampai'])) {
    $where .= " AND tanggal_masuk BETWEEN ? AND ?";
    $params[] = $_GET['masuk_dari'];
    $params[] = $_GET['masuk_sampai'];
}

if (!empty($_GET['keluar_dari']) && !empty($_GET['keluar_sampai'])) {
    $where .= " AND tanggal_keluar BETWEEN ? AND ?";
    $params[] = $_GET['keluar_dari'];
    $params[] = $_GET['keluar_sampai'];
}

if (!empty($_GET['bulan'])) {
    $where .= " AND DATE_FORMAT(tanggal_masuk, '%Y-%m') = ?";
    $params[] = $_GET['bulan'];
}

if (!empty($search)) {
    $where = "WHERE nama_pasien LIKE ? OR no_rm LIKE ?";
    $params = ["%{$search}%", "%{$search}%"];
}

/* ================= QUERY ================= */
$stmt = $pdo->prepare("SELECT * FROM records $where ORDER BY tanggal_masuk ASC");
$stmt->execute($params);
$data = $stmt->fetchAll();

/* ================= SPREADSHEET ================= */
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Data Rawat Inap');

// Header kolom
$headers = ['No', 'No RM', 'No SEP', 'Nama Pasien', 'Tanggal Masuk', 'Tanggal Keluar', 'Keterangan'];
$sheet->fromArray($headers, NULL, 'A1');

// Isi data
$rowIndex = 2;
$no = 1;

foreach ($data as $row) {
    $sheet->setCellValue("A{$rowIndex}", $no++);
    $sheet->setCellValue("B{$rowIndex}", $row['no_rm']);
    $sheet->setCellValue("C{$rowIndex}", '0158R011' . $row['no_sep']);
    $sheet->setCellValue("D{$rowIndex}", $row['nama_pasien']);
    $sheet->setCellValue("E{$rowIndex}", $row['tanggal_masuk']);
    $sheet->setCellValue("F{$rowIndex}", $row['tanggal_keluar']);
    $sheet->setCellValue("G{$rowIndex}", $row['keterangan']);
    $rowIndex++;
}

// Style opsional
$sheet->getStyle('A1:I1')->getFont()->setBold(true);
foreach (range('A', 'I') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

/* ================= OUTPUT ================= */
$filename = 'Data-Rawat-Inap-' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
