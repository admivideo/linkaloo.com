<?php
/****************************************************
 * Linkaloo Bluesky Bot - Registro de actividad
 * --------------------------------------------------
 * Recibe datos JSON desde n8n y los guarda en MySQL.
 * Registra tanto los posts encontrados como las respuestas publicadas.
 ****************************************************/

// Configuración de conexión a la base de datos
require '../config.php';

// Conexión segura a MySQL
$mysqli = new mysqli($servername, $username, $password, $database);
if ($mysqli->connect_error) {
    http_response_code(500);
    die(json_encode(["status" => "error", "message" => "Error de conexión a la base de datos"]));
}

// Leer y decodificar datos recibidos
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    die(json_encode(["status" => "error", "message" => "Datos JSON no válidos"]));
}

// Variables recibidas
$post_uri  = isset($data['post_uri']) ? trim($data['post_uri']) : '';
$author    = isset($data['author']) ? trim($data['author']) : '';
$text      = isset($data['text']) ? trim($data['text']) : '';
$message   = isset($data['message']) ? trim($data['message']) : null;
$type      = isset($data['type']) ? trim($data['type']) : 'post';
$createdAt = isset($data['createdAt']) ? trim($data['createdAt']) : date("Y-m-d H:i:s");

// Evitar entradas vacías
if (empty($post_uri) || empty($author)) {
    http_response_code(400);
    die(json_encode(["status" => "error", "message" => "Faltan campos obligatorios (post_uri o author)."]));
}

// Insertar registro
$stmt = $mysqli->prepare("
    INSERT INTO bot_bluesky_logs (post_uri, author, text, message, message_type, created_at)
    VALUES (?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "ssssss",
    $post_uri,
    $author,
    $text,
    $message,
    $type,
    $createdAt
);

if ($stmt->execute()) {
    echo json_encode(["status" => "ok", "message" => "Registro guardado correctamente"]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error al guardar: " . $stmt->error]);
}

$stmt->close();
$mysqli->close();
?>
