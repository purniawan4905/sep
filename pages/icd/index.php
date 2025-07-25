<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

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
    echo "<tr><th>No</th><th>Jenis</th><th>Nama Diagnosa</th><th>ICD</th><th>Keterangan</th></tr>";
    $no = 1;
    foreach ($data as $row) {
        echo "<tr>";
        echo "<td>{$no}</td>";
        echo "<td>" . htmlspecialchars($row['jenis']) . "</td>";
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
        $stmt = $pdo->prepare("INSERT INTO icd (nama_diagnosa, icd_code, keterangan, jenis) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $_POST['nama_diagnosa'],
            $_POST['icd_code'],
            $_POST['keterangan'] ?? null,
            $_POST['jenis']
        ]);
        header("Location: index.php?status=created"); exit;
    }

    if ($aksi === 'edit' && isset($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE icd SET nama_diagnosa=?, icd_code=?, keterangan=?, jenis=? WHERE id=?");
        $stmt->execute([
            $_POST['nama_diagnosa'],
            $_POST['icd_code'],
            $_POST['keterangan'] ?? null,
            $_POST['jenis'],
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

/* ===== PAGINATION + SEARCH ===== */
$limit = 20; // data per kolom

// --- ICD 10 ---
$page10 = isset($_GET['page10']) ? max(1, intval($_GET['page10'])) : 1;
$offset10 = ($page10 - 1) * $limit;
$search10 = isset($_GET['search10']) ? trim($_GET['search10']) : '';
$where10 = "WHERE jenis='ICD 10'";
$params10 = [];
if ($search10 !== '') {
    $where10 .= " AND (nama_diagnosa LIKE ? OR icd_code LIKE ?)";
    $params10 = ["%$search10%", "%$search10%"];
}
$countStmt10 = $pdo->prepare("SELECT COUNT(*) FROM icd $where10");
$countStmt10->execute($params10);
$count10 = $countStmt10->fetchColumn();
$totalPages10 = ceil($count10 / $limit);
$stmt10 = $pdo->prepare("SELECT * FROM icd $where10 ORDER BY id DESC LIMIT $limit OFFSET $offset10");
$stmt10->execute($params10);
$rows10 = $stmt10->fetchAll();

// --- ICD 9 ---
$page9 = isset($_GET['page9']) ? max(1, intval($_GET['page9'])) : 1;
$offset9 = ($page9 - 1) * $limit;
$search9 = isset($_GET['search9']) ? trim($_GET['search9']) : '';
$where9 = "WHERE jenis='ICD 9'";
$params9 = [];
if ($search9 !== '') {
    $where9 .= " AND (nama_diagnosa LIKE ? OR icd_code LIKE ?)";
    $params9 = ["%$search9%", "%$search9%"];
}
$countStmt9 = $pdo->prepare("SELECT COUNT(*) FROM icd $where9");
$countStmt9->execute($params9);
$count9 = $countStmt9->fetchColumn();
$totalPages9 = ceil($count9 / $limit);
$stmt9 = $pdo->prepare("SELECT * FROM icd $where9 ORDER BY id DESC LIMIT $limit OFFSET $offset9");
$stmt9->execute($params9);
$rows9 = $stmt9->fetchAll();


include __DIR__ . '/../../includes/header.php';
?>

<div x-data="modal()" class="bg-gray-100 p-4" x-cloak>
    <div class="flex flex-wrap gap-2 justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Data ICD</h1>
        <div class="flex gap-2">
            <a href="?export=excel" class="bg-green-600 hover:bg-green-500 text-white px-4 py-2 rounded transition">Ekspor ke Excel</a>
            <!-- Reset Semua: hapus semua query param -->
            <a href="index.php" class="bg-gray-500 hover:bg-gray-400 text-white px-4 py-2 rounded transition">Reset Semua</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Kolom ICD 10 -->
        <div class="bg-white p-4 rounded shadow border">
            <div class="flex justify-between mb-4 items-center">
                <h2 class="text-lg font-bold">ICD 10</h2>
                <button @click="openAdd('ICD 10')" class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">+ Tambah</button>
            </div>
            <!-- Search ICD 10 -->
            <form method="GET" class="mb-3 flex gap-2">
                <!-- Pertahankan state kolom ICD 9 -->
                <input type="hidden" name="page9" value="<?= $page9 ?>">
                <input type="hidden" name="search9" value="<?= htmlspecialchars($search9) ?>">
                <input type="text" name="search10" value="<?= htmlspecialchars($search10) ?>" placeholder="Cari ICD 10..." class="border px-2 py-1 rounded w-full">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded">Cari</button>
                <!-- Reset kolom ICD 10 saja -->
                <a href="?page10=1&page9=<?= $page9 ?>&search9=<?= urlencode($search9) ?>" class="bg-gray-400 hover:bg-gray-300 text-white px-3 py-1 rounded">Reset</a>
            </form>
            <table class="min-w-full text-sm">
                <thead class="bg-gray-100 text-gray-700 font-semibold">
                    <tr>
                        <th class="px-2 py-1">No</th>
                        <th class="px-2 py-1">Nama Diagnosa</th>
                        <th class="px-2 py-1">ICD</th>
                        <th class="px-2 py-1">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    <?php $no=$offset10+1; foreach($rows10 as $r): ?>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-2 py-1"><?= $no++ ?></td>
                            <td class="px-2 py-1"><?= htmlspecialchars($r['nama_diagnosa']) ?></td>
                            <td class="px-2 py-1"><?= htmlspecialchars($r['icd_code']) ?></td>
                            <td class="px-2 py-1 space-x-1">
                                <a href="#" @click.prevent="openEdit(<?= htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8') ?>)" class="text-blue-600 hover:underline">Edit</a>
                                <a href="#" @click.prevent="confirmDelete(<?= $r['id'] ?>)" class="text-red-600 hover:underline">Hapus</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(empty($rows10)): ?>
                        <tr><td colspan="4" class="text-center py-4 text-gray-500">Tidak ada data.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <!-- Pagination ICD 10 -->
            <div class="flex justify-between items-center mt-2 text-sm">
                <div>Total: <?= $count10 ?> data</div>
                <div class="space-x-1">
                    <?php for($i=1;$i<=$totalPages10;$i++): ?>
                        <a href="?page10=<?= $i ?>&page9=<?= $page9 ?>&search10=<?= urlencode($search10) ?>&search9=<?= urlencode($search9) ?>" class="px-3 py-1 border rounded <?= $i==$page10?'bg-blue-600 text-white':'hover:bg-gray-100' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <!-- Kolom ICD 9 -->
        <div class="bg-white p-4 rounded shadow border">
            <div class="flex justify-between mb-4 items-center">
                <h2 class="text-lg font-bold">ICD 9</h2>
                <button @click="openAdd('ICD 9')" class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">+ Tambah</button>
            </div>
            <!-- Search ICD 9 -->
            <form method="GET" class="mb-3 flex gap-2">
                <!-- Pertahankan state kolom ICD 10 -->
                <input type="hidden" name="page10" value="<?= $page10 ?>">
                <input type="hidden" name="search10" value="<?= htmlspecialchars($search10) ?>">
                <input type="text" name="search9" value="<?= htmlspecialchars($search9) ?>" placeholder="Cari ICD 9..." class="border px-2 py-1 rounded w-full">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded">Cari</button>
                <!-- Reset kolom ICD 9 saja -->
                <a href="?page9=1&page10=<?= $page10 ?>&search10=<?= urlencode($search10) ?>" class="bg-gray-400 hover:bg-gray-300 text-white px-3 py-1 rounded">Reset</a>
            </form>
            <table class="min-w-full text-sm">
                <thead class="bg-gray-100 text-gray-700 font-semibold">
                    <tr>
                        <th class="px-2 py-1">No</th>
                        <th class="px-2 py-1">Nama Diagnosa</th>
                        <th class="px-2 py-1">ICD</th>
                        <th class="px-2 py-1">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    <?php $no=$offset9+1; foreach($rows9 as $r): ?>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-2 py-1"><?= $no++ ?></td>
                            <td class="px-2 py-1"><?= htmlspecialchars($r['nama_diagnosa']) ?></td>
                            <td class="px-2 py-1"><?= htmlspecialchars($r['icd_code']) ?></td>
                            <td class="px-2 py-1 space-x-1">
                                <a href="#" @click.prevent="openEdit(<?= htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8') ?>)" class="text-blue-600 hover:underline">Edit</a>
                                <a href="#" @click.prevent="confirmDelete(<?= $r['id'] ?>)" class="text-red-600 hover:underline">Hapus</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(empty($rows9)): ?>
                        <tr><td colspan="4" class="text-center py-4 text-gray-500">Tidak ada data.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <!-- Pagination ICD 9 -->
            <div class="flex justify-between items-center mt-2 text-sm">
                <div>Total: <?= $count9 ?> data</div>
                <div class="space-x-1">
                    <?php for($i=1;$i<=$totalPages9;$i++): ?>
                        <a href="?page9=<?= $i ?>&page10=<?= $page10 ?>&search10=<?= urlencode($search10) ?>&search9=<?= urlencode($search9) ?>" class="px-3 py-1 border rounded <?= $i==$page9?'bg-blue-600 text-white':'hover:bg-gray-100' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
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

                <label class="block">
                    <span class="text-sm">Jenis ICD</span>
                    <select name="jenis" x-model="form.jenis" class="border p-2 rounded w-full" required>
                        <option value="ICD 10">ICD 10</option>
                        <option value="ICD 9">ICD 9</option>
                    </select>
                </label>

                <input type="text" name="nama_diagnosa" x-model="form.nama_diagnosa" placeholder="Nama Diagnosa/Prosedur" required class="border p-2 rounded">
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
        form: { id: '', nama_diagnosa: '', icd_code: '', keterangan: '', jenis: 'ICD 10' },

        openAdd(jenis) {
            this.aksi = 'tambah';
            this.formTitle = 'Tambah ' + jenis;
            this.form = { id: '', nama_diagnosa: '', icd_code: '', keterangan: '', jenis: jenis };
            this.showModal = true;
        },
        openEdit(d) {
            this.aksi = 'edit';
            this.formTitle = 'Edit ' + d.jenis;
            this.form = { id: d.id, nama_diagnosa: d.nama_diagnosa, icd_code: d.icd_code, keterangan: d.keterangan, jenis: d.jenis };
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
