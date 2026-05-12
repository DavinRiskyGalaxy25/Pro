<?php
if (!function_exists('kantinStartSession')) {

    function kantinStartSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'secure'   => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
            session_start();
        }
    }

    // ── CSRF ──────────────────────────────────
    function csrfToken(): string {
        kantinStartSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    function csrfField(): string {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
    }

    function csrfCheck(): void {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!hash_equals(csrfToken(), $token)) {
            http_response_code(403);
            die(json_encode(['status' => 'error', 'message' => 'CSRF token tidak valid']));
        }
    }

    // ── SESSION FINGERPRINT ───────────────────
    function sessionFingerprint(): string {
        return hash('sha256',
            ($_SERVER['HTTP_USER_AGENT'] ?? '') .
            ($_SERVER['REMOTE_ADDR']     ?? '')
        );
    }

    // ── LOGIN ─────────────────────────────────
    function loginUser(array $user): void {
        kantinStartSession();
        session_regenerate_id(true);
        $_SESSION['user_id']      = $user['id'];
        $_SESSION['username']     = $user['username'];
        $_SESSION['namalengkap']  = $user['namalengkap'];
        $_SESSION['email']        = $user['email'];
        $_SESSION['role']         = (int) $user['id_role'];
        $_SESSION['_fingerprint'] = sessionFingerprint();
        $_SESSION['login_at']     = time();
        unset($_SESSION['login_attempts'], $_SESSION['lockout_until']);
    }

    // ── LOGOUT ────────────────────────────────
    function logoutUser(): void {
        kantinStartSession();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    // ── CHECK AUTH ────────────────────────────
    function isLoggedIn(): bool {
        kantinStartSession();
        if (empty($_SESSION['user_id'])) return false;
        if (($_SESSION['_fingerprint'] ?? '') !== sessionFingerprint()) {
            logoutUser();
            return false;
        }
        return true;
    }

    // ── ROLE CONSTANTS ────────────────────────
    if (!defined('ROLE_ADMIN'))    define('ROLE_ADMIN',    1);
    if (!defined('ROLE_KASIR'))    define('ROLE_KASIR',    2);
    if (!defined('ROLE_PELANGGAN'))define('ROLE_PELANGGAN',3);

    function currentRole(): int { return (int)($_SESSION['role'] ?? 0); }
    function isAdmin(): bool    { return currentRole() === ROLE_ADMIN; }
    function isKasir(): bool    { return in_array(currentRole(), [ROLE_ADMIN, ROLE_KASIR]); }

    // ── GUARDS ────────────────────────────────
    function requireLogin(int $minRole = 0): void {
        if (!isLoggedIn()) {
            // [FIX-05] Redirect ke front controller, BUKAN /login.php
            header('Location: /?q=login');
            exit;
        }
        if ($minRole > 0 && currentRole() > $minRole) {
            http_response_code(403);
            // [FIX-06] Jangan include forbidden.php yang tidak ada
            // Tampilkan pesan sederhana atau redirect
            header('Location: /?q=menu');
            exit;
        }
    }

    function requireAdmin(): void { requireLogin(ROLE_ADMIN); }
    function requireKasir(): void { requireLogin(ROLE_KASIR); }

    // ── BRUTE-FORCE THROTTLE ──────────────────
    if (!defined('MAX_LOGIN_ATTEMPTS')) define('MAX_LOGIN_ATTEMPTS', 5);
    if (!defined('LOCKOUT_SECONDS'))    define('LOCKOUT_SECONDS',    300);

    function recordFailedLogin(): void {
        kantinStartSession();
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
            $_SESSION['lockout_until'] = time() + LOCKOUT_SECONDS;
        }
    }

    function isLockedOut(): bool {
        kantinStartSession();
        if (!isset($_SESSION['lockout_until'])) return false;
        if (time() > $_SESSION['lockout_until']) {
            unset($_SESSION['lockout_until'], $_SESSION['login_attempts']);
            return false;
        }
        return true;
    }

    function lockoutSecondsLeft(): int {
        return max(0, ($_SESSION['lockout_until'] ?? 0) - time());
    }
}
