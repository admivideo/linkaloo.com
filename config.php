<?php
// Database configuration for linkaloo
$host     = '82.223.84.165';
$dbname   = 'smartlinks';
$username = 'smartuserIOn0s';
$password = 'WMCuxq@ts8s8g8^w';
$charset  = 'utf8mb4';

if (!defined('DB_HOST')) {
    define('DB_HOST', $host);
}

if (!defined('DB_NAME')) {
    define('DB_NAME', $dbname);
}

if (!defined('DB_USER')) {
    define('DB_USER', $username);
}

if (!defined('DB_PASS')) {
    define('DB_PASS', $password);
}

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
];

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $username, $password, $options);
    $pdo->exec("SET NAMES 'utf8mb4'");
} catch (PDOException $e) {
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}

// Configuración de Google OAuth
define('GOOGLE_CLIENT_ID', '170566271159-49eodgubg84ff3nn4b0b3j7l1trfar1u.apps.googleusercontent.com');

// Configuración de sesiones
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);

// Función helper para obtener conexión a la base de datos
function getDatabaseConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        return false;
    }
}

?>
