<?php
/**
 * Sistema de logging centralizado para funciones de scraping
 * Fecha: 17 de Octubre de 2025
 */

/**
 * Crea una función de logging para una plataforma específica
 * 
 * @param string $platform Nombre de la plataforma (para identificar en logs)
 * @return callable Función de logging
 */
function createScrapingLogger($platform = 'GENERAL') {
    $logFile = __DIR__ . '/amazon_debug.log';
    
    return function($msg) use ($logFile, $platform) {
        $timestamp = date('Y-m-d H:i:s');
        $formattedMsg = "[$timestamp] [$platform] $msg";
        file_put_contents($logFile, $formattedMsg . "\n", FILE_APPEND);
        error_log("[$platform] $msg");
    };
}

/**
 * Configuración estándar de cURL para scraping
 * 
 * @param string $url URL a scrapear
 * @param array $extraOptions Opciones adicionales de cURL
 * @return resource Handle de cURL configurado
 */
function createScrapingCurl($url, $extraOptions = []) {
    $ch = curl_init($url);
    
    $defaultOptions = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_ENCODING => '', // Descomprimir automáticamente GZIP/Brotli
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language: es-ES,es;q=0.9,en;q=0.8',
            'Accept-Encoding: gzip, deflate, br',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1',
            'Cache-Control: no-cache',
            'Pragma: no-cache'
        ]
    ];
    
    // Merge con opciones extras
    $options = array_replace($defaultOptions, $extraOptions);
    curl_setopt_array($ch, $options);
    
    return $ch;
}

/**
 * Ejecuta request cURL y retorna resultado con logging
 * 
 * @param resource $ch Handle de cURL
 * @param callable $log Función de logging
 * @return array ['html' => string, 'httpCode' => int, 'error' => string|null]
 */
function executeCurlWithLogging($ch, $log) {
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    
    $log("HTTP Code: " . $httpCode);
    $log("HTML length: " . strlen($html ?? ''));
    
    if ($error) {
        $log("❌ Error cURL: " . $error);
    }
    
    if ($finalUrl) {
        $log("URL final: " . $finalUrl);
    }
    
    return [
        'html' => $html,
        'httpCode' => $httpCode,
        'error' => $error,
        'finalUrl' => $finalUrl
    ];
}

/**
 * Busca y extrae JSON de script __NEXT_DATA__ (usado por Next.js)
 * 
 * @param string $html HTML de la página
 * @param callable $log Función de logging
 * @return array|null Datos parseados o null si no se encuentra
 */
function extractNextDataJson($html, $log) {
    if (preg_match('/<script[^>]*id="__NEXT_DATA__"[^>]*>(.*?)<\/script>/s', $html, $matches)) {
        $log("✅ Encontrado script __NEXT_DATA__");
        $jsonData = $matches[1];
        $log("Tamaño JSON: " . strlen($jsonData) . " bytes");
        
        $data = json_decode($jsonData, true);
        if ($data) {
            $log("✅ JSON parseado exitosamente");
            return $data;
        } else {
            $log("❌ Error parseando JSON");
        }
    } else {
        $log("⚠️ No se encontró script __NEXT_DATA__");
    }
    
    return null;
}

/**
 * Extrae metadatos usando múltiples selectores XPath
 * 
 * @param DOMXPath $xpath Objeto XPath
 * @param array $selectors Lista de selectores a probar
 * @param callable $log Función de logging
 * @param string $fieldName Nombre del campo (para logging)
 * @return string|null Valor encontrado o null
 */
function extractWithSelectors($xpath, $selectors, $log, $fieldName) {
    $log("Buscando $fieldName...");
    
    foreach ($selectors as $selector) {
        $nodes = $xpath->query($selector);
        if ($nodes->length > 0) {
            $value = trim($nodes->item(0)->textContent ?? $nodes->item(0)->nodeValue ?? '');
            if (!empty($value) && strlen($value) > 3) {
                $log("✅ $fieldName encontrado con: " . $selector);
                $log("Valor: " . substr($value, 0, 100));
                return $value;
            }
        }
    }
    
    $log("⚠️ No se encontró $fieldName");
    return null;
}

/**
 * Limpia título removiendo nombre de la plataforma
 * 
 * @param string $title Título a limpiar
 * @param string $platform Nombre de la plataforma a remover
 * @return string Título limpio
 */
function cleanTitle($title, $platform) {
    $title = preg_replace('/\s*-\s*' . preg_quote($platform) . '.*$/i', '', $title);
    $title = preg_replace('/\s*\|\s*' . preg_quote($platform) . '.*$/i', '', $title);
    return trim($title);
}

/**
 * Limita descripción a longitud máxima
 * 
 * @param string $description Descripción a limitar
 * @param int $maxLength Longitud máxima (default: 300)
 * @return string Descripción limitada
 */
function limitDescription($description, $maxLength = 300) {
    if (strlen($description) > $maxLength) {
        return substr($description, 0, $maxLength - 3) . '...';
    }
    return $description;
}

/**
 * Normaliza URL de imagen (asegurar HTTPS)
 * 
 * @param string $imageUrl URL de imagen
 * @return string URL normalizada
 */
function normalizeImageUrl($imageUrl) {
    if (empty($imageUrl)) {
        return '';
    }
    
    // Asegurar protocolo HTTPS
    if (strpos($imageUrl, '//') === 0) {
        return 'https:' . $imageUrl;
    }
    
    return $imageUrl;
}

/**
 * Crea respuesta de fallback para plataformas bloqueadas
 * 
 * @param string $platform Nombre de la plataforma
 * @param string $logoUrl URL del logo de la plataforma
 * @param string $reason Razón del bloqueo
 * @return array Metadatos de fallback
 */
function createBlockedPlatformResponse($platform, $logoUrl, $reason = 'protection') {
    return [
        'title' => ucfirst($platform),
        'description' => 'Esta plataforma está protegida. Haz clic en el enlace para ver todos los detalles.',
        'image' => $logoUrl,
        'blocked' => true,
        'reason' => $reason
    ];
}
?>



