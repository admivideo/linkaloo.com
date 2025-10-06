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
                if (empty($imagenFinal)) $imagenFinal = $metadata['imagen'] ?? '';
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

// Función auxiliar para obtener metadatos de URL (mejorada como la versión web)
function getUrlMetadataFromUrl($url) {
    try {
        error_log("=== INICIANDO SCRAPE DE METADATOS ===");
        error_log("URL objetivo: " . $url);
        
        // Configurar cURL con user agent propio y seguir redirecciones
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Linkaloo/1.0; +https://linkaloo.com)',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
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
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        
        curl_close($ch);
        
        error_log("HTTP Code: " . $httpCode);
        error_log("Final URL: " . $finalUrl);
        error_log("Content-Type: " . $contentType);
        
        if ($html === false || $httpCode !== 200) {
            error_log("Error obteniendo contenido: HTTP " . $httpCode);
            return null;
        }
        
        // Normalizar a UTF-8
        $html = mb_convert_encoding($html, 'UTF-8', 'auto');
        
        // Cargar en DOMDocument para extracción robusta
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        
        // Extraer título
        $titulo = '';
        $titleNodes = $xpath->query('//title');
        if ($titleNodes->length > 0) {
            $titulo = trim($titleNodes->item(0)->textContent);
        }
        
        // Extraer descripción (Open Graph primero, luego meta description)
        $descripcion = '';
        $ogDescNodes = $xpath->query('//meta[@property="og:description"]/@content');
        if ($ogDescNodes->length > 0) {
            $descripcion = trim($ogDescNodes->item(0)->textContent);
        } else {
            $metaDescNodes = $xpath->query('//meta[@name="description"]/@content');
            if ($metaDescNodes->length > 0) {
                $descripcion = trim($metaDescNodes->item(0)->textContent);
            }
        }
        
        // Extraer imagen (Open Graph primero, luego Twitter, luego meta image)
        $imagen = '';
        $ogImageNodes = $xpath->query('//meta[@property="og:image"]/@content');
        if ($ogImageNodes->length > 0) {
            $imagen = trim($ogImageNodes->item(0)->textContent);
        } else {
            $twitterImageNodes = $xpath->query('//meta[@name="twitter:image"]/@content');
            if ($twitterImageNodes->length > 0) {
                $imagen = trim($twitterImageNodes->item(0)->textContent);
            } else {
                $metaImageNodes = $xpath->query('//meta[@name="image"]/@content');
                if ($metaImageNodes->length > 0) {
                    $imagen = trim($metaImageNodes->item(0)->textContent);
                }
            }
        }
        
        // Si la imagen es relativa, reconstruir con el dominio original
        if (!empty($imagen) && !filter_var($imagen, FILTER_VALIDATE_URL)) {
            $parsedUrl = parse_url($finalUrl);
            $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
            if (isset($parsedUrl['port'])) {
                $baseUrl .= ':' . $parsedUrl['port'];
            }
            
            if (strpos($imagen, '/') === 0) {
                // Imagen absoluta desde la raíz
                $imagen = $baseUrl . $imagen;
            } else {
                // Imagen relativa
                $path = isset($parsedUrl['path']) ? dirname($parsedUrl['path']) : '';
                $imagen = $baseUrl . $path . '/' . $imagen;
            }
        }
        
        // Limpiar y validar datos
        $titulo = html_entity_decode($titulo, ENT_QUOTES, 'UTF-8');
        $descripcion = html_entity_decode($descripcion, ENT_QUOTES, 'UTF-8');
        
        // Limitar longitud
        $titulo = mb_substr($titulo, 0, 200);
        $descripcion = mb_substr($descripcion, 0, 500);
        
        error_log("Metadatos extraídos:");
        error_log("  Título: " . $titulo);
        error_log("  Descripción: " . $descripcion);
        error_log("  Imagen: " . $imagen);
        error_log("  URL Final: " . $finalUrl);
        
        return [
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'imagen' => $imagen,
            'url_canonica' => $finalUrl
        ];
        
    } catch (Exception $e) {
        error_log("Error en scrapeMetadata: " . $e->getMessage());
        return null;
    }
}
?>
