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
<?php foreach ($links as $link):
    $imgSrc = $link['imagen'] ?: ($link['favicon'] ?: '');
    if (!$imgSrc && !empty($link['dominio'])) {
        $imgSrc = getLocalFavicon($link['dominio']);
    }
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
                    <?php if (!empty($link['favicon'])): ?>
                        <img src="<?= htmlspecialchars($link['favicon']) ?>" width="18" height="18" alt="" loading="lazy">
                    <?php elseif (!empty($link['dominio'])): ?>
                        <img src="<?= htmlspecialchars(getLocalFavicon($link['dominio'])) ?>" width="18" height="18" alt="" loading="lazy">
                    <?php endif; ?>
                    <?= htmlspecialchars($title) ?>
                </h4>
            </div>
            <?php if($desc !== ''): ?>
                <p><?= htmlspecialchars($desc) ?></p>
            <?php endif; ?>
            <?php if(!empty($link['etiquetas'])): ?>
                <div class="favolink-tags"><?= htmlspecialchars($link['etiquetas']) ?></div>
            <?php endif; ?>
            <div class="card-actions">
                <a href="<?= htmlspecialchars($link['url']) ?>" target="_blank" rel="noopener noreferrer" class="button">Visitar</a>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>

</div>
</body>
</html>
