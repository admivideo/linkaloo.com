<?php
require 'config.php';
require_once 'session.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $captcha = $_POST['g-recaptcha-response'] ?? '';
    if ($email && $password) {
        $captchaValid = false;
        if ($captcha && !empty($recaptchaSecretKey)) {
            $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptchaSecretKey}&response={$captcha}");
            $data = json_decode($response, true);
            $captchaValid = ($data['success'] ?? false)
                && ($data['score'] ?? 0) >= 0.5
                && ($data['action'] ?? '') === 'login';
        }
        if ($captchaValid) {
            $stmt = $pdo->prepare('SELECT id, nombre, pass_hash FROM usuarios WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['pass_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['user_name'] = $user['nombre'];
                linkalooIssueRememberMeToken($pdo, (int) $user['id']);
                header('Location: panel.php');
                exit;
            } else {
                $error = 'Usuario o contraseña incorrectos';
            }
        } else {
            $error = 'Verificación humana fallida';
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
        <form method="post" class="login-form" id="login-form">
            <input type="email" name="email" placeholder="Email">
            <input type="password" name="password" placeholder="Contraseña">
            <?php if(!empty($recaptchaSiteKey)): ?>
            <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
            <?php endif; ?>
            <button type="submit">Entrar</button>
        </form>
        <div class="login-links">
            <a href="register.php">Registrarse</a>
            <a href="recuperar_password.php">¿Olvidaste tu contraseña?</a>
        </div>
        <h3>O ingresa con</h3>
        <a class="social-btn google" href="oauth.php?provider=google">Google</a>
    </div>
</div>
</div>
<?php if(!empty($recaptchaSiteKey)): ?>
<script src="https://www.google.com/recaptcha/api.js?render=<?= $recaptchaSiteKey ?>"></script>
<script>
document.getElementById('login-form').addEventListener('submit', function(e) {
    e.preventDefault();
    grecaptcha.ready(function() {
        grecaptcha.execute('<?= $recaptchaSiteKey ?>', {action: 'login'}).then(function(token) {
            document.getElementById('g-recaptcha-response').value = token;
            e.target.submit();
        });
    });
});
</script>
<?php endif; ?>
</body>
</html>
