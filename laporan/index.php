<?php
// PERBAIKAN [BUG-16]: File ini KOSONG di versi asli.
requireAdmin();
$stats = getStatsDashboard();
?>
<div class="space-y-5">
  <div><h1 class="text-xl font-extrabold text-gray-900">Laporan & Dashboard</h1>
  <p class="text-sm text-gray-500 mt-0.5">Ringkasan kinerja kantin hari ini</p></div>

  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <?php $cards = [
      ['label'=>'Pendapatan Hari Ini','value'=>'Rp '.number_format($stats['pendapatan_hari'],0,',','.'),'icon'=>'💰','bg'=>'bg-green-50','color'=>'text-green-700'],
      ['label'=>'Transaksi Hari Ini','value'=>$stats['trx_hari'].' transaksi','icon'=>'🧾','bg'=>'bg-blue-50','color'=>'text-blue-700'],
      ['label'=>'Total Piutang Aktif','value'=>'Rp '.number_format($stats['total_piutang'],0,',','.'),'icon'=>'📋','bg'=>'bg-orange-50','color'=>'text-orange-700'],
      ['label'=>'Stok Kritis (≤5)','value'=>$stats['stok_kritis'].' item','icon'=>'⚠️','bg'=>'bg-red-50','color'=>'text-red-700'],
    ]; ?>
    <?php foreach ($cards as $c): ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-3">
      <div class="<?= $c['bg'] ?> w-10 h-10 rounded-xl flex items-center justify-center text-xl shrink-0"><?= $c['icon'] ?></div>
      <div><p class="text-xs text-gray-500 font-medium"><?= $c['label'] ?></p>
      <p class="<?= $c['color'] ?> font-extrabold text-sm font-mono leading-tight"><?= $c['value'] ?></p></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="flex gap-3 flex-wrap">
    <a href="?q=laporan_penjualan" class="px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-bold rounded-xl transition-all shadow-sm">
      📊 Laporan Penjualan Detail
    </a>
    <a href="?q=piutang&status=belum_lunas" class="px-5 py-2.5 bg-orange-100 hover:bg-orange-200 text-orange-800 text-sm font-bold rounded-xl transition-all">
      📋 Lihat Piutang Aktif
    </a>
    <a href="?q=stok" class="px-5 py-2.5 bg-blue-100 hover:bg-blue-200 text-blue-800 text-sm font-bold rounded-xl transition-all">
      📦 Kelola Stok
    </a>
  </div>

  <?php if (!empty($stats['chart_7hari'])): ?>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
    <h2 class="font-bold text-gray-900 mb-4">Penjualan 7 Hari Terakhir</h2>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead><tr class="bg-gray-50 border-b"><th class="text-left px-3 py-2 text-xs text-gray-500 font-semibold uppercase">Tanggal</th><th class="text-right px-3 py-2 text-xs text-gray-500 font-semibold uppercase">Omzet</th><th class="text-right px-3 py-2 text-xs text-gray-500 font-semibold uppercase">Transaksi</th></tr></thead>
        <tbody class="divide-y divide-gray-50">
          <?php foreach ($stats['chart_7hari'] as $row): ?>
          <tr class="hover:bg-gray-50">
            <td class="px-3 py-2.5 font-medium"><?= date('d M Y', strtotime($row['tgl'])) ?></td>
            <td class="px-3 py-2.5 text-right font-mono font-semibold text-green-700">Rp <?= number_format($row['omzet'],0,',','.') ?></td>
            <td class="px-3 py-2.5 text-right text-gray-600"><?= $row['jml'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>
