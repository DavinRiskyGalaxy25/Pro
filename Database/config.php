<?php
if (!defined('__DBCONFIG__')) {
    define('__DBCONFIG__', true);
    $host     = getenv('DB_HOST')     ?: 'localhost';
    $username = getenv('DB_USER')     ?: 'uamkantin';
    $password = getenv('DB_PASS')     ?: 'uamkantin';
    $database = getenv('DB_NAME')     ?: 'db_kantin';
    $port     = getenv('DB_PORT')     ?: '3306';
    $pdo = null;
    try {
        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,   // SECURITY: real prepared statements
            PDO::ATTR_TIMEOUT            => 5,
        ]);
    } catch (PDOException $e) {
        error_log("[KantinKu] DB Connection failed: " . $e->getMessage());
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
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
        if (!$pdo instanceof PDO) {
            throw new RuntimeException('Database connection is not available');
        }
        return $pdo;
    }
}

if (!function_exists('getDBConnection')) {
    function getDBConnection(): ?PDO {
        global $pdo;
        return $pdo;
    }
}