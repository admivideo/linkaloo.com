<?php
require 'config.php';

const STATS_USER = '4besdev616339117*-$%&';
const STATS_PASS = 'Fwc63GJuMKXybyBKAkepQPgj7p*-';

function requireStatsAuth(): void
{
    $providedUser = $_SERVER['PHP_AUTH_USER'] ?? null;
    $providedPass = $_SERVER['PHP_AUTH_PW'] ?? null;

    $isValidUser = is_string($providedUser) && hash_equals(STATS_USER, $providedUser);
    $isValidPass = is_string($providedPass) && hash_equals(STATS_PASS, $providedPass);

    if ($isValidUser && $isValidPass) {
        return;
    }

    header('WWW-Authenticate: Basic realm="Linkaloo Stats"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Acceso no autorizado.';
    exit;
}

function pickColumn(PDO $pdo, string $tableName, array $candidates): ?string
{
    $sql = 'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tableName]);

    $available = array_map('strtolower', array_column($stmt->fetchAll(), 'COLUMN_NAME'));

    foreach ($candidates as $candidate) {
        if (in_array(strtolower($candidate), $available, true)) {
            return $candidate;
        }
    }

    return null;
}

function formatDate(?string $value): string
{
    if (!$value) {
        return '-';
    }

    try {
        $date = new DateTimeImmutable($value);
        return $date->format('Y-m-d');
    } catch (Exception $e) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

requireStatsAuth();

$userCreatedColumn = pickColumn($pdo, 'usuarios', ['creado_en', 'created_at', 'fecha_creacion', 'registrado_en']);
$linkCreatedColumn = pickColumn($pdo, 'links', ['creado_en', 'created_at', 'fecha_creacion']);

$userDateSelect = $userCreatedColumn ? "`{$userCreatedColumn}`" : 'NULL';
$linkDateSelect = $linkCreatedColumn ? "`{$linkCreatedColumn}`" : null;

$usersSql = "SELECT id, {$userDateSelect} AS fecha_creacion FROM usuarios ORDER BY id ASC";
$usersStmt = $pdo->query($usersSql);
$users = $usersStmt->fetchAll();

$categoryCounts = [];
$categoryStmt = $pdo->query('SELECT usuario_id, COUNT(*) AS total FROM categorias GROUP BY usuario_id');
foreach ($categoryStmt->fetchAll() as $row) {
    $categoryCounts[(int) $row['usuario_id']] = (int) $row['total'];
}

$linkStats = [];
if ($linkDateSelect) {
    $linksSql = "
        SELECT
            usuario_id,
            COUNT(*) AS total,
            MIN({$linkDateSelect}) AS fecha_primer_favolink,
            MAX({$linkDateSelect}) AS fecha_ultimo_favolink
        FROM links
        GROUP BY usuario_id
    ";
} else {
    $linksSql = '
        SELECT
            usuario_id,
            COUNT(*) AS total,
            NULL AS fecha_primer_favolink,
            NULL AS fecha_ultimo_favolink
        FROM links
        GROUP BY usuario_id
    ';
}

$linkStmt = $pdo->query($linksSql);
foreach ($linkStmt->fetchAll() as $row) {
    $linkStats[(int) $row['usuario_id']] = [
        'total' => (int) $row['total'],
        'fecha_primer_favolink' => $row['fecha_primer_favolink'] ?? null,
        'fecha_ultimo_favolink' => $row['fecha_ultimo_favolink'] ?? null,
    ];
}

$statsRows = [];
foreach ($users as $user) {
    $userId = (int) $user['id'];
    $userLinks = $linkStats[$userId] ?? ['total' => 0, 'fecha_primer_favolink' => null, 'fecha_ultimo_favolink' => null];

    $statsRows[] = [
        'id' => $userId,
        'fecha_creacion' => $user['fecha_creacion'] ?? null,
        'cantidad_categorias' => $categoryCounts[$userId] ?? 0,
        'cantidad_favolinks_guardados' => $userLinks['total'],
        'fecha_primer_favolink' => $userLinks['fecha_primer_favolink'],
        'fecha_ultimo_favolink' => $userLinks['fecha_ultimo_favolink'],
    ];
}


$totalUsuarios = count($statsRows);
$totalLinks = 0;
$resumen = [
    'usuarios_sin_links' => ['usuarios' => 0, 'links' => 0],
    'usuarios_0_3' => ['usuarios' => 0, 'links' => 0],
    'usuarios_4_10' => ['usuarios' => 0, 'links' => 0],
    'usuarios_11_25' => ['usuarios' => 0, 'links' => 0],
    'usuarios_26_50' => ['usuarios' => 0, 'links' => 0],
    'usuarios_51_100' => ['usuarios' => 0, 'links' => 0],
    'usuarios_mas_100' => ['usuarios' => 0, 'links' => 0],
];

foreach ($statsRows as $row) {
    $linksGuardados = (int) ($row['cantidad_favolinks_guardados'] ?? 0);
    $totalLinks += $linksGuardados;

    if ($linksGuardados === 0) {
        $resumen['usuarios_sin_links']['usuarios']++;
        $resumen['usuarios_0_3']['usuarios']++;
        continue;
    }

    if ($linksGuardados <= 3) {
        $resumen['usuarios_0_3']['usuarios']++;
        $resumen['usuarios_0_3']['links'] += $linksGuardados;
    } elseif ($linksGuardados <= 10) {
        $resumen['usuarios_4_10']['usuarios']++;
        $resumen['usuarios_4_10']['links'] += $linksGuardados;
    } elseif ($linksGuardados <= 25) {
        $resumen['usuarios_11_25']['usuarios']++;
        $resumen['usuarios_11_25']['links'] += $linksGuardados;
    } elseif ($linksGuardados <= 50) {
        $resumen['usuarios_26_50']['usuarios']++;
        $resumen['usuarios_26_50']['links'] += $linksGuardados;
    } elseif ($linksGuardados <= 100) {
        $resumen['usuarios_51_100']['usuarios']++;
        $resumen['usuarios_51_100']['links'] += $linksGuardados;
    } else {
        $resumen['usuarios_mas_100']['usuarios']++;
        $resumen['usuarios_mas_100']['links'] += $linksGuardados;
    }
}

$segmentoPorcentajeLinks = [];
foreach ($resumen as $segmento => $data) {
    $segmentoPorcentajeLinks[$segmento] = $totalLinks > 0
        ? round(($data['links'] / $totalLinks) * 100, 2)
        : 0.0;
}

$segmentosInfo = [
    ['key' => 'usuarios_sin_links', 'label' => '0 links', 'color' => '#6b7280'],
    ['key' => 'usuarios_0_3', 'label' => '0-3', 'color' => '#22c55e'],
    ['key' => 'usuarios_4_10', 'label' => '4-10', 'color' => '#3b82f6'],
    ['key' => 'usuarios_11_25', 'label' => '11-25', 'color' => '#a855f7'],
    ['key' => 'usuarios_26_50', 'label' => '26-50', 'color' => '#f59e0b'],
    ['key' => 'usuarios_51_100', 'label' => '51-100', 'color' => '#ef4444'],
    ['key' => 'usuarios_mas_100', 'label' => '+100', 'color' => '#14b8a6'],
];

$chartParts = [];
$legendRows = [];
$acumulado = 0.0;
foreach ($segmentosInfo as $segmento) {
    $key = $segmento['key'];
    $pct = $segmentoPorcentajeLinks[$key] ?? 0.0;
    if ($pct > 0) {
        $inicio = $acumulado;
        $acumulado += $pct;
        $chartParts[] = sprintf('%s %.2f%% %.2f%%', $segmento['color'], $inicio, $acumulado);
    }

    $legendRows[] = [
        'label' => $segmento['label'],
        'color' => $segmento['color'],
        'pct' => $pct,
    ];
}
$pieBackground = $chartParts ? 'conic-gradient(' . implode(', ', $chartParts) . ')' : 'conic-gradient(#6b7280 0% 100%)';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Linkaloo estadísticas</title>
    <style>
        :root {
            color-scheme: dark;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #0f1224;
            color: #eff3ff;
        }

        .wrapper {
            width: min(1200px, 100% - 2rem);
            margin: 1.5rem auto;
        }

        h1 {
            margin: 0 0 1rem;
            font-size: clamp(1.2rem, 2.5vw, 1.8rem);
        }



        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 0.8rem;
            margin: 0 0 1rem;
        }

        .summary-card {
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.05);
            padding: 0.75rem;
        }

        .summary-title {
            margin: 0;
            font-size: 0.83rem;
            color: #c8d1ff;
        }

        .summary-value {
            margin: 0.25rem 0 0;
            font-size: 1.35rem;
            font-weight: 700;
        }


        .summary-meta {
            margin: 0.3rem 0 0;
            font-size: 0.85rem;
            color: #d9e0ff;
            line-height: 1.35;
        }

        .table-container {
            overflow-x: auto;
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 860px;
        }

        thead {
            background: rgba(255, 255, 255, 0.12);
        }

        th,
        td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.92rem;
        }

        tbody tr:hover {
            background: rgba(255, 255, 255, 0.06);
        }

        th button.sort-btn {
            all: unset;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-weight: 700;
            width: 100%;
        }

        th button.sort-btn::after {
            content: '↕';
            font-size: 0.75rem;
            opacity: 0.7;
        }

        th button.sort-btn[data-order="asc"]::after {
            content: '↑';
            opacity: 1;
        }

        th button.sort-btn[data-order="desc"]::after {
            content: '↓';
            opacity: 1;
        }

        .empty {
            padding: 1rem;
        }


        .layout-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 1rem;
            align-items: start;
        }

        .list-column .table-container {
            max-width: 100%;
        }

        .sidebar {
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            position: sticky;
            top: 1rem;
        }

        .sidebar h2 {
            margin: 0 0 0.8rem;
            font-size: 1rem;
        }

        .pie-chart {
            width: min(220px, 100%);
            aspect-ratio: 1 / 1;
            border-radius: 50%;
            margin: 0 auto 1rem;
            background: var(--pie-background);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .legend {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 0.35rem;
        }

        .legend li {
            display: flex;
            justify-content: space-between;
            gap: 0.5rem;
            font-size: 0.87rem;
        }

        .legend-label {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--dot-color);
        }

        @media (max-width: 980px) {
            .layout-grid {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: static;
            }
        }

        @media (max-width: 760px) {
            .table-container {
                border: none;
                background: transparent;
            }

            table,
            thead,
            tbody,
            tr,
            th,
            td {
                display: block;
            }

            thead {
                display: none;
            }

            table {
                min-width: 0;
            }

            tr {
                margin-bottom: 0.9rem;
                border: 1px solid rgba(255, 255, 255, 0.18);
                border-radius: 12px;
                background: rgba(255, 255, 255, 0.05);
                padding: 0.4rem 0;
            }

            td {
                border: none;
                padding: 0.55rem 0.85rem;
                display: flex;
                justify-content: space-between;
                gap: 0.75rem;
            }

            td::before {
                content: attr(data-label);
                font-weight: 700;
                color: #95a4ff;
            }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <h1>Estadísticas de usuarios de Linkaloo</h1>


    <div class="summary-grid">
        <article class="summary-card">
            <p class="summary-title">Total usuarios</p>
            <p class="summary-value"><?= $totalUsuarios ?></p>
            <p class="summary-meta">Links del segmento: <?= $totalLinks ?></p>
            <p class="summary-meta">% del total links: 100%</p>
        </article>
        <article class="summary-card">
            <p class="summary-title">Usuarios con 0 links guardados</p>
            <p class="summary-value"><?= $resumen['usuarios_sin_links']['usuarios'] ?></p>
            <p class="summary-meta">Links del segmento: <?= $resumen['usuarios_sin_links']['links'] ?></p>
            <p class="summary-meta">% del total links: <?= number_format($segmentoPorcentajeLinks['usuarios_sin_links'], 2, ',', '.') ?>%</p>
        </article>
        <article class="summary-card">
            <p class="summary-title">Usuarios con 0-3 favolinks guardados</p>
            <p class="summary-value"><?= $resumen['usuarios_0_3']['usuarios'] ?></p>
            <p class="summary-meta">Links del segmento: <?= $resumen['usuarios_0_3']['links'] ?></p>
            <p class="summary-meta">% del total links: <?= number_format($segmentoPorcentajeLinks['usuarios_0_3'], 2, ',', '.') ?>%</p>
        </article>
        <article class="summary-card">
            <p class="summary-title">Usuarios con 4-10 favolinks guardados</p>
            <p class="summary-value"><?= $resumen['usuarios_4_10']['usuarios'] ?></p>
            <p class="summary-meta">Links del segmento: <?= $resumen['usuarios_4_10']['links'] ?></p>
            <p class="summary-meta">% del total links: <?= number_format($segmentoPorcentajeLinks['usuarios_4_10'], 2, ',', '.') ?>%</p>
        </article>
        <article class="summary-card">
            <p class="summary-title">Usuarios con 11-25 favolinks guardados</p>
            <p class="summary-value"><?= $resumen['usuarios_11_25']['usuarios'] ?></p>
            <p class="summary-meta">Links del segmento: <?= $resumen['usuarios_11_25']['links'] ?></p>
            <p class="summary-meta">% del total links: <?= number_format($segmentoPorcentajeLinks['usuarios_11_25'], 2, ',', '.') ?>%</p>
        </article>
        <article class="summary-card">
            <p class="summary-title">Usuarios con 26-50 favolinks guardados</p>
            <p class="summary-value"><?= $resumen['usuarios_26_50']['usuarios'] ?></p>
            <p class="summary-meta">Links del segmento: <?= $resumen['usuarios_26_50']['links'] ?></p>
            <p class="summary-meta">% del total links: <?= number_format($segmentoPorcentajeLinks['usuarios_26_50'], 2, ',', '.') ?>%</p>
        </article>
        <article class="summary-card">
            <p class="summary-title">Usuarios con 51-100 favolinks guardados</p>
            <p class="summary-value"><?= $resumen['usuarios_51_100']['usuarios'] ?></p>
            <p class="summary-meta">Links del segmento: <?= $resumen['usuarios_51_100']['links'] ?></p>
            <p class="summary-meta">% del total links: <?= number_format($segmentoPorcentajeLinks['usuarios_51_100'], 2, ',', '.') ?>%</p>
        </article>
        <article class="summary-card">
            <p class="summary-title">Usuarios con +100 favolinks guardados</p>
            <p class="summary-value"><?= $resumen['usuarios_mas_100']['usuarios'] ?></p>
            <p class="summary-meta">Links del segmento: <?= $resumen['usuarios_mas_100']['links'] ?></p>
            <p class="summary-meta">% del total links: <?= number_format($segmentoPorcentajeLinks['usuarios_mas_100'], 2, ',', '.') ?>%</p>
        </article>
    </div>

    <div class="layout-grid">
        <div class="list-column">
    <div class="table-container">
        <?php if (!$statsRows): ?>
            <div class="empty">No hay datos para mostrar.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th><button type="button" class="sort-btn" data-key="id">ID</button></th>
                        <th><button type="button" class="sort-btn" data-key="fecha_creacion">Fecha de creación</button></th>
                        <th><button type="button" class="sort-btn" data-key="cantidad_categorias">Cantidad de categorías</button></th>
                        <th><button type="button" class="sort-btn" data-key="cantidad_favolinks_guardados">Cantidad de favolinks guardados</button></th>
                        <th><button type="button" class="sort-btn" data-key="fecha_primer_favolink">Fecha del primer favolink</button></th>
                        <th><button type="button" class="sort-btn" data-key="fecha_ultimo_favolink">Fecha del último favolink</button></th>
                    </tr>
                </thead>
                <tbody id="stats-body">
                <?php foreach ($statsRows as $row): ?>
                    <tr>
                        <td data-label="ID" data-sort="<?= (int) $row['id'] ?>"><?= (int) $row['id'] ?></td>
                        <td data-label="Fecha de creación" data-sort="<?= htmlspecialchars((string) ($row['fecha_creacion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= formatDate($row['fecha_creacion'] ?? null) ?></td>
                        <td data-label="Cantidad de categorías" data-sort="<?= (int) $row['cantidad_categorias'] ?>"><?= (int) $row['cantidad_categorias'] ?></td>
                        <td data-label="Cantidad de favolinks guardados" data-sort="<?= (int) $row['cantidad_favolinks_guardados'] ?>"><?= (int) $row['cantidad_favolinks_guardados'] ?></td>
                        <td data-label="Fecha del primer favolink" data-sort="<?= htmlspecialchars((string) ($row['fecha_primer_favolink'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= formatDate($row['fecha_primer_favolink'] ?? null) ?></td>
                        <td data-label="Fecha del último favolink" data-sort="<?= htmlspecialchars((string) ($row['fecha_ultimo_favolink'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= formatDate($row['fecha_ultimo_favolink'] ?? null) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
        </div>

        <aside class="sidebar">
            <h2>Distribución de links por segmento</h2>
            <div class="pie-chart" style="--pie-background: <?= htmlspecialchars($pieBackground, ENT_QUOTES, 'UTF-8') ?>;"></div>
            <ul class="legend">
                <?php foreach ($legendRows as $legend): ?>
                    <li>
                        <span class="legend-label"><span class="legend-dot" style="--dot-color: <?= htmlspecialchars($legend['color'], ENT_QUOTES, 'UTF-8') ?>;"></span><?= htmlspecialchars($legend['label'], ENT_QUOTES, 'UTF-8') ?></span>
                        <strong><?= number_format((float) $legend['pct'], 2, ',', '.') ?>%</strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        </aside>
    </div>
</div>
<script>
(function () {
    const tbody = document.getElementById('stats-body');
    const buttons = document.querySelectorAll('.sort-btn');
    if (!tbody || !buttons.length) {
        return;
    }

    const keyIndex = {
        id: 0,
        fecha_creacion: 1,
        cantidad_categorias: 2,
        cantidad_favolinks_guardados: 3,
        fecha_primer_favolink: 4,
        fecha_ultimo_favolink: 5
    };

    function valueFor(cellText, cellSort) {
        const value = cellSort !== '' ? cellSort : cellText.trim();
        if (value === '' || value === '-') {
            return null;
        }
        if (/^\d+$/.test(value)) {
            return Number(value);
        }
        return value;
    }

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            const key = button.dataset.key;
            const col = keyIndex[key];
            if (typeof col === 'undefined') {
                return;
            }

            const nextOrder = button.dataset.order === 'asc' ? 'desc' : 'asc';
            buttons.forEach((b) => {
                if (b !== button) {
                    b.removeAttribute('data-order');
                }
            });
            button.dataset.order = nextOrder;

            const rows = Array.from(tbody.querySelectorAll('tr'));
            rows.sort((a, b) => {
                const aCell = a.children[col];
                const bCell = b.children[col];
                const aVal = valueFor(aCell?.textContent ?? '', aCell?.dataset.sort ?? '');
                const bVal = valueFor(bCell?.textContent ?? '', bCell?.dataset.sort ?? '');

                if (aVal === null && bVal === null) return 0;
                if (aVal === null) return 1;
                if (bVal === null) return -1;

                let cmp = 0;
                if (typeof aVal === 'number' && typeof bVal === 'number') {
                    cmp = aVal - bVal;
                } else {
                    cmp = String(aVal).localeCompare(String(bVal), 'es');
                }

                return nextOrder === 'asc' ? cmp : -cmp;
            });

            rows.forEach((row) => tbody.appendChild(row));
        });
    });
})();
</script>
</body>
</html>
