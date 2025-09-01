<?php
require 'config.php';
session_start();
if(!isset($_SESSION['user_id'])){
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$error = '';

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
    return $meta;
}

function truncateText($text, $limit) {
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit - 3) . '...' : $text;
    }
    return strlen($text) > $limit ? substr($text, 0, $limit - 3) . '...' : $text;
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
            $link_title = truncateText($link_title, 50);
            $descripcion = truncateText($meta['description'] ?? '', 250);
            $imagen = $meta['image'] ?? '';
            $hash = sha1($link_url);
            $check = $pdo->prepare('SELECT id FROM links WHERE usuario_id = ? AND hash_url = ?');
            $check->execute([$user_id, $hash]);
            if($check->fetch()){
                $error = 'Este link ya está guardado.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO links (usuario_id, categoria_id, url, titulo, descripcion, imagen, hash_url) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$user_id, $categoria_id, $link_url, $link_title, $descripcion, $imagen, $hash]);
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
    <button class="scroll-btn scroll-left" aria-label="Anterior"><i data-feather="chevron-left"></i></button>
    <div class="board-slider">
        <button class="board-btn active" data-cat="all">Todo</button>
    <?php foreach($categorias as $categoria): ?>
        <button class="board-btn" data-cat="<?= $categoria['id'] ?>">
            <?= htmlspecialchars($categoria['nombre']) ?>
        </button>
    <?php endforeach; ?>
    </div>
    <button class="scroll-btn scroll-right" aria-label="Siguiente"><i data-feather="chevron-right"></i></button>
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
<?php foreach($links as $link): ?>
    <?php
        $domain = parse_url($link['url'], PHP_URL_HOST);
        $imgSrc = !empty($link['imagen']) ? $link['imagen'] : 'https://www.google.com/s2/favicons?domain=' . urlencode($domain) . '&sz=128';
    ?>
    <div class="card" data-cat="<?= $link['categoria_id'] ?>" data-id="<?= $link['id'] ?>">
        <div class="card-image <?= empty($link['imagen']) ? 'no-image' : '' ?>">
            <a href="<?= htmlspecialchars($link['url']) ?>" target="_blank" rel="noopener noreferrer">
                <img src="<?= htmlspecialchars($imgSrc) ?>" alt="">
            </a>
            <button class="share-btn" aria-label="Compartir"><i data-feather="share-2"></i></button>
            <div class="share-menu">
                <a class="share-whatsapp" href="https://api.whatsapp.com/send?text=<?= urlencode($link['url']) ?>" target="_blank" rel="noopener noreferrer" aria-label="Compartir en WhatsApp">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347M12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.82 11.82 0 0012.05 0Z"/></svg>
                </a>
            </div>
            <a href="editar_link.php?id=<?= $link['id'] ?>" class="edit-btn" aria-label="Editar"><i data-feather="edit-2"></i></a>
        </div>
        <div class="card-body">
            <?php $title = truncateText($link['titulo'] ?: $link['url'], 50); ?>
            <div class="card-title">
                <img src="https://www.google.com/s2/favicons?domain=<?= urlencode($domain) ?>" width="20" height="20" alt="">
                <h4><?= htmlspecialchars($title) ?></h4>
            </div>
            <?php if(!empty($link['descripcion'])): ?>
                <?php $desc = truncateText($link['descripcion'], 250); ?>
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
                <div class="action-btns">
                    <button class="delete-btn" data-id="<?= $link['id'] ?>" aria-label="Borrar"><i data-feather="trash-2"></i></button>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<div id="sentinel"></div>

</div>
</body>
</html>
