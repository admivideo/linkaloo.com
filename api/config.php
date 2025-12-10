<?php
/**
 * Archivo de configuración para la base de datos
 * Contiene los datos de conexión y configuraciones del servidor
 */

// Configuración de la base de datos
$db_config = [
    'host' => '82.223.84.165',
    'port' => '3306',
    'database' => 'smartlinks',
    'username' => '^A%Odbc%!IOn0s!',
    'password' => '$Fw7Hen^S&*36#DbSit@85$',
    'charset' => 'utf8mb4'
];

// Configuración del servidor
$server_config = [
    'base_url' => 'https://linkaloo.com',
    'api_url' => 'https://linkaloo.com/api',
    'fichas_path' => '../fichas/',
    'max_image_size' => 5 * 1024 * 1024, // 5MB
    'image_quality' => 90,
    'image_width' => 300
];

// Función para obtener la conexión a la base de datos
function getDatabaseConnection() {
    global $db_config;
    
    try {
        $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['database']};charset={$db_config['charset']}";
        $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception('Error de conexión a la base de datos: ' . $e->getMessage());
    }
}

// Función para obtener configuración del servidor
function getServerConfig($key = null) {
    global $server_config;
    
    if ($key === null) {
        return $server_config;
    }
    
    return isset($server_config[$key]) ? $server_config[$key] : null;
}

// Función helper para JSON sin escape de barras
function json_response($data) {
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
}

// Función para configurar headers CORS
function setCorsHeaders() {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

// Función para manejar preflight requests
function handlePreflightRequest() {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}
?>
