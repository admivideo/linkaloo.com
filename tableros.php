<?php
require 'config.php';
require_once 'session.php';
if(!isset($_SESSION['user_id'])){
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['board_name'])) {
        $name = trim($_POST['board_name']);
        if ($name) {
            $stmt = $pdo->prepare('INSERT INTO categorias (usuario_id, nombre) VALUES (?, ?)');
            $stmt->execute([$user_id, $name]);
        }
    }
}

$stmt = $pdo->prepare('SELECT c.id, c.nombre, c.share_token,
                              COUNT(l.id) AS total,
                              (SELECT l2.imagen FROM links l2
                               WHERE l2.categoria_id = c.id AND l2.usuario_id = ?
                               ORDER BY l2.id LIMIT 1) AS imagen
                         FROM categorias c
                         LEFT JOIN links l ON l.categoria_id = c.id AND l.usuario_id = ?
                         WHERE c.usuario_id = ?
                         GROUP BY c.id, c.nombre, c.share_token
                         ORDER BY c.creado_en DESC');
$stmt->execute([$user_id, $user_id, $user_id]);
$boards = $stmt->fetchAll();

$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];

include 'header.php';
?>
<div class="board-admin">
    <h2>Mis tableros</h2>
    <form method="post" class="board-create">
        <input type="text" name="board_name" placeholder="Nombre del tablero">
        <button type="submit">Crear</button>
    </form>
    <div class="board-grid">
    <?php foreach($boards as $board): ?>
        <div class="board-item">
            <a href="panel.php?cat=<?= $board['id'] ?>" class="board-link">
                <div class="board-thumb">
                <?php if(!empty($board['imagen'])): ?>
                    <img src="<?= htmlspecialchars($board['imagen']) ?>" alt="<?= htmlspecialchars($board['nombre']) ?>" loading="lazy">
                <?php endif; ?>
                    <span class="link-count"><i data-feather="link"></i><?= $board['total'] ?></span>
                </div>
                <span class="board-name"><?= htmlspecialchars($board['nombre']) ?></span>
            </a>
            <?php if(!empty($board['share_token'])): ?>
            <button type="button" class="share-board" data-url="<?= htmlspecialchars($baseUrl . '/tablero_publico.php?token=' . $board['share_token']) ?>" aria-label="Compartir">
                <i data-feather="share-2"></i>
            </button>
            <?php endif; ?>
            <a href="tablero.php?id=<?= $board['id'] ?>" class="edit-board" aria-label="Editar">
                <i data-feather="edit-2"></i>
            </a>
        </div>
    <?php endforeach; ?>
    </div>
</div>
</div>
</body>
</html>
