<?php
require 'config.php';
require 'favicon_utils.php';
require_once 'image_utils.php';
require_once 'session.php';
require_once 'device.php';

if(!isset($_SESSION['user_id'])){
    $query = $_SERVER['QUERY_STRING'] ?? '';
    $target = 'login.php' . ($query ? '?' . $query : '');
    header('Location: ' . $target);
    exit;
}

$user_id = $_SESSION['user_id'];
$descLimit = isMobile() ? 50 : 150;

$formValues = [
    'link_url' => '',
    'link_title' => '',
    'categoria_id' => '',
    'categoria_nombre' => '',
];

if(isset($_GET['shared'])){
    $sharedParam = trim($_GET['shared']);
    if(isValidSharedUrl($sharedParam)){
        $formValues['link_url'] = $sharedParam;
    }
}

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
    $link_url = trim($_POST['link_url'] ?? '');
    $link_title = trim($_POST['link_title'] ?? '');
    $categoria_id = isset($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : 0;
    $categoria_nombre = trim($_POST['categoria_nombre'] ?? '');

    $formValues['link_url'] = $link_url;
    $formValues['link_title'] = $link_title;
    $formValues['categoria_id'] = $categoria_id ? (string)$categoria_id : '';
    $formValues['categoria_nombre'] = $categoria_nombre;

    if($categoria_nombre){
        $stmt = $pdo->prepare('INSERT INTO categorias (usuario_id, nombre) VALUES (?, ?)');
        $stmt->execute([$user_id, $categoria_nombre]);
        $categoria_id = (int)$pdo->lastInsertId();
    }

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
        if (mb_strlen($descripcion) > $descLimit) {
            $descripcion = mb_substr($descripcion, 0, $descLimit - 3) . '...';
        }
        $imagen = $meta['image'] ?? '';
        if (empty($imagen)) {
            $domain = parse_url($link_url, PHP_URL_HOST);
            if ($domain) {
                $imagen = getLocalFavicon($domain);
            }
        }
        if(!empty($imagen) && str_starts_with($imagen, 'http')){
            $localImg = saveImageFromUrl($imagen, $user_id);
            if($localImg){
                $imagen = $localImg;
            }
        }
        $canon = canonicalizeUrl($link_url);
        $hash = sha1($canon);
        $check = $pdo->prepare('SELECT id FROM links WHERE usuario_id = ? AND hash_url = ?');
        $check->execute([$user_id, $hash]);
        if($check->fetch()){
            $_SESSION['panel_error'] = 'Este link ya está guardado.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO links (usuario_id, categoria_id, url, url_canonica, titulo, descripcion, imagen, hash_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$user_id, $categoria_id, $link_url, $canon, $link_title, $descripcion, $imagen, $hash]);
            if ($stmt->rowCount()) {
                $upd = $pdo->prepare('UPDATE categorias SET modificado_en = NOW() WHERE id = ?');
                $upd->execute([$categoria_id]);
            }
        }
    }

    header('Location: panel.php');
    exit;
}

$stmt = $pdo->prepare('SELECT id, nombre FROM categorias WHERE usuario_id = ? ORDER BY modificado_en DESC');
$stmt->execute([$user_id]);
$categorias = $stmt->fetchAll();

include 'header.php';
?>
<div class="favolink-page">
    <div class="favolink-card">
        <div class="app-logo"><img src="/img/logo_linkaloo_blue.png" alt="Linkaloo logo"></div>
        <p class="favolink-tagline">Yours favolinks with superpowers</p>
        <h2 class="modal-title">Añadir tu favolink</h2>
        <div class="control-forms">
            <div class="form-section">
                <form method="post" class="form-link favolink-form">
                    <input type="url" name="link_url" placeholder="Pega aquí el link" value="<?= htmlspecialchars($formValues['link_url']) ?>" required>
                    <input type="text" name="link_title" placeholder="Título (opcional)" maxlength="50" value="<?= htmlspecialchars($formValues['link_title']) ?>">
                    <div class="select-create">
                        <select name="categoria_id">
                            <option value="">Elige el tablero</option>
                            <?php foreach($categorias as $categoria): ?>
                                <option value="<?= $categoria['id'] ?>" <?= ($formValues['categoria_id'] !== '' && (int)$formValues['categoria_id'] === (int)$categoria['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($categoria['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="categoria_nombre" placeholder="o crea un nuevo (opcional)" value="<?= htmlspecialchars($formValues['categoria_nombre']) ?>">
                    </div>
                    <button type="submit">Guardar favolink</button>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
</body>
</html>
