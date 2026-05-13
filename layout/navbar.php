<?php
// layout/navbar.php — UAM Orange palette
$q = preg_replace('/[^a-z0-9_]/', '', strtolower($_GET['q'] ?? 'menu'));
$titles = [
  'menu'=>'Kasir','pembayaran'=>'Pembayaran',
  'laporan'=>'Laporan','laporan_penjualan'=>'Laporan Penjualan',
  'stok'=>'Stok Produk','piutang'=>'Data Piutang',
  'shift'=>'Shift Kasir','profil'=>'Profil Saya',
];
$title = $titles[$q] ?? ucfirst(str_replace('_',' ',$q));
$logoSrc = $GLOBALS['logoSrc'] ?? '';
?>
<header class="fixed top-0 left-0 right-0 h-16 bg-white border-b border-gray-200 z-50
               flex items-center px-4 gap-3 shadow-sm">

  <!-- Hamburger mobile -->
  <button onclick="toggleSidebar()"
          class="p-2 rounded-lg hover:bg-orange-50 text-gray-500 hover:text-orange-500
                 transition-colors lg:hidden shrink-0" aria-label="Menu">
    <i class="fa-solid fa-bars text-base"></i>
  </button>

  <!-- Desktop sidebar toggle -->
  <button onclick="toggleSidebarDesktop()"
          class="p-2 rounded-lg hover:bg-orange-50 text-gray-500 hover:text-orange-500
                 transition-colors hidden lg:flex shrink-0" aria-label="Toggle sidebar">
    <i class="fa-solid fa-bars text-base"></i>
  </button>

  <!-- Logo + Title -->
  <div class="flex items-center gap-2.5 min-w-0 flex-1">
    <?php if ($logoSrc): ?>
    <img src="<?= $logoSrc ?>" alt="UAM" class="h-8 w-auto object-contain shrink-0 hidden sm:block">
    <?php endif; ?>
    <div class="min-w-0">
      <h1 class="text-sm font-bold text-gray-900 leading-tight truncate"><?= $title ?></h1>
      <p class="text-xs text-gray-400 leading-tight mt-0.5 hidden sm:block" id="topbarDate"></p>
    </div>
  </div>

  <!-- Right actions -->
  <div class="flex items-center gap-2 shrink-0">

    <!-- Cart btn (POS only) -->
    <?php if ($q === 'menu'): ?>
    <button onclick="prosesCheckout()"
            class="relative p-2 rounded-lg hover:bg-orange-50 text-gray-500 hover:text-orange-500 transition-colors"
            title="Keranjang">
      <i class="fa-solid fa-cart-shopping text-base"></i>
      <span id="cartBadge"
            class="absolute -top-1 -right-1 bg-orange-500 text-white text-[10px] font-bold
                   rounded-full w-4 h-4 flex items-center justify-center hidden">0</span>
    </button>
    <?php endif; ?>

    <!-- Stok kritis (Admin) -->
    <?php if (isAdmin()):
      try { $pdo=getDB(); $s=$pdo->query("SELECT COUNT(*) FROM stok_barang WHERE stok<=5 AND aktif=1"); $kritis=(int)$s->fetchColumn(); }
      catch(Throwable $e){ $kritis=0; }
    ?>
    <button onclick="window.location='/?q=stok'"
            class="relative p-2 rounded-lg hover:bg-orange-50 text-gray-500 hover:text-orange-500 transition-colors"
            title="Notifikasi stok">
      <i class="fa-solid fa-bell text-base"></i>
      <?php if ($kritis > 0): ?>
      <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold
                   rounded-full w-4 h-4 flex items-center justify-center"><?= $kritis ?></span>
      <?php endif; ?>
    </button>
    <?php endif; ?>

    <!-- Avatar dropdown -->
    <div class="relative" x-data="{ open: false }">
      <button @click="open = !open"
              class="w-9 h-9 rounded-full bg-orange-grad flex items-center justify-center
                     text-white font-bold text-sm hover:ring-2 hover:ring-orange-400
                     hover:ring-offset-2 transition-all shadow-orange shrink-0">
        <?= strtoupper(substr($_SESSION['namalengkap'] ?? '?', 0, 1)) ?>
      </button>

      <div x-show="open" @click.away="open=false" x-cloak
           x-transition:enter="transition ease-out duration-100"
           x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
           x-transition:enter-end="opacity-100 scale-100 translate-y-0"
           x-transition:leave="transition ease-in duration-75"
           x-transition:leave-start="opacity-100 scale-100"
           x-transition:leave-end="opacity-0 scale-95"
           class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden z-50">

        <div class="px-4 py-3 bg-orange-50 border-b border-orange-100">
          <p class="text-sm font-bold text-gray-900 truncate"><?= htmlspecialchars($_SESSION['namalengkap'] ?? '—') ?></p>
          <p class="text-xs text-gray-400 truncate mt-0.5"><?= htmlspecialchars($_SESSION['email'] ?? '—') ?></p>
          <span class="inline-block mt-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold
                       <?= isAdmin() ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700' ?>">
            <?= isAdmin() ? 'Administrator' : 'Kasir' ?>
          </span>
        </div>

        <div class="py-1">
          <a href="/?q=profil" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-600 transition-colors">
            <i class="fa-solid fa-circle-user w-4 text-center"></i> Profil Saya
          </a>
          <a href="/?q=menu" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-600 transition-colors">
            <i class="fa-solid fa-cash-register w-4 text-center"></i> Kasir
          </a>
          <?php if (isAdmin()): ?>
          <a href="/?q=stok" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-600 transition-colors">
            <i class="fa-solid fa-box-open w-4 text-center"></i> Stok Produk
          </a>
          <a href="/?q=laporan" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-600 transition-colors">
            <i class="fa-solid fa-chart-bar w-4 text-center"></i> Laporan
          </a>
          <?php endif; ?>
        </div>

        <div class="border-t border-gray-100">
          <a href="/?q=logout"
             class="flex items-center gap-2.5 px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors">
            <i class="fa-solid fa-right-from-bracket w-4 text-center"></i> Keluar dari Sistem
          </a>
        </div>
      </div>
    </div>

  </div>
</header>