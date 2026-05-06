<?php
require_once __DIR__ . '/config.php';

function formatRupiah(int|float $n): string {
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
}

function cleanMoney(mixed $v): int {
    // Terima "10.000" atau "10000" atau 10000
    return (int) preg_replace('/[^0-9]/', '', (string)$v);
}

function sanitize(string $v): string {
    return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8');
}

function getAllBarang(bool $hanyaAktif = false): array {
    $pdo = getDB();
    $where = $hanyaAktif ? 'WHERE aktif = 1' : '';
    $stmt  = $pdo->query("SELECT * FROM stok_barang $where ORDER BY nama_barang ASC");
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
        $d['nama_barang'],
        $d['tipe']        ?? 'makanan',
        (int)   $d['stok'],
        (float) $d['harga_dasar'],
        (float) $d['harga_jual'],
        $d['foto']        ?? null,
    ]);
    return $ok ? (int) $pdo->lastInsertId() : false;
}

function editBarang(int $id, array $d): bool {
    $pdo  = getDB();
    $stmt = $pdo->prepare("
        UPDATE stok_barang
        SET nama_barang=?, tipe=?, harga_dasar=?, harga_jual=?, aktif=?
        WHERE id=?
    ");
    return $stmt->execute([
        $d['nama_barang'],
        $d['tipe'],
        (float) $d['harga_dasar'],
        (float) $d['harga_jual'],
        (int)   ($d['aktif'] ?? 1),
        $id,
    ]);
}

function restockBarang(int $id, int $jumlah, string $pemasok, string $oleh): bool {
    $pdo = getDB();
    $pdo->beginTransaction();
    try {
        // 1. Kurangi/tambah stok
        $pdo->prepare("UPDATE stok_barang SET stok = stok + ?, updated_at = NOW() WHERE id = ?")
            ->execute([$jumlah, $id]);

        // 2. Catat riwayat
        $barang = getBarangById($id);
        $pdo->prepare("
            INSERT INTO riwayat_stok (id_barang, nama_barang, jenis, jumlah, harga_satuan, total, pemasok, dicatat_oleh)
            VALUES (?, ?, 'restock', ?, ?, ?, ?, ?)
        ")->execute([
            $id,
            $barang['nama_barang'] ?? '—',
            $jumlah,
            (float) ($barang['harga_dasar'] ?? 0),
            $jumlah * (float)($barang['harga_dasar'] ?? 0),
            $pemasok,
            $oleh,
        ]);

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log("[stok] restockBarang: " . $e->getMessage());
        return false;
    }
}

function kurangiStok(int $idBarang, int $qty): bool {
    $pdo  = getDB();
    // Cek stok tidak negatif
    $stmt = $pdo->prepare("SELECT stok FROM stok_barang WHERE id = ? FOR UPDATE");
    $stmt->execute([$idBarang]);
    $row  = $stmt->fetch();
    if (!$row || $row['stok'] < $qty) return false;

    $pdo->prepare("UPDATE stok_barang SET stok = stok - ?, updated_at = NOW() WHERE id = ?")
        ->execute([$qty, $idBarang]);
    return true;
}

function hitungTotal(array $items, float $diskonPersen = 0, float $pajakPersen = 0): array {
    // SECURITY: Recalculate dari data server, bukan dari input klien
    $subtotal = 0;
    foreach ($items as $item) {
        $barang = getBarangById((int)($item['id_barang'] ?? 0));
        if (!$barang) continue;
        // Gunakan harga_jual dari DB, BUKAN dari request
        $subtotal += $barang['harga_jual'] * (int)$item['qty'];
    }

    $diskon = round($subtotal * ($diskonPersen / 100));
    $dasar  = $subtotal - $diskon;
    $pajak  = round($dasar  * ($pajakPersen  / 100));
    $total  = $dasar + $pajak;

    return compact('subtotal', 'diskon', 'pajak', 'total');
}


function simpanPenjualan(array $data, int $idKasir): array {
    $pdo = getDB();

    // Validasi items tidak kosong
    if (empty($data['items'])) {
        return ['status' => 'error', 'message' => 'Keranjang kosong'];
    }

    $hit = hitungTotal(
        $data['items'],
        (float)($data['diskon_persen'] ?? 0),
        (float)($data['pajak_persen']  ?? 0)
    );

    $nama     = sanitize($data['nama_pembeli'] ?? 'Umum');
    $metode   = in_array($data['metode'] ?? '', ['Cash','Piutang','Transfer'])
                ? $data['metode'] : 'Cash';
    $status   = $metode === 'Piutang' ? 'Piutang' : 'Lunas';
    $uangMasuk = $metode === 'Cash' ? cleanMoney($data['uang_masuk'] ?? 0) : $hit['total'];
    $kembalian = max(0, $uangMasuk - $hit['total']);

    if ($metode === 'Cash' && $uangMasuk < $hit['total']) {
        return ['status' => 'error', 'message' => 'Uang tidak cukup'];
    }

    $pdo->beginTransaction();
    try {
        // 1. Insert header penjualan
        $stmt = $pdo->prepare("
            INSERT INTO penjualan
              (id_kasir, nama_pembeli, subtotal, diskon, pajak, total,
               metode, status, uang_masuk, kembalian, keterangan)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([
            $idKasir,
            $nama,
            $hit['subtotal'],
            $hit['diskon'],
            $hit['pajak'],
            $hit['total'],
            $metode,
            $status,
            $uangMasuk,
            $kembalian,
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

            // Kurangi stok (gagal tidak rollback seluruh transaksi, hanya log)
            if (!kurangiStok($barang['id'], (int)$item['qty'])) {
                error_log("[penjualan] Stok tidak cukup: barang #{$barang['id']}");
            }
        }

        // 3. Jika Piutang → buat record piutang
        if ($metode === 'Piutang') {
            $pdo->prepare("
                INSERT INTO piutang (id_penjualan, nama_pembeli, total_hutang, sisa_hutang, keterangan)
                VALUES (?,?,?,?,?)
            ")->execute([
                $idPenjualan,
                $nama,
                $hit['total'],
                $hit['total'],
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
        error_log("[penjualan] simpanPenjualan: " . $e->getMessage());
        return ['status' => 'error', 'message' => 'Gagal menyimpan transaksi'];
    }
}

// Detail struk
function getStruk(int $idPenjualan): ?array {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT p.*, u.namalengkap AS kasir_nama FROM penjualan p LEFT JOIN users u ON u.id = p.id_kasir WHERE p.id = ?");
    $stmt->execute([$idPenjualan]);
    $trx = $stmt->fetch();
    if (!$trx) return null;

    $stmt2 = $pdo->prepare("SELECT * FROM detail_penjualan WHERE id_penjualan = ?");
    $stmt2->execute([$idPenjualan]);
    $trx['items'] = $stmt2->fetchAll();
    return $trx;
}

function getLaporanPenjualan(?string $dari = null, ?string $sampai = null, ?string $status = null): array {
    $pdo    = getDB();
    $where  = ['1=1'];
    $params = [];

    if ($dari)   { $where[] = 'DATE(p.tanggal) >= ?'; $params[] = $dari;   }
    if ($sampai) { $where[] = 'DATE(p.tanggal) <= ?'; $params[] = $sampai; }
    if ($status) { $where[] = 'p.status = ?';         $params[] = $status; }

    $sql  = "SELECT p.*, u.namalengkap AS kasir_nama
             FROM penjualan p
             LEFT JOIN users u ON u.id = p.id_kasir
             WHERE " . implode(' AND ', $where) . "
             ORDER BY p.tanggal DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $stmtD = $pdo->prepare("SELECT * FROM detail_penjualan WHERE id_penjualan = ?");
    foreach ($rows as &$row) {
        $stmtD->execute([$row['id']]);
        $row['items'] = $stmtD->fetchAll();
    }
    return $rows;
}

function getStatsDashboard(): array {
    $pdo  = getDB();
    $hari = date('Y-m-d');

    $stats = [];

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) FROM penjualan WHERE DATE(tanggal)=? AND status='Lunas'");
    $stmt->execute([$hari]);
    $stats['pendapatan_hari'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM penjualan WHERE DATE(tanggal)=?");
    $stmt->execute([$hari]);
    $stats['trx_hari'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COALESCE(SUM(sisa_hutang),0) FROM piutang WHERE status='belum_lunas'");
    $stats['total_piutang'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM stok_barang WHERE stok <= 5 AND aktif=1");
    $stats['stok_kritis'] = (int) $stmt->fetchColumn();

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

function getAllPiutang(string $status = ''): array {
    $pdo   = getDB();
    $where = $status ? "WHERE status = ?" : "";
    $stmt  = $pdo->prepare("SELECT * FROM piutang $where ORDER BY tanggal DESC");
    $stmt->execute($status ? [$status] : []);
    return $stmt->fetchAll();
}

function bayarPiutang(int $idPiutang, int $jumlah, string $dibayarOleh): array {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT * FROM piutang WHERE id = ? FOR UPDATE");

    $pdo->beginTransaction();
    try {
        $stmt->execute([$idPiutang]);
        $piutang = $stmt->fetch();

        if (!$piutang) return ['status'=>'error','message'=>'Data piutang tidak ditemukan'];
        if ($piutang['status'] === 'lunas') return ['status'=>'error','message'=>'Piutang sudah lunas'];
        if ($jumlah <= 0) return ['status'=>'error','message'=>'Jumlah bayar tidak valid'];

        $sisaSebelum  = (float) $piutang['sisa_hutang'];
        $sisaSesudah  = max(0, $sisaSebelum - $jumlah);
        $newStatus    = $sisaSesudah <= 0 ? 'lunas' : 'belum_lunas';

        $pdo->prepare("UPDATE piutang SET sisa_hutang=?, status=?, updated_at=NOW() WHERE id=?")
            ->execute([$sisaSesudah, $newStatus, $idPiutang]);

        $pdo->prepare("
            INSERT INTO riwayat_bayar_piutang
              (id_piutang, jumlah_bayar, sisa_sebelum, sisa_sesudah, dibayar_oleh)
            VALUES (?,?,?,?,?)
        ")->execute([$idPiutang, $jumlah, $sisaSebelum, $sisaSesudah, sanitize($dibayarOleh)]);

        $pdo->commit();
        return [
            'status'      => 'success',
            'sisa_sesudah'=> $sisaSesudah,
            'lunas'       => $newStatus === 'lunas',
        ];
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log("[piutang] bayarPiutang: " . $e->getMessage());
        return ['status'=>'error','message'=>'Gagal memproses pembayaran'];
    }
}
