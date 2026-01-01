<?php
$base_url = '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DigiTamu - Portal Layanan Digital UPA TIK Universitas Lampung</title>
    <link href="../../assets/css/output.css" rel="stylesheet">
    <link href="../../assets/css/custom.css" rel="stylesheet">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    <style>
        body { 
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

    <main class="flex-1">
        <section class="py-12 px-6">
            <div class="max-w-4xl mx-auto text-center">
                <h2 class="text-3xl md:text-4xl font-bold text-slate-800 mb-4">
                    Selamat Datang di DigiTamu
                </h2>
                <p class="text-slate-600 text-base md:text-lg mb-10 max-w-2xl mx-auto">
                    Platform layanan digital terpadu UPA TIK Universitas Lampung. Silakan pilih layanan yang Anda butuhkan untuk memulai.
                </p>

                <!-- Service Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-3xl mx-auto">
                    
                    <!-- Janji Temu Online -->
                    <div class="service-card bg-white rounded-2xl p-8 text-center">
                        <div class="w-16 h-16 bg-sky-50 rounded-2xl flex items-center justify-center mx-auto mb-5">
                            <svg class="w-8 h-8 text-[#38bdf8]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 mb-3">Janji Temu Online</h3>
                        <p class="text-slate-500 text-sm mb-6 leading-relaxed">
                            Jadwalkan pertemuan dengan staf teknis atau administrasi UPA TIK tanpa harus datang mengantri.
                        </p>
                        <a href="janji-temu.php" class="block w-full bg-[#38bdf8] hover:bg-[#0ea5e9] text-white font-semibold py-3 px-6 rounded-lg transition-colors text-sm">
                            Buat Jadwal
                        </a>
                    </div>

                    <!-- Peminjaman Fasilitas -->
                    <div class="service-card bg-white rounded-2xl p-8 text-center">
                        <div class="w-16 h-16 bg-sky-50 rounded-2xl flex items-center justify-center mx-auto mb-5">
                            <svg class="w-8 h-8 text-[#38bdf8]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 mb-3">Peminjaman Fasilitas</h3>
                        <p class="text-slate-500 text-sm mb-6 leading-relaxed">
                            Ajukan permohonan peminjaman laboratorium komputer, ruang server, atau peralatan multimedia.
                        </p>
                        <a href="peminjaman.php" class="block w-full bg-[#38bdf8] hover:bg-[#0ea5e9] text-white font-semibold py-3 px-6 rounded-lg transition-colors text-sm">
                            Ajukan Pinjaman
                        </a>
                    </div>

                </div>
            </div>
        </section>

        <!-- Info Section -->
        <section class="py-12 px-6 bg-white">
            <div class="max-w-5xl mx-auto">
                <h3 class="text-xl font-bold text-slate-800 text-center mb-10">Informasi Layanan</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    
                    <!-- Jam Operasional -->
                    <div>
                        <h4 class="font-bold text-[#38bdf8] mb-3">Jam Operasional</h4>
                        <ul class="text-slate-600 text-sm space-y-1">
                            <li>Senin - Kamis: 08.00 - 16.00 WIB</li>
                            <li>Jumat: 08.00 - 16.30 WIB</li>
                            <li>Sabtu - Minggu: Tutup</li>
                        </ul>
                    </div>

                    <!-- Layanan Utama -->
                    <div>
                        <h4 class="font-bold text-[#38bdf8] mb-3">Layanan Utama</h4>
                        <ul class="text-slate-600 text-sm space-y-1">
                            <li>• Maintenance Jaringan & Server</li>
                            <li>• Pengembangan Sistem Informasi</li>
                            <li>• Akun SSO & Email Unila</li>
                        </ul>
                    </div>

                    <!-- Pusat Bantuan -->
                    <div>
                        <h4 class="font-bold text-[#38bdf8] mb-3">Pusat Bantuan</h4>
                        <p class="text-slate-600 text-sm">
                            Silakan hubungi Helpdesk kami jika mengalami kendala teknis pada sistem ini.
                        </p>
                    </div>

                </div>
            </div>
        </section>
    </main>

    <?php include '../../includes/footer-public.php'; ?>

</body>
</html>
