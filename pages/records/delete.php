<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM records WHERE id = ?");
    $stmt->execute([$id]);
}

header('Location: index.php?status=deleted');
exit();
