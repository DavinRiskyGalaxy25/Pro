<?php
require_once __DIR__ . '/../Database/config.php';
require_once __DIR__ . '/../Database/auth.php';
kantinStartSession();
if (isLoggedIn()) { header('Location: /?q=menu'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (!hash_equals(csrfToken(), $_POST['csrf_token'] ?? '')) {
        $error = 'Request tidak valid.';
    } elseif (isLockedOut()) {
        $error = 'Terlalu banyak percobaan. Tunggu ' . lockoutSecondsLeft() . ' detik.';
    } else {
        $user_input = trim($_POST['username'] ?? '');
        $pass_input = $_POST['password'] ?? '';
        if ($user_input && $pass_input) {
            try {
                $pdo  = getDB();
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username=? OR email=? LIMIT 1");
                $stmt->execute([$user_input, $user_input]);
                $user = $stmt->fetch();
                if ($user && password_verify($pass_input, $user['password'])) {
                    loginUser($user); header('Location: /?q=menu'); exit;
                } else { recordFailedLogin(); $error = 'Username atau password salah.'; }
            } catch (Throwable $e) { error_log('[login] '.$e->getMessage()); $error = 'Kesalahan sistem.'; }
        } else { $error = 'Semua kolom wajib diisi.'; }
    }
}

// Read logo
$logoFile = __DIR__ . '/../public/logo_uam_b64.txt';
$logoSrc  = file_exists($logoFile) ? 'data:image/png;base64,'.trim(file_get_contents($logoFile)) : '';
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Masuk — Kantin UAM</title>
  <?php if($logoSrc): ?><link rel="icon" type="image/png" href="<?=$logoSrc?>"><?php endif; ?>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={theme:{extend:{fontFamily:{sans:['"Public Sans"','Inter','ui-sans-serif']},colors:{orange:{50:'#fff7ed',100:'#ffedd5',400:'#fb923c',500:'#f97316',600:'#ea6c0a',700:'#c2570a'}},boxShadow:{'orange':'0 4px 16px rgba(249,115,22,0.35)'}}}}}</script>
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body{font-family:'Public Sans',sans-serif}
    .bg-sidebar{background:linear-gradient(180deg,#1a1a2e 0%,#16213e 100%)}
    .bg-orange-grad{background:linear-gradient(135deg,#f97316 0%,#ea6c0a 60%,#c2570a 100%)}
  </style>
</head>
<body class="min-h-screen flex font-sans antialiased">

  <!-- Left: Form -->
  <div class="flex-1 flex flex-col justify-center items-center px-8 py-12 bg-white">
    <div class="w-full max-w-sm">

      <!-- Logo + Brand -->
      <div class="flex items-center gap-3 mb-8">
        <?php if($logoSrc): ?>
        <img src="<?=$logoSrc?>" alt="UAM" class="h-12 w-auto object-contain">
        <?php else: ?>
        <div class="w-12 h-12 rounded-2xl bg-orange-grad flex items-center justify-center text-white font-black text-xl">U</div>
        <?php endif; ?>
        <div>
          <p class="font-extrabold text-gray-900 text-lg leading-none">Kantin UAM</p>
          <p class="text-xs text-gray-400 mt-0.5">Universitas Anwar Medika</p>
        </div>
      </div>

      <h2 class="text-2xl font-extrabold text-gray-900 mb-1">Selamat datang</h2>
      <p class="text-sm text-gray-500 mb-7">Masuk untuk mengelola kantin</p>

      <?php if ($error): ?>
      <div class="flex items-start gap-2.5 bg-red-50 border border-red-200 text-red-700
                  rounded-xl px-4 py-3 mb-5 text-sm">
        <i class="fa-solid fa-circle-exclamation mt-0.5 shrink-0"></i>
        <p><?= htmlspecialchars($error) ?></p>
      </div>
      <?php endif; ?>

      <form method="POST" class="space-y-4">
        <?= csrfField() ?>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">
            <i class="fa-solid fa-user text-orange-400 mr-1.5"></i>Username atau Email
          </label>
          <input type="text" name="username" required autocomplete="username"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                 class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm
                        focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent
                        transition placeholder-gray-400"
                 placeholder="Masukkan username atau email">
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">
            <i class="fa-solid fa-lock text-orange-400 mr-1.5"></i>Password
          </label>
          <div class="relative">
            <input type="password" name="password" id="pwdInput" required
                   class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm
                          focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent
                          transition placeholder-gray-400 pr-11"
                   placeholder="••••••••">
            <button type="button" onclick="togglePwd()"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-orange-500 transition-colors text-sm">
              <i class="fa-solid fa-eye" id="eyeIcon"></i>
            </button>
          </div>
        </div>

        <button type="submit" name="login"
                class="w-full py-3 rounded-xl bg-orange-grad text-white font-bold text-sm
                       hover:opacity-90 active:scale-[.98] transition-all shadow-orange
                       focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2">
          <i class="fa-solid fa-right-to-bracket mr-2"></i>Masuk ke Sistem
        </button>
      </form>

      <p class="text-center text-sm text-gray-500 mt-6">
        Belum punya akun?
        <a href="/?q=daftar" class="text-orange-600 font-semibold hover:underline">Daftar sekarang</a>
      </p>

      <div class="mt-8 pt-6 border-t border-gray-100 text-center">
        <p class="text-xs text-gray-400 italic">Humanity Beyond Excellence</p>
      </div>
    </div>
  </div>

  <!-- Right: Visual panel -->
  <div class="hidden lg:flex flex-1 relative bg-sidebar overflow-hidden items-end">
    <div class="absolute inset-0 opacity-5"
         style="background-image:repeating-linear-gradient(45deg,#fff 0,#fff 1px,transparent 0,transparent 50%);background-size:30px 30px;"></div>
    <!-- Orange accent glow -->
    <div class="absolute top-1/3 left-1/2 -translate-x-1/2 w-72 h-72 bg-orange-500/20 rounded-full blur-3xl"></div>

    <?php if ($logoSrc): ?>
    <div class="absolute top-12 left-1/2 -translate-x-1/2">
      <img src="<?=$logoSrc?>" alt="UAM Logo" class="w-40 h-auto object-contain opacity-90">
    </div>
    <?php endif; ?>

    <div class="relative z-10 p-12 text-white">
      <p class="text-orange-400 text-sm font-bold uppercase tracking-widest mb-4">Universitas Anwar Medika</p>
      <h3 class="text-3xl font-extrabold mb-3 leading-tight">
        Sistem Informasi<br>Kantin Kampus
      </h3>
      <p class="text-slate-300 text-sm max-w-xs leading-relaxed">
        Platform terintegrasi untuk manajemen kasir, stok produk, piutang, dan laporan keuangan kantin.
      </p>
      <div class="flex gap-3 mt-8 flex-wrap">
        <span class="bg-orange-500/20 border border-orange-500/30 backdrop-blur-sm px-3 py-1.5 rounded-full text-xs font-semibold text-orange-300">
          <i class="fa-solid fa-cash-register mr-1.5"></i>Kasir Real-time
        </span>
        <span class="bg-white/10 border border-white/20 backdrop-blur-sm px-3 py-1.5 rounded-full text-xs font-semibold">
          <i class="fa-solid fa-chart-line mr-1.5"></i>Laporan Otomatis
        </span>
        <span class="bg-white/10 border border-white/20 backdrop-blur-sm px-3 py-1.5 rounded-full text-xs font-semibold">
          <i class="fa-solid fa-users mr-1.5"></i>Multi-role
        </span>
      </div>
    </div>
  </div>

</body>
<script>
function togglePwd() {
  const i = document.getElementById('pwdInput');
  const e = document.getElementById('eyeIcon');
  if (i.type === 'password') { i.type='text'; e.className='fa-solid fa-eye-slash'; }
  else { i.type='password'; e.className='fa-solid fa-eye'; }
}
</script>
</html>