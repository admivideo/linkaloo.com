<?php
require 'config.php';
require_once 'session.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId          = (int) $_SESSION['user_id'];
$accountDeleted  = false;
$errorMessage    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
        }

        $stmt = $pdo->prepare('SELECT email FROM usuarios WHERE id = ? FOR UPDATE');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            $pdo->rollBack();
            $errorMessage = 'No se encontró la cuenta.';
        } else {
            $currentEmail = $user['email'] ?? '';
            $suffix       = '*-*';
            $newEmail     = str_ends_with($currentEmail, $suffix) ? $currentEmail : $currentEmail . $suffix;

            $updateStmt = $pdo->prepare('UPDATE usuarios SET email = ? WHERE id = ?');
            $updateStmt->execute([$newEmail, $userId]);

            $tokenStmt = $pdo->prepare('DELETE FROM usuario_tokens WHERE usuario_id = ?');
            $tokenStmt->execute([$userId]);

            $pdo->commit();

            $accountDeleted = true;
            linkalooClearRememberMeToken($pdo);

            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            } else {
                setcookie(session_name(), '', time() - 42000, '/');
            }
            session_destroy();
        }
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Error al eliminar la cuenta: ' . $exception->getMessage());
        $errorMessage = 'No se pudo eliminar la cuenta. Inténtalo de nuevo más tarde.';
    }
}

include 'header.php';
?>
<div class="login-wrapper">
    <div class="login-block">
        <h2>Eliminar cuenta</h2>
        <?php if ($accountDeleted): ?>
            <p class="notice">Su cuenta ha sido eliminada</p>
            <div class="login-links">
                <a href="/index.php">Ir al inicio</a>
                <a href="/register.php">Crear una cuenta nueva</a>
            </div>
        <?php else: ?>
            <p>Esta acción es permanente. Si eliminas tu cuenta no podrás acceder de nuevo con tus credenciales actuales.</p>
            <?php if ($errorMessage): ?><p class="error"><?= htmlspecialchars($errorMessage) ?></p><?php endif; ?>
            <form method="post" class="login-form">
                <button type="submit">Eliminar cuenta</button>
            </form>
            <div class="login-links">
                <a href="/cpanel.php">Cancelar y volver</a>
            </div>
        <?php endif; ?>
    </div>
</div>
</div>
</body>
</html>
