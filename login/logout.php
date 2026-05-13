<?php
require_once __DIR__ . '/../Database/auth.php';
kantinStartSession();
logoutUser();
header('Location: /?q=login');
exit;
PHPEOF

# Update functions.php to add kategori support
cat >> /home/claude/kantinku-fixed/Database/functions.php << 'PHPEOF'

// ─────────────────────────────────────────────
// KATEGORI PRODUK
// ─────────────────────────────────────────────
function getAllKategori(): array {
    try {
        $pdo  = getDB();
        $stmt = $pdo->query("SELECT * FROM kategori_produk ORDER BY nama ASC");
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [
            ['id'=>1,'nama'=>'Makanan Berat','warna'=>'#f97316'],
            ['id'=>2,'nama'=>'Minuman','warna'=>'#3b82f6'],
            ['id'=>3,'nama'=>'Snack','warna'=>'#a855f7'],
            ['id'=>4,'nama'=>'Lainnya','warna'=>'#6b7280'],
        ];
    }
}

function tambahKategori(string $nama, string $warna = '#f97316'): int|false {
    try {
        $pdo  = getDB();
        $stmt = $pdo->prepare("INSERT INTO kategori_produk (nama, warna) VALUES (?,?)");
        $stmt->execute([sanitize($nama), $warna]);
        return (int)$pdo->lastInsertId();
    } catch (Throwable $e) { return false; }
}

// Generate SKU otomatis: KTN-[KATEGORI_3HURUF]-[5DIGIT]
function generateSKU(string $namaBarang, ?string $tipe = null): string {
    $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $namaBarang), 0, 3));
    if (strlen($prefix) < 3) $prefix = str_pad($prefix, 3, 'X');
    $num = str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
    return $prefix . '-' . $num;
}

function isSKUUnique(string $sku, ?int $excludeId = null): bool {
    try {
        $pdo  = getDB();
        $sql  = "SELECT id FROM stok_barang WHERE sku = ?";
        $params = [$sku];
        if ($excludeId) { $sql .= " AND id != ?"; $params[] = $excludeId; }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return !$stmt->fetch();
    } catch (Throwable $e) { return true; }
}