<?php
requireAdmin();

$dari   = $_GET['dari']   ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');
$status = $_GET['status'] ?? '';

$rows = getLaporanPenjualan($dari, $sampai, $status ?: null);

$totalPendapatan = array_sum(array_column(
    array_filter($rows, fn($r) => $r['status'] !== 'Batal'), 'total'
));
$totalTransaksi  = count($rows);
?>

<style>
@media print {
  .no-print { display: none !important; }
  .print-full { width: 100% !important; margin: 0 !important; padding: 0 !important; }
  .card-print { box-shadow: none !important; border: 1px solid #e5e7eb !important; }
}
</style>

<div class="space-y-5">

  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 no-print">
    <div>
      <h1 class="text-xl font-extrabold text-gray-900">Laporan Penjualan</h1>
      <p class="text-sm text-gray-500 mt-0.5">Data transaksi <?= htmlspecialchars($dari) ?> s/d <?= htmlspecialchars($sampai) ?></p>
    </div>
    <div class="flex gap-2">
      <button onclick="window.print()" class="flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-xl transition-colors">
        🖨 Print
      </button>
      <button onclick="exportCSV()" class="flex items-center gap-2 px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
        ⬇ Export CSV
      </button>
    </div>
  </div>

  <form method="GET" action="" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 no-print">
    <input type="hidden" name="q" value="laporan_penjualan">
    <div class="flex flex-wrap gap-3 items-end">
      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Dari Tanggal</label>
        <input type="date" name="dari" value="<?= htmlspecialchars($dari) ?>"
               class="px-3 py-2 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
      </div>
      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Sampai</label>
        <input type="date" name="sampai" value="<?= htmlspecialchars($sampai) ?>"
               class="px-3 py-2 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
      </div>
      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
        <select name="status" class="px-3 py-2 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 transition bg-white">
          <option value="">Semua</option>
          <option value="Lunas"   <?= $status==='Lunas'   ?'selected':'' ?>>Lunas</option>
          <option value="Piutang" <?= $status==='Piutang' ?'selected':'' ?>>Piutang</option>
          <option value="Batal"   <?= $status==='Batal'   ?'selected':'' ?>>Batal</option>
        </select>
      </div>
      <button type="submit" class="px-5 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold rounded-xl transition-colors shadow-sm">Filter</button>
      <a href="?q=laporan_penjualan" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-xl transition-colors">Reset</a>
    </div>
  </form>

  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 no-print">
    <?php
    $tunaiTotal   = array_sum(array_column(array_filter($rows, fn($r)=>$r['metode']==='Cash'&&$r['status']==='Lunas'), 'total'));
    $piutangTotal = array_sum(array_column(array_filter($rows, fn($r)=>$r['status']==='Piutang'), 'total'));
    $batalCount   = count(array_filter($rows, fn($r)=>$r['status']==='Batal'));
    $cards = [
      ['label'=>'Total Pendapatan','value'=>'Rp '.number_format($totalPendapatan,0,',','.'),'color'=>'text-brand-600','bg'=>'bg-brand-50','icon'=>'💰'],
      ['label'=>'Total Transaksi', 'value'=>$totalTransaksi.' transaksi',                   'color'=>'text-blue-600', 'bg'=>'bg-blue-50', 'icon'=>'🧾'],
      ['label'=>'Pendapatan Tunai','value'=>'Rp '.number_format($tunaiTotal,0,',','.')   ,  'color'=>'text-green-600','bg'=>'bg-green-50','icon'=>'💵'],
      ['label'=>'Piutang',         'value'=>'Rp '.number_format($piutangTotal,0,',','.'),   'color'=>'text-orange-600','bg'=>'bg-orange-50','icon'=>'📋'],
    ];
    ?>
    <?php foreach ($cards as $c): ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-center gap-3">
      <div class="<?= $c['bg'] ?> w-10 h-10 rounded-xl flex items-center justify-center text-xl shrink-0"><?= $c['icon'] ?></div>
      <div>
        <p class="text-xs text-gray-500 font-medium"><?= $c['label'] ?></p>
        <p class="<?= $c['color'] ?> font-extrabold text-sm font-mono leading-tight"><?= $c['value'] ?></p>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden card-print">
    <!-- Print header -->
    <div class="hidden print:block p-4 border-b text-center">
      <p class="font-bold text-lg">KANTIN UAM — Laporan Penjualan</p>
      <p class="text-sm text-gray-500">Periode: <?= $dari ?> s/d <?= $sampai ?></p>
      <p class="text-sm text-gray-500">Dicetak: <?= date('d/m/Y H:i') ?></p>
    </div>

    <div class="px-4 py-3 border-b border-gray-100 no-print">
      <div class="relative max-w-xs">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
        </svg>
        <input type="text" id="tableSearch" placeholder="Cari transaksi..."
               oninput="filterTable(this.value)"
               class="pl-9 pr-4 py-2 rounded-xl border border-gray-200 text-sm w-full
                      focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-sm" id="laporanTable">
        <thead>
          <tr class="bg-gray-50 border-b border-gray-100">
            <th class="text-left px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide">#</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide">Tanggal</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide">Pelanggan</th>
            <th class="text-left px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide">Item</th>
            <th class="text-right px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide">Subtotal</th>
            <th class="text-right px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide">Diskon</th>
            <th class="text-right px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide">Total</th>
            <th class="text-center px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide">Metode</th>
            <th class="text-center px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide">Status</th>
            <th class="text-center px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wide no-print">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50" id="tableBody">
          <?php if (empty($rows)): ?>
          <tr>
            <td colspan="10" class="text-center py-12 text-gray-400">
              <div class="flex flex-col items-center gap-2">
                <span class="text-4xl">📊</span>
                <p class="font-medium">Tidak ada data untuk periode ini</p>
              </div>
            </td>
          </tr>
          <?php else: ?>
          <?php foreach ($rows as $i => $row):
            $statusColor = match($row['status']) {
              'Lunas'   => 'bg-green-100 text-green-700',
              'Piutang' => 'bg-orange-100 text-orange-700',
              'Batal'   => 'bg-red-100 text-red-700',
              default   => 'bg-gray-100 text-gray-600',
            };
            $metodeColor = match($row['metode']) {
              'Cash'     => 'bg-blue-100 text-blue-700',
              'Transfer' => 'bg-purple-100 text-purple-700',
              'Piutang'  => 'bg-orange-100 text-orange-700',
              default    => 'bg-gray-100 text-gray-600',
            };
            $itemNames = implode(', ', array_map(fn($it)=>$it['nama_item'].'×'.$it['qty'], $row['items']));
          ?>
          <tr class="hover:bg-gray-50 transition-colors table-row" data-search="<?= strtolower(htmlspecialchars($row['nama_pembeli'].$row['status'].$itemNames)) ?>">
            <td class="px-4 py-3 text-gray-400 font-mono text-xs"><?= $row['id'] ?></td>
            <td class="px-4 py-3">
              <p class="font-medium text-gray-900"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></p>
              <p class="text-xs text-gray-400"><?= date('H:i', strtotime($row['tanggal'])) ?></p>
            </td>
            <td class="px-4 py-3 font-semibold text-gray-900"><?= htmlspecialchars($row['nama_pembeli']) ?></td>
            <td class="px-4 py-3 text-gray-500 text-xs max-w-[180px] truncate" title="<?= htmlspecialchars($itemNames) ?>"><?= htmlspecialchars($itemNames) ?></td>
            <td class="px-4 py-3 text-right font-mono text-gray-600">Rp <?= number_format($row['subtotal'],0,',','.') ?></td>
            <td class="px-4 py-3 text-right font-mono text-orange-600">
              <?= $row['diskon'] > 0 ? '-Rp '.number_format($row['diskon'],0,',','.') : '—' ?>
            </td>
            <td class="px-4 py-3 text-right font-mono font-bold text-gray-900">Rp <?= number_format($row['total'],0,',','.') ?></td>
            <td class="px-4 py-3 text-center">
              <span class="<?= $metodeColor ?> px-2.5 py-0.5 rounded-full text-xs font-semibold"><?= $row['metode'] ?></span>
            </td>
            <td class="px-4 py-3 text-center">
              <span class="<?= $statusColor ?> px-2.5 py-0.5 rounded-full text-xs font-semibold"><?= $row['status'] ?></span>
            </td>
            <td class="px-4 py-3 text-center no-print">
              <button onclick="lihatDetail(<?= $row['id'] ?>)"
                      class="px-3 py-1.5 text-xs font-semibold bg-gray-100 hover:bg-brand-100 hover:text-brand-700 rounded-lg transition-colors">
                Detail
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
        <?php if (!empty($rows)): ?>
        <tfoot>
          <tr class="bg-gray-50 border-t-2 border-gray-200 font-bold">
            <td colspan="6" class="px-4 py-3 text-right text-sm text-gray-700">TOTAL PENDAPATAN:</td>
            <td class="px-4 py-3 text-right font-mono text-brand-700 text-sm">Rp <?= number_format($totalPendapatan,0,',','.') ?></td>
            <td colspan="3"></td>
          </tr>
        </tfoot>
        <?php endif; ?>
      </table>
    </div>
  </div>

</div>

<div id="modalDetail" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b">
      <h3 class="font-bold text-gray-900">Detail Transaksi</h3>
      <button onclick="document.getElementById('modalDetail').style.display='none'" class="w-7 h-7 rounded-full bg-gray-100 hover:bg-gray-200 text-sm flex items-center justify-center transition-colors">✕</button>
    </div>
    <div id="detailContent" class="p-5"></div>
  </div>
</div>

<script>
const laporanData = <?= json_encode(array_map(fn($r) => [
  'id'          => $r['id'],
  'nama'        => $r['nama_pembeli'],
  'tanggal'     => $r['tanggal'],
  'items'       => $r['items'],
  'subtotal'    => $r['subtotal'],
  'diskon'      => $r['diskon'],
  'pajak'       => $r['pajak'],
  'total'       => $r['total'],
  'metode'      => $r['metode'],
  'status'      => $r['status'],
  'kembalian'   => $r['kembalian'],
  'keterangan'  => $r['keterangan'],
  'kasir_nama'  => $r['kasir_nama'] ?? '—',
], $rows), JSON_UNESCAPED_UNICODE) ?>;

function filterTable(q) {
  document.querySelectorAll('.table-row').forEach(tr => {
    const hay = tr.dataset.search || '';
    tr.style.display = !q || hay.includes(q.toLowerCase()) ? '' : 'none';
  });
}

function lihatDetail(id) {
  const row = laporanData.find(r => r.id === id);
  if (!row) return;

  const fmtRp = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');
  const itemsHtml = row.items.map(i =>
    `<div class="flex justify-between text-sm py-1">
      <span>${i.nama_item} ×${i.qty}</span>
      <span class="font-mono">${fmtRp(i.harga * i.qty)}</span>
    </div>`
  ).join('');

  document.getElementById('detailContent').innerHTML = `
    <div class="space-y-3">
      <div class="grid grid-cols-2 gap-2 text-sm">
        <div><p class="text-xs text-gray-500">ID</p><p class="font-bold">#${row.id}</p></div>
        <div><p class="text-xs text-gray-500">Pelanggan</p><p class="font-bold">${row.nama}</p></div>
        <div><p class="text-xs text-gray-500">Tanggal</p><p class="font-medium">${new Date(row.tanggal).toLocaleString('id-ID')}</p></div>
        <div><p class="text-xs text-gray-500">Kasir</p><p class="font-medium">${row.kasir_nama}</p></div>
      </div>
      <div class="border-t border-gray-100 pt-3">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Item Pesanan</p>
        ${itemsHtml}
      </div>
      <div class="border-t border-gray-100 pt-3 space-y-1 text-sm">
        <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span class="font-mono">${fmtRp(row.subtotal)}</span></div>
        ${row.diskon > 0 ? `<div class="flex justify-between text-orange-600"><span>Diskon</span><span class="font-mono">-${fmtRp(row.diskon)}</span></div>` : ''}
        ${row.pajak  > 0 ? `<div class="flex justify-between text-blue-600"><span>Pajak</span><span class="font-mono">+${fmtRp(row.pajak)}</span></div>`  : ''}
        <div class="flex justify-between font-bold text-base border-t border-gray-200 pt-2">
          <span>TOTAL</span><span class="font-mono text-brand-700">${fmtRp(row.total)}</span>
        </div>
        ${row.kembalian > 0 ? `<div class="flex justify-between text-green-600"><span>Kembalian</span><span class="font-mono">${fmtRp(row.kembalian)}</span></div>` : ''}
      </div>
      ${row.keterangan ? `<p class="text-xs text-gray-400 italic">"${row.keterangan}"</p>` : ''}
    </div>
  `;
  document.getElementById('modalDetail').style.display = 'flex';
}

function exportCSV() {
  const header = ['ID','Tanggal','Pelanggan','Item','Subtotal','Diskon','Pajak','Total','Metode','Status'];
  const rows   = laporanData.map(r => [
    r.id,
    new Date(r.tanggal).toLocaleString('id-ID'),
    `"${r.nama}"`,
    `"${r.items.map(i=>i.nama_item+'x'+i.qty).join('; ')}"`,
    r.subtotal, r.diskon, r.pajak, r.total, r.metode, r.status,
  ]);
  const csv  = [header, ...rows].map(r=>r.join(',')).join('\n');
  const blob = new Blob(['\uFEFF'+csv], {type:'text/csv;charset=utf-8'});
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href = url; a.download = `laporan-penjualan-<?= $dari ?>-<?= $sampai ?>.csv`; a.click();
  URL.revokeObjectURL(url);
}
</script>