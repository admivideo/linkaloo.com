<?php
require 'config.php';
require_once 'session.php';

$categorias = [];
if (isset($_SESSION['user_id'])) {
    $stmtUserCats = $pdo->prepare('SELECT id, nombre FROM categorias WHERE usuario_id = ? ORDER BY modificado_en DESC');
    $stmtUserCats->execute([$_SESSION['user_id']]);
    $categorias = $stmtUserCats->fetchAll();
}

define('TOPFAVOLINKS_EMBED', true);

include 'header.php';
include 'ontop.php';
?>
</div>
</body>
</html>
