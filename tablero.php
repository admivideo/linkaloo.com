<?php
require 'config.php';
session_start();
if(!isset($_SESSION['user_id'])){
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare(
    'SELECT c.*, COUNT(l.id) AS total_links, ' .
    '       (SELECT l2.imagen FROM links l2 WHERE l2.categoria_id = c.id AND l2.usuario_id = ? ORDER BY l2.id LIMIT 1) AS imagen ' .
    'FROM categorias c ' .
    'LEFT JOIN links l ON l.categoria_id = c.id AND l.usuario_id = ? ' .
    'WHERE c.id = ? AND c.usuario_id = ?'
);
$stmt->execute([$user_id, $user_id, $id, $user_id]);
$board = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$board){
    header('Location: tableros.php');
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $nombre = trim($_POST['nombre'] ?? '');
    $nota = trim($_POST['nota'] ?? '');
    $upd = $pdo->prepare('UPDATE categorias SET nombre = ?, nota = ? WHERE id = ? AND usuario_id = ?');
    $upd->execute([$nombre, $nota, $id, $user_id]);
    $stmt->execute([$user_id, $user_id, $id, $user_id]);
    $board = $stmt->fetch(PDO::FETCH_ASSOC);
}

$creado = $board['creado_en'] ? date('Y-m', strtotime($board['creado_en'])) : '';
$modificado = $board['modificado_en'] ? date('Y-m', strtotime($board['modificado_en'])) : '';

include 'header.php';
?>
<div class="board-detail">
    <div class="board-detail-image">
        <?php if(!empty($board['imagen'])): ?>
            <img src="<?= htmlspecialchars($board['imagen']) ?>" alt="<?= htmlspecialchars($board['nombre']) ?>">
        <?php endif; ?>
    </div>
    <div class="board-detail-info">
        <h2><?= htmlspecialchars($board['nombre']) ?></h2>
        <form method="post" class="board-detail-form">
            <label>Nombre<br>
                <input type="text" name="nombre" value="<?= htmlspecialchars($board['nombre']) ?>">
            </label>
            <label>Nota<br>
                <textarea name="nota"><?= htmlspecialchars($board['nota'] ?? '') ?></textarea>
            </label>
            <p>Links guardados: <a class="links-link" href="panel_de_control.php?cat=<?= $id ?>"><?= $board['total_links'] ?></a></p>
            <p>Creado: <?= htmlspecialchars($creado) ?></p>
            <p>Modificado: <?= htmlspecialchars($modificado) ?></p>
            <button type="submit">Guardar</button>
        </form>
    </div>
</div>
<?php include 'footer.php'; ?>
