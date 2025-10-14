<?php
require 'config.php';
require_once 'session.php';

$sharedParam = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sharedParam = trim($_POST['shared'] ?? '');
} elseif (isset($_GET['shared'])) {
    $sharedParam = trim($_GET['shared']);
}
if (!isValidSharedUrl($sharedParam)) {
    $sharedParam = '';
}

$encodedShared = $sharedParam !== '' ? rawurlencode($sharedParam) : '';
$googleOauthUrl = 'oauth.php?provider=google' . ($encodedShared ? '&shared=' . $encodedShared : '');
include 'header.php';
?>
<div class="app-logo"><img src="/img/logo_linkaloo_blue.png" alt="Linkaloo logo"></div>
<div class="login-wrapper">
    <div class="login-block">
        <h2>Iniciar sesión</h2>
        <p>Accede o regístrate usando tu cuenta de Google.</p>
        <a class="social-btn google" href="<?= htmlspecialchars($googleOauthUrl, ENT_QUOTES, 'UTF-8') ?>">Continuar con Google</a>
    </div>
</div>
</div>
</body>
</html>
