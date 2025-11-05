<?php
require 'config.php';
require 'favicon_utils.php';
require_once 'session.php';
require_once 'device.php';
if(!isset($_SESSION['user_id'])){
    $query = $_SERVER['QUERY_STRING'] ?? '';
    $target = 'login.php' . ($query ? '?' . $query : '');
    header('Location: ' . $target);
    exit;
}
$user_id = $_SESSION['user_id'];
// Categoría seleccionada (0 = todas)
$selectedCat = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$descLimit = isMobile() ? 50 : 150;
// Recuperar mensajes de error tras un posible redirect
$error = $_SESSION['panel_error'] ?? '';
unset($_SESSION['panel_error']);

if(isset($_GET['shared'])){
    $sharedIncoming = trim($_GET['shared']);
    if(isValidSharedUrl($sharedIncoming)){
        $redirect = 'agregar_favolink.php?shared=' . rawurlencode($sharedIncoming);
        header('Location: ' . $redirect);
        exit;
    }
}
$stmt = $pdo->prepare('SELECT id, nombre FROM categorias WHERE usuario_id = ? ORDER BY modificado_en DESC');
$stmt->execute([$user_id]);
$categorias = $stmt->fetchAll();

if ($selectedCat) {
    $stmtL = $pdo->prepare("SELECT id, categoria_id, url, titulo, descripcion, imagen FROM links WHERE usuario_id = ? AND categoria_id = ? ORDER BY creado_en DESC");
    $stmtL->execute([$user_id, $selectedCat]);
} else {
    $stmtL = $pdo->prepare("SELECT id, categoria_id, url, titulo, descripcion, imagen FROM links WHERE usuario_id = ? ORDER BY creado_en DESC");
    $stmtL->execute([$user_id]);
}
$links = $stmtL->fetchAll();

$catCounts = [];
foreach ($links as $l) {
    $cat = $l['categoria_id'];
    $catCounts[$cat] = ($catCounts[$cat] ?? 0) + 1;
}
$maxAdsPerCat = [];
foreach ($catCounts as $cat => $cnt) {
    $maxAdsPerCat[$cat] = intdiv($cnt, 8);
}

include 'header.php';
?>
<?php if(!empty($error)): ?>
<div class="alert">
    <span><?= htmlspecialchars($error) ?></span>
    <button class="alert-close" aria-label="Cerrar">&times;</button>
</div>
<?php endif; ?>
<div class="board-nav">
    <button type="button" class="board-scroll left" aria-label="Anterior"><i data-feather="chevron-left"></i></button>
    <div class="board-slider">
        <a href="panel.php" class="board-btn<?= $selectedCat === 0 ? ' active' : '' ?>" data-cat="all">Todo</a>
    <?php foreach($categorias as $categoria): ?>
        <a href="panel.php?cat=<?= $categoria['id'] ?>" class="board-btn<?= $categoria['id'] == $selectedCat ? ' active' : '' ?>" data-cat="<?= $categoria['id'] ?>">
            <?= htmlspecialchars($categoria['nombre']) ?>
        </a>
    <?php endforeach; ?>
    </div>
    <button type="button" class="board-scroll right" aria-label="Siguiente"><i data-feather="chevron-right"></i></button>
    <button type="button" class="search-toggle" aria-label="Buscar"><i data-feather="search"></i></button>
</div>

<input type="text" class="search-input" placeholder="Buscar links...">

<div class="link-cards">
<?php
$shownPerCat = [];
$adsShownPerCat = [];
foreach ($links as $link):
    $catId = $link['categoria_id'];
    $shownPerCat[$catId] = ($shownPerCat[$catId] ?? 0) + 1;
    $domain = parse_url($link['url'], PHP_URL_HOST);
    $favicon = $domain ? getLocalFavicon($domain) : '';
    $imgSrc = !empty($link['imagen']) ? $link['imagen'] : $favicon;
    $isDefault = empty($link['imagen']);
    $isLocalFavicon = str_starts_with($imgSrc, '/local_favicons/');
?>
    <div class="card" data-cat="<?= $link['categoria_id'] ?>" data-id="<?= $link['id'] ?>">
        <div class="card-image <?= $isDefault ? 'no-image' : '' ?> <?= $isLocalFavicon ? 'local-favicon' : '' ?>">
            <a href="<?= htmlspecialchars($link['url']) ?>" target="_blank" rel="noopener noreferrer">
                <img src="<?= htmlspecialchars($imgSrc) ?>" alt="" loading="lazy">
            </a>
            <button class="share-btn" data-url="<?= htmlspecialchars($link['url']) ?>" aria-label="Compartir"><i data-feather="share-2"></i></button>
            <a href="editar_link.php?id=<?= $link['id'] ?>" class="edit-btn" aria-label="Editar"><i data-feather="edit-2"></i></a>
        </div>
        <div class="card-body">
            <?php
                $title = $link['titulo'] ?: $link['url'];
                if (mb_strlen($title) > 50) {
                    $title = mb_substr($title, 0, 47) . '...';
                }
            ?>
            <div class="card-title">
                <h4><img src="<?= htmlspecialchars($favicon) ?>" width="18" height="18" alt="" loading="lazy"><?= htmlspecialchars($title) ?></h4>
            </div>
            <?php if(!empty($link['descripcion'])): ?>
                <?php
                    $desc = $link['descripcion'];
                    if (mb_strlen($desc) > $descLimit) {
                        $desc = mb_substr($desc, 0, $descLimit - 3) . '...';
                    }
                ?>
                <p><?= htmlspecialchars($desc) ?></p>
            <?php endif; ?>
            <div class="card-actions">
                <select class="move-select" data-id="<?= $link['id'] ?>">
                <?php foreach($categorias as $categoria): ?>
                    <option value="<?= $categoria['id'] ?>" <?= $categoria['id'] == $link['categoria_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($categoria['nombre']) ?>
                    </option>
                <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    <?php
        $catAdsLimit = $maxAdsPerCat[$catId] ?? 0;
        if ($shownPerCat[$catId] % 8 === 0 && ($adsShownPerCat[$catId] ?? 0) < $catAdsLimit):
    ?>
    <div class="card ad-card" data-cat="<?= $catId ?>">
<!--
        <div class="card-body">
<ins data-revive-zoneid="56" data-revive-id="cabd7431fd9e40f440e6d6f0c0dc8623"></ins>
<script async src="//4bes.es/adserver/www/delivery/asyncjs.php"></script>
            <div class="ad-label">patrocinado 300x600</div>
        </div>
-->
		<div class="card-body">
            <ins data-revive-zoneid="55" data-revive-id="cabd7431fd9e40f440e6d6f0c0dc8623"></ins>
            <script async src="//4bes.es/adserver/www/delivery/asyncjs.php"></script>
            <div class="ad-label">...</div>
        </div>
    </div>
    <?php
            $adsShownPerCat[$catId] = ($adsShownPerCat[$catId] ?? 0) + 1;
        endif;
    endforeach;
?>
</div>

</div>
</body>
</html>
