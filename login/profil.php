<?php
// login/profil.php — PERBAIKAN [BUG-13]: File kosong
require_once __DIR__ . '/../Database/auth.php';
requireLogin();
$user = $_SESSION;

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {
    csrfCheck();
    try {
        $pdo  = getDB();
        $nama = htmlspecialchars(trim($_POST['namalengkap'] ?? ''), ENT_QUOTES, 'UTF-8');
        $telp = htmlspecialchars(trim($_POST['telepon']     ?? ''), ENT_QUOTES, 'UTF-8');

        if (!$nama) { $error = 'Nama tidak boleh kosong.'; }
        else {
            $pdo->prepare("UPDATE users SET namalengkap=?, telepon=?, updated_at=NOW() WHERE id=?")
                ->execute([$nama, $telp, $_SESSION['user_id']]);
            $_SESSION['namalengkap'] = $nama;
            $success = 'Profil berhasil diperbarui.';
        }
    } catch (Throwable $e) {
        error_log('[profil] ' . $e->getMessage());
        $error = 'Gagal memperbarui profil.';
    }
}

// Ambil data terbaru dari DB
try {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch() ?: [];
} catch (Throwable $e) {
    $userData = [];
}
?>
<div class="max-w-xl mx-auto space-y-5">
  <div>
    <h1 class="text-xl font-extrabold text-gray-900">Profil Saya</h1>
    <p class="text-sm text-gray-500 mt-0.5">Kelola informasi akun Anda</p>
  </div>

  <?php if ($success): ?>
  <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm flex items-center gap-2">
    ✅ <?= htmlspecialchars($success) ?>
  </div>
  <?php endif; ?>
  <?php if ($error): ?>
  <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm flex items-center gap-2">
    ⚠️ <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <!-- Avatar header -->
    <div class="bg-gradient-to-br from-gray-900 to-green-900 px-6 py-8 flex items-center gap-5">
      <div class="w-16 h-16 rounded-full bg-gradient-to-br from-green-400 to-teal-400
                  flex items-center justify-center text-white text-2xl font-extrabold shadow-xl">
        <?= strtoupper(substr($userData['namalengkap'] ?? '?', 0, 1)) ?>
      </div>
      <div>
        <p class="text-white text-lg font-bold leading-tight"><?= htmlspecialchars($userData['namalengkap'] ?? '—') ?></p>
        <p class="text-green-300 text-sm mt-0.5"><?= htmlspecialchars($userData['email'] ?? '—') ?></p>
        <span class="inline-block mt-2 px-2.5 py-0.5 rounded-full text-[10px] font-bold
                     <?= (int)($userData['id_role'] ?? 0) === 1 ? 'bg-purple-500/30 text-purple-200' : 'bg-blue-500/30 text-blue-200' ?>">
          <?= (int)($userData['id_role'] ?? 0) === 1 ? 'Administrator' : 'Kasir' ?>
        </span>
      </div>
    </div>

    <!-- Form edit -->
    <form method="POST" class="p-6 space-y-4">
      <?= csrfField() ?>
      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Lengkap</label>
        <input type="text" name="namalengkap" required
               value="<?= htmlspecialchars($userData['namalengkap'] ?? '') ?>"
               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 transition">
      </div>
      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Username</label>
        <input type="text" value="<?= htmlspecialchars($userData['username'] ?? '') ?>" disabled
               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-gray-50 text-gray-400 cursor-not-allowed">
        <p class="text-xs text-gray-400 mt-1">Username tidak dapat diubah</p>
      </div>
      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Email</label>
        <input type="text" value="<?= htmlspecialchars($userData['email'] ?? '') ?>" disabled
               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-gray-50 text-gray-400 cursor-not-allowed">
      </div>
      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Nomor Telepon</label>
        <input type="text" name="telepon"
               value="<?= htmlspecialchars($userData['telepon'] ?? '') ?>"
               placeholder="+62 8xx-xxxx-xxxx"
               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 transition">
      </div>
      <div class="pt-2">
        <button type="submit" name="update_profil"
                class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-bold
                       rounded-xl transition-all shadow-sm focus:outline-none focus:ring-2 focus:ring-green-400 focus:ring-offset-2">
          Simpan Perubahan
        </button>
      </div>
    </form>
  </div>

  <!-- Info tambahan -->
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Informasi Akun</p>
    <div class="space-y-2 text-sm">
      <div class="flex justify-between">
        <span class="text-gray-500">Bergabung sejak</span>
        <span class="font-medium text-gray-900">
          <?= isset($userData['created_at']) ? date('d M Y', strtotime($userData['created_at'])) : '—' ?>
        </span>
      </div>
      <div class="flex justify-between">
        <span class="text-gray-500">Role</span>
        <span class="font-medium text-gray-900">
          <?= (int)($userData['id_role'] ?? 0) === 1 ? 'Administrator' : 'Kasir' ?>
        </span>
      </div>
    </div>
  </div>
</div>