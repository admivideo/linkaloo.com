<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Configuración de la base de datos
$host = '82.223.84.165';
$port = '3306';
$database = 'smartlinks';
$username = '^A%Odbc%!IOn0s!';
$password = '$Fw7Hen^S&*36#DbSit@85$';

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Conectar a la base de datos
    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    // Obtener datos del request
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'check_user':
            checkUser($pdo, $input);
            break;
            
        case 'create_user':
            createUser($pdo, $input);
            break;
            
        case 'get_categories':
            getCategories($pdo, $input);
            break;
            
        case 'test_connection':
            testConnection($pdo);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function checkUser($pdo, $input) {
    $email = $input['email'] ?? '';
    
    if (empty($email)) {
        throw new Exception('Email es requerido');
    }
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Usuario existe
        echo json_encode([
            'success' => true,
            'exists' => true,
            'user' => [
                'id' => (int)$user['id'],
                'nombre' => $user['nombre'],
                'email' => $user['email'],
                'google_id' => $user['google_id'],
                'creado_en' => $user['creado_en'],
                'actualizado_en' => $user['actualizado_en']
            ]
        ]);
    } else {
        // Usuario no existe
        echo json_encode([
            'success' => true,
            'exists' => false,
            'message' => 'Usuario no encontrado'
        ]);
    }
}

function createUser($pdo, $input) {
    $nombre = $input['nombre'] ?? '';
    $email = $input['email'] ?? '';
    $googleId = $input['google_id'] ?? '';
    
    if (empty($nombre) || empty($email)) {
        throw new Exception('Nombre y email son requeridos');
    }
    
    // Verificar si el usuario ya existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        // Usuario ya existe, actualizar datos
        $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, google_id = ?, actualizado_en = NOW() WHERE email = ?");
        $stmt->execute([$nombre, $googleId, $email]);
        
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'created' => false,
            'updated' => true,
            'user' => [
                'id' => (int)$user['id'],
                'nombre' => $user['nombre'],
                'email' => $user['email'],
                'google_id' => $user['google_id'],
                'creado_en' => $user['creado_en'],
                'actualizado_en' => $user['actualizado_en']
            ]
        ]);
    } else {
        // Crear nuevo usuario
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, google_id, creado_en, actualizado_en) VALUES (?, ?, ?, NOW(), NOW())");
        $stmt->execute([$nombre, $email, $googleId]);
        
        $userId = $pdo->lastInsertId();
        
        // Obtener el usuario creado
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'created' => true,
            'updated' => false,
            'user' => [
                'id' => (int)$user['id'],
                'nombre' => $user['nombre'],
                'email' => $user['email'],
                'google_id' => $user['google_id'],
                'creado_en' => $user['creado_en'],
                'actualizado_en' => $user['actualizado_en']
            ]
        ]);
    }
}

function getCategories($pdo, $input) {
    $userId = $input['user_id'] ?? 0;
    
    if ($userId <= 0) {
        throw new Exception('ID de usuario válido es requerido');
    }
    
    // Verificar que el usuario existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('Usuario no encontrado');
    }
    
    // Obtener categorías del usuario
    $stmt = $pdo->prepare("SELECT * FROM categorias WHERE usuario_id = ? ORDER BY nombre ASC");
    $stmt->execute([$userId]);
    $categories = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'user_id' => (int)$userId,
        'total_categories' => count($categories),
        'categories' => array_map(function($cat) {
            return [
                'id' => (int)$cat['id'],
                'usuario_id' => (int)$cat['usuario_id'],
                'nombre' => $cat['nombre'],
                'creado_en' => $cat['creado_en'],
                'modificado_en' => $cat['modificado_en'],
                'share_token' => $cat['share_token']
            ];
        }, $categories)
    ]);
}

function testConnection($pdo) {
    // Probar conexión con consultas simples
    $stmt = $pdo->query("SELECT COUNT(*) as total_usuarios FROM usuarios");
    $usuarios = $stmt->fetch();
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_categorias FROM categorias");
    $categorias = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'message' => 'Conexión exitosa',
        'stats' => [
            'total_usuarios' => (int)$usuarios['total_usuarios'],
            'total_categorias' => (int)$categorias['total_categorias']
        ]
    ]);
}
?>
