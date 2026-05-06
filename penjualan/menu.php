<?php
// penjualan/menu.php
// SECURITY: halaman ini sudah di-guard oleh index.php (requireLogin)
$allBarang = getAllBarang(true);  // hanya aktif
?>

<!-- ================================================
     KASIR / POS PAGE
     Fitur: Instant search, cart real-time,
            filter kategori, keyboard shortcut
     ================================================ -->

<div class="flex flex-col lg:flex-row gap-4 h-[calc(100vh-7rem)]">

  <!-- ── LEFT: Menu List ── -->
  <div class="flex-1 flex flex-col min-h-0 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

    <!-- Search + Filter Bar -->
    <div class="p-4 border-b border-gray-100 shrink-0">
      <div class="relative mb-3">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
        </svg>
        <input type="text" id="searchMenu" placeholder="Cari menu... (tekan / untuk fokus)"
               autocomplete="off"
               class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm
                      focus:outline-none focus:ring-2 focus:ring-brand-400 focus:border-transparent
                      transition placeholder-gray-400">
      </div>

      <!-- Category chips -->
      <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-thin">
        <?php
        $categories = ['Semua' => ''] + array_unique(array_column($allBarang, 'tipe'));
        foreach ($categories as $cat => $val):
          $val = $cat === 'Semua' ? '' : $val ?: $cat;
        ?>
        <button onclick="setKategori(this, '<?= htmlspecialchars($val) ?>')"
                data-kategori="<?= htmlspecialchars($val) ?>"
                class="chip-btn shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold border transition-all duration-150
                       <?= $cat === 'Semua' ? 'bg-brand-600 text-white border-brand-600' : 'bg-white text-gray-500 border-gray-200 hover:border-brand-400 hover:text-brand-600' ?>">
          <?= ucfirst($cat) ?>
        </button>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Menu Grid (scrollable) -->
    <div class="flex-1 overflow-y-auto p-4" id="menuGrid">
      <?php if (empty($allBarang)): ?>
        <div class="flex flex-col items-center justify-center h-full text-gray-400 gap-2">
          <span class="text-5xl">📦</span>
          <p class="text-sm font-medium">Belum ada menu tersedia</p>
        </div>
      <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3" id="menuItems">
          <?php foreach ($allBarang as $idx => $b):
            $stokOk = $b['stok'] > 0;
          ?>
          <div class="menu-card group relative bg-white rounded-xl border border-gray-100 shadow-sm
                      hover:shadow-md hover:border-brand-200 transition-all duration-150 overflow-hidden
                      <?= !$stokOk ? 'opacity-60' : '' ?>"
               data-nama="<?= strtolower(htmlspecialchars($b['nama_barang'])) ?>"
               data-kategori="<?= htmlspecialchars($b['tipe']) ?>">

            <!-- Stok badge -->
            <?php if ($b['stok'] <= 5 && $b['stok'] > 0): ?>
            <span class="absolute top-2 right-2 bg-orange-100 text-orange-600 text-[10px] font-bold px-1.5 py-0.5 rounded-full z-10">
              Sisa <?= $b['stok'] ?>
            </span>
            <?php elseif (!$stokOk): ?>
            <span class="absolute top-2 right-2 bg-red-100 text-red-600 text-[10px] font-bold px-1.5 py-0.5 rounded-full z-10">
              Habis
            </span>
            <?php endif; ?>

            <!-- Foto / Placeholder -->
            <div class="h-28 bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center overflow-hidden">
              <?php if (!empty($b['foto']) && file_exists(__DIR__.'/../public/uploads/'.$b['foto'])): ?>
                <img src="/public/uploads/<?= $b['foto'] ?>" alt="<?= htmlspecialchars($b['nama_barang']) ?>"
                     class="w-full h-full object-cover">
              <?php else: ?>
                <span class="text-5xl select-none">
                  <?= ['makanan'=>'🍛','minuman'=>'🥤','snack'=>'🍿'][$b['tipe']] ?? '🍽️' ?>
                </span>
              <?php endif; ?>
            </div>

            <div class="p-3">
              <p class="font-semibold text-sm text-gray-900 leading-tight truncate">
                <?= htmlspecialchars($b['nama_barang']) ?>
              </p>
              <p class="text-brand-600 font-bold text-sm font-mono mt-0.5">
                Rp <?= number_format($b['harga_jual'], 0, ',', '.') ?>
              </p>

              <button
                onclick="addToCart(<?= $b['id'] ?>, '<?= htmlspecialchars($b['nama_barang'], ENT_QUOTES) ?>', <?= $b['harga_jual'] ?>, <?= $b['stok'] ?>)"
                <?= !$stokOk ? 'disabled' : '' ?>
                class="mt-2 w-full py-2 rounded-lg text-xs font-bold transition-all duration-150
                       <?= $stokOk
                           ? 'bg-brand-600 hover:bg-brand-700 active:scale-95 text-white shadow-sm shadow-brand-600/30'
                           : 'bg-gray-100 text-gray-400 cursor-not-allowed' ?>">
                <?= $stokOk ? '+ Tambah' : 'Stok Habis' ?>
              </button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Empty search state -->
      <div id="emptySearch" class="hidden flex flex-col items-center justify-center py-16 text-gray-400 gap-2">
        <span class="text-4xl">🔍</span>
        <p class="text-sm font-medium">Tidak ada menu yang cocok</p>
      </div>
    </div>
  </div>

  <!-- ── RIGHT: Cart ── -->
  <div class="w-full lg:w-80 xl:w-96 flex flex-col bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden shrink-0">

    <div class="flex items-center justify-between px-4 py-3.5 border-b border-gray-100 shrink-0">
      <h2 class="font-bold text-gray-900 text-sm flex items-center gap-2">
        🛒 Keranjang
        <span id="cartCount" class="bg-brand-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full">0</span>
      </h2>
      <button onclick="clearCart()" class="text-xs text-red-500 hover:text-red-700 font-medium transition-colors">Kosongkan</button>
    </div>

    <!-- Cart items (scrollable) -->
    <div class="flex-1 overflow-y-auto" id="cartContainer">
      <div id="cartEmpty" class="flex flex-col items-center justify-center h-full py-12 text-gray-300 gap-2">
        <span class="text-5xl">🛒</span>
        <p class="text-sm font-medium">Keranjang kosong</p>
        <p class="text-xs">Pilih menu untuk memulai</p>
      </div>
      <div id="cartList" class="divide-y divide-gray-50"></div>
    </div>

    <!-- Summary -->
    <div class="border-t border-gray-100 px-4 py-3 bg-gray-50/50 shrink-0 space-y-2">
      <!-- Diskon & Pajak inputs -->
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="text-xs text-gray-500 font-medium">Diskon (%)</label>
          <input type="number" id="diskonInput" min="0" max="100" value="0"
                 oninput="recalc()"
                 class="w-full mt-0.5 px-2.5 py-1.5 rounded-lg border border-gray-200 text-sm text-center
                        focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
        </div>
        <div>
          <label class="text-xs text-gray-500 font-medium">Pajak (%)</label>
          <input type="number" id="pajakInput" min="0" max="100" value="0"
                 oninput="recalc()"
                 class="w-full mt-0.5 px-2.5 py-1.5 rounded-lg border border-gray-200 text-sm text-center
                        focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
        </div>
      </div>

      <div class="space-y-1 text-sm">
        <div class="flex justify-between text-gray-500">
          <span>Subtotal</span>
          <span id="subtotalDisplay" class="font-mono">Rp 0</span>
        </div>
        <div class="flex justify-between text-orange-600" id="diskonRow">
          <span>Diskon</span>
          <span id="diskonDisplay" class="font-mono">- Rp 0</span>
        </div>
        <div class="flex justify-between text-blue-600" id="pajakRow">
          <span>Pajak</span>
          <span id="pajakDisplay" class="font-mono">+ Rp 0</span>
        </div>
        <div class="flex justify-between text-gray-900 font-bold text-base pt-1 border-t border-gray-200">
          <span>TOTAL</span>
          <span id="totalDisplay" class="font-mono text-brand-600">Rp 0</span>
        </div>
      </div>

      <button onclick="prosesCheckout()"
              id="checkoutBtn" disabled
              class="w-full py-3 rounded-xl font-bold text-sm transition-all duration-150
                     bg-brand-600 hover:bg-brand-700 active:scale-[0.98] text-white
                     shadow-lg shadow-brand-600/30
                     disabled:bg-gray-200 disabled:text-gray-400 disabled:shadow-none disabled:cursor-not-allowed">
        Lanjut ke Pembayaran →
      </button>
    </div>
  </div>

</div>

<!-- Pembayaran Modal -->
<div id="modalPembayaran"
     class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden
              transform transition-all duration-200">

    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
      <h3 class="font-bold text-gray-900">Proses Pembayaran</h3>
      <button onclick="tutupModal()" class="w-7 h-7 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-sm transition-colors">✕</button>
    </div>

    <div class="px-6 py-5 space-y-4">
      <!-- Total display -->
      <div class="bg-brand-50 rounded-xl p-4 text-center">
        <p class="text-xs text-gray-500 font-medium mb-1">Total Pembayaran</p>
        <p id="modalTotal" class="text-3xl font-extrabold text-brand-700 font-mono">Rp 0</p>
      </div>

      <!-- Form -->
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1">Nama Pelanggan</label>
          <input type="text" id="namaPelanggan" placeholder="Umum"
                 class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm
                        focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1">Metode Bayar</label>
          <select id="metodePembayaran" onchange="onMetodeChange()"
                  class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm
                         focus:outline-none focus:ring-2 focus:ring-brand-400 transition bg-white">
            <option value="Cash">Cash</option>
            <option value="Transfer">Transfer</option>
            <option value="Piutang">Piutang (Hutang)</option>
          </select>
        </div>
      </div>

      <!-- Uang diterima (Cash only) -->
      <div id="cashSection">
        <label class="block text-xs font-semibold text-gray-700 mb-1">Uang Diterima</label>
        <input type="number" id="uangDiterima" placeholder="0" oninput="hitungKembalian()"
               class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm font-mono
                      focus:outline-none focus:ring-2 focus:ring-brand-400 transition">
        <!-- Quick buttons -->
        <div class="flex gap-2 mt-2 flex-wrap">
          <button onclick="setUangCepat(0)" class="px-2.5 py-1.5 bg-gray-100 hover:bg-brand-100 text-xs font-semibold rounded-lg transition-colors text-gray-700">Pas</button>
          <button onclick="setUangCepat(20000)"  class="px-2.5 py-1.5 bg-gray-100 hover:bg-brand-100 text-xs font-semibold rounded-lg transition-colors text-gray-700">20rb</button>
          <button onclick="setUangCepat(50000)"  class="px-2.5 py-1.5 bg-gray-100 hover:bg-brand-100 text-xs font-semibold rounded-lg transition-colors text-gray-700">50rb</button>
          <button onclick="setUangCepat(100000)" class="px-2.5 py-1.5 bg-gray-100 hover:bg-brand-100 text-xs font-semibold rounded-lg transition-colors text-gray-700">100rb</button>
        </div>
        <!-- Kembalian -->
        <div id="kembalianBox" class="hidden mt-2 p-3 rounded-xl bg-green-50 border border-green-200 text-center">
          <p class="text-xs text-green-600 font-medium">Kembalian</p>
          <p id="kembalianDisplay" class="text-xl font-extrabold text-green-700 font-mono">Rp 0</p>
        </div>
      </div>

      <div>
        <label class="block text-xs font-semibold text-gray-700 mb-1">Keterangan</label>
        <textarea id="keteranganTrx" rows="2" placeholder="Opsional..."
                  class="w-full px-3 py-2.5 rounded-xl border border-gray-200 text-sm
                         focus:outline-none focus:ring-2 focus:ring-brand-400 transition resize-none"></textarea>
      </div>
    </div>

    <div class="flex gap-3 px-6 pb-6">
      <button onclick="tutupModal()"
              class="flex-1 py-3 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700
                     hover:bg-gray-50 transition-colors">
        Batal
      </button>
      <button onclick="konfirmasiTransaksi()" id="btnKonfirmasi"
              class="flex-2 flex-1 py-3 rounded-xl bg-brand-600 hover:bg-brand-700 active:scale-[0.98]
                     text-white text-sm font-bold transition-all shadow-lg shadow-brand-600/30">
        ✓ Konfirmasi & Simpan
      </button>
    </div>
  </div>
</div>

<!-- Struk Modal (print-friendly) -->
<div id="modalStruk"
     class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b no-print">
      <h3 class="font-bold text-gray-900 text-sm">Struk Pembayaran</h3>
      <div class="flex gap-2">
        <button onclick="window.print()" class="px-3 py-1.5 bg-brand-600 text-white text-xs font-semibold rounded-lg hover:bg-brand-700 transition-colors">🖨 Print</button>
        <button onclick="transaksiSelesai()" class="px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-200 transition-colors">✓ Selesai</button>
      </div>
    </div>
    <div id="strukContent" class="p-5 font-mono text-sm"></div>
  </div>
</div>

<script>
// ================================================
// KASIR / POS JAVASCRIPT
// ================================================

// ── State ──
let cart    = {};    // { id: { id, nama, harga, qty, stok } }
let totalNominal = 0;

// ── Search & Filter ──
const searchInput = document.getElementById('searchMenu');
searchInput.addEventListener('input', filterMenu);
document.addEventListener('keydown', e => {
  if (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
    e.preventDefault(); searchInput.focus();
  }
  if (e.key === 'Escape') { tutupModal(); }
});

let activeKategori = '';
function setKategori(btn, kat) {
  activeKategori = kat;
  document.querySelectorAll('.chip-btn').forEach(b => {
    b.className = b.className.replace(/bg-brand-600 text-white border-brand-600/g, 'bg-white text-gray-500 border-gray-200');
  });
  btn.className += ' bg-brand-600 text-white border-brand-600';
  filterMenu();
}

function filterMenu() {
  const q    = searchInput.value.toLowerCase().trim();
  const cards= document.querySelectorAll('.menu-card');
  let visible = 0;

  cards.forEach(card => {
    const nama = card.dataset.nama || '';
    const kat  = card.dataset.kategori || '';
    const matchQ   = !q   || nama.includes(q);
    const matchKat = !activeKategori || kat === activeKategori;
    const show = matchQ && matchKat;
    card.style.display = show ? '' : 'none';
    if (show) visible++;
  });

  document.getElementById('emptySearch').style.display = visible === 0 ? 'flex' : 'none';
}

// ── Cart ──
function addToCart(id, nama, harga, stok) {
  if (cart[id]) {
    if (cart[id].qty >= stok) { showToast('Stok tidak mencukupi', 'error'); return; }
    cart[id].qty++;
  } else {
    cart[id] = { id, nama, harga, qty: 1, stok };
  }
  renderCart();
  showToast(`${nama} ditambahkan`, 'success');
}

function updateQty(id, delta) {
  if (!cart[id]) return;
  cart[id].qty += delta;
  if (cart[id].qty > cart[id].stok) { cart[id].qty = cart[id].stok; showToast('Maksimum stok tercapai', 'warning'); }
  if (cart[id].qty <= 0) { delete cart[id]; }
  renderCart();
}

function removeFromCart(id) {
  delete cart[id];
  renderCart();
}

function clearCart() {
  cart = {};
  renderCart();
}

function renderCart() {
  const list  = document.getElementById('cartList');
  const empty = document.getElementById('cartEmpty');
  const items = Object.values(cart);

  document.getElementById('cartCount').textContent = items.reduce((s,i)=>s+i.qty, 0);
  document.getElementById('cartBadge').textContent = items.reduce((s,i)=>s+i.qty, 0);
  document.getElementById('cartBadge').classList.toggle('hidden', items.length === 0);

  if (items.length === 0) {
    list.innerHTML = '';
    empty.style.display = 'flex';
    document.getElementById('checkoutBtn').disabled = true;
    recalc();
    return;
  }

  empty.style.display = 'none';
  list.innerHTML = items.map(item => `
    <div class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 transition-colors">
      <div class="flex-1 min-w-0">
        <p class="text-sm font-semibold text-gray-900 truncate">${item.nama}</p>
        <p class="text-xs text-brand-600 font-mono">${fmtRp(item.harga)}</p>
      </div>
      <div class="flex items-center gap-2 shrink-0">
        <button onclick="updateQty(${item.id},-1)"
                class="w-6 h-6 rounded-full bg-gray-100 hover:bg-red-100 hover:text-red-600 text-xs font-bold flex items-center justify-center transition-colors">−</button>
        <span class="text-sm font-bold w-6 text-center font-mono">${item.qty}</span>
        <button onclick="updateQty(${item.id},+1)"
                class="w-6 h-6 rounded-full bg-gray-100 hover:bg-brand-100 hover:text-brand-600 text-xs font-bold flex items-center justify-center transition-colors">+</button>
      </div>
      <p class="text-sm font-bold text-gray-900 font-mono w-20 text-right shrink-0">${fmtRp(item.harga * item.qty)}</p>
      <button onclick="removeFromCart(${item.id})" class="text-gray-300 hover:text-red-500 text-sm ml-1 transition-colors">✕</button>
    </div>
  `).join('');

  document.getElementById('checkoutBtn').disabled = false;
  recalc();
}

function recalc() {
  const items    = Object.values(cart);
  const subtotal = items.reduce((s, i) => s + i.harga * i.qty, 0);
  const diskon   = Math.round(subtotal * (parseFloat(document.getElementById('diskonInput').value||0) / 100));
  const base     = subtotal - diskon;
  const pajak    = Math.round(base    * (parseFloat(document.getElementById('pajakInput').value||0)  / 100));
  totalNominal   = base + pajak;

  document.getElementById('subtotalDisplay').textContent = fmtRp(subtotal);
  document.getElementById('diskonDisplay').textContent   = '- ' + fmtRp(diskon);
  document.getElementById('pajakDisplay').textContent    = '+ ' + fmtRp(pajak);
  document.getElementById('totalDisplay').textContent    = fmtRp(totalNominal);
  document.getElementById('modalTotal').textContent      = fmtRp(totalNominal);
}

// ── Checkout ──
function prosesCheckout() {
  if (!Object.keys(cart).length) return;
  recalc();
  document.getElementById('modalPembayaran').style.display = 'flex';
  document.getElementById('namaPelanggan').focus();
}

function tutupModal() {
  document.getElementById('modalPembayaran').style.display = 'none';
}

function onMetodeChange() {
  const metode = document.getElementById('metodePembayaran').value;
  document.getElementById('cashSection').style.display = metode === 'Cash' ? 'block' : 'none';
}

function setUangCepat(nominal) {
  const val = nominal === 0 ? totalNominal : nominal;
  document.getElementById('uangDiterima').value = val;
  hitungKembalian();
}

function hitungKembalian() {
  const uang     = parseFloat(document.getElementById('uangDiterima').value || 0);
  const kemb     = uang - totalNominal;
  const box      = document.getElementById('kembalianBox');
  const display  = document.getElementById('kembalianDisplay');
  if (uang > 0) {
    box.classList.remove('hidden');
    display.textContent = fmtRp(Math.max(0, kemb));
    display.className   = display.className.replace(/text-(red|green)-700/, kemb >= 0 ? 'text-green-700' : 'text-red-700');
  } else {
    box.classList.add('hidden');
  }
}

async function konfirmasiTransaksi() {
  const metode  = document.getElementById('metodePembayaran').value;
  const uang    = parseFloat(document.getElementById('uangDiterima').value || 0);

  if (metode === 'Cash' && uang < totalNominal) {
    showToast('Uang tidak mencukupi', 'error'); return;
  }

  const btn = document.getElementById('btnKonfirmasi');
  btn.disabled = true;
  btn.textContent = '⏳ Menyimpan...';

  try {
    const payload = {
      items:          Object.values(cart).map(i => ({ id_barang: i.id, qty: i.qty })),
      nama_pembeli:   document.getElementById('namaPelanggan').value || 'Umum',
      metode,
      diskon_persen:  parseFloat(document.getElementById('diskonInput').value || 0),
      pajak_persen:   parseFloat(document.getElementById('pajakInput').value || 0),
      uang_masuk:     uang,
      keterangan:     document.getElementById('keteranganTrx').value,
      csrf_token:     '<?= csrfToken() ?>',
    };

    const res  = await fetch('/api/simpan_penjualan.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '<?= csrfToken() ?>' },
      body: JSON.stringify(payload),
    });
    const data = await res.json();

    if (data.status === 'success') {
      tutupModal();
      tampilStruk(data);
    } else {
      showToast(data.message || 'Gagal menyimpan', 'error');
    }
  } catch (e) {
    showToast('Koneksi gagal', 'error');
    console.error(e);
  } finally {
    btn.disabled = false;
    btn.textContent = '✓ Konfirmasi & Simpan';
  }
}

function tampilStruk(data) {
  const items    = Object.values(cart);
  const now      = new Date().toLocaleString('id-ID');
  const kasir    = '<?= htmlspecialchars($_SESSION["namalengkap"] ?? "—") ?>';
  let   itemsStr = items.map(i =>
    `<div class="flex justify-between"><span>${i.nama} x${i.qty}</span><span>${fmtRp(i.harga*i.qty)}</span></div>`
  ).join('');

  document.getElementById('strukContent').innerHTML = `
    <div class="text-center mb-3">
      <p class="font-bold text-base">KANTIN UAM</p>
      <p class="text-xs text-gray-500">${now}</p>
      <p class="text-xs text-gray-500">Kasir: ${kasir} | #${data.id_penjualan}</p>
    </div>
    <div class="border-t border-dashed border-gray-300 my-2 pt-2 space-y-1 text-xs">${itemsStr}</div>
    <div class="border-t border-dashed border-gray-300 my-2 pt-2 space-y-1 text-xs">
      <div class="flex justify-between"><span>Subtotal</span><span>${fmtRp(data.subtotal)}</span></div>
      ${data.diskon > 0 ? `<div class="flex justify-between text-orange-600"><span>Diskon</span><span>-${fmtRp(data.diskon)}</span></div>` : ''}
      ${data.pajak  > 0 ? `<div class="flex justify-between text-blue-600"><span>Pajak</span><span>+${fmtRp(data.pajak)}</span></div>` : ''}
      <div class="flex justify-between font-bold text-sm pt-1 border-t border-gray-200">
        <span>TOTAL</span><span>${fmtRp(data.total)}</span>
      </div>
      ${data.metode === 'Cash' ? `
      <div class="flex justify-between"><span>Uang</span><span>${fmtRp(data.uang_masuk)}</span></div>
      <div class="flex justify-between text-green-600 font-bold"><span>Kembalian</span><span>${fmtRp(data.kembalian)}</span></div>
      ` : `<div class="text-center text-orange-600 font-bold">Metode: ${data.metode}</div>`}
    </div>
    <div class="text-center mt-3 text-xs text-gray-400">Terima kasih! 🙏</div>
  `;

  document.getElementById('modalStruk').style.display = 'flex';
}

function transaksiSelesai() {
  clearCart();
  document.getElementById('modalStruk').style.display = 'none';
  document.getElementById('namaPelanggan').value = '';
  document.getElementById('keteranganTrx').value = '';
  document.getElementById('uangDiterima').value  = '';
  document.getElementById('metodePembayaran').value = 'Cash';
  document.getElementById('diskonInput').value  = '0';
  document.getElementById('pajakInput').value   = '0';
  document.getElementById('kembalianBox').classList.add('hidden');
  showToast('Transaksi selesai! Keranjang direset.', 'success');
}

// ── Utils ──
function fmtRp(n) {
  return 'Rp ' + Math.round(n).toLocaleString('id-ID');
}
</script>