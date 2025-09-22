<?php
require 'config.php';
require_once 'session.php';

if (!isset($_GET['code'])) {
    echo 'Código de autorización no proporcionado';
    exit;
}

$code = $_GET['code'];
$stateParam = $_GET['state'] ?? '';
$expectedState = $_SESSION['oauth_state_token'] ?? '';
$sharedParam = $_SESSION['oauth_state_shared'] ?? '';
unset($_SESSION['oauth_state_token'], $_SESSION['oauth_state_shared']);
if (!$expectedState || !$stateParam || !hash_equals($expectedState, $stateParam)) {
    error_log('Intento de OAuth de Google con token de estado inválido.');
    echo 'Solicitud de autenticación no válida';
    exit;
}
if (!isValidSharedUrl($sharedParam)) {
    $sharedParam = '';
}
$encodedShared = $sharedParam !== '' ? rawurlencode($sharedParam) : '';

$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_POSTFIELDS     => http_build_query([
        'code'          => $code,
        'client_id'     => $googleClientId,
        'client_secret' => $googleClientSecret,
        'redirect_uri'  => $googleRedirectUri,
        'grant_type'    => 'authorization_code',
    ]),
]);

$tokenResponse = curl_exec($ch);
if ($tokenResponse === false) {
    error_log('Error de cURL al obtener token: ' . curl_error($ch));
    curl_close($ch);
    echo 'Error al obtener token de Google';
    exit;
}
curl_close($ch);

$tokenData = json_decode($tokenResponse, true) ?: [];

if (!isset($tokenData['access_token'])) {
    error_log('Respuesta de token no válida: ' . $tokenResponse);
    echo 'Error al obtener token de Google';
    exit;
}

$ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $tokenData['access_token']],
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
]);

$userInfoResponse = curl_exec($ch);
if ($userInfoResponse === false) {
    error_log('Error de cURL al obtener información de usuario: ' . curl_error($ch));
    curl_close($ch);
    echo 'Error al obtener información de usuario de Google';
    exit;
}
curl_close($ch);

$userInfo = json_decode($userInfoResponse, true) ?: [];
$email    = trim($userInfo['email'] ?? '');
$name     = trim($userInfo['name'] ?? '');
$verified = (bool) ($userInfo['verified_email'] ?? false);

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo 'Error al obtener información de usuario de Google';
    exit;
}

if (!$verified) {
    echo 'La dirección de correo electrónico de Google no está verificada.';
    exit;
}

$stmt = $pdo->prepare('SELECT id, nombre FROM usuarios WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    $userId   = (int) $user['id'];
    $userName = $user['nombre'];
    session_regenerate_id(true);
    $_SESSION['user_id']   = $userId;
    $_SESSION['user_name'] = $userName;
    linkalooIssueRememberMeToken($pdo, $userId);
    $redirect = $encodedShared ? 'agregar_favolink.php?shared=' . $encodedShared : 'panel.php';
    header('Location: ' . $redirect);
    exit;
} else {
    $passHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, email, pass_hash) VALUES (?, ?, ?)');
    $stmt->execute([$name ?: $email, $email, $passHash]);
    $userId   = (int) $pdo->lastInsertId();
    $userName = $name ?: $email;
    session_regenerate_id(true);
    $_SESSION['user_id']   = $userId;
    $_SESSION['user_name'] = $userName;
    linkalooIssueRememberMeToken($pdo, $userId);
    $redirect = 'seleccion_tableros.php' . ($encodedShared ? '?shared=' . $encodedShared : '');
    header('Location: ' . $redirect);
    exit;
}
?>

