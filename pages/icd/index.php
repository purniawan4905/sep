<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();


$limit = 10;
$page  = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$where = "";
$params = [];

/* ===== EXPORT EXCEL ===== */
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=data_icd.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $stmtExport = $pdo->prepare("SELECT * FROM icd ORDER BY id ASC");
    $stmtExport->execute();
    $data = $stmtExport->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1'>";
    echo "<tr><th>No</th><th>Nama Diagnosa</th><th>ICD</th><th>Keterangan</th></tr>";
    $no = 1;
    foreach ($data as $row) {
        echo "<tr>";
        echo "<td>{$no}</td>";
        echo "<td>" . htmlspecialchars($row['nama_diagnosa']) . "</td>";
        echo "<td>" . htmlspecialchars($row['icd_code']) . "</td>";
        echo "<td>" . htmlspecialchars($row['keterangan']) . "</td>";
        echo "</tr>";
        $no++;
    }
    echo "</table>";
    exit;
}

/* ===== CRUD HANDLER ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah') {
        $stmt = $pdo->prepare("INSERT INTO icd (nama_diagnosa, icd_code, keterangan) VALUES (?, ?, ?)");
        $stmt->execute([
            $_POST['nama_diagnosa'],
            $_POST['icd_code'],
            $_POST['keterangan'] ?? null
        ]);
        header("Location: index.php?status=created"); exit;
    }

    if ($aksi === 'edit' && isset($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE icd SET nama_diagnosa=?, icd_code=?, keterangan=? WHERE id=?");
        $stmt->execute([
            $_POST['nama_diagnosa'],
            $_POST['icd_code'],
            $_POST['keterangan'] ?? null,
            $_POST['id']
        ]);
        header("Location: index.php?status=updated"); exit;
    }
}

/* ===== DELETE ===== */
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM icd WHERE id=?");
    $stmt->execute([$_GET['delete']]);
    header("Location: index.php?status=deleted"); exit;
}

/* ===== SEARCH ===== */
if (!empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $where .= ($where ? " AND" : " WHERE") . " (nama_diagnosa LIKE ? OR icd_code LIKE ?)";
    $params[] = $search;
    $params[] = $search;
}

/* ===== PAGINATION ===== */
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM icd $where");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

$stmt = $pdo->prepare("SELECT * FROM icd $where ORDER BY id DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div x-data="modal()" class="bg-gray-100 p-4" x-cloak>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Data ICD</h1>
        <button @click="openAdd()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">+ Tambah Data</button>
    </div>

    <div class="overflow-x-auto bg-white rounded shadow p-4 mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-2">
        <div class="space-x-2">
            <a href="?export=excel" class="bg-green-600 hover:bg-green-500 text-white px-4 py-2 rounded transition">Ekspor ke Excel</a>
            <a href="index.php" class="bg-gray-500 hover:bg-gray-400 text-white px-3 py-2 rounded">Reset</a>
        </div>
        <form method="GET" class="flex gap-2">
            <input type="text" name="search" placeholder="Cari..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" class="border px-3 py-2 rounded w-64" />
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-2 rounded">Cari</button>
        </form>
    </div>

    <div class="overflow-x-auto bg-white rounded shadow border border-gray-200">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-100 text-gray-700 font-semibold">
                <tr>
                    <th class="px-4 py-2 text-center">No</th>
                    <th class="px-4 py-2">Nama Diagnosa</th>
                    <th class="px-4 py-2">ICD</th>
                    <th class="px-4 py-2">Keterangan</th>
                    <th class="px-4 py-2 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="text-gray-700">
                <?php $no=$offset+1; foreach($rows as $r): ?>
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-4 py-2 text-center"><?= $no++ ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars($r['nama_diagnosa']) ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars($r['icd_code']) ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars($r['keterangan']) ?></td>
                        <td class="px-4 py-2 text-center space-x-2">
                            <a href="#" @click.prevent="openEdit(<?= htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8') ?>)" class="text-blue-600 hover:underline">Edit</a>
                            <a href="#" @click.prevent="confirmDelete(<?= $r['id'] ?>)" class="text-red-600 hover:underline">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="flex justify-between items-center p-4">
            <div>Total: <?= $totalRows ?> data</div>
            <div class="space-x-1">
                <?php for($i=1;$i<=$totalPages;$i++): ?>
                    <a href="?page=<?= $i ?>" class="px-3 py-1 border rounded <?= $i==$page?'bg-blue-600 text-white':'hover:bg-gray-100' ?>"> <?= $i ?> </a>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <!-- Modal Tambah/Edit -->
    <div x-show="showModal" class="fixed inset-0 flex items-center justify-center bg-black/50 z-50" x-cloak @click.outside="closeModal()">
        <div class="bg-white w-full max-w-lg p-6 rounded-lg shadow-lg">
            <h2 class="text-lg font-bold mb-4" x-text="formTitle"></h2>
            <form method="POST" class="grid grid-cols-1 gap-4">
                <input type="hidden" name="aksi" :value="aksi">
                <input type="hidden" name="id" :value="form.id">

                <input type="text" name="nama_diagnosa" x-model="form.nama_diagnosa" placeholder="Nama Diagnosa" required class="border p-2 rounded">
                <input type="text" name="icd_code" x-model="form.icd_code" placeholder="ICD" required class="border p-2 rounded">
                <textarea name="keterangan" x-model="form.keterangan" placeholder="Keterangan" class="border p-2 rounded"></textarea>

                <div class="flex justify-end gap-2 mt-2">
                    <button type="button" @click="closeModal()" class="bg-gray-400 text-white px-4 py-2 rounded">Batal</button>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
window.modal = function () {
    return {
        showModal: false,
        aksi: 'tambah',
        formTitle: 'Tambah ICD',
        form: { id: '', nama_diagnosa: '', icd_code: '', keterangan: '' },

        openAdd() {
            this.aksi = 'tambah';
            this.formTitle = 'Tambah ICD';
            this.form = { id: '', nama_diagnosa: '', icd_code: '', keterangan: '' };
            this.showModal = true;
        },
        openEdit(d) {
            this.aksi = 'edit';
            this.formTitle = 'Edit ICD';
            this.form = { id: d.id, nama_diagnosa: d.nama_diagnosa, icd_code: d.icd_code, keterangan: d.keterangan };
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
            }).then(r => { if (r.isConfirmed) location = '?delete=' + id; });
        }
    }
}
</script>

<?php if(isset($_GET['status'])): ?>
<script>
Swal.fire({
    icon: 'success',
    title: {
        created: 'Data berhasil ditambahkan!',
        updated: 'Data berhasil diperbarui!',
        deleted: 'Data berhasil dihapus!'
    }['<?= $_GET['status'] ?>'],
    timer: 1500,
    showConfirmButton: false
});
</script>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
