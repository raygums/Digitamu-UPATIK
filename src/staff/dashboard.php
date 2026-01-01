<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Staff') {
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

$stats = ['tamu_hari_ini' => 0, 'di_ruangan' => 0, 'permohonan_baru' => 0];

$result = query("SELECT COUNT(*) as total FROM kunjungan WHERE DATE(waktu_masuk) = CURRENT_DATE");
$row = fetchOne($result);
$stats['tamu_hari_ini'] = $row['total'] ?? 0;

$result = query("SELECT COUNT(*) as total FROM kunjungan WHERE status = 'Masuk'");
$row = fetchOne($result);
$stats['di_ruangan'] = $row['total'] ?? 0;

$result = query("SELECT COUNT(*) as total FROM janji_temu WHERE status = 'pending'");
$row = fetchOne($result);
$permohonan_janji = $row['total'] ?? 0;

$result = query("SELECT COUNT(*) as total FROM peminjaman WHERE status = 'pending'");
$row = fetchOne($result);
$permohonan_pinjam = $row['total'] ?? 0;

$stats['permohonan_baru'] = $permohonan_janji + $permohonan_pinjam;

$aktivitas = fetchAll(query("
    SELECT 
        k.id_kunjungan,
        TO_CHAR(k.waktu_masuk, 'HH24:MI') as waktu,
        t.nama_lengkap as nama,
        t.instansi_lain as instansi,
        jl.nama_layanan,
        k.status
    FROM kunjungan k
    JOIN tamu t ON k.id_tamu = t.id_tamu
    LEFT JOIN jenis_layanan jl ON k.id_layanan = jl.id_layanan
    WHERE DATE(k.waktu_masuk) = CURRENT_DATE
    ORDER BY k.waktu_masuk DESC
    LIMIT 10
"));

if (isset($_GET['checkout']) && is_numeric($_GET['checkout'])) {
    $id_kunjungan = (int)$_GET['checkout'];
    $sql = "UPDATE kunjungan SET status = 'Keluar', waktu_keluar = NOW() WHERE id_kunjungan = $1 AND status = 'Masuk'";
    pg_query_params($db, $sql, [$id_kunjungan]);
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Staff - Portal TIK</title>
    <link href="../../assets/css/output.css" rel="stylesheet">
    <link href="../../assets/css/custom.css" rel="stylesheet">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Figtree', system-ui, sans-serif; }</style>
</head>
<body class="bg-slate-100 min-h-screen">

    <?php include '../../includes/sidebar-staff.php'; ?>

    <!-- Main Content -->
    <main class="ml-56 min-h-screen">
        
        <!-- Header -->
        <header class="bg-white border-b border-slate-200 px-8 py-4 flex items-center justify-between sticky top-0 z-40">
            <div>
                <h1 class="text-xl font-bold text-slate-800"><?= $greeting ?>, <?= htmlspecialchars(explode(' ', $user_nama)[0]) ?></h1>
                <p class="text-sm text-slate-500">Monitoring aktivitas kunjungan hari ini.</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right">
                    <p class="text-sm font-medium text-slate-800">Petugas Front Office</p>
                    <p class="text-xs text-slate-500">Staff TIK</p>
                </div>
                <div class="w-10 h-10 bg-slate-200 rounded-full flex items-center justify-center">
                    <span class="text-sm font-semibold text-slate-600"><?= strtoupper(substr($user_nama, 0, 2)) ?></span>
                </div>
            </div>
        </header>

        <div class="p-8">
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                
                <div class="stats-card">
                    <p class="text-sm text-slate-500 mb-1">Total Tamu Hari Ini</p>
                    <p class="text-3xl font-bold text-[#0ea5e9]"><?= $stats['tamu_hari_ini'] ?></p>
                </div>

                <div class="stats-card">
                    <p class="text-sm text-slate-500 mb-1">Sedang Di Ruangan</p>
                    <p class="text-3xl font-bold text-[#10b981]"><?= $stats['di_ruangan'] ?></p>
                </div>

                <div class="stats-card">
                    <p class="text-sm text-slate-500 mb-1">Permohonan Baru</p>
                    <p class="text-3xl font-bold text-[#f59e0b]"><?= $stats['permohonan_baru'] ?></p>
                </div>

            </div>

            <!-- Aktivitas Terkini -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-slate-800">Aktivitas Terkini</h2>
                    <a href="riwayat.php" class="bg-[#0ea5e9] hover:bg-[#0284c7] text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                        Export Excel
                    </a>
                </div>

                <?php if (empty($aktivitas)): ?>
                <div class="px-6 py-12 text-center text-slate-500">
                    Belum ada aktivitas kunjungan hari ini.
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase">Waktu</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase">Nama Pengunjung</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase">Instansi</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase">Keperluan</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase">Status</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($aktivitas as $a): ?>
                            <tr class="table-row-hover">
                                <td class="px-6 py-4 text-sm text-slate-600"><?= $a['waktu'] ?></td>
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
                                <td class="px-6 py-4">
                                    <?php if ($a['status'] === 'Masuk'): ?>
                                        <a href="?checkout=<?= $a['id_kunjungan'] ?>" onclick="return confirm('Checkout tamu ini?')" class="bg-red-500 hover:bg-red-600 text-white text-xs font-medium px-3 py-1.5 rounded transition-colors">
                                            Check Out
                                        </a>
                                    <?php else: ?>
                                        <a href="detail.php?id=<?= $a['id_kunjungan'] ?>" class="bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-medium px-3 py-1.5 rounded transition-colors">
                                            Detail
                                        </a>
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

</body>
</html>
