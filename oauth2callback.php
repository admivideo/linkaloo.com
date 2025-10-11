<?php
require 'config.php';
require_once 'session.php';

if (!isset($_GET['code'])) {
    echo 'Código de autorización no proporcionado';
    exit;
}

$code = $_GET['code'];
// Algunas implementaciones (como Google en ciertos navegadores) pueden
// devolver el parámetro `code` con signos "+" que PHP convierte en
// espacios al poblar $_GET. Esto provoca que el intercambio de token
// falle con "invalid_grant". Restituimos los "+" originales.
if (strpos($code, ' ') !== false) {
    $code = str_replace(' ', '+', $code);
}
$stateParam = $_GET['state'] ?? '';
$expectedState = $_SESSION['oauth_state_token'] ?? '';
$sharedParam = $_SESSION['oauth_state_shared'] ?? '';
unset($_SESSION['oauth_state_token'], $_SESSION['oauth_state_shared']);
if (!isValidSharedUrl($sharedParam)) {
    $sharedParam = '';
}
if (!$expectedState || !$stateParam || !hash_equals($expectedState, $stateParam)) {
    $sharedParam = '';
}
$encodedShared = $sharedParam !== '' ? rawurlencode($sharedParam) : '';

$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
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
]);

$userInfoResponse = curl_exec($ch);
if ($userInfoResponse === false) {
    error_log('Error de cURL al obtener información de usuario: ' . curl_error($ch));
}
curl_close($ch);

$userInfo = json_decode($userInfoResponse, true) ?: [];
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

