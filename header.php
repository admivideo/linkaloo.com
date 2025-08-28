<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/style.css">
    <script src="/assets/main.js" defer></script>
    <title>Linkadoo</title>
</head>
<body>
<header class="top-menu">
    <div class="logo"><a href="/panel_de_control.php">Linkadoo</a></div>
    <nav>
        <ul>
            <li><a href="/panel_de_control.php">Tableros</a></li>
            <li><a href="#">Usuario</a>
                <ul class="submenu">
                    <?php if(isset($_SESSION['user_id'])): ?>
                    <li><a href="/logout.php">Salir</a></li>
                    <?php else: ?>
                    <li><a href="/login.php">Entrar</a></li>
                    <li><a href="/register.php">Registro</a></li>
                    <?php endif; ?>
                </ul>
            </li>
        </ul>
    </nav>
</header>
<div class="content">
