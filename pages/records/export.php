<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="data_pasien.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['No RM', 'No SEP', 'Nama Pasien', 'Tanggal Masuk', 'Tanggal Keluar', 'Keterangan']);

$stmt = $pdo->query("SELECT * FROM records");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['no_rm'],
        $row['no_sep'],
        $row['nama_pasien'],
        $row['tanggal_masuk'],
        $row['tanggal_keluar'],
        $row['keterangan']
    ]);
}
fclose($output);
exit();
