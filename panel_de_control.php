<?php
require 'config.php';
session_start();
if(!isset($_SESSION['user_id'])){
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(isset($_POST['board_name'])){
        $board_name = trim($_POST['board_name']);
        if($board_name){
            $stmt = $pdo->prepare('INSERT INTO boards (user_id, name) VALUES (?, ?)');
            $stmt->execute([$user_id, $board_name]);
        }
    } elseif(isset($_POST['link_url'])){
        $link_url = trim($_POST['link_url']);
        $link_title = trim($_POST['link_title']);
        $board_id = (int)$_POST['board_id'];
        if($link_url && $board_id){
            $stmt = $pdo->prepare('INSERT INTO links (board_id, url, title) VALUES (?, ?, ?)');
            $stmt->execute([$board_id, $link_url, $link_title]);
        }
    }
}

$stmt = $pdo->prepare('SELECT id, name FROM boards WHERE user_id = ?');
$stmt->execute([$user_id]);
$boards = $stmt->fetchAll();

include 'header.php';
?>
<h2>Tableros</h2>
<ul>
<?php foreach($boards as $board): ?>
    <li>
        <strong><?= htmlspecialchars($board['name']) ?></strong>
        <ul>
        <?php
            $stmtL = $pdo->prepare('SELECT url, title FROM links WHERE board_id = ?');
            $stmtL->execute([$board['id']]);
            foreach($stmtL as $link){
                echo '<li><a href="'.htmlspecialchars($link['url']).'" target="_blank">'.htmlspecialchars($link['title'] ?: $link['url']).'</a></li>';
            }
        ?>
        </ul>
    </li>
<?php endforeach; ?>
</ul>

<h3>Crear tablero</h3>
<form method="post">
    <input type="text" name="board_name" placeholder="Nombre del tablero">
    <button type="submit">Crear</button>
</form>

<h3>Guardar link</h3>
<form method="post">
    <input type="url" name="link_url" placeholder="URL" required>
    <input type="text" name="link_title" placeholder="Título">
    <select name="board_id">
    <?php foreach($boards as $board): ?>
        <option value="<?= $board['id'] ?>"><?= htmlspecialchars($board['name']) ?></option>
    <?php endforeach; ?>
    </select>
    <button type="submit">Guardar</button>
</form>
<?php include 'footer.php'; ?>
