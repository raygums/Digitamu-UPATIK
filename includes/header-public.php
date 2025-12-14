<?php
/**
 * Header Component untuk halaman publik (tamu)
 * Menampilkan logo Unila dan UPA TIK
 */
?>
<header class="bg-[#38bdf8] py-4 px-6 flex items-center justify-between">
    <div class="flex items-center gap-4">
        <img src="https://upload.wikimedia.org/wikipedia/id/8/8d/Logo_Unila.png" alt="Logo Unila" class="h-12 w-12 object-contain bg-white rounded-full p-1">
        <div class="text-white">
            <h1 class="text-lg font-bold tracking-wide">UNIVERSITAS LAMPUNG</h1>
            <p class="text-sm italic">Be Strong!</p>
        </div>
    </div>
    <div class="flex items-center">
        <img src="<?= $base_url ?? '' ?>assets/images/logo-upatik.png" alt="Logo UPA TIK" class="h-12 object-contain" onerror="this.style.display='none'">
        <div class="text-white text-right hidden sm:block">
            <span class="text-xl font-bold">UPA<span class="text-red-500">TIK</span></span>
            <p class="text-xs">UNILA</p>
        </div>
    </div>
</header>
