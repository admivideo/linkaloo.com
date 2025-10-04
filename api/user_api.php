<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Configuración de la base de datos
$host = '82.223.84.165';
$port = '3306';
$database = 'smartlinks';
$username = '^A%Odbc%!IOn0s!';
$password = '$Fw7Hen^S&*36#DbSit@85$';

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Conectar a la base de datos
    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
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
            
        case 'test_connection':
            testConnection($pdo);
            break;
            
        case 'get_links':
            getLinks($pdo, $input);
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
    $stmt = $pdo->prepare("SELECT * FROM categorias WHERE usuario_id = ? ORDER BY nombre ASC");
    $stmt->execute([$userId]);
    $categories = $stmt->fetchAll();
    
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
                'share_token' => $cat['share_token']
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
    $stmt = $pdo->prepare("SELECT id FROM categorias WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$categoriaId, $userId]);
    $categoria = $stmt->fetch();
    
    if (!$categoria) {
        throw new Exception('Categoría no encontrada o no pertenece al usuario');
    }
    
    // Obtener links de la categoría
    $stmt = $pdo->prepare("SELECT * FROM links WHERE usuario_id = ? AND categoria_id = ? ORDER BY creado_en DESC");
    $stmt->execute([$userId, $categoriaId]);
    $links = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'user_id' => (int)$userId,
        'categoria_id' => (int)$categoriaId,
        'total_links' => count($links),
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

function createLink($pdo, $input) {
    $userId = $input['user_id'] ?? 0;
    $categoriaId = $input['categoria_id'] ?? 0;
    $url = $input['url'] ?? '';
    $titulo = $input['titulo'] ?? '';
    $descripcion = $input['descripcion'] ?? '';
    $notaLink = $input['nota_link'] ?? '';
    
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
    
    // Obtener metadatos de la URL si no se proporcionan título y descripción
    $urlCanonica = $url;
    if (empty($titulo) || empty($descripcion)) {
        $metadata = getUrlMetadataFromUrl($url);
        if ($metadata) {
            if (empty($titulo)) $titulo = $metadata['titulo'] ?? '';
            if (empty($descripcion)) $descripcion = $metadata['descripcion'] ?? '';
            if (!empty($metadata['imagen'])) $imagen = $metadata['imagen'];
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
    
    echo json_encode([
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
            
            // Obtener algunos links de ejemplo
            $stmt = $pdo->query("SELECT id, usuario_id, categoria_id, titulo, url FROM links LIMIT 5");
            $sampleLinks = $stmt->fetchAll();
            $debug['debug_info']['sample_links'] = $sampleLinks;
            
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
?>
