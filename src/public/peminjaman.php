<?php
session_start();
require_once '../../config/database.php';

$base_url = '../';
$errors = [];
$success = false;

$fasilitas = fetchAll(query("SELECT id_fasilitas, nama_fasilitas, jenis FROM fasilitas WHERE deleted_at IS NULL AND is_available = TRUE ORDER BY jenis, nama_fasilitas"));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_peminjam = trim($_POST['nama_peminjam'] ?? '');
    $instansi = trim($_POST['instansi'] ?? '');
    $id_fasilitas = $_POST['id_fasilitas'] ?? '';
    $tanggal_mulai = $_POST['tanggal_mulai'] ?? '';
    $tanggal_selesai = $_POST['tanggal_selesai'] ?? '';

    if (empty($nama_peminjam)) $errors['nama_peminjam'] = 'Nama wajib diisi';
    if (empty($instansi)) $errors['instansi'] = 'Asal instansi wajib diisi';
    if (empty($id_fasilitas)) $errors['id_fasilitas'] = 'Pilih fasilitas';
    if (empty($tanggal_mulai)) $errors['tanggal_mulai'] = 'Pilih tanggal mulai';
    if (empty($tanggal_selesai)) $errors['tanggal_selesai'] = 'Pilih tanggal selesai';

    $surat_path = null;
    if (isset($_FILES['surat_pengantar']) && $_FILES['surat_pengantar']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (!in_array($_FILES['surat_pengantar']['type'], $allowed_types)) {
            $errors['surat_pengantar'] = 'Format file harus PDF, JPG, atau PNG';
        } elseif ($_FILES['surat_pengantar']['size'] > $max_size) {
            $errors['surat_pengantar'] = 'Ukuran file maksimal 2MB';
        } else {
            $ext = pathinfo($_FILES['surat_pengantar']['name'], PATHINFO_EXTENSION);
            $filename = 'surat_' . date('YmdHis') . '_' . uniqid() . '.' . $ext;
            $upload_dir = '../../uploads/lampiran/';
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            if (move_uploaded_file($_FILES['surat_pengantar']['tmp_name'], $upload_dir . $filename)) {
                $surat_path = 'uploads/lampiran/' . $filename;
            }
        }
    }

    if (empty($errors)) {
        $sql = "INSERT INTO peminjaman (nama_peminjam, instansi, id_fasilitas, tanggal_mulai, tanggal_selesai, surat_pengantar_path) 
                VALUES ($1, $2, $3, $4, $5, $6)";
        $result = pg_query_params($db, $sql, [$nama_peminjam, $instansi, $id_fasilitas, $tanggal_mulai, $tanggal_selesai, $surat_path]);
        
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
    <title>Peminjaman Fasilitas - DigiTamu UPA TIK</title>
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
                <h2 class="text-2xl font-bold text-slate-800 mb-2">Permohonan Terkirim!</h2>
                <p class="text-slate-600 mb-6">Kami akan memproses permohonan Anda dalam 1-2 hari kerja.</p>
                <a href="index.php" class="inline-block bg-[#38bdf8] hover:bg-[#0ea5e9] text-white font-semibold py-3 px-8 rounded-lg transition-colors">
                    Kembali ke Beranda
                </a>
            </div>
            <?php else: ?>

            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="p-6 md:p-8">
                    <h2 class="text-xl font-bold text-slate-800 mb-1">Formulir Peminjaman</h2>
                    <p class="text-slate-500 text-sm mb-6">Isi data untuk meminjam fasilitas atau peralatan UPA TIK.</p>

                    <?php if (isset($errors['general'])): ?>
                    <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 mb-6 text-red-700 text-sm">
                        <?= htmlspecialchars($errors['general']) ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" class="space-y-5">
                        
                        <!-- Nama Peminjam -->
                        <div>
                            <label for="nama_peminjam" class="block text-sm font-medium text-slate-700 mb-2">Nama Peminjam</label>
                            <input 
                                type="text" 
                                id="nama_peminjam" 
                                name="nama_peminjam" 
                                value="<?= htmlspecialchars($_POST['nama_peminjam'] ?? '') ?>"
                                class="w-full px-4 py-3 border <?= isset($errors['nama_peminjam']) ? 'border-red-300' : 'border-slate-200' ?> rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 outline-none text-sm"
                                placeholder="Nama Lengkap"
                            >
                            <?php if (isset($errors['nama_peminjam'])): ?>
                                <p class="mt-1 text-xs text-red-500"><?= $errors['nama_peminjam'] ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Asal Instansi -->
                        <div>
                            <label for="instansi" class="block text-sm font-medium text-slate-700 mb-2">Asal Instansi / UKM</label>
                            <input 
                                type="text" 
                                id="instansi" 
                                name="instansi" 
                                value="<?= htmlspecialchars($_POST['instansi'] ?? '') ?>"
                                class="w-full px-4 py-3 border <?= isset($errors['instansi']) ? 'border-red-300' : 'border-slate-200' ?> rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 outline-none text-sm"
                                placeholder="Contoh: BEM FT / Himpunan"
                            >
                            <?php if (isset($errors['instansi'])): ?>
                                <p class="mt-1 text-xs text-red-500"><?= $errors['instansi'] ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Fasilitas -->
                        <div>
                            <label for="id_fasilitas" class="block text-sm font-medium text-slate-700 mb-2">Fasilitas yang Dipinjam</label>
                            <select 
                                id="id_fasilitas" 
                                name="id_fasilitas"
                                class="w-full px-4 py-3 border <?= isset($errors['id_fasilitas']) ? 'border-red-300' : 'border-slate-200' ?> rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 outline-none text-sm bg-white"
                            >
                                <option value="">- Pilih Barang / Ruangan -</option>
                                <?php foreach ($fasilitas as $f): ?>
                                    <option value="<?= $f['id'] ?>" <?= ($_POST['id_fasilitas'] ?? '') == $f['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($f['nama_fasilitas']) ?> (<?= ucfirst($f['jenis']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['id_fasilitas'])): ?>
                                <p class="mt-1 text-xs text-red-500"><?= $errors['id_fasilitas'] ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Tanggal -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="tanggal_mulai" class="block text-sm font-medium text-slate-700 mb-2">Mulai Tanggal</label>
                                <input 
                                    type="datetime-local" 
                                    id="tanggal_mulai" 
                                    name="tanggal_mulai" 
                                    value="<?= htmlspecialchars($_POST['tanggal_mulai'] ?? '') ?>"
                                    class="w-full px-4 py-3 border <?= isset($errors['tanggal_mulai']) ? 'border-red-300' : 'border-slate-200' ?> rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 outline-none text-sm"
                                >
                                <?php if (isset($errors['tanggal_mulai'])): ?>
                                    <p class="mt-1 text-xs text-red-500"><?= $errors['tanggal_mulai'] ?></p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <label for="tanggal_selesai" class="block text-sm font-medium text-slate-700 mb-2">Sampai Tanggal</label>
                                <input 
                                    type="datetime-local" 
                                    id="tanggal_selesai" 
                                    name="tanggal_selesai" 
                                    value="<?= htmlspecialchars($_POST['tanggal_selesai'] ?? '') ?>"
                                    class="w-full px-4 py-3 border <?= isset($errors['tanggal_selesai']) ? 'border-red-300' : 'border-slate-200' ?> rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 outline-none text-sm"
                                >
                                <?php if (isset($errors['tanggal_selesai'])): ?>
                                    <p class="mt-1 text-xs text-red-500"><?= $errors['tanggal_selesai'] ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Upload Surat -->
                        <div>
                            <label for="surat_pengantar" class="block text-sm font-medium text-slate-700 mb-2">Upload Surat Pengantar (PDF/JPG)</label>
                            <input 
                                type="file" 
                                id="surat_pengantar" 
                                name="surat_pengantar"
                                accept=".pdf,.jpg,.jpeg,.png"
                                class="w-full px-4 py-3 border border-slate-200 rounded-lg text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100"
                            >
                            <p class="mt-1 text-xs text-slate-500">Maksimal ukuran file 2MB.</p>
                            <?php if (isset($errors['surat_pengantar'])): ?>
                                <p class="mt-1 text-xs text-red-500"><?= $errors['surat_pengantar'] ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Submit Button -->
                        <button 
                            type="submit"
                            class="w-full bg-[#38bdf8] hover:bg-[#0ea5e9] text-white font-semibold py-4 rounded-lg transition-colors text-base"
                        >
                            Ajukan Peminjaman
                        </button>

                    </form>
                </div>
            </div>

            <?php endif; ?>
        </div>
    </main>

</body>
</html>
