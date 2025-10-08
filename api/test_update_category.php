<?php
// Archivo de prueba para testear la función updateLinkCategory
require_once 'config.php';

// Configurar headers CORS
setCorsHeaders();

// Manejar preflight requests
handlePreflightRequest();

try {
    // Obtener conexión a la base de datos
    $pdo = getDatabaseConnection();
    
    // Datos de prueba
    $input = [
        'action' => 'update_link_category',
        'link_id' => 800,
        'categoria_id' => 29
    ];
    
    error_log("=== PRUEBA DE FUNCIÓN updateLinkCategory ===");
    error_log("Input de prueba: " . json_encode($input));
    
    // Llamar directamente a la función
    updateLinkCategory($pdo, $input);
    
} catch (Exception $e) {
    error_log("ERROR en prueba: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function updateLinkCategory($pdo, $input) {
    error_log("=== FUNCIÓN updateLinkCategory LLAMADA ===");
    error_log("Input recibido: " . json_encode($input));
    
    $linkId = $input['link_id'] ?? 0;
    $categoriaId = $input['categoria_id'] ?? 0;
    
    error_log("Link ID: " . $linkId . ", Categoría ID: " . $categoriaId);
    
    if ($linkId <= 0) {
        throw new Exception('ID de link válido es requerido');
    }
    
    if ($categoriaId <= 0) {
        throw new Exception('ID de categoría válido es requerido');
    }
    
    // Verificar que el link existe
    error_log("Buscando link con ID: " . $linkId);
    $stmt = $pdo->prepare("SELECT * FROM links WHERE id = ?");
    $stmt->execute([$linkId]);
    $link = $stmt->fetch();
    
    if (!$link) {
        error_log("ERROR: Link no encontrado con ID: " . $linkId);
        throw new Exception('Link no encontrado');
    }
    
    $userId = $link['usuario_id'];
    error_log("Link encontrado - Usuario ID: " . $userId);
    
    // Verificar que la nueva categoría existe y pertenece al usuario
    error_log("Verificando categoría " . $categoriaId . " para usuario " . $userId);
    $stmt = $pdo->prepare("SELECT id FROM categorias WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$categoriaId, $userId]);
    $categoria = $stmt->fetch();
    
    if (!$categoria) {
        error_log("ERROR: Categoría no encontrada o no pertenece al usuario");
        throw new Exception('Categoría no encontrada o no pertenece al usuario');
    }
    
    error_log("Categoría verificada correctamente");
    
    // Actualizar la categoría del link
    error_log("Actualizando link " . $linkId . " a categoría " . $categoriaId);
    $stmt = $pdo->prepare("UPDATE links SET categoria_id = ?, actualizado_en = NOW() WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$categoriaId, $linkId, $userId]);
    error_log("Link actualizado exitosamente");
    
    // Verificar que se actualizó correctamente
    $stmt = $pdo->prepare("SELECT * FROM links WHERE id = ?");
    $stmt->execute([$linkId]);
    $updatedLink = $stmt->fetch();
    
    error_log("Enviando respuesta exitosa");
    echo json_encode([
        'success' => true,
        'action' => 'category_updated',
        'link' => [
            'id' => (int)$updatedLink['id'],
            'usuario_id' => (int)$updatedLink['usuario_id'],
            'categoria_id' => (int)$updatedLink['categoria_id'],
            'url' => $updatedLink['url'],
            'titulo' => $updatedLink['titulo'],
            'actualizado_en' => $updatedLink['actualizado_en']
        ],
        'message' => 'Categoría del enlace actualizada exitosamente'
    ]);
    error_log("=== FUNCIÓN updateLinkCategory COMPLETADA EXITOSAMENTE ===");
}
?>
