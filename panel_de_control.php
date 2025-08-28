<?php
require 'config.php';
session_start();
if(!isset($_SESSION['user_id'])){
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

function scrapeMetadata($url){
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; LinkadooBot/1.0)',
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
            $descripcion = $meta['description'] ?? '';
            if (mb_strlen($descripcion) > 250) {
                $descripcion = mb_substr($descripcion, 0, 247) . '...';
            }
            $imagen = $meta['image'] ?? '';
            $hash = sha1($link_url);
            $stmt = $pdo->prepare('INSERT INTO links (usuario_id, categoria_id, url, titulo, descripcion, imagen, hash_url) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$user_id, $categoria_id, $link_url, $link_title, $descripcion, $imagen, $hash]);
        }
    }
}

$stmt = $pdo->prepare('SELECT id, nombre FROM categorias WHERE usuario_id = ?');
$stmt->execute([$user_id]);
$categorias = $stmt->fetchAll();

$stmtL = $pdo->prepare('SELECT id, categoria_id, url, titulo, descripcion, imagen FROM links WHERE usuario_id = ?');
$stmtL->execute([$user_id]);
$links = $stmtL->fetchAll();

include 'header.php';
?>
<div class="control-forms">
    <form method="post" class="form-categoria">
        <input type="text" name="categoria_nombre" placeholder="Nombre del tablero">
        <button type="submit">Crear tablero</button>
    </form>
    <form method="post" class="form-link">
        <input type="url" name="link_url" placeholder="URL" required>
        <input type="text" name="link_title" placeholder="Título">
        <select name="categoria_id">
        <?php foreach($categorias as $categoria): ?>
            <option value="<?= $categoria['id'] ?>"><?= htmlspecialchars($categoria['nombre']) ?></option>
        <?php endforeach; ?>
        </select>
        <button type="submit">Guardar link</button>
    </form>
</div>

<div class="board-slider">
    <button class="board-btn active" data-cat="all">Todo</button>
<?php foreach($categorias as $categoria): ?>
    <button class="board-btn" data-cat="<?= $categoria['id'] ?>">
        <?= htmlspecialchars($categoria['nombre']) ?>
    </button>
<?php endforeach; ?>
</div>

<div class="link-cards">
<?php foreach($links as $link): ?>
        <div class="card" data-cat="<?= $link['categoria_id'] ?>" data-id="<?= $link['id'] ?>">
        <?php if(!empty($link['imagen'])): ?>
            <a href="<?= htmlspecialchars($link['url']) ?>" target="_blank" rel="noopener noreferrer">
                <img src="<?= htmlspecialchars($link['imagen']) ?>" alt="">
            </a>
        <?php endif; ?>
        <div class="card-body">
            <h4><?= htmlspecialchars($link['titulo'] ?: $link['url']) ?></h4>
            <?php if(!empty($link['descripcion'])): ?>
                <?php
                    $desc = $link['descripcion'];
                    if (mb_strlen($desc) > 250) {
                        $desc = mb_substr($desc, 0, 247) . '...';
                    }
                ?>
                <p><?= htmlspecialchars($desc) ?></p>
            <?php endif; ?>
            <?php $domain = parse_url($link['url'], PHP_URL_HOST); ?>
            <div class="card-domain"><?= htmlspecialchars($domain) ?></div>
        </div>
        <select class="move-select" data-id="<?= $link['id'] ?>">
        <?php foreach($categorias as $categoria): ?>
            <option value="<?= $categoria['id'] ?>" <?= $categoria['id'] == $link['categoria_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($categoria['nombre']) ?>
            </option>
        <?php endforeach; ?>
        </select>
        <button class="delete-btn" data-id="<?= $link['id'] ?>" aria-label="Borrar">🗑️</button>
    </div>
<?php endforeach; ?>
</div>

<?php include 'footer.php'; ?>
