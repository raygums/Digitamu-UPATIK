<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Staff') {
    header('Location: ../auth/login.php');
    exit;
}

$filter_tanggal = $_GET['tanggal'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_search = $_GET['search'] ?? '';

$where = [];
$params = [];
$param_count = 0;

if ($filter_tanggal) {
    $param_count++;
    $where[] = "DATE(k.waktu_masuk) = \${$param_count}";
    $params[] = $filter_tanggal;
}

if ($filter_status && in_array($filter_status, ['Masuk', 'Keluar'])) {
    $param_count++;
    $where[] = "k.status = \${$param_count}";
    $params[] = $filter_status;
}

if ($filter_search) {
    $param_count++;
    $where[] = "(t.nama_lengkap ILIKE \${$param_count} OR t.instansi_lain ILIKE \${$param_count})";
    $params[] = "%{$filter_search}%";
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$count_sql = "SELECT COUNT(*) as total FROM kunjungan k JOIN tamu t ON k.id_tamu = t.id_tamu $where_clause";
$result = empty($params) ? query($count_sql) : pg_query_params($db, $count_sql, $params);
$total = fetchOne($result)['total'] ?? 0;
$total_pages = ceil($total / $per_page);

$sql = "
    SELECT 
        k.id_kunjungan,
        k.waktu_masuk,
        k.waktu_keluar,
        k.status,
        t.nama_lengkap as nama,
        t.no_telp,
        t.email,
        t.instansi_lain as instansi,
        t.jenis_instansi,
        t.foto,
        jl.nama_layanan
    FROM kunjungan k
    JOIN tamu t ON k.id_tamu = t.id_tamu
    LEFT JOIN jenis_layanan jl ON k.id_layanan = jl.id_layanan
    $where_clause
    ORDER BY k.waktu_masuk DESC
    LIMIT $per_page OFFSET $offset
";

$kunjungan = empty($params) ? fetchAll(query($sql)) : fetchAll(pg_query_params($db, $sql, $params));

if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $export_sql = "
        SELECT 
            k.waktu_masuk,
            k.waktu_keluar,
            t.nama_lengkap as nama,
            t.jenis_instansi,
            t.no_telp,
            t.email,
            t.instansi_lain as instansi,
            jl.nama_layanan,
            k.status
        FROM kunjungan k
        JOIN tamu t ON k.id_tamu = t.id_tamu
        LEFT JOIN jenis_layanan jl ON k.id_layanan = jl.id_layanan
        $where_clause
        ORDER BY k.waktu_masuk DESC
    ";
    
    $export_data = empty($params) ? fetchAll(query($export_sql)) : fetchAll(pg_query_params($db, $export_sql, $params));
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="riwayat_kunjungan_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, ['Waktu Masuk', 'Waktu Keluar', 'Nama', 'Tipe', 'No HP', 'Email', 'Instansi', 'Keperluan', 'Status']);
    
    foreach ($export_data as $row) {
        fputcsv($output, [
            $row['waktu_masuk'],
            $row['waktu_keluar'] ?? '-',
            $row['nama'],
            $row['jenis_instansi'] ?? 'Eksternal',
            $row['no_telp'] ?? '-',
            $row['email'] ?? '-',
            $row['instansi'] ?? '-',
            $row['nama_layanan'] ?? '-',
            $row['status']
        ]);
    }
    
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Tamu - Portal TIK</title>
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
                <h1 class="text-xl font-bold text-slate-800">Riwayat Tamu</h1>
                <p class="text-sm text-slate-500">Data kunjungan tamu ke UPA TIK.</p>
            </div>
        </header>

        <div class="p-8">
            
            <!-- Filter & Export -->
            <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
                <form method="GET" class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Cari</label>
                        <input type="text" name="search" value="<?= htmlspecialchars($filter_search) ?>" placeholder="Nama atau instansi..." class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0ea5e9] focus:border-[#0ea5e9]">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal</label>
                        <input type="date" name="tanggal" value="<?= $filter_tanggal ?>" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0ea5e9] focus:border-[#0ea5e9]">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                        <select name="status" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0ea5e9] focus:border-[#0ea5e9]">
                            <option value="">Semua Status</option>
                            <option value="Masuk" <?= $filter_status === 'Masuk' ? 'selected' : '' ?>>Di Ruangan</option>
                            <option value="Keluar" <?= $filter_status === 'Keluar' ? 'selected' : '' ?>>Selesai</option>
                        </select>
                    </div>
                    <button type="submit" class="bg-[#0ea5e9] hover:bg-[#0284c7] text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        Filter
                    </button>
                    <a href="riwayat.php" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-4 py-2 rounded-lg font-medium transition-colors">
                        Reset
                    </a>
                    <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'excel'])) ?>" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Export Excel
                    </a>
                </form>
            </div>

            <!-- Data Table -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200">
                    <p class="text-sm text-slate-600">Total: <strong><?= number_format($total) ?></strong> data kunjungan</p>
                </div>

                <?php if (empty($kunjungan)): ?>
                <div class="px-6 py-12 text-center text-slate-500">
                    Tidak ada data kunjungan yang ditemukan.
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase">Foto</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase">Waktu</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase">Nama</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase">Instansi</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase">Keperluan</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($kunjungan as $k): ?>
                            <tr class="table-row-hover">
                                <td class="px-6 py-4">
                                    <?php if ($k['foto']): ?>
                                    <img src="../../uploads/foto_tamu/<?= $k['foto'] ?>" alt="Foto" class="w-10 h-10 rounded-full object-cover">
                                    <?php else: ?>
                                    <div class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center">
                                        <span class="text-xs font-semibold text-slate-500"><?= strtoupper(substr($k['nama'], 0, 2)) ?></span>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-slate-800"><?= date('d M Y', strtotime($k['waktu_masuk'])) ?></p>
                                    <p class="text-xs text-slate-500"><?= date('H:i', strtotime($k['waktu_masuk'])) ?> <?= $k['waktu_keluar'] ? '- ' . date('H:i', strtotime($k['waktu_keluar'])) : '' ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-slate-800"><?= htmlspecialchars($k['nama']) ?></p>
                                    <p class="text-xs text-slate-500"><?= ucfirst($k['jenis_instansi'] ?? 'Eksternal') ?></p>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600"><?= htmlspecialchars($k['instansi'] ?? '-') ?></td>
                                <td class="px-6 py-4 text-sm text-slate-600"><?= htmlspecialchars($k['nama_layanan'] ?? '-') ?></td>
                                <td class="px-6 py-4">
                                    <?php if ($k['status'] === 'Masuk'): ?>
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

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="px-6 py-4 border-t border-slate-200 flex items-center justify-between">
                    <p class="text-sm text-slate-600">Halaman <?= $page ?> dari <?= $total_pages ?></p>
                    <div class="flex gap-2">
                        <?php if ($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="px-3 py-1 border border-slate-300 rounded text-sm hover:bg-slate-50">Sebelumnya</a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="px-3 py-1 border rounded text-sm <?= $i === $page ? 'bg-[#0ea5e9] text-white border-[#0ea5e9]' : 'border-slate-300 hover:bg-slate-50' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="px-3 py-1 border border-slate-300 rounded text-sm hover:bg-slate-50">Selanjutnya</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>

        </div>
    </main>

</body>
</html>
