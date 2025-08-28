<?php
require 'config.php';
session_start();
if(!isset($_SESSION['user_id'])){
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(isset($_POST['categoria_nombre'])){
        $categoria_nombre = trim($_POST['categoria_nombre']);
        if($categoria_nombre){
            $stmt = $pdo->prepare('INSERT INTO categorias (usuario_id, nombre) VALUES (?, ?)');
            $stmt->execute([$user_id, $categoria_nombre]);
        }
    } elseif(isset($_POST['link_url'])){
        $link_url = trim($_POST['link_url']);
        $link_title = trim($_POST['link_title']);
        $categoria_id = (int)$_POST['categoria_id'];
        if($link_url && $categoria_id){
            $hash = sha1($link_url);
            $stmt = $pdo->prepare('INSERT INTO links (usuario_id, categoria_id, url, titulo, hash_url) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$user_id, $categoria_id, $link_url, $link_title, $hash]);
        }
    }
}

$stmt = $pdo->prepare('SELECT id, nombre FROM categorias WHERE usuario_id = ?');
$stmt->execute([$user_id]);
$categorias = $stmt->fetchAll();

$stmtL = $pdo->prepare('SELECT categoria_id, url, titulo, descripcion, imagen FROM links WHERE usuario_id = ?');
$stmtL->execute([$user_id]);
$links = $stmtL->fetchAll();

include 'header.php';
?>
<div class="board-slider">
<?php foreach($categorias as $index => $categoria): ?>
    <button class="board-btn<?= $index === 0 ? ' active' : '' ?>" data-cat="<?= $categoria['id'] ?>">
        <?= htmlspecialchars($categoria['nombre']) ?>
    </button>
<?php endforeach; ?>
</div>

<div class="link-cards">
<?php foreach($links as $link): ?>
    <div class="card" data-cat="<?= $link['categoria_id'] ?>">
        <?php if(!empty($link['imagen'])): ?>
            <img src="<?= htmlspecialchars($link['imagen']) ?>" alt="">
        <?php endif; ?>
        <div class="card-body">
            <h4><?= htmlspecialchars($link['titulo'] ?: $link['url']) ?></h4>
            <?php if(!empty($link['descripcion'])): ?>
                <p><?= htmlspecialchars($link['descripcion']) ?></p>
            <?php endif; ?>
            <a href="<?= htmlspecialchars($link['url']) ?>" target="_blank"><?= htmlspecialchars($link['url']) ?></a>
        </div>
    </div>
<?php endforeach; ?>
</div>

<h3>Crear tablero</h3>
<form method="post">
    <input type="text" name="categoria_nombre" placeholder="Nombre del tablero">
    <button type="submit">Crear</button>
</form>

<h3>Guardar link</h3>
<form method="post">
    <input type="url" name="link_url" placeholder="URL" required>
    <input type="text" name="link_title" placeholder="Título">
    <select name="categoria_id">
    <?php foreach($categorias as $categoria): ?>
        <option value="<?= $categoria['id'] ?>"><?= htmlspecialchars($categoria['nombre']) ?></option>
    <?php endforeach; ?>
    </select>
    <button type="submit">Guardar</button>
</form>
<?php include 'footer.php'; ?>
