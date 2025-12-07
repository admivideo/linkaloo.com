<?php
/**
 * Script de prueba de conexión a la base de datos
 * Ejecutar desde navegador o línea de comandos
 */

// Incluir archivo de configuración
require_once 'config.php';

// Configurar headers para salida HTML si se ejecuta desde navegador
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/html; charset=utf-8');
}

echo "<!DOCTYPE html>\n";
echo "<html><head><meta charset='utf-8'><title>Test Conexión BD</title>\n";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}";
echo ".success{color:#28a745;background:#d4edda;padding:15px;border-radius:5px;margin:10px 0;}";
echo ".error{color:#dc3545;background:#f8d7da;padding:15px;border-radius:5px;margin:10px 0;}";
echo ".info{color:#004085;background:#cce5ff;padding:15px;border-radius:5px;margin:10px 0;}";
echo "pre{background:#fff;padding:10px;border-radius:5px;overflow-x:auto;}</style></head><body>\n";
echo "<h1>🔍 Prueba de Conexión a Base de Datos</h1>\n";
echo "<p><strong>Fecha/Hora:</strong> " . date('Y-m-d H:i:s') . "</p>\n";

try {
    echo "<div class='info'>";
    echo "<h2>📋 Configuración de Conexión</h2>\n";
    echo "<ul>\n";
    echo "<li><strong>Host:</strong> " . htmlspecialchars($db_config['host']) . "</li>\n";
    echo "<li><strong>Puerto:</strong> " . htmlspecialchars($db_config['port']) . "</li>\n";
    echo "<li><strong>Base de Datos:</strong> " . htmlspecialchars($db_config['database']) . "</li>\n";
    echo "<li><strong>Usuario:</strong> " . htmlspecialchars($db_config['username']) . "</li>\n";
    echo "<li><strong>Charset:</strong> " . htmlspecialchars($db_config['charset']) . "</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
    
    echo "<div class='info'>";
    echo "<h2>🔌 Intentando conectar...</h2>\n";
    echo "</div>\n";
    
    // Intentar conexión
    $pdo = getDatabaseConnection();
    
    echo "<div class='success'>";
    echo "<h2>✅ Conexión Exitosa</h2>\n";
    echo "<p>La conexión a la base de datos se estableció correctamente.</p>\n";
    echo "</div>\n";
    
    // Probar consulta simple
    echo "<div class='info'>";
    echo "<h2>🧪 Ejecutando consulta de prueba...</h2>\n";
    echo "</div>\n";
    
    $stmt = $pdo->query("SELECT 1 as test, NOW() as fecha_servidor, DATABASE() as base_datos, USER() as usuario");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "<div class='success'>";
        echo "<h2>✅ Consulta de Prueba Exitosa</h2>\n";
        echo "<pre>";
        print_r($result);
        echo "</pre>\n";
        echo "</div>\n";
    }
    
    // Obtener información de la versión de MySQL
    $stmt = $pdo->query("SELECT VERSION() as version_mysql");
    $version = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($version) {
        echo "<div class='info'>";
        echo "<h2>📊 Información del Servidor MySQL</h2>\n";
        echo "<ul>\n";
        echo "<li><strong>Versión MySQL:</strong> " . htmlspecialchars($version['version_mysql']) . "</li>\n";
        echo "</ul>\n";
        echo "</div>\n";
    }
    
    // Probar listar algunas tablas
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if ($tables) {
        echo "<div class='info'>";
        echo "<h2>📋 Tablas en la Base de Datos</h2>\n";
        echo "<p>Total de tablas encontradas: <strong>" . count($tables) . "</strong></p>\n";
        echo "<ul>\n";
        foreach (array_slice($tables, 0, 10) as $table) {
            echo "<li>" . htmlspecialchars($table) . "</li>\n";
        }
        if (count($tables) > 10) {
            echo "<li><em>... y " . (count($tables) - 10) . " más</em></li>\n";
        }
        echo "</ul>\n";
        echo "</div>\n";
    }
    
    echo "<div class='success'>";
    echo "<h2>🎉 Prueba Completada con Éxito</h2>\n";
    echo "<p>Todos los tests de conexión pasaron correctamente.</p>\n";
    echo "</div>\n";
    
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Error de Conexión PDO</h2>\n";
    echo "<p><strong>Mensaje:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p><strong>Código:</strong> " . htmlspecialchars($e->getCode()) . "</p>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Error General</h2>\n";
    echo "<p><strong>Mensaje:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "</div>\n";
}

echo "</body></html>\n";
?>
