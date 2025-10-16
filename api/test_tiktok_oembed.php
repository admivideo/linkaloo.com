<?php
/**
 * Script de testing para TikTok oembed API
 * Ejecutar: php test_tiktok_oembed.php
 */

require_once 'share_api.php';

echo "========================================\n";
echo "  TEST: TikTok oembed API Integration  \n";
echo "========================================\n\n";

// URLs de prueba
$testUrls = [
    'https://www.tiktok.com/@tiktok/video/7106594312292453675',
    'https://www.tiktok.com/@zachking/video/7145716465730432302',
    'https://vm.tiktok.com/ZMhKqWxyz/' // URL corta (puede no existir)
];

foreach ($testUrls as $index => $testUrl) {
    $testNum = $index + 1;
    echo "--- TEST $testNum ---\n";
    echo "URL: $testUrl\n\n";
    
    // Probar oembed directo
    echo "1. Testing oembed API directo:\n";
    $oembedData = getTikTokOembed($testUrl);
    
    if ($oembedData) {
        echo "   ✅ oembed EXITOSO\n";
        echo "   Título: " . ($oembedData['title'] ?? 'N/A') . "\n";
        echo "   Autor: " . ($oembedData['author_name'] ?? 'N/A') . "\n";
        echo "   Thumbnail URL: " . (isset($oembedData['thumbnail_url']) ? 'SÍ' : 'NO') . "\n";
        if (isset($oembedData['thumbnail_url'])) {
            $thumbUrl = $oembedData['thumbnail_url'];
            echo "   Thumbnail: " . substr($thumbUrl, 0, 80) . "...\n";
        }
    } else {
        echo "   ❌ oembed FALLÓ\n";
    }
    
    echo "\n2. Testing función completa scrapeTikTokMetadata():\n";
    $metadata = scrapeTikTokMetadata($testUrl);
    
    if ($metadata) {
        echo "   ✅ Metadatos OBTENIDOS\n";
        echo "   Título: " . ($metadata['title'] ?? 'N/A') . "\n";
        echo "   Descripción: " . substr($metadata['description'] ?? 'N/A', 0, 60) . "...\n";
        echo "   Imagen: " . (isset($metadata['image']) && $metadata['image'] ? 'SÍ' : 'NO') . "\n";
        if (isset($metadata['image']) && $metadata['image']) {
            echo "   URL Imagen: " . substr($metadata['image'], 0, 80) . "...\n";
        }
    } else {
        echo "   ❌ No se obtuvieron metadatos\n";
    }
    
    echo "\n";
    echo "========================================\n\n";
}

echo "RESUMEN:\n";
echo "- Total de URLs probadas: " . count($testUrls) . "\n";
echo "- Verifica los logs para más detalles\n";
echo "\n✅ Testing completado\n";
?>

