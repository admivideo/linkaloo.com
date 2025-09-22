<?php
require 'config.php';
require_once 'session.php';

$sharedParam = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sharedParam = trim($_POST['shared'] ?? '');
} elseif (isset($_GET['shared'])) {
    $sharedParam = trim($_GET['shared']);
}
if (!isValidSharedUrl($sharedParam)) {
    $sharedParam = '';
}

$encodedShared = $sharedParam !== '' ? rawurlencode($sharedParam) : '';
$registerUrl   = 'register.php' . ($encodedShared ? '?shared=' . $encodedShared : '');
$googleOauthUrl = 'oauth.php?provider=google' . ($encodedShared ? '&shared=' . $encodedShared : '');
$recaptchaConfigured = !empty($recaptchaSiteKey) && !empty($recaptchaSecretKey);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $captcha = $_POST['g-recaptcha-response'] ?? '';
    if ($email !== '' && $password !== '') {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Introduce un email válido';
        } else {
            $captchaValid = true;
            if ($recaptchaConfigured) {
                $captchaValid = false;
                $captchaError = 'Verificación humana fallida';
                if ($captcha !== '') {
                    $payload = [
                        'secret'   => $recaptchaSecretKey,
                        'response' => $captcha,
                    ];
                    if (!empty($_SERVER['REMOTE_ADDR'])) {
                        $payload['remoteip'] = $_SERVER['REMOTE_ADDR'];
                    }
                    $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
                    if ($ch !== false) {
                        curl_setopt_array($ch, [
                            CURLOPT_POST            => true,
                            CURLOPT_RETURNTRANSFER  => true,
                            CURLOPT_TIMEOUT         => 5,
                            CURLOPT_CONNECTTIMEOUT  => 3,
                            CURLOPT_POSTFIELDS      => http_build_query($payload),
                        ]);
                        $verifyResponse = curl_exec($ch);
                        if ($verifyResponse === false) {
                            error_log('Error verificando reCAPTCHA: ' . curl_error($ch));
                            $captchaError = 'No se pudo verificar que eres una persona. Inténtalo de nuevo.';
                        } else {
                            $data = json_decode($verifyResponse, true);
                            if (is_array($data)
                                && ($data['success'] ?? false)
                                && ($data['score'] ?? 0) >= 0.5
                                && ($data['action'] ?? '') === 'login') {
                                $captchaValid = true;
                            } else {
                                if (is_array($data) && isset($data['error-codes'])) {
                                    error_log('Fallo en verificación reCAPTCHA: ' . implode(', ', (array) $data['error-codes']));
                                } else {
                                    error_log('Respuesta inesperada de reCAPTCHA: ' . $verifyResponse);
                                }
                            }
                        }
                        curl_close($ch);
                    } else {
                        error_log('No se pudo inicializar cURL para reCAPTCHA.');
                        $captchaError = 'No se pudo verificar que eres una persona. Inténtalo de nuevo.';
                    }
                } else {
                    $captchaError = 'No se recibió respuesta de reCAPTCHA. Inténtalo de nuevo.';
                }
                if (!$captchaValid) {
                    $error = $captchaError;
                }
            }

            if ($error === '') {
                $stmt = $pdo->prepare('SELECT id, nombre, pass_hash FROM usuarios WHERE email = ?');
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                if ($user && password_verify($password, $user['pass_hash'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int) $user['id'];
                    $_SESSION['user_name'] = $user['nombre'];
                    linkalooIssueRememberMeToken($pdo, (int) $user['id']);
                    $redirect = 'panel.php';
                    if ($sharedParam !== '') {
                        $redirect = 'agregar_favolink.php?shared=' . rawurlencode($sharedParam);
                    }
                    header('Location: ' . $redirect);
                    exit;
                } else {
                    $error = 'Usuario o contraseña incorrectos';
                }
            }
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
            <input type="hidden" name="shared" value="<?= htmlspecialchars($sharedParam, ENT_QUOTES, 'UTF-8') ?>">
            <?php if($recaptchaConfigured): ?>
            <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
            <?php endif; ?>
            <button type="submit">Entrar</button>
        </form>
        <div class="login-links">
            <a href="<?= htmlspecialchars($registerUrl, ENT_QUOTES, 'UTF-8') ?>">Registrarse</a>
            <a href="recuperar_password.php">¿Olvidaste tu contraseña?</a>
        </div>
        <h3>O ingresa con</h3>
        <a class="social-btn google" href="<?= htmlspecialchars($googleOauthUrl, ENT_QUOTES, 'UTF-8') ?>">Google</a>
    </div>
</div>
</div>
<?php if($recaptchaConfigured): ?>
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
