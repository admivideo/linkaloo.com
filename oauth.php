<?php
require 'config.php';
require_once 'session.php';

$provider = $_GET['provider'] ?? '';
$sharedParam = '';
if (isset($_GET['shared'])) {
    $sharedParam = trim($_GET['shared']);
}
if (!isValidSharedUrl($sharedParam)) {
    $sharedParam = '';
}

if ($provider === 'google') {
    $stateToken = bin2hex(random_bytes(16));
    $_SESSION['oauth_state_token']  = $stateToken;
    $_SESSION['oauth_state_shared'] = $sharedParam;

    $queryParams = [
        'client_id' => $googleClientId,
        'redirect_uri' => $googleRedirectUri,
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'access_type' => 'online',
        'prompt' => 'select_account',
        'state' => $stateToken,
    ];
    $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);
    header('Location: ' . $authUrl);
    exit;
}

header('Content-Type: text/plain');
if (!$provider) {
    echo 'Proveedor no especificado';
} else {
    echo "Autenticación con $provider aún no implementada.";
}
?>
