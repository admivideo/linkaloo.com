<?php
require 'config.php';
session_start();

$token = $_GET['token'] ?? '';
$message = '';
$showForm = false;
if ($token) {
    $stmt = $pdo->prepare('SELECT usuario_id, expires_at FROM password_resets WHERE token = ?');
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    if ($row && strtotime($row['expires_at']) > time()) {
        $showForm = true;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pass = $_POST['password'] ?? '';
            $confirm = $_POST['confirm'] ?? '';
            if ($pass && $pass === $confirm) {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $pdo->prepare('UPDATE usuarios SET pass_hash = ? WHERE id = ?')
                    ->execute([$hash, $row['usuario_id']]);
                $pdo->prepare('DELETE FROM password_resets WHERE token = ?')->execute([$token]);
                $showForm = false;
                $message = 'Contraseña actualizada. <a href="login.php">Inicia sesión</a>';
            } else {
                $message = 'Las contraseñas no coinciden';
            }
        }
    } else {
        $message = 'El enlace no es válido o ha expirado';
    }
} else {
    $message = 'Token no válido';
}
include 'header.php';
?>
<div class="login-wrapper">
    <div class="login-block">
        <h2>Restablecer contraseña</h2>
        <?php if($message): ?><p><?= $message ?></p><?php endif; ?>
        <?php if($showForm): ?>
        <form method="post" class="login-form">
            <input type="password" name="password" placeholder="Nueva contraseña">
            <input type="password" name="confirm" placeholder="Confirmar contraseña">
            <button type="submit">Guardar</button>
        </form>
        <?php endif; ?>
    </div>
</div>
</div>
</body>
</html>
