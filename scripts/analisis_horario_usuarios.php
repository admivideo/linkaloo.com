<?php

declare(strict_types=1);


function createConnection(): PDO
{
    $host = getenv('LINKALOO_DB_HOST') ?: '82.223.84.165';
    $database = getenv('LINKALOO_DB_NAME') ?: 'smartlinks';
    $user = getenv('LINKALOO_DB_USER') ?: 'smartuserIOn0s';
    $password = getenv('LINKALOO_DB_PASS') ?: 'WMCuxq@ts8s8g8^w';

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $database);

    return new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
    ]);
}

/**
 * @return array<int, array{hora:int, media_usuarios_activos_hora:float, total_usuarios_activos_hora:int, usuarios_ultimo_acceso:int, total_dias:int}>
 */
function fetchHourlyMetrics(PDO $pdo): array
{
    $sql = <<<SQL
WITH RECURSIVE horas AS (
    SELECT 0 AS hora
    UNION ALL
    SELECT hora + 1 FROM horas WHERE hora < 23
),
dias AS (
    SELECT COUNT(DISTINCT DATE(creado_en)) AS total_dias
    FROM links
    WHERE creado_en IS NOT NULL
),
actividad AS (
    SELECT
        HOUR(creado_en) AS hora,
        DATE(creado_en) AS fecha,
        COUNT(DISTINCT usuario_id) AS usuarios_activos
    FROM links
    WHERE creado_en IS NOT NULL
    GROUP BY HOUR(creado_en), DATE(creado_en)
),
actividad_promedio AS (
    SELECT
        a.hora,
        AVG(a.usuarios_activos) AS media_usuarios_activos_hora,
        SUM(a.usuarios_activos) AS total_usuarios_activos_hora
    FROM actividad a
    GROUP BY a.hora
),
conexiones_aprox AS (
    SELECT
        HOUR(ultimo_acceso) AS hora,
        COUNT(*) AS usuarios_ultimo_acceso
    FROM usuarios
    WHERE ultimo_acceso IS NOT NULL
    GROUP BY HOUR(ultimo_acceso)
)
SELECT
    h.hora,
    COALESCE(ap.media_usuarios_activos_hora, 0) AS media_usuarios_activos_hora,
    COALESCE(ap.total_usuarios_activos_hora, 0) AS total_usuarios_activos_hora,
    COALESCE(ca.usuarios_ultimo_acceso, 0) AS usuarios_ultimo_acceso,
    d.total_dias
FROM horas h
CROSS JOIN dias d
LEFT JOIN actividad_promedio ap ON ap.hora = h.hora
LEFT JOIN conexiones_aprox ca ON ca.hora = h.hora
ORDER BY h.hora ASC
SQL;

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return array_map(static function (array $row): array {
        return [
            'hora' => (int) $row['hora'],
            'media_usuarios_activos_hora' => (float) $row['media_usuarios_activos_hora'],
            'total_usuarios_activos_hora' => (int) $row['total_usuarios_activos_hora'],
            'usuarios_ultimo_acceso' => (int) $row['usuarios_ultimo_acceso'],
            'total_dias' => (int) $row['total_dias'],
        ];
    }, $rows);
}

function hourLabel(int $hour): string
{
    return str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':00';
}

try {
    $pdo = createConnection();
    $metrics = fetchHourlyMetrics($pdo);
} catch (Throwable $exception) {
    http_response_code(500);
    echo 'Error al cargar métricas horarias: ' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
}

usort($metrics, static fn (array $a, array $b): int => $b['media_usuarios_activos_hora'] <=> $a['media_usuarios_activos_hora']);
$topHours = array_slice($metrics, 0, 3);
usort($metrics, static fn (array $a, array $b): int => $a['hora'] <=> $b['hora']);

$chartData = array_map(static function (array $row): array {
    return [
        'label' => hourLabel($row['hora']),
        'value' => round($row['media_usuarios_activos_hora'], 2),
    ];
}, $metrics);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Análisis horario de usuarios</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #0f172a; }
        h1 { margin-bottom: 8px; }
        .muted { color: #475569; }
        .cards { display: grid; gap: 12px; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); margin: 16px 0; }
        .card { border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px; background: #f8fafc; }
        .big { font-size: 1.25rem; font-weight: 700; }
        .chart-wrap { border: 1px solid #cbd5e1; border-radius: 10px; padding: 16px; background: #fff; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #e2e8f0; padding: 8px; text-align: left; }
        th { background: #f1f5f9; }
    </style>
</head>
<body>
    <h1>Actividad de usuarios por franja horaria (1 hora)</h1>
    <p class="muted">Eje X: horas del día. Eje Y: media de usuarios activos por hora.</p>

    <section class="cards">
        <?php foreach ($topHours as $index => $row): ?>
            <article class="card">
                <div class="muted">Top <?= $index + 1 ?></div>
                <div class="big"><?= htmlspecialchars(hourLabel((int) $row['hora']), ENT_QUOTES, 'UTF-8') ?></div>
                <div>Media: <?= number_format((float) $row['media_usuarios_activos_hora'], 2) ?> usuarios</div>
                <div class="muted">Conexión aprox: <?= (int) $row['usuarios_ultimo_acceso'] ?> usuarios</div>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="chart-wrap">
        <canvas id="hourlyChart" width="1200" height="450" aria-label="Gráfico de barras de actividad horaria"></canvas>
    </section>

    <table>
        <thead>
            <tr>
                <th>Hora</th>
                <th>Media usuarios activos</th>
                <th>Total usuarios activos</th>
                <th>Usuarios (último acceso)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($metrics as $row): ?>
                <tr>
                    <td><?= htmlspecialchars(hourLabel((int) $row['hora']), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= number_format((float) $row['media_usuarios_activos_hora'], 2) ?></td>
                    <td><?= (int) $row['total_usuarios_activos_hora'] ?></td>
                    <td><?= (int) $row['usuarios_ultimo_acceso'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script>
        const data = <?= json_encode($chartData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const canvas = document.getElementById('hourlyChart');
        const ctx = canvas.getContext('2d');

        const width = canvas.width;
        const height = canvas.height;
        const padding = { top: 20, right: 20, bottom: 70, left: 60 };
        const innerWidth = width - padding.left - padding.right;
        const innerHeight = height - padding.top - padding.bottom;

        const maxValue = Math.max(...data.map(d => d.value), 1);
        const barWidth = innerWidth / data.length * 0.7;
        const gap = innerWidth / data.length * 0.3;

        ctx.clearRect(0, 0, width, height);

        ctx.strokeStyle = '#94a3b8';
        ctx.beginPath();
        ctx.moveTo(padding.left, padding.top);
        ctx.lineTo(padding.left, height - padding.bottom);
        ctx.lineTo(width - padding.right, height - padding.bottom);
        ctx.stroke();

        ctx.fillStyle = '#2563eb';
        data.forEach((d, i) => {
            const x = padding.left + i * (barWidth + gap) + gap / 2;
            const barHeight = (d.value / maxValue) * innerHeight;
            const y = height - padding.bottom - barHeight;
            ctx.fillRect(x, y, barWidth, barHeight);

            if (i % 2 === 0) {
                ctx.save();
                ctx.fillStyle = '#334155';
                ctx.font = '12px Arial';
                ctx.translate(x + barWidth / 2, height - padding.bottom + 14);
                ctx.rotate(-Math.PI / 4);
                ctx.fillText(d.label, 0, 0);
                ctx.restore();
            }
        });

        ctx.fillStyle = '#0f172a';
        ctx.font = '13px Arial';
        ctx.fillText('Media de usuarios activos', 8, padding.top + 8);
        ctx.fillText('Horas del día', width / 2 - 30, height - 12);
    </script>
</body>
</html>
