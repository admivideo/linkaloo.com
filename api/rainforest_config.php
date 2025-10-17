<?php
/**
 * Configuración de Rainforest API para Amazon
 * 
 * Instrucciones:
 * 1. Registrarse en https://www.rainforestapi.com/
 * 2. Obtener API Key del dashboard
 * 3. Reemplazar 'TU_API_KEY_AQUI' con tu API key real
 */

// ⚠️ IMPORTANTE: Reemplaza esto con tu API Key real
define('RAINFOREST_API_KEY', 'ABB9AE0FE81C43FD8871176F05E3CD6F');

// URL base de Rainforest API
define('RAINFOREST_API_URL', 'https://api.rainforestapi.com/request');

/**
 * Obtener información de producto de Amazon usando Rainforest API
 * 
 * @param string $url URL del producto de Amazon
 * @return array|null Metadatos del producto o null si falla
 */
function getAmazonDataWithRainforest($url) {
    error_log("=== RAINFOREST API PARA AMAZON ===");
    error_log("URL original: " . $url);
    
    // Verificar que la API key esté configurada
    if (RAINFOREST_API_KEY === 'ABB9AE0FE81C43FD8871176F05E3CD6F') {
        error_log("⚠️ ERROR: Rainforest API Key no configurada");
        error_log("Por favor, edita rainforest_config.php y agrega tu API Key");
        return null;
    }
    
    // Extraer ASIN de la URL
    $asin = extractAmazonAsin($url);
    
    if (!$asin) {
        error_log("❌ No se pudo extraer ASIN de la URL");
        return null;
    }
    
    error_log("ASIN extraído: " . $asin);
    
    // Detectar dominio de Amazon
    $amazonDomain = detectAmazonDomain($url);
    error_log("Dominio detectado: " . $amazonDomain);
    
    // Construir URL de Rainforest API
    $apiUrl = RAINFOREST_API_URL . '?' . http_build_query([
        'api_key' => RAINFOREST_API_KEY,
        'type' => 'product',
        'amazon_domain' => $amazonDomain,
        'asin' => $asin
    ]);
    
    error_log("Llamando a Rainforest API...");
    
    // Hacer request a Rainforest API
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("❌ Error cURL en Rainforest: " . $error);
        return null;
    }
    
    error_log("Rainforest HTTP Code: " . $httpCode);
    error_log("Rainforest Response: " . substr($response ?? 'EMPTY', 0, 1000));
    
    if ($httpCode !== 200) {
        error_log("❌ Rainforest API respondió con HTTP " . $httpCode);
        
        // Intentar decodificar el error
        $errorData = json_decode($response ?? '{}', true);
        if (isset($errorData['error'])) {
            error_log("Error de Rainforest: " . $errorData['error']);
        }
        if (isset($errorData['message'])) {
            error_log("Mensaje de Rainforest: " . $errorData['message']);
        }
        
        return null;
    }
    
    $data = json_decode($response, true);
    
    if (!$data) {
        error_log("❌ Error decodificando JSON de Rainforest");
        error_log("JSON Error: " . json_last_error_msg());
        return null;
    }
    
    // Verificar que tengamos datos del producto
    if (!isset($data['product'])) {
        error_log("❌ Rainforest no devolvió datos de producto");
        error_log("Respuesta completa: " . json_encode($data));
        
        // Verificar si hay mensaje de error
        if (isset($data['error'])) {
            error_log("Error API: " . $data['error']);
        }
        if (isset($data['request_info'])) {
            error_log("Request Info: " . json_encode($data['request_info']));
        }
        
        return null;
    }
    
    $product = $data['product'];
    
    // Extraer metadatos
    $metadata = [
        'title' => $product['title'] ?? 'Producto de Amazon',
        'description' => '',
        'image' => $product['main_image']['link'] ?? null
    ];
    
    // Construir descripción desde feature bullets
    if (isset($product['feature_bullets']) && is_array($product['feature_bullets'])) {
        $metadata['description'] = implode('. ', array_slice($product['feature_bullets'], 0, 3));
        if (strlen($metadata['description']) > 300) {
            $metadata['description'] = substr($metadata['description'], 0, 297) . '...';
        }
    }
    
    error_log("✅ Metadatos obtenidos de Rainforest API:");
    error_log("Título: " . $metadata['title']);
    error_log("Descripción: " . substr($metadata['description'], 0, 100) . "...");
    error_log("Imagen: " . ($metadata['image'] ?? 'N/A'));
    
    return $metadata;
}

/**
 * Extraer ASIN de URL de Amazon
 */
function extractAmazonAsin($url) {
    // Expandir URL corta si es necesario
    if (strpos($url, 'amzn.') !== false || strpos($url, 'a.co') !== false) {
        $url = expandAmazonShortUrl($url);
    }
    
    // Extraer ASIN (10 caracteres alfanuméricos)
    if (preg_match('/\/(dp|gp\/product)\/([A-Z0-9]{10})/', $url, $matches)) {
        return $matches[2];
    }
    
    return null;
}

/**
 * Detectar dominio de Amazon
 */
function detectAmazonDomain($url) {
    // Expandir URL corta primero
    if (strpos($url, 'amzn.') !== false || strpos($url, 'a.co') !== false) {
        $url = expandAmazonShortUrl($url);
    }
    
    // Detectar dominio específico
    if (strpos($url, 'amazon.es') !== false) return 'amazon.es';
    if (strpos($url, 'amazon.com') !== false) return 'amazon.com';
    if (strpos($url, 'amazon.co.uk') !== false) return 'amazon.co.uk';
    if (strpos($url, 'amazon.de') !== false) return 'amazon.de';
    if (strpos($url, 'amazon.fr') !== false) return 'amazon.fr';
    if (strpos($url, 'amazon.it') !== false) return 'amazon.it';
    if (strpos($url, 'amazon.ca') !== false) return 'amazon.ca';
    if (strpos($url, 'amazon.com.mx') !== false) return 'amazon.com.mx';
    
    // Default: amazon.com
    return 'amazon.com';
}

/**
 * Expandir URL corta de Amazon (compartida con share_api.php)
 * Reutilizamos la función existente si está disponible
 */
if (!function_exists('expandAmazonShortUrl')) {
    function expandAmazonShortUrl($url) {
        error_log("Expandiendo URL corta de Amazon: " . $url);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_NOBODY => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html',
                'Accept-Language: es-ES,es;q=0.9'
            ]
        ]);
        
        curl_exec($ch);
        $expandedUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);
        
        if ($expandedUrl) {
            error_log("URL expandida: " . $expandedUrl);
            return $expandedUrl;
        }
        
        return $url;
    }
}
?>

