<?php
/**
 * Script de prueba para scraping de Quora
 * Analiza URLs de Quora (qr.ae y quora.com) para extraer metadatos
 */

header('Content-Type: text/html; charset=UTF-8');

echo "<h1>🔍 Análisis de Scraping de Quora</h1>";
echo "<hr>";

// URLs de prueba
$testUrls = [
    'https://qr.ae/pCK7nS',  // URL corta de Quora
    'https://www.quora.com/What-is-the-best-way-to-learn-programming',  // URL larga ejemplo
];

foreach ($testUrls as $url) {
    echo "<h2>📌 Analizando: " . htmlspecialchars($url) . "</h2>";
    
    // Expandir URL corta si es necesario
    if (strpos($url, 'qr.ae') !== false) {
        echo "<p><strong>🔗 URL corta detectada (qr.ae)</strong></p>";
        $expandedUrl = expandQuoraShortUrl($url);
        echo "<p>URL expandida: " . htmlspecialchars($expandedUrl) . "</p>";
        $url = $expandedUrl;
    }
    
    // Intentar scraping
    $metadata = scrapeQuoraMetadata($url);
    
    echo "<h3>📊 Metadatos extraídos:</h3>";
    echo "<pre>";
    print_r($metadata);
    echo "</pre>";
    echo "<hr>";
}

/**
 * Expande URLs cortas de Quora (qr.ae)
 */
function expandQuoraShortUrl($shortUrl) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $shortUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,  // Seguir redirecciones
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_HEADER => false,
    ]);
    
    $response = curl_exec($ch);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    
    echo "<p>HTTP Code: $httpCode</p>";
    echo "<p>Final URL: " . htmlspecialchars($finalUrl) . "</p>";
    
    return $finalUrl ?: $shortUrl;
}

/**
 * Scraping de metadatos de Quora
 */
function scrapeQuoraMetadata($url) {
    $ch = curl_init();
    
    // Headers completos para simular navegador real
    $headers = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language: es-ES,es;q=0.9,en;q=0.8',
        'Accept-Encoding: gzip, deflate, br',
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1',
        'Sec-Fetch-Dest: document',
        'Sec-Fetch-Mode: navigate',
        'Sec-Fetch-Site: none',
        'Cache-Control: max-age=0',
    ];
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_ENCODING => '',  // Descomprimir automáticamente
    ]);
    
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
    echo "<p><strong>HTML Length:</strong> " . strlen($html) . " bytes</p>";
    
    curl_close($ch);
    
    if ($httpCode != 200 || empty($html)) {
        return [
            'error' => 'HTTP Error: ' . $httpCode,
            'titulo' => '',
            'descripcion' => '',
            'imagen' => '',
        ];
    }
    
    // Analizar HTML
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    
    $metadata = [
        'titulo' => '',
        'descripcion' => '',
        'imagen' => '',
        'url_canonica' => '',
    ];
    
    // Método 1: OpenGraph tags (og:)
    $ogTitle = $xpath->query('//meta[@property="og:title"]/@content');
    if ($ogTitle->length > 0) {
        $metadata['titulo'] = $ogTitle->item(0)->nodeValue;
        echo "<p>✅ og:title encontrado</p>";
    }
    
    $ogDescription = $xpath->query('//meta[@property="og:description"]/@content');
    if ($ogDescription->length > 0) {
        $metadata['descripcion'] = $ogDescription->item(0)->nodeValue;
        echo "<p>✅ og:description encontrado</p>";
    }
    
    $ogImage = $xpath->query('//meta[@property="og:image"]/@content');
    if ($ogImage->length > 0) {
        $metadata['imagen'] = $ogImage->item(0)->nodeValue;
        echo "<p>✅ og:image encontrado</p>";
    }
    
    $ogUrl = $xpath->query('//meta[@property="og:url"]/@content');
    if ($ogUrl->length > 0) {
        $metadata['url_canonica'] = $ogUrl->item(0)->nodeValue;
        echo "<p>✅ og:url encontrado</p>";
    }
    
    // Método 2: Twitter Card tags (fallback)
    if (empty($metadata['titulo'])) {
        $twitterTitle = $xpath->query('//meta[@name="twitter:title"]/@content');
        if ($twitterTitle->length > 0) {
            $metadata['titulo'] = $twitterTitle->item(0)->nodeValue;
            echo "<p>✅ twitter:title encontrado</p>";
        }
    }
    
    if (empty($metadata['descripcion'])) {
        $twitterDescription = $xpath->query('//meta[@name="twitter:description"]/@content');
        if ($twitterDescription->length > 0) {
            $metadata['descripcion'] = $twitterDescription->item(0)->nodeValue;
            echo "<p>✅ twitter:description encontrado</p>";
        }
    }
    
    if (empty($metadata['imagen'])) {
        $twitterImage = $xpath->query('//meta[@name="twitter:image"]/@content');
        if ($twitterImage->length > 0) {
            $metadata['imagen'] = $twitterImage->item(0)->nodeValue;
            echo "<p>✅ twitter:image encontrado</p>";
        }
    }
    
    // Método 3: Meta tags estándar (fallback)
    if (empty($metadata['titulo'])) {
        $titleTag = $xpath->query('//title');
        if ($titleTag->length > 0) {
            $metadata['titulo'] = $titleTag->item(0)->nodeValue;
            echo "<p>✅ title tag encontrado</p>";
        }
    }
    
    if (empty($metadata['descripcion'])) {
        $metaDescription = $xpath->query('//meta[@name="description"]/@content');
        if ($metaDescription->length > 0) {
            $metadata['descripcion'] = $metaDescription->item(0)->nodeValue;
            echo "<p>✅ meta description encontrado</p>";
        }
    }
    
    // Buscar JSON embebido (similar a Wallapop)
    $scripts = $xpath->query('//script[@type="application/ld+json"]');
    if ($scripts->length > 0) {
        echo "<p>📋 JSON-LD encontrado: " . $scripts->length . " elementos</p>";
        for ($i = 0; $i < $scripts->length; $i++) {
            $jsonContent = $scripts->item($i)->nodeValue;
            echo "<details><summary>Ver JSON-LD #" . ($i+1) . "</summary><pre>" . 
                 htmlspecialchars(substr($jsonContent, 0, 500)) . "...</pre></details>";
        }
    }
    
    // Mostrar primeros 2000 caracteres del HTML para análisis
    echo "<details><summary>🔍 Ver primeros 2000 caracteres del HTML</summary>";
    echo "<pre>" . htmlspecialchars(substr($html, 0, 2000)) . "...</pre>";
    echo "</details>";
    
    return $metadata;
}

echo "<h2>✅ Análisis completado</h2>";
echo "<p>Sube este archivo a tu servidor en: <code>https://linkaloo.com/api/test_quora_scraping.php</code></p>";
?>

