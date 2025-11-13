<?php
require 'config.php';
require 'favicon_utils.php';
require_once 'image_utils.php';
require_once 'session.php';
require_once 'device.php';

$token = $_GET['token'] ?? '';
if(!$token){
    http_response_code(404);
    exit('Tablero no disponible');
}

$stmt = $pdo->prepare('SELECT c.id, c.nombre, c.nota, (SELECT l2.imagen FROM links l2 WHERE l2.categoria_id = c.id ORDER BY l2.id LIMIT 1) AS imagen FROM categorias c WHERE share_token = ?');
$stmt->execute([$token]);
$board = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$board){
    http_response_code(404);
    exit('Tablero no disponible');
}

$isMobile = isMobile();
$totalStmt = $pdo->prepare('SELECT COUNT(*) FROM links WHERE categoria_id = ?');
$totalStmt->execute([$board['id']]);
$totalLinks = (int)$totalStmt->fetchColumn();
$linksQuery = 'SELECT url, titulo, descripcion, imagen FROM links WHERE categoria_id = ? ORDER BY id DESC';
if(!$isMobile){
    $linksQuery .= ' LIMIT 500';
}
$linksStmt = $pdo->prepare($linksQuery);
$linksStmt->execute([$board['id']]);
$links = $linksStmt->fetchAll();
$loadedLinks = count($links);
$descLimit = $isMobile ? 50 : 150;

$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
$shareUrl = $baseUrl . '/tablero_publico.php?token=' . $token;
$shareImg = $board['imagen'] ?? '';
if (!empty($shareImg) && !preg_match('#^https?://#', $shareImg)) {
    $shareImg = $baseUrl . '/' . ltrim($shareImg, '/');
}

include 'header.php';
?>
<div class="board-detail">
    <div class="board-detail-info">
        <div class="detail-header">
            <h2><?= htmlspecialchars($board['nombre']) ?></h2>
            <button type="button" class="share-board" data-url="<?= htmlspecialchars($shareUrl) ?>" data-title="<?= htmlspecialchars($board['nombre']) ?>" <?= !empty($shareImg) ? 'data-image="' . htmlspecialchars($shareImg) . '"' : '' ?> aria-label="Compartir"><i data-feather="share-2"></i></button>
        </div>
        <?php if(!empty($board['nota'])): ?>
        <p><?= htmlspecialchars($board['nota']) ?></p>
        <?php endif; ?>
    </div>
</div>
<?php if(!empty($links)): ?>
<div class="link-cards board-links" data-mode="public" data-token="<?= htmlspecialchars($token, ENT_QUOTES) ?>" data-total="<?= $totalLinks ?>" data-loaded="<?= $loadedLinks ?>">
<?php foreach($links as $link): ?>
    <?php
        $domain = parse_url($link['url'], PHP_URL_HOST);
        $favicon = $domain ? getLocalFavicon($domain) : '';
        $imgSrc = !empty($link['imagen']) ? $link['imagen'] : $favicon;
        $isDefault = empty($link['imagen']);
        $isLocalFavicon = str_starts_with($imgSrc, '/local_favicons/');
        $title = $link['titulo'] ?: $link['url'];
        if (mb_strlen($title) > 50) {
            $title = mb_substr($title, 0, 47) . '...';
        }
        $desc = $link['descripcion'] ?? '';
        if (mb_strlen($desc) > $descLimit) {
            $desc = mb_substr($desc, 0, $descLimit - 3) . '...';
        }
    ?>
    <div class="card">
        <div class="card-image <?= $isDefault ? 'no-image' : '' ?> <?= $isLocalFavicon ? 'local-favicon' : '' ?>">
            <a href="<?= htmlspecialchars($link['url']) ?>" target="_blank" rel="noopener noreferrer">
                <img src="<?= htmlspecialchars($imgSrc) ?>" alt="" loading="lazy">
            </a>
            <button class="share-btn" data-url="<?= htmlspecialchars($link['url']) ?>" aria-label="Compartir"><i data-feather="share-2"></i></button>
        </div>
        <div class="card-body">
            <div class="card-title">
                <h4><img src="<?= htmlspecialchars($favicon) ?>" width="18" height="18" alt="" loading="lazy"><?= htmlspecialchars($title) ?></h4>
            </div>
            <?php if(!empty($desc)): ?>
            <p><?= htmlspecialchars($desc) ?></p>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php else: ?>
<p>No hay links en este tablero.</p>
<?php endif; ?>
</div>
</body>
</html>
