<?php
$lifetime = 365 * 24 * 60 * 60; // 365 días
ini_set('session.gc_maxlifetime', $lifetime);
session_set_cookie_params([
    'lifetime' => $lifetime,
    'path' => '/',
    'domain' => '',
    'secure' => !empty($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
