<?php
require 'config.php';
require 'favicon_utils.php';
require_once 'image_utils.php';
require_once 'session.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error'   => 'Autenticación requerida.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int) $_SESSION['user_id'];

function ensureUtf8(string $string): string
{
    $encoding = mb_detect_encoding($string, 'UTF-8, ISO-8859-1, WINDOWS-1252', true);
    if ($encoding && $encoding !== 'UTF-8') {
        $string = mb_convert_encoding($string, 'UTF-8', $encoding);
    }

    return $string;
}

function canonicalizeUrl(string $url): string
{
    $parts = parse_url(trim($url));
    if (!$parts || empty($parts['host'])) {
        return $url;
    }

    $scheme = strtolower($parts['scheme'] ?? 'http');
    $host   = strtolower($parts['host']);
    $path   = isset($parts['path']) ? rtrim($parts['path'], '/') : '';
    $query  = isset($parts['query']) ? '?' . $parts['query'] : '';
    $port   = isset($parts['port']) ? ':' . $parts['port'] : '';

    return $scheme . '://' . $host . $port . $path . $query;
}

function scrapeMetadata(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; linkalooBot/1.0)',
        CURLOPT_TIMEOUT        => 5,
    ]);

    $html = curl_exec($ch);
    curl_close($ch);

    if (!$html) {
        return [];
    }

    $encoding = mb_detect_encoding($html, 'UTF-8, ISO-8859-1, WINDOWS-1252', true);
    if ($encoding) {
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', $encoding);
    }

    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    if (@$doc->loadHTML($html) === false) {
        libxml_clear_errors();
        return [];
    }
    libxml_clear_errors();

    $xpath   = new DOMXPath($doc);
    $meta    = [];
    $titles  = $doc->getElementsByTagName('title');
    if ($titles->length) {
        $meta['title'] = trim($titles->item(0)->textContent);
    }

    $getMeta = function (string $name, string $attr = 'property') use ($xpath): string {
        $nodes = $xpath->query("//meta[@$attr='$name']/@content");
        return $nodes->length ? trim($nodes->item(0)->nodeValue) : '';
    };

    $meta['description'] = $getMeta('og:description') ?: $getMeta('description', 'name');
    $meta['image']       = $getMeta('og:image') ?: $getMeta('twitter:image');

    if (!empty($meta['image']) && !preg_match('#^https?://#', $meta['image'])) {
        $parts = parse_url($url);
        if ($parts && !empty($parts['host'])) {
            $base = $parts['scheme'] . '://' . $parts['host'];
            if (isset($parts['port'])) {
                $base .= ':' . $parts['port'];
            }
            $meta['image'] = rtrim($base, '/') . '/' . ltrim($meta['image'], '/');
        }
    }

    foreach ($meta as &$value) {
        $value = ensureUtf8($value);
    }
    unset($value);

    return $meta;
}

function readRequestBody(): array
{
    $data        = $_POST;
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        if ($raw !== false && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $data = array_merge($data, $decoded);
            }
        }
    }

    return $data;
}

$requestData = readRequestBody();

$linkUrl = trim($requestData['link_url'] ?? $requestData['url'] ?? '');
if ($linkUrl === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'El campo link_url es obligatorio.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!filter_var($linkUrl, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'El enlace proporcionado no es válido.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$categoryId   = (int) ($requestData['categoria_id'] ?? $requestData['category_id'] ?? 0);
$categoryName = trim($requestData['categoria_nombre'] ?? $requestData['category_name'] ?? '');

if ($categoryId <= 0 && $categoryName === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'Debe indicar un tablero existente o un nombre para crear uno nuevo.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if ($categoryName !== '') {
        $stmt = $pdo->prepare('INSERT INTO categorias (usuario_id, nombre) VALUES (?, ?)');
        $stmt->execute([$userId, ensureUtf8($categoryName)]);
        $categoryId = (int) $pdo->lastInsertId();
    } elseif ($categoryId > 0) {
        $stmt = $pdo->prepare('SELECT id FROM categorias WHERE id = ? AND usuario_id = ?');
        $stmt->execute([$categoryId, $userId]);
        if (!$stmt->fetchColumn()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error'   => 'No tiene permisos para usar este tablero.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'No se pudo preparar el tablero solicitado.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$linkTitle = trim($requestData['link_title'] ?? $requestData['titulo'] ?? $requestData['title'] ?? '');
$description = trim($requestData['descripcion'] ?? $requestData['description'] ?? '');
$imageUrl = trim($requestData['imagen'] ?? $requestData['image'] ?? '');
$faviconUrl = trim($requestData['favicon'] ?? '');
$note = trim($requestData['nota_link'] ?? $requestData['nota'] ?? $requestData['note'] ?? '');
$tags = trim($requestData['etiquetas'] ?? $requestData['tags'] ?? '');

$descLimit = 150;

if ($linkTitle === '' || $description === '' || $imageUrl === '') {
    $metadata = scrapeMetadata($linkUrl);
    if ($linkTitle === '' && !empty($metadata['title'])) {
        $linkTitle = $metadata['title'];
    }
    if ($description === '' && !empty($metadata['description'])) {
        $description = $metadata['description'];
    }
    if ($imageUrl === '' && !empty($metadata['image'])) {
        $imageUrl = $metadata['image'];
    }
}

$linkTitle = ensureUtf8($linkTitle);
if ($linkTitle !== '' && mb_strlen($linkTitle) > 50) {
    $linkTitle = mb_substr($linkTitle, 0, 47) . '...';
}

$description = ensureUtf8($description);
if ($description !== '' && mb_strlen($description) > $descLimit) {
    $description = mb_substr($description, 0, $descLimit - 3) . '...';
}

$note = ensureUtf8($note);
$tags = ensureUtf8($tags);

if ($imageUrl !== '' && str_starts_with($imageUrl, 'http')) {
    $local = saveImageFromUrl($imageUrl, $userId);
    if ($local !== '') {
        $imageUrl = $local;
    }
}

$domain = parse_url($linkUrl, PHP_URL_HOST) ?: '';
if ($faviconUrl === '' && $domain) {
    $faviconUrl = getLocalFavicon($domain);
}

$canonicalUrl = canonicalizeUrl($linkUrl);
$hash = sha1($canonicalUrl);

try {
    $stmt = $pdo->prepare('SELECT id FROM links WHERE usuario_id = ? AND hash_url = ?');
    $stmt->execute([$userId, $hash]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error'   => 'Este link ya está guardado.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo->beginTransaction();

    $insert = $pdo->prepare('INSERT INTO links (usuario_id, categoria_id, url, url_canonica, titulo, descripcion, imagen, favicon, dominio, nota_link, etiquetas, hash_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $insert->execute([
        $userId,
        $categoryId,
        $linkUrl,
        $canonicalUrl,
        $linkTitle !== '' ? $linkTitle : null,
        $description !== '' ? $description : null,
        $imageUrl !== '' ? $imageUrl : null,
        $faviconUrl !== '' ? $faviconUrl : null,
        $domain !== '' ? $domain : null,
        $note !== '' ? $note : null,
        $tags !== '' ? $tags : null,
        $hash,
    ]);

    $linkId = (int) $pdo->lastInsertId();

    $update = $pdo->prepare('UPDATE categorias SET modificado_en = NOW() WHERE id = ?');
    $update->execute([$categoryId]);

    $pdo->commit();

    echo json_encode([
        'success'   => true,
        'link_id'   => $linkId,
        'categoria' => $categoryId,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Ocurrió un error al guardar el link.',
    ], JSON_UNESCAPED_UNICODE);
}
