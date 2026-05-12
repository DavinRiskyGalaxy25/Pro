<?php
if (!defined('__DBCONFIG__')) {
    define('__DBCONFIG__', true);

    // ── [FIX-04] Load .env file secara manual ──
    $envPaths = [__DIR__ . '/../.env', __DIR__ . '/.env'];
    foreach ($envPaths as $envFile) {
        if (file_exists($envFile) && is_readable($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) continue;
                if (str_contains($line, '=')) {
                    [$key, $val] = explode('=', $line, 2);
                    $key = trim($key);
                    $val = trim(trim($val), '"\'');
                    if (!getenv($key)) { putenv("$key=$val"); $_ENV[$key] = $val; }
                }
            }
            break;
        }
    }

    $host     = getenv('DB_HOST') ?: 'localhost';
    $username = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASS') ?: '';
    $database = getenv('DB_NAME') ?: 'db_kantin';
    $port     = getenv('DB_PORT') ?: '3306';
    $pdo      = null;

    try {
        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 5,
        ]);
    } catch (PDOException $e) {
        error_log('[KantinKu] DB Connection failed: ' . $e->getMessage());
        if (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
            header('Content-Type: application/json');
            http_response_code(503);
            echo json_encode(['status' => 'error', 'message' => 'Database tidak tersedia']);
            exit;
        }
    }
}

if (!function_exists('getDB')) {
    function getDB(): PDO {
        global $pdo;
        if (!$pdo instanceof PDO) throw new RuntimeException('Database connection is not available');
        return $pdo;
    }
}
if (!function_exists('getDBConnection')) {
    function getDBConnection(): ?PDO { global $pdo; return $pdo; }
}