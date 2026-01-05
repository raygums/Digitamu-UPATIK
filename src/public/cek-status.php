<?php
session_start();
require_once '../../config/database.php';

$base_url = '../';
$email = '';
$results = [];
$searched = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $searched = true;
    
    if (!empty($email)) {
        $email_escaped = pg_escape_string($db, $email);
        
        $janji_temu = fetchAll(query("
            SELECT 
                id_janji,
                'janji_temu' as tipe,
                nama,
                tujuan,
                tanggal_waktu,
                status,
                catatan_staff,
                created_at
            FROM janji_temu 
            WHERE email = '$email_escaped'
            ORDER BY created_at DESC
        "));
        
        $peminjaman = fetchAll(query("
            SELECT 
                p.id_peminjaman,
                'peminjaman' as tipe,
                p.nama_peminjam as nama,
                f.nama_fasilitas,
                p.tanggal_mulai,
                p.tanggal_selesai,
                p.status,
                p.catatan_staff,
                p.created_at
            FROM peminjaman p
            LEFT JOIN fasilitas f ON p.id_fasilitas = f.id_fasilitas
            WHERE p.email = '$email_escaped'
            ORDER BY p.created_at DESC
        "));
        
        $results = array_merge($janji_temu, $peminjaman);
        
        usort($results, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
    }
}

function getStatusBadge($status) {
    switch ($status) {
        case 'pending':
            return '<span class="px-3 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Menunggu</span>';
        case 'disetujui':
            return '<span class="px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Disetujui</span>';
        case 'ditolak':
            return '<span class="px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Ditolak</span>';
        default:
            return '<span class="px-3 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-800">' . ucfirst($status) . '</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Status Permohonan - DigiTamu UPA TIK</title>
    <link href="../../assets/css/output.css" rel="stylesheet">
    <link href="../../assets/css/custom.css" rel="stylesheet">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    <style>body { 
        font-family: 'Figtree', system-ui, sans-serif; 
        background-image: url('../../assets/images/bg.svg');
        background-size: contain;
        background-position: center; 
        background-attachment: fixed;
        background-repeat: repeat;
    }
    </style>
</head>
<body>

    <?php include '../../includes/header-public.php'; ?>

    <main class="flex-1 py-8 px-4">
        <div class="max-w-2xl mx-auto">
            
            <a href="index.php" class="inline-flex items-center gap-2 text-slate-600 hover:text-[#38bdf8] mb-6 transition-colors">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>
                </svg>
                <span class="text-sm font-medium">Kembali</span>
            </a>

            <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-6">
                <div class="p-6 md:p-8">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 bg-sky-100 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-sky-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-800">Cek Status Permohonan</h2>
                            <p class="text-slate-500 text-sm">Masukkan email untuk melihat status janji temu atau peminjaman.</p>
                        </div>
                    </div>

                    <form method="POST" class="flex gap-3">
                        <input 
                            type="email" 
                            name="email" 
                            value="<?= htmlspecialchars($email) ?>"
                            class="flex-1 px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 outline-none text-sm"
                            placeholder="Masukkan email Anda"
                            required
                        >
                        <button 
                            type="submit"
                            class="bg-[#38bdf8] hover:bg-[#0ea5e9] text-white font-semibold px-6 py-3 rounded-lg transition-colors text-sm"
                        >
                            Cari
                        </button>
                    </form>
                </div>
            </div>

            <?php if ($searched): ?>
                <?php if (empty($results)): ?>
                <div class="bg-white rounded-2xl shadow-lg p-8 text-center">
                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="15" x2="15" y2="15"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">Tidak Ada Data</h3>
                    <p class="text-slate-500 text-sm">Tidak ditemukan permohonan dengan email tersebut.</p>
                </div>
                <?php else: ?>
                <div class="space-y-4">
                    <p class="text-sm text-slate-600">Ditemukan <strong><?= count($results) ?></strong> permohonan</p>
                    
                    <?php foreach ($results as $r): ?>
                    <div class="bg-white rounded-xl shadow-md border border-slate-100 overflow-hidden">
                        <div class="p-5">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <?php if ($r['tipe'] === 'janji_temu'): ?>
                                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Janji Temu</span>
                                        <?php else: ?>
                                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">Peminjaman</span>
                                        <?php endif; ?>
                                        <?= getStatusBadge($r['status']) ?>
                                    </div>
                                    <h3 class="text-lg font-semibold text-slate-800"><?= htmlspecialchars($r['nama']) ?></h3>
                                </div>
                                <p class="text-xs text-slate-400"><?= date('d M Y, H:i', strtotime($r['created_at'])) ?></p>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <?php if ($r['tipe'] === 'janji_temu'): ?>
                                <div>
                                    <p class="text-slate-500">Tujuan</p>
                                    <p class="font-medium text-slate-800"><?= htmlspecialchars($r['tujuan'] ?? '-') ?></p>
                                </div>
                                <div>
                                    <p class="text-slate-500">Jadwal</p>
                                    <p class="font-medium text-slate-800"><?= date('d M Y, H:i', strtotime($r['tanggal_waktu'])) ?> WIB</p>
                                </div>
                                <?php else: ?>
                                <div>
                                    <p class="text-slate-500">Fasilitas</p>
                                    <p class="font-medium text-slate-800"><?= htmlspecialchars($r['nama_fasilitas'] ?? '-') ?></p>
                                </div>
                                <div>
                                    <p class="text-slate-500">Periode</p>
                                    <p class="font-medium text-slate-800"><?= date('d M', strtotime($r['tanggal_mulai'])) ?> - <?= date('d M Y', strtotime($r['tanggal_selesai'])) ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($r['catatan_staff'])): ?>
                            <div class="mt-3 p-3 bg-slate-50 rounded-lg">
                                <p class="text-xs text-slate-500 mb-1">Catatan dari Staff:</p>
                                <p class="text-sm text-slate-700"><?= htmlspecialchars($r['catatan_staff']) ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($r['status'] === 'pending'): ?>
                        <div class="px-5 py-3 bg-amber-50 border-t border-amber-100">
                            <p class="text-xs text-amber-700">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Permohonan Anda sedang dalam proses verifikasi. Mohon tunggu 1-2 hari kerja.
                            </p>
                        </div>
                        <?php elseif ($r['status'] === 'disetujui'): ?>
                        <div class="px-5 py-3 bg-green-50 border-t border-green-100">
                            <p class="text-xs text-green-700">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Permohonan Anda telah disetujui. Silakan datang sesuai jadwal yang telah ditentukan.
                            </p>
                        </div>
                        <?php elseif ($r['status'] === 'ditolak'): ?>
                        <div class="px-5 py-3 bg-red-50 border-t border-red-100">
                            <p class="text-xs text-red-700">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                Maaf, permohonan Anda tidak dapat disetujui. Silakan hubungi UPA TIK untuk informasi lebih lanjut.
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>

        </div>
    </main>

</body>
</html>
