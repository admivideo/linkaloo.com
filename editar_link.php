<?php
require 'config.php';
session_start();
if(!isset($_SESSION['user_id'])){
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare('SELECT * FROM links WHERE id = ? AND usuario_id = ?');
$stmt->execute([$id, $user_id]);
$link = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$link){
    header('Location: panel.php');
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $nota = trim($_POST['nota_link'] ?? '');
    $upd = $pdo->prepare('UPDATE links SET nota_link = ? WHERE id = ? AND usuario_id = ?');
    $upd->execute([$nota, $id, $user_id]);
    $stmt->execute([$id, $user_id]);
    $link = $stmt->fetch(PDO::FETCH_ASSOC);
}

include 'header.php';
$title = $link['titulo'] ?: $link['url'];
$domain = parse_url($link['url'], PHP_URL_HOST);
?>
<div class="board-detail">
    <div class="board-detail-image">
        <?php if(!empty($link['imagen'])): ?>
            <img src="<?= htmlspecialchars($link['imagen']) ?>" alt="<?= htmlspecialchars($title) ?>">
        <?php endif; ?>
    </div>
    <div class="board-detail-info">
        <div class="link-header">
            <img src="https://www.google.com/s2/favicons?domain=<?= urlencode($domain) ?>" width="20" height="20" alt="">
            <h2><?= htmlspecialchars($title) ?></h2>
        </div>
        <?php if(!empty($link['creado_en'])): ?>
            <p class="created-at">Creado el <?= date('Y-m-d', strtotime($link['creado_en'])) ?></p>
        <?php endif; ?>
        <form method="post" class="board-detail-form">
            <p><a href="<?= htmlspecialchars($link['url']) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($link['url']) ?></a></p>
            <?php if(!empty($link['descripcion'])): ?>
                <p><?= htmlspecialchars($link['descripcion']) ?></p>
            <?php endif; ?>
            <label>Nota<br>
                <textarea name="nota_link"><?= htmlspecialchars($link['nota_link'] ?? '') ?></textarea>
            </label>
            <button type="submit">Guardar</button>
        </form>
    </div>
</div>
</div>
</body>
</html>
