<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header('Location: ../auth/login.php');
    exit;
}

$periode = $_GET['periode'] ?? 'bulan_ini';
$start_date = '';
$end_date = date('Y-m-d');

switch ($periode) {
    case 'hari_ini':
        $start_date = date('Y-m-d');
        break;
    case 'minggu_ini':
        $start_date = date('Y-m-d', strtotime('monday this week'));
        break;
    case 'bulan_ini':
        $start_date = date('Y-m-01');
        break;
    case 'tahun_ini':
        $start_date = date('Y-01-01');
        break;
    case 'custom':
        $start_date = $_GET['start'] ?? date('Y-m-01');
        $end_date = $_GET['end'] ?? date('Y-m-d');
        break;
}

$stats = [];

$result = query("SELECT COUNT(*) as total FROM kunjungan WHERE DATE(waktu_masuk) BETWEEN '$start_date' AND '$end_date'");
$stats['total_kunjungan'] = fetchOne($result)['total'] ?? 0;

$result = query("SELECT t.jenis_instansi, COUNT(*) as total FROM kunjungan k JOIN tamu t ON k.id_tamu = t.id_tamu WHERE DATE(k.waktu_masuk) BETWEEN '$start_date' AND '$end_date' GROUP BY t.jenis_instansi");
$tipe_stats = fetchAll($result);
$stats['internal'] = 0;
$stats['eksternal'] = 0;
foreach ($tipe_stats as $ts) {
    if ($ts['jenis_instansi'] === 'Internal') $stats['internal'] = $ts['total'];
    else $stats['eksternal'] = $ts['total'];
}

$result = query("SELECT status, COUNT(*) as total FROM janji_temu WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date' GROUP BY status");
$janji_stats = fetchAll($result);
$stats['janji_pending'] = 0;
$stats['janji_disetujui'] = 0;
$stats['janji_ditolak'] = 0;
foreach ($janji_stats as $js) {
    $stats['janji_' . $js['status']] = $js['total'];
}

$result = query("SELECT status, COUNT(*) as total FROM peminjaman WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date' GROUP BY status");
$pinjam_stats = fetchAll($result);
$stats['pinjam_pending'] = 0;
$stats['pinjam_disetujui'] = 0;
$stats['pinjam_ditolak'] = 0;
foreach ($pinjam_stats as $ps) {
    $stats['pinjam_' . $ps['status']] = $ps['total'];
}

$daily_data = fetchAll(query("
    SELECT DATE(waktu_masuk) as tanggal, COUNT(*) as jumlah 
    FROM kunjungan 
    WHERE DATE(waktu_masuk) BETWEEN '$start_date' AND '$end_date'
    GROUP BY DATE(waktu_masuk) 
    ORDER BY tanggal ASC
"));

$keperluan_data = fetchAll(query("
    SELECT jl.nama_layanan, COUNT(*) as jumlah 
    FROM kunjungan k 
    JOIN jenis_layanan jl ON k.id_layanan = jl.id_layanan 
    WHERE DATE(k.waktu_masuk) BETWEEN '$start_date' AND '$end_date'
    GROUP BY jl.nama_layanan 
    ORDER BY jumlah DESC
"));

$divisi_data = fetchAll(query("
    SELECT tujuan as nama_unit, COUNT(*) as jumlah 
    FROM janji_temu 
    WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'
    GROUP BY tujuan 
    ORDER BY jumlah DESC
"));

$instansi_data = fetchAll(query("
    SELECT t.instansi_lain as instansi, COUNT(*) as jumlah 
    FROM kunjungan k 
    JOIN tamu t ON k.id_tamu = t.id_tamu 
    WHERE DATE(k.waktu_masuk) BETWEEN '$start_date' AND '$end_date' AND t.instansi_lain IS NOT NULL AND t.instansi_lain != ''
    GROUP BY t.instansi_lain 
    ORDER BY jumlah DESC 
    LIMIT 10
"));

if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    
    if ($export_type === 'excel') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="laporan_' . $periode . '_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Summary
        fputcsv($output, ['LAPORAN KUNJUNGAN UPA TIK']);
        fputcsv($output, ['Periode: ' . $start_date . ' s/d ' . $end_date]);
        fputcsv($output, []);
        fputcsv($output, ['RINGKASAN']);
        fputcsv($output, ['Total Kunjungan', $stats['total_kunjungan']]);
        fputcsv($output, ['Tamu Internal', $stats['internal']]);
        fputcsv($output, ['Tamu Eksternal', $stats['eksternal']]);
        fputcsv($output, []);
        
        fputcsv($output, ['DATA PER HARI']);
        fputcsv($output, ['Tanggal', 'Jumlah']);
        foreach ($daily_data as $d) {
            fputcsv($output, [$d['tanggal'], $d['jumlah']]);
        }
        fputcsv($output, []);
        
        fputcsv($output, ['DATA PER KEPERLUAN']);
        fputcsv($output, ['Keperluan', 'Jumlah']);
        foreach ($keperluan_data as $k) {
            fputcsv($output, [$k['nama_keperluan'], $k['jumlah']]);
        }
        
        fclose($output);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan & Data - Portal TIK</title>
    <link href="../../assets/css/output.css" rel="stylesheet">
    <link href="../../assets/css/custom.css" rel="stylesheet">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>body { font-family: 'Figtree', system-ui, sans-serif; }</style>
</head>
<body class="bg-slate-100 min-h-screen">

    <?php include '../../includes/sidebar-admin.php'; ?>

    <!-- Main Content -->
    <main class="ml-56 min-h-screen">
        
        <!-- Header -->
        <header class="bg-white border-b border-slate-200 px-8 py-4 flex items-center justify-between sticky top-0 z-40">
            <div>
                <h1 class="text-xl font-bold text-slate-800">Laporan & Data</h1>
                <p class="text-sm text-slate-500">Analisis data kunjungan dan layanan.</p>
            </div>
            <a href="?periode=<?= $periode ?>&start=<?= $start_date ?>&end=<?= $end_date ?>&export=excel" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Export Excel
            </a>
        </header>

        <div class="p-8">
            
            <!-- Filter Periode -->
            <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
                <form method="GET" class="flex flex-wrap items-end gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Periode</label>
                        <select name="periode" onchange="toggleCustomDate(this.value)" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0ea5e9] focus:border-[#0ea5e9]">
                            <option value="hari_ini" <?= $periode === 'hari_ini' ? 'selected' : '' ?>>Hari Ini</option>
                            <option value="minggu_ini" <?= $periode === 'minggu_ini' ? 'selected' : '' ?>>Minggu Ini</option>
                            <option value="bulan_ini" <?= $periode === 'bulan_ini' ? 'selected' : '' ?>>Bulan Ini</option>
                            <option value="tahun_ini" <?= $periode === 'tahun_ini' ? 'selected' : '' ?>>Tahun Ini</option>
                            <option value="custom" <?= $periode === 'custom' ? 'selected' : '' ?>>Custom</option>
                        </select>
                    </div>
                    <div id="customDate" class="<?= $periode !== 'custom' ? 'hidden' : '' ?> flex gap-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Dari</label>
                            <input type="date" name="start" value="<?= $start_date ?>" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0ea5e9] focus:border-[#0ea5e9]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Sampai</label>
                            <input type="date" name="end" value="<?= $end_date ?>" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0ea5e9] focus:border-[#0ea5e9]">
                        </div>
                    </div>
                    <button type="submit" class="bg-[#0ea5e9] hover:bg-[#0284c7] text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        Terapkan
                    </button>
                </form>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="stats-card">
                    <p class="text-sm text-slate-500 mb-1">Total Kunjungan</p>
                    <p class="text-3xl font-bold text-slate-800"><?= number_format($stats['total_kunjungan']) ?></p>
                </div>
                <div class="stats-card">
                    <p class="text-sm text-slate-500 mb-1">Tamu Internal</p>
                    <p class="text-3xl font-bold text-[#0ea5e9]"><?= number_format($stats['internal']) ?></p>
                </div>
                <div class="stats-card">
                    <p class="text-sm text-slate-500 mb-1">Tamu Eksternal</p>
                    <p class="text-3xl font-bold text-[#10b981]"><?= number_format($stats['eksternal']) ?></p>
                </div>
                <div class="stats-card">
                    <p class="text-sm text-slate-500 mb-1">Rata-rata/Hari</p>
                    <?php 
                    $days = max(1, (strtotime($end_date) - strtotime($start_date)) / 86400 + 1);
                    $avg = round($stats['total_kunjungan'] / $days, 1);
                    ?>
                    <p class="text-3xl font-bold text-[#f59e0b]"><?= $avg ?></p>
                </div>
            </div>

            <!-- Charts Row 1 -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                
                <!-- Trend Harian -->
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="text-lg font-bold text-slate-800 mb-4">Trend Kunjungan</h2>
                    <canvas id="trendChart" height="200"></canvas>
                </div>

                <!-- Per Keperluan -->
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="text-lg font-bold text-slate-800 mb-4">Berdasarkan Keperluan</h2>
                    <canvas id="keperluanChart" height="200"></canvas>
                </div>

            </div>

            <!-- Charts Row 2 -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                
                <!-- Per Divisi -->
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="text-lg font-bold text-slate-800 mb-4">Berdasarkan Divisi Tujuan</h2>
                    <canvas id="divisiChart" height="200"></canvas>
                </div>

                <!-- Status Permohonan -->
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="text-lg font-bold text-slate-800 mb-4">Status Permohonan</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <h3 class="text-sm font-medium text-slate-600 mb-3">Janji Temu</h3>
                            <div class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-slate-600">Menunggu</span>
                                    <span class="px-2 py-1 text-xs font-medium rounded bg-amber-100 text-amber-800"><?= $stats['janji_pending'] ?></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-slate-600">Disetujui</span>
                                    <span class="px-2 py-1 text-xs font-medium rounded bg-green-100 text-green-800"><?= $stats['janji_disetujui'] ?></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-slate-600">Ditolak</span>
                                    <span class="px-2 py-1 text-xs font-medium rounded bg-red-100 text-red-800"><?= $stats['janji_ditolak'] ?></span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-slate-600 mb-3">Peminjaman</h3>
                            <div class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-slate-600">Menunggu</span>
                                    <span class="px-2 py-1 text-xs font-medium rounded bg-amber-100 text-amber-800"><?= $stats['pinjam_pending'] ?></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-slate-600">Disetujui</span>
                                    <span class="px-2 py-1 text-xs font-medium rounded bg-green-100 text-green-800"><?= $stats['pinjam_disetujui'] ?></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-slate-600">Ditolak</span>
                                    <span class="px-2 py-1 text-xs font-medium rounded bg-red-100 text-red-800"><?= $stats['pinjam_ditolak'] ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Top Instansi -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200">
                    <h2 class="text-lg font-bold text-slate-800">Top 10 Instansi</h2>
                </div>
                <?php if (empty($instansi_data)): ?>
                <div class="px-6 py-8 text-center text-slate-500">
                    Tidak ada data instansi untuk periode ini.
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase">No</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase">Instansi</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase">Jumlah Kunjungan</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase">Persentase</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php 
                            $no = 1;
                            foreach ($instansi_data as $i): 
                                $persen = $stats['total_kunjungan'] > 0 ? round(($i['jumlah'] / $stats['total_kunjungan']) * 100, 1) : 0;
                            ?>
                            <tr class="table-row-hover">
                                <td class="px-6 py-4 text-sm text-slate-600"><?= $no++ ?></td>
                                <td class="px-6 py-4 text-sm font-medium text-slate-800"><?= htmlspecialchars($i['instansi']) ?></td>
                                <td class="px-6 py-4 text-sm text-slate-600"><?= $i['jumlah'] ?></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-slate-200 rounded-full h-2 max-w-[100px]">
                                            <div class="bg-[#0ea5e9] h-2 rounded-full" style="width: <?= $persen ?>%"></div>
                                        </div>
                                        <span class="text-sm text-slate-600"><?= $persen ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <script>
    function toggleCustomDate(value) {
        document.getElementById('customDate').classList.toggle('hidden', value !== 'custom');
    }

    // Chart data
    const trendLabels = <?= json_encode(array_map(fn($d) => date('d M', strtotime($d['tanggal'])), $daily_data)) ?>;
    const trendData = <?= json_encode(array_map('intval', array_column($daily_data, 'jumlah'))) ?>;
    
    const keperluanLabels = <?= json_encode(array_column($keperluan_data, 'nama_keperluan')) ?>;
    const keperluanData = <?= json_encode(array_map('intval', array_column($keperluan_data, 'jumlah'))) ?>;
    
    const divisiLabels = <?= json_encode(array_column($divisi_data, 'nama_divisi')) ?>;
    const divisiData = <?= json_encode(array_map('intval', array_column($divisi_data, 'jumlah'))) ?>;

    // Trend Chart
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: trendLabels,
            datasets: [{
                label: 'Kunjungan',
                data: trendData,
                borderColor: '#0ea5e9',
                backgroundColor: 'rgba(14, 165, 233, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });

    // Keperluan Chart
    new Chart(document.getElementById('keperluanChart'), {
        type: 'doughnut',
        data: {
            labels: keperluanLabels,
            datasets: [{
                data: keperluanData,
                backgroundColor: ['#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // Divisi Chart
    new Chart(document.getElementById('divisiChart'), {
        type: 'bar',
        data: {
            labels: divisiLabels,
            datasets: [{
                label: 'Kunjungan',
                data: divisiData,
                backgroundColor: '#0ea5e9',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
    </script>

</body>
</html>
