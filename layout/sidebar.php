<?php
// layout/sidebar.php — UAM Branding | Orange 30% palette
$q    = preg_replace('/[^a-z0-9_]/', '', strtolower($_GET['q'] ?? 'menu'));
$role = currentRole();

$navItems = [
  ['q'=>'menu',    'label'=>'Kasir',        'icon'=>'fa-solid fa-cash-register', 'roles'=>[1,2]],
  ['q'=>'piutang', 'label'=>'Piutang',      'icon'=>'fa-solid fa-file-invoice-dollar','roles'=>[1,2]],
  ['q'=>'stok',    'label'=>'Stok Produk',  'icon'=>'fa-solid fa-box-open',      'roles'=>[1]],
  ['q'=>'laporan', 'label'=>'Laporan',      'icon'=>'fa-solid fa-chart-bar',     'roles'=>[1]],
  ['q'=>'shift',   'label'=>'Shift',        'icon'=>'fa-solid fa-clock',         'roles'=>[1,2]],
  ['q'=>'profil',  'label'=>'Profil',       'icon'=>'fa-solid fa-circle-user',   'roles'=>[1,2]],
];

$logoSrc = $GLOBALS['logoSrc'] ?? '';
?>

<aside id="sidebar"
  class="bg-sidebar fixed top-0 left-0 h-full w-64 flex flex-col z-40 shadow-2xl
         -translate-x-full lg:translate-x-0">

  <!-- Brand -->
  <div class="flex items-center gap-3 px-5 py-4 border-b border-white/5 shrink-0">
    <?php if ($logoSrc): ?>
    <img src="<?= $logoSrc ?>" alt="Logo UAM"
         class="w-9 h-9 rounded-xl object-contain bg-white/10 p-0.5 shrink-0">
    <?php else: ?>
    <div class="w-9 h-9 rounded-xl bg-orange-grad flex items-center justify-center text-white font-black text-base shrink-0">U</div>
    <?php endif; ?>
    <div class="min-w-0">
      <p class="text-white font-bold text-sm leading-none tracking-tight">Kantin UAM</p>
      <p class="text-orange-400 text-[10px] uppercase tracking-widest mt-0.5 font-semibold">Anwar Medika</p>
    </div>
    <button onclick="closeSidebar()"
            class="ml-auto lg:hidden w-7 h-7 rounded-lg flex items-center justify-center
                   text-slate-400 hover:text-white hover:bg-white/10 transition-colors text-sm">
      <i class="fa-solid fa-xmark"></i>
    </button>
  </div>

  <!-- User card -->
  <div class="mx-3 my-3 px-3 py-3 rounded-xl bg-white/5 flex items-center gap-3 shrink-0">
    <div class="w-9 h-9 rounded-full bg-orange-grad flex items-center justify-center
                text-white font-bold text-sm shrink-0 shadow-orange">
      <?= strtoupper(substr($_SESSION['namalengkap'] ?? '?', 0, 1)) ?>
    </div>
    <div class="min-w-0 flex-1">
      <p class="text-white text-sm font-semibold truncate leading-tight">
        <?= htmlspecialchars($_SESSION['namalengkap'] ?? '—') ?>
      </p>
      <p class="text-slate-400 text-xs truncate mt-0.5">
        <?= $role === 1 ? 'Administrator' : 'Kasir' ?>
      </p>
    </div>
    <span class="w-2 h-2 rounded-full bg-emerald-400 shrink-0 shadow-[0_0_6px_2px_rgba(52,211,153,.5)]"></span>
  </div>

  <!-- Nav -->
  <nav class="flex-1 overflow-y-auto px-3 pb-3 space-y-0.5">
    <p class="text-[10px] text-slate-600 font-bold uppercase tracking-widest px-2 pt-3 pb-2 select-none">
      Navigasi
    </p>
    <?php foreach ($navItems as $item):
      if (!in_array($role, $item['roles'])) continue;
      $active = $q === $item['q'];
    ?>
    <a href="/?q=<?= $item['q'] ?>"
       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
              <?= $active
                ? 'bg-orange-500/20 text-orange-400 font-semibold'
                : 'text-slate-400 hover:bg-white/5 hover:text-white' ?>
              relative group">
      <?php if ($active): ?>
      <span class="absolute left-0 top-1/4 bottom-1/4 w-0.5 rounded-r-full bg-orange-500"></span>
      <?php endif; ?>
      <i class="<?= $item['icon'] ?> w-4 text-center shrink-0 text-base
                <?= $active ? 'text-orange-400' : 'text-slate-500 group-hover:text-slate-300' ?>"></i>
      <span><?= $item['label'] ?></span>
      <?php if ($active): ?>
      <span class="ml-auto w-1.5 h-1.5 rounded-full bg-orange-500"></span>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
  </nav>

  <!-- Logout + version -->
  <div class="px-3 pb-4 border-t border-white/5 pt-3 shrink-0">
    <a href="/?q=logout"
       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
              text-slate-400 hover:bg-red-500/15 hover:text-red-400 transition-all duration-150">
      <i class="fa-solid fa-right-from-bracket w-4 text-center shrink-0"></i>
      <span>Keluar</span>
    </a>
    <p class="text-center text-[10px] text-slate-700 mt-3 select-none font-mono">
      Kantin UAM v3 · 2025
    </p>
  </div>

</aside>

<!-- Mobile overlay -->
<div id="sidebarOverlay" onclick="closeSidebar()"
     class="fixed inset-0 bg-black/60 backdrop-blur-sm z-30 hidden lg:hidden"></div>