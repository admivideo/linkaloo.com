<?php
/**
 * Script de testing para Rainforest API
 * 
 * ANTES DE EJECUTAR:
 * 1. Editar rainforest_config.php
 * 2. Agregar tu API Key de Rainforest
 * 3. Ejecutar: php test_rainforest_api.php
 */

require_once 'rainforest_config.php';

echo "========================================\n";
echo "  TEST: Rainforest API para Amazon  \n";
echo "========================================\n\n";

// Verificar que la API key esté configurada
if (RAINFOREST_API_KEY === 'TU_API_KEY_AQUI' || empty(RAINFOREST_API_KEY)) {
    echo "❌ ERROR: API Key no configurada\n";
    echo "\nPor favor:\n";
    echo "1. Edita: server_endpoints/rainforest_config.php\n";
    echo "2. Reemplaza 'TU_API_KEY_AQUI' con tu API key real\n";
    echo "3. Vuelve a ejecutar este script\n\n";
    exit(1);
}

echo "✅ API Key configurada\n";
echo "API Key: " . substr(RAINFOREST_API_KEY, 0, 15) . "...\n\n";

// URLs de prueba
$testUrls = [
    'https://www.amazon.es/dp/B0FV95XQVF',  // Beneath - PS5 (válido)
    'https://www.amazon.es/dp/B0D1XD1ZV3',  // Producto válido
    'https://www.amazon.com/dp/B0D1XD1ZV3'  // Amazon USA
];

foreach ($testUrls as $index => $testUrl) {
    $testNum = $index + 1;
    echo "--- TEST $testNum ---\n";
    echo "URL: $testUrl\n\n";
    
    // Test 1: Extraer ASIN
    echo "1. Extrayendo ASIN:\n";
    $asin = extractAmazonAsin($testUrl);
    echo "   ASIN: " . ($asin ?? 'N/A') . "\n";
    
    // Test 2: Detectar dominio
    echo "\n2. Detectando dominio:\n";
    $domain = detectAmazonDomain($testUrl);
    echo "   Dominio: " . $domain . "\n";
    
    // Test 3: Rainforest API completa
    echo "\n3. Llamando a Rainforest API:\n";
    
    // Habilitar output de errores
    ob_start();
    $metadata = getAmazonDataWithRainforest($testUrl);
    $output = ob_get_clean();
    
    if ($metadata) {
        echo "   ✅ API EXITOSA\n";
        echo "   Título: " . ($metadata['title'] ?? 'N/A') . "\n";
        echo "   Descripción: " . substr($metadata['description'] ?? 'N/A', 0, 80) . "...\n";
        echo "   Imagen: " . (isset($metadata['image']) && $metadata['image'] ? 'SÍ' : 'NO') . "\n";
        if (isset($metadata['image']) && $metadata['image']) {
            echo "   URL Imagen: " . substr($metadata['image'], 0, 80) . "...\n";
        }
    } else {
        echo "   ❌ API FALLÓ\n";
        echo "   IMPORTANTE: Revisa los logs de error de PHP en tu servidor\n";
        echo "   Los logs mostrarán el error exacto de Rainforest API\n";
        if ($output) {
            echo "\n   Output capturado:\n";
            echo "   " . str_replace("\n", "\n   ", $output) . "\n";
        }
    }
    
    echo "\n";
    echo "========================================\n\n";
    
    // Pequeña pausa entre tests
    if ($testNum < count($testUrls)) {
        echo "Esperando 1 segundo...\n\n";
        sleep(1);
    }
}

echo "RESUMEN:\n";
echo "- Total de URLs probadas: " . count($testUrls) . "\n";
echo "- Verifica los logs para más detalles\n";
echo "\n✅ Testing completado\n";
echo "\nSi todo funcionó correctamente, tu configuración está lista para producción.\n";
?>

