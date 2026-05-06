<?php
// login/login.php
require_once __DIR__ . '/../Database/config.php';
require_once __DIR__ . '/../Database/auth.php';
kantinStartSession();

if (isLoggedIn()) { header('Location: /?q=menu'); exit; }

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // CSRF check
    if (!hash_equals(csrfToken(), $_POST['csrf_token'] ?? '')) {
        $error = 'Request tidak valid. Coba lagi.';
    } elseif (isLockedOut()) {
        $error = 'Terlalu banyak percobaan login. Coba lagi dalam ' . lockoutSecondsLeft() . ' detik.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($username && $password) {
            try {
                $pdo  = getDB();
                // Cari berdasarkan username ATAU email
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
                $stmt->execute([$username, $username]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    loginUser($user);
                    header('Location: /?q=menu');
                    exit;
                } else {
                    recordFailedLogin();
                    $error = 'Username/email atau password salah.';
                }
            } catch (Throwable $e) {
                error_log("[login] " . $e->getMessage());
                $error = 'Terjadi kesalahan sistem. Coba beberapa saat lagi.';
            }
        } else {
            $error = 'Username dan password wajib diisi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — KantinKu</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: { extend: { colors: { brand: { 600:'#16a34a',700:'#15803d',400:'#4ade80' } },
               fontFamily: { sans: ['Plus Jakarta Sans','ui-sans-serif'] } } }
    }
  </script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="h-full font-sans antialiased">

<div class="min-h-screen flex">

  <!-- Left Panel — Form -->
  <div class="flex-1 flex flex-col justify-center items-center px-8 py-12 bg-white">
    <div class="w-full max-w-sm">

      <!-- Logo -->
      <div class="flex items-center gap-3 mb-8">
        <div class="w-10 h-10 rounded-xl bg-brand-600 flex items-center justify-center text-white font-black text-xl">K</div>
        <div>
          <p class="font-extrabold text-gray-900 text-lg leading-none">KantinKu</p>
          <p class="text-xs text-gray-400">Sistem Manajemen Kantin</p>
        </div>
      </div>

      <h2 class="text-2xl font-extrabold text-gray-900 mb-1">Selamat datang kembali</h2>
      <p class="text-sm text-gray-500 mb-8">Masuk untuk melanjutkan ke sistem kasir</p>

      <?php if ($error): ?>
      <div class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-6 text-sm">
        <span class="mt-0.5">⚠️</span>
        <p><?= htmlspecialchars($error) ?></p>
      </div>
      <?php endif; ?>

      <form method="POST" action="" class="space-y-4">
        <?= csrfField() ?>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Username atau Email</label>
          <input type="text" name="username" required autocomplete="username"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                 class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm
                        focus:outline-none focus:ring-2 focus:ring-brand-400 focus:border-transparent
                        transition placeholder-gray-400"
                 placeholder="Masukkan username atau email">
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Password</label>
          <div class="relative">
            <input type="password" name="password" id="pwdInput" required autocomplete="current-password"
                   class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm
                          focus:outline-none focus:ring-2 focus:ring-brand-400 focus:border-transparent
                          transition placeholder-gray-400 pr-12"
                   placeholder="••••••••">
            <button type="button" onclick="togglePwd()"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-lg">
              👁
            </button>
          </div>
        </div>

        <button type="submit" name="login"
                class="w-full py-3 rounded-xl bg-brand-600 hover:bg-brand-700 active:bg-brand-800
                       text-white font-bold text-sm transition-all duration-150
                       focus:outline-none focus:ring-2 focus:ring-brand-400 focus:ring-offset-2
                       shadow-lg shadow-brand-600/30">
          Masuk ke Sistem
        </button>
      </form>

      <p class="text-center text-sm text-gray-500 mt-6">
        Belum punya akun?
        <a href="/?q=daftar" class="text-brand-600 font-semibold hover:underline">Daftar sekarang</a>
      </p>
    </div>
  </div>

  <!-- Right Panel — Visual -->
  <div class="hidden lg:flex flex-1 relative bg-gray-900 overflow-hidden items-end">
    <!-- Gradient overlay -->
    <div class="absolute inset-0 bg-gradient-to-br from-brand-900/80 via-gray-900/60 to-teal-900/80 z-10"></div>
    <!-- Pattern -->
    <div class="absolute inset-0 opacity-5"
         style="background-image: repeating-linear-gradient(45deg,#fff 0,#fff 1px,transparent 0,transparent 50%);background-size:30px 30px;"></div>

    <div class="relative z-20 p-12 text-white">
      <div class="text-6xl mb-6">🍽️</div>
      <h3 class="text-3xl font-extrabold mb-3 leading-tight">
        Kelola kantin<br>dengan lebih mudah
      </h3>
      <p class="text-gray-300 text-base max-w-xs leading-relaxed">
        Sistem kasir terintegrasi dengan manajemen stok, piutang, shift, dan laporan keuangan real-time.
      </p>
      <div class="flex gap-3 mt-8 flex-wrap">
        <span class="bg-white/10 backdrop-blur-sm border border-white/20 px-3 py-1.5 rounded-full text-sm">✓ POS Real-time</span>
        <span class="bg-white/10 backdrop-blur-sm border border-white/20 px-3 py-1.5 rounded-full text-sm">✓ Laporan Otomatis</span>
        <span class="bg-white/10 backdrop-blur-sm border border-white/20 px-3 py-1.5 rounded-full text-sm">✓ Multi-role</span>
      </div>
    </div>
  </div>

</div>

<script>
function togglePwd() {
  const i = document.getElementById('pwdInput');
  i.type = i.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>