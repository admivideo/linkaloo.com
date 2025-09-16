<?php
require 'config.php';
require_once 'session.php';

linkalooClearRememberMeToken($pdo);

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
} else {
    setcookie(session_name(), '', time() - 42000, '/');
}

session_destroy();
header('Location: login.php');
exit;
