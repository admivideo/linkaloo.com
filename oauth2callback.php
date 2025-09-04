<?php
require 'config.php';
session_start();

if (!isset($_GET['code'])) {
    echo 'Código de autorización no proporcionado';
    exit;
}

$code = $_GET['code'];

$tokenResponse = file_get_contents('https://oauth2.googleapis.com/token', false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query([
            'code' => $code,
            'client_id' => $googleClientId,
            'client_secret' => $googleClientSecret,
            'redirect_uri' => $googleRedirectUri,
            'grant_type' => 'authorization_code'
        ])
    ]
]));

$tokenData = json_decode($tokenResponse, true);

if (!isset($tokenData['access_token'])) {
    echo 'Error al obtener token de Google';
    exit;
}

$userInfoResponse = file_get_contents('https://www.googleapis.com/oauth2/v2/userinfo', false, stream_context_create([
    'http' => [
        'header' => 'Authorization: Bearer ' . $tokenData['access_token']
    ]
]));

$userInfo = json_decode($userInfoResponse, true);
$email    = $userInfo['email'] ?? '';
$name     = $userInfo['name'] ?? '';

if (!$email) {
    echo 'Error al obtener información de usuario de Google';
    exit;
}

$stmt = $pdo->prepare('SELECT id, nombre FROM usuarios WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    $userId   = $user['id'];
    $userName = $user['nombre'];
} else {
    $passHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, email, pass_hash) VALUES (?, ?, ?)');
    $stmt->execute([$name ?: $email, $email, $passHash]);
    $userId   = $pdo->lastInsertId();
    $userName = $name ?: $email;
}

$_SESSION['user_id']   = $userId;
$_SESSION['user_name'] = $userName;

header('Location: panel.php');
exit;
?>

