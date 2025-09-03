<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';

$success = '';
$error = '';

// Upload file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
    $nama_file = basename($_FILES['file']['name']);
    $tmp = $_FILES['file']['tmp_name'];
    $deskripsi = $_POST['deskripsi'] ?? '';
    $upload_dir = __DIR__ . '/uploads/';
    $upload_path = $upload_dir . $nama_file;
    $allowed_ext = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif'];
    $ext = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed_ext)) {
        $error = "Jenis file tidak diizinkan.";
    } elseif ($_FILES['file']['size'] > 2 * 1024 * 1024) {
        $error = "Ukuran file maksimal 2MB.";
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM dokumen WHERE nama_file = ?");
        $stmt->execute([$nama_file]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Nama file sudah ada. Harap ganti nama file sebelum upload.";
        } elseif (move_uploaded_file($tmp, $upload_path)) {
            $stmt = $pdo->prepare("INSERT INTO dokumen (nama_file, deskripsi) VALUES (?, ?)");
            $stmt->execute([$nama_file, $deskripsi]);
            $success = "Dokumen berhasil diupload.";
        } else {
            $error = "Gagal mengupload dokumen.";
        }
    }
}

// Hapus file
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("SELECT nama_file FROM dokumen WHERE id = ?");
    $stmt->execute([$id]);
    $file = $stmt->fetchColumn();

    if ($file) {
        unlink(__DIR__ . '/uploads/' . $file);
        $pdo->prepare("DELETE FROM dokumen WHERE id = ?")->execute([$id]);
        $success = "Dokumen berhasil dihapus.";
    } else {
        $error = "File tidak ditemukan.";
    }
}

// Update deskripsi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id = $_POST['edit_id'];
    $deskripsi = $_POST['edit_deskripsi'];
    $pdo->prepare("UPDATE dokumen SET deskripsi = ? WHERE id = ?")->execute([$deskripsi, $id]);
    $success = "Deskripsi dokumen diperbarui.";
}

// Ambil semua data
$data = $pdo->query("SELECT * FROM dokumen ORDER BY tanggal_upload DESC")->fetchAll();

include_once __DIR__ . '/../../includes/header.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Manajemen Dokumen</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100 p-8">

<div class="overflow-x-auto bg-white rounded-lg shadow border border-gray-200 p-4" x-data="dokumenApp">
  <h1 class="text-2xl font-bold mb-6">📁 Manajemen Dokumen</h1>

  <!-- Form Upload -->
  <form method="post" enctype="multipart/form-data" class="flex flex-col md:flex-row items-center gap-4 mb-6">
    <input type="file" name="file" required class="border p-2 rounded w-full md:w-1/3">
    <input type="text" name="deskripsi" placeholder="Deskripsi dokumen" class="border p-2 rounded w-full md:w-1/2">
    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Upload</button>
  </form>

  <!-- Tabel Dokumen -->
  <div class="overflow-x-auto">
    <table class="w-full border text-sm">
      <thead class="bg-gray-200">
        <tr>
          <th class="p-2 border">No</th>
          <th class="p-2 border">Nama File</th>
          <th class="p-2 border">Deskripsi</th>
          <th class="p-2 border">Tanggal Upload</th>
          <th class="p-2 border">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($data as $i => $row): ?>
        <tr class="border hover:bg-gray-50">
          <td class="p-2 border"><?= $i + 1 ?></td>
          <td class="p-2 border">
            <a href="uploads/<?= htmlspecialchars($row['nama_file']) ?>" target="_blank" class="text-blue-600 underline">
              <?= htmlspecialchars($row['nama_file']) ?>
            </a>
          </td>
          <td class="p-2 border"><?= htmlspecialchars($row['deskripsi']) ?></td>
          <td class="p-2 border"><?= $row['tanggal_upload'] ?></td>
          <td class="p-2 border space-x-2">
            <button @click="showModal=true; previewSrc='uploads/<?= $row['nama_file'] ?>'"
                    class="text-green-600 hover:underline">View</button>

            <a href="uploads/<?= $row['nama_file'] ?>" download class="text-blue-600 hover:underline">Download</a>

            <button @click="editId=<?= $row['id'] ?>; editDeskripsi='<?= htmlspecialchars($row['deskripsi']) ?>'; showEditModal=true"
                    class="text-yellow-600 hover:underline">Edit</button>

            <button @click="Swal.fire({
              title: 'Yakin hapus dokumen?',
              text: 'Dokumen akan dihapus permanen!',
              icon: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#e3342f',
              confirmButtonText: 'Hapus',
              cancelButtonText: 'Batal'
            }).then((result) => {
              if (result.isConfirmed) {
                window.location.href = '?delete=<?= $row['id'] ?>';
              }
            })" class="text-red-600 hover:underline">Hapus</button>
          </td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>

  <!-- Modal Preview Dokumen -->
  <div x-show="showModal" x-cloak class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded shadow-lg w-full max-w-3xl p-4 relative">
      <button class="absolute top-2 right-2 text-gray-500" @click="showModal = false">✖</button>
      <iframe x-bind:src="getPreviewLink(previewSrc)" class="w-full h-[700px]" src="https://docs.google.com/gview?url=http://localhost/sep/pages/dokumen/uploads/<?= urlencode($row['nama_file']) ?>&embedded=true" style="width:100%; he frameborder="0"></iframe>

    </div>
  </div>

  <!-- Modal Edit Deskripsi -->
  <div x-show="showEditModal" x-cloak class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
    <form method="post" class="bg-white p-6 rounded shadow-lg w-full max-w-md">
      <input type="hidden" name="edit_id" :value="editId">
      <h2 class="text-lg font-bold mb-4">Edit Deskripsi</h2>
      <textarea name="edit_deskripsi" x-model="editDeskripsi" rows="4" class="w-full border p-2 rounded mb-4"></textarea>
      <div class="flex justify-end gap-2">
        <button type="button" @click="showEditModal = false" class="px-4 py-2 border rounded">Batal</button>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Simpan</button>
      </div>
    </form>
  </div>

  <!-- SweetAlert feedback -->
  <?php if ($success): ?>
    <script>
      Swal.fire({ icon: 'success', title: 'Berhasil', text: '<?= $success ?>' });
    </script>
  <?php elseif ($error): ?>
    <script>
      Swal.fire({ icon: 'error', title: 'Gagal', text: '<?= $error ?>' });
    </script>
  <?php endif; ?>

</div>
<script>
  document.addEventListener('alpine:init', () => {
    Alpine.data('dokumenApp', () => ({
      showModal: false,
      previewSrc: '',
      showEditModal: false,
      editId: null,
      editDeskripsi: '',
      getPreviewLink(src) {
        const ext = src.split('.').pop().toLowerCase();
        if (ext === 'pdf') {
          return src;
        } else {
          const url = window.location.origin + '/pages/dokumen/' + src;
          return 'https://docs.google.com/gview?url=' + encodeURIComponent(url) + '&embedded=true';
        }
      }
    }));
  });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>