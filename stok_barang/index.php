<?php
// stok_barang/index.php — Manajemen Produk + Kategori + SKU + Upload Foto
requireAdmin();

$barangList  = getAllBarang();
$kategoriList = getAllKategori();
$msg = ''; $err = '';

// Handle foto upload helper
function handleFotoUpload(): ?string {
    if (empty($_FILES['foto']['name'])) return null;
    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    $mime    = mime_content_type($_FILES['foto']['tmp_name']);
    if (!in_array($mime, $allowed)) { throw new RuntimeException('Tipe file tidak diizinkan. Gunakan JPG, PNG, atau WEBP.'); }
    if ($_FILES['foto']['size'] > 2 * 1024 * 1024) { throw new RuntimeException('Ukuran file maks 2MB.'); }
    $ext      = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'][$mime];
    $filename = uniqid('prod_') . '.' . $ext;
    $dir      = __DIR__ . '/../public/uploads/produk/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    move_uploaded_file($_FILES['foto']['tmp_name'], $dir . $filename);
    return $filename;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $act = $_POST['action'] ?? '';

    if ($act === 'tambah') {
        try {
            // SKU: pakai input user jika ada, generate otomatis jika kosong
            $skuInput = trim($_POST['sku'] ?? '');
            if (!$skuInput) {
                $skuInput = generateSKU($_POST['nama_barang'] ?? 'PRD');
                // Pastikan unik
                $tries = 0;
                while (!isSKUUnique($skuInput) && $tries < 10) {
                    $skuInput = generateSKU($_POST['nama_barang'] ?? 'PRD'); $tries++;
                }
            } elseif (!isSKUUnique($skuInput)) {
                throw new RuntimeException("SKU '$skuInput' sudah digunakan.");
            }

            $fotoFile = handleFotoUpload();

            $pdo = getDB();
            $stmt = $pdo->prepare("
                INSERT INTO stok_barang (sku, id_kategori, nama_barang, tipe, stok, harga_dasar, harga_jual, foto, aktif)
                VALUES (?,?,?,?,?,?,?,?,1)
            ");
            $stmt->execute([
                $skuInput,
                $_POST['id_kategori'] ?: null,
                sanitize($_POST['nama_barang']),
                $_POST['tipe'] ?? 'makanan',
                (int)$_POST['stok'],
                cleanMoney($_POST['harga_dasar']),
                cleanMoney($_POST['harga_jual']),
                $fotoFile,
            ]);
            $newId = (int)$pdo->lastInsertId();

            // Catat riwayat stok awal
            $pdo->prepare("INSERT INTO riwayat_stok (id_barang,nama_barang,jenis,jumlah,harga_satuan,total,dicatat_oleh) VALUES (?,?,'tambah_barang',?,?,?,?)")
                ->execute([$newId, sanitize($_POST['nama_barang']), (int)$_POST['stok'],
                           cleanMoney($_POST['harga_dasar']), (int)$_POST['stok']*cleanMoney($_POST['harga_dasar']),
                           $_SESSION['namalengkap'] ?? 'admin']);
            $msg = "Produk berhasil ditambahkan (SKU: $skuInput)";
        } catch (RuntimeException $e) { $err = $e->getMessage(); }
        catch (Throwable $e) { error_log($e); $err = 'Gagal menyimpan produk.'; }
        $barangList = getAllBarang();
    }

    if ($act === 'tambah_kategori') {
        $namaKat = trim($_POST['nama_kategori'] ?? '');
        if ($namaKat) {
            $id = tambahKategori($namaKat, $_POST['warna_kategori'] ?? '#f97316');
            $msg = $id ? "Kategori '$namaKat' ditambahkan." : ''; $err = $id ? '' : 'Gagal tambah kategori.';
        }
        $kategoriList = getAllKategori();
    }

    if ($act === 'restock') {
        $ok = restockBarang((int)$_POST['id_barang'], (int)$_POST['jumlah'],
              sanitize($_POST['pemasok'] ?? ''), $_SESSION['namalengkap'] ?? 'admin');
        $msg = $ok ? 'Restock berhasil.' : ''; $err = $ok ? '' : 'Gagal restock.';
        $barangList = getAllBarang();
    }

    if ($act === 'toggle') {
        $pdo  = getDB();
        $curr = $pdo->prepare("SELECT * FROM stok_barang WHERE id=?");
        $curr->execute([(int)$_POST['id_barang']]);
        $row  = $curr->fetch();
        if ($row) { editBarang((int)$_POST['id_barang'], array_merge($row,['aktif'=>$row['aktif']?0:1])); $msg='Status diperbarui.'; }
        $barangList = getAllBarang();
    }
}
?>

<div class="space-y-5">

  <!-- Page header -->
  <div class="flex items-center justify-between flex-wrap gap-3">
    <div>
      <h1 class="text-xl font-extrabold text-gray-900">
        <i class="fa-solid fa-box-open text-orange-500 mr-2"></i>Stok Produk
      </h1>
      <p class="text-sm text-gray-500 mt-0.5">Kelola produk, kategori, dan inventori kantin</p>
    </div>
    <div class="flex gap-2">
      <button onclick="showModal('modalKategori')"
              class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 hover:border-orange-300
                     hover:bg-orange-50 text-gray-700 hover:text-orange-700 text-sm font-semibold
                     rounded-xl transition-all shadow-sm">
        <i class="fa-solid fa-tag"></i> Kategori
      </button>
      <button onclick="showModal('modalTambah')"
              class="flex items-center gap-2 px-4 py-2 bg-orange-grad text-white text-sm font-bold
                     rounded-xl transition-all shadow-orange hover:opacity-90 active:scale-[.98]">
        <i class="fa-solid fa-plus"></i> Tambah Produk
      </button>
    </div>
  </div>

  <!-- Alerts -->
  <?php if ($msg): ?>
  <div class="flex items-center gap-2.5 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-4 py-3 text-sm">
    <i class="fa-solid fa-circle-check shrink-0"></i><?= htmlspecialchars($msg) ?>
  </div>
  <?php endif; ?>
  <?php if ($err): ?>
  <div class="flex items-center gap-2.5 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
    <i class="fa-solid fa-circle-exclamation shrink-0"></i><?= htmlspecialchars($err) ?>
  </div>
  <?php endif; ?>

  <!-- Kategori chips -->
  <?php if (!empty($kategoriList)): ?>
  <div class="flex flex-wrap gap-2">
    <?php foreach ($kategoriList as $kat): ?>
    <span class="px-3 py-1 rounded-full text-xs font-semibold border"
          style="background:<?= htmlspecialchars($kat['warna']) ?>20;color:<?= htmlspecialchars($kat['warna']) ?>;border-color:<?= htmlspecialchars($kat['warna']) ?>50">
      <?= htmlspecialchars($kat['nama']) ?>
    </span>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Table -->
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

    <!-- Search bar -->
    <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-3">
      <div class="relative flex-1 max-w-xs">
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
        <input type="text" id="searchProduk" placeholder="Cari nama atau SKU..."
               oninput="filterProduk(this.value)"
               class="w-full pl-9 pr-4 py-2 rounded-xl border border-gray-200 text-sm
                      focus:outline-none focus:ring-2 focus:ring-orange-400 transition">
      </div>
      <span class="text-xs text-gray-400"><?= count($barangList) ?> produk</span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-sm" id="tabelProduk">
        <thead>
          <tr class="bg-gray-50 border-b border-gray-100 text-xs font-semibold text-gray-500 uppercase tracking-wide">
            <th class="text-left px-4 py-3">SKU</th>
            <th class="text-left px-4 py-3">Produk</th>
            <th class="text-left px-4 py-3">Kategori</th>
            <th class="text-right px-4 py-3">Stok</th>
            <th class="text-right px-4 py-3">Harga Jual</th>
            <th class="text-center px-4 py-3">Status</th>
            <th class="text-center px-4 py-3">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50" id="bodyProduk">
          <?php if (empty($barangList)): ?>
          <tr><td colspan="7" class="text-center py-14 text-gray-400">
            <i class="fa-solid fa-box-open text-4xl mb-3 block text-gray-200"></i>
            Belum ada produk. Tambahkan produk pertama.
          </td></tr>
          <?php else: ?>
          <?php
          // Build kategori lookup
          $katMap = [];
          foreach ($kategoriList as $k) $katMap[$k['id']] = $k;
          ?>
          <?php foreach ($barangList as $b):
            $kat = $katMap[$b['id_kategori'] ?? 0] ?? null;
          ?>
          <tr class="hover:bg-orange-50/30 transition-colors prod-row"
              data-search="<?= strtolower($b['nama_barang'] . ' ' . ($b['sku'] ?? '')) ?>">
            <td class="px-4 py-3">
              <span class="font-mono text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded">
                <?= htmlspecialchars($b['sku'] ?? '—') ?>
              </span>
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <?php if (!empty($b['foto'])): ?>
                <img src="/public/uploads/produk/<?= htmlspecialchars($b['foto']) ?>"
                     class="w-9 h-9 rounded-lg object-cover border border-gray-100" alt="">
                <?php else: ?>
                <div class="w-9 h-9 rounded-lg bg-orange-50 flex items-center justify-center text-orange-300 text-lg shrink-0">
                  <?= ['makanan'=>'🍛','minuman'=>'🥤','snack'=>'🍿'][$b['tipe']] ?? '🍽️' ?>
                </div>
                <?php endif; ?>
                <div>
                  <p class="font-semibold text-gray-900"><?= htmlspecialchars($b['nama_barang']) ?></p>
                  <p class="text-xs text-gray-400 capitalize"><?= $b['tipe'] ?></p>
                </div>
              </div>
            </td>
            <td class="px-4 py-3">
              <?php if ($kat): ?>
              <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold"
                    style="background:<?= htmlspecialchars($kat['warna']) ?>18;color:<?= htmlspecialchars($kat['warna']) ?>">
                <?= htmlspecialchars($kat['nama']) ?>
              </span>
              <?php else: ?>
              <span class="text-gray-300 text-xs">—</span>
              <?php endif; ?>
            </td>
            <td class="px-4 py-3 text-right font-mono">
              <span class="font-bold <?= $b['stok']==0?'text-red-600':($b['stok']<=5?'text-orange-500':'text-gray-900') ?>">
                <?= $b['stok'] ?>
              </span>
              <?php if ($b['stok']==0): ?>
              <span class="ml-1 text-[10px] bg-red-100 text-red-600 px-1.5 py-0.5 rounded-full font-bold">Habis</span>
              <?php elseif ($b['stok']<=5): ?>
              <span class="ml-1 text-[10px] bg-orange-100 text-orange-600 px-1.5 py-0.5 rounded-full font-bold">Kritis</span>
              <?php endif; ?>
            </td>
            <td class="px-4 py-3 text-right font-mono font-semibold text-emerald-700">
              Rp <?= number_format($b['harga_jual'],0,',','.') ?>
            </td>
            <td class="px-4 py-3 text-center">
              <form method="POST" class="inline">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id_barang" value="<?= $b['id'] ?>">
                <button type="submit"
                        class="px-2.5 py-1 rounded-full text-xs font-bold transition-colors
                               <?= $b['aktif']?'bg-emerald-100 text-emerald-700 hover:bg-emerald-200':'bg-gray-100 text-gray-500 hover:bg-gray-200' ?>">
                  <?= $b['aktif']?'Aktif':'Nonaktif' ?>
                </button>
              </form>
            </td>
            <td class="px-4 py-3 text-center">
              <button onclick="bukaRestock(<?= $b['id'] ?>,'<?= htmlspecialchars($b['nama_barang'],ENT_QUOTES) ?>')"
                      class="px-3 py-1.5 bg-orange-50 hover:bg-orange-100 text-orange-700 text-xs font-semibold rounded-lg transition-colors">
                <i class="fa-solid fa-rotate mr-1"></i>Restock
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

<!-- Modal: Tambah Produk -->
<div id="modalTambah" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden max-h-[90vh] flex flex-col">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
      <h3 class="font-bold text-gray-900"><i class="fa-solid fa-plus text-orange-500 mr-2"></i>Tambah Produk</h3>
      <button onclick="hideModal('modalTambah')" class="w-7 h-7 rounded-full bg-gray-100 hover:bg-gray-200 text-sm flex items-center justify-center transition-colors">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <form method="POST" enctype="multipart/form-data" class="p-6 overflow-y-auto space-y-4">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="tambah">

      <!-- SKU -->
      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">
          SKU / Kode Produk
          <span class="ml-1 text-gray-400 font-normal">(kosongkan = otomatis)</span>
        </label>
        <div class="flex gap-2">
          <input type="text" name="sku" id="skuInput" placeholder="Contoh: NAS-00001"
                 class="flex-1 px-3 py-2.5 rounded-xl border border-gray-200 text-sm font-mono
                        focus:outline-none focus:ring-2 focus:ring-orange-400 transition uppercase"
                 oninput="this.value=this.value.toUpperCase()">
          <button type="button" onclick="generateSKUPreview()"
                  class="px-3 py-2.5 bg-orange-50 hover:bg-orange-100 text-orange-700 text-xs font-semibold
                         rounded-xl transition-colors border border-orange-200 shrink-0">
            <i class="fa-solid fa-wand-magic-sparkles mr-1"></i>Generate
          </button>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div class="col-span-2">
          <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Produk *</label>
          <input type="text" name="nama_barang" required id="namaBarangInput"
                 oninput="prefillSKU()"
                 class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 transition">
        </div>

        <!-- Kategori -->
        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1">Kategori</label>
          <select name="id_kategori"
                  class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 bg-white transition">
            <option value="">— Pilih Kategori —</option>
            <?php foreach ($kategoriList as $k): ?>
            <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Tipe -->
        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1">Tipe</label>
          <select name="tipe" class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 bg-white transition">
            <option value="makanan">Makanan</option>
            <option value="minuman">Minuman</option>
            <option value="snack">Snack</option>
            <option value="lainnya">Lainnya</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1">Stok Awal *</label>
          <input type="number" name="stok" min="0" required value="0"
                 class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 transition">
        </div>
        <div></div>
        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1">Harga Dasar (Rp) *</label>
          <input type="number" name="harga_dasar" min="0" required
                 class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-orange-400 transition">
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1">Harga Jual (Rp) *</label>
          <input type="number" name="harga_jual" min="0" required
                 class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-orange-400 transition">
        </div>
      </div>

      <!-- Foto upload -->
      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">
          <i class="fa-solid fa-image text-orange-400 mr-1"></i>Foto Produk
          <span class="text-gray-400 font-normal ml-1">(opsional, maks 2MB)</span>
        </label>
        <div class="border-2 border-dashed border-gray-200 hover:border-orange-300 rounded-xl p-4 text-center transition-colors cursor-pointer"
             onclick="document.getElementById('fotoInput').click()">
          <div id="fotoPreview" class="hidden mb-3">
            <img id="fotoPreviewImg" src="" alt="" class="w-20 h-20 object-cover rounded-xl mx-auto">
          </div>
          <div id="fotoPlaceholder">
            <i class="fa-solid fa-cloud-arrow-up text-3xl text-gray-300 mb-2"></i>
            <p class="text-sm text-gray-500 font-medium">Klik untuk upload foto</p>
            <p class="text-xs text-gray-400">JPG, PNG, WEBP (maks 2MB)</p>
          </div>
          <input type="file" name="foto" id="fotoInput" accept="image/*" class="hidden"
                 onchange="previewFoto(event)">
        </div>
      </div>

      <div class="flex justify-end gap-3 pt-2">
        <button type="button" onclick="hideModal('modalTambah')"
                class="px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">Batal</button>
        <button type="submit"
                class="px-6 py-2 bg-orange-grad text-white text-sm font-bold rounded-xl transition-all shadow-orange hover:opacity-90">
          <i class="fa-solid fa-floppy-disk mr-1.5"></i>Simpan Produk
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Tambah Kategori -->
<div id="modalKategori" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
      <h3 class="font-bold text-gray-900"><i class="fa-solid fa-tag text-orange-500 mr-2"></i>Kelola Kategori</h3>
      <button onclick="hideModal('modalKategori')" class="w-7 h-7 rounded-full bg-gray-100 hover:bg-gray-200 text-sm flex items-center justify-center">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div class="p-5">
      <!-- Daftar kategori -->
      <div class="space-y-1.5 mb-4 max-h-40 overflow-y-auto">
        <?php foreach ($kategoriList as $k): ?>
        <div class="flex items-center gap-2.5 px-3 py-2 rounded-lg bg-gray-50">
          <span class="w-3 h-3 rounded-full shrink-0" style="background:<?= htmlspecialchars($k['warna']) ?>"></span>
          <span class="text-sm font-medium flex-1"><?= htmlspecialchars($k['nama']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <!-- Form tambah -->
      <form method="POST" class="border-t border-gray-100 pt-4 space-y-3">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="tambah_kategori">
        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Kategori Baru *</label>
          <input type="text" name="nama_kategori" required placeholder="Contoh: Menu Spesial"
                 class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 transition">
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1">Warna Label</label>
          <div class="flex items-center gap-3">
            <input type="color" name="warna_kategori" value="#f97316"
                   class="w-10 h-10 rounded-lg border border-gray-200 cursor-pointer p-0.5">
            <div class="flex gap-1.5 flex-wrap">
              <?php foreach (['#f97316','#3b82f6','#a855f7','#ec4899','#14b8a6','#f59e0b','#6b7280'] as $c): ?>
              <button type="button" onclick="document.querySelector('[name=warna_kategori]').value='<?=$c?>'"
                      class="w-6 h-6 rounded-full border-2 border-white shadow-sm transition-transform hover:scale-110"
                      style="background:<?=$c?>"></button>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <button type="submit"
                class="w-full py-2.5 bg-orange-grad text-white text-sm font-bold rounded-xl transition-all hover:opacity-90 shadow-orange">
          <i class="fa-solid fa-plus mr-1.5"></i>Tambah Kategori
        </button>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Restock -->
<div id="modalRestock" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
      <h3 class="font-bold text-gray-900"><i class="fa-solid fa-rotate text-orange-500 mr-2"></i>Restock: <span id="restockNama">—</span></h3>
      <button onclick="hideModal('modalRestock')" class="w-7 h-7 rounded-full bg-gray-100 hover:bg-gray-200 text-sm flex items-center justify-center">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <form method="POST" class="p-5 space-y-3">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="restock">
      <input type="hidden" name="id_barang" id="restockId">
      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Jumlah Tambah *</label>
        <input type="number" name="jumlah" min="1" required
               class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-orange-400 transition">
      </div>
      <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Pemasok</label>
        <input type="text" name="pemasok" placeholder="Opsional"
               class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 transition">
      </div>
      <div class="flex justify-end gap-3 pt-1">
        <button type="button" onclick="hideModal('modalRestock')"
                class="px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">Batal</button>
        <button type="submit"
                class="px-5 py-2 bg-orange-grad text-white text-sm font-bold rounded-xl transition-all shadow-orange hover:opacity-90">
          <i class="fa-solid fa-check mr-1.5"></i>Konfirmasi
        </button>
      </div>
    </form>
  </div>
</div>

<style>
  .bg-orange-grad { background: linear-gradient(135deg,#f97316 0%,#ea6c0a 60%,#c2570a 100%); }
  .shadow-orange  { box-shadow: 0 4px 16px rgba(249,115,22,.35); }
</style>
<script>
function showModal(id) {
  const m = document.getElementById(id);
  if (m) { m.classList.remove('hidden'); m.classList.add('flex'); }
}
function hideModal(id) {
  const m = document.getElementById(id);
  if (m) { m.classList.add('hidden'); m.classList.remove('flex'); }
}
function bukaRestock(id, nama) {
  document.getElementById('restockId').value = id;
  document.getElementById('restockNama').textContent = nama;
  showModal('modalRestock');
}
function filterProduk(q) {
  q = q.toLowerCase();
  document.querySelectorAll('.prod-row').forEach(r => {
    r.style.display = !q || (r.dataset.search||'').includes(q) ? '' : 'none';
  });
}
function prefillSKU() {
  const sku = document.getElementById('skuInput');
  if (!sku.value) generateSKUPreview();
}
function generateSKUPreview() {
  const nama = document.getElementById('namaBarangInput').value || 'PRD';
  const prefix = nama.replace(/[^a-zA-Z]/g,'').substring(0,3).toUpperCase().padEnd(3,'X');
  const num = String(Math.floor(Math.random()*99999)+1).padStart(5,'0');
  document.getElementById('skuInput').value = prefix + '-' + num;
}
function previewFoto(e) {
  const file = e.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = function(ev) {
    document.getElementById('fotoPreviewImg').src = ev.target.result;
    document.getElementById('fotoPreview').classList.remove('hidden');
    document.getElementById('fotoPlaceholder').classList.add('hidden');
  };
  reader.readAsDataURL(file);
}
</script>