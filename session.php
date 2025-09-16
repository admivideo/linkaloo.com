<?php
if (!defined('LINKALOO_SESSION_LIFETIME')) {
    define('LINKALOO_SESSION_LIFETIME', 365 * 24 * 60 * 60); // 365 días
}

if (!defined('LINKALOO_REMEMBER_COOKIE_NAME')) {
    define('LINKALOO_REMEMBER_COOKIE_NAME', 'linkaloo_remember');
}

ini_set('session.gc_maxlifetime', LINKALOO_SESSION_LIFETIME);
ini_set('session.cookie_lifetime', LINKALOO_SESSION_LIFETIME);

session_set_cookie_params([
    'lifetime' => LINKALOO_SESSION_LIFETIME,
    'path'     => '/',
    'domain'   => '',
    'secure'   => !empty($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('linkalooRememberCookieOptions')) {
    function linkalooRememberCookieOptions(int $expires): array
    {
        return [
            'expires'  => $expires,
            'path'     => '/',
            'domain'   => '',
            'secure'   => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }
}

if (!function_exists('linkalooIssueRememberMeToken')) {
    function linkalooIssueRememberMeToken(PDO $pdo, int $userId): void
    {
        try {
            $pdo->prepare('DELETE FROM usuario_tokens WHERE usuario_id = ?')->execute([$userId]);

            $selector  = bin2hex(random_bytes(16));
            $validator = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $validator);
            $expiresAt = date('Y-m-d H:i:s', time() + LINKALOO_SESSION_LIFETIME);

            $stmt = $pdo->prepare('INSERT INTO usuario_tokens (usuario_id, selector, token_hash, expires_at) VALUES (?, ?, ?, ?)');
            $stmt->execute([$userId, $selector, $tokenHash, $expiresAt]);
        } catch (Throwable $exception) {
            error_log('Error issuing remember-me token: ' . $exception->getMessage());
            return;
        }

        $cookieValue = $selector . ':' . $validator;
        setcookie(LINKALOO_REMEMBER_COOKIE_NAME, $cookieValue, linkalooRememberCookieOptions(time() + LINKALOO_SESSION_LIFETIME));
        $_COOKIE[LINKALOO_REMEMBER_COOKIE_NAME] = $cookieValue;
    }
}

if (!function_exists('linkalooDeleteRememberToken')) {
    function linkalooDeleteRememberToken(PDO $pdo, string $selector): void
    {
        if ($selector === '') {
            return;
        }

        try {
            $stmt = $pdo->prepare('DELETE FROM usuario_tokens WHERE selector = ?');
            $stmt->execute([$selector]);
        } catch (Throwable $exception) {
            error_log('Error deleting remember-me token: ' . $exception->getMessage());
        }
    }
}

if (!function_exists('linkalooClearRememberMeToken')) {
    function linkalooClearRememberMeToken(PDO $pdo): void
    {
        $cookieValue = $_COOKIE[LINKALOO_REMEMBER_COOKIE_NAME] ?? null;
        if ($cookieValue && strpos($cookieValue, ':') !== false) {
            [$selector] = explode(':', $cookieValue, 2);
            linkalooDeleteRememberToken($pdo, $selector);
        }

        setcookie(LINKALOO_REMEMBER_COOKIE_NAME, '', linkalooRememberCookieOptions(time() - 3600));
        unset($_COOKIE[LINKALOO_REMEMBER_COOKIE_NAME]);
    }
}

if (!function_exists('linkalooAttemptAutoLogin')) {
    function linkalooAttemptAutoLogin(PDO $pdo): void
    {
        $cookieValue = $_COOKIE[LINKALOO_REMEMBER_COOKIE_NAME] ?? null;
        if (!$cookieValue || strpos($cookieValue, ':') === false) {
            linkalooClearRememberMeToken($pdo);
            return;
        }

        [$selector, $validator] = explode(':', $cookieValue, 2);
        if ($selector === '' || $validator === '') {
            linkalooClearRememberMeToken($pdo);
            return;
        }

        try {
            $stmt = $pdo->prepare('SELECT usuario_id, token_hash, expires_at FROM usuario_tokens WHERE selector = ? LIMIT 1');
            $stmt->execute([$selector]);
            $tokenRow = $stmt->fetch();
        } catch (Throwable $exception) {
            error_log('Error retrieving remember-me token: ' . $exception->getMessage());
            linkalooClearRememberMeToken($pdo);
            return;
        }

        if (!$tokenRow) {
            linkalooClearRememberMeToken($pdo);
            return;
        }

        if (strtotime($tokenRow['expires_at']) < time()) {
            linkalooDeleteRememberToken($pdo, $selector);
            linkalooClearRememberMeToken($pdo);
            return;
        }

        $expectedHash  = $tokenRow['token_hash'];
        $validatorHash = hash('sha256', $validator);
        if (!hash_equals($expectedHash, $validatorHash)) {
            linkalooDeleteRememberToken($pdo, $selector);
            linkalooClearRememberMeToken($pdo);
            return;
        }

        try {
            $userStmt = $pdo->prepare('SELECT id, nombre FROM usuarios WHERE id = ? LIMIT 1');
            $userStmt->execute([(int) $tokenRow['usuario_id']]);
            $user = $userStmt->fetch();
        } catch (Throwable $exception) {
            error_log('Error retrieving user for remember-me login: ' . $exception->getMessage());
            linkalooDeleteRememberToken($pdo, $selector);
            linkalooClearRememberMeToken($pdo);
            return;
        }

        if (!$user) {
            linkalooDeleteRememberToken($pdo, $selector);
            linkalooClearRememberMeToken($pdo);
            return;
        }

        session_regenerate_id(true);
        $_SESSION['user_id']   = (int) $user['id'];
        $_SESSION['user_name'] = $user['nombre'];

        linkalooIssueRememberMeToken($pdo, (int) $user['id']);
    }
}

if (empty($_SESSION['user_id']) && !empty($_COOKIE[LINKALOO_REMEMBER_COOKIE_NAME])) {
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        require_once __DIR__ . '/config.php';
    }

    linkalooAttemptAutoLogin($pdo);
}
?>
