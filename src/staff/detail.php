<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Staff') {
    header('Location: ../auth/login.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$type = $_GET['type'] ?? 'kunjungan';

$data = null;

if ($type === 'janji_temu' && $id > 0) {
    $data = fetchOne(query("
        SELECT 
            jt.*,
            u.nama_lengkap as diproses_nama
        FROM janji_temu jt
        LEFT JOIN users u ON jt.diproses_oleh = u.id_user
        WHERE jt.id_janji = $id
    "));
    $title = 'Detail Janji Temu';
} elseif ($type === 'peminjaman' && $id > 0) {
    $data = fetchOne(query("
        SELECT 
            p.*,
            f.nama_fasilitas,
            u.nama_lengkap as diproses_nama
        FROM peminjaman p
        LEFT JOIN fasilitas f ON p.id_fasilitas = f.id_fasilitas
        LEFT JOIN users u ON p.diproses_oleh = u.id_user
        WHERE p.id_peminjaman = $id
    "));
    $title = 'Detail Peminjaman';
} else {
    $data = fetchOne(query("
        SELECT 
            k.*,
            t.nama_lengkap,
            t.email,
            t.no_telp,
            t.jenis_instansi,
            t.instansi_lain,
            t.foto,
            uk.nama_unit,
            jl.nama_layanan
        FROM kunjungan k
        JOIN tamu t ON k.id_tamu = t.id_tamu
        LEFT JOIN unit_kerja uk ON t.id_unit = uk.id_unit
        LEFT JOIN jenis_layanan jl ON k.id_layanan = jl.id_layanan
        WHERE k.id_kunjungan = $id
    "));
    $title = 'Detail Kunjungan';
    $type = 'kunjungan';
}

if (!$data) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> - Portal TIK</title>
    <link href="../../assets/css/output.css" rel="stylesheet">
    <link href="../../assets/css/custom.css" rel="stylesheet">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Figtree', system-ui, sans-serif; }</style>
</head>
<body class="bg-slate-100 min-h-screen">

    <?php include '../../includes/sidebar-staff.php'; ?>

    <main class="ml-56 min-h-screen">
        
        <header class="bg-white border-b border-slate-200 px-8 py-4 flex items-center justify-between sticky top-0 z-40">
            <div class="flex items-center gap-4">
                <a href="javascript:history.back()" class="text-slate-500 hover:text-slate-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-xl font-bold text-slate-800"><?= $title ?></h1>
                    <p class="text-sm text-slate-500">Informasi lengkap <?= strtolower($title) ?>.</p>
                </div>
            </div>
        </header>

        <div class="p-8">
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                
                <?php if ($type === 'kunjungan'): ?>
                <div class="p-6 border-b border-slate-200">
                    <div class="flex items-start gap-6">
                        <?php if (!empty($data['foto'])): ?>
                        <img src="../../uploads/foto_tamu/<?= htmlspecialchars($data['foto']) ?>" alt="Foto" class="w-32 h-32 rounded-xl object-cover">
                        <?php else: ?>
                        <div class="w-32 h-32 rounded-xl bg-slate-200 flex items-center justify-center">
                            <svg class="w-12 h-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <?php endif; ?>
                        <div class="flex-1">
                            <h2 class="text-2xl font-bold text-slate-800 mb-2"><?= htmlspecialchars($data['nama_lengkap']) ?></h2>
                            <div class="flex items-center gap-3">
                                <span class="px-3 py-1 rounded-full text-sm font-medium <?= $data['status'] === 'Masuk' ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800' ?>">
                                    <?= $data['status'] ?>
                                </span>
                                <span class="px-3 py-1 rounded-full text-sm font-medium <?= $data['jenis_instansi'] === 'Internal' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' ?>">
                                    <?= $data['jenis_instansi'] ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-slate-500 mb-1">Email</p>
                            <p class="font-medium text-slate-800"><?= htmlspecialchars($data['email'] ?? '-') ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 mb-1">No. Telepon</p>
                            <p class="font-medium text-slate-800"><?= htmlspecialchars($data['no_telp'] ?? '-') ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 mb-1">Unit Kerja / Instansi</p>
                            <p class="font-medium text-slate-800"><?= htmlspecialchars($data['nama_unit'] ?? $data['instansi_lain'] ?? '-') ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 mb-1">Keperluan</p>
                            <p class="font-medium text-slate-800"><?= htmlspecialchars($data['nama_layanan'] ?? '-') ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 mb-1">Waktu Masuk</p>
                            <p class="font-medium text-slate-800"><?= date('d M Y, H:i', strtotime($data['waktu_masuk'])) ?> WIB</p>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 mb-1">Waktu Keluar</p>
                            <p class="font-medium text-slate-800"><?= $data['waktu_keluar'] ? date('d M Y, H:i', strtotime($data['waktu_keluar'])) . ' WIB' : '-' ?></p>
                        </div>
                        <?php if (!empty($data['detail_keperluan'])): ?>
                        <div class="col-span-2">
                            <p class="text-sm text-slate-500 mb-1">Detail Keperluan</p>
                            <p class="font-medium text-slate-800"><?= htmlspecialchars($data['detail_keperluan']) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php elseif ($type === 'janji_temu'): ?>
                <div class="p-6 border-b border-slate-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-bold text-slate-800"><?= htmlspecialchars($data['nama']) ?></h2>
                        <span class="px-3 py-1 rounded-full text-sm font-medium 
                            <?php if ($data['status'] === 'pending'): ?>bg-amber-100 text-amber-800
                            <?php elseif ($data['status'] === 'disetujui'): ?>bg-green-100 text-green-800
                            <?php else: ?>bg-red-100 text-red-800<?php endif; ?>">
                            <?= ucfirst($data['status']) ?>
                        </span>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-slate-500 mb-1">Email</p>
                            <p class="font-medium text-slate-800"><?= htmlspecialchars($data['email']) ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 mb-1">Tujuan</p>
                            <p class="font-medium text-slate-800"><?= htmlspecialchars($data['tujuan'] ?? '-') ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 mb-1">Jadwal</p>
                            <p class="font-medium text-slate-800"><?= date('d M Y, H:i', strtotime($data['tanggal_waktu'])) ?> WIB</p>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 mb-1">Diajukan Pada</p>
                            <p class="font-medium text-slate-800"><?= date('d M Y, H:i', strtotime($data['created_at'])) ?></p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-sm text-slate-500 mb-1">Topik Diskusi</p>
                            <p class="font-medium text-slate-800"><?= htmlspecialchars($data['topik_diskusi'] ?? '-') ?></p>
                        </div>
                        <?php if ($data['status'] !== 'pending'): ?>
                        <div>
                            <p class="text-sm text-slate-500 mb-1">Diproses Oleh</p>
                            <p class="font-medium text-slate-800"><?= htmlspecialchars($data['diproses_nama'] ?? '-') ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 mb-1">Tanggal Diproses</p>
                            <p class="font-medium text-slate-800"><?= $data['tanggal_diproses'] ? date('d M Y, H:i', strtotime($data['tanggal_diproses'])) : '-' ?></p>
                        </div>
                        <?php if (!empty($data['catatan_staff'])): ?>
                        <div class="col-span-2">
                            <p class="text-sm text-slate-500 mb-1">Catatan Staff</p>
                            <p class="font-medium text-slate-800"><?= htmlspecialchars($data['catatan_staff']) ?></p>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php elseif ($type === 'peminjaman'): ?>
                <div class="p-6 border-b border-slate-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-bold text-slate-800"><?= htmlspecialchars($data['nama_peminjam']) ?></h2>
                        <span class="px-3 py-1 rounded-full text-sm font-medium 
                            <?php if ($data['status'] === 'pending'): ?>bg-amber-100 text-amber-800
                            <?php elseif ($data['status'] === 'disetujui'): ?>bg-green-100 text-green-800
                            <?php else: ?>bg-red-100 text-red-800<?php endif; ?>">
                            <?= ucfirst($data['status']) ?>
                        </span>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-slate-500 mb-1">Instansi</p>
                            <p class="font-medium text-slate-800"><?= htmlspecialchars($data['instansi'] ?? '-') ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 mb-1">Fasilitas</p>
                            <p class="font-medium text-slate-800"><?= htmlspecialchars($data['nama_fasilitas'] ?? '-') ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 mb-1">Tanggal Mulai</p>
                            <p class="font-medium text-slate-800"><?= date('d M Y', strtotime($data['tanggal_mulai'])) ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 mb-1">Tanggal Selesai</p>
                            <p class="font-medium text-slate-800"><?= date('d M Y', strtotime($data['tanggal_selesai'])) ?></p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-sm text-slate-500 mb-1">Keperluan</p>
                            <p class="font-medium text-slate-800"><?= htmlspecialchars($data['keperluan'] ?? '-') ?></p>
                        </div>
                        <?php if ($data['status'] !== 'pending'): ?>
                        <div>
                            <p class="text-sm text-slate-500 mb-1">Diproses Oleh</p>
                            <p class="font-medium text-slate-800"><?= htmlspecialchars($data['diproses_nama'] ?? '-') ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 mb-1">Tanggal Diproses</p>
                            <p class="font-medium text-slate-800"><?= $data['tanggal_diproses'] ? date('d M Y, H:i', strtotime($data['tanggal_diproses'])) : '-' ?></p>
                        </div>
                        <?php if (!empty($data['catatan_staff'])): ?>
                        <div class="col-span-2">
                            <p class="text-sm text-slate-500 mb-1">Catatan Staff</p>
                            <p class="font-medium text-slate-800"><?= htmlspecialchars($data['catatan_staff']) ?></p>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
    </main>

</body>
</html>
