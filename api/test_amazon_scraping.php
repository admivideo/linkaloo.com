<?php
/**
 * Script de testing para Amazon scraping
 * Ejecutar: php test_amazon_scraping.php
 */

require_once 'share_api.php';

echo "========================================\n";
echo "  TEST: Amazon Scraping Implementation  \n";
echo "========================================\n\n";

// URLs de prueba de Amazon
$testUrls = [
    // Amazon España - URLs válidas
    'https://www.amazon.es/dp/B0FV95XQVF',  // Beneath - PS5
    'https://www.amazon.es/dp/B0D1XD1ZV3',  // Producto válido
    
    // Amazon USA
    'https://www.amazon.com/dp/B0D1XD1ZV3',
    
    // URL con parámetros de tracking (debe limpiarla)
    'https://www.amazon.es/dp/B0FV95XQVF/ref=sr_1_1?keywords=beneath&qid=1697457600&sr=8-1'
];

foreach ($testUrls as $index => $testUrl) {
    $testNum = $index + 1;
    echo "--- TEST $testNum ---\n";
    echo "URL: $testUrl\n\n";
    
    // Test 1: Limpiar URL
    echo "1. Testing cleanAmazonUrl():\n";
    $cleanUrl = cleanAmazonUrl($testUrl);
    echo "   URL limpia: $cleanUrl\n";
    
    // Test 2: Función completa
    echo "\n2. Testing scrapeAmazonMetadata():\n";
    $metadata = scrapeAmazonMetadata($testUrl);
    
    if ($metadata && count($metadata) > 0) {
        echo "   ✅ Metadatos OBTENIDOS\n";
        echo "   Título: " . ($metadata['title'] ?? 'N/A') . "\n";
        echo "   Descripción: " . substr($metadata['description'] ?? 'N/A', 0, 80) . "...\n";
        echo "   Imagen: " . (isset($metadata['image']) && $metadata['image'] ? 'SÍ' : 'NO') . "\n";
        if (isset($metadata['image']) && $metadata['image']) {
            echo "   URL Imagen: " . substr($metadata['image'], 0, 100) . "...\n";
        }
    } else {
        echo "   ❌ No se obtuvieron metadatos\n";
    }
    
    echo "\n";
    echo "========================================\n\n";
    
    // Pequeña pausa entre requests para no ser bloqueado
    if ($testNum < count($testUrls)) {
        echo "Esperando 2 segundos antes del siguiente test...\n\n";
        sleep(2);
    }
}

echo "RESUMEN:\n";
echo "- Total de URLs probadas: " . count($testUrls) . "\n";
echo "- Verifica los logs para más detalles\n";
echo "\n✅ Testing completado\n";
?>

