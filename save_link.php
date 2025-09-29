<?php
require 'config.php';

header('Content-Type: application/json; charset=utf-8');

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

$userId     = isset($requestData['usuario_id']) ? (int) $requestData['usuario_id'] : 0;
$categoryId = isset($requestData['categoria_id']) ? (int) $requestData['categoria_id'] : 0;
$url        = trim($requestData['url'] ?? '');
$title      = trim($requestData['titulo'] ?? '');

if ($userId <= 0 || $categoryId <= 0 || $url === '' || $title === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'Debe proporcionar usuario_id, categoria_id, url y titulo.',
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

if (mb_strlen($title) > 50) {
    $title = mb_substr($title, 0, 50);
}

try {
    $stmt = $pdo->prepare('INSERT INTO links (usuario_id, categoria_id, url, titulo) VALUES (?, ?, ?, ?)');
    $stmt->execute([
        $userId,
        $categoryId,
        $url,
        $title,
    ]);

    echo json_encode([
        'success' => true,
        'link_id' => (int) $pdo->lastInsertId(),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Ocurrió un error al guardar el link.',
    ], JSON_UNESCAPED_UNICODE);
}
