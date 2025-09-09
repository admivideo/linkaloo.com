<?php
require 'config.php';
session_start();
header('Content-Type: application/json');
if(!isset($_SESSION['user_id'])){
    echo json_encode(['success' => false]);
    exit;
}
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$categoria_id = isset($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : 0;
if($id && $categoria_id){
    $pdo->beginTransaction();
    $oldStmt = $pdo->prepare('SELECT categoria_id FROM links WHERE id = ? AND usuario_id = ?');
    $oldStmt->execute([$id, $_SESSION['user_id']]);
    $oldCat = $oldStmt->fetchColumn();
    $stmt = $pdo->prepare('UPDATE links SET categoria_id = ? WHERE id = ? AND usuario_id = ?');
    $stmt->execute([$categoria_id, $id, $_SESSION['user_id']]);
    if($stmt->rowCount() > 0){
        if($oldCat){
            $upd = $pdo->prepare('UPDATE categorias SET modificado_en = NOW() WHERE id IN (?, ?)');
            $upd->execute([$categoria_id, $oldCat]);
        } else {
            $upd = $pdo->prepare('UPDATE categorias SET modificado_en = NOW() WHERE id = ?');
            $upd->execute([$categoria_id]);
        }
        $pdo->commit();
        echo json_encode(['success' => true]);
    } else {
        $pdo->rollBack();
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
