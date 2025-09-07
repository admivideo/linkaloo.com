<?php
require 'config.php';
session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    if ($email && $password) {
        $stmt = $pdo->prepare('SELECT id, nombre, pass_hash FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['pass_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nombre'];
            header('Location: panel.php');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos';
        }
    } else {
        $error = 'Introduce email y contraseña';
    }
}
include 'header.php';
?>
<div class="app-logo"><img src="/img/logo_linkaloo_blue.png" alt="Linkaloo logo"></div>
<div class="login-wrapper">
    <div class="login-block">
        <h2>Iniciar sesión</h2>
        <?php if($error): ?><p class="error"><?= $error ?></p><?php endif; ?>
        <form method="post" class="login-form">
            <input type="email" name="email" placeholder="Email">
            <input type="password" name="password" placeholder="Contraseña">
            <button type="submit">Entrar</button>
        </form>
        <div class="login-links">
            <a href="register.php">Registrarse</a>
            <a href="#">¿Olvidaste tu contraseña?</a>
        </div>
    </div>
    <div class="social-block">
        <h3>O ingresar con</h3>
        <a class="social-btn instagram" href="oauth.php?provider=instagram">Instagram</a>
        <a class="social-btn google" href="oauth.php?provider=google">Google</a>
        <a class="social-btn facebook" href="oauth.php?provider=facebook">Facebook</a>
    </div>
</div>
</div>
</body>
</html>
