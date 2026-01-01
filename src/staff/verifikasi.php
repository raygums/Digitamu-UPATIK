<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Staff') {
    header('Location: ../auth/login.php');
    exit;
}

$user_nama = $_SESSION['user_nama'];
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $catatan = pg_escape_string($db, $_POST['catatan'] ?? '');
    
    $status = ($action === 'approve') ? 'disetujui' : 'ditolak';
    
    if ($type === 'janji_temu' && $id > 0) {
        $sql = "UPDATE janji_temu SET status = '$status', catatan_staff = '$catatan', diproses_oleh = {$_SESSION['user_id']}, tanggal_diproses = NOW() WHERE id_janji = $id";
        if (pg_query($db, $sql)) {
            $message = 'Janji temu berhasil ' . ($action === 'approve' ? 'disetujui' : 'ditolak');
            $message_type = $action === 'approve' ? 'success' : 'warning';
        }
    } elseif ($type === 'peminjaman' && $id > 0) {
        $sql = "UPDATE peminjaman SET status = '$status', catatan_staff = '$catatan', diproses_oleh = {$_SESSION['user_id']}, tanggal_diproses = NOW() WHERE id_peminjaman = $id";
        if (pg_query($db, $sql)) {
            $message = 'Peminjaman berhasil ' . ($action === 'approve' ? 'disetujui' : 'ditolak');
            $message_type = $action === 'approve' ? 'success' : 'warning';
        }
    }
}

$janji_temu_pending = fetchAll(query("
    SELECT 
        jt.id_janji,
        jt.nama,
        jt.email,
        jt.tujuan as nama_unit,
        jt.tanggal_waktu,
        jt.topik_diskusi,
        jt.created_at
    FROM janji_temu jt
    WHERE jt.status = 'pending'
    ORDER BY jt.tanggal_waktu ASC
"));

$peminjaman_pending = fetchAll(query("
    SELECT 
        p.id_peminjaman,
        p.nama_peminjam as nama,
        p.instansi,
        f.nama_fasilitas,
        p.tanggal_mulai,
        p.tanggal_selesai,
        p.created_at
    FROM peminjaman p
    LEFT JOIN fasilitas f ON p.id_fasilitas = f.id_fasilitas
    WHERE p.status = 'pending'
    ORDER BY p.tanggal_mulai ASC
"));

$total_pending = count($janji_temu_pending) + count($peminjaman_pending);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Permohonan - Portal TIK</title>
    <link href="../../assets/css/output.css" rel="stylesheet">
    <link href="../../assets/css/custom.css" rel="stylesheet">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Figtree', system-ui, sans-serif; }</style>
</head>
<body class="bg-slate-100 min-h-screen">

    <?php include '../../includes/sidebar-staff.php'; ?>

    <main class="ml-56 min-h-screen">
        
        <header class="bg-white border-b border-slate-200 px-8 py-4 flex items-center justify-between sticky top-0 z-40">
            <div>
                <h1 class="text-xl font-bold text-slate-800">Verifikasi Permohonan</h1>
                <p class="text-sm text-slate-500">Kelola permintaan janji temu dan peminjaman fasilitas.</p>
            </div>
            <div class="flex items-center gap-3">
                <?php if ($total_pending > 0): ?>
                <span class="bg-amber-100 text-amber-800 text-sm font-medium px-3 py-1 rounded-full">
                    <?= $total_pending ?> Menunggu
                </span>
                <?php endif; ?>
            </div>
        </header>

        <div class="p-8">
            
            <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?= $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>

            <div class="mb-6">
                <div class="border-b border-slate-200">
                    <nav class="-mb-px flex space-x-8" role="tablist">
                        <button onclick="showTab('janji')" id="tab-janji" class="tab-btn active border-b-2 border-[#0ea5e9] text-[#0ea5e9] py-3 px-1 text-sm font-medium">
                            Janji Temu <span class="ml-1 bg-[#0ea5e9] text-white text-xs px-2 py-0.5 rounded-full"><?= count($janji_temu_pending) ?></span>
                        </button>
                        <button onclick="showTab('pinjam')" id="tab-pinjam" class="tab-btn border-b-2 border-transparent text-slate-500 hover:text-slate-700 py-3 px-1 text-sm font-medium">
                            Peminjaman <span class="ml-1 bg-slate-200 text-slate-600 text-xs px-2 py-0.5 rounded-full"><?= count($peminjaman_pending) ?></span>
                        </button>
                    </nav>
                </div>
            </div>

            <div id="content-janji" class="tab-content">
                <?php if (empty($janji_temu_pending)): ?>
                <div class="bg-white rounded-xl border border-slate-200 p-12 text-center">
                    <svg class="w-16 h-16 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-slate-500">Tidak ada permohonan janji temu yang menunggu verifikasi.</p>
                </div>
                <?php else: ?>
                <div class="grid gap-4">
                    <?php foreach ($janji_temu_pending as $jt): ?>
                    <div class="bg-white rounded-xl border border-slate-200 p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-3">
                                    <h3 class="text-lg font-semibold text-slate-800"><?= htmlspecialchars($jt['nama']) ?></h3>
                                    <span class="px-2 py-1 text-xs font-medium rounded bg-amber-100 text-amber-800">Menunggu</span>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                                    <div>
                                        <p class="text-slate-500">Email</p>
                                        <p class="font-medium text-slate-800"><?= htmlspecialchars($jt['email']) ?></p>
                                    </div>
                                    <div>
                                        <p class="text-slate-500">Unit Kerja Tujuan</p>
                                        <p class="font-medium text-slate-800"><?= htmlspecialchars($jt['nama_unit'] ?? '-') ?></p>
                                    </div>
                                    <div>
                                        <p class="text-slate-500">Jadwal</p>
                                        <p class="font-medium text-slate-800"><?= date('d M Y, H:i', strtotime($jt['tanggal_waktu'])) ?> WIB</p>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <p class="text-slate-500 text-sm">Topik Diskusi</p>
                                    <p class="text-slate-800"><?= htmlspecialchars($jt['topik_diskusi'] ?? '-') ?></p>
                                </div>
                            </div>
                            <div class="flex gap-2 ml-4">
                                <button onclick="openModal('janji_temu', <?= $jt['id_janji'] ?>, 'approve')" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                    Setujui
                                </button>
                                <button onclick="openModal('janji_temu', <?= $jt['id_janji'] ?>, 'reject')" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                    Tolak
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div id="content-pinjam" class="tab-content hidden">
                <?php if (empty($peminjaman_pending)): ?>
                <div class="bg-white rounded-xl border border-slate-200 p-12 text-center">
                    <svg class="w-16 h-16 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-slate-500">Tidak ada permohonan peminjaman yang menunggu verifikasi.</p>
                </div>
                <?php else: ?>
                <div class="grid gap-4">
                    <?php foreach ($peminjaman_pending as $p): ?>
                    <div class="bg-white rounded-xl border border-slate-200 p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-3">
                                    <h3 class="text-lg font-semibold text-slate-800"><?= htmlspecialchars($p['nama']) ?></h3>
                                    <span class="px-2 py-1 text-xs font-medium rounded bg-amber-100 text-amber-800">Menunggu</span>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                    <div>
                                        <p class="text-slate-500">Instansi</p>
                                        <p class="font-medium text-slate-800"><?= htmlspecialchars($p['instansi'] ?? '-') ?></p>
                                    </div>
                                    <div>
                                        <p class="text-slate-500">Fasilitas</p>
                                        <p class="font-medium text-slate-800"><?= htmlspecialchars($p['nama_fasilitas'] ?? '-') ?></p>
                                    </div>
                                    <div>
                                        <p class="text-slate-500">Tanggal Mulai</p>
                                        <p class="font-medium text-slate-800"><?= date('d M Y', strtotime($p['tanggal_mulai'])) ?></p>
                                    </div>
                                    <div>
                                        <p class="text-slate-500">Tanggal Selesai</p>
                                        <p class="font-medium text-slate-800"><?= date('d M Y', strtotime($p['tanggal_selesai'])) ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-2 ml-4">
                                <button onclick="openModal('peminjaman', <?= $p['id'] ?>, 'approve')" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                    Setujui
                                </button>
                                <button onclick="openModal('peminjaman', <?= $p['id'] ?>, 'reject')" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                    Tolak
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <div id="verifyModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl w-full max-w-md mx-4 p-6">
            <h3 id="modalTitle" class="text-lg font-bold text-slate-800 mb-4">Konfirmasi</h3>
            <form method="POST">
                <input type="hidden" name="type" id="modalType">
                <input type="hidden" name="id" id="modalId">
                <input type="hidden" name="action" id="modalAction">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Catatan (opsional)</label>
                    <textarea name="catatan" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0ea5e9] focus:border-[#0ea5e9]" placeholder="Tambahkan catatan..."></textarea>
                </div>
                
                <div class="flex gap-3">
                    <button type="button" onclick="closeModal()" class="flex-1 px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors">
                        Batal
                    </button>
                    <button type="submit" id="modalSubmit" class="flex-1 px-4 py-2 text-white rounded-lg font-medium transition-colors">
                        Konfirmasi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function showTab(tab) {
        document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('active', 'border-[#0ea5e9]', 'text-[#0ea5e9]');
            b.classList.add('border-transparent', 'text-slate-500');
        });
        
        document.getElementById('content-' + tab).classList.remove('hidden');
        const activeBtn = document.getElementById('tab-' + tab);
        activeBtn.classList.add('active', 'border-[#0ea5e9]', 'text-[#0ea5e9]');
        activeBtn.classList.remove('border-transparent', 'text-slate-500');
    }

    function openModal(type, id, action) {
        document.getElementById('modalType').value = type;
        document.getElementById('modalId').value = id;
        document.getElementById('modalAction').value = action;
        
        const title = document.getElementById('modalTitle');
        const submit = document.getElementById('modalSubmit');
        
        if (action === 'approve') {
            title.textContent = 'Setujui Permohonan';
            submit.className = 'flex-1 px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg font-medium transition-colors';
        } else {
            title.textContent = 'Tolak Permohonan';
            submit.className = 'flex-1 px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg font-medium transition-colors';
        }
        
        document.getElementById('verifyModal').classList.remove('hidden');
        document.getElementById('verifyModal').classList.add('flex');
    }

    function closeModal() {
        document.getElementById('verifyModal').classList.add('hidden');
        document.getElementById('verifyModal').classList.remove('flex');
    }
    </script>

</body>
</html>
