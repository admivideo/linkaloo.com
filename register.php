<?php
require 'config.php';
session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = 'El usuario ya existe';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
            $stmt->execute([$username, $hash]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            header('Location: panel_de_control.php');
            exit;
        }
    } else {
        $error = 'Rellena todos los campos';
    }
}
include 'header.php';
?>
<h2>Registro</h2>
<?php if($error): ?><p style="color:red;"><?= $error ?></p><?php endif; ?>
<form method="post">
    <label>Usuario: <input type="text" name="username"></label><br>
    <label>Contraseña: <input type="password" name="password"></label><br>
    <button type="submit">Registrarse</button>
</form>
<?php include 'footer.php'; ?>
