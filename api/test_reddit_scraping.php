<?php
/**
 * Script de prueba para scraping de Reddit
 * Analiza URLs de Reddit para extraer metadatos optimizados
 */

header('Content-Type: text/html; charset=UTF-8');

echo "<h1>🔍 Análisis de Scraping de Reddit</h1>";
echo "<hr>";

// URLs de prueba
$testUrls = [
    'https://www.reddit.com/r/mildlyinfuriating/s/quuO4k8K2u',  // URL móvil corta
    'https://www.reddit.com/r/programming/comments/example',  // URL desktop (ejemplo)
];

foreach ($testUrls as $url) {
    echo "<h2>📌 Analizando: " . htmlspecialchars($url) . "</h2>";
    
    // Expandir URL corta si es necesario
    if (strpos($url, '/s/') !== false) {
        echo "<p><strong>🔗 URL móvil corta detectada (/s/)</strong></p>";
        $expandedUrl = expandRedditShortUrl($url);
        echo "<p>URL expandida: " . htmlspecialchars($expandedUrl) . "</p>";
        $urlToScrape = $expandedUrl;
    } else {
        $urlToScrape = $url;
    }
    
    // Probar API JSON de Reddit (método preferido)
    echo "<h3>🎯 Método 1: Reddit JSON API</h3>";
    $jsonMetadata = getRedditJsonMetadata($urlToScrape);
    echo "<pre>";
    print_r($jsonMetadata);
    echo "</pre>";
    
    // Probar scraping HTML tradicional (fallback)
    echo "<h3>🎯 Método 2: Scraping HTML</h3>";
    $htmlMetadata = scrapeRedditHtmlMetadata($urlToScrape);
    echo "<pre>";
    print_r($htmlMetadata);
    echo "</pre>";
    
    echo "<hr>";
}

/**
 * Expande URLs cortas de Reddit móvil (/s/)
 */
function expandRedditShortUrl($shortUrl) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $shortUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_NOBODY => true,  // Solo headers para redirección
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    ]);
    
    curl_exec($ch);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    
    echo "<p>HTTP Code: $httpCode</p>";
    echo "<p>Final URL: " . htmlspecialchars($finalUrl) . "</p>";
    
    return $finalUrl ?: $shortUrl;
}

/**
 * Método preferido: Reddit JSON API
 * Reddit permite agregar .json a cualquier URL para obtener datos estructurados
 */
function getRedditJsonMetadata($url) {
    // Limpiar URL y agregar .json
    $urlParsed = parse_url($url);
    $path = rtrim($urlParsed['path'], '/');
    
    // Construir URL JSON
    $jsonUrl = 'https://www.reddit.com' . $path . '.json';
    
    echo "<p>🌐 JSON URL: " . htmlspecialchars($jsonUrl) . "</p>";
    
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $jsonUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Linkaloo/1.0 (Contact: your@email.com)',  // User-Agent para API
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
        ],
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
    echo "<p><strong>Response Length:</strong> " . strlen($response) . " bytes</p>";
    
    curl_close($ch);
    
    if ($httpCode != 200 || empty($response)) {
        echo "<p>❌ JSON API falló con código: $httpCode</p>";
        return [
            'error' => 'JSON API Error: ' . $httpCode,
            'method' => 'json_api',
        ];
    }
    
    // Decodificar JSON
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "<p>❌ Error decodificando JSON: " . json_last_error_msg() . "</p>";
        return [
            'error' => 'JSON decode error',
            'method' => 'json_api',
        ];
    }
    
    // Mostrar estructura JSON (primeros 3000 caracteres)
    echo "<details><summary>📋 Ver estructura JSON</summary>";
    echo "<pre>" . htmlspecialchars(substr(json_encode($data, JSON_PRETTY_PRINT), 0, 3000)) . "...</pre>";
    echo "</details>";
    
    // Extraer metadatos del JSON
    $metadata = [
        'method' => 'json_api',
        'titulo' => '',
        'descripcion' => '',
        'imagen' => '',
        'url_canonica' => '',
        'autor' => '',
        'subreddit' => '',
        'upvotes' => 0,
        'tipo' => '',
    ];
    
    // Navegar por la estructura de Reddit JSON
    // Estructura típica: [0][data][children][0][data]
    if (isset($data[0]['data']['children'][0]['data'])) {
        $postData = $data[0]['data']['children'][0]['data'];
        
        $metadata['titulo'] = $postData['title'] ?? '';
        $metadata['descripcion'] = $postData['selftext'] ?? '';
        $metadata['autor'] = $postData['author'] ?? '';
        $metadata['subreddit'] = $postData['subreddit'] ?? '';
        $metadata['upvotes'] = $postData['ups'] ?? 0;
        $metadata['url_canonica'] = 'https://www.reddit.com' . ($postData['permalink'] ?? '');
        
        // Imagen - Reddit tiene varias fuentes posibles
        if (!empty($postData['preview']['images'][0]['source']['url'])) {
            $metadata['imagen'] = html_entity_decode($postData['preview']['images'][0]['source']['url']);
        } elseif (!empty($postData['thumbnail']) && $postData['thumbnail'] !== 'self' && $postData['thumbnail'] !== 'default') {
            $metadata['imagen'] = $postData['thumbnail'];
        } elseif (!empty($postData['url_overridden_by_dest'])) {
            // Para posts con imágenes externas
            $extUrl = $postData['url_overridden_by_dest'];
            if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $extUrl)) {
                $metadata['imagen'] = $extUrl;
            }
        }
        
        // Tipo de post
        if (!empty($postData['post_hint'])) {
            $metadata['tipo'] = $postData['post_hint'];  // image, video, link, self
        }
        
        echo "<p>✅ Datos extraídos del JSON:</p>";
        echo "<ul>";
        echo "<li>Título: " . htmlspecialchars(substr($metadata['titulo'], 0, 100)) . "</li>";
        echo "<li>Autor: " . htmlspecialchars($metadata['autor']) . "</li>";
        echo "<li>Subreddit: r/" . htmlspecialchars($metadata['subreddit']) . "</li>";
        echo "<li>Upvotes: " . $metadata['upvotes'] . "</li>";
        echo "<li>Tipo: " . htmlspecialchars($metadata['tipo']) . "</li>";
        echo "<li>Imagen: " . (empty($metadata['imagen']) ? 'No' : 'Sí') . "</li>";
        echo "</ul>";
    } else {
        echo "<p>⚠️ Estructura JSON no esperada</p>";
        $metadata['error'] = 'Estructura JSON desconocida';
    }
    
    return $metadata;
}

/**
 * Fallback: Scraping HTML con OpenGraph tags
 */
function scrapeRedditHtmlMetadata($url) {
    $ch = curl_init();
    
    $headers = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language: es-ES,es;q=0.9,en;q=0.8',
        'Accept-Encoding: gzip, deflate, br',
    ];
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_ENCODING => '',
    ]);
    
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
    echo "<p><strong>HTML Length:</strong> " . strlen($html) . " bytes</p>";
    
    curl_close($ch);
    
    if ($httpCode != 200 || empty($html)) {
        return [
            'error' => 'HTTP Error: ' . $httpCode,
            'method' => 'html_scraping',
        ];
    }
    
    // Parsear HTML
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    
    $metadata = [
        'method' => 'html_scraping',
        'titulo' => '',
        'descripcion' => '',
        'imagen' => '',
    ];
    
    // Extraer OpenGraph tags
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
    
    return $metadata;
}

echo "<h2>✅ Análisis completado</h2>";
echo "<p>Sube este archivo a tu servidor en: <code>https://linkaloo.com/api/test_reddit_scraping.php</code></p>";
echo "<p><strong>NOTA:</strong> Reddit tiene una excelente API JSON pública - solo hay que agregar .json a cualquier URL!</p>";
?>

