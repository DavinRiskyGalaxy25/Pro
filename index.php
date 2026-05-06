<?php
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/Database/config.php';
require_once __DIR__ . '/Database/auth.php';
require_once __DIR__ . '/Database/functions.php';

kantinStartSession();

// ── Routing ──────────────────────────────────
$q = preg_replace('/[^a-z0-9_]/', '', strtolower($_GET['q'] ?? 'menu'));

// Pages that don't require auth
$publicPages = ['login', 'daftar', 'logout'];

if (!in_array($q, $publicPages) && !isLoggedIn()) {
    header('Location: /?q=login');
    exit;
}

// Page map → PHP file
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

// Halaman restricted admin only
$adminOnly = ['stok', 'laporan', 'laporan_penjualan'];
if (in_array($q, $adminOnly) && !isAdmin()) {
    header('Location: /?q=menu');
    exit;
}

$page = $pageMap[$q] ?? 'penjualan/menu.php';
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KantinKu — <?= htmlspecialchars(ucfirst(str_replace('_',' ',$q))) ?></title>
  <!-- Tailwind CDN (production: gunakan build CLI) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: {
              50:  '#f0fdf4', 100: '#dcfce7', 200: '#bbf7d0',
              300: '#86efac', 400: '#4ade80', 500: '#22c55e',
              600: '#16a34a', 700: '#15803d', 800: '#166534',
              900: '#14532d',
            }
          },
          fontFamily: { sans: ['Plus Jakarta Sans','ui-sans-serif','system-ui'] }
        }
      }
    }
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    /* Minimal custom CSS — hanya yang tidak bisa dilakukan Tailwind */
    [x-cloak]    { display: none !important; }
    .font-mono   { font-family: 'JetBrains Mono', monospace; }
    @media print {
      .no-print  { display: none !important; }
      body       { background: white; }
    }
    /* Scrollbar thin */
    ::-webkit-scrollbar       { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }
  </style>
</head>
<body class="bg-gray-50 text-gray-800 min-h-full font-sans antialiased">

<?php if (!in_array($q, ['login','daftar'])): ?>
<?php include __DIR__ . '/layout/navbar.php'; ?>
<?php include __DIR__ . '/layout/sidebar.php'; ?>

<div class="pl-0 lg:pl-64 pt-16 min-h-screen transition-all duration-300" id="mainContent">
  <main class="p-4 md:p-6 max-w-[1600px]">
    <?php include __DIR__ . '/' . $page; ?>
  </main>
</div>

<!-- Toast Container -->
<div id="toastContainer" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 flex flex-col gap-2 pointer-events-none"></div>

<?php else: ?>
  <?php include __DIR__ . '/' . $page; ?>
<?php endif; ?>

<!-- Alpine.js for small interactions -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="/public/js/app.js"></script>
</body>
</html>