<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sin conexión - linkaloo</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f8fa;
            font-family: 'Rambla', 'Helvetica Neue', Arial, sans-serif;
            color: #1f2933;
        }
        .offline-wrapper {
            text-align: center;
            padding: 40px 20px;
            max-width: 420px;
        }
        .offline-wrapper img {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
        }
        .offline-wrapper p {
            margin: 24px 0 0;
            font-size: 1.1rem;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    <main class="offline-wrapper">
        <img src="/img/Trabajando_Desconectado_500.jpg" alt="Ilustración de linkaloo trabajando sin conexión">
        <p>No podemos conectar con linkaloo en este momento.<br>Revisa tu conexión a internet e inténtalo de nuevo.</p>
    </main>
<?php include 'firebase_scripts.php'; ?>
</body>
</html>
