<?php
// Incluir archivo de configuración
require_once 'config.php';

// Configurar headers CORS
setCorsHeaders();

// Manejar preflight requests
handlePreflightRequest();

try {
    // Obtener conexión a la base de datos
    $pdo = getDatabaseConnection();
    
    // Obtener datos del request
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'save_shared_link':
            saveSharedLink($pdo, $input);
            break;
            
        case 'get_url_metadata':
            getUrlMetadata($pdo, $input);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function saveSharedLink($pdo, $input) {
    try {
        error_log("=== GUARDANDO LINK COMPARTIDO ===");
        error_log("Input recibido: " . json_encode($input));
        
        // Validar datos requeridos
        if (!isset($input['user_id']) || empty($input['user_id'])) {
            throw new Exception('ID de usuario requerido');
        }
        
        if (!isset($input['categoria_id']) || empty($input['categoria_id'])) {
            throw new Exception('ID de categoría requerido');
        }
        
        if (!isset($input['url']) || empty($input['url'])) {
            throw new Exception('URL requerida');
        }
        
        $userId = intval($input['user_id']);
        $categoriaId = intval($input['categoria_id']);
        $url = trim($input['url']);
        $titulo = trim($input['titulo'] ?? '');
        $descripcion = trim($input['descripcion'] ?? '');
        $imagen = trim($input['imagen'] ?? '');
        $imageData = $input['image_data'] ?? '';
        
        error_log("Datos validados - User ID: " . $userId . ", Categoría ID: " . $categoriaId);
        error_log("URL: " . $url);
        error_log("Título: " . $titulo);
        error_log("Descripción: " . $descripcion);
        error_log("Imagen URL: " . $imagen);
        error_log("Tiene datos de imagen: " . (!empty($imageData) ? 'Sí' : 'No'));
        
        // Validar URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('URL no válida');
        }
        
        // Verificar que el usuario existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception('Usuario no encontrado');
        }
        
        // Verificar que la categoría existe y pertenece al usuario
        $stmt = $pdo->prepare("SELECT id FROM categorias WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$categoriaId, $userId]);
        $categoria = $stmt->fetch();
        
        if (!$categoria) {
            throw new Exception('Categoría no encontrada o no pertenece al usuario');
        }
        
        // Generar hash de la URL para identificación única
        $hashUrl = hash('sha256', $url);
        
        // Verificar si ya existe un link con la misma URL en esta categoría
        $stmt = $pdo->prepare("SELECT id FROM links WHERE usuario_id = ? AND categoria_id = ? AND hash_url = ?");
        $stmt->execute([$userId, $categoriaId, $hashUrl]);
        $existingLink = $stmt->fetch();

        if ($existingLink) {
            throw new Exception('Ya existe un link con esta URL en esta categoría');
        }
        
        // Procesar imagen si se proporciona
        $imagenFinal = $imagen;
        if (!empty($imageData)) {
            error_log("Procesando imagen compartida...");
            $imagenFinal = processSharedImage($userId, $url, $imageData, $titulo, $descripcion);
        }
        
         // Obtener metadatos de la URL si no se proporcionan título, descripción o imagen
         $urlCanonica = $url;
         if (empty($titulo) || empty($descripcion) || empty($imagenFinal)) {
             $metadata = getUrlMetadataFromUrl($url);
             if ($metadata) {
                 if (empty($titulo)) $titulo = $metadata['titulo'] ?? '';
                 if (empty($descripcion)) $descripcion = $metadata['descripcion'] ?? '';
                 if (empty($imagenFinal)) {
                     $imagenFinal = $metadata['imagen'] ?? '';
                     // Si tenemos una URL de imagen de Wallapop, descargarla y procesarla
                     if (!empty($imagenFinal) && strpos($imagenFinal, 'img.wallapop.com') !== false) {
                         error_log("Detectada imagen de Wallapop, descargando y procesando...");
                         $imagenFinal = downloadAndProcessWallapopImage($userId, $imagenFinal, $titulo);
                     }
                 }
                 $urlCanonica = $metadata['url_canonica'] ?? $url;
             }
         }
        
        // Crear el link
        $stmt = $pdo->prepare("INSERT INTO links (usuario_id, categoria_id, url, url_canonica, titulo, descripcion, imagen, hash_url, creado_en, actualizado_en) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$userId, $categoriaId, $url, $urlCanonica, $titulo, $descripcion, $imagenFinal ?: null, $hashUrl]);
        
        $linkId = $pdo->lastInsertId();
        
        // Obtener el link creado
        $stmt = $pdo->prepare("SELECT * FROM links WHERE id = ?");
        $stmt->execute([$linkId]);
        $link = $stmt->fetch();
        
        error_log("Link compartido guardado exitosamente con ID: " . $linkId);
        
        json_response([
            'success' => true,
            'message' => 'Link compartido guardado exitosamente',
            'link' => [
                'id' => (int)$link['id'],
                'usuario_id' => (int)$link['usuario_id'],
                'categoria_id' => (int)$link['categoria_id'],
                'url' => $link['url'],
                'url_canonica' => $link['url_canonica'],
                'titulo' => $link['titulo'],
                'descripcion' => $link['descripcion'],
                'imagen' => $link['imagen'],
                'creado_en' => $link['creado_en'],
                'actualizado_en' => $link['actualizado_en'],
                'hash_url' => $link['hash_url']
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("ERROR en saveSharedLink: " . $e->getMessage());
        json_response([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function processSharedImage($userId, $originalUrl, $imageData, $title, $description) {
    try {
        error_log("=== PROCESANDO IMAGEN COMPARTIDA ===");
        
        // Decodificar imagen base64
        $decodedImage = base64_decode($imageData);
        if ($decodedImage === false) {
            throw new Exception('Error decodificando imagen base64');
        }
        
        $imageSize = strlen($decodedImage);
        error_log("Tamaño de imagen decodificada: " . $imageSize . " bytes");
        
        // Validar tamaño de imagen
        $maxSize = getServerConfig('max_image_size');
        if ($imageSize > $maxSize) {
            throw new Exception('Imagen demasiado grande (máximo ' . ($maxSize / 1024 / 1024) . 'MB)');
        }
        
        // Crear directorio específico del usuario: /fichas/(id_usuario)/
        $userDir = getServerConfig('fichas_path') . $userId . '/';
        if (!file_exists($userDir)) {
            mkdir($userDir, 0755, true);
            error_log("Directorio de usuario creado: " . $userDir);
        }
        
        // Generar nombre de archivo único
        $timestamp = time();
        $urlHash = crc32($originalUrl);
        $fileName = 'shared_' . $timestamp . '_' . abs($urlHash) . '.jpg';
        $filePath = $userDir . $fileName;
        
        error_log("Archivo a guardar: " . $filePath);
        
        // Crear imagen desde datos decodificados
        $sourceImage = imagecreatefromstring($decodedImage);
        if ($sourceImage === false) {
            throw new Exception('No se pudo crear imagen desde los datos');
        }
        
        // Obtener dimensiones originales
        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);
        error_log("Dimensiones originales: " . $originalWidth . "x" . $originalHeight);
        
        // Calcular nuevas dimensiones (300px de ancho, altura proporcional)
        $newWidth = getServerConfig('image_width');
        $newHeight = intval(($originalHeight * $newWidth) / $originalWidth);
        error_log("Nuevas dimensiones: " . $newWidth . "x" . $newHeight);
        
        // Crear imagen redimensionada
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        if ($resizedImage === false) {
            imagedestroy($sourceImage);
            throw new Exception('No se pudo crear imagen redimensionada');
        }
        
        // Preservar transparencia para PNG y GIF
        imagealphablending($resizedImage, false);
        imagesavealpha($resizedImage, true);
        
        // Redimensionar imagen
        $resizeSuccess = imagecopyresampled(
            $resizedImage, $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $originalWidth, $originalHeight
        );
        
        if (!$resizeSuccess) {
            imagedestroy($sourceImage);
            imagedestroy($resizedImage);
            throw new Exception('Error redimensionando imagen');
        }
        
        // Guardar imagen redimensionada como JPG
        $imageQuality = getServerConfig('image_quality');
        $saveSuccess = imagejpeg($resizedImage, $filePath, $imageQuality);
        
        // Limpiar memoria
        imagedestroy($sourceImage);
        imagedestroy($resizedImage);
        
        if (!$saveSuccess) {
            throw new Exception('Error guardando imagen redimensionada');
        }
        
        error_log("Imagen compartida redimensionada y guardada exitosamente");
        
        // Crear URL relativa para la imagen (con barra inicial)
        $relativePath = '/fichas/' . $userId . '/' . $fileName;
        
        // Verificar que el archivo se guardó correctamente
        if (!file_exists($filePath)) {
            throw new Exception('Archivo no encontrado después de guardar');
        }
        
        $actualSize = filesize($filePath);
        error_log("Tamaño real del archivo guardado: " . $actualSize . " bytes");
        
        // Validar que es una imagen válida
        $imageInfo = getimagesize($filePath);
        if ($imageInfo === false) {
            unlink($filePath); // Eliminar archivo inválido
            throw new Exception('Archivo no es una imagen válida');
        }
        
        error_log("Imagen válida - Dimensiones finales: " . $imageInfo[0] . "x" . $imageInfo[1]);
        error_log("Tipo MIME: " . $imageInfo['mime']);
        
        return $relativePath;
        
    } catch (Exception $e) {
        error_log("ERROR procesando imagen compartida: " . $e->getMessage());
        return '';
    }
}

function getUrlMetadata($pdo, $input) {
    $url = $input['url'] ?? '';
    
    if (empty($url)) {
        throw new Exception('URL es requerida');
    }
    
    // Validar URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        throw new Exception('URL no válida');
    }
    
    // Obtener metadatos de la URL
    $metadata = getUrlMetadataFromUrl($url);
    
    if ($metadata) {
        echo json_encode([
            'success' => true,
            'url' => $url,
            'metadata' => $metadata
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'url' => $url,
            'error' => 'No se pudieron obtener los metadatos de la URL'
        ]);
    }
}

// Funciones auxiliares idénticas a la versión web
function ensureUtf8($string) {
    $encoding = mb_detect_encoding($string, 'UTF-8, ISO-8859-1, WINDOWS-1252', true);
    if ($encoding && $encoding !== 'UTF-8') {
        $string = mb_convert_encoding($string, 'UTF-8', $encoding);
    }
    return $string;
}

function canonicalizeUrl($url) {
    $parts = parse_url(trim($url));
    if (!$parts || empty($parts['host'])) {
        return $url;
    }
    $scheme = strtolower($parts['scheme'] ?? 'http');
    $host = strtolower($parts['host']);
    $path = isset($parts['path']) ? rtrim($parts['path'], '/') : '';
    $query = isset($parts['query']) ? '?' . $parts['query'] : '';
    $port = isset($parts['port']) ? ':' . $parts['port'] : '';
    return $scheme . '://' . $host . $port . $path . $query;
}

function scrapeMetadata($url) {
    error_log("=== INICIANDO SCRAPE DE METADATOS (Versión Web) ===");
    error_log("URL objetivo: " . $url);
    
    // Detectar si es Pinterest y usar función específica
    if (strpos($url, 'pinterest.com') !== false || strpos($url, 'pinterest.es') !== false) {
        error_log("Detectado Pinterest, usando función específica");
        return scrapePinterestMetadata($url);
    }
    
    // Detectar si es Wallapop y usar función específica
    if (strpos($url, 'wallapop.com') !== false) {
        error_log("Detectado Wallapop, usando función específica");
        return scrapeWallapopMetadata($url);
    }
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; linkalooBot/1.0)',
        CURLOPT_TIMEOUT => 5,
    ]);
    
    $html = curl_exec($ch);
    curl_close($ch);
    
    if (!$html) {
        error_log("Error: No se pudo obtener contenido HTML");
        return [];
    }
    
    $enc = mb_detect_encoding($html, 'UTF-8, ISO-8859-1, WINDOWS-1252', true);
    if ($enc) {
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', $enc);
    }
    
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $doc->loadHTML($html);
    $xpath = new DOMXPath($doc);
    
    $getMeta = function($name, $attr = 'property') use ($xpath) {
        $nodes = $xpath->query("//meta[@$attr='$name']/@content");
        return $nodes->length ? trim($nodes->item(0)->nodeValue) : '';
    };
    
    $meta = [];
    $titles = $doc->getElementsByTagName('title');
    if ($titles->length) {
        $meta['title'] = trim($titles->item(0)->textContent);
    }
    
    $meta['description'] = $getMeta('og:description') ?: $getMeta('description', 'name');
    $meta['image'] = $getMeta('og:image') ?: $getMeta('twitter:image');
    
    if (!empty($meta['image']) && !preg_match('#^https?://#', $meta['image'])) {
        $parts = parse_url($url);
        $base = $parts['scheme'] . '://' . $parts['host'];
        if (isset($parts['port'])) {
            $base .= ':' . $parts['port'];
        }
        $meta['image'] = rtrim($base, '/') . '/' . ltrim($meta['image'], '/');
    }
    
    foreach ($meta as &$value) {
        $value = ensureUtf8($value);
    }
    unset($value);
    
    error_log("Metadatos extraídos:");
    error_log("  Título: " . ($meta['title'] ?? ''));
    error_log("  Descripción: " . ($meta['description'] ?? ''));
    error_log("  Imagen: " . ($meta['image'] ?? ''));
    
    return $meta;
}

// Función específica para Pinterest
function scrapePinterestMetadata($url) {
    error_log("=== SCRAPE ESPECÍFICO PARA PINTEREST ===");
    error_log("URL Pinterest: " . $url);
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: es-ES,es;q=0.9,en;q=0.8',
            'Accept-Encoding: gzip, deflate',
            'Cache-Control: no-cache',
            'Pragma: no-cache'
        ]
    ]);
    
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if (!$html || $httpCode !== 200) {
        error_log("Error obteniendo Pinterest: HTTP " . $httpCode);
        return [];
    }
    
    $enc = mb_detect_encoding($html, 'UTF-8, ISO-8859-1, WINDOWS-1252', true);
    if ($enc) {
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', $enc);
    }
    
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $doc->loadHTML($html);
    $xpath = new DOMXPath($doc);
    
    $meta = [];
    
    // Extraer título - Pinterest usa diferentes selectores
    $titleSelectors = [
        '//h1[@data-test-id="pin-title"]',
        '//h1[contains(@class, "pin-title")]',
        '//h1[contains(@class, "title")]',
        '//title',
        '//meta[@property="og:title"]/@content',
        '//meta[@name="twitter:title"]/@content'
    ];
    
    foreach ($titleSelectors as $selector) {
        $nodes = $xpath->query($selector);
        if ($nodes->length > 0) {
            $title = trim($nodes->item(0)->textContent ?? $nodes->item(0)->nodeValue ?? '');
            if (!empty($title)) {
                $meta['title'] = $title;
                break;
            }
        }
    }
    
    // Extraer descripción - Pinterest tiene estructura específica
    $descSelectors = [
        '//div[@data-test-id="pin-description"]',
        '//div[contains(@class, "pin-description")]',
        '//div[contains(@class, "description")]',
        '//meta[@property="og:description"]/@content',
        '//meta[@name="description"]/@content'
    ];
    
    foreach ($descSelectors as $selector) {
        $nodes = $xpath->query($selector);
        if ($nodes->length > 0) {
            $desc = trim($nodes->item(0)->textContent ?? $nodes->item(0)->nodeValue ?? '');
            if (!empty($desc)) {
                $meta['description'] = $desc;
                break;
            }
        }
    }
    
    // Extraer imagen - Pinterest usa estructura específica
    $imageSelectors = [
        '//img[@data-test-id="pin-image"]/@src',
        '//img[contains(@class, "pin-image")]/@src',
        '//img[contains(@class, "pin-img")]/@src',
        '//meta[@property="og:image"]/@content',
        '//meta[@name="twitter:image"]/@content'
    ];
    
    foreach ($imageSelectors as $selector) {
        $nodes = $xpath->query($selector);
        if ($nodes->length > 0) {
            $img = trim($nodes->item(0)->nodeValue ?? '');
            if (!empty($img)) {
                $meta['image'] = $img;
                break;
            }
        }
    }
    
    // Si no encontramos imagen, buscar en el JSON embebido de Pinterest
    if (empty($meta['image'])) {
        if (preg_match('/"image":\s*"([^"]+)"/', $html, $matches)) {
            $meta['image'] = $matches[1];
        }
    }
    
    // Limpiar y normalizar datos
    foreach ($meta as &$value) {
        $value = ensureUtf8($value);
        $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    }
    unset($value);
    
    // Limpiar título específico de Pinterest
    if (!empty($meta['title'])) {
        // Remover "Pin de" y "en" del título
        $meta['title'] = preg_replace('/^Pin de\s+[^|]+\s+en\s+/', '', $meta['title']);
        $meta['title'] = preg_replace('/\s+\|\s+.*$/', '', $meta['title']);
        $meta['title'] = trim($meta['title']);
    }
    
    error_log("Metadatos Pinterest extraídos:");
    error_log("  Título: " . ($meta['title'] ?? ''));
    error_log("  Descripción: " . ($meta['description'] ?? ''));
    error_log("  Imagen: " . ($meta['image'] ?? ''));
    
    return $meta;
}

// Función específica para Wallapop
function scrapeWallapopMetadata($url) {
    error_log("=== SCRAPE ESPECÍFICO PARA WALLAPOP ===");
    error_log("URL Wallapop: " . $url);
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: es-ES,es;q=0.9,en;q=0.8',
            'Accept-Encoding: gzip, deflate',
            'Cache-Control: no-cache',
            'Pragma: no-cache'
        ]
    ]);
    
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if (!$html || $httpCode !== 200) {
        error_log("Error obteniendo Wallapop: HTTP " . $httpCode);
        return [];
    }
    
    $enc = mb_detect_encoding($html, 'UTF-8, ISO-8859-1, WINDOWS-1252', true);
    if ($enc) {
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', $enc);
    }
    
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $doc->loadHTML($html);
    $xpath = new DOMXPath($doc);
    
    $meta = [];
    
    // Extraer título - Wallapop usa diferentes selectores
    $titleSelectors = [
        '//h1[contains(@class, "ItemTitle")]',
        '//h1[contains(@class, "item-title")]',
        '//h1[contains(@class, "title")]',
        '//h1',
        '//meta[@property="og:title"]/@content',
        '//meta[@name="twitter:title"]/@content'
    ];
    
    foreach ($titleSelectors as $selector) {
        $nodes = $xpath->query($selector);
        if ($nodes->length > 0) {
            $title = trim($nodes->item(0)->textContent ?? $nodes->item(0)->nodeValue ?? '');
            if (!empty($title)) {
                $meta['title'] = $title;
                break;
            }
        }
    }
    
    // Extraer descripción - Wallapop tiene estructura específica para productos
    $descSelectors = [
        '//div[contains(@class, "ItemDescription")]',
        '//div[contains(@class, "item-description")]',
        '//div[contains(@class, "description")]',
        '//p[contains(@class, "ItemDescription")]',
        '//meta[@property="og:description"]/@content',
        '//meta[@name="description"]/@content'
    ];
    
    foreach ($descSelectors as $selector) {
        $nodes = $xpath->query($selector);
        if ($nodes->length > 0) {
            $desc = trim($nodes->item(0)->textContent ?? $nodes->item(0)->nodeValue ?? '');
            if (!empty($desc)) {
                $meta['description'] = $desc;
                break;
            }
        }
    }
    
    // Extraer imagen - Wallapop usa estructura específica para productos
    $imageSelectors = [
        '//img[contains(@class, "ItemImage")]/@src',
        '//img[contains(@class, "item-image")]/@src',
        '//img[contains(@class, "product-image")]/@src',
        '//img[contains(@class, "ItemGallery")]/@src',
        '//meta[@property="og:image"]/@content',
        '//meta[@name="twitter:image"]/@content'
    ];
    
    foreach ($imageSelectors as $selector) {
        $nodes = $xpath->query($selector);
        if ($nodes->length > 0) {
            $img = trim($nodes->item(0)->nodeValue ?? '');
            if (!empty($img)) {
                $meta['image'] = $img;
                break;
            }
        }
    }
    
    // Si no encontramos imagen, buscar en el JSON embebido de Wallapop
    if (empty($meta['image'])) {
        if (preg_match('/"image":\s*"([^"]+)"/', $html, $matches)) {
            $meta['image'] = $matches[1];
        }
        // Buscar en datos estructurados
        if (preg_match('/"@type":\s*"Product".*?"image":\s*"([^"]+)"/s', $html, $matches)) {
            $meta['image'] = $matches[1];
        }
    }
    
     // Extraer precio si está disponible
     $priceSelectors = [
         '//span[contains(@class, "ItemPrice")]',
         '//span[contains(@class, "item-price")]',
         '//span[contains(@class, "price")]',
         '//div[contains(@class, "ItemPrice")]',
         '//span[contains(text(), "€")]',
         '//div[contains(text(), "€")]'
     ];
     
     $price = '';
     foreach ($priceSelectors as $selector) {
         $nodes = $xpath->query($selector);
         if ($nodes->length > 0) {
             $price = trim($nodes->item(0)->textContent ?? '');
             if (!empty($price) && (strpos($price, '€') !== false || strpos($price, '$') !== false || strpos($price, '£') !== false)) {
                 break;
             }
         }
     }
    
    // Si encontramos precio, agregarlo a la descripción
    if (!empty($price) && !empty($meta['description'])) {
        $meta['description'] = $price . ' - ' . $meta['description'];
    } elseif (!empty($price)) {
        $meta['description'] = $price;
    }
    
    // Limpiar y normalizar datos
    foreach ($meta as &$value) {
        $value = ensureUtf8($value);
        $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    }
    unset($value);
    
    // Limpiar título específico de Wallapop
    if (!empty($meta['title'])) {
        // Remover "Wallapop" del título si está presente
        $meta['title'] = preg_replace('/\s*-\s*Wallapop$/', '', $meta['title']);
        $meta['title'] = trim($meta['title']);
    }
    
    error_log("Metadatos Wallapop extraídos:");
    error_log("  Título: " . ($meta['title'] ?? ''));
    error_log("  Descripción: " . ($meta['description'] ?? ''));
    error_log("  Imagen: " . ($meta['image'] ?? ''));
    
     return $meta;
}

// Función específica para descargar y procesar imágenes de Wallapop
function downloadAndProcessWallapopImage($userId, $imageUrl, $titulo) {
    try {
        error_log("=== DESCARGANDO IMAGEN DE WALLAPOP ===");
        error_log("URL de imagen: " . $imageUrl);
        error_log("User ID: " . $userId);
        
        // Configurar cURL para descargar la imagen
        $ch = curl_init($imageUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Accept: image/webp,image/apng,image/*,*/*;q=0.8',
                'Accept-Language: es-ES,es;q=0.9,en;q=0.8',
                'Accept-Encoding: gzip, deflate',
                'Cache-Control: no-cache',
                'Pragma: no-cache'
            ]
        ]);
        
        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        
        if (!$imageData || $httpCode !== 200) {
            error_log("Error descargando imagen de Wallapop: HTTP " . $httpCode);
            return $imageUrl; // Devolver URL original si falla
        }
        
        error_log("Imagen descargada exitosamente. Content-Type: " . $contentType);
        error_log("Tamaño de imagen: " . strlen($imageData) . " bytes");
        
        // Convertir a base64 para procesar
        $base64Image = base64_encode($imageData);
        
        // Usar la función existente de procesamiento de imágenes
        $processedImage = processSharedImage($userId, $imageUrl, $base64Image, $titulo, '');
        
        if ($processedImage) {
            error_log("Imagen de Wallapop procesada exitosamente: " . $processedImage);
            return $processedImage;
        } else {
            error_log("Error procesando imagen de Wallapop, usando URL original");
            return $imageUrl;
        }
        
    } catch (Exception $e) {
        error_log("Error en downloadAndProcessWallapopImage: " . $e->getMessage());
        return $imageUrl; // Devolver URL original si falla
    }
}

// Función auxiliar para obtener metadatos de URL (usando la misma lógica que la versión web)
function getUrlMetadataFromUrl($url) {
    try {
        $meta = scrapeMetadata($url);
        
        if (empty($meta)) {
            return null;
        }
        
        // Mapear los campos a la estructura esperada por la API
        return [
            'titulo' => $meta['title'] ?? '',
            'descripcion' => $meta['description'] ?? '',
            'imagen' => $meta['image'] ?? '',
            'url_canonica' => canonicalizeUrl($url)
        ];
        
    } catch (Exception $e) {
        error_log("Error en getUrlMetadataFromUrl: " . $e->getMessage());
        return null;
    }
}
?>
