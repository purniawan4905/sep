<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../config/database.php';

$error = '';
$success = '';

$namaBulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

// Ambil bulan & tahun dari GET atau default ke saat ini
$bulanDipilih = isset($_GET['bulan']) ? intval($_GET['bulan']) : date('n');
$tahunDipilih = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');

// Query data sesuai bulan dan tahun yang dipilih
$stmt = $pdo->prepare("SELECT * FROM jadwal_dokter WHERE MONTH(tanggal) = :bulan AND YEAR(tanggal) = :tahun ORDER BY tanggal ASC");
$stmt->execute([
    ':bulan' => $bulanDipilih,
    ':tahun' => $tahunDipilih
]);
$dataJadwal = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Gunakan data yang sudah difilter
$data = $dataJadwal;

// Ambil daftar dokter unik
$dokterList = $pdo->query("SELECT DISTINCT nama_dokter FROM jadwal_dokter ORDER BY nama_dokter")->fetchAll(PDO::FETCH_COLUMN);

// Tambah atau Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $tanggal = $_POST['tanggal'];
    $hari = $_POST['hari'];
    $shift = $_POST['shift'];
    $lokasi = $_POST['lokasi'];
    $nama = $_POST['nama_dokter'];

    if ($id) {
        $stmt = $pdo->prepare("UPDATE jadwal_dokter SET tanggal=?, hari=?, shift=?, lokasi=?, nama_dokter=? WHERE id=?");
        $stmt->execute([$tanggal, $hari, $shift, $lokasi, $nama, $id]);
        header("Location: index.php?bulan=$bulanDipilih&tahun=$tahunDipilih&edit_sukses=1");
        exit;
    } else {
        $stmt = $pdo->prepare("INSERT INTO jadwal_dokter (tanggal, hari, shift, lokasi, nama_dokter) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$tanggal, $hari, $shift, $lokasi, $nama]);
        header("Location: index.php?bulan=$bulanDipilih&tahun=$tahunDipilih&tambah_sukses=1");
        exit;
    }
}

// Hapus
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    // Ambil data untuk ditampilkan di alert
    $stmt = $pdo->prepare("SELECT * FROM jadwal_dokter WHERE id = ?");
    $stmt->execute([$id]);
    $dataHapus = $stmt->fetch();
    
    $stmt = $pdo->prepare("DELETE FROM jadwal_dokter WHERE id = ?");
    $stmt->execute([$id]);
    
    // Redirect dengan parameter sukses
    header("Location: index.php?bulan=$bulanDipilih&tahun=$tahunDipilih&hapus_sukses=1");
    exit();
}
?>

<?php include_once __DIR__ . '/../../includes/header.php'; ?>

<!-- SweetAlert2 CSS & JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
/* Custom SweetAlert Styling */
.swal2-popup {
    border-radius: 20px !important;
    padding: 2rem !important;
}

.swal2-title {
    font-family: 'Inter', sans-serif !important;
    font-weight: 600 !important;
}

.swal2-html-container {
    font-family: 'Inter', sans-serif !important;
}

.swal2-confirm {
    border-radius: 12px !important;
    padding: 12px 24px !important;
    font-weight: 600 !important;
}

.swal2-cancel {
    border-radius: 12px !important;
    padding: 12px 24px !important;
    font-weight: 600 !important;
}

.swal2-timer-progress-bar {
    background: linear-gradient(90deg, #667eea, #764ba2) !important;
}

/* Styling untuk tombol aksi */
.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    transition: all 0.2s ease;
    margin: 0 4px;
}

.action-btn.edit {
    background-color: #e6f2ff;
    color: #0066cc;
}

.action-btn.edit:hover {
    background-color: #0066cc;
    color: white;
    transform: scale(1.1);
}

.action-btn.delete {
    background-color: #ffe6e6;
    color: #cc0000;
}

.action-btn.delete:hover {
    background-color: #cc0000;
    color: white;
    transform: scale(1.1);
}

/* Styling untuk badge */
.badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 12px;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    line-height: 1.5;
}

.badge-pagi {
    background-color: #fef3c7;
    color: #92400e;
}

.badge-siang {
    background-color: #dbeafe;
    color: #1e40af;
}

.badge-malam {
    background-color: #1f2937;
    color: white;
}

.badge-igd {
    background-color: #fee2e2;
    color: #991b1b;
}

.badge-bangsal {
    background-color: #dcfce7;
    color: #166534;
}

/* Hover effect untuk baris tabel */
.table-row-hover:hover {
    background-color: #f9fafb;
}

/* Animasi untuk modal */
.modal-enter-active {
    animation: modalFadeIn 0.3s ease;
}

@keyframes modalFadeIn {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(-10px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}
</style>

<div x-data="jadwalDokter()" class="container mx-auto p-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h1 class="text-3xl font-semibold text-gray-800">Jadwal Dokter IGD</h1>
        <div class="flex gap-2">
            <a href="cetak_pdf.php?bulan=<?= $bulanDipilih ?>&tahun=<?= $tahunDipilih ?>" 
               target="_blank"
               class="bg-green-600 hover:bg-green-700 text-white font-medium px-5 py-2.5 rounded-lg shadow flex items-center gap-2 transition">
                <i class="fas fa-print"></i>
                Cetak PDF
            </a>
            <button @click="openForm()" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2.5 rounded-lg shadow flex items-center gap-2 transition">
                <i class="fas fa-plus"></i>
                Tambah Jadwal
            </button>
        </div>
    </div>

    <!-- Jadwal Bulanan -->
    <div class="mb-8 overflow-auto rounded-lg border border-gray-300">
        <form method="GET" class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b">
            <h2 class="text-xl font-semibold text-gray-700">
                Rekap Jadwal IGD <?= $namaBulan[$bulanDipilih] . ' ' . $tahunDipilih ?>
            </h2>
            <div class="flex items-center gap-2">
                <select name="bulan" class="border rounded px-2 py-1 text-sm" onchange="this.form.submit()">
                    <?php for ($b = 1; $b <= 12; $b++): ?>
                        <option value="<?= $b ?>" <?= $b === $bulanDipilih ? 'selected' : '' ?>>
                            <?= $namaBulan[$b] ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <select name="tahun" class="border rounded px-2 py-1 text-sm" onchange="this.form.submit()">
                    <?php for ($t = date('Y') - 1; $t <= date('Y') + 2; $t++): ?>
                        <option value="<?= $t ?>" <?= $t === $tahunDipilih ? 'selected' : '' ?>>
                            <?= $t ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <a href="cetak_pdf.php?bulan=<?= $bulanDipilih ?>&tahun=<?= $tahunDipilih ?>" 
                   target="_blank"
                   class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded text-sm flex items-center gap-1 transition">
                    <i class="fas fa-file-pdf"></i>
                    PDF
                </a>
            </div>
        </form>
        
        <?php if (empty($dataJadwal)): ?>
            <div class="text-center py-8 bg-gray-50">
                <i class="fas fa-calendar-times text-gray-400 text-5xl mb-3"></i>
                <p class="text-gray-500">Tidak ada jadwal untuk bulan <?= $namaBulan[$bulanDipilih] . ' ' . $tahunDipilih ?></p>
                <button @click="openForm()" class="mt-3 text-blue-600 hover:text-blue-800 transition">
                    <i class="fas fa-plus-circle mr-1"></i>Tambah Jadwal
                </button>
            </div>
        <?php else: ?>
            <table class="table-auto text-sm w-full text-center border-collapse">
                <thead class="bg-gray-100 text-xs">
                    <tr>
                        <th class="border px-2 py-1" rowspan="2">Tanggal</th>
                        <th class="border px-2 py-1" rowspan="2">Hari</th>
                        <th class="border px-2 py-1" colspan="2">PAGI</th>
                        <th class="border px-2 py-1" colspan="2">SIANG</th>
                        <th class="border px-2 py-1" colspan="2">MALAM</th>
                    </tr>
                    <tr>
                        <th class="border px-2 py-1">Bangsal</th>
                        <th class="border px-2 py-1">IGD</th>
                        <th class="border px-2 py-1">Bangsal</th>
                        <th class="border px-2 py-1">IGD</th>
                        <th class="border px-2 py-1">Bangsal</th>
                        <th class="border px-2 py-1">IGD</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $grouped = [];
                    foreach ($dataJadwal as $row) {
                        $grouped[$row['tanggal']][$row['shift']][$row['lokasi']] = $row['nama_dokter'];
                    }
                    ksort($grouped);

                    foreach ($grouped as $tgl => $shifts):
                        $timestamp = strtotime($tgl);
                        $tanggal = date('j', $timestamp);
                        $hariInggris = date('l', $timestamp);
                        $namaHari = [
                            'Sunday' => 'Minggu',
                            'Monday' => 'Senin',
                            'Tuesday' => 'Selasa',
                            'Wednesday' => 'Rabu',
                            'Thursday' => 'Kamis',
                            'Friday' => 'Jumat',
                            'Saturday' => 'Sabtu'
                        ];
                        $hariIndonesia = $namaHari[$hariInggris];
                        $rowBg = 'bg-white';
                        if ($hariInggris == 'Sunday') $rowBg = 'bg-red-50';
                        elseif ($hariInggris == 'Saturday') $rowBg = 'bg-blue-50';
                    ?>
                        <tr class="<?= $rowBg ?> hover:bg-yellow-50 transition">
                            <td class="border px-2 py-1 font-medium"><?= $tanggal ?></td>
                            <td class="border px-2 py-1"><?= $hariIndonesia ?></td>
                            <?php foreach (['PAGI', 'SIANG', 'MALAM'] as $shift): ?>
                                <?php foreach (['BANGSAL', 'IGD'] as $lokasi): ?>
                                    <?php $dokter = $shifts[$shift][$lokasi] ?? '-' ?>
                                    <td class="border px-2 py-1 <?= $dokter == '-' ? 'text-gray-400' : 'text-gray-700' ?>">
                                        <?= htmlspecialchars($dokter) ?>
                                    </td>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="text-right text-xs text-gray-500 mt-2 px-2">
                Total: <?= count($dataJadwal) ?> entri jadwal
            </div>
        <?php endif; ?>
    </div>

    <!-- Tabel Detail -->
    <div class="overflow-auto rounded-lg shadow border border-gray-200">
        <div class="bg-gray-50 px-4 py-2 border-b flex justify-between items-center">
            <h3 class="font-semibold text-gray-700">Detail Jadwal</h3>
            <span class="text-xs text-gray-500">Menampilkan data bulan <?= $namaBulan[$bulanDipilih] . ' ' . $tahunDipilih ?></span>
        </div>
        <table class="w-full text-sm text-left border-collapse">
            <thead class="bg-blue-50 text-gray-700">
                <tr>
                    <th class="border px-4 py-2 text-center w-16">No</th>
                    <th class="border px-4 py-2">Tanggal</th>
                    <th class="border px-4 py-2">Hari</th>
                    <th class="border px-4 py-2">Shift</th>
                    <th class="border px-4 py-2">Lokasi</th>
                    <th class="border px-4 py-2">Nama Dokter</th>
                    <th class="border px-4 py-2 text-center w-24">Aksi</th>
                </tr>
            </thead>
            <tbody class="text-gray-700 bg-white">
                <?php if (empty($dataJadwal)): ?>
                    <tr>
                        <td colspan="7" class="border px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-folder-open text-3xl mb-2 block text-gray-300"></i>
                            Tidak ada data untuk ditampilkan
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($dataJadwal as $row): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="border px-4 py-2 text-center font-medium"><?= $no++ ?></td>
                        <td class="border px-4 py-2"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                        <td class="border px-4 py-2"><?= htmlspecialchars($row['hari']) ?></td>
                        <td class="border px-4 py-2">
                            <span class="badge 
                                <?= $row['shift'] == 'PAGI' ? 'badge-pagi' : '' ?>
                                <?= $row['shift'] == 'SIANG' ? 'badge-siang' : '' ?>
                                <?= $row['shift'] == 'MALAM' ? 'badge-malam' : '' ?>">
                                <?= $row['shift'] ?>
                            </span>
                        </td>
                        <td class="border px-4 py-2">
                            <span class="badge 
                                <?= $row['lokasi'] == 'IGD' ? 'badge-igd' : 'badge-bangsal' ?>">
                                <?= $row['lokasi'] ?>
                            </span>
                        </td>
                        <td class="border px-4 py-2 font-medium"><?= htmlspecialchars($row['nama_dokter']) ?></td>
                        <td class="border px-4 py-2 text-center">
                            <div class="flex justify-center items-center">
                                <!-- Tombol Edit -->
                                <button @click="editForm(<?= htmlspecialchars(json_encode($row)) ?>)" 
                                        class="action-btn edit mx-1" 
                                        title="Edit Data">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <!-- Tombol Hapus -->
                                <button @click="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars($row['nama_dokter']) ?>', '<?= date('d/m/Y', strtotime($row['tanggal'])) ?>', '<?= $row['shift'] ?>', '<?= $row['lokasi'] ?>')" 
                                        class="action-btn delete mx-1"
                                        title="Hapus Data">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if (!empty($dataJadwal)): ?>
        <div class="bg-gray-50 px-4 py-2 border-t text-xs text-gray-500 flex justify-between items-center">
            <span>Total: <?= count($dataJadwal) ?> entri jadwal</span>
            <span>
                <i class="fas fa-edit text-blue-600 mx-1"></i> Edit 
                <i class="fas fa-trash text-red-600 mx-1 ml-2"></i> Hapus
            </span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal Form -->
    <div x-show="showForm" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 p-4" x-cloak x-transition>
        <div class="bg-white w-full max-w-xl rounded-lg shadow-lg p-6 space-y-4 modal-enter-active">
            <h2 class="text-xl font-semibold text-gray-800" x-text="form.id ? 'Edit Jadwal' : 'Tambah Jadwal'"></h2>
            <form method="POST" @submit="showForm = false" class="space-y-4">
                <input type="hidden" name="id" x-model="form.id">
                <input type="hidden" name="bulan" value="<?= $bulanDipilih ?>">
                <input type="hidden" name="tahun" value="<?= $tahunDipilih ?>">

                <div>
                    <label class="block mb-1 text-sm font-medium">Tanggal</label>
                    <input type="date" name="tanggal" id="form-tanggal" x-model="form.tanggal" required 
                           class="w-full border rounded p-2 focus:ring-2 focus:ring-blue-300 focus:border-blue-400 outline-none">
                </div>

                <div>
                    <label class="block mb-1 text-sm font-medium">Hari</label>
                    <input type="text" name="hari" id="form-hari" x-model="form.hari" readonly 
                           class="w-full border p-2 bg-gray-100 rounded text-gray-600">
                </div>

                <div>
                    <label class="block mb-1 text-sm font-medium">Shift</label>
                    <select name="shift" x-model="form.shift" required 
                            class="w-full border p-2 rounded focus:ring-2 focus:ring-blue-300 focus:border-blue-400 outline-none">
                        <option value="">Pilih Shift</option>
                        <option value="PAGI">PAGI</option>
                        <option value="SIANG">SIANG</option>
                        <option value="MALAM">MALAM</option>
                    </select>
                </div>

                <div>
                    <label class="block mb-1 text-sm font-medium">Lokasi</label>
                    <select name="lokasi" x-model="form.lokasi" required 
                            class="w-full border p-2 rounded focus:ring-2 focus:ring-blue-300 focus:border-blue-400 outline-none">
                        <option value="">Pilih Lokasi</option>
                        <option value="BANGSAL">BANGSAL</option>
                        <option value="IGD">IGD</option>
                    </select>
                </div>

                <div>
                    <label class="block mb-1 text-sm font-medium">Nama Dokter</label>
                    <input type="text" name="nama_dokter" x-model="form.nama_dokter" list="list-dokter" required 
                           class="w-full border p-2 rounded focus:ring-2 focus:ring-blue-300 focus:border-blue-400 outline-none"
                           placeholder="Ketik atau pilih nama dokter">
                    <datalist id="list-dokter">
                        <?php foreach ($dokterList as $nama): ?>
                            <option value="<?= htmlspecialchars($nama) ?>">
                        <?php endforeach ?>
                    </datalist>
                </div>

                <div class="flex justify-end pt-4 border-t">
                    <button type="button" @click="showForm = false" 
                            class="mr-2 px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded transition">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded transition flex items-center gap-1">
                        <i class="fas fa-save"></i>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Alpine.js -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<script>
function jadwalDokter() {
    return {
        showForm: false,
        form: {
            id: '',
            tanggal: '',
            hari: '',
            shift: '',
            lokasi: '',
            nama_dokter: ''
        },
        openForm() {
            this.form = { id: '', tanggal: '', hari: '', shift: '', lokasi: '', nama_dokter: '' };
            this.showForm = true;
            setTimeout(() => {
                const tanggalField = document.getElementById('form-tanggal');
                if (tanggalField) tanggalField.focus();
            }, 100);
        },
        editForm(row) {
            this.form = { ...row };
            this.showForm = true;
            
            // SweetAlert untuk konfirmasi edit
            Swal.fire({
                icon: 'info',
                title: 'Edit Jadwal',
                text: `Anda akan mengedit jadwal ${row.nama_dokter}`,
                showConfirmButton: false,
                timer: 1500,
                position: 'top-end',
                toast: true,
                background: '#fff',
                timerProgressBar: true
            });
        },
        confirmDelete(id, namaDokter, tanggal, shift, lokasi) {
            Swal.fire({
                title: 'Hapus Jadwal?',
                html: `
                    <div class="text-left">
                        <p class="mb-2">Anda akan menghapus jadwal berikut:</p>
                        <div class="bg-red-50 p-3 rounded-lg">
                            <p><strong>Dokter:</strong> ${namaDokter}</p>
                            <p><strong>Tanggal:</strong> ${tanggal}</p>
                            <p><strong>Shift:</strong> ${shift}</p>
                            <p><strong>Lokasi:</strong> ${lokasi}</p>
                        </div>
                        <p class="mt-3 text-red-600 font-semibold">Data yang dihapus tidak dapat dikembalikan!</p>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                background: '#fff',
                backdrop: 'rgba(0,0,0,0.5)',
                customClass: {
                    popup: 'rounded-2xl',
                    title: 'text-xl font-bold',
                    htmlContainer: 'text-sm',
                    confirmButton: 'px-6 py-2 rounded-lg',
                    cancelButton: 'px-6 py-2 rounded-lg'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location = `?hapus=${id}&bulan=<?= $bulanDipilih ?>&tahun=<?= $tahunDipilih ?>`;
                }
            });
        }
    };
}

document.addEventListener("DOMContentLoaded", () => {
    const tanggal = document.getElementById('form-tanggal');
    const hari = document.getElementById('form-hari');
    
    if (tanggal) {
        tanggal.addEventListener('change', function () {
            if (this.value) {
                const tgl = new Date(this.value + 'T00:00:00');
                const hariIndo = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                hari.value = hariIndo[tgl.getDay()];
            }
        });
    }
});
</script>

<!-- SweetAlert untuk Notifikasi CRUD -->
<?php if (isset($_GET['tambah_sukses'])): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Jadwal berhasil ditambahkan',
            timer: 2000,
            showConfirmButton: false,
            position: 'center',
            background: '#fff',
            timerProgressBar: true,
            customClass: {
                popup: 'rounded-2xl',
                title: 'text-green-600 font-bold',
                timerProgressBar: 'bg-green-500'
            }
        });
    });
</script>
<?php endif; ?>

<?php if (isset($_GET['edit_sukses'])): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Jadwal berhasil diperbarui',
            timer: 2000,
            showConfirmButton: false,
            position: 'center',
            background: '#fff',
            timerProgressBar: true,
            customClass: {
                popup: 'rounded-2xl',
                title: 'text-blue-600 font-bold',
                timerProgressBar: 'bg-blue-500'
            }
        });
    });
</script>
<?php endif; ?>

<?php if (isset($_GET['hapus_sukses'])): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil Dihapus!',
            text: 'Jadwal telah dihapus dari sistem',
            timer: 2000,
            showConfirmButton: false,
            position: 'center',
            background: '#fff',
            timerProgressBar: true,
            customClass: {
                popup: 'rounded-2xl',
                title: 'text-red-600 font-bold',
                timerProgressBar: 'bg-red-500'
            }
        });
    });
</script>
<?php endif; ?>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?>