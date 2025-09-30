<?php
require 'config.php';
require_once 'session.php';

/**
 * Google OAuth Endpoint para Linkaloo
 * Recibe tokens de Google y establece sesión de usuario
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Obtener el token de Google
$input = json_decode(file_get_contents('php://input'), true);
$id_token = $input['id_token'] ?? '';

if (empty($id_token)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID token is required']);
    exit();
}

// Verificar el token con Google
$google_user_info = verifyGoogleToken($id_token);

if (!$google_user_info) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid Google token']);
    exit();
}

// Crear o actualizar usuario en la base de datos
$user_id = createOrUpdateUser($google_user_info);

if (!$user_id) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create/update user']);
    exit();
}

// Establecer sesión
session_start();
$_SESSION['user_id'] = $user_id;
$_SESSION['user_email'] = $google_user_info['email'];
$_SESSION['user_name'] = $google_user_info['name'];
$_SESSION['google_id'] = $google_user_info['sub'];

// Respuesta exitosa
echo json_encode([
    'success' => true,
    'user' => [
        'id' => $user_id,
        'email' => $google_user_info['email'],
        'name' => $google_user_info['name']
    ]
]);

/**
 * Verifica el token de Google usando la API pública
 */
function verifyGoogleToken($id_token) {
    $client_id = '170566271159-49eodgubg84ff3nn4b0b3j7l1trfar1u.apps.googleusercontent.com';
    
    // Verificar el token con Google
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($id_token);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    
    if (!$response) {
        return false;
    }
    
    $data = json_decode($response, true);
    
    // Verificar que el token es para nuestro cliente
    if (!isset($data['aud']) || $data['aud'] !== $client_id) {
        return false;
    }
    
    // Verificar que el token no ha expirado
    if (isset($data['exp']) && $data['exp'] < time()) {
        return false;
    }
    
    return [
        'sub' => $data['sub'] ?? '',
        'email' => $data['email'] ?? '',
        'name' => $data['name'] ?? '',
        'picture' => $data['picture'] ?? '',
        'email_verified' => $data['email_verified'] ?? false
    ];
}

/**
 * Crea o actualiza un usuario en la base de datos
 */
function createOrUpdateUser($user_info) {
   
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Verificar si el usuario ya existe por Google ID
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE google_id = ?");
        $stmt->execute([$user_info['sub']]);
        $existing_user = $stmt->fetch();
        
        if ($existing_user) {
            // Actualizar usuario existente
            $stmt = $pdo->prepare("UPDATE usuarios SET email = ?, name = ?, picture = ?, modificado_en = NOW() WHERE google_id = ?");
            $stmt->execute([
                $user_info['email'],
                $user_info['name'],
                $user_info['picture'],
                $user_info['sub']
            ]);
            return $existing_user['id'];
        } else {
            // Crear nuevo usuario
            $stmt = $pdo->prepare("INSERT INTO usuarios (google_id, email, name, picture, creado_en, modificado_en) VALUES (?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([
                $user_info['sub'],
                $user_info['email'],
                $user_info['name'],
                $user_info['picture']
            ]);
            return $pdo->lastInsertId();
        }
        
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}
?>
