<?php
requireAdmin();
$barangList = getAllBarang();
$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $act = $_POST['action'] ?? '';

    if ($act === 'tambah') {
        $id = tambahBarang([
            'nama_barang'  => $_POST['nama_barang']  ?? '',
            'tipe'         => $_POST['tipe']         ?? 'makanan',
            'stok'         => (int)($_POST['stok']   ?? 0),
            'harga_dasar'  => cleanMoney($_POST['harga_dasar'] ?? 0),
            'harga_jual'   => cleanMoney($_POST['harga_jual']  ?? 0),
            'dicatat_oleh' => $_SESSION['namalengkap'] ?? 'admin',
        ]);
        $msg = $id ? "Barang berhasil ditambahkan (ID: $id)" : '';
        $err = $id ? '' : 'Gagal menambahkan barang.';
        $barangList = getAllBarang();
    }

    if ($act === 'restock') {
        $ok = restockBarang(
            (int)($_POST['id_barang'] ?? 0),
            (int)($_POST['jumlah']    ?? 0),
            sanitize($_POST['pemasok'] ?? ''),
            $_SESSION['namalengkap']  ?? 'admin'
        );
        $msg = $ok ? 'Restock berhasil.' : '';
        $err = $ok ? '' : 'Gagal restock.';
        $barangList = getAllBarang();
    }

    if ($act === 'toggle') {
        $pdo  = getDB();
        $curr = $pdo->prepare("SELECT aktif FROM stok_barang WHERE id=?");
        $curr->execute([(int)$_POST['id_barang']]);
        $row  = $curr->fetch();
        if ($row) {
            editBarang((int)$_POST['id_barang'], array_merge($row, ['aktif' => $row['aktif'] ? 0 : 1]));
            $msg = 'Status barang diperbarui.';
        }
        $barangList = getAllBarang();
    }
}
?>
<div class="space-y-5">
  <div class="flex items-center justify-between">
    <div><h1 class="text-xl font-extrabold text-gray-900">Stok Barang</h1>
    <p class="text-sm text-gray-500 mt-0.5">Manajemen inventory menu kantin</p></div>
    <button onclick="document.getElementById('modalTambah').classList.remove('hidden')"
            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-bold rounded-xl transition-all shadow-sm">
      + Tambah Barang
    </button>
  </div>

  <?php if ($msg): ?><div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">⚠️ <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-gray-50 border-b border-gray-100">
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Nama</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Tipe</th>
            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Stok</th>
            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Harga Jual</th>
            <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
            <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <?php if (empty($barangList)): ?>
          <tr><td colspan="6" class="text-center py-12 text-gray-400">Belum ada barang. Tambahkan barang baru.</td></tr>
          <?php else: ?>
          <?php foreach ($barangList as $b): ?>
          <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 font-semibold text-gray-900"><?= htmlspecialchars($b['nama_barang']) ?></td>
            <td class="px-4 py-3">
              <span class="bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full text-xs font-semibold capitalize"><?= $b['tipe'] ?></span>
            </td>
            <td class="px-4 py-3 text-right font-mono">
              <span class="<?= $b['stok'] <= 5 ? 'text-red-600 font-bold' : 'text-gray-900' ?>"><?= $b['stok'] ?></span>
              <?php if ($b['stok'] <= 5 && $b['stok'] > 0): ?>
              <span class="ml-1 text-[10px] bg-orange-100 text-orange-600 px-1.5 py-0.5 rounded-full font-bold">Kritis</span>
              <?php elseif ($b['stok'] == 0): ?>
              <span class="ml-1 text-[10px] bg-red-100 text-red-600 px-1.5 py-0.5 rounded-full font-bold">Habis</span>
              <?php endif; ?>
            </td>
            <td class="px-4 py-3 text-right font-mono text-green-700 font-semibold">
              Rp <?= number_format($b['harga_jual'], 0, ',', '.') ?>
            </td>
            <td class="px-4 py-3 text-center">
              <form method="POST" class="inline">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id_barang" value="<?= $b['id'] ?>">
                <button type="submit" class="<?= $b['aktif'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?> px-2.5 py-0.5 rounded-full text-xs font-bold transition-colors hover:opacity-80">
                  <?= $b['aktif'] ? 'Aktif' : 'Nonaktif' ?>
                </button>
              </form>
            </td>
            <td class="px-4 py-3 text-center">
              <button onclick="bukaRestock(<?= $b['id'] ?>, '<?= htmlspecialchars($b['nama_barang'], ENT_QUOTES) ?>')"
                      class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold rounded-lg transition-colors">
                Restock
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Tambah Barang -->
<div id="modalTambah" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4 flex">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b">
      <h3 class="font-bold text-gray-900">Tambah Barang Baru</h3>
      <button onclick="document.getElementById('modalTambah').classList.add('hidden')" class="w-7 h-7 rounded-full bg-gray-100 hover:bg-gray-200 text-sm flex items-center justify-center">✕</button>
    </div>
    <form method="POST" class="p-5 space-y-3">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="tambah">
      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Barang *</label>
        <input type="text" name="nama_barang" required class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 transition">
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1">Tipe</label>
          <select name="tipe" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 bg-white transition">
            <option value="makanan">Makanan</option>
            <option value="minuman">Minuman</option>
            <option value="snack">Snack</option>
            <option value="lainnya">Lainnya</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1">Stok Awal *</label>
          <input type="number" name="stok" min="0" required class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 transition">
        </div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1">Harga Dasar *</label>
          <input type="number" name="harga_dasar" min="0" required class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 transition">
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1">Harga Jual *</label>
          <input type="number" name="harga_jual" min="0" required class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 transition">
        </div>
      </div>
      <div class="flex justify-end gap-3 pt-2">
        <button type="button" onclick="document.getElementById('modalTambah').classList.add('hidden')" class="px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">Batal</button>
        <button type="submit" class="px-5 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-bold rounded-xl transition-all shadow-sm">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Restock -->
<div id="modalRestock" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4 flex">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b">
      <h3 class="font-bold text-gray-900">Restock: <span id="restockNama">—</span></h3>
      <button onclick="document.getElementById('modalRestock').classList.add('hidden')" class="w-7 h-7 rounded-full bg-gray-100 hover:bg-gray-200 text-sm flex items-center justify-center">✕</button>
    </div>
    <form method="POST" class="p-5 space-y-3">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="restock">
      <input type="hidden" name="id_barang" id="restockId">
      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Jumlah Tambah *</label>
        <input type="number" name="jumlah" min="1" required class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 transition">
      </div>
      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Pemasok</label>
        <input type="text" name="pemasok" placeholder="Nama pemasok (opsional)" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 transition">
      </div>
      <div class="flex justify-end gap-3 pt-2">
        <button type="button" onclick="document.getElementById('modalRestock').classList.add('hidden')" class="px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">Batal</button>
        <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-xl transition-all shadow-sm">Restock</button>
      </div>
    </form>
  </div>
</div>

<script>
function bukaRestock(id, nama) {
  document.getElementById('restockId').value = id;
  document.getElementById('restockNama').textContent = nama;
  document.getElementById('modalRestock').classList.remove('hidden');
}
</script>
