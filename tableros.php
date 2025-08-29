<?php
require 'config.php';
session_start();
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
    } elseif (isset($_POST['delete_id'])) {
        $id = (int)$_POST['delete_id'];
        $stmt = $pdo->prepare('DELETE FROM categorias WHERE id = ? AND usuario_id = ?');
        $stmt->execute([$id, $user_id]);
    }
}

$stmt = $pdo->prepare('SELECT c.id, c.nombre,
                              COUNT(l.id) AS total,
                              (SELECT l2.imagen FROM links l2
                               WHERE l2.categoria_id = c.id AND l2.usuario_id = ?
                               ORDER BY l2.id LIMIT 1) AS imagen
                         FROM categorias c
                         LEFT JOIN links l ON l.categoria_id = c.id AND l.usuario_id = ?
                         WHERE c.usuario_id = ?
                         GROUP BY c.id, c.nombre
                         ORDER BY c.id');
$stmt->execute([$user_id, $user_id, $user_id]);
$boards = $stmt->fetchAll();

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
            <a href="panel_de_control.php?cat=<?= $board['id'] ?>" class="board-link">
            <?php if(!empty($board['imagen'])): ?>
                <img src="<?= htmlspecialchars($board['imagen']) ?>" alt="<?= htmlspecialchars($board['nombre']) ?>">
            <?php endif; ?>
            <span class="board-name"><?= htmlspecialchars($board['nombre']) ?></span>
            <span class="count"><?= $board['total'] ?> links guardados</span>
            </a>
            <a href="tablero.php?id=<?= $board['id'] ?>" class="edit-board" aria-label="Editar">✏️</a>
            <form method="post">
                <input type="hidden" name="delete_id" value="<?= $board['id'] ?>">
                <button type="submit" class="delete-board" aria-label="Eliminar">🗑️</button>
            </form>
        </div>
    <?php endforeach; ?>
    </div>
</div>
<?php include 'footer.php'; ?>
