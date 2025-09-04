<?php
// Iniciar sesión solo si no se ha hecho ya
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Evitar caché de contenido dinámico
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
$cssVersion = filemtime(__DIR__ . '/assets/style.css');
$jsVersion  = filemtime(__DIR__ . '/assets/main.js');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/img/favicon.png" type="image/png">
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rambla:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css?v=<?= $cssVersion ?>">
    <script src="https://unpkg.com/feather-icons" defer></script>
    <script src="/assets/main.js?v=<?= $jsVersion ?>" defer></script>
    <title>linkaloo</title>
</head>
<body>
<header class="top-menu">
    <div class="logo"><a href="/panel.php"><img src="/img/logo_linkaloo_blue.png" alt="linkaloo"><!-- Logo file already on server --></a></div>
    <nav>
        <button class="menu-toggle" aria-label="Menú"><span></span><span></span><span></span></button>
        <ul class="menu">
            <?php if(isset($_SESSION['user_id'])): ?>
                <li><a href="/tableros.php">Tableros</a></li>
                <li><a href="/cpanel.php"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario'); ?></a></li>
            <?php else: ?>
                <li><a href="/login.php">Login</a></li>
                <li><a href="/register.php">Registro</a></li>
            <?php endif; ?>
            <li class="settings-menu">
                <button class="settings-toggle" aria-label="Configuración"><i data-feather="settings"></i></button>
                <ul class="settings-submenu">
                    <li><a href="/cookies.php">Cookies</a></li>
                    <li><a href="/politica_cookies.php">Política de cookies</a></li>
                    <li><a href="/condiciones_servicio.php">Condiciones de servicio</a></li>
                    <li><a href="/politica_privacidad.php">Política de privacidad</a></li>
                    <li><a href="/quienes_somos.php">Quiénes somos</a></li>
                </ul>
            </li>
        </ul>
    </nav>
</header>
<div class="content">
