<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';

require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;


// Ambil parameter bulan dan tahun
$bulan = isset($_GET['bulan']) ? intval($_GET['bulan']) : date('n');
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');

$namaBulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

// Query data
$stmt = $pdo->prepare("SELECT * FROM jadwal_dokter WHERE MONTH(tanggal) = :bulan AND YEAR(tanggal) = :tahun ORDER BY tanggal ASC");
$stmt->execute([
    ':bulan' => $bulan,
    ':tahun' => $tahun
]);
$dataJadwal = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group data by tanggal
$grouped = [];
foreach ($dataJadwal as $row) {
    $grouped[$row['tanggal']][$row['shift']][$row['lokasi']] = $row['nama_dokter'];
}
ksort($grouped);

// Nama hari dalam bahasa Indonesia
$namaHari = [
    'Sunday' => 'Minggu',
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu'
];

// Buat HTML untuk PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Jadwal Dokter IGD</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }
        .header h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 18px;
        }
        .header h2 {
            margin: 5px 0;
            color: #34495e;
            font-size: 14px;
        }
        .header p {
            margin: 5px 0;
            color: #7f8c8d;
            font-size: 11px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th {
            background: #3498db;
            color: white;
            padding: 8px 4px;
            font-size: 9px;
            font-weight: bold;
            text-align: center;
            border: 1px solid #2980b9;
        }
        td {
            padding: 6px 4px;
            border: 1px solid #bdc3c7;
            text-align: center;
            vertical-align: middle;
        }
        .hari-minggu {
            background: #fadbd8;
        }
        .hari-sabtu {
            background: #d4e6f1;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 9px;
            color: #7f8c8d;
            border-top: 1px solid #bdc3c7;
            padding-top: 10px;
        }
        .signature {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .signature div {
            width: 200px;
            text-align: center;
        }
        .signature .line {
            margin-top: 50px;
            border-top: 1px solid #333;
            padding-top: 5px;
        }
        .info {
            margin: 10px 0;
            padding: 10px;
            background: #ecf0f1;
            border-radius: 5px;
            font-size: 10px;
        }
        .watermark {
            position: fixed;
            bottom: 50px;
            right: 50px;
            opacity: 0.1;
            font-size: 50px;
            transform: rotate(-30deg);
            color: #3498db;
            z-index: -1;
        }
        .top-left {
            top: 20px;
            left: 20px;
        }

        .top-right {
            top: 20px;
            right: 20px;
        }

        .bottom-left {
            bottom: 20px;
            left: 20px;
        }

        .bottom-right {
            bottom: 20px;
            right: 20px;
        }
    </style>
</head>
<body>
    <div class="watermark top-left">RSU SEBENING KASIH</div>
    <div class="watermark top-right">RSU SEBENING KASIH</div>
    <div class="watermark bottom-left">RSU SEBENING KASIH</div>
    <div class="watermark bottom-right">RSU SEBENING KASIH</div>
    
    <div class="header">
        <h1>RSU SEBENING KASIH</h1>
        <h2>JADWAL DOKTER IGD</h2>
        <p>Bulan ' . $namaBulan[$bulan] . ' ' . $tahun . '</p>
    </div>

    <div class="info">
        <strong>Informasi:</strong> Jadwal ini berlaku selama bulan ' . $namaBulan[$bulan] . ' ' . $tahun . ' dan dapat berubah sewaktu-waktu.
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 8%">Tanggal</th>
                <th rowspan="2" style="width: 8%">Hari</th>
                <th colspan="2" style="width: 28%">PAGI</th>
                <th colspan="2" style="width: 28%">SIANG</th>
                <th colspan="2" style="width: 28%">MALAM</th>
            </tr>
            <tr>
                <th style="width: 14%">Bangsal</th>
                <th style="width: 14%">IGD</th>
                <th style="width: 14%">Bangsal</th>
                <th style="width: 14%">IGD</th>
                <th style="width: 14%">Bangsal</th>
                <th style="width: 14%">IGD</th>
            </tr>
        </thead>
        <tbody>';

foreach ($grouped as $tgl => $shifts) {
    $timestamp = strtotime($tgl);
    $tanggal = date('j', $timestamp);
    $hariInggris = date('l', $timestamp);
    $hariIndonesia = $namaHari[$hariInggris];
    
    // Tentukan class untuk baris
    $rowClass = '';
    if ($hariInggris == 'Sunday') {
        $rowClass = ' class="hari-minggu"';
    } elseif ($hariInggris == 'Saturday') {
        $rowClass = ' class="hari-sabtu"';
    }
    
    $html .= '<tr' . $rowClass . '>';
    $html .= '<td>' . $tanggal . '</td>';
    $html .= '<td>' . $hariIndonesia . '</td>';
    
    foreach (['PAGI', 'SIANG', 'MALAM'] as $shift) {
        foreach (['BANGSAL', 'IGD'] as $lokasi) {
            $dokter = isset($shifts[$shift][$lokasi]) ? $shifts[$shift][$lokasi] : '-';
            $html .= '<td>' . htmlspecialchars($dokter) . '</td>';
        }
    }
    
    $html .= '</tr>';
}

$html .= '
    </tbody>
</table>

<table width="100%" style="margin-top:100px;">
    <tr>
        <td width="50%" style="text-align:center;">
            Mengetahui,<br>
            Kepala IGD<br><br><br><br>
            ___________________________<br>
            ( dr. ............. )
        </td>

        <td width="50%" style="text-align:center;">
            Pati, ' . date('d F Y') . '<br>
            Petugas Piket<br><br><br><br>
            ___________________________<br>
            ( ............. )
        </td>
    </tr>
</table>

<div class="footer"> <p>Dokumen ini dicetak pada tanggal ' . date('d-m-Y H:i:s') . ' oleh ' . htmlspecialchars($_SESSION['username'] ?? 'System') . '</p> <p>* Jadwal dapat berubah tanpa pemberitahuan terlebih dahulu</p> </div>

</body>
</html>';

// Setup Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// Output PDF
$dompdf->stream("jadwal_dokter_ig_{$bulan}_{$tahun}.pdf", array("Attachment" => false));