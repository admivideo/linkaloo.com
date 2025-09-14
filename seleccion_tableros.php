<?php
require 'config.php';
require_once 'session.php';
if(!isset($_SESSION['user_id'])){
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

$predefinedBoards = [
    'Ciencias y educación',
    'Deportes',
    'Fitness y salud',
    'Música',
    'Comedia',
    'Comida y bebida',
    'Automoción y vehículos',
    'DIY',
    'Animales',
    'Belleza y estilo',
    'Viajes',
    'Motivación y consejos',
    'Gaming',
    'Entretenimiento',
    'Arte',
    'Trucos para la vida cotidiana',
    'Actividades al aire libre',
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
    header('Location: panel.php');
    exit;
}

include 'header.php';
?>
<div class="board-select">
    <h2>Elige tus intereses</h2>
    <p>Recibe mejores sugerencias de vídeos</p>
    <form method="post">
        <div class="board-options">
            <?php foreach($predefinedBoards as $board): ?>
            <label class="board-option">
                <input type="checkbox" name="boards[]" value="<?= htmlspecialchars($board) ?>">
                <span><?= htmlspecialchars($board) ?></span>
            </label>
            <?php endforeach; ?>
        </div>
        <div class="board-select-buttons">
            <a href="panel.php" class="skip-btn">Omitir</a>
            <button type="submit" class="next-btn">Siguiente</button>
        </div>
    </form>
</div>
</div>
</body>
</html>
