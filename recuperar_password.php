<?php
require 'config.php';
require_once 'session.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if ($email) {
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            $token = bin2hex(random_bytes(16));
            $stmt = $pdo->prepare('INSERT INTO password_resets (usuario_id, token, expiracion) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))');
            $stmt->execute([$user['id'], $token]);
            $resetLink = "https://linkaloo.com/restablecer_password.php?token=$token";
            @mail($email, 'Recuperar contraseña', "Haz clic en el siguiente enlace para restablecer tu contraseña: $resetLink");
        }
        $message = 'Si el correo existe, hemos enviado instrucciones a tu email.';
    } else {
        $message = 'Introduce un correo válido';
    }
}
include 'header.php';
?>
<div class="app-logo"><img src="/img/logo_linkaloo_blue.png" alt="Linkaloo logo"></div>
<div class="login-wrapper">
    <div class="login-block">
        <h2>Recuperar contraseña</h2>
        <?php if($message): ?><p class="notice"><?= htmlspecialchars($message) ?></p><?php endif; ?>
        <form method="post" class="login-form">
            <input type="email" name="email" placeholder="Email">
            <button type="submit">Enviar enlace</button>
        </form>
        <div class="login-links">
            <a href="login.php">Volver al login</a>
        </div>
    </div>
</div>
</div>
</body>
</html>
