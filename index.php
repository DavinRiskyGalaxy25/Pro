<?php
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/Database/config.php';
require_once __DIR__ . '/Database/auth.php';
require_once __DIR__ . '/Database/functions.php';
kantinStartSession();
$q = preg_replace('/[^a-z0-9_]/', '', strtolower($_GET['q'] ?? 'menu'));
$publicPages = ['login', 'daftar', 'logout'];
if (!in_array($q, $publicPages) && !isLoggedIn()) { header('Location: /?q=login'); exit; }
$pageMap = [
    'menu'=>'penjualan/menu.php','pembayaran'=>'penjualan/pembayaran.php',
    'laporan'=>'laporan/index.php','laporan_penjualan'=>'laporan/penjualan.php',
    'stok'=>'stok_barang/index.php','piutang'=>'piutang/index.php',
    'shift'=>'shift/index.php','login'=>'login/login.php',
    'daftar'=>'login/daftar.php','logout'=>'login/logout.php','profil'=>'login/profil.php',
];
$adminOnly = ['stok','laporan','laporan_penjualan'];
if (in_array($q, $adminOnly) && !isAdmin()) { header('Location: /?q=menu'); exit; }
$page = $pageMap[$q] ?? 'penjualan/menu.php';
$fullPath = realpath(__DIR__ . '/' . $page);
if (!$fullPath || !str_starts_with($fullPath, realpath(__DIR__))) { http_response_code(400); exit('Invalid page'); }
$logoFile = __DIR__ . '/public/logo_uam_b64.txt';
$logoSrc  = file_exists($logoFile) ? 'data:image/png;base64,' . trim(file_get_contents($logoFile)) : '';
$GLOBALS['logoSrc'] = $logoSrc;
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kantin UAM — <?= htmlspecialchars(ucfirst(str_replace('_',' ',$q))) ?></title>
  <?php if ($logoSrc): ?><link rel="icon" type="image/png" href="<?= $logoSrc ?>"><?php endif; ?>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
  tailwind.config = {
    theme: { extend: {
      colors: {
        orange: { 50:'#fff7ed',100:'#ffedd5',200:'#fed7aa',300:'#fdba74',400:'#fb923c',500:'#f97316',600:'#ea6c0a',700:'#c2570a',800:'#9a3f06',900:'#7c3205' },
      },
      fontFamily: { sans:['"Public Sans"','Inter','ui-sans-serif'], mono:['"JetBrains Mono"','ui-monospace'] },
      boxShadow: { 'orange':'0 4px 16px rgba(249,115,22,0.35)', 'orange-lg':'0 8px 32px rgba(249,115,22,0.25)' }
    }}
  }
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    [x-cloak]{display:none!important}
    #sidebar{transition:transform .28s cubic-bezier(.4,0,.2,1)}
    #mainContent{transition:padding-left .28s cubic-bezier(.4,0,.2,1)}
    .bg-sidebar{background:linear-gradient(180deg,#1a1a2e 0%,#16213e 100%)}
    .bg-orange-grad{background:linear-gradient(135deg,#f97316 0%,#ea6c0a 60%,#c2570a 100%)}
    ::-webkit-scrollbar{width:5px;height:5px}
    ::-webkit-scrollbar-thumb{background:#d1d5db;border-radius:10px}
    @media print{.no-print{display:none!important}}
    @keyframes slideUp{from{opacity:0;transform:translateY(12px) scale(.95)}to{opacity:1;transform:translateY(0) scale(1)}}
    .toast-in{animation:slideUp .3s cubic-bezier(.34,1.56,.64,1) forwards}
    .font-mono{font-family:'JetBrains Mono',ui-monospace}
  </style>
</head>
<body class="bg-gray-50 text-gray-800 min-h-full font-sans antialiased">
<?php if (!in_array($q, ['login','daftar'])): ?>
<?php include __DIR__ . '/layout/navbar.php'; ?>
<?php include __DIR__ . '/layout/sidebar.php'; ?>
<div class="lg:pl-64 pt-16 min-h-screen" id="mainContent">
  <main class="p-4 md:p-6 max-w-screen-xl mx-auto">
    <?php include $fullPath; ?>
  </main>
</div>
<div id="toastContainer" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[100] flex flex-col-reverse gap-2 items-center pointer-events-none"></div>
<?php else: ?>
<?php include $fullPath; ?>
<?php endif; ?>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="/public/js/app.js"></script>
</body>
</html>