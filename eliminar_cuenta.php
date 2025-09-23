<?php
require 'config.php';
require_once 'session.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId        = (int) $_SESSION['user_id'];
$errorMessages = [];
$errorHtml     = '';
$success       = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmed = isset($_POST['confirm']) && $_POST['confirm'] === '1';

    if ($password === '') {
        $errorMessages[] = 'Introduce tu contraseña para confirmar.';
    }

    if (!$confirmed) {
        $errorMessages[] = 'Marca la casilla para confirmar que deseas eliminar tu cuenta.';
    }

    if (empty($errorMessages)) {
        $stmt = $pdo->prepare('SELECT pass_hash FROM usuarios WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['pass_hash'])) {
            $errorMessages[] = 'La contraseña no es correcta.';
        } else {
            try {
                $deleteStmt = $pdo->prepare('DELETE FROM usuarios WHERE id = ?');
                $deleteStmt->execute([$userId]);

                linkalooClearRememberMeToken($pdo);

                $_SESSION = [];
                if (ini_get('session.use_cookies')) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
                } else {
                    setcookie(session_name(), '', time() - 42000, '/');
                }

                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_destroy();
                }

                $success = true;
            } catch (Throwable $exception) {
                error_log('Error deleting account: ' . $exception->getMessage());
                $errorMessages[] = 'No se pudo eliminar la cuenta. Inténtalo más tarde.';
            }
        }
    }
}

if (!empty($errorMessages)) {
    $safeMessages = array_map(static function ($message) {
        return htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    }, $errorMessages);
    $errorHtml = implode('<br>', $safeMessages);
}

include 'header.php';
?>
<div class="login-wrapper">
    <div class="login-block">
        <?php if ($success): ?>
            <h2>Cuenta eliminada</h2>
            <p class="notice">Tu cuenta y todos tus datos se han eliminado correctamente.</p>
            <div class="login-links">
                <a href="index.php">Ir a la página principal</a>
                <a href="register.php">Crear una nueva cuenta</a>
            </div>
        <?php else: ?>
            <h2>Eliminar cuenta</h2>
            <p class="warning-text">Eliminar tu cuenta es irreversible. Se borrarán tus tableros, enlaces y cualquier otro dato asociado.</p>
            <?php if ($errorHtml !== ''): ?>
                <p class="error"><?= $errorHtml ?></p>
            <?php endif; ?>
            <form method="post" class="login-form">
                <input type="password" name="password" placeholder="Contraseña actual" required>
                <label class="checkbox-label">
                    <input type="checkbox" name="confirm" value="1" required>
                    <span>Confirmo que deseo eliminar mi cuenta y entiendo que esta acción no se puede deshacer.</span>
                </label>
                <button type="submit" class="danger">Eliminar cuenta</button>
            </form>
            <div class="login-links">
                <a href="cpanel.php">Cancelar y volver</a>
            </div>
        <?php endif; ?>
    </div>
</div>
</div>
</body>
</html>
