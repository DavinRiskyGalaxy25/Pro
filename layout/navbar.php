<?php
// =============================================
// KantinKu v3 — Top Navbar
// layout/navbar.php
// =============================================

$q = preg_replace('/[^a-z0-9_]/', '', strtolower($_GET['q'] ?? 'menu'));

// Judul halaman berdasarkan parameter ?q=
$pageTitles = [
    'menu'              => 'Kasir / POS',
    'pembayaran'        => 'Pembayaran',
    'laporan'           => 'Laporan',
    'laporan_penjualan' => 'Laporan Penjualan',
    'laporan_pembelian' => 'Laporan Pembelian',
    'stok'              => 'Stok Barang',
    'piutang'           => 'Data Piutang',
    'shift'             => 'Shift Kasir',
    'profil'            => 'Profil Saya',
];

$pageTitle = $pageTitles[$q] ?? ucfirst(str_replace('_', ' ', $q));
?>

<!-- =============================================
     TOP NAVBAR
     - Fixed di atas, z-index 50
     - Tinggi: h-16 (64px)
     - Konten utama diberi padding-top: pt-16
     ============================================= -->
<header
    class="fixed top-0 left-0 right-0 h-16 bg-white border-b border-gray-200
           z-50 flex items-center px-4 gap-3 shadow-sm"
>

    <!-- ── Kiri: Hamburger + Judul Halaman ── -->
    <div class="flex items-center gap-3 flex-1 min-w-0">

        <!-- Hamburger Mobile (< lg) -->
        <button
            onclick="toggleSidebar()"
            class="p-2 rounded-lg hover:bg-gray-100 text-gray-500 hover:text-gray-900
                   transition-colors lg:hidden shrink-0"
            aria-label="Buka menu"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <!-- Toggle Sidebar Desktop (≥ lg) -->
        <button
            onclick="toggleSidebarDesktop()"
            class="p-2 rounded-lg hover:bg-gray-100 text-gray-500 hover:text-gray-900
                   transition-colors hidden lg:flex shrink-0"
            aria-label="Toggle sidebar"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <!-- Judul + Tanggal -->
        <div class="min-w-0">
            <h1 class="text-sm font-bold text-gray-900 leading-tight truncate">
                <?= htmlspecialchars($pageTitle) ?>
            </h1>
            <p class="text-xs text-gray-400 leading-tight mt-0.5" id="topbarDate">
                <!-- Diisi oleh JavaScript -->
            </p>
        </div>
    </div>

    <!-- ── Kanan: Aksi & Avatar ── -->
    <div class="flex items-center gap-2 shrink-0">

        <!-- Tombol Cart — hanya tampil di halaman POS -->
        <?php if ($q === 'menu'): ?>
        <button
            onclick="prosesCheckout()"
            class="relative p-2 rounded-lg hover:bg-gray-100 text-gray-500 hover:text-gray-900
                   transition-colors"
            title="Keranjang belanja"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293
                         c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <!-- Badge jumlah item -->
            <span
                id="cartBadge"
                class="absolute -top-1 -right-1 bg-red-500 text-white
                       text-[10px] font-bold rounded-full w-4 h-4
                       flex items-center justify-center hidden"
            >
                0
            </span>
        </button>
        <?php endif; ?>

        <!-- Notifikasi (Stok Kritis) — hanya Admin -->
        <?php if (isAdmin()): ?>
        <button
            class="relative p-2 rounded-lg hover:bg-gray-100 text-gray-500 hover:text-gray-900
                   transition-colors"
            title="Notifikasi"
            onclick="window.location.href='/?q=stok'"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <?php
            // Cek stok kritis (stok <= 5)
            try {
                $pdo  = getDB();
                $stmt = $pdo->query("SELECT COUNT(*) FROM stok_barang WHERE stok <= 5 AND aktif = 1");
                $kritisCount = (int) $stmt->fetchColumn();
            } catch (Throwable $e) {
                $kritisCount = 0;
            }
            ?>
            <?php if ($kritisCount > 0): ?>
            <span class="absolute -top-1 -right-1 bg-orange-500 text-white
                         text-[10px] font-bold rounded-full w-4 h-4
                         flex items-center justify-center">
                <?= $kritisCount ?>
            </span>
            <?php endif; ?>
        </button>
        <?php endif; ?>

        <!-- ── Avatar Dropdown ── -->
        <div class="relative" x-data="{ open: false }">

            <!-- Tombol Avatar -->
            <button
                @click="open = !open"
                class="w-9 h-9 rounded-full bg-gradient-to-br from-green-500 to-teal-400
                       flex items-center justify-center text-white font-bold text-sm
                       hover:ring-2 hover:ring-green-400 hover:ring-offset-2
                       transition-all duration-150 shrink-0"
                aria-label="Menu akun"
            >
                <?= strtoupper(substr($_SESSION['namalengkap'] ?? '?', 0, 1)) ?>
            </button>

            <!-- Dropdown Panel -->
            <div
                x-show="open"
                @click.away="open = false"
                x-cloak
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95 -translate-y-1"
                x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl
                       border border-gray-100 overflow-hidden z-50"
            >
                <!-- Header: Info User -->
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
                    <p class="text-sm font-semibold text-gray-900 truncate">
                        <?= htmlspecialchars($_SESSION['namalengkap'] ?? '—') ?>
                    </p>
                    <p class="text-xs text-gray-400 truncate mt-0.5">
                        <?= htmlspecialchars($_SESSION['email'] ?? '—') ?>
                    </p>
                    <span class="inline-block mt-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold
                                 <?= (int)($_SESSION['role'] ?? 0) === 1
                                     ? 'bg-purple-100 text-purple-700'
                                     : 'bg-blue-100 text-blue-700' ?>">
                        <?= (int)($_SESSION['role'] ?? 0) === 1 ? 'Administrator' : 'Kasir' ?>
                    </span>
                </div>

                <!-- Menu Links -->
                <div class="py-1">
                    <a href="/?q=profil"
                       class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700
                              hover:bg-gray-50 transition-colors">
                        <span class="text-base">👤</span> Profil Saya
                    </a>

                    <a href="/?q=menu"
                       class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700
                              hover:bg-gray-50 transition-colors">
                        <span class="text-base">🛒</span> Kasir / POS
                    </a>

                    <?php if (isAdmin()): ?>
                    <a href="/?q=stok"
                       class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700
                              hover:bg-gray-50 transition-colors">
                        <span class="text-base">📦</span> Kelola Stok
                    </a>
                    <a href="/?q=laporan"
                       class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700
                              hover:bg-gray-50 transition-colors">
                        <span class="text-base">📊</span> Laporan
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Logout -->
                <div class="border-t border-gray-100">
                    <a href="/?q=logout"
                       class="flex items-center gap-2.5 px-4 py-2.5 text-sm font-medium
                              text-red-600 hover:bg-red-50 transition-colors">
                        <span class="text-base">🚪</span> Keluar dari Sistem
                    </a>
                </div>

            </div>
        </div>
        <!-- ── End Avatar Dropdown ── -->

    </div>
    <!-- ── End Kanan ── -->

</header>