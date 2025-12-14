<?php
require 'config.php';
require 'favicon_utils.php';
require_once 'session.php';
require_once 'device.php';

$descLimit = isMobile() ? 50 : 150;
$selectedCat = isset($_GET['cat']) ? trim($_GET['cat']) : '';

// Categorías del usuario para mostrar el botón de añadir en el header
$categorias = [];
if (isset($_SESSION['user_id'])) {
    $stmtUserCats = $pdo->prepare('SELECT id, nombre FROM categorias WHERE usuario_id = ? ORDER BY modificado_en DESC');
    $stmtUserCats->execute([$_SESSION['user_id']]);
    $categorias = $stmtUserCats->fetchAll();
}

// Categorías disponibles en el listado de Top Favolinks
$stmtTopCats = $pdo->query('SELECT DISTINCT categoria FROM TopFavolinks ORDER BY categoria ASC');
$topCategories = $stmtTopCats->fetchAll(PDO::FETCH_COLUMN);

if ($selectedCat !== '' && !in_array($selectedCat, $topCategories, true)) {
    $selectedCat = '';
}

if ($selectedCat !== '') {
    $stmtLinks = $pdo->prepare('SELECT id, categoria, url, titulo, descripcion, imagen, favicon, dominio, etiquetas FROM TopFavolinks WHERE categoria = ? ORDER BY creado_en DESC');
    $stmtLinks->execute([$selectedCat]);
} else {
    $stmtLinks = $pdo->query('SELECT id, categoria, url, titulo, descripcion, imagen, favicon, dominio, etiquetas FROM TopFavolinks ORDER BY creado_en DESC');
}

$links = $stmtLinks->fetchAll();

$catCounts = [];
foreach ($links as $l) {
    $cat = $l['categoria'];
    $catCounts[$cat] = ($catCounts[$cat] ?? 0) + 1;
}
$maxAdsPerCat = [];
foreach ($catCounts as $cat => $cnt) {
    $maxAdsPerCat[$cat] = intdiv($cnt, 8);
}

include 'header.php';
?>
<div class="board-nav">
    <button type="button" class="board-scroll left" aria-label="Anterior"><i data-feather="chevron-left"></i></button>
    <div class="board-slider">
        <a href="top_favolinks.php" class="board-btn<?= $selectedCat === '' ? ' active' : '' ?>" data-cat="all">Todo</a>
        <?php foreach ($topCategories as $cat): ?>
            <a href="top_favolinks.php?cat=<?= urlencode($cat) ?>" class="board-btn<?= $cat === $selectedCat ? ' active' : '' ?>" data-cat="<?= htmlspecialchars($cat) ?>">
                <?= htmlspecialchars($cat) ?>
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
    $catId = $link['categoria'];
    $shownPerCat[$catId] = ($shownPerCat[$catId] ?? 0) + 1;
    $domain = !empty($link['dominio']) ? $link['dominio'] : parse_url($link['url'], PHP_URL_HOST);
    $favicon = $domain ? getLocalFavicon($domain) : '';
    $imgSrc = !empty($link['imagen']) ? $link['imagen'] : ($link['favicon'] ?: $favicon);
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

    $faviconSrc = !empty($link['favicon']) ? $link['favicon'] : $favicon;
?>
    <div class="card" data-cat="<?= htmlspecialchars($link['categoria']) ?>" data-id="<?= $link['id'] ?>">
        <div class="card-image <?= $isDefault ? 'no-image' : '' ?> <?= $isLocalFavicon ? 'local-favicon' : '' ?>">
            <a href="<?= htmlspecialchars($link['url']) ?>" target="_blank" rel="noopener noreferrer">
                <img src="<?= htmlspecialchars($imgSrc) ?>" alt="" loading="lazy">
            </a>
            <button class="share-btn" data-url="<?= htmlspecialchars($link['url']) ?>" aria-label="Compartir"><i data-feather="share-2"></i></button>
        </div>
        <div class="card-body">
            <div class="card-title">
                <h4>
                    <?php if ($faviconSrc !== ''): ?>
                        <img
                            src="<?= htmlspecialchars($faviconSrc) ?>"
                            width="18"
                            height="18"
                            alt=""
                            loading="lazy"
                        >
                    <?php endif; ?>
                    <?= htmlspecialchars($title) ?>
                </h4>
            </div>
            <?php if($desc !== ''): ?>
                <p><?= htmlspecialchars($desc) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php
        $catAdsLimit = $maxAdsPerCat[$catId] ?? 0;
        if ($shownPerCat[$catId] % 8 === 0 && ($adsShownPerCat[$catId] ?? 0) < $catAdsLimit):
    ?>
    <div class="card ad-card" data-cat="<?= htmlspecialchars($catId) ?>">
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
