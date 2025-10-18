<?php
/**
 * Funciones de scraping para diferentes plataformas
 * Optimizado y refactorizado - 17 de Octubre de 2025
 */

require_once 'scraping_logger.php';

/**
 * Expande URL corta de TEMU (temu.to, share.temu.com)
 */
function expandTemuShortUrl($url) {
    $log = createScrapingLogger('TEMU-EXPAND');
    $log("Expandiendo URL: " . $url);
    
    $ch = createScrapingCurl($url, [
        CURLOPT_NOBODY => true,
        CURLOPT_MAXREDIRS => 10
    ]);
    
    $result = executeCurlWithLogging($ch, $log);
    
    if ($result['httpCode'] >= 200 && $result['httpCode'] < 400 && 
        $result['finalUrl'] && $result['finalUrl'] !== $url) {
        $log("✅ URL expandida exitosamente");
        return $result['finalUrl'];
    }
    
    $log("⚠️ No se pudo expandir, usando original");
    return $url;
}

/**
 * Scraping específico para TEMU
 */
function scrapeTemuMetadata($url) {
    $log = createScrapingLogger('TEMU');
    $log("=== INICIANDO SCRAPE ===");
    $log("URL: " . $url);
    
    // Expandir URL corta si es necesario
    if (strpos($url, 'temu.to') !== false || strpos($url, 'share.temu.com') !== false) {
        $url = expandTemuShortUrl($url);
    }
    
    // Intentar extraer de parámetros URL
    $parsedUrl = parse_url($url);
    if (isset($parsedUrl['query'])) {
        parse_str($parsedUrl['query'], $params);
        
        if (isset($params['share_img']) || isset($params['thumb_url'])) {
            $meta = [
                'title' => 'Producto TEMU',
                'description' => 'Ver producto en TEMU',
                'image' => $params['share_img'] ?? $params['thumb_url']
            ];
            $log("✅ Metadatos extraídos de URL");
            return $meta;
        }
    }
    
    $log("⚠️ Sin metadatos en URL, intentando scraping HTML");
    // Fallback a scraping HTML si es necesario...
    return [];
}

/**
 * Scraping específico para Wallapop
 */
function scrapeWallapopMetadata($url) {
    $log = createScrapingLogger('WALLAPOP');
    $log("=== INICIANDO SCRAPE ===");
    $log("URL: " . $url);
    
    $ch = createScrapingCurl($url);
    $result = executeCurlWithLogging($ch, $log);
    
    if ($result['httpCode'] !== 200) {
        $log("❌ Error HTTP " . $result['httpCode']);
        return [];
    }
    
    // Intentar extraer JSON de Next.js
    $data = extractNextDataJson($result['html'], $log);
    
    if ($data && isset($data['props']['pageProps']['item'])) {
        $item = $data['props']['pageProps']['item'];
        $log("✅ Item encontrado en JSON");
        
        $meta = [
            'title' => is_array($item['title'] ?? null) ? implode(' ', $item['title']) : ($item['title'] ?? ''),
            'description' => is_array($item['description'] ?? null) ? implode(' ', $item['description']) : ($item['description'] ?? ''),
            'image' => $item['images'][0]['original'] ?? $item['images'][0]['medium'] ?? $item['image'] ?? ''
        ];
        
        if ($meta['description']) {
            $meta['description'] = limitDescription($meta['description']);
        }
        
        if (!empty($meta['title']) && !empty($meta['image'])) {
            $log("✅ Extracción exitosa");
            return $meta;
        }
    }
    
    $log("⚠️ JSON no disponible, fallback a scraping HTML");
    // Fallback...
    return [];
}

/**
 * Scraping específico para Idealista
 */
function scrapeIdealistaMetadata($url) {
    $log = createScrapingLogger('IDEALISTA');
    $log("=== INICIANDO SCRAPE ===");
    
    // Limpiar URL de parámetros tracking
    $url = preg_replace('/[\?&](utm_[^&]+)/', '', $url);
    $url = rtrim($url, '?&');
    $log("URL limpia: " . $url);
    
    $ch = createScrapingCurl($url, [
        CURLOPT_REFERER => 'https://www.idealista.com/'
    ]);
    
    $result = executeCurlWithLogging($ch, $log);
    
    // Detectar Cloudflare
    if ($result['httpCode'] === 403 && 
        strpos($result['html'], 'Please enable JS') !== false) {
        $log("🛡️ Cloudflare detectado");
        return createBlockedPlatformResponse(
            'Inmueble en Idealista',
            'https://st3.idealista.com/static/common/img/logo-idealista.svg',
            'cloudflare_protection'
        );
    }
    
    if ($result['httpCode'] !== 200) {
        return [];
    }
    
    // Intentar extraer JSON de Next.js
    $data = extractNextDataJson($result['html'], $log);
    // ... resto del código ...
    
    return [];
}

/**
 * Scraping específico para Milanuncios
 */
function scrapeMilanunciosMetadata($url) {
    $log = createScrapingLogger('MILANUNCIOS');
    $log("=== INICIANDO SCRAPE ===");
    $log("URL: " . $url);
    
    $ch = createScrapingCurl($url);
    $result = executeCurlWithLogging($ch, $log);
    
    // Detectar protección anti-bot
    if ($result['httpCode'] === 403 && 
        strpos($result['html'], 'Pardon Our Interruption') !== false) {
        $log("🛡️ Protección anti-bot detectada");
        return createBlockedPlatformResponse(
            'Anuncio en Milanuncios',
            'https://www.milanuncios.com/static/img/logo-milanuncios.svg',
            'anti_bot_protection'
        );
    }
    
    if ($result['httpCode'] !== 200) {
        return [];
    }
    
    // Scraping HTML estándar
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    @$doc->loadHTML($result['html']);
    $xpath = new DOMXPath($doc);
    
    $meta = [];
    
    // Título
    $titleSelectors = [
        '//meta[@property="og:title"]/@content',
        '//h1',
        '//title'
    ];
    $meta['title'] = extractWithSelectors($xpath, $titleSelectors, $log, 'Título');
    
    // Descripción
    $descSelectors = [
        '//meta[@property="og:description"]/@content',
        '//meta[@name="description"]/@content'
    ];
    $meta['description'] = extractWithSelectors($xpath, $descSelectors, $log, 'Descripción');
    
    if ($meta['description']) {
        $meta['description'] = limitDescription($meta['description']);
    }
    
    // Imagen
    $imageSelectors = [
        '//meta[@property="og:image"]/@content',
        '//img[contains(@src, "milanuncios.com")]/@src'
    ];
    $meta['image'] = extractWithSelectors($xpath, $imageSelectors, $log, 'Imagen');
    $meta['image'] = normalizeImageUrl($meta['image'] ?? '');
    
    if ($meta['title']) {
        $meta['title'] = cleanTitle($meta['title'], 'Milanuncios');
    }
    
    $log("✅ Scraping completado");
    return array_filter($meta); // Remover valores vacíos
}
?>



