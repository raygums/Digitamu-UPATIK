<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header('Location: ../auth/login.php');
    exit;
}

$user_nama = $_SESSION['user_nama'];

$hour = date('H');
if ($hour < 12) {
    $greeting = 'Selamat Pagi';
} elseif ($hour < 15) {
    $greeting = 'Selamat Siang';
} elseif ($hour < 18) {
    $greeting = 'Selamat Sore';
} else {
    $greeting = 'Selamat Malam';
}

$stats = [
    'total_kunjungan' => 0,
    'kunjungan_bulan' => 0,
    'total_staff' => 0,
    'rata_rata_harian' => 0
];

$result = query("SELECT COUNT(*) as total FROM kunjungan");
$row = fetchOne($result);
$stats['total_kunjungan'] = $row['total'] ?? 0;

$result = query("SELECT COUNT(*) as total FROM kunjungan WHERE DATE_TRUNC('month', waktu_masuk) = DATE_TRUNC('month', CURRENT_DATE)");
$row = fetchOne($result);
$stats['kunjungan_bulan'] = $row['total'] ?? 0;

$result = query("SELECT COUNT(*) as total FROM users WHERE role = 'Staff'");
$row = fetchOne($result);
$stats['total_staff'] = $row['total'] ?? 0;

$result = query("SELECT COALESCE(ROUND(AVG(daily_count)), 0) as avg FROM (SELECT COUNT(*) as daily_count FROM kunjungan WHERE waktu_masuk >= CURRENT_DATE - INTERVAL '30 days' GROUP BY DATE(waktu_masuk)) sub");
$row = fetchOne($result);
$stats['rata_rata_harian'] = (int)($row['avg'] ?? 0);

$keperluan_stats = fetchAll(query("
    SELECT 
        jl.nama_layanan,
        COUNT(k.id_kunjungan) as jumlah
    FROM jenis_layanan jl
    LEFT JOIN kunjungan k ON jl.id_layanan = k.id_layanan 
        AND DATE_TRUNC('month', k.waktu_masuk) = DATE_TRUNC('month', CURRENT_DATE)
    GROUP BY jl.id_layanan, jl.nama_layanan
    ORDER BY jumlah DESC
"));

$divisi_stats = fetchAll(query("
    SELECT 
        tujuan as nama_unit,
        COUNT(*) as jumlah
    FROM janji_temu
    WHERE DATE_TRUNC('month', created_at) = DATE_TRUNC('month', CURRENT_DATE)
    GROUP BY tujuan
    ORDER BY jumlah DESC
    LIMIT 5
"));

$aktivitas_terbaru = fetchAll(query("
    SELECT 
        k.id_kunjungan,
        t.nama_lengkap as nama,
        t.instansi_lain as instansi,
        jl.nama_layanan,
        k.waktu_masuk,
        k.status
    FROM kunjungan k
    JOIN tamu t ON k.id_tamu = t.id_tamu
    LEFT JOIN jenis_layanan jl ON k.id_layanan = jl.id_layanan
    ORDER BY k.waktu_masuk DESC
    LIMIT 5
"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Portal TIK</title>
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
                <h1 class="text-xl font-bold text-slate-800"><?= $greeting ?>, <?= htmlspecialchars(explode(' ', $user_nama)[0]) ?></h1>
                <p class="text-sm text-slate-500">Overview statistik dan manajemen sistem.</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right">
                    <p class="text-sm font-medium text-slate-800">Administrator</p>
                    <p class="text-xs text-slate-500">UPA TIK</p>
                </div>
                <div class="w-10 h-10 bg-[#0ea5e9] rounded-full flex items-center justify-center">
                    <span class="text-sm font-semibold text-white"><?= strtoupper(substr($user_nama, 0, 2)) ?></span>
                </div>
            </div>
        </header>

        <div class="p-8">
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                
                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500 mb-1">Total Kunjungan</p>
                            <p class="text-2xl font-bold text-slate-800"><?= number_format($stats['total_kunjungan']) ?></p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-[#0ea5e9]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500 mb-1">Bulan Ini</p>
                            <p class="text-2xl font-bold text-slate-800"><?= number_format($stats['kunjungan_bulan']) ?></p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500 mb-1">Total Staff</p>
                            <p class="text-2xl font-bold text-slate-800"><?= $stats['total_staff'] ?></p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-slate-500 mb-1">Rata-rata/Hari</p>
                            <p class="text-2xl font-bold text-slate-800"><?= $stats['rata_rata_harian'] ?></p>
                        </div>
                        <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Charts Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                
                <!-- Statistik per Keperluan -->
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="text-lg font-bold text-slate-800 mb-4">Statistik Keperluan Bulan Ini</h2>
                    <canvas id="keperluanChart" height="200"></canvas>
                </div>

                <!-- Statistik per Divisi -->
                <div class="bg-white rounded-xl border border-slate-200 p-6">
                    <h2 class="text-lg font-bold text-slate-800 mb-4">Top 5 Divisi Tujuan</h2>
                    <canvas id="divisiChart" height="200"></canvas>
                </div>

            </div>

            <!-- Aktivitas Terbaru -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-slate-800">Aktivitas Terbaru</h2>
                    <a href="laporan.php" class="text-[#0ea5e9] hover:text-[#0284c7] text-sm font-medium">
                        Lihat Semua →
                    </a>
                </div>

                <?php if (empty($aktivitas_terbaru)): ?>
                <div class="px-6 py-12 text-center text-slate-500">
                    Belum ada aktivitas kunjungan.
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase">Waktu</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase">Nama</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase">Instansi</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase">Keperluan</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($aktivitas_terbaru as $a): ?>
                            <tr class="table-row-hover">
                                <td class="px-6 py-4 text-sm text-slate-600"><?= date('d M Y, H:i', strtotime($a['waktu_masuk'])) ?></td>
                                <td class="px-6 py-4 text-sm font-medium text-slate-800"><?= htmlspecialchars($a['nama']) ?></td>
                                <td class="px-6 py-4 text-sm text-slate-600"><?= htmlspecialchars($a['instansi'] ?? '-') ?></td>
                                <td class="px-6 py-4 text-sm text-slate-600"><?= htmlspecialchars($a['nama_layanan'] ?? '-') ?></td>
                                <td class="px-6 py-4">
                                    <?php if ($a['status'] === 'Masuk'): ?>
                                        <span class="px-3 py-1 text-xs font-medium rounded-full badge-info">Di Dalam</span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 text-xs font-medium rounded-full badge-success">Selesai</span>
                                    <?php endif; ?>
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
    const keperluanLabels = <?= json_encode(array_column($keperluan_stats, 'nama_layanan')) ?>;
    const keperluanData = <?= json_encode(array_map('intval', array_column($keperluan_stats, 'jumlah'))) ?>;
    
    const divisiLabels = <?= json_encode(array_column($divisi_stats, 'nama_unit')) ?>;
    const divisiData = <?= json_encode(array_map('intval', array_column($divisi_stats, 'jumlah'))) ?>;

    new Chart(document.getElementById('keperluanChart'), {
        type: 'doughnut',
        data: {
            labels: keperluanLabels,
            datasets: [{
                data: keperluanData,
                backgroundColor: [
                    '#0ea5e9',
                    '#10b981',
                    '#f59e0b',
                    '#ef4444',
                    '#8b5cf6',
                    '#ec4899'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    new Chart(document.getElementById('divisiChart'), {
        type: 'bar',
        data: {
            labels: divisiLabels,
            datasets: [{
                label: 'Jumlah Kunjungan',
                data: divisiData,
                backgroundColor: '#0ea5e9',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
    </script>

</body>
</html>
