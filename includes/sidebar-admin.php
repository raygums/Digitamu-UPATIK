<?php
/**
 * Sidebar Component untuk Admin
 */
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="fixed left-0 top-0 w-56 h-full bg-[#1e293b] text-white flex flex-col z-50">
    
    <!-- Logo -->
    <div class="px-5 py-4">
        <p class="text-slate-400 text-xs uppercase tracking-wider">Admin Panel</p>
    </div>

    <!-- Navigation -->
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
                <a href="staff.php" class="flex items-center gap-3 px-4 py-3 rounded-lg <?= $current_page === 'staff.php' ? 'sidebar-menu-active text-white' : 'text-slate-400 hover:bg-slate-700 hover:text-white' ?> transition-all">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    <span class="text-sm font-medium">Manajemen Staff</span>
                </a>
            </li>
            <li>
                <a href="laporan.php" class="flex items-center gap-3 px-4 py-3 rounded-lg <?= $current_page === 'laporan.php' ? 'sidebar-menu-active text-white' : 'text-slate-400 hover:bg-slate-700 hover:text-white' ?> transition-all">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/>
                    </svg>
                    <span class="text-sm font-medium">Laporan & Data</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Logout -->
    <div class="px-3 py-4 border-t border-slate-700">
        <a href="../logout.php" class="flex items-center gap-3 px-4 py-3 text-red-400 hover:text-red-300 transition-colors">
            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            <span class="text-sm font-medium">Logout</span>
        </a>
    </div>
</aside>
