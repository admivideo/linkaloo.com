<?php
require 'config.php';
require_once 'session.php';

$token = $_GET['token'] ?? '';
$message = '';
$showForm = false;

if ($token) {
    $stmt = $pdo->prepare('SELECT usuario_id FROM password_resets WHERE token = ? AND expiracion > NOW()');
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    if ($row) {
        $showForm = true;
        $userId = $row['usuario_id'];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            if ($password) {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare('UPDATE usuarios SET pass_hash = ? WHERE id = ?');
                $stmt->execute([$hash, $userId]);
                $stmt = $pdo->prepare('DELETE FROM password_resets WHERE token = ?');
                $stmt->execute([$token]);
                $message = 'Contraseña actualizada. Ahora puedes iniciar sesión.';
                $showForm = false;
            } else {
                $message = 'Introduce una nueva contraseña';
            }
        }
    } else {
        $message = 'Enlace de recuperación inválido o expirado.';
    }
} else {
    $message = 'Token no válido.';
}

include 'header.php';
?>
<div class="app-logo"><img src="/img/logo_linkaloo_blue.png" alt="Linkaloo logo"></div>
<div class="login-wrapper">
    <div class="login-block">
        <h2>Restablecer contraseña</h2>
        <?php if($message): ?><p class="notice"><?= htmlspecialchars($message) ?></p><?php endif; ?>
        <?php if($showForm): ?>
        <form method="post" class="login-form">
            <input type="password" name="password" placeholder="Nueva contraseña">
            <button type="submit">Guardar contraseña</button>
        </form>
        <?php endif; ?>
        <div class="login-links">
            <a href="login.php">Iniciar sesión</a>
        </div>
    </div>
</div>
</div>
<?php include 'firebase_scripts.php'; ?>
</body>
</html>
