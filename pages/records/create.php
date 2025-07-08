<!-- <?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no_rm = $_POST['no_rm'];
    $no_sep = $_POST['no_sep'];
    $nama_pasien = $_POST['nama_pasien'];
    $tanggal_masuk = $_POST['tanggal_masuk'];
    $tanggal_keluar = $_POST['tanggal_keluar'];
    $keterangan = $_POST['keterangan'];

    $stmt = $pdo->prepare("INSERT INTO records (no_rm, no_sep, nama_pasien, tanggal_masuk, tanggal_keluar, keterangan) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$no_rm, $no_sep, $nama_pasien, $tanggal_masuk, $tanggal_keluar, $keterangan]);

    header("Location: index.php?status=created");
    exit();
}

include __DIR__ . '/../../includes/header.php';
?>

<h1 class="text-2xl font-bold mb-6">Tambah Data Pasien</h1>

<form method="POST" class="bg-white p-6 rounded shadow-md w-full md:w-2/3 lg:w-1/2 space-y-4">
    <div>
        <label class="block mb-1 font-medium">No RM</label>
        <input type="text" name="no_rm" required class="w-full px-4 py-2 border rounded" />
    </div>
    <div>
        <label class="block mb-1 font-medium">No SEP</label>
        <input type="text" name="no_sep" required class="w-full px-4 py-2 border rounded" />
    </div>
    <div>
        <label class="block mb-1 font-medium">Nama Pasien</label>
        <input type="text" name="nama_pasien" required class="w-full px-4 py-2 border rounded" />
    </div>
    <div>
        <label class="block mb-1 font-medium">Tanggal Masuk</label>
        <input type="date" name="tanggal_masuk" required class="w-full px-4 py-2 border rounded" />
    </div>
    <div>
        <label class="block mb-1 font-medium">Tanggal Keluar</label>
        <input type="date" name="tanggal_keluar" class="w-full px-4 py-2 border rounded" />
    </div>
    <div>
        <label class="block mb-1 font-medium">Keterangan</label>
        <textarea name="keterangan" class="w-full px-4 py-2 border rounded"></textarea>
    </div>
   <div class="flex justify-between mt-6">
    <a href="index.php" class="inline-block bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-2 rounded transition">
        ← Kembali
    </a>
    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded transition">
        Simpan
    </button>
    </div>
</form>

<?php include __DIR__ . '/../../includes/footer.php'; ?> -->
