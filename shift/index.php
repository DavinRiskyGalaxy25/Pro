<?php
// PERBAIKAN [BUG-17]: File ini KOSONG di versi asli.
requireKasir();
?>
<div class="space-y-5">
  <div><h1 class="text-xl font-extrabold text-gray-900">Shift Kasir</h1>
  <p class="text-sm text-gray-500">Fitur manajemen shift akan segera tersedia.</p></div>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center text-gray-400">
    <p class="text-4xl mb-3">🕐</p>
    <p class="font-semibold">Modul Shift dalam pengembangan</p>
    <p class="text-sm mt-1">Saat ini kamu login sebagai: <strong class="text-gray-700"><?= htmlspecialchars($_SESSION['namalengkap'] ?? '—') ?></strong></p>
    <p class="text-sm text-gray-400 mt-0.5">Login sejak: <?= isset($_SESSION['login_at']) ? date('d M Y H:i', $_SESSION['login_at']) : '—' ?></p>
  </div>
</div>
