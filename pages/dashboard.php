<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';
include __DIR__ . '/../includes/header.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: /pencatatansep/login.php');
    exit();
}

// Ambil data total record
$count = $pdo->query("SELECT COUNT(*) FROM records")->fetchColumn();

$chart = $pdo->query("
    SELECT 
        DATE_FORMAT(tanggal_masuk, '%Y-%m') as bulan,
        COUNT(*) as total
    FROM records
    GROUP BY bulan
    ORDER BY bulan
")->fetchAll(PDO::FETCH_ASSOC);

$labels = array_column($chart, 'bulan');
$values = array_column($chart, 'total');

// Rename agar lebih spesifik untuk grafik tren
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

// Ambil data pasien yang masuk hari ini
$today = date('Y-m-d');
$stmtToday = $pdo->prepare("SELECT * FROM records WHERE DATE(tanggal_masuk) = ?");
$stmtToday->execute([$today]);
$todayPatients = $stmtToday->fetchAll();

// Ambil jumlah pasien masuk hari ini
$today = date('Y-m-d');
$stmtNotif = $pdo->prepare("SELECT COUNT(*) FROM records WHERE tanggal_masuk = ?");
$stmtNotif->execute([$today]);
$pasienHariIni = $stmtNotif->fetchColumn();

// Mencegah akses dari tombol Back setelah logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-extrabold text-gray-800">Dashboard</h1>

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

        <!-- Chart card -->
        <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-100 hover:shadow-xl transition duration-200">
            <div class="text-gray-600 font-semibold text-lg mb-4">Visualisasi Data</div>
            <canvas id="recordsChart" class="w-full h-64"></canvas>
        </div>
    </div>
</div>

<div class="bg-white rounded shadow p-6 mb-6 mt-6 border border-gray-100 hover:shadow-xl transition duration-200">
    <h2 class="text-xl font-bold mb-4">Tren Masuk Pasien per Bulan</h2>
    <canvas id="trendChart" height="100"></canvas>
</div>

<!-- ======== GRAFIK DIAGNOSA ======== -->
  <div class="mt-6 bg-white p-4 rounded shadow border border-gray-100 hover:shadow-xl transition duration-200">
    <h2 class="text-xl font-bold mb-2">Grafik 5 Diagnosa dengan Total Tarif Tertinggi</h2>
    <canvas id="chartDiagnosa" height="120"></canvas>
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
<script>
        /* ---------- BAR: Total Records ---------- */
        const recCtx = document.getElementById('recordsChart').getContext('2d');
        new Chart(recCtx, {
        type:'bar',
        data:{
            labels:['Total Data'],
            datasets:[{
            label:'Jumlah Records',
            data:[<?= $count ?>],
            backgroundColor:'#3b82f6',
            borderRadius:6
            }]
        },
        options:{
            responsive:true,
            plugins:{
            legend:{display:false},
            tooltip:{callbacks:{label:ctx=>' '+ctx.parsed.y+' record'}}
            },
            scales:{ y:{beginAtZero:true,ticks:{stepSize:1}} }
        }
        });

        /* ---------- LINE: Tren Pasien Masuk ---------- */
        const trenCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trenCtx, {
        type:'line',
        data:{
            labels:<?= json_encode($trendlabels) ?>,
            datasets:[{
            label:'Jumlah Pasien Masuk',
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

        /* 5 diagnosa dengan tarif tertinggi */
        <?php if (!empty($diagLabels)): ?>

        const ctxDiagnosa = document.getElementById('chartDiagnosa').getContext('2d');
        new Chart(ctxDiagnosa, {
            type: 'bar',
            data: {
                labels: <?= json_encode($diagLabels) ?>,
                datasets: [
                    {
                        label: 'Kelas 1',
                        data: <?= json_encode($kelas1) ?>,
                        backgroundColor: '#3b82f6'
                    },
                    {
                        label: 'Kelas 2',
                        data: <?= json_encode($kelas2) ?>,
                        backgroundColor: '#10b981'
                    },
                    {
                        label: 'Kelas 3',
                        data: <?= json_encode($kelas3) ?>,
                        backgroundColor: '#f59e0b'
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: ctx => 'Rp ' + Number(ctx.parsed.y).toLocaleString('id-ID')
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: v => 'Rp ' + v.toLocaleString('id-ID')
                        }
                    }
                }
            }
        });

        <?php else: ?>
            console.warn('Data chart diagnosa tidak tersedia');
        <?php endif; ?>
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
