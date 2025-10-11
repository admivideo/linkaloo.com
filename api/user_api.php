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
            
        case 'test_connection':
            testConnection($pdo);
            break;
            
            
        case 'get_links':
            getLinks($pdo, $input);
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
    $userId = $input['user_id'] ?? 0;
    $categoriaId = $input['categoria_id'] ?? 0;
    $url = $input['url'] ?? '';
    $titulo = $input['titulo'] ?? '';
    $descripcion = $input['descripcion'] ?? '';
    $notaLink = $input['nota_link'] ?? '';
    $imagen = $input['imagen'] ?? ''; // Obtener imagen del input
    
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
    
    // Generar hash de la URL para identificación única
    $hashUrl = hash('sha256', $url);
    
    // Verificar si ya existe un link con la misma URL en esta categoría
    $stmt = $pdo->prepare("SELECT id FROM links WHERE usuario_id = ? AND categoria_id = ? AND hash_url = ?");
    $stmt->execute([$userId, $categoriaId, $hashUrl]);
    $existingLink = $stmt->fetch();

    if ($existingLink) {
        throw new Exception('Ya existe un link con esta URL en esta categoría');
    }
    
    // Obtener metadatos de la URL solo si no se proporcionan título, descripción o imagen
    $urlCanonica = $url;
    if (empty($titulo) || empty($descripcion) || empty($imagen)) {
        $metadata = getUrlMetadataFromUrl($url);
        if ($metadata) {
            if (empty($titulo)) $titulo = $metadata['titulo'] ?? '';
            if (empty($descripcion)) $descripcion = $metadata['descripcion'] ?? '';
            if (empty($imagen)) $imagen = $metadata['imagen'] ?? ''; // Solo usar metadatos si no hay imagen
            $urlCanonica = $metadata['url_canonica'] ?? $url;
        }
    }
    
    // Crear el link
    $stmt = $pdo->prepare("INSERT INTO links (usuario_id, categoria_id, url, url_canonica, titulo, descripcion, imagen, nota_link, hash_url, creado_en, actualizado_en) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute([$userId, $categoriaId, $url, $urlCanonica, $titulo, $descripcion, $imagen ?? null, $notaLink, $hashUrl]);
    
    $linkId = $pdo->lastInsertId();
    
    // Obtener el link creado
    $stmt = $pdo->prepare("SELECT * FROM links WHERE id = ?");
    $stmt->execute([$linkId]);
    $link = $stmt->fetch();
    
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
    $userId = $input['user_id'] ?? 0;
    $categoryId = $input['category_id'] ?? 0;
    $nombre = $input['nombre'] ?? null;
    $nota = $input['nota'] ?? null;
    
    if ($userId <= 0) {
        throw new Exception('ID de usuario válido es requerido');
    }
    
    if ($categoryId <= 0) {
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
    $stmt = $pdo->prepare("SELECT id FROM categorias WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$categoryId, $userId]);
    $category = $stmt->fetch();
    
    if (!$category) {
        throw new Exception('Categoría no encontrada o no pertenece al usuario');
    }
    
    // Preparar la query de actualización
    $updateFields = [];
    $params = [];
    
    if ($nombre !== null) {
        $updateFields[] = "nombre = ?";
        $params[] = $nombre;
    }
    
    if ($nota !== null) {
        $updateFields[] = "nota = ?";
        $params[] = $nota;
    }
    
    if (empty($updateFields)) {
        throw new Exception('No hay campos para actualizar');
    }
    
    // Añadir fecha de modificación
    $updateFields[] = "modificado_en = NOW()";
    
    // Añadir parámetros para la condición WHERE
    $params[] = $categoryId;
    $params[] = $userId;
    
    $sql = "UPDATE categorias SET " . implode(', ', $updateFields) . " WHERE id = ? AND usuario_id = ?";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Categoría actualizada exitosamente',
            'category_id' => (int)$categoryId,
            'updated_fields' => array_keys(array_filter([
                'nombre' => $nombre !== null,
                'nota' => $nota !== null
            ]))
        ]);
    } else {
        throw new Exception('Error al actualizar la categoría');
    }
}

?>