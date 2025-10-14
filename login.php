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
        <p class="login-subtitle">Accede o regístrate usando tu cuenta de Google.</p>
        <a class="social-btn google" href="<?= htmlspecialchars($googleOauthUrl, ENT_QUOTES, 'UTF-8') ?>">
            <span class="google-icon" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" role="img" xmlns="http://www.w3.org/2000/svg">
                    <title>Google</title>
                    <path d="M21.6 12.227c0-.76-.068-1.492-.195-2.197H12v4.159h5.381a4.6 4.6 0 0 1-1.995 3.017v2.508h3.229c1.89-1.742 2.985-4.305 2.985-7.487" fill="#4285F4"/>
                    <path d="M12 22c2.7 0 4.963-.897 6.618-2.386l-3.229-2.508c-.897.6-2.048.954-3.389.954-2.608 0-4.818-1.762-5.606-4.132H3.03v2.597A9.997 9.997 0 0 0 12 22" fill="#34A853"/>
                    <path d="M6.394 13.928A6 6 0 0 1 6.08 12c0-.67.115-1.321.314-1.928V7.475H3.03A9.998 9.998 0 0 0 2 12c0 1.61.386 3.129 1.03 4.525l3.364-2.597" fill="#FBBC05"/>
                    <path d="M12 6.58c1.471 0 2.79.505 3.83 1.495l2.872-2.872C16.958 3.513 14.695 2.5 12 2.5a9.997 9.997 0 0 0-8.97 4.975l3.364 2.597C7.182 8.342 9.392 6.58 12 6.58" fill="#EA4335"/>
                </svg>
            </span>
            <span class="google-text">
                <strong>Continuar con Google</strong>
                <small>Inicio de sesión protegido por Google OAuth</small>
            </span>
        </a>
        <p class="login-assurance">Nunca compartiremos tu actividad sin tu autorización y puedes revocar el acceso en cualquier momento desde tu cuenta de Google.</p>
    </div>
</div>
</div>
</body>
</html>
