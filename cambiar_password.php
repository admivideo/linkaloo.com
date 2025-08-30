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
    $actual = $_POST['actual'] ?? '';
    $nueva = $_POST['nueva'] ?? '';
    if($actual && $nueva){
        $stmt = $pdo->prepare('SELECT pass_hash FROM usuarios WHERE id = ?');
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        if($user && password_verify($actual, $user['pass_hash'])){
            $hash = password_hash($nueva, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('UPDATE usuarios SET pass_hash = ? WHERE id = ?');
            $stmt->execute([$hash, $user_id]);
            $message = 'Contraseña actualizada';
        } else {
            $message = 'Contraseña actual incorrecta';
        }
    } else {
        $message = 'Completa todos los campos';
    }
}

include 'header.php';
?>
<div class="login-wrapper">
    <div class="login-block">
        <h2>Cambiar contraseña</h2>
        <?php if($message): ?><p class="notice"><?= htmlspecialchars($message) ?></p><?php endif; ?>
        <form method="post" class="login-form">
            <input type="password" name="actual" placeholder="Contraseña actual">
            <input type="password" name="nueva" placeholder="Nueva contraseña">
            <button type="submit">Actualizar</button>
        </form>
        <div class="login-links">
            <a href="cpanel.php">Volver al perfil</a>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
