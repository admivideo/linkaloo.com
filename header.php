<?php
// Iniciar sesión solo si no se ha hecho ya
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rambla:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
    <script src="https://unpkg.com/feather-icons" defer></script>
    <script src="/assets/main.js" defer></script>
    <title>Linkadoo</title>
</head>
<body>
<header class="top-menu">
    <div class="logo"><a href="/panel.php"><img src="/img/linkaloo_white.png" alt="Linkadoo"></a></div>
    <nav>
        <button class="menu-toggle" aria-label="Menú"><span></span><span></span><span></span></button>
        <ul class="menu">
            <li><a href="/panel.php">Inicio</a></li>
            <?php if(isset($_SESSION['user_id'])): ?>
                <li><a href="/tableros.php">Tableros</a></li>
                <li><a href="#"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario'); ?></a></li>
                <li><a href="/logout.php">Salir</a></li>
            <?php else: ?>
                <li><a href="/login.php">Login</a></li>
                <li><a href="/register.php">Registro</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>
<div class="content">
