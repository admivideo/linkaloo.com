<?php
$configLoaded = false;
$docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') : '';
$configCandidates = [
    $docRoot ? $docRoot . '/api/config.php' : null,
    __DIR__ . '/config.php',
    __DIR__ . '/api/config.php',
    __DIR__ . '/../api/config.php'
];

foreach ($configCandidates as $candidate) {
    if (empty($candidate)) {
        continue;
    }
    if (file_exists($candidate)) {
        require_once $candidate;
        $configLoaded = true;
        break;
    }
}

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$token = preg_replace('/\s+/', '', $token);
$tokenLower = strtolower($token);
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
        $stmt = $pdo->prepare("SELECT id, nombre, nota FROM categorias WHERE LOWER(TRIM(share_token)) = ? LIMIT 1");
        $stmt->execute([$tokenLower]);
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
        :root {
            --linkaloo-blue: #1da1f2;
            --bg-light: #f6f8fb;
            --text-primary: #1c1c1c;
            --text-secondary: #4a4a4a;
            --card-border: #e6e9ef;
        }
        body { font-family: Arial, sans-serif; background: var(--bg-light); color: var(--text-primary); margin: 0; }
        .container { max-width: 560px; margin: 0 auto; padding: 24px 16px 40px; text-align: center; }
        .card { background: #ffffff; border-radius: 16px; padding: 24px; box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
        h1 { font-size: 22px; margin: 0 0 12px; color: var(--text-primary); }
        p { font-size: 15px; line-height: 1.5; color: var(--text-secondary); }
        .btn { display: inline-block; margin: 10px 6px 0; padding: 12px 18px; border-radius: 10px; text-decoration: none; font-weight: 600; }
        .btn-primary { background: var(--linkaloo-blue); color: #fff; }
        .btn-secondary { background: #f1f3f5; color: var(--text-primary); }
        .token { font-size: 12px; color: #888; margin-top: 16px; word-break: break-all; }
        .links-grid { display: grid; grid-template-columns: 1fr; gap: 14px; margin-top: 18px; }
        .link-card { text-align: left; background: #fff; border: 1px solid var(--card-border); border-radius: 14px; overflow: hidden; box-shadow: 0 4px 14px rgba(0,0,0,0.06); }
        .link-image { width: 100%; height: 180px; object-fit: cover; display: block; background: #eef2f6; }
        .link-body { padding: 12px 14px 14px; }
        .link-title { font-size: 16px; font-weight: 700; color: var(--text-primary); margin: 0 0 6px; }
        .link-desc { font-size: 13px; color: var(--text-secondary); margin: 0 0 8px; }
        .link-url { font-size: 12px; color: var(--linkaloo-blue); word-break: break-all; text-decoration: none; }
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
                    <div class="links-grid">
                        <?php foreach ($links as $link) : ?>
                            <div class="link-card">
                                <?php if (!empty($link['imagen'])) : ?>
                                    <?php
                                        $img = $link['imagen'];
                                        if (strpos($img, 'http://') !== 0 && strpos($img, 'https://') !== 0) {
                                            $img = 'https://linkaloo.com' . $img;
                                        }
                                    ?>
                                    <img class="link-image" src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" alt="">
                                <?php endif; ?>
                                <div class="link-body">
                                    <div class="link-title"><?php echo htmlspecialchars($link['titulo'] ?? 'Sin título', ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php if (!empty($link['descripcion'])) : ?>
                                        <div class="link-desc"><?php echo htmlspecialchars($link['descripcion'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($link['url'])) : ?>
                                        <a class="link-url" href="<?php echo htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                                            <?php echo htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
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
