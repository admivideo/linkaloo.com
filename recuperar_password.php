<?php
require 'config.php';
session_start();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if ($email) {
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            $token = bin2hex(random_bytes(16));
            $expires = date('Y-m-d H:i:s', time() + 3600);
            $pdo->prepare('INSERT INTO password_resets (usuario_id, token, expires_at) VALUES (?,?,?)')
                ->execute([$user['id'], $token, $expires]);
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $resetLink = "$scheme://".$_SERVER['HTTP_HOST']."/restablecer_password.php?token=$token";
            $message = 'Enlace de recuperación: <a href="'.htmlspecialchars($resetLink).'">'.htmlspecialchars($resetLink).'</a>';
        } else {
            $message = 'Si el correo existe, recibirás un enlace para restablecer la contraseña.';
        }
    } else {
        $message = 'Introduce tu email';
    }
}
include 'header.php';
?>
<div class="login-wrapper">
    <div class="login-block">
        <h2>Recuperar contraseña</h2>
        <?php if($message): ?><p><?= $message ?></p><?php endif; ?>
        <form method="post" class="login-form">
            <input type="email" name="email" placeholder="Email">
            <button type="submit">Enviar enlace</button>
        </form>
        <div class="login-links">
            <a href="login.php">Volver a iniciar sesión</a>
        </div>
    </div>
</div>
</div>
</body>
</html>
