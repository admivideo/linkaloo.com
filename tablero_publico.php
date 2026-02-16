<?php
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$encodedToken = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
$playStoreUrl = "https://play.google.com/store/apps/details?id=com.linka2025.linkaloo";
$deepLink = "linkaloo://tablero?token=" . urlencode($token);
$intentLink = "intent://tablero?token=" . urlencode($token) .
    "#Intent;scheme=linkaloo;package=com.linka2025.linkaloo;end";
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
            <h1>Abrir tablero en Linkaloo</h1>
            <p>Este tablero está listo para abrirse en la app. Si no la tienes instalada, puedes descargarla desde Google Play.</p>
            <a class="btn btn-primary" href="<?php echo $intentLink; ?>">Abrir en Linkaloo</a>
            <a class="btn btn-secondary" href="<?php echo $playStoreUrl; ?>">Instalar Linkaloo</a>
            <?php if (!empty($encodedToken)) : ?>
                <div class="token">Token: <?php echo $encodedToken; ?></div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        (function () {
            var token = "<?php echo addslashes($token); ?>";
            if (!token) {
                return;
            }
            var deepLink = "<?php echo $deepLink; ?>";
            try {
                setTimeout(function () {
                    window.location.href = deepLink;
                }, 100);
            } catch (e) {
                // ignore
            }
        })();
    </script>
</body>
</html>
