<?php
session_start();
require_once '../../config/database.php';

$message = '';
$error = '';
$reset_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Email wajib diisi';
    } else {
        $sql = "SELECT id_user, nama_lengkap, email FROM users WHERE email = $1 AND delete_at IS NULL";
        $result = pg_query_params($db, $sql, [$email]);
        $user = pg_fetch_assoc($result);

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expired_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $update_sql = "UPDATE users SET 
                           reset_token = $1, 
                           reset_token_expired = $2,
                           update_at = NOW()
                           WHERE id_user = $3";
            $update_result = pg_query_params($db, $update_sql, [$token, $expired_at, $user['id_user']]);

            if ($update_result) {
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $base_path = dirname($_SERVER['PHP_SELF']);
                $reset_link = $protocol . '://' . $host . $base_path . '/reset-password.php?token=' . $token;
                
                $message = 'Link reset password berhasil dibuat! Silakan klik link di bawah untuk mereset password Anda.';
            } else {
                $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
            }
        } else {
            $message = 'Jika email terdaftar, link reset password akan ditampilkan.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Portal TIK</title>
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
                <h1 class="text-xl font-bold text-[#38bdf8]">Lupa Password</h1>
                <p class="text-slate-500 text-sm">Masukkan email Anda untuk mereset password</p>
            </div>

            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 mb-6 text-red-700 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <?php if ($message): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg px-4 py-3 mb-6 text-green-700 text-sm">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>

            <?php if ($reset_link): ?>
            <div class="bg-sky-50 border border-sky-200 rounded-lg p-4 mb-6">
                <p class="text-sm font-medium text-slate-700 mb-2">Link Reset Password:</p>
                <div class="bg-white border border-slate-200 rounded p-3 mb-3 break-all text-xs text-slate-600">
                    <?= htmlspecialchars($reset_link) ?>
                </div>
                <div class="flex gap-2">
                    <a href="<?= htmlspecialchars($reset_link) ?>" class="flex-1 bg-[#38bdf8] hover:bg-[#0ea5e9] text-white text-center font-semibold py-3 rounded-lg transition-colors text-sm">
                        Buka Link Reset
                    </a>
                    <button onclick="copyLink()" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-4 rounded-lg transition-colors text-sm">
                        📋 Copy
                    </button>
                </div>
                <p class="text-xs text-slate-500 mt-3">
                    ⏰ Link berlaku selama 1 jam
                </p>
            </div>
            <?php else: ?>
            <form method="POST" class="space-y-5">
                
                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-2">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 outline-none text-sm bg-slate-50"
                        placeholder="Masukkan email Anda"
                        required
                    >
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit"
                    class="w-full bg-[#38bdf8] hover:bg-[#0ea5e9] text-white font-semibold py-4 rounded-lg transition-colors text-base"
                >
                    Kirim Link Reset
                </button>

            </form>
            <?php endif; ?>

            <!-- Back to Login -->
            <div class="text-center mt-6">
                <a href="login.php" class="text-sm text-[#38bdf8] hover:underline">
                    ← Kembali ke Login
                </a>
            </div>

        </div>

    </div>

    <script>
        function copyLink() {
            const link = '<?= $reset_link ?>';
            navigator.clipboard.writeText(link).then(() => {
                alert('✅ Link berhasil di-copy!');
            });
        }
    </script>

</body>
</html>
