<?php
// =============================================
// KantinKu v3 — Business Logic Functions
// Database/functions.php
//
// PERBAIKAN:
//   [BUG-07] kurangiStok() menggunakan FOR UPDATE di LUAR transaksi DB.
//            FOR UPDATE hanya bekerja dalam BEGIN...COMMIT block.
//            Di luar transaksi, FOR UPDATE diabaikan → race condition.
//   [BUG-08] bayarPiutang(): FOR UPDATE dipanggil SEBELUM beginTransaction()
//            → lock tidak efektif.
//   [BUG-09] getAllBarang() menggunakan query() dengan string interpolasi $where
//            → Tidak berbahaya di sini tapi tidak konsisten, perbaiki.
//   [BUG-10] getLaporanPenjualan(): loop N+1 query untuk items.
//            → Efisiensi buruk, perbaiki dengan single JOIN query.
// =============================================
require_once __DIR__ . '/config.php';

// ─────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────
function formatRupiah(int|float $n): string {
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
}

function cleanMoney(mixed $v): int {
    return (int) preg_replace('/[^0-9]/', '', (string)$v);
}

function sanitize(string $v): string {
    return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8');
}

// ─────────────────────────────────────────────
// STOK BARANG
// ─────────────────────────────────────────────

function getAllBarang(bool $hanyaAktif = false): array {
    $pdo = getDB();
    if ($hanyaAktif) {
        // [FIX-09] Gunakan prepared statement, bukan string interpolasi
        $stmt = $pdo->prepare("SELECT * FROM stok_barang WHERE aktif = 1 ORDER BY nama_barang ASC");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("SELECT * FROM stok_barang ORDER BY nama_barang ASC");
        $stmt->execute();
    }
    return $stmt->fetchAll();
}

function getBarangById(int $id): ?array {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT * FROM stok_barang WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function tambahBarang(array $d): int|false {
    $pdo  = getDB();
    $stmt = $pdo->prepare("
        INSERT INTO stok_barang (nama_barang, tipe, stok, harga_dasar, harga_jual, foto)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $ok = $stmt->execute([
        sanitize($d['nama_barang']),
        $d['tipe']     ?? 'makanan',
        (int)   $d['stok'],
        (float) $d['harga_dasar'],
        (float) $d['harga_jual'],
        $d['foto']     ?? null,
    ]);
    if ($ok) {
        $newId = (int) $pdo->lastInsertId();
        // Catat riwayat stok masuk
        $pdo->prepare("
            INSERT INTO riwayat_stok (id_barang, nama_barang, jenis, jumlah, harga_satuan, total, dicatat_oleh)
            VALUES (?, ?, 'tambah_barang', ?, ?, ?, ?)
        ")->execute([
            $newId,
            sanitize($d['nama_barang']),
            (int)   $d['stok'],
            (float) $d['harga_dasar'],
            (int)$d['stok'] * (float)$d['harga_dasar'],
            $d['dicatat_oleh'] ?? 'sistem',
        ]);
        return $newId;
    }
    return false;
}

function editBarang(int $id, array $d): bool {
    $pdo  = getDB();
    $stmt = $pdo->prepare("
        UPDATE stok_barang
        SET nama_barang=?, tipe=?, harga_dasar=?, harga_jual=?, aktif=?, updated_at=NOW()
        WHERE id=?
    ");
    return $stmt->execute([
        sanitize($d['nama_barang']),
        $d['tipe'],
        (float) $d['harga_dasar'],
        (float) $d['harga_jual'],
        (int)  ($d['aktif'] ?? 1),
        $id,
    ]);
}

function restockBarang(int $id, int $jumlah, string $pemasok, string $oleh): bool {
    $pdo = getDB();
    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE stok_barang SET stok = stok + ?, updated_at = NOW() WHERE id = ?")
            ->execute([$jumlah, $id]);

        $barang = getBarangById($id);
        $pdo->prepare("
            INSERT INTO riwayat_stok (id_barang, nama_barang, jenis, jumlah, harga_satuan, total, pemasok, dicatat_oleh)
            VALUES (?, ?, 'restock', ?, ?, ?, ?, ?)
        ")->execute([
            $id,
            $barang['nama_barang'] ?? '—',
            $jumlah,
            (float)($barang['harga_dasar'] ?? 0),
            $jumlah * (float)($barang['harga_dasar'] ?? 0),
            sanitize($pemasok),
            sanitize($oleh),
        ]);

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('[stok] restockBarang: ' . $e->getMessage());
        return false;
    }
}

// [FIX-07] kurangiStok() sekarang WAJIB dipanggil dalam transaksi yang sudah aktif.
// Fungsi tidak membuat transaksi sendiri karena dipanggil dari simpanPenjualan()
// yang sudah punya transaksi. FOR UPDATE HANYA efektif dalam transaksi aktif.
function kurangiStok(int $idBarang, int $qty, PDO $pdo): bool {
    $stmt = $pdo->prepare("SELECT stok FROM stok_barang WHERE id = ? FOR UPDATE");
    $stmt->execute([$idBarang]);
    $row = $stmt->fetch();
    if (!$row || $row['stok'] < $qty) return false;

    $pdo->prepare("UPDATE stok_barang SET stok = stok - ?, updated_at = NOW() WHERE id = ?")
        ->execute([$qty, $idBarang]);
    return true;
}

// ─────────────────────────────────────────────
// PENJUALAN — Logika Kasir
// ─────────────────────────────────────────────
function hitungTotal(array $items, float $diskonPersen = 0, float $pajakPersen = 0): array {
    $subtotal = 0;
    foreach ($items as $item) {
        // SECURITY: Harga SELALU dari DB, tidak dari request
        $barang = getBarangById((int)($item['id_barang'] ?? 0));
        if (!$barang) continue;
        $subtotal += $barang['harga_jual'] * max(1, (int)$item['qty']);
    }
    $diskon = (int) round($subtotal * ($diskonPersen / 100));
    $dasar  = $subtotal - $diskon;
    $pajak  = (int) round($dasar    * ($pajakPersen  / 100));
    $total  = $dasar + $pajak;
    return compact('subtotal', 'diskon', 'pajak', 'total');
}

function simpanPenjualan(array $data, int $idKasir): array {
    $pdo = getDB();

    if (empty($data['items'])) {
        return ['status' => 'error', 'message' => 'Keranjang kosong'];
    }

    // Validasi qty tidak negatif/nol
    foreach ($data['items'] as $item) {
        if ((int)($item['qty'] ?? 0) <= 0) {
            return ['status' => 'error', 'message' => 'Qty item tidak valid'];
        }
    }

    $hit = hitungTotal(
        $data['items'],
        (float)($data['diskon_persen'] ?? 0),
        (float)($data['pajak_persen']  ?? 0)
    );

    if ($hit['total'] <= 0) {
        return ['status' => 'error', 'message' => 'Total tidak valid'];
    }

    $nama      = sanitize($data['nama_pembeli'] ?? 'Umum');
    $metode    = in_array($data['metode'] ?? '', ['Cash', 'Piutang', 'Transfer'])
                 ? $data['metode'] : 'Cash';
    $status    = $metode === 'Piutang' ? 'Piutang' : 'Lunas';
    $uangMasuk = $metode === 'Cash' ? cleanMoney($data['uang_masuk'] ?? 0) : $hit['total'];
    $kembalian = max(0, $uangMasuk - $hit['total']);

    if ($metode === 'Cash' && $uangMasuk < $hit['total']) {
        return ['status' => 'error', 'message' => 'Uang tidak cukup'];
    }

    $pdo->beginTransaction();
    try {
        // 1. Insert header
        $stmt = $pdo->prepare("
            INSERT INTO penjualan
              (id_kasir, nama_pembeli, subtotal, diskon, pajak, total,
               metode, status, uang_masuk, kembalian, keterangan)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([
            $idKasir, $nama,
            $hit['subtotal'], $hit['diskon'], $hit['pajak'], $hit['total'],
            $metode, $status, $uangMasuk, $kembalian,
            sanitize($data['keterangan'] ?? ''),
        ]);
        $idPenjualan = (int) $pdo->lastInsertId();

        // 2. Insert detail & kurangi stok
        $stmtDetail = $pdo->prepare("
            INSERT INTO detail_penjualan (id_penjualan, id_barang, nama_item, qty, harga)
            VALUES (?,?,?,?,?)
        ");
        foreach ($data['items'] as $item) {
            $barang = getBarangById((int)($item['id_barang'] ?? 0));
            if (!$barang) continue;

            $stmtDetail->execute([
                $idPenjualan,
                $barang['id'],
                $barang['nama_barang'],
                (int) $item['qty'],
                $barang['harga_jual'],
            ]);

            // [FIX-07] Kirim $pdo ke kurangiStok agar FOR UPDATE efektif
            if (!kurangiStok($barang['id'], (int)$item['qty'], $pdo)) {
                // Stok tidak cukup → rollback seluruh transaksi
                $pdo->rollBack();
                return ['status' => 'error', 'message' => "Stok '{$barang['nama_barang']}' tidak mencukupi"];
            }
        }

        // 3. Jika Piutang → buat record
        if ($metode === 'Piutang') {
            $pdo->prepare("
                INSERT INTO piutang (id_penjualan, nama_pembeli, total_hutang, sisa_hutang, keterangan)
                VALUES (?,?,?,?,?)
            ")->execute([
                $idPenjualan, $nama,
                $hit['total'], $hit['total'],
                sanitize($data['keterangan'] ?? ''),
            ]);
        }

        $pdo->commit();

        return [
            'status'       => 'success',
            'id_penjualan' => $idPenjualan,
            'subtotal'     => $hit['subtotal'],
            'diskon'       => $hit['diskon'],
            'pajak'        => $hit['pajak'],
            'total'        => $hit['total'],
            'kembalian'    => $kembalian,
            'nama'         => $nama,
            'metode'       => $metode,
        ];

    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('[penjualan] simpanPenjualan: ' . $e->getMessage());
        return ['status' => 'error', 'message' => 'Gagal menyimpan transaksi'];
    }
}

function getStruk(int $idPenjualan): ?array {
    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT p.*, u.namalengkap AS kasir_nama
        FROM penjualan p
        LEFT JOIN users u ON u.id = p.id_kasir
        WHERE p.id = ?
    ");
    $stmt->execute([$idPenjualan]);
    $trx = $stmt->fetch();
    if (!$trx) return null;

    $stmt2 = $pdo->prepare("SELECT * FROM detail_penjualan WHERE id_penjualan = ?");
    $stmt2->execute([$idPenjualan]);
    $trx['items'] = $stmt2->fetchAll();
    return $trx;
}

// ─────────────────────────────────────────────
// LAPORAN
// [FIX-10] Ganti loop N+1 query dengan satu JOIN query
// ─────────────────────────────────────────────
function getLaporanPenjualan(?string $dari = null, ?string $sampai = null, ?string $status = null): array {
    $pdo    = getDB();
    $where  = ['1=1'];
    $params = [];

    if ($dari)   { $where[] = 'DATE(p.tanggal) >= ?'; $params[] = $dari;   }
    if ($sampai) { $where[] = 'DATE(p.tanggal) <= ?'; $params[] = $sampai; }
    if ($status) { $where[] = 'p.status = ?';         $params[] = $status; }

    // [FIX-10] Satu query JOIN — ambil header + items sekaligus
    $sql = "
        SELECT
            p.id, p.id_kasir, p.nama_pembeli, p.tanggal,
            p.subtotal, p.diskon, p.pajak, p.total,
            p.metode, p.status, p.uang_masuk, p.kembalian, p.keterangan,
            u.namalengkap AS kasir_nama,
            dp.id          AS item_id,
            dp.id_barang,
            dp.nama_item,
            dp.qty,
            dp.harga,
            dp.subtotal    AS item_subtotal
        FROM penjualan p
        LEFT JOIN users u          ON u.id = p.id_kasir
        LEFT JOIN detail_penjualan dp ON dp.id_penjualan = p.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY p.tanggal DESC, dp.id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rawRows = $stmt->fetchAll();

    // Susun ulang: group items per transaksi
    $result = [];
    foreach ($rawRows as $row) {
        $id = $row['id'];
        if (!isset($result[$id])) {
            $result[$id] = [
                'id'          => $row['id'],
                'id_kasir'    => $row['id_kasir'],
                'nama_pembeli'=> $row['nama_pembeli'],
                'tanggal'     => $row['tanggal'],
                'subtotal'    => $row['subtotal'],
                'diskon'      => $row['diskon'],
                'pajak'       => $row['pajak'],
                'total'       => $row['total'],
                'metode'      => $row['metode'],
                'status'      => $row['status'],
                'uang_masuk'  => $row['uang_masuk'],
                'kembalian'   => $row['kembalian'],
                'keterangan'  => $row['keterangan'],
                'kasir_nama'  => $row['kasir_nama'],
                'items'       => [],
            ];
        }
        if ($row['item_id']) {
            $result[$id]['items'][] = [
                'id'       => $row['item_id'],
                'id_barang'=> $row['id_barang'],
                'nama_item'=> $row['nama_item'],
                'qty'      => $row['qty'],
                'harga'    => $row['harga'],
                'subtotal' => $row['item_subtotal'],
            ];
        }
    }

    return array_values($result);
}

function getStatsDashboard(): array {
    $pdo  = getDB();
    $hari = date('Y-m-d');
    $stats = [];

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) FROM penjualan WHERE DATE(tanggal)=? AND status='Lunas'");
    $stmt->execute([$hari]);
    $stats['pendapatan_hari'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM penjualan WHERE DATE(tanggal)=?");
    $stmt->execute([$hari]);
    $stats['trx_hari'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COALESCE(SUM(sisa_hutang),0) FROM piutang WHERE status='belum_lunas'");
    $stats['total_piutang'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM stok_barang WHERE stok <= 5 AND aktif=1");
    $stats['stok_kritis'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("
        SELECT DATE(tanggal) AS tgl, SUM(total) AS omzet, COUNT(*) AS jml
        FROM penjualan
        WHERE tanggal >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status != 'Batal'
        GROUP BY DATE(tanggal)
        ORDER BY tgl ASC
    ");
    $stats['chart_7hari'] = $stmt->fetchAll();

    return $stats;
}

// ─────────────────────────────────────────────
// PIUTANG
// [FIX-08] beginTransaction() HARUS sebelum FOR UPDATE
// ─────────────────────────────────────────────
function getAllPiutang(string $status = ''): array {
    $pdo  = getDB();
    if ($status) {
        $stmt = $pdo->prepare("SELECT * FROM piutang WHERE status = ? ORDER BY tanggal DESC");
        $stmt->execute([$status]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM piutang ORDER BY tanggal DESC");
        $stmt->execute();
    }
    return $stmt->fetchAll();
}

function bayarPiutang(int $idPiutang, int $jumlah, string $dibayarOleh): array {
    $pdo = getDB();

    // [FIX-08] beginTransaction() DULU, baru FOR UPDATE
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT * FROM piutang WHERE id = ? FOR UPDATE");
        $stmt->execute([$idPiutang]);
        $piutang = $stmt->fetch();

        if (!$piutang)                          return tap($pdo->rollBack(), fn() => ['status'=>'error','message'=>'Data piutang tidak ditemukan']);
        if ($piutang['status'] === 'lunas')     { $pdo->rollBack(); return ['status'=>'error','message'=>'Piutang sudah lunas']; }
        if ($jumlah <= 0)                       { $pdo->rollBack(); return ['status'=>'error','message'=>'Jumlah bayar tidak valid']; }
        if ($jumlah > (float)$piutang['sisa_hutang']) {
            $pdo->rollBack();
            return ['status'=>'error','message'=>'Jumlah melebihi sisa hutang'];
        }

        $sisaSebelum = (float)$piutang['sisa_hutang'];
        $sisaSesudah = max(0, $sisaSebelum - $jumlah);
        $newStatus   = $sisaSesudah <= 0 ? 'lunas' : 'belum_lunas';

        $pdo->prepare("UPDATE piutang SET sisa_hutang=?, status=?, updated_at=NOW() WHERE id=?")
            ->execute([$sisaSesudah, $newStatus, $idPiutang]);

        $pdo->prepare("
            INSERT INTO riwayat_bayar_piutang
              (id_piutang, jumlah_bayar, sisa_sebelum, sisa_sesudah, dibayar_oleh)
            VALUES (?,?,?,?,?)
        ")->execute([$idPiutang, $jumlah, $sisaSebelum, $sisaSesudah, sanitize($dibayarOleh)]);

        $pdo->commit();
        return [
            'status'       => 'success',
            'sisa_sesudah' => $sisaSesudah,
            'lunas'        => $newStatus === 'lunas',
        ];
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('[piutang] bayarPiutang: ' . $e->getMessage());
        return ['status'=>'error','message'=>'Gagal memproses pembayaran'];
    }
}

// Helper — mencegah closure void di atas
function tap(mixed $value, callable $fn): mixed { $fn(); return $value; }
