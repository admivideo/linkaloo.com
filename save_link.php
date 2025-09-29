<?php
require 'config.php';

header('Content-Type: application/json; charset=utf-8');

function readRequestBody(): array
{
    $data        = array_merge($_GET, $_POST);
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

function valueFrom(array $data, array $keys)
{
    foreach ($keys as $key) {
        if (array_key_exists($key, $data)) {
            return $data[$key];
        }
    }

    return null;
}

function normalizeString($value): ?string
{
    if ($value === null || $value === false) {
        return null;
    }

    if (is_array($value)) {
        return null;
    }

    $value = trim((string) $value);

    return $value === '' ? null : $value;
}

function truncateString(?string $value, int $maxLength): ?string
{
    if ($value === null) {
        return null;
    }

    if (mb_strlen($value) <= $maxLength) {
        return $value;
    }

    return mb_substr($value, 0, $maxLength);
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

$requestData = readRequestBody();

$userIdRaw      = valueFrom($requestData, ['usuario_id', 'user_id', 'usuarioId', 'userId']);
$categoryIdRaw  = valueFrom($requestData, ['categoria_id', 'category_id', 'categoriaId', 'categoryId']);
$url            = normalizeString(valueFrom($requestData, ['url', 'link_url', 'enlace', 'link']));
$title          = normalizeString(valueFrom($requestData, ['titulo', 'title', 'link_title']));
$newCategoryRaw = normalizeString(valueFrom($requestData, ['categoria_nombre', 'category_name', 'categoriaNombre', 'categoryName']));
$description    = normalizeString(valueFrom($requestData, ['descripcion', 'description']));
$image          = normalizeString(valueFrom($requestData, ['imagen', 'image', 'imagen_url', 'image_url']));
$note           = normalizeString(valueFrom($requestData, ['nota', 'nota_link', 'notaLink', 'note']));
$tags           = normalizeString(valueFrom($requestData, ['etiquetas', 'tags']));

$userId     = is_numeric($userIdRaw) ? (int) $userIdRaw : 0;
$categoryId = is_numeric($categoryIdRaw) ? (int) $categoryIdRaw : 0;

if ($userId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'Debe proporcionar un usuario_id válido.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($url === null) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'Debe proporcionar la URL del enlace.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($categoryId <= 0 && $newCategoryRaw === null) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'Debe proporcionar categoria_id o categoria_nombre.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'El enlace proporcionado no es válido.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($title !== null) {
    $title = truncateString($title, 50);
}

$description = truncateString($description, 300);
$note        = truncateString($note, 300);
$tags        = truncateString($tags, 255);

try {
    if ($categoryId <= 0 && $newCategoryRaw !== null) {
        $createCategory = $pdo->prepare('INSERT INTO categorias (usuario_id, nombre) VALUES (?, ?)');
        $createCategory->execute([$userId, $newCategoryRaw]);
        $categoryId = (int) $pdo->lastInsertId();
    }

    if ($categoryId <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error'   => 'No se pudo determinar la categoría para el enlace.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $canonicalUrl = canonicalizeUrl($url);
    $hash         = sha1($canonicalUrl);

    $check = $pdo->prepare('SELECT id FROM links WHERE usuario_id = ? AND hash_url = ? LIMIT 1');
    $check->execute([$userId, $hash]);
    $existingId = $check->fetchColumn();

    if ($existingId) {
        echo json_encode([
            'success'   => true,
            'link_id'   => (int) $existingId,
            'duplicate' => true,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare('INSERT INTO links (usuario_id, categoria_id, url, url_canonica, titulo, descripcion, imagen, hash_url, nota_link, etiquetas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $userId,
        $categoryId,
        $url,
        $canonicalUrl,
        $title,
        $description,
        $image,
        $hash,
        $note,
        $tags,
    ]);

    if ($stmt->rowCount()) {
        $updateCategory = $pdo->prepare('UPDATE categorias SET modificado_en = NOW() WHERE id = ?');
        $updateCategory->execute([$categoryId]);
    }

    echo json_encode([
        'success'   => true,
        'link_id'   => (int) $pdo->lastInsertId(),
        'categoria' => $categoryId,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    error_log('save_link.php error: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Ocurrió un error al guardar el link.',
    ], JSON_UNESCAPED_UNICODE);
}
