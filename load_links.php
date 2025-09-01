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
$cat = isset($_GET['cat']) ? $_GET['cat'] : 'all';

$sql = "SELECT id, categoria_id, url, titulo, descripcion, imagen FROM links WHERE usuario_id = ?";
$params = [$user_id];
if($cat !== 'all'){
    $sql .= " AND categoria_id = ?";
    $params[] = (int)$cat;
}
$sql .= " ORDER BY creado_en DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$links = $stmt->fetchAll();

function truncateText($text, $limit) {
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit - 3) . '...' : $text;
    }
    return strlen($text) > $limit ? substr($text, 0, $limit - 3) . '...' : $text;
}

foreach($links as &$link){
    $link['titulo'] = truncateText($link['titulo'], 50);
    if(!empty($link['descripcion'])){
        $link['descripcion'] = truncateText($link['descripcion'], 250);
    }
}
unset($link);
header('Content-Type: application/json');
echo json_encode($links);
?>
