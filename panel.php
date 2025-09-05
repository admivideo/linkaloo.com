<?php
require 'config.php';
session_start();
if(!isset($_SESSION['user_id'])){
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$error = '';

function ensureUtf8($string){
    $encoding = mb_detect_encoding($string, 'UTF-8, ISO-8859-1, WINDOWS-1252', true);
    if($encoding && $encoding !== 'UTF-8'){
        $string = mb_convert_encoding($string, 'UTF-8', $encoding);
    }
    return $string;
}

function canonicalizeUrl($url){
    $parts = parse_url(trim($url));
    if(!$parts || empty($parts['host'])){
        return $url;
    }
    $scheme = strtolower($parts['scheme'] ?? 'http');
    $host = strtolower($parts['host']);
    $path = isset($parts['path']) ? rtrim($parts['path'], '/') : '';
    $query = isset($parts['query']) ? '?' . $parts['query'] : '';
    $port = isset($parts['port']) ? ':' . $parts['port'] : '';
    return $scheme . '://' . $host . $port . $path . $query;
}

function scrapeMetadata($url){
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; linkalooBot/1.0)',
        CURLOPT_TIMEOUT => 5,
    ]);
    $html = curl_exec($ch);
    curl_close($ch);
    if(!$html){
        return [];
    }
    $enc = mb_detect_encoding($html, 'UTF-8, ISO-8859-1, WINDOWS-1252', true);
    if($enc){
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', $enc);
    }
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $doc->loadHTML($html);
    $xpath = new DOMXPath($doc);
    $getMeta = function($name, $attr='property') use ($xpath){
        $nodes = $xpath->query("//meta[@$attr='$name']/@content");
        return $nodes->length ? trim($nodes->item(0)->nodeValue) : '';
    };
    $meta = [];
    $titles = $doc->getElementsByTagName('title');
    if($titles->length){
        $meta['title'] = trim($titles->item(0)->textContent);
    }
    $meta['description'] = $getMeta('og:description') ?: $getMeta('description','name');
    $meta['image'] = $getMeta('og:image') ?: $getMeta('twitter:image');
    if(!empty($meta['image']) && !preg_match('#^https?://#', $meta['image'])){
        $parts = parse_url($url);
        $base = $parts['scheme'].'://'.$parts['host'];
        if(isset($parts['port'])){
            $base .= ':'.$parts['port'];
        }
        $meta['image'] = rtrim($base,'/').'/'.ltrim($meta['image'],'/');
    }
    foreach($meta as &$value){
        $value = ensureUtf8($value);
    }
    unset($value);
    return $meta;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(isset($_POST['categoria_nombre'])){
        $categoria_nombre = trim($_POST['categoria_nombre']);
        if($categoria_nombre){
            $stmt = $pdo->prepare('INSERT INTO categorias (usuario_id, nombre) VALUES (?, ?)');
            $stmt->execute([$user_id, $categoria_nombre]);
        }
    } elseif(isset($_POST['link_url'])){
        $link_url = trim($_POST['link_url']);
        $link_title = trim($_POST['link_title']);
        $categoria_id = (int)$_POST['categoria_id'];
        if($link_url && $categoria_id){
            $meta = scrapeMetadata($link_url);
            if(!$link_title && !empty($meta['title'])){
                $link_title = $meta['title'];
            }
            $link_title = ensureUtf8($link_title);
            if (mb_strlen($link_title) > 50) {
                $link_title = mb_substr($link_title, 0, 47) . '...';
            }
            $descripcion = ensureUtf8($meta['description'] ?? '');
            if (mb_strlen($descripcion) > 75) {
                $descripcion = mb_substr($descripcion, 0, 72) . '...';
            }
            $imagen = $meta['image'] ?? '';
            if (empty($imagen)) {
                $domain = parse_url($link_url, PHP_URL_HOST);
                if ($domain) {
                    $imagen = 'https://www.google.com/s2/favicons?domain=' . urlencode($domain) . '&sz=128';
                }
            }
            $canon = canonicalizeUrl($link_url);
            $hash = sha1($canon);
            $check = $pdo->prepare('SELECT id FROM links WHERE usuario_id = ? AND hash_url = ?');
            $check->execute([$user_id, $hash]);
            if($check->fetch()){
                $error = 'Este link ya está guardado.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO links (usuario_id, categoria_id, url, url_canonica, titulo, descripcion, imagen, hash_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$user_id, $categoria_id, $link_url, $canon, $link_title, $descripcion, $imagen, $hash]);
            }
        }
    }
}

$stmt = $pdo->prepare('SELECT id, nombre FROM categorias WHERE usuario_id = ? ORDER BY creado_en DESC');
$stmt->execute([$user_id]);
$categorias = $stmt->fetchAll();

$stmtL = $pdo->prepare("SELECT id, categoria_id, url, titulo, descripcion, imagen FROM links WHERE usuario_id = ? ORDER BY creado_en DESC LIMIT 18");
$stmtL->execute([$user_id]);
$links = $stmtL->fetchAll();

include 'header.php';
?>
<?php if(!empty($error)): ?>
<div class="alert">
    <span><?= htmlspecialchars($error) ?></span>
    <button class="alert-close" aria-label="Cerrar">&times;</button>
</div>
<?php endif; ?>
<div class="board-nav">
    <button class="board-scroll left" aria-label="Anterior"><i data-feather="chevron-left"></i></button>
    <div class="board-slider">
        <button class="board-btn active" data-cat="all">Todo</button>
    <?php foreach($categorias as $categoria): ?>
        <button class="board-btn" data-cat="<?= $categoria['id'] ?>">
            <?= htmlspecialchars($categoria['nombre']) ?>
        </button>
    <?php endforeach; ?>
    </div>
    <button class="board-scroll right" aria-label="Siguiente"><i data-feather="chevron-right"></i></button>
    <button class="search-toggle" aria-label="Buscar"><i data-feather="search"></i></button>
    <button class="toggle-forms" aria-label="Añadir"><i data-feather="plus"></i></button>
</div>

<input type="text" class="search-input" placeholder="Buscar links...">

<div class="control-forms">
    <form method="post" class="form-categoria">
        <input type="text" name="categoria_nombre" placeholder="Nombre del tablero">
        <button type="submit">Crear tablero</button>
    </form>
    <form method="post" class="form-link">
        <input type="url" name="link_url" placeholder="URL" required>
        <input type="text" name="link_title" placeholder="Título" maxlength="50">
        <select name="categoria_id">
        <?php foreach($categorias as $categoria): ?>
            <option value="<?= $categoria['id'] ?>"><?= htmlspecialchars($categoria['nombre']) ?></option>
        <?php endforeach; ?>
        </select>
        <button type="submit">Guardar link</button>
    </form>
</div>

<div class="link-cards">
<?php foreach($links as $index => $link): ?>
    <?php
        $domain = parse_url($link['url'], PHP_URL_HOST);
        $imgSrc = !empty($link['imagen']) ? $link['imagen'] : 'https://www.google.com/s2/favicons?domain=' . urlencode($domain) . '&sz=128';
        $isDefault = empty($link['imagen']) || strpos($link['imagen'], 'google.com/s2/favicons') !== false;
    ?>
    <div class="card" data-cat="<?= $link['categoria_id'] ?>" data-id="<?= $link['id'] ?>">
        <div class="card-image <?= $isDefault ? 'no-image' : '' ?>">
            <a href="<?= htmlspecialchars($link['url']) ?>" target="_blank" rel="noopener noreferrer">
                <img src="<?= htmlspecialchars($imgSrc) ?>" alt="">
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
                <img src="https://www.google.com/s2/favicons?domain=<?= urlencode($domain) ?>" width="20" height="20" alt="">
                <h4><?= htmlspecialchars($title) ?></h4>
            </div>
            <?php if(!empty($link['descripcion'])): ?>
                <?php
                    $desc = $link['descripcion'];
                    if (mb_strlen($desc) > 75) {
                        $desc = mb_substr($desc, 0, 72) . '...';
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
    <?php if(($index + 1) % 16 === 0): ?>
    <div class="card ad-card" data-cat="ad">
        <div class="card-body">
            <ins data-revive-zoneid="52" data-revive-id="cabd7431fd9e40f440e6d6f0c0dc8623"></ins>
            <script async src="//4bes.es/adserver/www/delivery/asyncjs.php"></script>
        </div>
    </div>
    <?php endif; ?>
<?php endforeach; ?>
</div>
<div id="sentinel"></div>

</div>
</body>
</html>
