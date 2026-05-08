<?php
$q    = preg_replace('/[^a-z0-9_]/', '', strtolower($_GET['q'] ?? 'menu'));
$role = currentRole(); 
$navItems = [
    [
        'q'      => 'menu',
        'label'  => 'Kasir / POS',
        'icon'   => '🛒',
        'roles'  => [1, 2],
        'desc'   => 'Transaksi penjualan',
    ],
    [
        'q'      => 'piutang',
        'label'  => 'Data Piutang',
        'icon'   => '📋',
        'roles'  => [1, 2],
        'desc'   => 'Kelola hutang pelanggan',
    ],
    [
        'q'      => 'stok',
        'label'  => 'Stok Barang',
        'icon'   => '📦',
        'roles'  => [1],
        'desc'   => 'Manajemen inventory',
    ],
    [
        'q'      => 'laporan',
        'label'  => 'Laporan',
        'icon'   => '📊',
        'roles'  => [1],
        'desc'   => 'Laporan keuangan',
    ],
    [
        'q'      => 'shift',
        'label'  => 'Shift Kasir',
        'icon'   => '🕐',
        'roles'  => [1, 2],
        'desc'   => 'Manajemen shift',
    ],
    [
        'q'      => 'profil',
        'label'  => 'Profil Saya',
        'icon'   => '👤',
        'roles'  => [1, 2],
        'desc'   => 'Pengaturan akun',
    ],
];
?>

<aside
    id="sidebar"
    class="fixed top-0 left-0 h-full w-64 bg-gray-900 text-gray-300
           flex flex-col z-40 shadow-2xl
           -translate-x-full lg:translate-x-0
           transition-transform duration-300 ease-in-out"
>

    <div class="flex items-center gap-3 px-5 py-5 border-b border-gray-800 shrink-0">
        <div class="w-9 h-9 rounded-xl bg-green-600 flex items-center justify-center
                    text-white font-black text-lg shrink-0 shadow-lg shadow-green-900/50">
            K
        </div>
        <div class="min-w-0">
            <p class="text-white font-bold text-sm leading-none tracking-tight">KantinKu</p>
            <p class="text-[10px] text-green-400 uppercase tracking-widest mt-0.5 font-medium">
                POS System v3
            </p>
        </div>
        <button
            onclick="closeSidebar()"
            class="ml-auto lg:hidden w-7 h-7 rounded-lg flex items-center justify-center
                   text-gray-400 hover:text-white hover:bg-gray-800 transition-colors text-sm"
            aria-label="Tutup sidebar"
        >
            ✕
        </button>
    </div>

    <div class="mx-3 my-3 px-3 py-3 rounded-xl bg-gray-800/60 flex items-center gap-3 shrink-0">
        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-green-500 to-teal-400
                    flex items-center justify-center text-white font-bold text-sm shrink-0">
            <?= strtoupper(substr($_SESSION['namalengkap'] ?? '?', 0, 1)) ?>
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-white text-sm font-semibold truncate leading-tight">
                <?= htmlspecialchars($_SESSION['namalengkap'] ?? '—') ?>
            </p>
            <p class="text-gray-400 text-xs truncate mt-0.5">
                <?php if (isset($_SESSION['role'])): ?>
                    <?= (int)$_SESSION['role'] === 1 ? 'Administrator' : 'Kasir' ?>
                <?php else: ?>
                    —
                <?php endif; ?>
            </p>
        </div>
        <span class="w-2 h-2 rounded-full bg-green-400 shrink-0
                     shadow-[0_0_6px_2px_rgba(74,222,128,0.5)]"
              title="Online">
        </span>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 pb-3"
         style="scrollbar-width: thin; scrollbar-color: #374151 transparent;">

        <p class="text-[10px] text-gray-600 font-bold uppercase tracking-widest
                  px-2 pt-4 pb-2 select-none">
            Menu Utama
        </p>

        <?php foreach ($navItems as $item):
            if (!in_array($role, $item['roles'])) continue;

            $isActive = ($q === $item['q']);
        ?>
        <a
            href="/?q=<?= htmlspecialchars($item['q']) ?>"
            title="<?= htmlspecialchars($item['desc']) ?>"
            class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
                   mb-0.5 transition-all duration-150 relative
                   <?= $isActive
                       ? 'bg-green-600/20 text-green-400 font-semibold'
                       : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?>"
        >
            <span class="text-base w-5 text-center shrink-0 leading-none">
                <?= $item['icon'] ?>
            </span>

            <span class="truncate"><?= htmlspecialchars($item['label']) ?></span>

            <?php if ($isActive): ?>
            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-green-400 shrink-0"></span>
            <?php endif; ?>

            <?php if ($isActive): ?>
            <span class="absolute left-0 top-1/4 bottom-1/4 w-0.5 rounded-full bg-green-400"></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>

    </nav>

    <div class="px-3 pb-4 border-t border-gray-800 pt-3 shrink-0">
        <a
            href="/?q=logout"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
                   text-gray-400 hover:bg-red-900/30 hover:text-red-400
                   transition-all duration-150 group"
        >
            <span class="text-base w-5 text-center shrink-0">🚪</span>
            <span>Keluar</span>
        </a>
        <p class="text-center text-[10px] text-gray-700 mt-3 select-none font-mono">
            KantinKu v3.0.0 · 2025
        </p>
    </div>

</aside>

<div
    id="sidebarOverlay"
    onclick="closeSidebar()"
    class="fixed inset-0 bg-black/50 backdrop-blur-sm z-30 hidden lg:hidden"
    aria-hidden="true"
></div>