<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header('Location: ../auth/login.php');
    exit;
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $nama = pg_escape_string($db, $_POST['nama'] ?? '');
        $username = pg_escape_string($db, $_POST['username'] ?? '');
        $email = pg_escape_string($db, $_POST['email'] ?? '');
        $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
        $role = $_POST['role'] === 'Admin' ? 'Admin' : 'Staff';
        
        $check = fetchOne(query("SELECT id_user FROM users WHERE email = '$email' OR username = '$username'"));
        if ($check) {
            $message = 'Email atau username sudah terdaftar!';
            $message_type = 'error';
        } else {
            $sql = "INSERT INTO users (nama_lengkap, username, email, password, role, create_at) VALUES ('$nama', '$username', '$email', '$password', '$role', NOW())";
            if (pg_query($db, $sql)) {
                $message = 'Staff berhasil ditambahkan!';
                $message_type = 'success';
            } else {
                $message = 'Gagal menambahkan staff!';
                $message_type = 'error';
            }
        }
    } elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = pg_escape_string($db, $_POST['nama'] ?? '');
        $username = pg_escape_string($db, $_POST['username'] ?? '');
        $email = pg_escape_string($db, $_POST['email'] ?? '');
        $role = $_POST['role'] === 'Admin' ? 'Admin' : 'Staff';
        
        $check = fetchOne(query("SELECT id_user FROM users WHERE (email = '$email' OR username = '$username') AND id_user != $id"));
        if ($check) {
            $message = 'Email atau username sudah digunakan!';
            $message_type = 'error';
        } else {
            $sql = "UPDATE users SET nama_lengkap = '$nama', username = '$username', email = '$email', role = '$role', update_at = NOW() WHERE id_user = $id";
            
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $sql = "UPDATE users SET nama_lengkap = '$nama', username = '$username', email = '$email', role = '$role', password = '$password', update_at = NOW() WHERE id_user = $id";
            }
            
            if (pg_query($db, $sql)) {
                $message = 'Staff berhasil diupdate!';
                $message_type = 'success';
            } else {
                $message = 'Gagal mengupdate staff!';
                $message_type = 'error';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id === $_SESSION['user_id']) {
            $message = 'Tidak dapat menghapus akun sendiri!';
            $message_type = 'error';
        } else {
            $sql = "UPDATE users SET delete_at = NOW() WHERE id_user = $id";
            if (pg_query($db, $sql)) {
                $message = 'Staff berhasil dihapus!';
                $message_type = 'success';
            } else {
                $message = 'Gagal menghapus staff!';
                $message_type = 'error';
            }
        }
    }
}

$users = fetchAll(query("SELECT * FROM users WHERE delete_at IS NULL ORDER BY role ASC, nama_lengkap ASC"));

$total_admin = 0;
$total_staff = 0;
foreach ($users as $u) {
    if ($u['role'] === 'Admin') $total_admin++;
    else $total_staff++;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management Staff - Portal TIK</title>
    <link href="../../assets/css/output.css" rel="stylesheet">
    <link href="../../assets/css/custom.css" rel="stylesheet">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Figtree', system-ui, sans-serif; }</style>
</head>
<body class="bg-slate-100 min-h-screen">

    <?php include '../../includes/sidebar-admin.php'; ?>

    <!-- Main Content -->
    <main class="ml-56 min-h-screen">
        
        <!-- Header -->
        <header class="bg-white border-b border-slate-200 px-8 py-4 flex items-center justify-between sticky top-0 z-40">
            <div>
                <h1 class="text-xl font-bold text-slate-800">Management Staff</h1>
                <p class="text-sm text-slate-500">Kelola akun staff dan administrator.</p>
            </div>
            <button onclick="openAddModal()" class="bg-[#0ea5e9] hover:bg-[#0284c7] text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Tambah Staff
            </button>
        </header>

        <div class="p-8">
            
            <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?= $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="stats-card">
                    <p class="text-sm text-slate-500 mb-1">Total Pengguna</p>
                    <p class="text-3xl font-bold text-slate-800"><?= count($users) ?></p>
                </div>
                <div class="stats-card">
                    <p class="text-sm text-slate-500 mb-1">Administrator</p>
                    <p class="text-3xl font-bold text-[#0ea5e9]"><?= $total_admin ?></p>
                </div>
                <div class="stats-card">
                    <p class="text-sm text-slate-500 mb-1">Staff</p>
                    <p class="text-3xl font-bold text-[#10b981]"><?= $total_staff ?></p>
                </div>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase">Nama</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase">Email</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase">Role</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase">Terdaftar</th>
                                <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($users as $u): ?>
                            <tr class="table-row-hover">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full <?= $u['role'] === 'Admin' ? 'bg-[#0ea5e9]' : 'bg-slate-200' ?> flex items-center justify-center">
                                            <span class="text-sm font-semibold <?= $u['role'] === 'Admin' ? 'text-white' : 'text-slate-600' ?>"><?= strtoupper(substr($u['nama_lengkap'], 0, 2)) ?></span>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-slate-800"><?= htmlspecialchars($u['nama_lengkap']) ?></p>
                                            <p class="text-xs text-slate-500">@<?= htmlspecialchars($u['username']) ?></p>
                                            <?php if ($u['id_user'] === $_SESSION['user_id']): ?>
                                            <span class="text-xs text-[#0ea5e9]">(Anda)</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600"><?= htmlspecialchars($u['email']) ?></td>
                                <td class="px-6 py-4">
                                    <?php if ($u['role'] === 'Admin'): ?>
                                        <span class="px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">Admin</span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Staff</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600"><?= $u['create_at'] ? date('d M Y', strtotime($u['create_at'])) : '-' ?></td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2">
                                        <button onclick="openEditModal(<?= htmlspecialchars(json_encode($u)) ?>)" class="bg-amber-100 hover:bg-amber-200 text-amber-800 text-xs font-medium px-3 py-1.5 rounded transition-colors">
                                            Edit
                                        </button>
                                        <?php if ($u['id_user'] !== $_SESSION['user_id']): ?>
                                        <form method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus staff ini?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $u['id_user'] ?>">
                                            <button type="submit" class="bg-red-100 hover:bg-red-200 text-red-800 text-xs font-medium px-3 py-1.5 rounded transition-colors">
                                                Hapus
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <!-- Modal Tambah -->
    <div id="addModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl w-full max-w-md mx-4 p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Tambah Staff Baru</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input type="text" name="nama" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0ea5e9] focus:border-[#0ea5e9]">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Username <span class="text-red-500">*</span></label>
                    <input type="text" name="username" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0ea5e9] focus:border-[#0ea5e9]">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0ea5e9] focus:border-[#0ea5e9]">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Password <span class="text-red-500">*</span></label>
                    <input type="password" name="password" required minlength="6" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0ea5e9] focus:border-[#0ea5e9]">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Role <span class="text-red-500">*</span></label>
                    <select name="role" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0ea5e9] focus:border-[#0ea5e9]">
                        <option value="Staff">Staff</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
                
                <div class="flex gap-3">
                    <button type="button" onclick="closeAddModal()" class="flex-1 px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-[#0ea5e9] hover:bg-[#0284c7] text-white rounded-lg font-medium transition-colors">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit -->
    <div id="editModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl w-full max-w-md mx-4 p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Edit Staff</h3>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editId">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input type="text" name="nama" id="editNama" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0ea5e9] focus:border-[#0ea5e9]">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Username <span class="text-red-500">*</span></label>
                    <input type="text" name="username" id="editUsername" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0ea5e9] focus:border-[#0ea5e9]">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" id="editEmail" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0ea5e9] focus:border-[#0ea5e9]">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Password Baru</label>
                    <input type="password" name="password" minlength="6" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0ea5e9] focus:border-[#0ea5e9]" placeholder="Kosongkan jika tidak diubah">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Role <span class="text-red-500">*</span></label>
                    <select name="role" id="editRole" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0ea5e9] focus:border-[#0ea5e9]">
                        <option value="Staff">Staff</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
                
                <div class="flex gap-3">
                    <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-[#0ea5e9] hover:bg-[#0284c7] text-white rounded-lg font-medium transition-colors">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openAddModal() {
        document.getElementById('addModal').classList.remove('hidden');
        document.getElementById('addModal').classList.add('flex');
    }

    function closeAddModal() {
        document.getElementById('addModal').classList.add('hidden');
        document.getElementById('addModal').classList.remove('flex');
    }

    function openEditModal(user) {
        document.getElementById('editId').value = user.id_user;
        document.getElementById('editNama').value = user.nama_lengkap;
        document.getElementById('editUsername').value = user.username;
        document.getElementById('editEmail').value = user.email;
        document.getElementById('editRole').value = user.role;
        
        document.getElementById('editModal').classList.remove('hidden');
        document.getElementById('editModal').classList.add('flex');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        document.getElementById('editModal').classList.remove('flex');
    }
    </script>

</body>
</html>
