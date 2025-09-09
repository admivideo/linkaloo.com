<?php
require 'config.php';
session_start();
header('Content-Type: application/json');
if(!isset($_SESSION['user_id'])){
    echo json_encode(['success'=>false]);
    exit;
}
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if($id){
    $catStmt = $pdo->prepare('SELECT categoria_id FROM links WHERE id = ? AND usuario_id = ?');
    $catStmt->execute([$id, $_SESSION['user_id']]);
    $catId = $catStmt->fetchColumn();
    $stmt = $pdo->prepare('DELETE FROM links WHERE id = ? AND usuario_id = ?');
    $stmt->execute([$id, $_SESSION['user_id']]);
    if($stmt->rowCount() > 0){
        if($catId){
            $upd = $pdo->prepare('UPDATE categorias SET modificado_en = NOW() WHERE id = ?');
            $upd->execute([$catId]);
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success'=>false]);
}
