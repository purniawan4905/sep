<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /pencatatansep/login.php');
    exit();
}

$showDisclaimer = false;
// cek apakah baru login
if (empty($_SESSION['just_logged_in'])) {
    $_SESSION['just_logged_in'] = true;
    $showDisclaimer = true;
}


// Ambil bulan dan tahun dari GET atau default
$selectedMonth = $_GET['bulan'] ?? date('m');
$selectedYear  = $_GET['tahun'] ?? date('Y');
$selectedMonthYear = $selectedYear . '-' . $selectedMonth;

// Ambil data total record
$count = $pdo->query("SELECT COUNT(*) FROM records")->fetchColumn();

// Grafik tren per bulan
$chart = $pdo->query("
    SELECT DATE_FORMAT(tanggal_masuk, '%Y-%m') as bulan, COUNT(*) as total
    FROM records
    GROUP BY bulan
    ORDER BY bulan
")->fetchAll(PDO::FETCH_ASSOC);
$labels = array_column($chart, 'bulan');
$values = array_column($chart, 'total');
$trendlabels = $labels;
$trendvalues = $values;

// Grafik Diagnosa
$stmt = $pdo->query("
    SELECT diagnosa, kelas1, kelas2, kelas3
    FROM diagnosa
    ORDER BY (kelas1 + kelas2 + kelas3) DESC
    LIMIT 5
");
$diagData = $stmt->fetchAll(PDO::FETCH_ASSOC);
$diagLabels = array_column($diagData, 'diagnosa');
$kelas1 = array_column($diagData, 'kelas1');
$kelas2 = array_column($diagData, 'kelas2');
$kelas3 = array_column($diagData, 'kelas3');

// Grafik Pasien Harian Bulan Ini
$stmtHarian = $pdo->prepare("
    SELECT DATE(tanggal_masuk) AS tanggal, COUNT(*) AS total
    FROM records
    WHERE DATE_FORMAT(tanggal_masuk, '%Y-%m') = ?
    GROUP BY tanggal
    ORDER BY tanggal
");
$stmtHarian->execute([$selectedMonthYear]);
$dataHarian = $stmtHarian->fetchAll(PDO::FETCH_ASSOC);
$harianLabels = array_map(function($tgl) {
    return date('d M', strtotime($tgl));
}, array_column($dataHarian, 'tanggal'));
$harianValues = array_column($dataHarian, 'total');

$totalPasienBulan = array_sum($harianValues);
$harianPercentages = [];
foreach ($harianValues as $val) {
    $harianPercentages[] = $totalPasienBulan > 0 
        ? round(($val / $totalPasienBulan) * 100, 2)
        : 0;
}

// Pasien hari ini
$today = date('Y-m-d');
$stmtToday = $pdo->prepare("SELECT * FROM records WHERE DATE(tanggal_masuk) = ?");
$stmtToday->execute([$today]);
$todayPatients = $stmtToday->fetchAll();
$stmtNotif = $pdo->prepare("SELECT COUNT(*) FROM records WHERE tanggal_masuk = ?");
$stmtNotif->execute([$today]);
$pasienHariIni = $stmtNotif->fetchColumn();

$selectedMonthPulang = $_GET['bulan_pulang'] ?? date('m');
$selectedYearPulang  = $_GET['tahun_pulang'] ?? date('Y');

// Siapkan data harian pasien keluar
$dataPulangHarian = [];
$jumlahHari = cal_days_in_month(CAL_GREGORIAN, (int)$selectedMonthPulang, (int)$selectedYearPulang);
for ($i = 1; $i <= $jumlahHari; $i++) {
    $tanggal = sprintf('%04d-%02d-%02d', $selectedYearPulang, $selectedMonthPulang, $i);
    $labelsPulangHarian[] = date('d M', strtotime($tanggal)); // contoh: 01 Jan
    $dataPulangHarian[$i] = 0;
}

$stmt = $pdo->prepare("SELECT DAY(tanggal_keluar) AS hari, COUNT(*) AS total 
                       FROM records 
                       WHERE MONTH(tanggal_keluar) = ? AND YEAR(tanggal_keluar) = ?
                       GROUP BY DAY(tanggal_keluar)");
$stmt->execute([$selectedMonthPulang, $selectedYearPulang]);
while ($row = $stmt->fetch()) {
    $dataPulangHarian[(int)$row['hari']] = (int)$row['total'];
}

// Cache Control
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include __DIR__ . '/../includes/header.php';
?>

<!-- Running Text Ramadhan -->
<div class="w-full bg-gradient-to-r from-blue-500 via-indigo-500 to-blue-500 text-white py-2 overflow-hidden relative rounded-lg shadow-md mb-3">
    <marquee behavior="scroll" direction="left"><div class="whitespace-nowrap animate-marquee text-sm md:text-base font-semibold tracking-wide">
        🌙 Selamat Menunaikan Ibadah Puasa, Marhaban Ya Ramadhan 🌙 — Semoga amal ibadah kita diterima dan diberikan kelancaran dalam setiap aktivitas 🙏
    </div></marquee>
</div>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-extrabold text-gray-800">Dashboard</h1>

    <?php if ($showDisclaimer): ?>
        <script>
            Swal.fire({
            title: 'Disclaimer ⚠️',
            html: `
                <p class="text-center leading-relaxed">
                Sistem ini hanya untuk kepentingan internal RSU Sebening Kasih.<br><br>
                Dilarang menyalahgunakan data pasien maupun informasi yang ada di dalam sistem.<br><br>
                <b>Semangat Kerja, Meskipun Jiwa Meronta ronta 😎</b>
                </p>
            `,
            icon: 'info',
            confirmButtonText: 'Siap, Gaskeun 🚀',
            confirmButtonColor: '#0ea5e9',
            background: 'rgba(255, 255, 255, 0.95)',
            color: '#000',
            showClass: {
                popup: 'animate__animated animate__fadeInDown animate__faster'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp animate__faster'
            }
            });
        </script>
        <!-- CDN Animate.css buat efek fade-in -->
        <script src="https://cdn.tailwindcss.com"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
        <?php endif; ?>

    <!-- Jam & Tanggal Besar -->
    <div class="text-right">
        <!-- <div id="big-date" class="text-2xl font-bold text-gray-700"></div> -->
        <div id="big-time" class="text-4xl font-extrabold bg-gradient-to-r from-blue-500 to-indigo-600 bg-clip-text text-transparent drop-shadow-lg tracking-widest animate-pulse"></div>
    </div>

    <!-- Notifikasi -->
        <div class="relative group">
            <button class="bg-white text-gray-600 hover:text-blue-600 p-2 rounded-full shadow-sm border border-gray-200 focus:outline-none relative">
                <!-- Bell Icon -->
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11c0-2.21-1.343-4.105-3.26-4.832A2 2 0 0013 4V3a1 1 0 10-2 0v1a2 2 0 00-1.74 2.168C7.343 6.895 6 8.79 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>

                <!-- Badge warna -->
                <span class="absolute -top-1 -right-1 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white rounded-full
                    <?= $pasienHariIni > 0 ? 'bg-red-600' : 'bg-green-500' ?>">
                    <?= $pasienHariIni ?>
                </span>
            </button>

            <!-- Tooltip -->
            <div class="absolute right-0 mt-2 w-64 bg-white border border-gray-200 rounded shadow-lg text-sm hidden group-hover:block z-50">
                <div class="px-4 py-2 border-b font-semibold text-gray-700">
                    Notifikasi
                </div>
                <div class="p-2 text-gray-600">
                    <?php if ($pasienHariIni > 0): ?>
                        Hari ini ada <span class="font-bold text-red-600"><?= $pasienHariIni ?></span> pasien yang masuk.
                    <?php else: ?>
                        Tidak ada pasien yang masuk hari ini.
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Card total records -->
        <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-100 hover:shadow-xl transition duration-200">
            <div class="flex items-center justify-between mb-4">
                <div class="text-gray-600 font-semibold text-lg">Total Records</div>
                <div class="bg-blue-100 text-blue-600 p-2 rounded-full">
                    <!-- Icon -->
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6a2 2 0 012-2h2a2 2 0 012 2v6m-4 0h.01M6 9v2a2 2 0 002 2h8a2 2 0 002-2V9" />
                    </svg>
                </div>
            </div>
            <div class="text-4xl font-bold text-gray-900"><?= $count ?></div>
             <div class="text-sm text-blue-500 mt-1">🛏️ Pasien Rawat Inap</div>
        </div>

        <!-- Aksi Cepat -->
        <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-100 hover:shadow-xl transition duration-200">
            <div class="text-gray-600 font-semibold text-lg mb-4">Aksi Cepat</div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Tambah Data Rawat Inap -->
                <a href="records/index.php"
                class="flex flex-col items-center justify-center gap-2 bg-blue-600 text-white px-4 py-5 rounded-xl hover:bg-blue-700 transform hover:scale-105 transition duration-300 shadow-lg hover:shadow-blue-300 hover:shadow-xl">
                    <i class="fa fa-procedures text-2xl"></i> 
                    <span>Tambah Rawat Inap</span>
                </a>

                <!-- Tambah Data Diagnosa -->
                <a href="diagnosa/index.php"
                class="flex flex-col items-center justify-center gap-2 bg-green-600 text-white px-4 py-5 rounded-xl hover:bg-green-700 transform hover:scale-105 transition duration-300 shadow-lg hover:shadow-green-300 hover:shadow-xl">
                    <i class="fa fa-stethoscope text-2xl"></i> 
                    <span>Tambah Diagnosa</span>
                </a>

                <!-- Tambah Data ICD -->
                <a href="icd/index.php"
                class="flex flex-col items-center justify-center gap-2 bg-indigo-600 text-white px-4 py-5 rounded-xl hover:bg-indigo-700 transform hover:scale-105 transition duration-300 shadow-lg hover:shadow-indigo-300 hover:shadow-xl">
                    <i class="fa fa-code text-2xl"></i> 
                    <span>Tambah ICD</span>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded shadow p-6 mb-6 mt-6 border border-gray-100 hover:shadow-xl transition duration-200">
    <h2 class="text-xl font-bold mb-4">Tren Masuk Pasien per Bulan</h2>
    <canvas id="trendChart" height="100"></canvas>
</div>

<!-- Grafik Harian -->
<div class="mt-6 bg-white p-4 rounded shadow border border-gray-100 hover:shadow-xl transition duration-200">
    <h2 class="text-xl font-bold">Jumlah Pasien Masuk Harian</h2>
    <form method="get" class="flex gap-2 items-center mb-4">
        <select name="bulan" class="border border-gray-300 rounded p-1 text-sm">
            <?php for ($m = 1; $m <= 12; $m++):
                $val = str_pad($m, 2, '0', STR_PAD_LEFT);
                $label = date('F', mktime(0, 0, 0, $m, 1)); ?>
                <option value="<?= $val ?>" <?= $val == $selectedMonth ? 'selected' : '' ?>><?= $label ?></option>
            <?php endfor; ?>
        </select>
        <select name="tahun" class="border border-gray-300 rounded p-1 text-sm">
            <?php $currentYear = date('Y');
            for ($y = $currentYear; $y >= $currentYear - 5; $y--): ?>
                <option value="<?= $y ?>" <?= $y == $selectedYear ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded text-sm">Tampilkan</button>
    </form>
    <canvas id="chartPasienHarianBulanIni" height="100"></canvas>
</div>

<!-- Grafik Pasien Pulang Harian -->
<div class="mt-6 bg-white p-4 rounded shadow border border-gray-100 hover:shadow-xl transition duration-200">
    <h2 class="text-xl font-bold">Jumlah Pasien Pulang Harian</h2>
    <form method="get" class="flex gap-2 items-center mb-4">
        <select name="bulan_pulang" class="border border-gray-300 rounded p-1 text-sm">
            <?php for ($m = 1; $m <= 12; $m++):
                $val = str_pad($m, 2, '0', STR_PAD_LEFT);
                $label = date('F', mktime(0, 0, 0, $m, 1)); ?>
                <option value="<?= $val ?>" <?= ($val == $selectedMonthPulang) ? 'selected' : '' ?>><?= $label ?></option>
            <?php endfor; ?>
        </select>
        <select name="tahun_pulang" class="border border-gray-300 rounded p-1 text-sm">
            <?php $currentYear = date('Y');
            for ($y = $currentYear; $y >= $currentYear - 5; $y--): ?>
                <option value="<?= $y ?>" <?= ($y == $selectedYearPulang) ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded text-sm">Tampilkan</button>
    </form>
    <canvas id="chartPasienPulangHarian" height="100"></canvas>
</div>

<!-- Grafik Pasien Masuk Per Tanggal -->
<div class="mt-6 bg-white p-4 rounded shadow border border-gray-100 hover:shadow-xl transition duration-200">
    <h2 class="text-xl font-bold mb-2">
        Presentase Pasien Masuk Per Tanggal (<?= date('F Y', strtotime($selectedMonthYear.'-01')) ?>)
    </h2>
    <canvas id="chartPasienPerTanggal" height="120"></canvas>
</div>


<!-- ======== LIST PASIEN MASUK HARI INI ======== -->
<div class="mt-6 bg-white p-4 rounded shadow border border-gray-100 hover:shadow-xl transition duration-200">
    <h2 class="text-xl font-bold mb-2">Pasien Masuk Hari Ini (<?= date('d M Y') ?>)</h2>
    <h5>Total : <?= $pasienHariIni ?></h5>
    
    <?php if (count($todayPatients) > 0): ?>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left font-semibold text-gray-600">No</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-600">No. RM</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-600">Nama</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-600">Tanggal Masuk</th>
                    <th class="px-4 py-2 text-left font-semibold text-gray-600">Keterangan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-gray-800">
                <?php $no = 1; foreach ($todayPatients as $row): ?>
                <tr>
                    <td class="px-4 py-2"><?= $no++ ?></td>
                    <td class="px-4 py-2"><?= htmlspecialchars($row['no_rm']) ?></td>
                    <td class="px-4 py-2"><?= htmlspecialchars($row['nama_pasien']) ?></td>
                    <td class="px-4 py-2"><?= date('d M Y', strtotime($row['tanggal_masuk'])) ?></td>
                    <td class="px-4 py-2"><?= htmlspecialchars($row['keterangan'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <p class="text-gray-500">Tidak ada pasien masuk hari ini.</p>
    <?php endif; ?>
</div>

    <!-- Footer -->
        <footer class="border-t border-gray-200 bg-white text-center text-sm text-gray-500 p-4 mt-6">
        <div class="max-w-6xl mx-auto flex flex-col sm:flex-row justify-between items-center">
          <p>&copy; <?= date('Y') ?> RS Sebening Kasih. All rights reserved.</p>
          <p>Developed with 💙 by <a href="https://purniawan4905.github.io/" target="_blank" class="text-blue-600 hover:underline">Agus Cah Ganteng 😎</a></p>
        </div>
      </footer>


<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script>

        /* Jam & Tanggal Besar */
        function updateTime() {
            const now = new Date();
            document.getElementById('big-time').textContent = now.toLocaleTimeString('id-ID', { 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit' 
            });
        }
        setInterval(updateTime, 1000);
        updateTime();

        /* ---------- LINE: Tren Pasien Masuk ---------- */
        const trenCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trenCtx, {
        type:'line',
        data:{
            labels:<?= json_encode($trendlabels) ?>,
            datasets:[{
            label:'Jumlah Pasien Masuk Per Bulan',
            data:<?= json_encode($trendvalues) ?>,
            fill:true,
            backgroundColor:'rgba(59,130,246,.2)',
            borderColor:'#3b82f6',
            borderWidth:2,
            tension:.3
            }]
        },
        options:{
            responsive:true,
            plugins:{
            legend:{display:true},
            tooltip:{mode:'index',intersect:false}
            }
        }
        });

const ctxTanggal = document.getElementById('chartPasienPerTanggal').getContext('2d');

new Chart(ctxTanggal, {
    type: 'line', // line chart lebih jelas untuk tren naik-turun
    data: {
        labels: <?= json_encode($harianLabels) ?>,
        datasets: [{
            label: 'Persentase Pasien Masuk',
            data: <?= json_encode($harianPercentages) ?>,
            borderColor: '#3643bdff',
            backgroundColor: 'rgba(54,67,189,0.3)',
            fill: true,
            tension: 0.3, // biar agak halus garisnya
            pointRadius: 4,
            pointBackgroundColor: '#3643bdff'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.parsed.y + '%';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                title: {
                    display: true,
                    text: 'Persentase (%)'
                },
                ticks: {
                    callback: function(value) {
                        return value + '%';
                    }
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Tanggal'
                }
            }
        }
    }
});

        /* ---------- LINE: Pasien Masuk Harian Bulan Ini ---------- */
const ctxHarian = document.getElementById('chartPasienHarianBulanIni').getContext('2d');
new Chart(ctxHarian, {
    type: 'line',
    data: {
        labels: <?= json_encode($harianLabels) ?>,
        datasets: [{
            label: 'Jumlah Pasien Masuk Per Hari',
            data: <?= json_encode($harianValues) ?>,
            fill: true,
            backgroundColor: 'rgba(234, 88, 12, 0.2)',
            borderColor: '#ea580c',
            borderWidth: 2,
            tension: 0.3,
            pointRadius: 3,
            pointBackgroundColor: '#ea580c'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            tooltip: {
                callbacks: {
                    label: ctx => ctx.parsed.y + ' pasien'
                }
            },
            legend: {
                display: true
            }
        },
        scales: {
            x: {
                title: {
                    display: true,
                    text: 'Tanggal'
                }
            },
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Jumlah Pasien'
                }
            }
        }
    }
});

const labelsPulang = <?= json_encode($labelsPulangHarian) ?>;
    const dataPulang = <?= json_encode(array_values($dataPulangHarian)) ?>;

    new Chart(document.getElementById('chartPasienPulangHarian').getContext('2d'), {
        type: 'line',
        data: {
            labels: labelsPulang,
            datasets: [{
                label: 'Jumlah Pasien Pulang',
                data: dataPulang,
                backgroundColor: 'rgba(195, 64, 255, 0.2)',
                borderColor: 'rgba(195, 64, 255, 1)',
                fill: true,
                tension: 0.4,
                pointRadius: 2,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Tanggal'
                    },
                    ticks: {
                        maxRotation: 90,
                        minRotation: 45,
                        font: {
                            size: 10
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Jumlah Pasien'
                    }
                }
            }
        }
    });
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
