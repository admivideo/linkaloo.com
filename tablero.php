<?php
require 'config.php';
require 'favicon_utils.php';
require_once 'image_utils.php';
require_once 'session.php';
if(!isset($_SESSION['user_id'])){
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare(
    'SELECT c.*, COUNT(l.id) AS total_links, ' .
    '       (SELECT l2.imagen FROM links l2 WHERE l2.categoria_id = c.id AND l2.usuario_id = ? ORDER BY l2.id LIMIT 1) AS imagen ' .
    'FROM categorias c ' .
    'LEFT JOIN links l ON l.categoria_id = c.id AND l.usuario_id = ? ' .
    'WHERE c.id = ? AND c.usuario_id = ?'
);
$stmt->execute([$user_id, $user_id, $id, $user_id]);
$board = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$board){
    header('Location: tableros.php');
    exit;
}

function scrapeImage($url){
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
        return '';
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
    $image = $getMeta('og:image') ?: $getMeta('twitter:image');
    if(!empty($image) && !preg_match('#^https?://#', $image)){
        $parts = parse_url($url);
        $base = $parts['scheme'].'://'.$parts['host'];
        if(isset($parts['port'])){
            $base .= ':'.$parts['port'];
        }
        $image = rtrim($base,'/').'/'.ltrim($image,'/');
    }
    return $image;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(isset($_POST['delete_board'])){
        $pdo->prepare('DELETE FROM links WHERE categoria_id = ? AND usuario_id = ?')->execute([$id, $user_id]);
        $pdo->prepare('DELETE FROM categorias WHERE id = ? AND usuario_id = ?')->execute([$id, $user_id]);
        header('Location: tableros.php');
        exit;
    } elseif(isset($_POST['update_images'])){
        $linksStmt = $pdo->prepare('SELECT id, url, imagen FROM links WHERE usuario_id = ? AND categoria_id = ?');
        $linksStmt->execute([$user_id, $id]);
        $links = $linksStmt->fetchAll();
        foreach($links as $link){
            $newImage = scrapeImage($link['url']);
            if(empty($newImage)){
                $domain = parse_url($link['url'], PHP_URL_HOST);
                if($domain){
                    $newImage = getLocalFavicon($domain);
                }
            }
            if(!empty($newImage) && str_starts_with($newImage, 'http')){
                $localImg = saveImageFromUrl($newImage, $user_id);
                if($localImg){
                    $newImage = $localImg;
                }
            }
            if($newImage && $newImage !== $link['imagen']){
                $updImg = $pdo->prepare('UPDATE links SET imagen = ? WHERE id = ? AND usuario_id = ?');
                $updImg->execute([$newImage, $link['id'], $user_id]);
            }
        }
        header('Location: tablero.php?id=' . $id);
        exit;
    } else {
        $nombre = trim($_POST['nombre'] ?? '');
        $nota = trim($_POST['nota'] ?? '');
        $publico = isset($_POST['publico']);
        $shareToken = $board['share_token'];
        if($publico && empty($shareToken)){
            $shareToken = bin2hex(random_bytes(16));
        } elseif(!$publico){
            $shareToken = null;
        }
        $upd = $pdo->prepare('UPDATE categorias SET nombre = ?, nota = ?, share_token = ? WHERE id = ? AND usuario_id = ?');
        $upd->execute([$nombre, $nota, $shareToken, $id, $user_id]);
        $stmt->execute([$user_id, $user_id, $id, $user_id]);
        $board = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

$linksStmt = $pdo->prepare('SELECT id, url, imagen FROM links WHERE usuario_id = ? AND categoria_id = ? ORDER BY id DESC');
$linksStmt->execute([$user_id, $id]);
$links = $linksStmt->fetchAll();

$creado = $board['creado_en'] ? date('Y-m', strtotime($board['creado_en'])) : '';
$modificado = $board['modificado_en'] ? date('Y-m', strtotime($board['modificado_en'])) : '';

$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
$publicUrl = !empty($board['share_token']) ? $baseUrl . '/tablero_publico.php?token=' . $board['share_token'] : '';
$shareImg = $board['imagen'] ?? '';
if (!empty($shareImg) && !preg_match('#^https?://#', $shareImg)) {
    $shareImg = $baseUrl . '/' . ltrim($shareImg, '/');
}

include 'header.php';
?>
<div class="board-detail">
    <div class="board-detail-image">
        <?php if(!empty($board['imagen'])): ?>
            <img src="<?= htmlspecialchars($board['imagen']) ?>" alt="<?= htmlspecialchars($board['nombre']) ?>" loading="lazy">
        <?php endif; ?>
    </div>
    <div class="board-detail-info">
        <div class="detail-header">
            <h2><?= htmlspecialchars($board['nombre']) ?></h2>
            <?php if(!empty($board['share_token'])): ?>
            <button type="button" class="share-board" data-url="<?= htmlspecialchars($publicUrl) ?>" data-title="<?= htmlspecialchars($board['nombre']) ?>" <?= !empty($shareImg) ? 'data-image="' . htmlspecialchars($shareImg) . '"' : '' ?> aria-label="Compartir"><i data-feather="share-2"></i></button>
            <?php endif; ?>
        </div>
        <form method="post" class="board-detail-form">
            <label>Nombre<br>
                <input type="text" name="nombre" value="<?= htmlspecialchars($board['nombre']) ?>">
            </label>
            <label>Nota<br>
                <textarea name="nota"><?= htmlspecialchars($board['nota'] ?? '') ?></textarea>
            </label>
            <label>
                <input type="checkbox" name="publico" value="1" <?= !empty($board['share_token']) ? 'checked' : '' ?>>
                Compartir tablero públicamente <i data-feather="share-2"></i>
            </label>
            <p>Links guardados: <a class="links-link" href="panel.php?cat=<?= $id ?>"><?= $board['total_links'] ?></a></p>
            <p>Creado: <?= htmlspecialchars($creado) ?></p>
            <p>Modificado: <?= htmlspecialchars($modificado) ?></p>
            <div class="board-form-buttons">
                <button type="submit" name="update_images">Actualizar imágenes</button>
                <button type="submit">Guardar</button>
                <button type="submit" name="delete_board" onclick="return confirm('¿Eliminar este tablero?');">Eliminar tablero</button>
            </div>
        </form>
    </div>
</div>
<?php if(!empty($links)): ?>
<div class="link-cards board-links">
<?php foreach($links as $link): ?>
    <?php
        $domain = parse_url($link['url'], PHP_URL_HOST);
    $favicon = $domain ? getLocalFavicon($domain) : '';
    $imgSrc = !empty($link['imagen']) ? $link['imagen'] : $favicon;
    $isDefault = empty($link['imagen']);
    ?>
    <div class="card">
        <div class="card-image <?= $isDefault ? 'no-image' : '' ?>">
            <a href="<?= htmlspecialchars($link['url']) ?>" target="_blank" rel="noopener noreferrer">
                <img src="<?= htmlspecialchars($imgSrc) ?>" alt="" loading="lazy">
            </a>
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
