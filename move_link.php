<?php
require 'config.php';
require_once 'session.php';
header('Content-Type: application/json');
if(!isset($_SESSION['user_id'])){
    echo json_encode(['success' => false]);
    exit;
}
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$categoria_id = isset($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : 0;
if($id && $categoria_id){
    $sel = $pdo->prepare('SELECT categoria_id FROM links WHERE id = ? AND usuario_id = ?');
    $sel->execute([$id, $_SESSION['user_id']]);
    $oldCat = $sel->fetchColumn();
    $stmt = $pdo->prepare('UPDATE links SET categoria_id = ? WHERE id = ? AND usuario_id = ?');
    $stmt->execute([$categoria_id, $id, $_SESSION['user_id']]);
    if ($stmt->rowCount() > 0) {
        $update = $pdo->prepare('UPDATE categorias SET modificado_en = NOW() WHERE id IN (?, ?)');
        $update->execute([$categoria_id, $oldCat]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
