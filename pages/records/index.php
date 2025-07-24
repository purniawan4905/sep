<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';


$limit  = 20;                                   // data per halaman
$page   = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$where  = "WHERE 1 = 1";
$params = [];

/* ---------- FILTER ---------- */
$hasFilter = false;

if (!empty($_GET['masuk_dari']) && !empty($_GET['masuk_sampai'])) {
    $where .= " AND tanggal_masuk BETWEEN ? AND ?";
    $params[] = $_GET['masuk_dari'];
    $params[] = $_GET['masuk_sampai'];
    $hasFilter = true;
}

if (!empty($_GET['keluar_dari']) && !empty($_GET['keluar_sampai'])) {
    $where .= " AND tanggal_keluar BETWEEN ? AND ?";
    $params[] = $_GET['keluar_dari'];
    $params[] = $_GET['keluar_sampai'];
    $hasFilter = true;
}

if (!empty($_GET['bulan'])) {
    $where .= " AND DATE_FORMAT(tanggal_masuk, '%Y-%m') = ?";
    $params[] = $_GET['bulan'];
    $hasFilter = true;
}

// Jika tidak ada filter sama sekali, tampilkan data bulan ini
if (!$hasFilter) {
    $bulan_ini = date('Y-m');
    $where .= " AND DATE_FORMAT(tanggal_masuk, '%Y-%m') = ?";
    $params[] = $bulan_ini;
}

// Search
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $where = "WHERE nama_pasien LIKE ? OR no_rm LIKE ?";
    $params = ["%$search%", "%$search%"];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah') {
        $no_rm          = $_POST['no_rm'] ?? '';
        $no_sep         = $_POST['no_sep'] ?? '';
        $nama_pasien    = $_POST['nama_pasien'] ?? '';
        $tanggal_masuk  = $_POST['tanggal_masuk'] ?? '';
        $tanggal_keluar = $_POST['tanggal_keluar'] ?? '';
        $keterangan     = $_POST['keterangan'] ?? '';

        $stmt = $pdo->prepare("INSERT INTO records (no_rm, no_sep, nama_pasien, tanggal_masuk, tanggal_keluar, keterangan)
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$no_rm, $no_sep, $nama_pasien, $tanggal_masuk, $tanggal_keluar, $keterangan]);

        header("Location: index.php?status=created");
        exit;
    }

    if ($aksi === 'edit') {
        $id             = $_POST['id'] ?? '';
        $no_rm          = $_POST['no_rm'] ?? '';
        $no_sep         = $_POST['no_sep'] ?? '';
        $nama_pasien    = $_POST['nama_pasien'] ?? '';
        $tanggal_masuk  = $_POST['tanggal_masuk'] ?? '';
        $tanggal_keluar = $_POST['tanggal_keluar'] ?? '';
        $keterangan     = $_POST['keterangan'] ?? '';

        $stmt = $pdo->prepare("UPDATE records SET no_rm = ?, no_sep = ?, nama_pasien = ?, tanggal_masuk = ?, tanggal_keluar = ?, keterangan = ?
                               WHERE id = ?");
        $stmt->execute([$no_rm, $no_sep, $nama_pasien, $tanggal_masuk, $tanggal_keluar, $keterangan, $id]);

        header("Location: index.php?status=updated");
        exit;
    }
}

$countQuery = "SELECT COUNT(*) FROM records $where";
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$totalRows = $stmt->fetchColumn();

$totalPages = ceil($totalRows / $limit);

$query = "SELECT * FROM records $where ORDER BY id DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$data = $stmt->fetchAll();

// $stmt = $pdo->prepare("SELECT * FROM records $where ORDER BY tanggal_masuk DESC LIMIT $limit OFFSET $offset");
// $stmt->execute($params);
// $records = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM records $where ORDER BY id DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$records = $stmt->fetchAll();


// Ambil data tren pasien masuk per bulan
$trendQuery = $pdo->query("
    SELECT 
        DATE_FORMAT(tanggal_masuk, '%Y-%m') AS bulan,
        COUNT(*) AS total
    FROM records
    GROUP BY bulan
    ORDER BY bulan
");

$trendChartData = $trendQuery->fetchAll(PDO::FETCH_ASSOC);
$trendlabels = array_column($trendChartData, 'bulan');
$trendvalues = array_column($trendChartData, 'total');

include __DIR__ . '/../../includes/header.php';
?>

<!-- ======================= ROOT ALPINE ======================= -->
<div x-data="modalHandler()" x-init="console.log('Alpine Loaded')" x-cloak>

    <!-- ---------- HEADER ---------- -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Data Rawat Inap</h1>

        <!-- Tombol buka modal tambah -->
        <button @click="showAddModal = true"
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
            + Tambah Data
        </button>
    </div>

    <!-- ---------- FORM FILTER ---------- -->
            <form method="GET" class="mb-4 flex gap-4 items-end flex-wrap">
            <div>
                <label class="block mb-1 text-sm">Tanggal Masuk (dari)</label>
                <input type="date" name="masuk_dari"
                    value="<?= $_GET['masuk_dari'] ?? '' ?>" class="px-3 py-2 border rounded" />
            </div>
            <div>
                <label class="block mb-1 text-sm">Tanggal Masuk (sampai)</label>
                <input type="date" name="masuk_sampai"
                    value="<?= $_GET['masuk_sampai'] ?? '' ?>" class="px-3 py-2 border rounded" />
            </div>
            <div>
                <label class="block mb-1 text-sm">Tanggal Keluar (dari)</label>
                <input type="date" name="keluar_dari"
                    value="<?= $_GET['keluar_dari'] ?? '' ?>" class="px-3 py-2 border rounded" />
            </div>
            <div>
                <label class="block mb-1 text-sm">Tanggal Keluar (sampai)</label>
                <input type="date" name="keluar_sampai"
                    value="<?= $_GET['keluar_sampai'] ?? '' ?>" class="px-3 py-2 border rounded" />
            </div>
            <div>
                <label class="block mb-1 text-sm">Filter Bulan</label>
                <input type="month" name="bulan"
                    value="<?= $_GET['bulan'] ?? '' ?>" class="px-3 py-2 border rounded" />
            </div>
            
            <div class="flex gap-2">
                <button type="submit"
                        class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 transition">
                    Filter
                </button>
                <a href="index.php"
                class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400 transition">
                    Reset
                </a>
            </div>
        </form>

    <!-- ---------- TABEL ---------- -->
<div class="overflow-x-auto bg-white rounded-lg shadow border border-gray-200 p-4">
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-4 gap-2">
        <a href="export_excel.php?<?= http_build_query($_GET) ?>"
        class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded transition">
        Ekspor ke Excel
        </a>

        <!-- Form Search -->
        <form method="GET" class="flex gap-2">
            <input type="text" name="search" placeholder="Cari Nama Pasien & No RM..."
                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                   class="border px-3 py-2 rounded w-64" />
            <button type="submit"
                    class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 transition">
                Cari
            </button>
        </form>
    </div>

        <table class="min-w-full text-sm text-left table-auto">
            <thead class="bg-gray-100 text-gray-700 font-semibold">
            <tr>
                <th class="px-4 py-3 text-center">No</th>
                <th class="px-4 py-3">No RM</th>
                <th class="px-4 py-3">No SEP</th>
                <th class="px-4 py-3">Nama Pasien</th>
                <th class="px-4 py-3">Tanggal Masuk</th>
                <th class="px-4 py-3">Tanggal Keluar</th>
                <th class="px-4 py-3">Keterangan</th>
                <th class="px-4 py-3 text-center">Aksi</th>
            </tr>
            </thead>
            <tbody class="text-gray-700">
            <?php $no = $offset + 1;
            foreach ($records as $row): ?>
                <tr class="border-t hover:bg-gray-50">
                    <td class="px-4 py-2 text-center"><?= $no++ ?></td>
                    <td class="px-4 py-2"><?= htmlspecialchars($row['no_rm']) ?></td>
                    <td class="px-4 py-2">0158R011<?= htmlspecialchars($row['no_sep']) ?></td>
                    <td class="px-4 py-2"><?= htmlspecialchars($row['nama_pasien']) ?></td>
                    <td class="px-4 py-2"><?= htmlspecialchars($row['tanggal_masuk']) ?></td>
                    <td class="px-4 py-2"><?= htmlspecialchars($row['tanggal_keluar']) ?></td>
                    <td class="px-4 py-2"><?= htmlspecialchars($row['keterangan']) ?></td>
                    <td class="px-4 py-2 text-center space-x-2">
                        <!-- buka modal edit -->
                        <a href="#"
                           @click.prevent="openEditModal(
                               <?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>
                           )"
                           class="text-blue-600 hover:underline">Edit</a>
                        <a href="#" onclick="confirmDelete(<?= $row['id'] ?>)"
                           class="text-red-600 hover:underline">Hapus</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- ---------- PAGINATION ---------- -->
        <div class="flex justify-between items-center mt-4 px-4 py-2">
            <div>Total: <?= $totalRows ?> data</div>
            <div class="space-x-1">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                       class="px-3 py-1 border rounded <?= $i == $page
                            ? 'bg-indigo-600 text-white'
                            : 'bg-white text-gray-700 hover:bg-gray-100' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    </div>

<!-- =============== MODAL TAMBAH DATA =============== -->
<div  x-show="showAddModal"
      x-cloak
      x-transition.opacity.scale
      @keydown.escape.window="showAddModal = false"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">

    <div @click.outside="showAddModal = false"
         class="bg-white w-full max-w-lg p-6 rounded-lg shadow-lg">

        <h2 class="text-lg font-bold mb-4">Tambah Data Rawat Inap</h2>

        <form action="" method="POST"
              @submit="showAddModal = false"
              class="grid grid-cols-2 gap-4">

            <input type="hidden" name="aksi" value="tambah">

            <input type="text"   name="no_rm"          placeholder="No RM"       required class="border p-2 rounded col-span-2 sm:col-span-1">
            <input type="text"   name="no_sep"         placeholder="No SEP"      required class="border p-2 rounded col-span-2 sm:col-span-1">
            <input type="text"   name="nama_pasien"    placeholder="Nama Pasien" required class="border p-2 rounded col-span-2">
            <input type="date"   name="tanggal_masuk" class="border p-2 rounded col-span-2 sm:col-span-1">
            <input type="date"   name="tanggal_keluar" class="border p-2 rounded col-span-2 sm:col-span-1">
            <input list="keteranganList" name="keterangan" placeholder="Keterangan" class="border p-2 rounded col-span-2">
            <datalist id="keteranganList">
                <option value="Pulang Atas Persetujuan Dokter">
                <option value="Meninggal">
                <option value="Rujuk Ke > Tinggi">
                <option value="Pulang APS">
            </datalist>

            <div class="col-span-2 flex justify-end gap-2 mt-2">
                <button type="button" @click="showAddModal = false"
                        class="bg-gray-400 text-white px-4 py-2 rounded">Batal</button>
                <button type="submit"
                        class="bg-blue-600 text-white px-4 py-2 rounded">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- =============== MODAL EDIT DATA ================= -->
<div  x-show="showEditModal"
      x-cloak
      x-transition.opacity.scale
      @keydown.escape.window="showEditModal = false"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">

    <div @click.outside="showEditModal = false"
         class="bg-white w-full max-w-lg p-6 rounded-lg shadow-lg">

        <h2 class="text-lg font-bold mb-4">Edit Data</h2>

        <form action="" method="POST"
              @submit="showEditModal = false"
              class="grid grid-cols-2 gap-4">

            <input type="hidden" name="aksi" value="edit">
            <input type="hidden" name="id"   :value="editData.id">

            <input type="text"  name="no_rm"          :value="editData.no_rm"          required class="border p-2 rounded col-span-2 sm:col-span-1">
            <input type="text"  name="no_sep"         :value="editData.no_sep"         required class="border p-2 rounded col-span-2 sm:col-span-1">
            <input type="text"  name="nama_pasien"    :value="editData.nama_pasien"    required class="border p-2 rounded col-span-2">
            <input type="date"  name="tanggal_masuk"  :value="editData.tanggal_masuk"  class="border p-2 rounded col-span-2 sm:col-span-1">
            <input type="date"  name="tanggal_keluar" :value="editData.tanggal_keluar"  class="border p-2 rounded col-span-2 sm:col-span-1">
            <input list="keteranganList" name="keterangan" placeholder="Keterangan" class="border p-2 rounded col-span-2">
            <datalist id="keteranganList">
                <option value="Pulang Atas Persetujuan Dokter">
                <option value="Meninggal">
                <option value="Rujuk Ke > Tinggi">
                <option value="Pulang APS">
            </datalist>

            <div class="col-span-2 flex justify-end gap-2 mt-2">
                <button type="button" @click="showEditModal = false"
                        class="bg-gray-400 text-white px-4 py-2 rounded">Batal</button>
                <button type="submit"
                        class="bg-indigo-600 text-white px-4 py-2 rounded">Update</button>
            </div>
        </form>
    </div>
</div>



</div><!-- /x-data root -->

<!-- ---------- SCRIPTS ---------- -->
<!-- Pindahkan ke bagian <head> atau sebelum </body> -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function modalHandler() {
    return {
        showAddModal:  false,
        showEditModal: false,
        editData:      {},

        /* buka modal edit & masukkan data */
        openEditModal(data) {
            this.editData     = data;
            this.showEditModal = true;
        }
    }
}

/* Hapus data dengan SweetAlert */
function confirmDelete(id) {
    Swal.fire({
        title: 'Yakin ingin menghapus?',
        text: "Data yang dihapus tidak dapat dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'delete.php?id=' + id;
        }
    });
}

/* Notifikasi sukses tambah / hapus */
<?php if (isset($_GET['status'])): ?>
Swal.fire({
    icon: 'success',
    title: {
        'created': 'Data berhasil ditambahkan!',
        'updated': 'Data berhasil diperbarui!',
        'deleted': 'Data berhasil dihapus!'
    }[<?= json_encode($_GET['status']) ?>] || 'Berhasil!',
    timer: 1500,
    showConfirmButton: false
});
<?php endif; ?>
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
