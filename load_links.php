<?php
require 'config.php';
require 'favicon_utils.php';
require_once 'session.php';
require_once 'device.php';
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
