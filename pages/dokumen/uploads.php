<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $uploadDir = __DIR__ . '/../../uploads/';
    $fileName = basename($_FILES['file']['name']);
    $targetPath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
        $stmt = $pdo->prepare("INSERT INTO dokumen (nama_file, deskripsi, tanggal_upload) VALUES (?, ?, NOW())");
        $stmt->execute([$fileName, $_POST['deskripsi']]);
    }
}

header('Location: index.php');
exit();
