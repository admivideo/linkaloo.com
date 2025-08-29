<?php
require 'config.php';
session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($nombre && $email && $password) {
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'El email ya existe';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, email, pass_hash) VALUES (?, ?, ?)');
            $stmt->execute([$nombre, $email, $hash]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_name'] = $nombre;
            header('Location: panel_de_control.php');
            exit;
        }
    } else {
        $error = 'Rellena todos los campos';
    }
}
include 'header.php';
?>
<div class="login-wrapper">
    <div class="login-block">
        <h2>Registro</h2>
        <?php if($error): ?><p class="error"><?= $error ?></p><?php endif; ?>
        <form method="post" class="login-form">
            <input type="text" name="nombre" placeholder="Nombre">
            <input type="email" name="email" placeholder="Email">
            <input type="password" name="password" placeholder="Contraseña">
            <button type="submit">Registrarse</button>
        </form>
        <div class="login-links">
            <a href="login.php">Iniciar sesión</a>
            <a href="#">¿Olvidaste tu contraseña?</a>
        </div>
    </div>
    <div class="social-block">
        <h3>O registrarte con</h3>
        <a class="social-btn instagram" href="oauth.php?provider=instagram">Instagram</a>
        <a class="social-btn google" href="oauth.php?provider=google">Google</a>
        <a class="social-btn facebook" href="oauth.php?provider=facebook">Facebook</a>
    </div>
</div>
<?php include 'footer.php'; ?>
