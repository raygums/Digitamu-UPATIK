<?php
session_start();
require_once '../../config/database.php';

$base_url = '../';
$errors = [];
$success = false;

$keperluan = fetchAll(query("SELECT id_layanan, nama_layanan FROM jenis_layanan ORDER BY id_layanan"));
$unit_kerja_parent = fetchAll(query("
    SELECT DISTINCT 
        CASE 
            WHEN nama_unit LIKE 'FEB%' THEN 'FEB - Fakultas Ekonomi dan Bisnis'
            WHEN nama_unit LIKE 'FISIP%' THEN 'FISIP - Fakultas Ilmu Sosial dan Ilmu Politik'
            WHEN nama_unit LIKE 'FH%' THEN 'FH - Fakultas Hukum'
            WHEN nama_unit LIKE 'FKIP%' THEN 'FKIP - Fakultas Keguruan dan Ilmu Pendidikan'
            WHEN nama_unit LIKE 'FMIPA%' THEN 'FMIPA - Fakultas Matematika dan Ilmu Pengetahuan Alam'
            WHEN nama_unit LIKE 'FP%' THEN 'FP - Fakultas Pertanian'
            WHEN nama_unit LIKE 'FT%' THEN 'FT - Fakultas Teknik'
            WHEN nama_unit LIKE 'FK%' THEN 'FK - Fakultas Kedokteran'
            ELSE nama_unit
        END as kategori,
        CASE 
            WHEN nama_unit LIKE 'FEB%' THEN 1
            WHEN nama_unit LIKE 'FISIP%' THEN 2
            WHEN nama_unit LIKE 'FH%' THEN 3
            WHEN nama_unit LIKE 'FKIP%' THEN 4
            WHEN nama_unit LIKE 'FMIPA%' THEN 5
            WHEN nama_unit LIKE 'FP%' THEN 6
            WHEN nama_unit LIKE 'FT%' THEN 7
            WHEN nama_unit LIKE 'FK%' THEN 8
            WHEN nama_unit = 'LPPM Unila' THEN 9
            WHEN nama_unit = 'Rektorat' THEN 10
            WHEN nama_unit = 'UKM' THEN 11
            WHEN nama_unit LIKE 'UPA%' THEN 12
            WHEN nama_unit = 'Lainnya' THEN 99
            ELSE 50
        END as urutan
    FROM unit_kerja
    ORDER BY urutan, kategori
"));

$unit_kerja_all = fetchAll(query("SELECT id_unit, nama_unit FROM unit_kerja ORDER BY nama_unit"));
$unit_kerja_json = [];
foreach ($unit_kerja_all as $uk) {
    $prefix = '';
    if (strpos($uk['nama_unit'], 'FEB -') === 0) $prefix = 'FEB';
    elseif (strpos($uk['nama_unit'], 'FISIP -') === 0) $prefix = 'FISIP';
    elseif (strpos($uk['nama_unit'], 'FH -') === 0) $prefix = 'FH';
    elseif (strpos($uk['nama_unit'], 'FKIP -') === 0) $prefix = 'FKIP';
    elseif (strpos($uk['nama_unit'], 'FMIPA -') === 0) $prefix = 'FMIPA';
    elseif (strpos($uk['nama_unit'], 'FP -') === 0) $prefix = 'FP';
    elseif (strpos($uk['nama_unit'], 'FT -') === 0) $prefix = 'FT';
    elseif (strpos($uk['nama_unit'], 'FK -') === 0) $prefix = 'FK';
    
    if ($prefix) {
        if (!isset($unit_kerja_json[$prefix])) {
            $unit_kerja_json[$prefix] = [];
        }
        $unit_kerja_json[$prefix][] = [
            'id' => $uk['id_unit'],
            'nama' => str_replace($prefix . ' - ', '', $uk['nama_unit'])
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_telp = trim($_POST['no_telp'] ?? '');
    $jenis_instansi = $_POST['jenis_instansi'] ?? 'Eksternal';
    $kategori_unit = $_POST['kategori_unit'] ?? '';
    $id_unit = $_POST['id_unit'] ?? null;
    $unit_lainnya = trim($_POST['unit_lainnya'] ?? '');
    $instansi_lain = trim($_POST['instansi_lain'] ?? '');
    $id_layanan = $_POST['id_layanan'] ?? '';
    $keperluan_lainnya = trim($_POST['keperluan_lainnya'] ?? '');
    $detail_keperluan = trim($_POST['detail_keperluan'] ?? '');
    $foto_base64 = $_POST['foto_base64'] ?? '';

    if (empty($nama)) $errors['nama'] = 'Nama wajib diisi';
    if ($jenis_instansi === 'Internal') {
        if (empty($kategori_unit)) $errors['kategori_unit'] = 'Pilih kategori unit kerja';
        elseif ($kategori_unit === 'Lainnya' && empty($unit_lainnya)) $errors['unit_lainnya'] = 'Unit kerja lainnya wajib diisi';
        else {
            $prefix = explode(' - ', $kategori_unit)[0];
            if (in_array($prefix, ['FEB', 'FISIP', 'FH', 'FKIP', 'FMIPA', 'FP', 'FT', 'FK']) && empty($id_unit)) {
                $errors['id_unit'] = 'Pilih unit kerja / jurusan';
            }
        }
    }
    if ($jenis_instansi === 'Eksternal' && empty($instansi_lain)) $errors['instansi_lain'] = 'Asal instansi wajib diisi';
    if (empty($id_layanan)) $errors['id_layanan'] = 'Pilih keperluan kunjungan';
    
    $nama_layanan_selected = '';
    foreach ($keperluan as $kp) {
        if ($kp['id_layanan'] == $id_layanan) {
            $nama_layanan_selected = strtolower($kp['nama_layanan']);
            break;
        }
    }
    if ($nama_layanan_selected === 'lainnya' && empty($keperluan_lainnya)) {
        $errors['keperluan_lainnya'] = 'Keperluan lainnya wajib diisi';
    }

    $foto_filename = null;
    if (!empty($foto_base64)) {
        $foto_data = explode(',', $foto_base64);
        if (count($foto_data) === 2) {
            $foto_decoded = base64_decode($foto_data[1]);
            $filename = 'tamu_' . date('YmdHis') . '_' . uniqid() . '.jpg';
            $upload_dir = '../../uploads/foto_tamu/';
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            if (file_put_contents($upload_dir . $filename, $foto_decoded)) {
                $foto_filename = $filename;
            }
        }
    }

    if (empty($errors)) {
        pg_query($db, "BEGIN");
        
        try {
            $final_detail = $detail_keperluan;
            if (!empty($keperluan_lainnya)) {
                $final_detail = $keperluan_lainnya . ($detail_keperluan ? ' - ' . $detail_keperluan : '');
            }
            
            $id_unit_val = null;
            $instansi_val = null;
            
            if ($jenis_instansi === 'Internal') {
                if ($kategori_unit === 'Lainnya') {
                    $instansi_val = $unit_lainnya;
                } elseif (!empty($id_unit)) {
                    $id_unit_val = $id_unit;
                } else {
                    $unit_selected = '';
                    foreach ($unit_kerja_parent as $ukp) {
                        if ($ukp['kategori'] === $kategori_unit) {
                            $unit_selected = $kategori_unit;
                            break;
                        }
                    }
                    if ($unit_selected) {
                        $result = fetchOne(query("SELECT id_unit FROM unit_kerja WHERE nama_unit LIKE '" . pg_escape_string($db, $unit_selected) . "%' LIMIT 1"));
                        if ($result) $id_unit_val = $result['id_unit'];
                    }
                }
            } else {
                $instansi_val = $instansi_lain;
            }
            
            $sql_tamu = "INSERT INTO tamu (nama_lengkap, email, no_telp, id_unit, instansi_lain, jenis_instansi, foto, created_at) VALUES ($1, $2, $3, $4, $5, $6, $7, NOW()) RETURNING id_tamu";
            $result_tamu = pg_query_params($db, $sql_tamu, [$nama, $email ?: null, $no_telp ?: null, $id_unit_val, $instansi_val, $jenis_instansi, $foto_filename]);
            $row_tamu = pg_fetch_assoc($result_tamu);
            $id_tamu = $row_tamu['id_tamu'];

            $sql_kunjungan = "INSERT INTO kunjungan (id_tamu, id_layanan, detail_keperluan, waktu_masuk, status) VALUES ($1, $2, $3, NOW(), 'Masuk')";
            pg_query_params($db, $sql_kunjungan, [$id_tamu, $id_layanan, $final_detail ?: null]);
            
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
                                <label for="no_telp" class="block text-sm font-medium text-slate-700 mb-2">No. HP</label>
                                <input 
                                    type="tel" 
                                    id="no_telp" 
                                    name="no_telp" 
                                    value="<?= htmlspecialchars($_POST['no_telp'] ?? '') ?>"
                                    class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 outline-none text-sm bg-slate-50"
                                    placeholder="08xxxxxxxxxx"
                                >
                            </div>
                        </div>

                        <!-- Tipe Tamu -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Tipe Pengunjung <span class="text-red-500">*</span></label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="jenis_instansi" value="Internal" id="tipeInternal" onchange="toggleInstansi()" <?= ($_POST['jenis_instansi'] ?? '') === 'Internal' ? 'checked' : '' ?> class="w-4 h-4 text-sky-500">
                                    <span class="text-sm">Internal Unila</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="jenis_instansi" value="Eksternal" id="tipeEksternal" onchange="toggleInstansi()" <?= ($_POST['jenis_instansi'] ?? 'Eksternal') === 'Eksternal' ? 'checked' : '' ?> class="w-4 h-4 text-sky-500">
                                    <span class="text-sm">Eksternal</span>
                                </label>
                            </div>
                        </div>

                        <div id="unitKerjaContainer" class="<?= ($_POST['jenis_instansi'] ?? 'Eksternal') === 'Eksternal' ? 'hidden' : '' ?>">
                            <label for="kategori_unit" class="block text-sm font-medium text-slate-700 mb-2">Kategori Unit Kerja <span class="text-red-500">*</span></label>
                            <select 
                                id="kategori_unit" 
                                name="kategori_unit"
                                onchange="toggleSubUnit()"
                                class="w-full px-4 py-3 border <?= isset($errors['kategori_unit']) ? 'border-red-300' : 'border-slate-200' ?> rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 outline-none text-sm bg-slate-50"
                            >
                                <option value="">- Pilih Kategori -</option>
                                <?php foreach ($unit_kerja_parent as $ukp): ?>
                                    <option value="<?= htmlspecialchars($ukp['kategori']) ?>" <?= ($_POST['kategori_unit'] ?? '') === $ukp['kategori'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($ukp['kategori']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['kategori_unit'])): ?>
                                <p class="mt-1 text-xs text-red-500"><?= $errors['kategori_unit'] ?></p>
                            <?php endif; ?>
                        </div>

                        <div id="subUnitContainer" class="hidden">
                            <label for="id_unit" class="block text-sm font-medium text-slate-700 mb-2"><span id="labelSubUnit">Unit Kerja</span> <span class="text-red-500">*</span></label>
                            <select 
                                id="id_unit" 
                                name="id_unit"
                                class="w-full px-4 py-3 border <?= isset($errors['id_unit']) ? 'border-red-300' : 'border-slate-200' ?> rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 outline-none text-sm bg-slate-50"
                            >
                                <option value="">- Pilih -</option>
                            </select>
                            <?php if (isset($errors['id_unit'])): ?>
                                <p class="mt-1 text-xs text-red-500"><?= $errors['id_unit'] ?></p>
                            <?php endif; ?>
                        </div>

                        <div id="unitLainnyaContainer" class="hidden">
                            <label for="unit_lainnya" class="block text-sm font-medium text-slate-700 mb-2">Unit Kerja Lainnya <span class="text-red-500">*</span></label>
                            <input 
                                type="text" 
                                id="unit_lainnya" 
                                name="unit_lainnya" 
                                value="<?= htmlspecialchars($_POST['unit_lainnya'] ?? '') ?>"
                                class="w-full px-4 py-3 border <?= isset($errors['unit_lainnya']) ? 'border-red-300' : 'border-slate-200' ?> rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 outline-none text-sm bg-slate-50"
                                placeholder="Sebutkan unit kerja Anda..."
                            >
                            <?php if (isset($errors['unit_lainnya'])): ?>
                                <p class="mt-1 text-xs text-red-500"><?= $errors['unit_lainnya'] ?></p>
                            <?php endif; ?>
                        </div>

                        <div id="instansiLainContainer" class="<?= ($_POST['jenis_instansi'] ?? 'Eksternal') === 'Internal' ? 'hidden' : '' ?>">
                            <label for="instansi_lain" class="block text-sm font-medium text-slate-700 mb-2">Asal Instansi <span class="text-red-500">*</span></label>
                            <input 
                                type="text" 
                                id="instansi_lain" 
                                name="instansi_lain" 
                                value="<?= htmlspecialchars($_POST['instansi_lain'] ?? '') ?>"
                                class="w-full px-4 py-3 border <?= isset($errors['instansi_lain']) ? 'border-red-300' : 'border-slate-200' ?> rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 outline-none text-sm bg-slate-50"
                                placeholder="Contoh: PT ABC / Universitas XYZ"
                            >
                            <?php if (isset($errors['instansi_lain'])): ?>
                                <p class="mt-1 text-xs text-red-500"><?= $errors['instansi_lain'] ?></p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label for="id_layanan" class="block text-sm font-medium text-slate-700 mb-2">Keperluan Kunjungan <span class="text-red-500">*</span></label>
                            <select 
                                id="id_layanan" 
                                name="id_layanan"
                                onchange="toggleKeperluanLainnya()"
                                class="w-full px-4 py-3 border <?= isset($errors['id_layanan']) ? 'border-red-300' : 'border-slate-200' ?> rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 outline-none text-sm bg-slate-50"
                            >
                                <option value="">- Pilih Layanan -</option>
                                <?php foreach ($keperluan as $kp): ?>
                                    <option value="<?= $kp['id_layanan'] ?>" data-nama="<?= strtolower($kp['nama_layanan']) ?>" <?= ($_POST['id_layanan'] ?? '') == $kp['id_layanan'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($kp['nama_layanan']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['id_layanan'])): ?>
                                <p class="mt-1 text-xs text-red-500"><?= $errors['id_layanan'] ?></p>
                            <?php endif; ?>
                        </div>

                        <div id="keperluanLainnyaContainer" class="hidden">
                            <label for="keperluan_lainnya" class="block text-sm font-medium text-slate-700 mb-2">Keperluan Lainnya <span class="text-red-500">*</span></label>
                            <input 
                                type="text" 
                                id="keperluan_lainnya" 
                                name="keperluan_lainnya" 
                                value="<?= htmlspecialchars($_POST['keperluan_lainnya'] ?? '') ?>"
                                class="w-full px-4 py-3 border <?= isset($errors['keperluan_lainnya']) ? 'border-red-300' : 'border-slate-200' ?> rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 outline-none text-sm bg-slate-50"
                                placeholder="Jelaskan keperluan Anda..."
                            >
                            <?php if (isset($errors['keperluan_lainnya'])): ?>
                                <p class="mt-1 text-xs text-red-500"><?= $errors['keperluan_lainnya'] ?></p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label for="detail_keperluan" class="block text-sm font-medium text-slate-700 mb-2">Detail Tambahan (Opsional)</label>
                            <textarea 
                                id="detail_keperluan" 
                                name="detail_keperluan" 
                                rows="2"
                                class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 outline-none text-sm bg-slate-50 resize-none"
                                placeholder="Informasi tambahan..."
                            ><?= htmlspecialchars($_POST['detail_keperluan'] ?? '') ?></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Foto</label>
                            <div class="camera-container bg-slate-200 rounded-lg h-80 flex items-center justify-center cursor-pointer" id="cameraContainer" onclick="startCamera()">
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
        const unitKerjaData = <?= json_encode($unit_kerja_json) ?>;
        
        let stream = null;
        const video = document.getElementById('videoPreview');
        const canvas = document.getElementById('photoCanvas');
        const photoPreview = document.getElementById('photoPreview');
        const placeholder = document.getElementById('cameraPlaceholder');
        const buttons = document.getElementById('cameraButtons');
        const fotoInput = document.getElementById('fotoBase64');

        function toggleInstansi() {
            const isInternal = document.getElementById('tipeInternal').checked;
            const unitKerjaContainer = document.getElementById('unitKerjaContainer');
            const instansiLainContainer = document.getElementById('instansiLainContainer');
            const subUnitContainer = document.getElementById('subUnitContainer');
            const unitLainnyaContainer = document.getElementById('unitLainnyaContainer');
            
            if (isInternal) {
                unitKerjaContainer.classList.remove('hidden');
                instansiLainContainer.classList.add('hidden');
            } else {
                unitKerjaContainer.classList.add('hidden');
                subUnitContainer.classList.add('hidden');
                unitLainnyaContainer.classList.add('hidden');
                instansiLainContainer.classList.remove('hidden');
            }
        }

        function toggleSubUnit() {
            const kategoriSelect = document.getElementById('kategori_unit');
            const kategori = kategoriSelect.value;
            const subUnitContainer = document.getElementById('subUnitContainer');
            const unitLainnyaContainer = document.getElementById('unitLainnyaContainer');
            const idUnitSelect = document.getElementById('id_unit');
            const labelSubUnit = document.getElementById('labelSubUnit');
            
            subUnitContainer.classList.add('hidden');
            unitLainnyaContainer.classList.add('hidden');
            
            if (kategori === 'Lainnya') {
                unitLainnyaContainer.classList.remove('hidden');
            } else if (kategori) {
                const prefix = kategori.split(' - ')[0];
                const isFakultas = ['FEB', 'FISIP', 'FH', 'FKIP', 'FMIPA', 'FP', 'FT', 'FK'].includes(prefix);
                
                if (unitKerjaData[prefix] && unitKerjaData[prefix].length > 0) {
                    idUnitSelect.innerHTML = '<option value="">- Pilih -</option>';
                    unitKerjaData[prefix].forEach(unit => {
                        const option = document.createElement('option');
                        option.value = unit.id;
                        option.textContent = unit.nama;
                        idUnitSelect.appendChild(option);
                    });
                    
                    labelSubUnit.textContent = isFakultas ? 'Jurusan / Program Studi' : 'Unit Kerja';
                    subUnitContainer.classList.remove('hidden');
                }
            }
        }

        function toggleKeperluanLainnya() {
            const select = document.getElementById('id_layanan');
            const selectedOption = select.options[select.selectedIndex];
            const namaLayanan = selectedOption?.getAttribute('data-nama') || '';
            const container = document.getElementById('keperluanLainnyaContainer');
            
            if (namaLayanan === 'lainnya') {
                container.classList.remove('hidden');
            } else {
                container.classList.add('hidden');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            toggleInstansi();
            toggleKeperluanLainnya();
            toggleSubUnit();
        });

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