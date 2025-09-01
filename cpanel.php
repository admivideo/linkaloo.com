<?php
require 'config.php';
session_start();
if(!isset($_SESSION['user_id'])){
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$message = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    if($nombre && $email){
        $stmt = $pdo->prepare('UPDATE usuarios SET nombre = ?, email = ? WHERE id = ?');
        $stmt->execute([$nombre, $email, $user_id]);
        $_SESSION['user_name'] = $nombre;
        $message = 'Datos actualizados';
    } else {
        $message = 'Rellena todos los campos';
    }
}

$stmt = $pdo->prepare('SELECT nombre, email FROM usuarios WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

include 'header.php';
?>
<div class="login-wrapper">
    <div class="login-block">
        <h2>Mi cuenta</h2>
        <?php if($message): ?><p class="notice"><?= htmlspecialchars($message) ?></p><?php endif; ?>
        <form method="post" class="login-form">
            <input type="text" name="nombre" value="<?= htmlspecialchars($user['nombre'] ?? '') ?>" placeholder="Nombre">
            <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="Email">
            <button type="submit">Guardar</button>
        </form>
        <div class="login-links">
            <a href="cambiar_password.php">Cambiar contraseña</a>
            <a href="logout.php">Salir</a>
        </div>
    </div>
</div>
</div>
</body>
</html>
