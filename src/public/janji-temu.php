<?php
session_start();
require_once '../../config/database.php';

$base_url = '../';
$errors = [];
$success = false;

$staf_tujuan = [
    'Kepala UPA TIK',
    'PJ. Pengembangan dan Inovasi',
    'PJ. Layanan Sistem dan Teknologi Informasi',
    'PJ. Manajemen dan Integrasi Sistem',
    'PJ. Infrastruktur Jaringan',
    'PJ. Sumber Daya Sistem Informasi',
    'PJ. Pusat Data dan Keamanan'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $tujuan = $_POST['tujuan'] ?? '';
    $tanggal_waktu = $_POST['tanggal_waktu'] ?? '';
    $topik_diskusi = trim($_POST['topik_diskusi'] ?? '');

    if (empty($nama)) $errors['nama'] = 'Nama wajib diisi';
    if (empty($email)) $errors['email'] = 'Email wajib diisi';
    if (empty($tujuan)) $errors['tujuan'] = 'Pilih staf/divisi tujuan';
    if (empty($tanggal_waktu)) $errors['tanggal_waktu'] = 'Pilih tanggal dan waktu';

    if (empty($errors)) {
        $sql = "INSERT INTO janji_temu (nama, email, tujuan, tanggal_waktu, topik_diskusi) VALUES ($1, $2, $3, $4, $5)";
        $result = pg_query_params($db, $sql, [$nama, $email, $tujuan, $tanggal_waktu, $topik_diskusi ?: null]);
        
        if ($result) {
            $success = true;
        } else {
            $errors['general'] = 'Terjadi kesalahan sistem';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Janji Temu - DigiTamu UPA TIK</title>
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
        <div class="max-w-xl mx-auto">
            
            <!-- Back Button -->
            <a href="index.php" class="inline-flex items-center gap-2 text-slate-600 hover:text-[#38bdf8] mb-6 transition-colors">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>
                </svg>
                <span class="text-sm font-medium">Kembali</span>
            </a>

            <?php if ($success): ?>
            <div class="bg-white rounded-2xl shadow-lg p-8 text-center">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-green-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-slate-800 mb-2">Janji Temu Berhasil Dibuat!</h2>
                <p class="text-slate-600 mb-6">Kami akan menghubungi Anda melalui email untuk konfirmasi.</p>
                <a href="index.php" class="inline-block bg-[#38bdf8] hover:bg-[#0ea5e9] text-white font-semibold py-3 px-8 rounded-lg transition-colors">
                    Kembali ke Beranda
                </a>
            </div>
            <?php else: ?>

            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="p-6 md:p-8">
                    <h2 class="text-xl font-bold text-slate-800 mb-1">Jadwalkan Janji Temu</h2>
                    <p class="text-slate-500 text-sm mb-6">Silakan lengkapi formulir untuk membuat janji dengan staf kami.</p>

                    <?php if (isset($errors['general'])): ?>
                    <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 mb-6 text-red-700 text-sm">
                        <?= htmlspecialchars($errors['general']) ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-5">
                        
                        <!-- Nama Lengkap -->
                        <div>
                            <label for="nama" class="block text-sm font-medium text-slate-700 mb-2">Nama Lengkap</label>
                            <input 
                                type="text" 
                                id="nama" 
                                name="nama" 
                                value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"
                                class="w-full px-4 py-3 border <?= isset($errors['nama']) ? 'border-red-300' : 'border-slate-200' ?> rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 outline-none text-sm"
                                placeholder="Masukkan nama Anda"
                            >
                            <?php if (isset($errors['nama'])): ?>
                                <p class="mt-1 text-xs text-red-500"><?= $errors['nama'] ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-slate-700 mb-2">Email</label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                class="w-full px-4 py-3 border <?= isset($errors['email']) ? 'border-red-300' : 'border-slate-200' ?> rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 outline-none text-sm"
                                placeholder="user@example.com"
                            >
                            <?php if (isset($errors['email'])): ?>
                                <p class="mt-1 text-xs text-red-500"><?= $errors['email'] ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Ingin Bertemu Siapa -->
                        <div>
                            <label for="tujuan" class="block text-sm font-medium text-slate-700 mb-2">Ingin Bertemu Siapa?</label>
                            <select 
                                id="tujuan" 
                                name="tujuan"
                                class="w-full px-4 py-3 border <?= isset($errors['tujuan']) ? 'border-red-300' : 'border-slate-200' ?> rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 outline-none text-sm bg-white"
                            >
                                <option value="">- Pilih Staf / Divisi -</option>
                                <?php foreach ($staf_tujuan as $staf): ?>
                                    <option value="<?= htmlspecialchars($staf) ?>" <?= ($_POST['tujuan'] ?? '') === $staf ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($staf) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['tujuan'])): ?>
                                <p class="mt-1 text-xs text-red-500"><?= $errors['tujuan'] ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Tanggal & Waktu -->
                        <div>
                            <label for="tanggal_waktu" class="block text-sm font-medium text-slate-700 mb-2">Rencana Tanggal & Waktu</label>
                            <input 
                                type="datetime-local" 
                                id="tanggal_waktu" 
                                name="tanggal_waktu" 
                                value="<?= htmlspecialchars($_POST['tanggal_waktu'] ?? '') ?>"
                                class="w-full px-4 py-3 border <?= isset($errors['tanggal_waktu']) ? 'border-red-300' : 'border-slate-200' ?> rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 outline-none text-sm"
                            >
                            <?php if (isset($errors['tanggal_waktu'])): ?>
                                <p class="mt-1 text-xs text-red-500"><?= $errors['tanggal_waktu'] ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Topik Diskusi -->
                        <div>
                            <label for="topik_diskusi" class="block text-sm font-medium text-slate-700 mb-2">Topik Diskusi</label>
                            <textarea 
                                id="topik_diskusi" 
                                name="topik_diskusi" 
                                rows="4"
                                class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 outline-none text-sm resize-none"
                                placeholder="Jelaskan secara singkat topik yang ingin dibahas..."
                            ><?= htmlspecialchars($_POST['topik_diskusi'] ?? '') ?></textarea>
                        </div>

                        <!-- Submit Button -->
                        <button 
                            type="submit"
                            class="w-full bg-[#38bdf8] hover:bg-[#0ea5e9] text-white font-semibold py-4 rounded-lg transition-colors text-base"
                        >
                            Buat Janji Temu
                        </button>

                    </form>
                </div>
            </div>

            <?php endif; ?>
        </div>
    </main>

</body>
</html>
