<?php
session_start();
require_once '../../config/database.php';

$error = '';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'Admin') {
        header('Location: ../admin/dashboard.php');
    } elseif ($_SESSION['user_role'] === 'Staff') {
        header('Location: ../staff/dashboard.php');
    } else {
        session_destroy();
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi';
    } else {
        $sql = "SELECT id_user, nama_lengkap, email, password, role FROM users WHERE email = $1 AND delete_at IS NULL";
        $result = pg_query_params($db, $sql, [$email]);
        $user = pg_fetch_assoc($result);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['user_nama'] = $user['nama_lengkap'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = ucfirst($user['role']);

            error_log("Login berhasil - ID: {$user['id_user']}, Role: {$user['role']}");
            
            if (strtolower($user['role']) === 'admin') {
                header('Location: ../admin/dashboard.php');
            } else {
                header('Location: ../staff/dashboard.php');
            }
            exit;
        } else {
            $error = 'Email atau password salah';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Portal TIK</title>
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
<body class="min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        
        <!-- Login Card -->
        <div class="bg-white rounded-2xl shadow-lg p-8">
            
            <!-- Logo -->
            <div class="text-center mb-8">
                <img src="../../assets/images/logo_upatik.png" alt="Logo UPA TIK" class="w-20 h-20 object-contain mx-auto mb-4">
                <h1 class="text-xl font-bold text-[#38bdf8]">Portal Login</h1>
                <p class="text-slate-500 text-sm">Silakan login untuk masuk ke dashboard</p>
            </div>

            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 mb-6 text-red-700 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                
                <!-- Akun SSO -->
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-2">Akun SSO</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 outline-none text-sm bg-slate-50"
                        placeholder="Masukkan akun SSO anda"
                    >
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-2">Kata Sandi</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password"
                        class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 outline-none text-sm bg-slate-50"
                        placeholder="Masukkan kata sandi"
                    >
                </div>

                <!-- Remember Me & Lupa Sandi -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 text-sky-500 border-slate-300 rounded focus:ring-sky-400">
                        <span class="text-sm text-slate-600">Ingat Saya</span>
                    </label>
                    <a href="lupa-password.php" class="text-sm text-[#38bdf8] hover:underline">Lupa sandi?</a>
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit"
                    class="w-full bg-[#38bdf8] hover:bg-[#0ea5e9] text-white font-semibold py-4 rounded-lg transition-colors text-base"
                >
                    Masuk
                </button>

            </form>

            <!-- Footer -->
            <p class="text-center text-slate-400 text-xs mt-8">
                &copy; <?= date('Y') ?> UPT TIK Universitas Lampung
            </p>
        </div>

    </div>

</body>
</html>
