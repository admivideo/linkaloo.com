<?php
require 'config.php';

/**
 * Session Endpoint para Linkaloo
 * Verifica si hay una sesión activa y devuelve información del usuario
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

// Verificar si hay una sesión activa
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    http_response_code(401);
    echo json_encode(['authenticated' => false]);
    exit();
}

// Obtener información actualizada del usuario desde la base de datos
$user_info = getUserInfo($_SESSION['user_id']);

if (!$user_info) {
    // Usuario no encontrado en la base de datos, limpiar sesión
    session_destroy();
    http_response_code(401);
    echo json_encode(['authenticated' => false]);
    exit();
}

// Respuesta con información del usuario
echo json_encode([
    'authenticated' => true,
    'user' => [
        'id' => $user_info['id'],
        'email' => $user_info['email'],
        'name' => $user_info['name'],
        'picture' => $user_info['picture']
    ]
]);

/**
 * Obtiene información del usuario desde la base de datos
 */
function getUserInfo($user_id) {
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT id, email, name, picture FROM usuarios WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}
?>

