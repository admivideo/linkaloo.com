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

include 'header.php';
?>
<h2>Tableros</h2>
<ul>
<?php foreach($categorias as $categoria): ?>
    <li>
        <strong><?= htmlspecialchars($categoria['nombre']) ?></strong>
        <ul>
        <?php
            $stmtL = $pdo->prepare('SELECT url, titulo FROM links WHERE categoria_id = ? AND usuario_id = ?');
            $stmtL->execute([$categoria['id'], $user_id]);
            foreach($stmtL as $link){
                $mostrar = $link['titulo'] ?: $link['url'];
                echo '<li><a href="'.htmlspecialchars($link['url']).'" target="_blank">'.htmlspecialchars($mostrar).'</a></li>';
            }
        ?>
        </ul>
    </li>
<?php endforeach; ?>
</ul>

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
