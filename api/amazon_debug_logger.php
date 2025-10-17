<?php
/**
 * Sistema de logging personalizado para debugging de Amazon
 * Accesible desde: https://linkaloo.com/api/amazon_debug_logger.php
 */

// Archivo de log personalizado
$logFile = __DIR__ . '/amazon_debug.log';

// Si se recibe parámetro "clear", limpiar el log
if (isset($_GET['clear'])) {
    file_put_contents($logFile, '');
    echo "✅ Log limpiado\n";
    echo "<a href='?'>Ver log</a>\n";
    exit;
}

// Si se recibe parámetro "tail", mostrar últimas N líneas
$tail = isset($_GET['tail']) ? (int)$_GET['tail'] : 100;

// Leer el archivo de log
if (!file_exists($logFile)) {
    echo "📝 Log de Amazon Debug\n";
    echo "======================\n\n";
    echo "ℹ️ No hay logs todavía.\n\n";
    echo "Comparte un link de Amazon desde la app para ver los logs aquí.\n\n";
    echo "<a href='?clear'>Limpiar log</a> | ";
    echo "<a href='?'>Refrescar</a> | ";
    echo "<a href='?tail=50'>Últimas 50 líneas</a> | ";
    echo "<a href='?tail=200'>Últimas 200 líneas</a>\n";
    exit;
}

// Leer el archivo
$lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$totalLines = count($lines);

// Mostrar solo las últimas N líneas
if ($totalLines > $tail) {
    $lines = array_slice($lines, -$tail);
}

// Mostrar con formato
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Amazon Debug Log - Linkaloo</title>
    <style>
        body {
            font-family: 'Consolas', 'Monaco', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            margin: 0;
        }
        .header {
            background: #2d2d30;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .header h1 {
            margin: 0 0 10px 0;
            color: #4ec9b0;
        }
        .stats {
            color: #9cdcfe;
            font-size: 14px;
        }
        .controls {
            margin: 10px 0;
        }
        .controls a {
            color: #4ec9b0;
            text-decoration: none;
            padding: 5px 10px;
            background: #3c3c3c;
            border-radius: 3px;
            margin-right: 10px;
            display: inline-block;
        }
        .controls a:hover {
            background: #505050;
        }
        .log-container {
            background: #1e1e1e;
            border: 1px solid #3c3c3c;
            border-radius: 5px;
            padding: 15px;
            overflow-x: auto;
        }
        .log-line {
            margin: 2px 0;
            padding: 3px;
            border-radius: 2px;
        }
        .log-line.error {
            background: #3d1f1f;
            color: #f48771;
        }
        .log-line.success {
            background: #1f3d1f;
            color: #4ec9b0;
        }
        .log-line.warning {
            background: #3d3d1f;
            color: #dcdcaa;
        }
        .log-line.info {
            color: #9cdcfe;
        }
        .timestamp {
            color: #858585;
            margin-right: 10px;
        }
        pre {
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Amazon Debug Log - Linkaloo</h1>
        <div class="stats">
            Total de líneas en el archivo: <?php echo $totalLines; ?><br>
            Mostrando: <?php echo count($lines); ?> líneas
        </div>
        <div class="controls">
            <a href="?">🔄 Refrescar</a>
            <a href="?tail=50">📄 Últimas 50</a>
            <a href="?tail=100">📄 Últimas 100</a>
            <a href="?tail=200">📄 Últimas 200</a>
            <a href="?tail=1000">📄 Últimas 1000</a>
            <a href="?clear" onclick="return confirm('¿Limpiar todo el log?')">🗑️ Limpiar Log</a>
        </div>
    </div>
    
    <div class="log-container">
        <?php if (empty($lines)): ?>
            <p style="color: #858585;">ℹ️ No hay logs todavía. Comparte un link de Amazon desde la app.</p>
        <?php else: ?>
            <?php foreach ($lines as $line): ?>
                <?php
                // Detectar tipo de mensaje
                $class = 'info';
                if (strpos($line, '❌') !== false || strpos($line, 'ERROR') !== false || strpos($line, 'Error') !== false) {
                    $class = 'error';
                } elseif (strpos($line, '✅') !== false || strpos($line, 'exitosa') !== false || strpos($line, 'EXITOSA') !== false) {
                    $class = 'success';
                } elseif (strpos($line, '⚠️') !== false || strpos($line, 'WARNING') !== false) {
                    $class = 'warning';
                }
                ?>
                <div class="log-line <?php echo $class; ?>">
                    <pre><?php echo htmlspecialchars($line); ?></pre>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <script>
        // Auto-scroll al final
        window.scrollTo(0, document.body.scrollHeight);
        
        // Auto-refresh cada 5 segundos (opcional, comentado por defecto)
        // setTimeout(function(){ location.reload(); }, 5000);
    </script>
</body>
</html>

