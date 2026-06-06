<?php
// Inicialización de sesión y helpers de seguridad
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) return false;
    $valid = hash_equals($_SESSION['csrf_token'], $token);
    if (!$valid) return false;
    // Expira en 1 hora
    if (isset($_SESSION['csrf_token_time']) && (time() - $_SESSION['csrf_token_time'] > 3600)) {
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        return false;
    }
    return true;
}

// Flash messages helpers (session)
function set_flash(string $type, string $message): void {
    if (!isset($_SESSION['flash'])) $_SESSION['flash'] = [];
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flash(): array {
    $out = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $out;
}
