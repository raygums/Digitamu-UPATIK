<?php
/**
 * Form Buku Tamu On-Site (Kiosk Mode)
 * Untuk tamu yang datang langsung ke UPA TIK
 */
session_start();
require_once '../config/database.php';

$base_url = '../';
$errors = [];
$success = false;

// Ambil data referensi untuk dropdown
$keperluan = fetchAll(query("SELECT id, nama_keperluan FROM ref_keperluan WHERE deleted_at IS NULL ORDER BY id"));

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $instansi = trim($_POST['instansi'] ?? '');
    $tipe = $_POST['tipe'] ?? 'eksternal';
    $id_keperluan = $_POST['id_keperluan'] ?? '';
    $detail_keperluan = trim($_POST['detail_keperluan'] ?? '');
    $alasan_kunjungan = trim($_POST['alasan_kunjungan'] ?? '');
    $foto_base64 = $_POST['foto_base64'] ?? '';

    // Validasi
    if (empty($nama)) $errors['nama'] = 'Nama wajib diisi';
    if (empty($instansi)) $errors['instansi'] = 'Asal instansi wajib diisi';
    if (empty($id_keperluan)) $errors['id_keperluan'] = 'Pilih keperluan kunjungan';

    // Proses foto dari kamera (base64)
    $foto_filename = null;
    if (!empty($foto_base64)) {
        $foto_data = explode(',', $foto_base64);
        if (count($foto_data) === 2) {
            $foto_decoded = base64_decode($foto_data[1]);
            $filename = 'tamu_' . date('YmdHis') . '_' . uniqid() . '.jpg';
            $upload_dir = '../uploads/foto_tamu/';
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            if (file_put_contents($upload_dir . $filename, $foto_decoded)) {
                $foto_filename = $filename;
            }
        }
    }

    // Simpan ke database
    if (empty($errors)) {
        pg_query($db, "BEGIN");
        
        try {
            // Insert tamu
            $sql_tamu = "INSERT INTO tamu (nama, email, no_hp, instansi, tipe, foto) VALUES ($1, $2, $3, $4, $5, $6) RETURNING id";
            $result_tamu = pg_query_params($db, $sql_tamu, [$nama, $email ?: null, $no_hp ?: null, $instansi, $tipe, $foto_filename]);
            $row_tamu = pg_fetch_assoc($result_tamu);
            $id_tamu = $row_tamu['id'];

            // Insert kunjungan
            $sql_kunjungan = "INSERT INTO kunjungan (id_tamu, id_keperluan, detail_keperluan, alasan_kunjungan) VALUES ($1, $2, $3, $4)";
            pg_query_params($db, $sql_kunjungan, [$id_tamu, $id_keperluan, $detail_keperluan ?: null, $alasan_kunjungan ?: null]);
            
            pg_query($db, "COMMIT");
            $success = true;
            
        } catch (Exception $e) {
            pg_query($db, "ROLLBACK");
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
    <title>Buku Tamu - DigiTamu UPA TIK</title>
    <link href="../assets/css/output.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Figtree', system-ui, sans-serif; }</style>
</head>
<body class="bg-topografi min-h-screen flex flex-col">

    <?php include '../includes/header-public.php'; ?>

    <main class="flex-1 py-8 px-4">
        <div class="max-w-xl mx-auto">
            
            <!-- Success Message -->
            <?php if ($success): ?>
            <div class="bg-white rounded-2xl shadow-lg p-8 text-center">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-green-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-slate-800 mb-2">Check-In Berhasil!</h2>
                <p class="text-slate-600 mb-6">Terima kasih telah berkunjung. Silakan tunggu di ruang tunggu.</p>
                <a href="tamu.php" class="inline-block bg-[#38bdf8] hover:bg-[#0ea5e9] text-white font-semibold py-3 px-8 rounded-lg transition-colors">
                    Tamu Baru
                </a>
            </div>
            <?php else: ?>

            <!-- Form Card -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                
                <div class="p-6 md:p-8">
                    <h2 class="text-xl font-bold text-slate-800 mb-1">Selamat Datang!</h2>
                    <p class="text-slate-500 text-sm mb-6">Silakan isi data diri Anda</p>

                    <?php if (isset($errors['general'])): ?>
                    <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 mb-6 text-red-700 text-sm">
                        <?= htmlspecialchars($errors['general']) ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" id="formTamu" class="space-y-5">
                        
                        <!-- Nama Lengkap -->
                        <div>
                            <label for="nama" class="block text-sm font-medium text-slate-700 mb-2">Nama Lengkap <span class="text-red-500">*</span></label>
                            <input 
                                type="text" 
                                id="nama" 
                                name="nama" 
                                value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"
                                class="w-full px-4 py-3 border <?= isset($errors['nama']) ? 'border-red-300' : 'border-slate-200' ?> rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 outline-none text-sm bg-slate-50"
                                placeholder="Contoh: Budi Santoso"
                            >
                            <?php if (isset($errors['nama'])): ?>
                                <p class="mt-1 text-xs text-red-500"><?= $errors['nama'] ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Email & No HP -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="email" class="block text-sm font-medium text-slate-700 mb-2">Email</label>
                                <input 
                                    type="email" 
                                    id="email" 
                                    name="email" 
                                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                    class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 outline-none text-sm bg-slate-50"
                                    placeholder="user@email.com"
                                >
                            </div>
                            <div>
                                <label for="no_hp" class="block text-sm font-medium text-slate-700 mb-2">No. HP</label>
                                <input 
                                    type="tel" 
                                    id="no_hp" 
                                    name="no_hp" 
                                    value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>"
                                    class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 outline-none text-sm bg-slate-50"
                                    placeholder="08xxxxxxxxxx"
                                >
                            </div>
                        </div>

                        <!-- Tipe Tamu -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Tipe Pengunjung</label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="tipe" value="internal" <?= ($_POST['tipe'] ?? '') === 'internal' ? 'checked' : '' ?> class="w-4 h-4 text-sky-500">
                                    <span class="text-sm">Internal Unila</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="tipe" value="eksternal" <?= ($_POST['tipe'] ?? 'eksternal') === 'eksternal' ? 'checked' : '' ?> class="w-4 h-4 text-sky-500">
                                    <span class="text-sm">Eksternal</span>
                                </label>
                            </div>
                        </div>

                        <!-- Asal Instansi -->
                        <div>
                            <label for="instansi" class="block text-sm font-medium text-slate-700 mb-2">Asal Instansi / Unit Kerja <span class="text-red-500">*</span></label>
                            <input 
                                type="text" 
                                id="instansi" 
                                name="instansi" 
                                value="<?= htmlspecialchars($_POST['instansi'] ?? '') ?>"
                                class="w-full px-4 py-3 border <?= isset($errors['instansi']) ? 'border-red-300' : 'border-slate-200' ?> rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 outline-none text-sm bg-slate-50"
                                placeholder="Contoh: Fakultas Teknik / PT ABC"
                            >
                            <?php if (isset($errors['instansi'])): ?>
                                <p class="mt-1 text-xs text-red-500"><?= $errors['instansi'] ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Keperluan Kunjungan -->
                        <div>
                            <label for="id_keperluan" class="block text-sm font-medium text-slate-700 mb-2">Keperluan Kunjungan <span class="text-red-500">*</span></label>
                            <select 
                                id="id_keperluan" 
                                name="id_keperluan"
                                class="w-full px-4 py-3 border <?= isset($errors['id_keperluan']) ? 'border-red-300' : 'border-slate-200' ?> rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 outline-none text-sm bg-slate-50"
                            >
                                <option value="">- Pilih Layanan -</option>
                                <?php foreach ($keperluan as $kp): ?>
                                    <option value="<?= $kp['id'] ?>" <?= ($_POST['id_keperluan'] ?? '') == $kp['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($kp['nama_keperluan']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['id_keperluan'])): ?>
                                <p class="mt-1 text-xs text-red-500"><?= $errors['id_keperluan'] ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Detail Keperluan -->
                        <div>
                            <label for="detail_keperluan" class="block text-sm font-medium text-slate-700 mb-2">Detail Keperluan (Opsional)</label>
                            <input 
                                type="text" 
                                id="detail_keperluan" 
                                name="detail_keperluan" 
                                value="<?= htmlspecialchars($_POST['detail_keperluan'] ?? '') ?>"
                                class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 outline-none text-sm bg-slate-50"
                                placeholder="Jelaskan keperluan spesifik..."
                            >
                        </div>

                        <!-- Alasan Kunjungan -->
                        <div>
                            <label for="alasan_kunjungan" class="block text-sm font-medium text-slate-700 mb-2">Alasan Kunjungan</label>
                            <textarea 
                                id="alasan_kunjungan" 
                                name="alasan_kunjungan" 
                                rows="3"
                                class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 outline-none text-sm bg-slate-50 resize-none"
                                placeholder="Jelaskan alasan..."
                            ><?= htmlspecialchars($_POST['alasan_kunjungan'] ?? '') ?></textarea>
                        </div>

                        <!-- Camera / Foto -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Foto</label>
                            <div class="camera-container bg-slate-200 rounded-lg h-48 flex items-center justify-center cursor-pointer" id="cameraContainer" onclick="startCamera()">
                                <video id="videoPreview" class="hidden w-full h-full object-cover rounded-lg"></video>
                                <img id="photoPreview" class="hidden w-full h-full object-cover rounded-lg">
                                <div id="cameraPlaceholder" class="text-center text-slate-500">
                                    <svg class="w-10 h-10 mx-auto mb-2 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/>
                                    </svg>
                                    <p class="text-sm">Ambil Foto</p>
                                </div>
                            </div>
                            <canvas id="photoCanvas" class="hidden"></canvas>
                            <input type="hidden" name="foto_base64" id="fotoBase64">
                            <div id="cameraButtons" class="hidden mt-3 gap-2">
                                <button type="button" onclick="capturePhoto()" class="flex-1 bg-[#38bdf8] text-white py-2 px-4 rounded-lg text-sm font-medium">
                                    📸 Ambil Foto
                                </button>
                                <button type="button" onclick="retakePhoto()" class="flex-1 bg-slate-200 text-slate-700 py-2 px-4 rounded-lg text-sm font-medium">
                                    🔄 Ulangi
                                </button>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button 
                            type="submit"
                            class="w-full bg-[#38bdf8] hover:bg-[#0ea5e9] text-white font-semibold py-4 rounded-lg transition-colors text-base mt-6"
                        >
                            Check In
                        </button>

                    </form>
                </div>
            </div>

            <?php endif; ?>
        </div>
    </main>

    <script>
        let stream = null;
        const video = document.getElementById('videoPreview');
        const canvas = document.getElementById('photoCanvas');
        const photoPreview = document.getElementById('photoPreview');
        const placeholder = document.getElementById('cameraPlaceholder');
        const buttons = document.getElementById('cameraButtons');
        const fotoInput = document.getElementById('fotoBase64');

        async function startCamera() {
            if (stream) return;
            
            try {
                stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { facingMode: 'user', width: 640, height: 480 } 
                });
                video.srcObject = stream;
                video.play();
                video.classList.remove('hidden');
                placeholder.classList.add('hidden');
                buttons.classList.remove('hidden');
            } catch (err) {
                alert('Tidak dapat mengakses kamera. Pastikan izin kamera diaktifkan.');
                console.error(err);
            }
        }

        function capturePhoto() {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0);
            
            const dataURL = canvas.toDataURL('image/jpeg', 0.8);
            fotoInput.value = dataURL;
            
            photoPreview.src = dataURL;
            photoPreview.classList.remove('hidden');
            video.classList.add('hidden');
            
            // Stop camera
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
        }

        function retakePhoto() {
            photoPreview.classList.add('hidden');
            fotoInput.value = '';
            startCamera();
        }
    </script>

</body>
</html>
