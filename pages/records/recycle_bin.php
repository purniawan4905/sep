<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';

// Restore
if (isset($_GET['restore'])) {
    $id = (int)$_GET['restore'];
    $stmt = $pdo->prepare("UPDATE records SET is_deleted = 0 WHERE id = ?");
    $stmt->execute([$id]);
    
    $_SESSION['alert'] = [
        'icon' => 'success',
        'title' => 'Dipulihkan',
        'text' => 'Data berhasil dipulihkan dari Recycle Bin!'
    ];
    
    header("Location: recycle_bin.php");
    exit;
}

// Hapus permanen
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM records WHERE id = ?");
    $stmt->execute([$id]);
    
    $_SESSION['alert'] = [
        'icon' => 'success',
        'title' => 'Dihapus Permanen',
        'text' => 'Data berhasil dihapus permanen!'
    ];
    
    header("Location: recycle_bin.php");
    exit;
}

// Ambil data terhapus
$stmt = $pdo->query("SELECT * FROM records WHERE is_deleted = 1 ORDER BY id DESC");
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// include __DIR__ . '/../../includes/header.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recycle Bin - Sistem Manajemen Data</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f7fafc;
    }
    
    .card-hover {
      transition: all 0.3s ease;
    }
    
    .card-hover:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    }
    
    .btn-restore {
      background: linear-gradient(to right, #10b981, #059669);
      transition: all 0.3s ease;
    }
    
    .btn-restore:hover {
      background: linear-gradient(to right, #059669, #047857);
      transform: translateY(-2px);
    }
    
    .btn-delete {
      background: linear-gradient(to right, #ef4444, #dc2626);
      transition: all 0.3s ease;
    }
    
    .btn-delete:hover {
      background: linear-gradient(to right, #dc2626, #b91c1c);
      transform: translateY(-2px);
    }
    
    .empty-state {
      background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    }
  </style>
</head>
<body class="bg-gray-50">
  <div class="min-h-screen">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 to-indigo-700 shadow-lg">
      <div class="container mx-auto px-4 py-6">
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-2xl font-bold text-white">
              <i class="fas fa-trash-restore mr-2"></i> Recycle Bin
            </h1>
            <p class="text-blue-100 mt-1">Kelola data yang telah dihapus</p>
          </div>
          <a href="index.php" class="bg-white text-blue-600 hover:bg-blue-50 px-4 py-2 rounded-lg font-medium transition duration-300 shadow-sm">
            <i class="fas fa-arrow-left mr-2"></i> Kembali ke Dashboard
          </a>
        </div>
      </div>
    </div>

    <!-- Stats Overview -->
    <div class="container mx-auto px-4 -mt-4">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-md p-5 card-hover">
          <div class="flex items-center">
            <div class="rounded-full bg-blue-100 p-3">
              <i class="fas fa-trash text-blue-600 text-xl"></i>
            </div>
            <div class="ml-4">
              <h2 class="text-gray-500 text-sm font-medium">Total Dihapus</h2>
              <p class="text-2xl font-bold text-gray-800"><?php echo count($records); ?></p>
            </div>
          </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-md p-5 card-hover">
          <div class="flex items-center">
            <div class="rounded-full bg-green-100 p-3">
              <i class="fas fa-undo text-green-600 text-xl"></i>
            </div>
            <div class="ml-4">
              <h2 class="text-gray-500 text-sm font-medium">Dapat Dipulihkan</h2>
              <p class="text-2xl font-bold text-gray-800"><?php echo count($records); ?></p>
            </div>
          </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-md p-5 card-hover">
          <div class="flex items-center">
            <div class="rounded-full bg-purple-100 p-3">
              <i class="fas fa-clock text-purple-600 text-xl"></i>
            </div>
            <div class="ml-4">
              <h2 class="text-gray-500 text-sm font-medium">Masa Retensi</h2>
              <p class="text-2xl font-bold text-gray-800">30 Hari</p>
            </div>
          </div>
        </div>
      </div>
    </div>

         <!-- Pesan tambahan -->
    <div class="bg-yellow-100 border-l-8 border-yellow-500 text-yellow-800 p-6 mt-2 text-center shadow-md">
      <p class="text-2xl font-bold">
        🚧 Mas Agus Masih Repot, Untuk kelengkapan masih dalam proses pengembangan 😁
      </p>
    </div>