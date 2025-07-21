<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';   // sesuaikan path
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

// ===== Pagination & Fetch =====
$limit  = 10;
$page   = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$where  = "";
$params = [];


/* ---- CRUD Handler ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
// AMBIL DATA YANG MUNGKIN TIDAK DIISI
    $naik_2_ke_1   = isset($_POST['naik_2_ke_1'])   ? $_POST['naik_2_ke_1']   : 0;
    $naik_2_ke_vip = isset($_POST['naik_2_ke_vip']) ? $_POST['naik_2_ke_vip'] : 0;
    $naik_1_ke_vip = isset($_POST['naik_1_ke_vip']) ? $_POST['naik_1_ke_vip'] : 0;

    if ($aksi === 'tambah') {
        $stmt = $pdo->prepare("INSERT INTO diagnosa (diagnosa, kelas1, kelas2, kelas3, `2_naik_1`, `2_naik_>1`, `1_naik_>1`, keterangan)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['diagnosa'],
            $_POST['kelas1'],
            $_POST['kelas2'],
            $_POST['kelas3'],
            $naik_2_ke_1,
            $naik_2_ke_vip,
            $naik_1_ke_vip,
            $_POST['keterangan'] ?? null
        ]);
        header("Location: index.php?status=created"); exit;
    }

    if ($aksi === 'edit' && isset($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE diagnosa SET diagnosa=?, kelas1=?, kelas2=?, kelas3=?, `2_naik_1`=?, `2_naik_>1`=?, `1_naik_>1`=?, keterangan=?
                               WHERE id = ?");
        $stmt->execute([
            $_POST['diagnosa'],
            $_POST['kelas1'],
            $_POST['kelas2'],
            $_POST['kelas3'],
            $naik_2_ke_1,
            $naik_2_ke_vip,
            $naik_1_ke_vip,
            $_POST['keterangan'] ?? null,
            $_POST['id']
        ]);
        header("Location: index.php?status=updated"); exit;
    }
}

// ---- Delete (GET) ----
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM diagnosa WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: index.php?status=deleted"); exit;
}

// Search
if (!empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $where .= ($where ? " AND" : " WHERE") . " diagnosa LIKE ?";
    $params[] = $search;
}

// ---- Fetch data ----
// Hitung total data (untuk pagination)
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM diagnosa $where");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

/* ---------- DATA GRID + PAGINATION ---------- */
$query = "SELECT * FROM diagnosa $where ORDER BY id DESC LIMIT $limit OFFSET $offset";
$stmt  = $pdo->prepare($query);
$stmt->execute($params);
$rows  = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';

/* ---------- DATA GRAFIK DIAGNOSA (TOP 5) ---------- */
// $stmt = $pdo->query("
//     SELECT diagnosa, kelas1, kelas2, kelas3
//     FROM diagnosa
//     ORDER BY (kelas1 + kelas2 + kelas3) DESC
//     LIMIT 5
// ");
// $diagData = $stmt->fetchAll(PDO::FETCH_ASSOC);
// $diagLabels = array_column($diagData, 'diagnosa');
// $kelas1 = array_column($diagData, 'kelas1');
// $kelas2 = array_column($diagData, 'kelas2');
// $kelas3 = array_column($diagData, 'kelas3');
?>

<div x-data="modal()" class="bg-gray-100"  x-init="console.log('Alpine Loaded')" x-cloak>

<!-- ---------- HEADER ---------- -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Data Diagnosa</h1>

<!-- Tombol buka modal tambah -->
        <button @click="openAdd(); console.log('Tambah diklik')"
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
            + Tambah Data
        </button>
    </div>

<!-- ---------- TABEL ---------- -->
<div class="overflow-x-auto bg-white rounded-lg shadow border border-gray-200 p-4">
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-4 gap-2">
        <a href="#"
           class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded transition">
            Ekspor ke CSV
        </a>

        <a href="index.php"
            class="bg-gray-500 hover:bg-gray-400 text-white px-4 py-2 rounded transition me-auto">
            Reset
        </a>

        <!-- Form Search -->
        <form method="GET" class="flex gap-2">
            <input type="text" name="search" placeholder="Cari Diagnosa..."
                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                   class="border px-3 py-2 rounded w-64" />
            <button type="submit"
                    class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 transition">
                Cari
            </button>
        </form>
    </div>

<!-- TABLE -->
<div class="overflow-x-auto bg-white rounded-lg shadow border border-gray-200">
<table class="min-w-full text-sm">
  <thead class="bg-gray-100 text-gray-700 font-semibold">
    <tr>
      <th class="px-4 py-3 text-center">No</th>
      <th class="px-4 py-3">Diagnosa</th>
      <th class="px-4 py-3 text-right">Kelas 1</th>
      <th class="px-4 py-3 text-right">Kelas 2</th>
      <th class="px-4 py-3 text-right">Kelas 3</th>
      <th class="px-4 py-3 text-right">Kelas 2 Naik Kelas 1</th>
      <th class="px-4 py-3 text-right">Kelas 2 Naik VIP 75%</th>
      <th class="px-4 py-3 text-right">Kelas 1 Naik VIP 75%</th>
      <th class="px-4 py-3">Keterangan</th>
      <th class="px-4 py-3 text-center">Aksi</th>
    </tr>
  </thead>
  <tbody class="text-gray-700">
    <?php $no=$offset+1; foreach($rows as $r): ?>
      <tr class="border-t hover:bg-gray-50">
        <td class="px-4 py-2 text-center"><?= $no++ ?></td>
        <td class="px-4 py-2"><?= htmlspecialchars($r['diagnosa']) ?></td>
        <td class="px-4 py-2 text-right"><?= number_format($r['kelas1']) ?></td>
        <td class="px-4 py-2 text-right"><?= number_format($r['kelas2']) ?></td>
        <td class="px-4 py-2 text-right"><?= number_format($r['kelas3']) ?></td>
        <td class="px-4 py-2 text-right"><?= number_format($r['2_naik_1']) ?></td>
        <td class="px-4 py-2 text-right"><?= number_format($r['2_naik_>1']) ?></td>
        <td class="px-4 py-2 text-right"><?= number_format($r['1_naik_>1']) ?></td>
        <td class="px-4 py-2"><?= htmlspecialchars($r['keterangan']) ?></td>
        <td class="px-4 py-2 text-center space-x-2">
          <a href="#" @click.prevent="openEdit(<?= htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8') ?>)"
             class="text-blue-600 hover:underline">Edit</a>
          <a href="#" @click.prevent="confirmDelete(<?= $r['id'] ?>)"
             class="text-red-600 hover:underline">Hapus</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<!-- PAGINATION -->
<div class="flex justify-between items-center p-4">
  <div>Total : <?= $totalRows ?> data</div>
  <div class="space-x-1">
    <?php for($i=1;$i<=$totalPages;$i++): ?>
      <a href="?page=<?= $i ?>"
         class="px-3 py-1 border rounded <?= $i==$page?'bg-blue-600 text-white':'hover:bg-gray-100' ?>">
         <?= $i ?>
      </a>
    <?php endfor; ?>
  </div>
</div>
</div>


<!-- ============= MODAL (Tambah & Edit) ============= -->
<div x-show="showModal"
     class="fixed inset-0 flex items-center justify-center bg-black/50 z-50"
     x-cloak @click.outside="closeModal()" x-transition>
  <div class="bg-white w-full max-w-lg p-6 rounded-lg shadow-lg">
    <h2 class="text-lg font-bold mb-4" x-text="formTitle"></h2>
    <form method="POST" class="grid grid-cols-2 gap-4">
      <input type="hidden" name="aksi" :value="aksi">
      <input type="hidden" name="id" :value="form.id">
<input type="text" name="diagnosa" x-model="form.diagnosa" placeholder="Diagnosa"
       required class="border p-2 rounded col-span-2">

      <input type="number" step="0.01" name="kelas1" x-model="form.kelas1" placeholder="Tarif Kelas 1"
            required class="border p-2 rounded">

      <input type="number" step="0.01" name="kelas2" x-model="form.kelas2" placeholder="Tarif Kelas 2"
            required class="border p-2 rounded">

      <input type="number" step="0.01" name="kelas3" x-model="form.kelas3" placeholder="Tarif Kelas 3"
            required class="border p-2 rounded">

      <!-- Kelas 2 Naik Kelas 1 -->
      <input type="number" step="0.01" name="naik_2_ke_1" x-model="form.naik_2_ke_1"
            placeholder="Kelas 2 Naik Kelas 1"
            :class="invalid_2_1 ? 'border-red-500 bg-red-100' : 'border-green-500 bg-white'"
            class="border p-2 rounded" readonly>

      <!-- Kelas 2 Naik VIP -->
      <input type="number" step="0.01" name="naik_2_ke_vip" x-model="form.naik_2_ke_vip"
            placeholder="Kelas 2 Naik VIP"
            :class="invalid_2_vip ? 'border-red-500 bg-red-100' : 'border-green-500 bg-white'"
            class="border p-2 rounded" readonly>

      <!-- Kelas 1 Naik VIP -->
      <input type="number" step="0.01" name="naik_1_ke_vip" x-model="form.naik_1_ke_vip"
            placeholder="Kelas 1 Naik VIP"
            :class="invalid_1_vip ? 'border-red-500 bg-red-100' : 'border-green-500 bg-white'"
            class="border p-2 rounded" readonly>

      <input type="text" name="keterangan" x-model="form.keterangan" placeholder="Keterangan"
            class="border p-2 rounded col-span-2">

      <div class="col-span-2 flex justify-end gap-2 mt-2">
        <button type="button" @click="closeModal()" class="bg-gray-400 text-white px-4 py-2 rounded">Batal</button>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Simpan</button>
      </div>
    </form>
  </div>
</div>

  <script>
    window.modal = function () {
  return {
    showModal: false,
    aksi: 'tambah',
    formTitle: 'Tambah Diagnosa',
    form: {
      id: '',
      diagnosa: '',
      kelas1: 0,
      kelas2: 0,
      kelas3: 0,
      naik_2_ke_1: 0,
      naik_2_ke_vip: 0,
      naik_1_ke_vip: 0,
      keterangan: ''
    },

    // Fungsi untuk menghitung naik kelas otomatis
    updateNaikKelas() {
      const k1 = parseFloat(this.form.kelas1) || 0;
      const k2 = parseFloat(this.form.kelas2) || 0;

      const naik_2_1 = k1 - k2;
      const naik_2_vip = (k1 - k2) + (k1 * 0.75);
      const naik_1_vip = k1 * 0.75;

      this.form.naik_2_ke_1 = parseFloat(naik_2_1.toFixed(2));
      this.form.naik_2_ke_vip = parseFloat(naik_2_vip.toFixed(2));
      this.form.naik_1_ke_vip = parseFloat(naik_1_vip.toFixed(2));

      // Validasi logika (misalnya jika hasil < 0)
      this.invalid_2_1 = naik_2_1 < 0;
      this.invalid_2_vip = naik_2_vip < 0;
      this.invalid_1_vip = naik_1_vip < 0;
    },

    openAdd() {
      this.aksi = 'tambah';
      this.formTitle = 'Tambah Diagnosa';
      this.form = {
        id: '',
        diagnosa: '',
        kelas1: 0,
        kelas2: 0,
        kelas3: 0,
        naik_2_ke_1: 0,
        naik_2_ke_vip: 0,
        naik_1_ke_vip: 0,
        keterangan: ''
      };
      this.showModal = true;
    },

    openEdit(d) {
      this.aksi = 'edit';
      this.formTitle = 'Edit Diagnosa';
      this.form = {
        id: d.id,
        diagnosa: d.diagnosa,
        kelas1: parseFloat(d.kelas1) || 0,
        kelas2: parseFloat(d.kelas2) || 0,
        kelas3: parseFloat(d.kelas3) || 0,
        naik_2_ke_1: parseFloat(d['2_naik_1']) || 0,
        naik_2_ke_vip: parseFloat(d['2_naik_>1']) || 0,
        naik_1_ke_vip: parseFloat(d['1_naik_>1']) || 0,
        keterangan: d.keterangan
      };
      this.updateNaikKelas(); // hitung ulang
      this.showModal = true;
    },

    closeModal() { this.showModal = false; },

    confirmDelete(id) {
      Swal.fire({
        title: 'Hapus data?',
        text: 'Data yang dihapus tidak dapat dikembalikan!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, hapus'
      }).then(r => {
        if (r.isConfirmed) location = '?delete=' + id;
      });
    }
  }
}
  </script>

<!-- ALERt sukses -->
<?php if(isset($_GET['status'])): ?>
<script>
Swal.fire({
  icon:'success',
  title:{
    created:'Data berhasil ditambahkan!',
    updated:'Data berhasil diperbarui!',
    deleted:'Data berhasil dihapus!'
  }['<?= $_GET['status'] ?>'],
  timer:1500,
  showConfirmButton:false
});
</script>
<?php endif; ?>
<!-- Alpine WAJIB SETELAH window.modal -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
