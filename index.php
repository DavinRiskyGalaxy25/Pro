<?php
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/Database/config.php';
require_once __DIR__ . '/Database/auth.php';
require_once __DIR__ . '/Database/functions.php';

// [FIX-01] Session HARUS distart sebelum $q dipakai oleh isLoggedIn()
kantinStartSession();

// [FIX-02] $q HARUS didefinisikan sebelum dipakai di mana pun
$q = preg_replace('/[^a-z0-9_]/', '', strtolower($_GET['q'] ?? 'menu'));

$publicPages = ['login', 'daftar', 'logout'];

// [FIX-03] Auth check setelah $q tersedia
if (!in_array($q, $publicPages) && !isLoggedIn()) {
    header('Location: /?q=login');
    exit;
}

$pageMap = [
    'menu'              => 'penjualan/menu.php',
    'pembayaran'        => 'penjualan/pembayaran.php',
    'laporan'           => 'laporan/index.php',
    'laporan_penjualan' => 'laporan/penjualan.php',
    'stok'              => 'stok_barang/index.php',
    'piutang'           => 'piutang/index.php',
    'shift'             => 'shift/index.php',
    'login'             => 'login/login.php',
    'daftar'            => 'login/daftar.php',
    'logout'            => 'login/logout.php',
    'profil'            => 'login/profil.php',
];

$adminOnly = ['stok', 'laporan', 'laporan_penjualan'];
if (in_array($q, $adminOnly) && !isAdmin()) {
    header('Location: /?q=menu');
    exit;
}

$page = $pageMap[$q] ?? 'penjualan/menu.php';

$fullPath = realpath(__DIR__ . '/' . $page);
if (!$fullPath || !str_starts_with($fullPath, realpath(__DIR__))) {
    http_response_code(400);
    exit('Invalid page');
}
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KantinKu — <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $q))) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: {
              50:'#f0fdf4', 100:'#dcfce7', 200:'#bbf7d0',
              300:'#86efac', 400:'#4ade80', 500:'#22c55e',
              600:'#16a34a', 700:'#15803d', 800:'#166534', 900:'#14532d',
            }
          },
          fontFamily: { sans: ['Plus Jakarta Sans', 'ui-sans-serif', 'system-ui'] }
        }
      }
    }
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    [x-cloak]  { display: none !important; }
    .font-mono { font-family: 'JetBrains Mono', monospace; }
    @media print {
      .no-print { display: none !important; }
      body      { background: white; }
    }
    ::-webkit-scrollbar       { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }
  </style>
</head>
<body class="bg-gray-50 text-gray-800 min-h-full font-sans antialiased">

<?php if (!in_array($q, ['login', 'daftar'])): ?>
  <?php include __DIR__ . '/layout/navbar.php'; ?>
  <?php include __DIR__ . '/layout/sidebar.php'; ?>

  <div class="pl-0 lg:pl-64 pt-16 min-h-screen transition-all duration-300" id="mainContent">
    <main class="p-4 md:p-6 max-w-[1600px]">
      <?php include $fullPath; ?>
    </main>
  </div>

  <div id="toastContainer"
       class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 flex flex-col gap-2 pointer-events-none">
  </div>

<?php else: ?>
  <?php include $fullPath; ?>
<?php endif; ?>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="/public/js/app.js"></script>
</body>
</html>
