<?php
require 'config.php';
require_once 'session.php';
if(!isset($_SESSION['user_id'])){
    header('Location: login.php');
    exit;
}
$sharedParam = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sharedParam = trim($_POST['shared'] ?? '');
} elseif (isset($_GET['shared'])) {
    $sharedParam = trim($_GET['shared']);
}
if (!isValidSharedUrl($sharedParam)) {
    $sharedParam = '';
}
$encodedShared = $sharedParam !== '' ? rawurlencode($sharedParam) : '';
$skipUrl = 'panel.php' . ($encodedShared ? '?shared=' . $encodedShared : '');
$user_id = $_SESSION['user_id'];

$predefinedBoards = [
    'Recetas',
    'Humor',
    'Noticias',
    'Moda',
    'Decoración',
    'Música',
    'Cine y series',
    'Deportes',
    'Escapadas',
    'Tecnología',
    'Belleza',
    'Fitness',
    'Mascotas',
    'Desarrollo personal',
    'Política',
    'Negocios',
    'Compras',
    'Coches',
    'Motos',
];

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $selected = $_POST['boards'] ?? [];
    if(is_array($selected)){
        $stmt = $pdo->prepare('INSERT INTO categorias (usuario_id, nombre) VALUES (?, ?)');
        foreach($selected as $board){
            $board = trim($board);
            if($board){
                $stmt->execute([$user_id, $board]);
            }
        }
    }
    $redirect = $skipUrl;
    header('Location: ' . $redirect);
    exit;
}

include 'header.php';
?>
<div class="board-select">
    <h2>Elige tus intereses</h2>
    <p>así vamos creando tus tableros</p>
    <form method="post">
        <input type="hidden" name="shared" value="<?= htmlspecialchars($sharedParam, ENT_QUOTES, 'UTF-8') ?>">
        <div class="board-options">
            <?php foreach($predefinedBoards as $board): ?>
            <label class="board-option">
                <input type="checkbox" name="boards[]" value="<?= htmlspecialchars($board) ?>">
                <span><?= htmlspecialchars($board) ?></span>
            </label>
            <?php endforeach; ?>
        </div>
        <div class="board-select-buttons">
            <a href="<?= htmlspecialchars($skipUrl, ENT_QUOTES, 'UTF-8') ?>" class="skip-btn">Omitir</a>
            <button type="submit" class="next-btn">Siguiente</button>
        </div>
    </form>
</div>
</div>
</body>
</html>
