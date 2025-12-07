<?php
// Configurar logging de errores
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('display_errors', 0);

// Incluir archivo de configuración
require_once 'config.php';

// Configurar headers CORS
setCorsHeaders();

// Logger personalizado reutilizando amazon_debug.log
if (!defined('LINKALOO_DEBUG_LOG')) {
    define('LINKALOO_DEBUG_LOG', __DIR__ . '/amazon_debug.log');
}
if (!function_exists('debugLog')) {
    function debugLog($msg) {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents(LINKALOO_DEBUG_LOG, "[$timestamp] [user_api] $msg\n", FILE_APPEND);
    }
}

// Manejar preflight requests
handlePreflightRequest();

// LOG INICIAL para confirmar que este archivo se está ejecutando
error_log("=== user_api.php INICIADO - Versión con get_top_favolinks - " . date('Y-m-d H:i:s') . " ===");

// Endpoint de prueba para confirmar que la versión desplegada es la correcta y que el logger funciona
if (isset($_GET['debug_test'])) {
    debugLog('Test manual desde user_api (debug_test)');
    header('Content-Type: text/plain');
    echo "user_api debug_test OK\n";
    echo "Versión: Archivo actualizado con get_top_favolinks\n";
    echo "Función getTopFavolinks existe: " . (function_exists('getTopFavolinks') ? 'SÍ' : 'NO') . "\n";
    echo "Fecha: " . date('Y-m-d H:i:s') . "\n";
    exit;
}

// COMENTADO: Estas líneas impedían que el API procesara las peticiones
// echo 'user_api DEPLOY 2025-11-10'; 
// exit;

try {
    // Obtener datos del request primero (antes de conectar a BD)
    $rawInput = file_get_contents('php://input');
    error_log("Raw input recibido: " . $rawInput);
    error_log("Raw input length: " . strlen($rawInput));
    error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
    
    // Si no hay input, puede ser un GET request sin body
    if (empty($rawInput) && $_SERVER['REQUEST_METHOD'] === 'GET') {
        error_log("⚠️ Request GET sin body - no se puede procesar acción");
        throw new Exception('Este endpoint requiere un POST request con JSON body');
    }
    
    $input = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Error decodificando JSON: " . json_last_error_msg());
        throw new Exception('Error decodificando JSON: ' . json_last_error_msg());
    }
    
    $action = isset($input['action']) ? trim($input['action']) : '';
    error_log("Acción recibida (raw): '" . $action . "'");
    error_log("Acción recibida (length): " . strlen($action));
    error_log("Acción recibida (hex): " . bin2hex($action));
    error_log("Input completo: " . json_encode($input));
    
    // LOGGING EXTRA PARA DEBUGGING
    error_log("=== COMPARACIÓN DE ACCIÓN ===");
    error_log("Acción recibida: [" . $action . "]");
    error_log("Acción esperada: [get_top_favolinks]");
    error_log("¿Son iguales? " . ($action === 'get_top_favolinks' ? 'SÍ' : 'NO'));
    error_log("¿Son iguales (case-insensitive)? " . (strtolower($action) === 'get_top_favolinks' ? 'SÍ' : 'NO'));
    error_log("¿Contiene 'get_top_favolinks'? " . (strpos($action, 'get_top_favolinks') !== false ? 'SÍ' : 'NO'));
    error_log("Tipo de variable: " . gettype($action));
    error_log("=== FIN COMPARACIÓN ===");
    
    // VERIFICACIÓN MUY TEMPRANA para get_top_favolinks (ANTES de conectar a BD)
    // Esto evita cualquier problema con la conexión a BD o el switch
    // Usar múltiples métodos de comparación para evitar problemas de encoding/caché
    $actionNormalized = strtolower(trim($action));
    $isGetTopFavolinks = ($action === 'get_top_favolinks') || 
                         ($actionNormalized === 'get_top_favolinks') ||
                         (strcasecmp($action, 'get_top_favolinks') === 0) ||
                         (preg_match('/^get_top_favolinks$/i', $action));
    
    if ($isGetTopFavolinks) {
        error_log("✅✅✅ ACCIÓN get_top_favolinks DETECTADA - Procesando ANTES de conectar a BD");
        error_log("✅ Método de detección: " . 
            ($action === 'get_top_favolinks' ? 'Comparación exacta' : 
             ($actionNormalized === 'get_top_favolinks' ? 'strtolower+trim' :
              (strcasecmp($action, 'get_top_favolinks') === 0 ? 'strcasecmp' : 'preg_match'))));
        
        // Conectar a BD solo cuando sea necesario
        $pdo = getDatabaseConnection();
        
        error_log("✅ Verificando si función existe: " . (function_exists('getTopFavolinks') ? 'SÍ' : 'NO'));
        
        if (!function_exists('getTopFavolinks')) {
            error_log("❌ ERROR: La función getTopFavolinks NO existe!");
            throw new Exception('Función getTopFavolinks no encontrada en el servidor');
        }
        
        try {
            error_log("✅ Llamando a getTopFavolinks...");
            getTopFavolinks($pdo, $input);
            error_log("✅ getTopFavolinks completado exitosamente");
        } catch (Exception $e) {
            error_log("❌ Error en getTopFavolinks: " . $e->getMessage());
            error_log("❌ Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error procesando Top Favolinks: ' . $e->getMessage()
            ]);
        }
        exit; // Salir después de procesar para evitar el switch
    } else {
        error_log("⚠️ Acción NO es get_top_favolinks, continuando con switch...");
    }
    
    // Obtener conexión a la base de datos (después de validar el input y procesar get_top_favolinks)
    $pdo = getDatabaseConnection();
    
    // Lista de acciones disponibles para debugging
    $availableActions = [
        'check_user', 'create_user', 'get_categories', 'update_category',
        'create_category', 'delete_category', 'debug_table_structure',
        'test_connection', 'get_links', 'check_duplicate_link',
        'get_links_paginated', 'create_link', 'update_link', 'delete_link',
        'get_url_metadata', 'debug_links', 'upload_image', 'get_top_favolinks'
    ];
    error_log("Acciones disponibles: " . implode(', ', $availableActions));
    error_log("¿get_top_favolinks está en la lista? " . (in_array('get_top_favolinks', $availableActions) ? 'SÍ' : 'NO'));
    error_log("¿La acción recibida está en la lista? " . (in_array($action, $availableActions) ? 'SÍ' : 'NO'));
    
    switch ($action) {
        case 'check_user':
            checkUser($pdo, $input);
            break;
            
        case 'create_user':
            createUser($pdo, $input);
            break;
            
        case 'get_categories':
            getCategories($pdo, $input);
            break;
            
        case 'update_category':
            updateCategory($pdo, $input);
            break;
            
        case 'create_category':
            createCategory($pdo, $input);
            break;
            
        case 'delete_category':
            deleteCategory($pdo, $input);
            break;
            
        case 'debug_table_structure':
            debugTableStructure($pdo);
            break;
            
        case 'test_connection':
            testConnection($pdo);
            break;
            
            
        case 'get_links':
            getLinks($pdo, $input);
            break;
            
        case 'check_duplicate_link':
            checkDuplicateLink($pdo, $input);
            break;
            
        case 'get_links_paginated':
            getLinksPaginated($pdo, $input);
            break;
            
        case 'create_link':
            createLink($pdo, $input);
            break;
            
        case 'update_link':
            updateLink($pdo, $input);
            break;
            
        case 'delete_link':
            deleteLink($pdo, $input);
            break;
            
        case 'get_url_metadata':
            getUrlMetadata($pdo, $input);
            break;
            
        case 'debug_links':
            debugLinks($pdo, $input);
            break;
            
        case 'upload_image':
            uploadImage($pdo, $input);
            break;
            
        case 'get_top_favolinks':
            error_log("✅ Case get_top_favolinks encontrado, llamando función...");
            getTopFavolinks($pdo, $input);
            break;
            
        default:
            error_log("❌ Acción no reconocida en switch: '" . $action . "'");
            error_log("❌ Tipo de acción: " . gettype($action));
            error_log("❌ Longitud: " . strlen($action));
            throw new Exception('Acción no válida: ' . $action);
    }
    
} catch (Exception $e) {
    error_log("❌ EXCEPCIÓN CAPTURADA EN user_api.php: " . $e->getMessage());
    error_log("❌ STACK TRACE: " . $e->getTraceAsString());
    error_log("❌ Archivo: " . $e->getFile() . " Línea: " . $e->getLine());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE);
}

function checkUser($pdo, $input) {
    $email = $input['email'] ?? '';
    
    if (empty($email)) {
        throw new Exception('Email es requerido');
    }
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Usuario existe
        echo json_encode([
            'success' => true,
            'exists' => true,
            'user' => [
                'id' => (int)$user['id'],
                'nombre' => $user['nombre'],
                'email' => $user['email'],
                'google_id' => $user['google_id'],
                'creado_en' => $user['creado_en'],
                'actualizado_en' => $user['actualizado_en']
            ]
        ]);
    } else {
        // Usuario no existe
        echo json_encode([
            'success' => true,
            'exists' => false,
            'message' => 'Usuario no encontrado'
        ]);
    }
}

function createUser($pdo, $input) {
    $nombre = $input['nombre'] ?? '';
    $email = $input['email'] ?? '';
    $googleId = $input['google_id'] ?? '';
    
    if (empty($nombre) || empty($email)) {
        throw new Exception('Nombre y email son requeridos');
    }
    
    // Verificar si el usuario ya existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        // Usuario ya existe, actualizar datos
        $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, google_id = ?, actualizado_en = NOW() WHERE email = ?");
        $stmt->execute([$nombre, $googleId, $email]);
        
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'created' => false,
            'updated' => true,
            'user' => [
                'id' => (int)$user['id'],
                'nombre' => $user['nombre'],
                'email' => $user['email'],
                'google_id' => $user['google_id'],
                'creado_en' => $user['creado_en'],
                'actualizado_en' => $user['actualizado_en']
            ]
        ]);
    } else {
        // Crear nuevo usuario
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, google_id, creado_en, actualizado_en) VALUES (?, ?, ?, NOW(), NOW())");
        $stmt->execute([$nombre, $email, $googleId]);
        
        $userId = $pdo->lastInsertId();
        
        // Obtener el usuario creado
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'created' => true,
            'updated' => false,
            'user' => [
                'id' => (int)$user['id'],
                'nombre' => $user['nombre'],
                'email' => $user['email'],
                'google_id' => $user['google_id'],
                'creado_en' => $user['creado_en'],
                'actualizado_en' => $user['actualizado_en']
            ]
        ]);
    }
}

function getCategories($pdo, $input) {
    $userId = $input['user_id'] ?? 0;
    
    if ($userId <= 0) {
        throw new Exception('ID de usuario válido es requerido');
    }
    
    // Verificar que el usuario existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('Usuario no encontrado');
    }
    
    // Obtener categorías del usuario
    $stmt = $pdo->prepare("
        SELECT c.*
        FROM categorias c 
        WHERE c.usuario_id = ? 
    ");
    $stmt->execute([$userId]);
    $categories = $stmt->fetchAll();
    
    // Ordenar en PHP por fecha de modificación
    usort($categories, function($a, $b) {
        $dateA = $a['modificado_en'] ?: $a['creado_en'];
        $dateB = $b['modificado_en'] ?: $b['creado_en'];
        
        // Ordenar por fecha descendente (más reciente primero)
        $result = strtotime($dateB) - strtotime($dateA);
        
        // Si las fechas son iguales, ordenar por nombre
        if ($result === 0) {
            return strcmp($a['nombre'], $b['nombre']);
        }
        
        return $result;
    });
    
    echo json_encode([
        'success' => true,
        'user_id' => (int)$userId,
        'total_categories' => count($categories),
        'categories' => array_map(function($cat) {
            return [
                'id' => (int)$cat['id'],
                'usuario_id' => (int)$cat['usuario_id'],
                'nombre' => $cat['nombre'],
                'creado_en' => $cat['creado_en'],
                'modificado_en' => $cat['modificado_en'],
                'share_token' => $cat['share_token'] ?? null,
                'nota' => $cat['nota'] ?? null
            ];
        }, $categories)
    ]);
}


function testConnection($pdo) {
    // Probar conexión con consultas simples
    $stmt = $pdo->query("SELECT COUNT(*) as total_usuarios FROM usuarios");
    $usuarios = $stmt->fetch();
    
    $stmt = $pdo->query("SELECT COUNT(*) as total_categorias FROM categorias");
    $categorias = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'message' => 'Conexión exitosa',
        'stats' => [
            'total_usuarios' => (int)$usuarios['total_usuarios'],
            'total_categorias' => (int)$categorias['total_categorias']
        ]
    ]);
}

function getLinks($pdo, $input) {
    $userId = $input['user_id'] ?? 0;
    $categoriaId = $input['categoria_id'] ?? 0;
    
    if ($userId <= 0) {
        throw new Exception('ID de usuario válido es requerido');
    }
    
    if ($categoriaId <= 0) {
        throw new Exception('ID de categoría válido es requerido');
    }
    
    // Verificar que el usuario existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('Usuario no encontrado');
    }
    
    // Verificar que la categoría existe y pertenece al usuario
    $stmt = $pdo->prepare("SELECT id, usuario_id FROM categorias WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$categoriaId, $userId]);
    $categoria = $stmt->fetch();
    
    if (!$categoria) {
        // Debug: verificar si la categoría existe pero pertenece a otro usuario
        $stmt = $pdo->prepare("SELECT id, usuario_id FROM categorias WHERE id = ?");
        $stmt->execute([$categoriaId]);
        $categoriaExistente = $stmt->fetch();
        
        if ($categoriaExistente) {
            throw new Exception('Categoría existe pero pertenece al usuario ' . $categoriaExistente['usuario_id'] . ', no al usuario ' . $userId);
        } else {
            throw new Exception('Categoría no encontrada');
        }
    }
    
    // Debug: verificar cuántos links tiene el usuario en total
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM links WHERE usuario_id = ?");
    $stmt->execute([$userId]);
    $totalUsuario = $stmt->fetch();
    
    // Debug: verificar cuántos links tiene la categoría en total
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM links WHERE categoria_id = ?");
    $stmt->execute([$categoriaId]);
    $totalCategoria = $stmt->fetch();
    
    // Obtener links de la categoría
    $stmt = $pdo->prepare("SELECT * FROM links WHERE usuario_id = ? AND categoria_id = ? ORDER BY creado_en DESC");
    $stmt->execute([$userId, $categoriaId]);
    $links = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'user_id' => (int)$userId,
        'categoria_id' => (int)$categoriaId,
        'total_links' => count($links),
        'debug_info' => [
            'total_links_usuario' => (int)$totalUsuario['total'],
            'total_links_categoria' => (int)$totalCategoria['total'],
            'categoria_usuario_id' => (int)$categoria['usuario_id']
        ],
        'links' => array_map(function($link) {
            return [
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
                'nota_link' => $link['nota_link'],
                'hash_url' => $link['hash_url']
            ];
        }, $links)
    ]);
}

function getLinksPaginated($pdo, $input) {
    try {
        // Log de entrada para debug
        error_log("getLinksPaginated called with input: " . json_encode($input));
        
        $userId = $input['user_id'] ?? 0;
        $categoriaId = $input['categoria_id'] ?? 0;
        $page = max(1, (int)($input['page'] ?? 1));
        $limit = max(1, min(100, (int)($input['limit'] ?? 20))); // Límite entre 1 y 100
        
        error_log("Parsed parameters - userId: $userId, categoriaId: $categoriaId, page: $page, limit: $limit");
        
        if ($userId <= 0) {
            throw new Exception('ID de usuario válido es requerido');
        }
        
        if ($categoriaId <= 0) {
            throw new Exception('ID de categoría válido es requerido');
        }
    
    // Verificar que el usuario existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('Usuario no encontrado');
    }
    
    // Verificar que la categoría existe y pertenece al usuario
    $stmt = $pdo->prepare("SELECT id, usuario_id FROM categorias WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$categoriaId, $userId]);
    $categoria = $stmt->fetch();
    
    if (!$categoria) {
        throw new Exception('Categoría no encontrada o no pertenece al usuario');
    }
    
    // Calcular offset
    $offset = ($page - 1) * $limit;
    
    // Obtener total de links para esta categoría
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM links WHERE usuario_id = ? AND categoria_id = ?");
    $stmt->execute([$userId, $categoriaId]);
    $totalResult = $stmt->fetch();
    $totalLinks = (int)$totalResult['total'];
    
        // Obtener links paginados - usar string concatenation para LIMIT y OFFSET
        $query = "SELECT * FROM links WHERE usuario_id = ? AND categoria_id = ? ORDER BY creado_en DESC LIMIT $limit OFFSET $offset";
        error_log("Executing query: $query with params: userId=$userId, categoriaId=$categoriaId");
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$userId, $categoriaId]);
        $links = $stmt->fetchAll();
        
        error_log("Query executed successfully, found " . count($links) . " links");
    
        echo json_encode([
            'success' => true,
            'user_id' => (int)$userId,
            'categoria_id' => (int)$categoriaId,
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset,
            'total_links' => count($links),
            'total_links_categoria' => $totalLinks,
            'has_more' => ($offset + count($links)) < $totalLinks,
            'links' => array_map(function($link) {
                return [
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
                    'nota_link' => $link['nota_link'],
                    'hash_url' => $link['hash_url']
                ];
            }, $links)
        ]);
        
        error_log("getLinksPaginated completed successfully");
        
    } catch (Exception $e) {
        error_log("Error in getLinksPaginated: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error interno del servidor: ' . $e->getMessage()
        ]);
    }
}

function createLink($pdo, $input) {
    try {
        error_log("=== INICIANDO createLink ===");
        error_log("Input recibido: " . json_encode($input));
        
        $userId = $input['user_id'] ?? 0;
        $categoriaId = $input['categoria_id'] ?? 0;
        $url = $input['url'] ?? '';
        $titulo = $input['titulo'] ?? '';
        $descripcion = $input['descripcion'] ?? '';
        $notaLink = $input['nota_link'] ?? '';
        $imagen = $input['imagen'] ?? ''; // Obtener imagen del input
        
        error_log("Parámetros procesados - userId: $userId, categoriaId: $categoriaId, url: $url, titulo: $titulo");
    
    if ($userId <= 0) {
        throw new Exception('ID de usuario válido es requerido');
    }
    
    if ($categoriaId <= 0) {
        throw new Exception('ID de categoría válido es requerido');
    }
    
    if (empty($url)) {
        throw new Exception('URL es requerida');
    }
    
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
    
    // Normalizar URL para hashing consistente
    $normalizedUrl = normalizeUrlForHash($url);
    $originalUrlTrimmed = rtrim(trim($url), '/');

    if (empty($normalizedUrl)) {
        throw new Exception('No se pudo normalizar la URL');
    }

    // Generar hashes (normalizado y original) para compatibilidad hacia atrás
    $hashNormalized = hash('sha256', $normalizedUrl);
    $hashOriginal = hash('sha256', $originalUrlTrimmed ?: $url);

    // Verificar si ya existe un link con la misma URL en esta categoría
    $hashesToCheck = array_unique([$hashNormalized, $hashOriginal]);
    $hashPlaceholders = implode(',', array_fill(0, count($hashesToCheck), '?'));
    $stmt = $pdo->prepare("SELECT id FROM links WHERE usuario_id = ? AND categoria_id = ? AND hash_url IN ($hashPlaceholders)");
    $stmt->execute(array_merge([$userId, $categoriaId], $hashesToCheck));
    $existingLink = $stmt->fetch();

    if ($existingLink) {
        throw new Exception('Ya existe un link con esta URL en esta categoría');
    }
    
    // Obtener metadatos de la URL solo si no se proporcionan título, descripción o imagen
    $urlCanonica = $normalizedUrl;
    if (empty($titulo) || empty($descripcion) || empty($imagen)) {
        $metadata = getUrlMetadataFromUrl($url);
        if ($metadata) {
            if (empty($titulo)) $titulo = $metadata['titulo'] ?? '';
            if (empty($descripcion)) $descripcion = $metadata['descripcion'] ?? '';
            if (empty($imagen)) $imagen = $metadata['imagen'] ?? ''; // Solo usar metadatos si no hay imagen
            if (!empty($metadata['url_canonica'])) {
                $urlCanonica = normalizeUrlForHash($metadata['url_canonica']);
                $hashNormalized = hash('sha256', $urlCanonica);
            }
        }
    }
    
    // Crear el link
    $stmt = $pdo->prepare("INSERT INTO links (usuario_id, categoria_id, url, url_canonica, titulo, descripcion, imagen, nota_link, hash_url, creado_en, actualizado_en) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute([$userId, $categoriaId, $url, $urlCanonica, $titulo, $descripcion, $imagen ?? null, $notaLink, $hashNormalized]);
    
    $linkId = $pdo->lastInsertId();
    
    // Obtener el link creado
    $stmt = $pdo->prepare("SELECT * FROM links WHERE id = ?");
    $stmt->execute([$linkId]);
    $link = $stmt->fetch();
    
    error_log("Link creado exitosamente con ID: $linkId");
    error_log("Datos del link: " . json_encode($link));
    
    json_response([
        'success' => true,
        'action' => 'created',
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
            'nota_link' => $link['nota_link'],
            'hash_url' => $link['hash_url']
        ]
    ]);
    
    error_log("=== FINALIZANDO createLink - Respuesta enviada ===");
    
    } catch (Exception $e) {
        error_log("ERROR en createLink: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        json_response([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

// Función auxiliar para obtener metadatos de URL
function getUrlMetadataFromUrl($url) {
    try {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Mozilla/5.0 (compatible; Linkaloo/1.0)',
                'follow_location' => true,
                'max_redirects' => 3
            ]
        ]);
        
        $html = @file_get_contents($url, false, $context);
        
        if ($html === false) {
            return null;
        }
        
        // Obtener URL final después de redirecciones
        $headers = get_headers($url, 1);
        $finalUrl = is_array($headers['Location']) ? end($headers['Location']) : ($headers['Location'] ?? $url);
        
        // Extraer metadatos usando expresiones regulares simples
        $titulo = '';
        $descripcion = '';
        $imagen = '';
        
        // Título
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            $titulo = trim(strip_tags($matches[1]));
        }
        
        // Meta descripción
        if (preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/is', $html, $matches)) {
            $descripcion = trim($matches[1]);
        }
        
        // Meta imagen
        if (preg_match('/<meta[^>]*property=["\']og:image["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/is', $html, $matches)) {
            $imagen = trim($matches[1]);
        }
        
        return [
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'imagen' => $imagen,
            'url_canonica' => $finalUrl
        ];
        
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Normaliza una URL para hashing y detección de duplicados.
 * - Si es YouTube, se reduce a la forma base https://www.youtube.com/watch?v=VIDEO_ID
 * - Para el resto de URLs, se eliminan parámetros de tracking comunes y se ordenan.
 */
function normalizeUrlForHash($url) {
    if (empty($url)) {
        return '';
    }

    $url = trim($url);

    if (!preg_match('/^https?:\/\//i', $url)) {
        $url = 'https://' . $url;
    }

    $parts = parse_url($url);
    if ($parts === false || empty($parts['host'])) {
        return $url;
    }

    $host = strtolower($parts['host']);

    // Normalización simplificada para YouTube
    if (strpos($host, 'youtube.com') !== false || strpos($host, 'youtu.be') !== false) {
        $normalizedYouTube = normalizeYouTubeUrlSimple($url);
        if (!empty($normalizedYouTube)) {
            return $normalizedYouTube;
        }
    }

    $scheme = strtolower($parts['scheme'] ?? 'https');
    $path = $parts['path'] ?? '';
    $query = $parts['query'] ?? '';

    parse_str($query, $queryParams);
    if (!empty($queryParams)) {
        $queryParams = removeTrackingParameters($queryParams);
        if (!empty($queryParams)) {
            ksort($queryParams);
            $query = http_build_query($queryParams);
        } else {
            $query = '';
        }
    }

    $path = preg_replace('#/+#', '/', $path);
    if ($path === '') {
        $path = '/';
    }

    $normalizedUrl = $scheme . '://' . strtolower($host) . $path;
    if (!empty($query)) {
        $normalizedUrl .= '?' . $query;
    } elseif ($path !== '/' && substr($normalizedUrl, -1) === '/') {
        $normalizedUrl = rtrim($normalizedUrl, '/');
    }

    return $normalizedUrl;
}

/**
 * Normalización mínima para URLs de YouTube.
 * Convierte cualquier variante al formato https://www.youtube.com/watch?v=VIDEO_ID
 * Devuelve string vacío si no se puede extraer un identificador.
 */
function normalizeYouTubeUrlSimple($url) {
    $url = trim($url);
    if ($url === '') {
        return '';
    }
    if (!preg_match('/^https?:\/\//i', $url)) {
        $url = 'https://' . $url;
    }

    $parts = parse_url($url);
    if ($parts === false || empty($parts['host'])) {
        return '';
    }

    $host = strtolower($parts['host']);
    $path = $parts['path'] ?? '';
    $query = $parts['query'] ?? '';

    // youtu.be/VIDEO_ID
    if (strpos($host, 'youtu.be') !== false) {
        $videoId = trim($path, '/');
        return $videoId ? 'https://www.youtube.com/watch?v=' . $videoId : '';
    }

    // unificar host
    if ($host === 'm.youtube.com' || $host === 'music.youtube.com') {
        $host = 'www.youtube.com';
    }

    if (strpos($host, 'youtube.com') === false) {
        return '';
    }

    // shorts /shorts/VIDEO_ID
    if (strpos($path, '/shorts/') === 0) {
        $segments = explode('/', trim($path, '/'));
        $videoId = $segments[1] ?? '';
        return $videoId ? 'https://www.youtube.com/watch?v=' . $videoId : '';
    }

    // embed /embed/VIDEO_ID
    if (strpos($path, '/embed/') === 0) {
        $segments = explode('/', trim($path, '/'));
        $videoId = $segments[1] ?? '';
        return $videoId ? 'https://www.youtube.com/watch?v=' . $videoId : '';
    }

    // watch?v=VIDEO_ID
    if (strpos($path, '/watch') === 0) {
        parse_str($query, $params);
        $videoId = $params['v'] ?? '';
        return $videoId ? 'https://www.youtube.com/watch?v=' . $videoId : '';
    }

    // playlist?list=LIST_ID
    if (strpos($path, '/playlist') === 0) {
        parse_str($query, $params);
        $listId = $params['list'] ?? '';
        return $listId ? 'https://www.youtube.com/playlist?list=' . $listId : '';
    }

    return '';
}

/**
 * Elimina parámetros de tracking comunes de un arreglo de query params
 */
function removeTrackingParameters($params) {
    if (empty($params)) {
        return $params;
    }

    $trackingKeys = [
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
        'utm_name', 'utm_id', 'utm_reader', 'utm_place',
        'gclid', 'fbclid', 'igshid', 'mc_cid', 'mc_eid', 'si',
        'feature', 'spm', 'mibextid', 'hss_channel', 'hss_campaign',
        'hss_src'
    ];

    foreach ($params as $key => $value) {
        $lower = strtolower($key);
        if (strpos($lower, 'utm_') === 0 || in_array($lower, $trackingKeys, true)) {
            unset($params[$key]);
        }
    }

    return $params;
}

/**
 * Genera variantes de URL para comparar en la búsqueda de duplicados
 */
function generateUrlVariants($urls) {
    $variants = [];

    foreach ($urls as $url) {
        if (empty($url)) {
            continue;
        }

        $trimmed = trim($url);
        if ($trimmed === '') {
            continue;
        }

        $variants[] = $trimmed;
        $variants[] = rtrim($trimmed, '/');
        $variants[] = strtolower($trimmed);
        $variants[] = strtolower(rtrim($trimmed, '/'));

        $withoutProtocol = preg_replace('/^https?:\/\//i', '', $trimmed);
        if (!empty($withoutProtocol)) {
            $variants[] = $withoutProtocol;
            $variants[] = strtolower($withoutProtocol);
        }

        if (stripos($trimmed, 'https://') === 0) {
            $variants[] = 'http://' . substr($trimmed, strlen('https://'));
        } elseif (stripos($trimmed, 'http://') === 0) {
            $variants[] = 'https://' . substr($trimmed, strlen('http://'));
        }
    }

    $variants = array_values(array_unique(array_filter($variants)));

    return $variants;
}

/**
 * Determina si el link existente realmente coincide con la URL actual
 * Evita falsos positivos cuando un registro antiguo fue indexado con lógica diferente
 */
function linksAreSame($existingLink, $normalizedUrl, $originalUrl, $hashesToCheck) {
    $existingHashes = [];
    if (!empty($existingLink['hash_url'])) {
        $existingHashes[] = $existingLink['hash_url'];
    }

    foreach (['url', 'url_canonica'] as $key) {
        if (!empty($existingLink[$key])) {
            $normalizedExisting = normalizeUrlForHash($existingLink[$key]);
            if (!empty($normalizedExisting)) {
                $existingHashes[] = hash('sha256', $normalizedExisting);
            }
        }
    }

    $existingHashes = array_unique(array_filter($existingHashes));

    foreach ($existingHashes as $existingHash) {
        if (in_array($existingHash, $hashesToCheck, true)) {
            $msg = "🧮 Hash coincide: existingHash=$existingHash";
            error_log($msg);
            debugLog($msg);
            return true;
        }
    }

    $existingVariants = [];
    foreach (['url', 'url_canonica'] as $key) {
        if (!empty($existingLink[$key])) {
            $existingVariants = array_merge($existingVariants, generateUrlVariants([$existingLink[$key]]));
        }
    }
    $existingVariants = array_unique(array_filter($existingVariants));

    $currentVariants = generateUrlVariants([$normalizedUrl, $originalUrl]);
    foreach ($currentVariants as $variant) {
        if (in_array($variant, $existingVariants, true)) {
            $msg = "🔄 Variante coincide: $variant";
            error_log($msg);
            debugLog($msg);
            return true;
        }
    }

    return false;
}

function updateLink($pdo, $input) {
    $linkId = $input['link_id'] ?? 0;
    $userId = $input['user_id'] ?? 0;
    $titulo = $input['titulo'] ?? '';
    $descripcion = $input['descripcion'] ?? '';
    $notaLink = $input['nota_link'] ?? '';
    $imagen = $input['imagen'] ?? '';
    
    if ($linkId <= 0) {
        throw new Exception('ID de link válido es requerido');
    }
    
    if ($userId <= 0) {
        throw new Exception('ID de usuario válido es requerido');
    }
    
    // Verificar que el link existe y pertenece al usuario
    $stmt = $pdo->prepare("SELECT * FROM links WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$linkId, $userId]);
    $link = $stmt->fetch();
    
    if (!$link) {
        throw new Exception('Link no encontrado o no pertenece al usuario');
    }
    
    // Actualizar solo los campos proporcionados
    $updateFields = [];
    $updateValues = [];
    
    if (!empty($titulo)) {
        $updateFields[] = "titulo = ?";
        $updateValues[] = $titulo;
    }
    
    if (!empty($descripcion)) {
        $updateFields[] = "descripcion = ?";
        $updateValues[] = $descripcion;
    }
    
    if (!empty($imagen)) {
        $updateFields[] = "imagen = ?";
        $updateValues[] = $imagen;
    }
    
    if (isset($input['nota_link'])) { // Permitir notas vacías
        $updateFields[] = "nota_link = ?";
        $updateValues[] = $notaLink;
    }
    
    if (empty($updateFields)) {
        throw new Exception('No hay campos para actualizar');
    }
    
    $updateFields[] = "actualizado_en = NOW()";
    $updateValues[] = $linkId;
    $updateValues[] = $userId;
    
    $sql = "UPDATE links SET " . implode(', ', $updateFields) . " WHERE id = ? AND usuario_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($updateValues);
    
    // Obtener el link actualizado
    $stmt = $pdo->prepare("SELECT * FROM links WHERE id = ?");
    $stmt->execute([$linkId]);
    $updatedLink = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'action' => 'updated',
        'link' => [
            'id' => (int)$updatedLink['id'],
            'usuario_id' => (int)$updatedLink['usuario_id'],
            'categoria_id' => (int)$updatedLink['categoria_id'],
            'url' => $updatedLink['url'],
            'url_canonica' => $updatedLink['url_canonica'],
            'titulo' => $updatedLink['titulo'],
            'descripcion' => $updatedLink['descripcion'],
            'imagen' => $updatedLink['imagen'],
            'creado_en' => $updatedLink['creado_en'],
            'actualizado_en' => $updatedLink['actualizado_en'],
            'nota_link' => $updatedLink['nota_link'],
            'hash_url' => $updatedLink['hash_url']
        ]
    ]);
}

function deleteLink($pdo, $input) {
    $linkId = $input['link_id'] ?? 0;
    $userId = $input['user_id'] ?? 0;
    
    if ($linkId <= 0) {
        throw new Exception('ID de link válido es requerido');
    }
    
    if ($userId <= 0) {
        throw new Exception('ID de usuario válido es requerido');
    }
    
    // Verificar que el link existe y pertenece al usuario
    $stmt = $pdo->prepare("SELECT * FROM links WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$linkId, $userId]);
    $link = $stmt->fetch();
    
    if (!$link) {
        throw new Exception('Link no encontrado o no pertenece al usuario');
    }
    
    // Eliminar el link
    $stmt = $pdo->prepare("DELETE FROM links WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$linkId, $userId]);
    
    echo json_encode([
        'success' => true,
        'action' => 'deleted',
        'link_id' => (int)$linkId,
        'message' => 'Link eliminado exitosamente'
    ]);
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

function debugLinks($pdo, $input) {
    try {
        // Verificar si la tabla links existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'links'");
        $tableExists = $stmt->fetch();
        
        $debug = [
            'success' => true,
            'debug_info' => []
        ];
        
        if ($tableExists) {
            $debug['debug_info']['table_exists'] = true;
            
            // Contar total de links
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM links");
            $totalLinks = $stmt->fetch();
            $debug['debug_info']['total_links'] = (int)$totalLinks['total'];
            
            // Obtener estructura de la tabla
            $stmt = $pdo->query("DESCRIBE links");
            $columns = $stmt->fetchAll();
            $debug['debug_info']['columns'] = array_map(function($col) {
                return $col['Field'] . ' (' . $col['Type'] . ')';
            }, $columns);
            
            // Obtener algunos links de ejemplo con imágenes
            $stmt = $pdo->query("SELECT id, usuario_id, categoria_id, titulo, url, imagen FROM links ORDER BY id DESC LIMIT 10");
            $sampleLinks = $stmt->fetchAll();
            $debug['debug_info']['sample_links'] = $sampleLinks;
            
            // Contar links con imágenes
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM links WHERE imagen IS NOT NULL AND imagen != ''");
            $linksWithImages = $stmt->fetch();
            $debug['debug_info']['links_with_images'] = (int)$linksWithImages['total'];
            
            // Obtener links con imágenes específicamente
            $stmt = $pdo->query("SELECT id, usuario_id, titulo, imagen FROM links WHERE imagen IS NOT NULL AND imagen != '' ORDER BY id DESC LIMIT 5");
            $linksWithImagesData = $stmt->fetchAll();
            $debug['debug_info']['links_with_images_data'] = $linksWithImagesData;
            
            // Obtener estadísticas por usuario
            $stmt = $pdo->query("SELECT usuario_id, COUNT(*) as count FROM links GROUP BY usuario_id");
            $userStats = $stmt->fetchAll();
            $debug['debug_info']['links_by_user'] = $userStats;
            
            // Obtener estadísticas por categoría
            $stmt = $pdo->query("SELECT categoria_id, COUNT(*) as count FROM links GROUP BY categoria_id");
            $categoryStats = $stmt->fetchAll();
            $debug['debug_info']['links_by_category'] = $categoryStats;
            
        } else {
            $debug['debug_info']['table_exists'] = false;
            $debug['debug_info']['error'] = 'La tabla links no existe';
        }
        
        echo json_encode($debug);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function uploadImage($pdo, $input) {
    try {
        error_log("=== INICIANDO SUBIDA DE IMAGEN ===");
        error_log("Input recibido: " . json_encode($input));
        
        // Validar datos requeridos
        if (!isset($input['image_data']) || empty($input['image_data'])) {
            throw new Exception('Datos de imagen requeridos');
        }
        
        if (!isset($input['user_id']) || empty($input['user_id'])) {
            throw new Exception('ID de usuario requerido');
        }
        
        $imageData = $input['image_data'];
        $userId = intval($input['user_id']);
        $originalUrl = $input['original_url'] ?? '';
        $title = $input['title'] ?? '';
        $description = $input['description'] ?? '';
        $imageType = $input['image_type'] ?? 'link_thumbnail';
        
        error_log("Datos validados - User ID: " . $userId);
        error_log("Original URL: " . $originalUrl);
        error_log("Título: " . $title);
        error_log("Tipo de imagen: " . $imageType);
        
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
        $fileName = 'archivo_' . $timestamp . '_' . abs($urlHash) . '.jpg';
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
        
        error_log("Imagen redimensionada y guardada exitosamente");
        
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
        
        // Respuesta exitosa
        $response = [
            'success' => true,
            'message' => 'Imagen subida y redimensionada exitosamente',
            'local_image_url' => $relativePath,
            'original_url' => $originalUrl,
            'file_name' => $fileName,
            'file_size' => $actualSize,
            'original_dimensions' => $originalWidth . 'x' . $originalHeight,
            'resized_dimensions' => $imageInfo[0] . 'x' . $imageInfo[1],
            'mime_type' => $imageInfo['mime'],
            'upload_timestamp' => $timestamp,
            'user_id' => $userId
        ];
        
        error_log("=== SUBIDA DE IMAGEN COMPLETADA ===");
        error_log("Respuesta: " . json_encode($response));
        
        json_response($response);
        
    } catch (Exception $e) {
        error_log("ERROR en uploadImage: " . $e->getMessage());
        json_response([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function updateCategory($pdo, $input) {
    try {
        error_log("=== UPDATE CATEGORY INICIADO ===");
        error_log("Input recibido: " . json_encode($input));
        
        $userId = $input['user_id'] ?? 0;
        $categoryId = $input['category_id'] ?? 0;
        $nombre = $input['nombre'] ?? null;
        $nota = $input['nota'] ?? null;
        $compartirPublico = $input['compartir_publico'] ?? false;
        
        error_log("Datos procesados - UserID: $userId, CategoryID: $categoryId, Nombre: " . ($nombre ?? 'null') . ", Nota: " . ($nota ?? 'null') . ", Compartir público: " . ($compartirPublico ? 'true' : 'false'));
    
    if ($userId <= 0) {
        error_log("ERROR: ID de usuario no válido: $userId");
        throw new Exception('ID de usuario válido es requerido');
    }
    
    if ($categoryId <= 0) {
        error_log("ERROR: ID de categoría no válido: $categoryId");
        throw new Exception('ID de categoría válido es requerido');
    }
    
    // Verificar que el usuario existe
    error_log("Verificando usuario ID: $userId");
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        error_log("ERROR: Usuario no encontrado con ID: $userId");
        throw new Exception('Usuario no encontrado');
    }
    error_log("Usuario verificado correctamente");
    
    // Verificar que la categoría existe y pertenece al usuario
    error_log("Verificando categoría ID: $categoryId para usuario: $userId");
    $stmt = $pdo->prepare("SELECT id FROM categorias WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$categoryId, $userId]);
    $category = $stmt->fetch();
    
    if (!$category) {
        error_log("ERROR: Categoría no encontrada o no pertenece al usuario - CategoryID: $categoryId, UserID: $userId");
        throw new Exception('Categoría no encontrada o no pertenece al usuario');
    }
    error_log("Categoría verificada correctamente");
    
    // Preparar la query de actualización
    error_log("Preparando query de actualización...");
    $updateFields = [];
    $params = [];
    $shareToken = null;
    
    if ($nombre !== null) {
        $updateFields[] = "nombre = ?";
        $params[] = $nombre;
        error_log("Campo 'nombre' añadido: '$nombre'");
    }
    
    if ($nota !== null) {
        $updateFields[] = "nota = ?";
        $params[] = $nota;
        error_log("Campo 'nota' añadido: '$nota'");
    }
    
    // Manejar compartir público
    if ($compartirPublico) {
        // Verificar si ya tiene share_token
        $stmt = $pdo->prepare("SELECT share_token FROM categorias WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$categoryId, $userId]);
        $currentCategory = $stmt->fetch();
        
        if (!$currentCategory || !$currentCategory['share_token']) {
            // Generar nuevo share_token
            $shareToken = bin2hex(random_bytes(16)); // 32 caracteres hexadecimales
            $updateFields[] = "share_token = ?";
            $params[] = $shareToken;
            error_log("Nuevo share_token generado: '$shareToken'");
        } else {
            $shareToken = $currentCategory['share_token'];
            error_log("Share_token existente: '$shareToken'");
        }
    } else {
        // Si se desactiva compartir público, eliminar share_token
        $updateFields[] = "share_token = NULL";
        error_log("Share_token eliminado (compartir público desactivado)");
    }
    
    if (empty($updateFields)) {
        error_log("ERROR: No hay campos para actualizar");
        throw new Exception('No hay campos para actualizar');
    }
    
    // Añadir fecha de modificación
    $updateFields[] = "modificado_en = NOW()";
    error_log("Campo 'modificado_en' añadido");
    
    // Añadir parámetros para la condición WHERE
    $params[] = $categoryId;
    $params[] = $userId;
    
    $sql = "UPDATE categorias SET " . implode(', ', $updateFields) . " WHERE id = ? AND usuario_id = ?";
    error_log("SQL Query: $sql");
    error_log("Parámetros: " . json_encode($params));
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    error_log("Resultado de execute: " . ($result ? 'true' : 'false'));
    
    if ($result) {
        $response = [
            'success' => true,
            'message' => 'Categoría actualizada exitosamente',
            'category_id' => (int)$categoryId,
            'updated_fields' => array_keys(array_filter([
                'nombre' => $nombre !== null,
                'nota' => $nota !== null,
                'compartir_publico' => true
            ]))
        ];
        
        // Añadir información de compartir si está activado
        if ($compartirPublico && $shareToken) {
            $publicUrl = "https://linkaloo.com/tablero_publico.php?token=" . $shareToken;
            $response['share_token'] = $shareToken;
            $response['public_url'] = $publicUrl;
            error_log("URL pública generada: '$publicUrl'");
        }
        
        error_log("✅ UPDATE CATEGORY EXITOSO: " . json_encode($response));
        echo json_encode($response);
    } else {
        error_log("ERROR: Error al actualizar la categoría - result es false");
        throw new Exception('Error al actualizar la categoría');
    }
    } catch (Exception $e) {
        error_log("❌ ERROR EN UPDATE CATEGORY: " . $e->getMessage());
        error_log("❌ STACK TRACE: " . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
       }
   }

   function createCategory($pdo, $input) {
       try {
           error_log("=== CREATE CATEGORY INICIADO ===");
           error_log("Input recibido: " . json_encode($input));

           $userId = $input['user_id'] ?? 0;
           $nombre = $input['nombre'] ?? '';

           error_log("Datos procesados - UserID: $userId, Nombre: " . ($nombre ?: 'null'));

           if ($userId <= 0) {
               error_log("ERROR: ID de usuario no válido: $userId");
               throw new Exception('ID de usuario válido es requerido');
           }

           if (empty($nombre) || trim($nombre) === '') {
               error_log("ERROR: Nombre de categoría vacío");
               throw new Exception('Nombre de categoría es requerido');
           }

           $nombre = trim($nombre);

           // Verificar que el usuario existe
           error_log("Verificando usuario ID: $userId");
           $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
           $stmt->execute([$userId]);
           $user = $stmt->fetch();

           if (!$user) {
               error_log("ERROR: Usuario no encontrado con ID: $userId");
               throw new Exception('Usuario no encontrado');
           }
           error_log("Usuario verificado correctamente");

           // Verificar si ya existe una categoría con el mismo nombre para este usuario
           error_log("Verificando si ya existe categoría con nombre: '$nombre'");
           $stmt = $pdo->prepare("SELECT id FROM categorias WHERE usuario_id = ? AND nombre = ?");
           $stmt->execute([$userId, $nombre]);
           $existingCategory = $stmt->fetch();

           if ($existingCategory) {
               error_log("ERROR: Ya existe una categoría con el nombre '$nombre' para el usuario $userId");
               throw new Exception('Ya existe un tablero con ese nombre');
           }

           // Crear la nueva categoría
           error_log("Creando nueva categoría...");
           $stmt = $pdo->prepare("INSERT INTO categorias (usuario_id, nombre, creado_en, modificado_en) VALUES (?, ?, NOW(), NOW())");
           $result = $stmt->execute([$userId, $nombre]);

           if ($result) {
               $categoryId = $pdo->lastInsertId();
               error_log("✅ Categoría creada exitosamente con ID: $categoryId");

               echo json_encode([
                   'success' => true,
                   'message' => 'Tablero creado exitosamente',
                   'category_id' => (int)$categoryId,
                   'category_name' => $nombre
               ]);
           } else {
               error_log("ERROR: Error al insertar la categoría en la base de datos");
               throw new Exception('Error al crear el tablero en la base de datos');
           }

       } catch (Exception $e) {
           error_log("❌ ERROR EN CREATE CATEGORY: " . $e->getMessage());
           error_log("❌ STACK TRACE: " . $e->getTraceAsString());
           
           http_response_code(500);
           echo json_encode([
               'success' => false,
               'error' => $e->getMessage()
           ]);
       }
   }

   function deleteCategory($pdo, $input) {
       try {
           error_log("=== DELETE CATEGORY INICIADO ===");
           error_log("Input recibido: " . json_encode($input));

           $userId = $input['user_id'] ?? 0;
           $categoryId = $input['category_id'] ?? 0;

           error_log("Datos procesados - UserID: $userId, CategoryID: $categoryId");

           if ($userId <= 0) {
               error_log("ERROR: ID de usuario no válido: $userId");
               throw new Exception('ID de usuario válido es requerido');
           }

           if ($categoryId <= 0) {
               error_log("ERROR: ID de categoría no válido: $categoryId");
               throw new Exception('ID de categoría válido es requerido');
           }

           // Verificar que el usuario existe
           error_log("Verificando usuario ID: $userId");
           $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
           $stmt->execute([$userId]);
           $user = $stmt->fetch();

           if (!$user) {
               error_log("ERROR: Usuario no encontrado con ID: $userId");
               throw new Exception('Usuario no encontrado');
           }
           error_log("Usuario verificado correctamente");

           // Verificar que la categoría existe y pertenece al usuario
           error_log("Verificando categoría ID: $categoryId para usuario: $userId");
           $stmt = $pdo->prepare("SELECT id, nombre FROM categorias WHERE id = ? AND usuario_id = ?");
           $stmt->execute([$categoryId, $userId]);
           $category = $stmt->fetch();

           if (!$category) {
               error_log("ERROR: Categoría no encontrada o no pertenece al usuario - CategoryID: $categoryId, UserID: $userId");
               throw new Exception('Categoría no encontrada o no pertenece al usuario');
           }
           error_log("Categoría verificada correctamente: " . $category['nombre']);

           // Obtener o crear tablero "Sin Categoría"
           error_log("Buscando tablero 'Sin Categoría' para usuario: $userId");
           $stmt = $pdo->prepare("SELECT id FROM categorias WHERE usuario_id = ? AND nombre = 'Sin Categoría'");
           $stmt->execute([$userId]);
           $sinCategoria = $stmt->fetch();

           if (!$sinCategoria) {
               error_log("Tablero 'Sin Categoría' no existe, creándolo...");
               $stmt = $pdo->prepare("INSERT INTO categorias (usuario_id, nombre, creado_en, modificado_en) VALUES (?, 'Sin Categoría', NOW(), NOW())");
               $stmt->execute([$userId]);
               $sinCategoriaId = $pdo->lastInsertId();
               error_log("Tablero 'Sin Categoría' creado con ID: $sinCategoriaId");
           } else {
               $sinCategoriaId = $sinCategoria['id'];
               error_log("Tablero 'Sin Categoría' encontrado con ID: $sinCategoriaId");
           }

           // Mover todos los links del tablero a "Sin Categoría"
           error_log("Moviendo links de categoría $categoryId a 'Sin Categoría' ($sinCategoriaId)");
           $stmt = $pdo->prepare("UPDATE links SET categoria_id = ? WHERE categoria_id = ? AND usuario_id = ?");
           $result = $stmt->execute([$sinCategoriaId, $categoryId, $userId]);
           
           if ($result) {
               $linksMoved = $stmt->rowCount();
               error_log("✅ $linksMoved links movidos a 'Sin Categoría'");
           } else {
               error_log("⚠️ No se pudieron mover los links, pero continuando con la eliminación");
           }

           // Eliminar la categoría
           error_log("Eliminando categoría ID: $categoryId");
           $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ? AND usuario_id = ?");
           $result = $stmt->execute([$categoryId, $userId]);

           if ($result) {
               error_log("✅ Categoría eliminada exitosamente");

               echo json_encode([
                   'success' => true,
                   'message' => 'Tablero eliminado exitosamente',
                   'category_id' => (int)$categoryId,
                   'category_name' => $category['nombre'],
                   'links_moved_to_sin_categoria' => $linksMoved ?? 0
               ]);
           } else {
               error_log("ERROR: Error al eliminar la categoría de la base de datos");
               throw new Exception('Error al eliminar el tablero de la base de datos');
           }

       } catch (Exception $e) {
           error_log("❌ ERROR EN DELETE CATEGORY: " . $e->getMessage());
           error_log("❌ STACK TRACE: " . $e->getTraceAsString());
           
           http_response_code(500);
           echo json_encode([
               'success' => false,
               'error' => $e->getMessage()
           ]);
       }
   }

   function debugTableStructure($pdo) {
    try {
        error_log("=== DEBUG TABLE STRUCTURE ===");
        
        // Verificar estructura de tabla categorias
        $stmt = $pdo->query("DESCRIBE categorias");
        $columns = $stmt->fetchAll();
        
        error_log("Columnas de la tabla 'categorias':");
        foreach ($columns as $column) {
            error_log("  - " . $column['Field'] . " (" . $column['Type'] . ")");
        }
        
        // Verificar si existe la columna 'nota'
        $notaExists = false;
        foreach ($columns as $column) {
            if ($column['Field'] === 'nota') {
                $notaExists = true;
                error_log("✅ Columna 'nota' encontrada: " . $column['Type']);
                break;
            }
        }
        
        if (!$notaExists) {
            error_log("❌ Columna 'nota' NO encontrada en la tabla 'categorias'");
        }
        
        echo json_encode([
            'success' => true,
            'table_structure' => $columns,
            'nota_column_exists' => $notaExists
        ]);
        
    } catch (Exception $e) {
        error_log("ERROR en debugTableStructure: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Verifica si un link ya existe para un usuario
 * Detecta duplicados por hash de URL
 */
function checkDuplicateLink($pdo, $input) {
    try {
        error_log("=== CHECK DUPLICATE LINK ===");
        debugLog("=== CHECK DUPLICATE LINK: " . json_encode($input) . " ===");
        
        $userId = $input['user_id'] ?? null;
        $url = $input['url'] ?? null;
        
        if (!$userId || !$url) {
            throw new Exception('user_id y url son requeridos');
        }
        
        error_log("👤 Usuario ID: $userId");
        error_log("🔗 URL a verificar: $url");
        debugLog("Usuario $userId - URL a verificar: $url");
        
        // Normalizar la URL y obtener hashes
        $normalizedUrl = normalizeUrlForHash($url);
        if (empty($normalizedUrl)) {
            throw new Exception('No se pudo normalizar la URL');
        }

        $originalUrlTrimmed = rtrim(trim($url), '/');

        $hashNormalized = hash('sha256', $normalizedUrl);
        $hashOriginal = hash('sha256', $originalUrlTrimmed ?: $url);

        $hashesToCheck = array_unique([$hashNormalized, $hashOriginal]);
        $hashPlaceholders = implode(',', array_fill(0, count($hashesToCheck), '?'));

        error_log("🧹 URL normalizada: $normalizedUrl");
        error_log("🔐 Hash normalizado: $hashNormalized");
        error_log("🔐 Hash original: $hashOriginal");
        debugLog("URL normalizada: $normalizedUrl | hash_norm=$hashNormalized | hash_orig=$hashOriginal");
        
        // Buscar por hash exacto primero
        error_log("🔍 Preparando query de búsqueda por hash...");
        $stmt = $pdo->prepare("
            SELECT 
                l.id,
                l.usuario_id,
                l.categoria_id,
                l.url,
                l.url_canonica,
                l.titulo,
                l.descripcion,
                l.imagen,
                l.creado_en,
                l.actualizado_en,
                c.nombre as categoria_nombre
            FROM links l
            LEFT JOIN categorias c ON l.categoria_id = c.id
            WHERE l.usuario_id = ? AND l.hash_url IN ($hashPlaceholders)
            LIMIT 1
        ");
        
        error_log("🔍 Ejecutando query con userId=$userId y hashes=" . implode(',', $hashesToCheck));
        $stmt->execute(array_merge([$userId], $hashesToCheck));
        error_log("🔍 Query ejecutada, obteniendo resultado...");
        $linkExistente = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("🔍 Resultado obtenido: " . ($linkExistente ? "Link encontrado ID=" . $linkExistente['id'] : "No encontrado"));
        
        if ($linkExistente && !linksAreSame($linkExistente, $normalizedUrl, $originalUrlTrimmed ?: $url, $hashesToCheck)) {
            error_log("ℹ️ Resultado descartado: hash/URL no coinciden realmente con la URL actual");
            $linkExistente = null;
        }
        $hashCheckResult = !empty($linkExistente) ? 'match' : 'miss';
        
        if ($linkExistente) {
            error_log("⚠️ DUPLICADO ENCONTRADO por hash - Link ID: " . $linkExistente['id']);
            error_log("📂 Categoría: " . $linkExistente['categoria_nombre']);
            error_log("📅 Guardado el: " . $linkExistente['creado_en']);
            debugLog("Duplicado encontrado (hash) -> ID=" . $linkExistente['id'] . " | URL guardada=" . $linkExistente['url']);
            
            echo json_encode([
                'success' => true,
                'duplicate_found' => true,
                'existing_link' => [
                    'id' => (int)$linkExistente['id'],
                    'url' => $linkExistente['url'],
                    'url_canonica' => $linkExistente['url_canonica'],
                    'titulo' => $linkExistente['titulo'],
                    'descripcion' => $linkExistente['descripcion'],
                    'imagen' => $linkExistente['imagen'],
                    'categoria_id' => (int)$linkExistente['categoria_id'],
                    'categoria_nombre' => $linkExistente['categoria_nombre'],
                    'creado_en' => $linkExistente['creado_en'],
                    'actualizado_en' => $linkExistente['actualizado_en']
                ]
            ]);
            return;
        }
        
        // Si no se encuentra por hash, buscar por URL similar (sin parámetros)
        error_log("🔍 Buscando coincidencias exactas en URL/URL canónica...");
        $urlVariants = generateUrlVariants([$normalizedUrl, $originalUrlTrimmed ?: $url]);

        if (!empty($urlVariants)) {
            $urlPlaceholders = implode(',', array_fill(0, count($urlVariants), '?'));
            
            try {
                $stmt = $pdo->prepare("
                    SELECT 
                        l.id,
                        l.usuario_id,
                        l.categoria_id,
                        l.url,
                        l.url_canonica,
                        l.titulo,
                        l.descripcion,
                        l.imagen,
                        l.creado_en,
                        l.actualizado_en,
                        c.nombre as categoria_nombre
                    FROM links l
                    LEFT JOIN categorias c ON l.categoria_id = c.id
                    WHERE l.usuario_id = ? 
                    AND (
                        l.url IN ($urlPlaceholders)
                        OR (l.url_canonica IS NOT NULL AND l.url_canonica IN ($urlPlaceholders))
                    )
                    LIMIT 1
                ");
            
                error_log("🔍 Ejecutando query de coincidencias exactas...");
                $stmt->execute(array_merge([$userId], $urlVariants, $urlVariants));
                error_log("🔍 Query de coincidencias ejecutada");
                $linkExistente = $stmt->fetch(PDO::FETCH_ASSOC);
                error_log("🔍 Resultado coincidencias: " . ($linkExistente ? "Link encontrado ID=" . $linkExistente['id'] : "No encontrado"));
            } catch (PDOException $e) {
                error_log("❌ ERROR en query de coincidencias: " . $e->getMessage());
                $linkExistente = null;
            }
        }
        
        if ($linkExistente && !linksAreSame($linkExistente, $normalizedUrl, $originalUrlTrimmed ?: $url, $hashesToCheck)) {
            error_log("ℹ️ Resultado descartado tras coincidencias exactas: hash/URL no coinciden realmente");
            $linkExistente = null;
        }
        $exactMatchResult = !empty($linkExistente) ? 'match' : 'miss';
        
        if ($linkExistente) {
            error_log("⚠️ DUPLICADO ENCONTRADO por URL similar - Link ID: " . $linkExistente['id']);
            error_log("📂 Categoría: " . $linkExistente['categoria_nombre']);
            error_log("📅 Guardado el: " . $linkExistente['creado_en']);
            debugLog("Duplicado encontrado (URL) -> ID=" . $linkExistente['id'] . " | URL guardada=" . $linkExistente['url']);
            
            echo json_encode([
                'success' => true,
                'duplicate_found' => true,
                'existing_link' => [
                    'id' => (int)$linkExistente['id'],
                    'url' => $linkExistente['url'],
                    'url_canonica' => $linkExistente['url_canonica'],
                    'titulo' => $linkExistente['titulo'],
                    'descripcion' => $linkExistente['descripcion'],
                    'imagen' => $linkExistente['imagen'],
                    'categoria_id' => (int)$linkExistente['categoria_id'],
                    'categoria_nombre' => $linkExistente['categoria_nombre'],
                    'creado_en' => $linkExistente['creado_en'],
                    'actualizado_en' => $linkExistente['actualizado_en']
                ]
            ]);
            return;
        }
        
        // No se encontró duplicado
        error_log("✅ No se encontró duplicado");
        $summary = "Summary -> HashCheck: $hashCheckResult, ExactMatch: $exactMatchResult, Hashes: " . json_encode($hashesToCheck);
        error_log($summary);
        debugLog($summary);
        echo json_encode([
            'success' => true,
            'duplicate_found' => false
        ]);
        
    } catch (Exception $e) {
        error_log("❌ ERROR en checkDuplicateLink: " . $e->getMessage());
        error_log("❌ STACK TRACE: " . $e->getTraceAsString());
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Obtiene los links más compartidos de la tabla TopFavolinks
 * Filtra por categoría si se proporciona
 */
function getTopFavolinks($pdo, $input) {
    try {
        error_log("=== GET TOP FAVOLINKS INICIADO ===");
        error_log("Input recibido: " . json_encode($input));
        
        $categoria = $input['categoria'] ?? null;
        
        if ($categoria !== null && trim($categoria) !== '') {
            error_log("Filtro por categoría: " . $categoria);
            // Filtrar por categoría específica
            $stmt = $pdo->prepare("
                SELECT categoria, url, titulo, descripcion, imagen, favicon 
                FROM TopFavolinks 
                WHERE categoria = ?
                ORDER BY categoria, titulo
            ");
            $stmt->execute([$categoria]);
        } else {
            error_log("Obteniendo todos los Top Favolinks (sin filtro)");
            // Obtener todos los Top Favolinks
            $stmt = $pdo->prepare("
                SELECT categoria, url, titulo, descripcion, imagen, favicon 
                FROM TopFavolinks 
                ORDER BY categoria, titulo
            ");
            $stmt->execute();
        }
        
        $topFavolinks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Total Top Favolinks encontrados: " . count($topFavolinks));
        
        echo json_encode([
            'success' => true,
            'total_favolinks' => count($topFavolinks),
            'top_favolinks' => array_map(function($link) {
                return [
                    'categoria' => $link['categoria'] ?? null,
                    'url' => $link['url'] ?? '',
                    'titulo' => $link['titulo'] ?? null,
                    'descripcion' => $link['descripcion'] ?? null,
                    'imagen' => $link['imagen'] ?? null,
                    'favicon' => $link['favicon'] ?? null
                ];
            }, $topFavolinks)
        ]);
        
        error_log("=== GET TOP FAVOLINKS COMPLETADO ===");
        
    } catch (Exception $e) {
        error_log("❌ ERROR EN GET TOP FAVOLINKS: " . $e->getMessage());
        error_log("❌ STACK TRACE: " . $e->getTraceAsString());
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

?>