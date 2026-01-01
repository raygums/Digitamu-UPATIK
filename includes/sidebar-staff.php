<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="fixed left-0 top-0 w-56 h-full bg-[#1e293b] text-white flex flex-col z-50">
    
    <div class="px-5 py-5 flex items-center justify-center border-b border-slate-700">
        <div class="text-center">
            <div class="flex items-center justify-center gap-2">
                <div class="w-8 h-8 bg-gradient-to-br from-sky-400 to-sky-600 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>
                    </svg>
                </div>
                <span class="text-lg font-bold text-white">UPA <span class="text-sky-400">TIK</span></span>
            </div>
            <p class="text-xs text-slate-400 mt-1">Universitas Lampung</p>
        </div>
    </div>

    <nav class="flex-1 px-3 py-4">
        <ul class="space-y-1">
            <li>
                <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-lg <?= $current_page === 'dashboard.php' ? 'sidebar-menu-active text-white' : 'text-slate-400 hover:bg-slate-700 hover:text-white' ?> transition-all">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    <span class="text-sm font-medium">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="verifikasi.php" class="flex items-center gap-3 px-4 py-3 rounded-lg <?= $current_page === 'verifikasi.php' ? 'sidebar-menu-active text-white' : 'text-slate-400 hover:bg-slate-700 hover:text-white' ?> transition-all">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    <span class="text-sm font-medium">Verifikasi Permohonan</span>
                </a>
            </li>
            <li>
                <a href="riwayat.php" class="flex items-center gap-3 px-4 py-3 rounded-lg <?= $current_page === 'riwayat.php' ? 'sidebar-menu-active text-white' : 'text-slate-400 hover:bg-slate-700 hover:text-white' ?> transition-all">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <span class="text-sm font-medium">Riwayat Tamu</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="px-3 py-4 border-t border-slate-700">
        <a href="../auth/logout.php" class="flex items-center gap-3 px-4 py-3 text-red-400 hover:text-red-300 transition-colors">
            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            <span class="text-sm font-medium">Logout</span>
        </a>
    </div>
</aside>
