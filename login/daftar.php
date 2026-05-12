<?php
require_once __DIR__ . '/../Database/config.php';
require_once __DIR__ . '/../Database/auth.php';
kantinStartSession();

if (isLoggedIn()) { header('Location: /?q=menu'); exit; }

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['daftar'])) {
    if (!hash_equals(csrfToken(), $_POST['csrf_token'] ?? '')) {
        $error = 'Request tidak valid.';
    } else {
        $nama     = trim($_POST['namalengkap'] ?? '');
        $username = trim($_POST['username']    ?? '');
        $email    = trim($_POST['email']       ?? '');
        $password = $_POST['password']         ?? '';
        $konfirm  = $_POST['konfirmasi']       ?? '';

        // Validasi
        if (!$nama || !$username || !$email || !$password) {
            $error = 'Semua kolom wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } elseif (strlen($password) < 8) {
            $error = 'Password minimal 8 karakter.';
        } elseif ($password !== $konfirm) {
            $error = 'Konfirmasi password tidak cocok.';
        } else {
            try {
                $pdo = getDB();
                // Cek duplikat
                $cek = $pdo->prepare("SELECT id FROM users WHERE username=? OR email=? LIMIT 1");
                $cek->execute([$username, $email]);
                if ($cek->fetch()) {
                    $error = 'Username atau email sudah terdaftar.';
                } else {
                    // Default role = 2 (kasir), admin dibuat langsung via DB
                    $pdo->prepare("
                        INSERT INTO users (id_role, username, namalengkap, email, password)
                        VALUES (2, ?, ?, ?, ?)
                    ")->execute([
                        $username,
                        htmlspecialchars($nama, ENT_QUOTES, 'UTF-8'),
                        $email,
                        password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
                    ]);
                    $success = 'Akun berhasil dibuat! Silakan login.';
                }
            } catch (Throwable $e) {
                error_log('[daftar] ' . $e->getMessage());
                $error = 'Terjadi kesalahan. Coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar Akun — KantinKu</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={theme:{extend:{colors:{brand:{600:'#16a34a',700:'#15803d',400:'#4ade80'}},fontFamily:{sans:['Plus Jakarta Sans','ui-sans-serif']}}}}</script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50 font-sans antialiased flex items-center justify-center p-4">

<div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
  <div class="bg-gradient-to-br from-gray-900 to-green-900 px-8 py-6 text-white">
    <div class="flex items-center gap-3 mb-2">
      <div class="w-9 h-9 rounded-xl bg-green-600 flex items-center justify-center font-black text-lg">K</div>
      <span class="font-bold">KantinKu</span>
    </div>
    <h2 class="text-xl font-extrabold">Buat Akun Baru</h2>
    <p class="text-green-200 text-sm mt-1">Akun baru akan memiliki role Kasir</p>
  </div>

  <div class="px-8 py-6">
    <?php if ($error): ?>
    <div class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-5 text-sm">
      <span class="mt-0.5 shrink-0">⚠️</span><p><?= htmlspecialchars($error) ?></p>
    </div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="flex items-start gap-3 bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-5 text-sm">
      <span class="mt-0.5 shrink-0">✅</span>
      <p><?= htmlspecialchars($success) ?> <a href="/?q=login" class="font-bold underline">Login sekarang →</a></p>
    </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <?= csrfField() ?>

      <div>
        <label class="block text-xs font-semibold text-gray-700 mb-1">Nama Lengkap *</label>
        <input type="text" name="namalengkap" required value="<?= htmlspecialchars($_POST['namalengkap'] ?? '') ?>"
               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 transition">
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1">Username *</label>
          <input type="text" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                 class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 transition">
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1">Email *</label>
          <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 transition">
        </div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1">Password *</label>
          <input type="password" name="password" required placeholder="Min. 8 karakter"
                 class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 transition">
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1">Konfirmasi *</label>
          <input type="password" name="konfirmasi" required placeholder="Ulangi password"
                 class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 transition">
        </div>
      </div>

      <button type="submit" name="daftar"
              class="w-full py-3 rounded-xl bg-green-600 hover:bg-green-700 text-white font-bold text-sm
                     transition-all shadow-lg shadow-green-600/30 focus:outline-none focus:ring-2 focus:ring-green-400 focus:ring-offset-2">
        Buat Akun
      </button>
    </form>

    <p class="text-center text-sm text-gray-500 mt-5">
      Sudah punya akun? <a href="/?q=login" class="text-green-600 font-semibold hover:underline">Login di sini</a>
    </p>
  </div>
</div>

</body>
</html>