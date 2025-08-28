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
    $stmt = $pdo->prepare('UPDATE links SET categoria_id = ? WHERE id = ? AND usuario_id = ?');
    $stmt->execute([$categoria_id, $id, $_SESSION['user_id']]);
    echo json_encode(['success' => $stmt->rowCount() > 0]);
} else {
    echo json_encode(['success' => false]);
}
