<?php
require 'config.php';
require_once 'session.php';

$provider = $_GET['provider'] ?? '';

if ($provider === 'google') {
    $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id' => $googleClientId,
        'redirect_uri' => $googleRedirectUri,
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'access_type' => 'online',
        'prompt' => 'select_account'
    ]);
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
