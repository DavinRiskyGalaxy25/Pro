<?php
// PERBAIKAN [BUG-15]: File ini KOSONG di versi asli.
requireKasir();
$statusFilter = $_GET['status'] ?? '';
$piutangList  = getAllPiutang($statusFilter);

$msg = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bayar'])) {
    csrfCheck();
    $result = bayarPiutang(
        (int)($_POST['id_piutang'] ?? 0),
        cleanMoney($_POST['jumlah_bayar'] ?? 0),
        sanitize($_POST['dibayar_oleh'] ?? $_SESSION['namalengkap'] ?? 'kasir')
    );
    $msg = $result['status'] === 'success' ? ($result['lunas'] ? '🎉 Piutang LUNAS!' : 'Pembayaran berhasil dicatat.') : '';
    $err = $result['status'] !== 'success' ? ($result['message'] ?? 'Gagal.') : '';
    $piutangList = getAllPiutang($statusFilter);
}
?>
<div class="space-y-5">
  <div class="flex items-center justify-between flex-wrap gap-3">
    <div><h1 class="text-xl font-extrabold text-gray-900">Data Piutang</h1>
    <p class="text-sm text-gray-500 mt-0.5">Kelola hutang pelanggan</p></div>
    <div class="flex gap-2">
      <?php foreach ([''=>'Semua','belum_lunas'=>'Belum Lunas','lunas'=>'Lunas'] as $val=>$lbl): ?>
      <a href="?q=piutang&status=<?= $val ?>"
         class="px-3 py-1.5 rounded-full text-xs font-semibold border transition-all
                <?= $statusFilter===$val ? 'bg-green-600 text-white border-green-600' : 'bg-white text-gray-500 border-gray-200 hover:border-green-400' ?>">
        <?= $lbl ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if ($msg): ?><div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead><tr class="bg-gray-50 border-b border-gray-100">
          <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Pelanggan</th>
          <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Total Hutang</th>
          <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Sisa Hutang</th>
          <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
          <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
          <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Aksi</th>
        </tr></thead>
        <tbody class="divide-y divide-gray-50">
          <?php if (empty($piutangList)): ?>
          <tr><td colspan="6" class="text-center py-12 text-gray-400">Tidak ada data piutang.</td></tr>
          <?php else: ?>
          <?php foreach ($piutangList as $p): $isLunas = $p['status']==='lunas'; ?>
          <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 font-semibold text-gray-900"><?= htmlspecialchars($p['nama_pembeli']) ?></td>
            <td class="px-4 py-3 text-right font-mono text-gray-600">Rp <?= number_format($p['total_hutang'],0,',','.') ?></td>
            <td class="px-4 py-3 text-right font-mono font-bold <?= $isLunas ? 'text-green-600' : 'text-red-600' ?>">
              Rp <?= number_format($p['sisa_hutang'],0,',','.') ?>
            </td>
            <td class="px-4 py-3 text-center">
              <span class="px-2.5 py-0.5 rounded-full text-xs font-bold <?= $isLunas ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' ?>">
                <?= $isLunas ? 'Lunas' : 'Belum Lunas' ?>
              </span>
            </td>
            <td class="px-4 py-3 text-center text-gray-500 text-xs"><?= date('d/m/Y', strtotime($p['tanggal'])) ?></td>
            <td class="px-4 py-3 text-center">
              <?php if (!$isLunas): ?>
              <button onclick="bukaBayar(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nama_pembeli'], ENT_QUOTES) ?>', <?= $p['sisa_hutang'] ?>)"
                      class="px-3 py-1.5 bg-green-50 hover:bg-green-100 text-green-700 text-xs font-semibold rounded-lg transition-colors">
                Bayar
              </button>
              <?php else: ?>
              <span class="text-gray-300 text-xs">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Bayar -->
<div id="modalBayar" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4 flex">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b">
      <h3 class="font-bold text-gray-900">Bayar Piutang</h3>
      <button onclick="document.getElementById('modalBayar').classList.add('hidden')" class="w-7 h-7 rounded-full bg-gray-100 hover:bg-gray-200 text-sm flex items-center justify-center">✕</button>
    </div>
    <form method="POST" class="p-5 space-y-3">
      <?= csrfField() ?>
      <input type="hidden" name="bayar" value="1">
      <input type="hidden" name="id_piutang" id="bayarId">
      <div class="bg-orange-50 border border-orange-200 rounded-xl p-3 text-sm">
        <p class="font-semibold text-orange-800" id="bayarNama">—</p>
        <p class="text-orange-600 font-mono mt-0.5">Sisa: <strong id="bayarSisa">—</strong></p>
      </div>
      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Jumlah Bayar *</label>
        <input type="number" name="jumlah_bayar" id="jumlahBayar" min="1" required
               class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 transition font-mono">
      </div>
      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Dibayar Oleh</label>
        <input type="text" name="dibayar_oleh" value="<?= htmlspecialchars($_SESSION['namalengkap'] ?? '') ?>"
               class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 transition">
      </div>
      <div class="flex justify-end gap-3 pt-2">
        <button type="button" onclick="document.getElementById('modalBayar').classList.add('hidden')" class="px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">Batal</button>
        <button type="submit" class="px-5 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-bold rounded-xl transition-all shadow-sm">Konfirmasi Bayar</button>
      </div>
    </form>
  </div>
</div>
<script>
function bukaBayar(id, nama, sisa) {
  document.getElementById('bayarId').value = id;
  document.getElementById('bayarNama').textContent = nama;
  document.getElementById('bayarSisa').textContent = 'Rp ' + sisa.toLocaleString('id-ID');
  document.getElementById('jumlahBayar').max = sisa;
  document.getElementById('jumlahBayar').value = sisa;
  document.getElementById('modalBayar').classList.remove('hidden');
}
</script>