<?php
/**
 * Script de testing para TEMU scraping
 * Ejecutar: php test_temu_scraping.php
 */

require_once 'share_api.php';

echo "========================================\n";
echo "  TEST: TEMU Scraping Implementation  \n";
echo "========================================\n\n";

// URLs de prueba de TEMU
$testUrls = [
    'https://www.temu.com/smart-watch-fitness-tracker-g-601099508489989.html',
    'https://www.temu.com/wireless-earbuds-bluetooth-headphones-g-601099234567890.html',
    'https://temu.to/m/abc123'  // URL corta (puede no existir)
];

foreach ($testUrls as $index => $testUrl) {
    $testNum = $index + 1;
    echo "--- TEST $testNum ---\n";
    echo "URL: $testUrl\n\n";
    
    // Test: Función completa
    echo "Testing scrapeTemuMetadata():\n";
    $metadata = scrapeTemuMetadata($testUrl);
    
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
    
    // Pequeña pausa entre tests
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

