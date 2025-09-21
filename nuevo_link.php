<?php
require 'config.php';
require_once 'session.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$selectedCat = isset($_GET['cat']) ? (int) $_GET['cat'] : 0;

$stmt = $pdo->prepare('SELECT id, nombre FROM categorias WHERE usuario_id = ? ORDER BY modificado_en DESC');
$stmt->execute([$user_id]);
$categorias = $stmt->fetchAll();

$sharedPrefill = '';
if (isset($_GET['shared'])) {
    $candidate = trim($_GET['shared']);
    if ($candidate !== '' && isValidSharedUrl($candidate)) {
        $sharedPrefill = $candidate;
    }
}

include 'header.php';
?>
<div class="add-link-page">
    <div class="add-link-card">
        <div class="app-logo"><img src="/img/logo_linkaloo_blue.png" alt="Linkaloo logo"></div>
        <h2 class="modal-title">Añadir link</h2>
        <div class="control-forms">
            <div class="form-section">
                <form method="post" action="panel.php" class="form-link">
                    <input type="url" name="link_url" placeholder="Pega aquí tu link" value="<?= htmlspecialchars($sharedPrefill, ENT_QUOTES, 'UTF-8') ?>" required>
                    <input type="text" name="link_title" placeholder="Título (opcional)" maxlength="50">
                    <div class="select-create">
                        <select name="categoria_id">
                            <option value="">Elige el tablero</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= $categoria['id'] ?>" <?= $categoria['id'] == $selectedCat ? 'selected' : '' ?>><?= htmlspecialchars($categoria['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="categoria_nombre" placeholder="o crea uno nuevo (opcional)">
                    </div>
                    <button type="submit">Guardar favolink</button>
                </form>
                <a class="back-to-panel" href="panel.php">Volver al panel</a>
            </div>
        </div>
    </div>
</div>
</div>
</body>
</html>
