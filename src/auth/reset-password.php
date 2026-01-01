<?php
session_start();
require_once '../../config/database.php';

$error = '';
$success = false;
$token = $_GET['token'] ?? '';
$valid_token = false;

if ($token) {
    $sql = "SELECT id_user, nama_lengkap, email, reset_token_expired 
            FROM users 
            WHERE reset_token = $1 AND delete_at IS NULL";
    $result = pg_query_params($db, $sql, [$token]);
    $user = pg_fetch_assoc($result);

    if ($user) {
        $expired_time = strtotime($user['reset_token_expired']);
        if ($expired_time > time()) {
            $valid_token = true;
        } else {
            $error = 'Link reset password sudah kadaluarsa. Silakan request ulang.';
        }
    } else {
        $error = 'Link reset password tidak valid.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($password)) {
        $error = 'Password wajib diisi';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter';
    } elseif ($password !== $confirm_password) {
        $error = 'Konfirmasi password tidak cocok';
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $update_sql = "UPDATE users SET 
                       password = $1,
                       reset_token = NULL,
                       reset_token_expired = NULL,
                       update_at = NOW()
                       WHERE id_user = $2";
        $update_result = pg_query_params($db, $update_sql, [$hashed_password, $user['id_user']]);

        if ($update_result) {
            $success = true;
        } else {
            $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Portal TIK</title>
    <link href="../../assets/css/output.css" rel="stylesheet">
    <link href="../../assets/css/custom.css" rel="stylesheet">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Figtree', system-ui, sans-serif; }</style>
</head>
<body class="bg-topografi min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        
        <!-- Reset Password Card -->
        <div class="bg-white rounded-2xl shadow-lg p-8">
            
            <!-- Logo -->
            <div class="text-center mb-8">
                <img src="../../assets/images/logo_upatik.png" alt="Logo UPA TIK" class="w-20 h-20 object-contain mx-auto mb-4">
                <h1 class="text-xl font-bold text-[#38bdf8]">Reset Password</h1>
                <p class="text-slate-500 text-sm">Masukkan password baru Anda</p>
            </div>

            <?php if ($success): ?>
            <!-- Success Message -->
            <div class="text-center">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-green-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-slate-800 mb-2">Password Berhasil Direset!</h2>
                <p class="text-slate-600 mb-6">Anda sekarang dapat login dengan password baru.</p>
                <a href="login.php" class="inline-block bg-[#38bdf8] hover:bg-[#0ea5e9] text-white font-semibold py-3 px-8 rounded-lg transition-colors">
                    Login Sekarang
                </a>
            </div>

            <?php elseif (!$token || !$valid_token): ?>
            <!-- Invalid Token -->
            <div class="text-center">
                <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-red-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><line x1="15" x2="9" y1="9" y2="15"/><line x1="9" x2="15" y1="9" y2="15"/>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-slate-800 mb-2">Link Tidak Valid</h2>
                <p class="text-slate-600 mb-6"><?= htmlspecialchars($error) ?></p>
                <a href="lupa-password.php" class="inline-block bg-[#38bdf8] hover:bg-[#0ea5e9] text-white font-semibold py-3 px-8 rounded-lg transition-colors">
                    Request Ulang
                </a>
            </div>

            <?php else: ?>
            <!-- Reset Form -->
            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 mb-6 text-red-700 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <div class="bg-sky-50 border border-sky-200 rounded-lg px-4 py-3 mb-6 text-sm">
                <p class="font-medium text-slate-700">Reset password untuk:</p>
                <p class="text-slate-600"><?= htmlspecialchars($user['email']) ?></p>
            </div>

            <form method="POST" class="space-y-5">
                
                <!-- Password Baru -->
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-2">Password Baru</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password"
                        class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 outline-none text-sm bg-slate-50"
                        placeholder="Minimal 6 karakter"
                        required
                        minlength="6"
                    >
                    <p class="text-xs text-slate-500 mt-1">Minimal 6 karakter</p>
                </div>

                <!-- Konfirmasi Password -->
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-slate-700 mb-2">Konfirmasi Password</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password"
                        class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 outline-none text-sm bg-slate-50"
                        placeholder="Ketik ulang password"
                        required
                    >
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit"
                    class="w-full bg-[#38bdf8] hover:bg-[#0ea5e9] text-white font-semibold py-4 rounded-lg transition-colors text-base"
                >
                    Reset Password
                </button>

            </form>

            <!-- Back to Login -->
            <div class="text-center mt-6">
                <a href="login.php" class="text-sm text-[#38bdf8] hover:underline">
                    ← Kembali ke Login
                </a>
            </div>
            <?php endif; ?>

        </div>

    </div>

</body>
</html>
