<?php
$configLoaded = false;
$configCandidates = [
    __DIR__ . '/config.php',
    __DIR__ . '/api/config.php',
    __DIR__ . '/../api/config.php'
];

foreach ($configCandidates as $candidate) {
    if (file_exists($candidate)) {
        require_once $candidate;
        $configLoaded = true;
        break;
    }
}

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$encodedToken = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
$isInApp = !empty($_GET['in_app']) && $_GET['in_app'] !== '0';

$playStoreUrl = "https://play.google.com/store/apps/details?id=com.linka2025.linkaloo";
$deepLink = "linkaloo://tablero?token=" . urlencode($token);
$intentLink = "intent://tablero?token=" . urlencode($token) .
    "#Intent;scheme=linkaloo;package=com.linka2025.linkaloo;end";

$categoria = null;
$links = [];

if ($isInApp && !empty($token) && $configLoaded && function_exists('getDatabaseConnection')) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("SELECT id, nombre, nota FROM categorias WHERE share_token = ? LIMIT 1");
        $stmt->execute([$token]);
        $categoria = $stmt->fetch();

        if ($categoria) {
            $linksStmt = $pdo->prepare(
                "SELECT titulo, descripcion, url, imagen FROM links WHERE categoria_id = ? ORDER BY creado_en DESC"
            );
            $linksStmt->execute([$categoria['id']]);
            $links = $linksStmt->fetchAll();
        }
    } catch (Exception $e) {
        $categoria = null;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Tablero compartido - Linkaloo</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f6f8fb; color: #1c1c1c; margin: 0; }
        .container { max-width: 520px; margin: 0 auto; padding: 32px 20px 40px; text-align: center; }
        .card { background: #ffffff; border-radius: 12px; padding: 24px; box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
        h1 { font-size: 22px; margin: 0 0 12px; }
        p { font-size: 15px; line-height: 1.5; color: #4a4a4a; }
        .btn { display: inline-block; margin: 10px 6px 0; padding: 12px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; }
        .btn-primary { background: #1da1f2; color: #fff; }
        .btn-secondary { background: #f1f3f5; color: #1c1c1c; }
        .token { font-size: 12px; color: #888; margin-top: 16px; word-break: break-all; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <?php if ($isInApp && $categoria) : ?>
                <h1><?php echo htmlspecialchars($categoria['nombre'], ENT_QUOTES, 'UTF-8'); ?></h1>
                <?php if (!empty($categoria['nota'])) : ?>
                    <p><?php echo nl2br(htmlspecialchars($categoria['nota'], ENT_QUOTES, 'UTF-8')); ?></p>
                <?php endif; ?>

                <?php if (!empty($links)) : ?>
                    <?php foreach ($links as $link) : ?>
                        <div style="text-align:left;margin:16px 0;padding:12px;border:1px solid #e6e9ef;border-radius:10px;">
                            <?php if (!empty($link['imagen'])) : ?>
                                <img src="<?php echo htmlspecialchars($link['imagen'], ENT_QUOTES, 'UTF-8'); ?>" alt="" style="width:100%;height:auto;border-radius:8px;margin-bottom:8px;">
                            <?php endif; ?>
                            <div style="font-weight:700;"><?php echo htmlspecialchars($link['titulo'] ?? 'Sin título', ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php if (!empty($link['descripcion'])) : ?>
                                <div style="font-size:13px;color:#4a4a4a;margin-top:4px;">
                                    <?php echo htmlspecialchars($link['descripcion'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($link['url'])) : ?>
                                <div style="margin-top:6px;">
                                    <a href="<?php echo htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                                        <?php echo htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p>No hay enlaces disponibles en este tablero.</p>
                <?php endif; ?>
            <?php elseif ($isInApp && !$categoria) : ?>
                <h1>No se pudo cargar el tablero</h1>
                <p>El enlace no es válido o el tablero ya no está disponible.</p>
                <?php if (!empty($encodedToken)) : ?>
                    <div class="token">Token: <?php echo $encodedToken; ?></div>
                <?php endif; ?>
            <?php else : ?>
                <h1>Abrir tablero en Linkaloo</h1>
                <p>Este tablero está listo para abrirse en la app. Si no la tienes instalada, puedes descargarla desde Google Play.</p>
                <a class="btn btn-primary" id="openLinkaloo" href="<?php echo $intentLink; ?>">Abrir en Linkaloo</a>
                <a class="btn btn-secondary" href="<?php echo $playStoreUrl; ?>">Instalar Linkaloo</a>
                <?php if (!empty($encodedToken)) : ?>
                    <div class="token">Token: <?php echo $encodedToken; ?></div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <script>
        (function () {
            var token = "<?php echo addslashes($token); ?>";
            if (!token) {
                return;
            }
            // No auto-redirect. The "Abrir en Linkaloo" button uses intent://
        })();
    </script>
</body>
</html>
