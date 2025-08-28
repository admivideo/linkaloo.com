<?php
require 'config.php';
session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        $stmt = $pdo->prepare('SELECT id, password FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header('Location: panel_de_control.php');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos';
        }
    } else {
        $error = 'Introduce usuario y contraseña';
    }
}
include 'header.php';
?>
<h2>Iniciar sesión</h2>
<?php if($error): ?><p style="color:red;"><?= $error ?></p><?php endif; ?>
<form method="post">
    <label>Usuario: <input type="text" name="username"></label><br>
    <label>Contraseña: <input type="password" name="password"></label><br>
    <button type="submit">Entrar</button>
</form>
<div class="social-login">
    <p>O iniciar con:</p>
    <a href="oauth.php?provider=google">Google</a> |
    <a href="oauth.php?provider=facebook">Facebook</a> |
    <a href="oauth.php?provider=instagram">Instagram</a>
</div>
<?php include 'footer.php'; ?>
