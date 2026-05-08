<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../Database/config.php';
require_once __DIR__ . '/../Database/auth.php';
require_once __DIR__ . '/../Database/functions.php';

kantinStartSession();
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Tidak diizinkan']);
    exit;
}

$csrfFromHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
$input          = json_decode(file_get_contents('php://input'), true) ?? [];
$csrfFromBody   = $input['csrf_token'] ?? '';
$csrfOk         = hash_equals(csrfToken(), $csrfFromHeader) || hash_equals(csrfToken(), $csrfFromBody);

if (!$csrfOk) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Token tidak valid']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$result = simpanPenjualan($input, (int) $_SESSION['user_id']);

if ($result['status'] === 'success') {
    http_response_code(200);
} else {
    http_response_code(422);
}

echo json_encode($result);