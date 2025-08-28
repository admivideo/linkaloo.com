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
    $stmt = $pdo->prepare('DELETE FROM links WHERE id = ? AND usuario_id = ?');
    $stmt->execute([$id, $_SESSION['user_id']]);
    echo json_encode(['success' => $stmt->rowCount() > 0]);
} else {
    echo json_encode(['success'=>false]);
}
