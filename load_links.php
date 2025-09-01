<?php
require 'config.php';
session_start();
if(!isset($_SESSION['user_id'])){
    http_response_code(401);
    exit;
}
$user_id = (int)$_SESSION['user_id'];
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = 18;
$stmt = $pdo->prepare("SELECT id, categoria_id, url, titulo, descripcion, imagen FROM links WHERE usuario_id = ? ORDER BY creado_en DESC LIMIT $limit OFFSET $offset");
$stmt->execute([$user_id]);
$links = $stmt->fetchAll();
header('Content-Type: application/json');
echo json_encode($links);
?>
