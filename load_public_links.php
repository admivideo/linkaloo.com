<?php
require 'config.php';
require 'favicon_utils.php';
require_once 'device.php';

$token = $_GET['token'] ?? '';
if(empty($token)){
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([]);
    exit;
}

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
if($offset < 0){
    $offset = 0;
}
$limitParam = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
if($limitParam <= 0){
    $limitParam = 50;
}
$limit = min($limitParam, 500);

$stmt = $pdo->prepare('SELECT id FROM categorias WHERE share_token = ?');
$stmt->execute([$token]);
$board = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$board){
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([]);
    exit;
}

$sql = "SELECT url, titulo, descripcion, imagen FROM links WHERE categoria_id = ? ORDER BY id DESC LIMIT $limit OFFSET $offset";
$stmtLinks = $pdo->prepare($sql);
$stmtLinks->execute([$board['id']]);
$links = $stmtLinks->fetchAll();

$descLimit = isMobile() ? 50 : 150;
foreach($links as &$link){
    if(mb_strlen($link['titulo']) > 50){
        $link['titulo'] = mb_substr($link['titulo'], 0, 47) . '...';
    }
    if(!empty($link['descripcion']) && mb_strlen($link['descripcion']) > $descLimit){
        $link['descripcion'] = mb_substr($link['descripcion'], 0, $descLimit - 3) . '...';
    }
    $domain = parse_url($link['url'], PHP_URL_HOST);
    $link['favicon'] = $domain ? getLocalFavicon($domain) : '';
}
unset($link);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($links, JSON_UNESCAPED_UNICODE);
?>
