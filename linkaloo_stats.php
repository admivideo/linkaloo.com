<?php
require 'config.php';

const STATS_USER = '4besdev616339117*-$%&';
const STATS_PASS = 'Fwc63GJuMKXybyBKAkepQPgj7p*-';

/** @return array<int, array{key:string,title:string,legend:string,color:string,min:int,max:?int}> */
function statsSegments(): array
{
    return [
        ['key' => 'usuarios_sin_links', 'title' => 'Users con 0 links', 'legend' => '0 links', 'color' => '#6b7280', 'min' => 0, 'max' => 0],
        ['key' => 'usuarios_1_3', 'title' => 'Users con 1-3 favolinks', 'legend' => '1-3', 'color' => '#22c55e', 'min' => 1, 'max' => 3],
        ['key' => 'usuarios_4_10', 'title' => 'Users con 4-10 favolinks', 'legend' => '4-10', 'color' => '#3b82f6', 'min' => 4, 'max' => 10],
        ['key' => 'usuarios_11_25', 'title' => 'Users con 11-25 favolinks', 'legend' => '11-25', 'color' => '#a855f7', 'min' => 11, 'max' => 25],
        ['key' => 'usuarios_26_50', 'title' => 'Users con 26-50 favolinks', 'legend' => '26-50', 'color' => '#f59e0b', 'min' => 26, 'max' => 50],
        ['key' => 'usuarios_51_100', 'title' => 'Users con 51-100 favolinks', 'legend' => '51-100', 'color' => '#ef4444', 'min' => 51, 'max' => 100],
        ['key' => 'usuarios_mas_100', 'title' => 'Users con +100 favolinks', 'legend' => '+100', 'color' => '#14b8a6', 'min' => 101, 'max' => null],
    ];
}

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
        return (new DateTimeImmutable($value))->format('Y-m-d');
    } catch (Exception $e) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

/** @param array<int, array{key:string,title:string,legend:string,color:string,min:int,max:?int}> $segments */
function segmentKeyForLinks(int $linksCount, array $segments): string
{
    foreach ($segments as $segment) {
        $min = $segment['min'];
        $max = $segment['max'];
        if ($linksCount >= $min && ($max === null || $linksCount <= $max)) {
            return $segment['key'];
        }
    }

    return 'usuarios_mas_100';
}

requireStatsAuth();

$segments = statsSegments();
$segmentTitles = [];
$segmentColors = [];
$segmentLegends = [];
$resumen = [];
foreach ($segments as $segment) {
    $key = $segment['key'];
    $segmentTitles[$key] = $segment['title'];
    $segmentColors[$key] = $segment['color'];
    $segmentLegends[$key] = $segment['legend'];
    $resumen[$key] = ['usuarios' => 0, 'links' => 0];
}

$userCreatedColumn = pickColumn($pdo, 'usuarios', ['creado_en', 'created_at', 'fecha_creacion', 'registrado_en']);
$linkCreatedColumn = pickColumn($pdo, 'links', ['creado_en', 'created_at', 'fecha_creacion']);

$userDateSelect = $userCreatedColumn ? "u.`{$userCreatedColumn}`" : 'NULL';
$linkMinMaxSelect = $linkCreatedColumn
    ? "MIN(`{$linkCreatedColumn}`) AS fecha_primer_favolink, MAX(`{$linkCreatedColumn}`) AS fecha_ultimo_favolink"
    : 'NULL AS fecha_primer_favolink, NULL AS fecha_ultimo_favolink';

$statsSql = "
    SELECT
        u.id,
        {$userDateSelect} AS fecha_creacion,
        COALESCE(c.total, 0) AS cantidad_categorias,
        COALESCE(l.total, 0) AS cantidad_favolinks_guardados,
        l.fecha_primer_favolink,
        l.fecha_ultimo_favolink
    FROM usuarios u
    LEFT JOIN (
        SELECT usuario_id, COUNT(*) AS total
        FROM categorias
        GROUP BY usuario_id
    ) c ON c.usuario_id = u.id
    LEFT JOIN (
        SELECT usuario_id, COUNT(*) AS total, {$linkMinMaxSelect}
        FROM links
        GROUP BY usuario_id
    ) l ON l.usuario_id = u.id
    ORDER BY u.id ASC
";

$statsRows = $pdo->query($statsSql)->fetchAll();

$totalLinks = 0;
foreach ($statsRows as $row) {
    $linksGuardados = (int) ($row['cantidad_favolinks_guardados'] ?? 0);
    $totalLinks += $linksGuardados;

    $segmentKey = segmentKeyForLinks($linksGuardados, $segments);
    $resumen[$segmentKey]['usuarios']++;
    $resumen[$segmentKey]['links'] += $linksGuardados;
}

$totalUsuarios = count($statsRows);

$segmentoPorcentajeLinks = [];
$chartParts = [];
$legendRows = [];
$summaryCards = [
    ['title' => 'Total usuarios', 'usuarios' => $totalUsuarios, 'links' => $totalLinks, 'pct' => 100.0],
];

$acumulado = 0.0;
foreach ($segments as $segment) {
    $key = $segment['key'];
    $links = $resumen[$key]['links'];
    $pct = $totalLinks > 0 ? round(($links / $totalLinks) * 100, 2) : 0.0;
    $segmentoPorcentajeLinks[$key] = $pct;

    if ($pct > 0) {
        $inicio = $acumulado;
        $acumulado += $pct;
        $chartParts[] = sprintf('%s %.2f%% %.2f%%', $segmentColors[$key], $inicio, $acumulado);
    }

    $legendRows[] = [
        'label' => $segmentLegends[$key],
        'color' => $segmentColors[$key],
        'pct' => $pct,
    ];

    $summaryCards[] = [
        'title' => $segmentTitles[$key],
        'usuarios' => $resumen[$key]['usuarios'],
        'links' => $links,
        'pct' => $pct,
    ];
}

$pieBackground = $chartParts ? 'conic-gradient(' . implode(', ', $chartParts) . ')' : 'conic-gradient(#6b7280 0% 100%)';

$tableHeaders = [
    ['key' => 'id', 'label' => 'ID'],
    ['key' => 'fecha_creacion', 'label' => 'Fecha de creación'],
    ['key' => 'cantidad_categorias', 'label' => 'Cantidad de categorías'],
    ['key' => 'cantidad_favolinks_guardados', 'label' => 'Cantidad de favolinks guardados'],
    ['key' => 'fecha_primer_favolink', 'label' => 'Fecha del primer favolink'],
    ['key' => 'fecha_ultimo_favolink', 'label' => 'Fecha del último favolink'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Linkaloo estadísticas</title>
    <style>
        :root { color-scheme: dark; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #0f1224; color: #eff3ff; }
        .wrapper { width: min(1200px, 100% - 2rem); margin: 1.5rem auto; }
        h1 { margin: 0 0 1rem; font-size: clamp(1.2rem, 2.5vw, 1.8rem); }

        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 0.8rem; margin: 0 0 1rem; }
        .summary-card, .sidebar, .table-container { border: 1px solid rgba(255, 255, 255, 0.16); border-radius: 12px; background: rgba(255, 255, 255, 0.05); }
        .summary-card { padding: 0.75rem; }
        .summary-title { margin: 0; font-size: 0.83rem; color: #c8d1ff; }
        .summary-value { margin: 0.25rem 0 0; font-size: 1.35rem; font-weight: 700; }
        .summary-meta { margin: 0.3rem 0 0; font-size: 0.85rem; color: #d9e0ff; line-height: 1.35; }

        .layout-grid { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 1rem; align-items: start; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 860px; }
        thead { background: rgba(255, 255, 255, 0.12); }
        th, td { padding: 0.8rem; text-align: left; border-bottom: 1px solid rgba(255, 255, 255, 0.1); font-size: 0.92rem; }
        tbody tr:hover { background: rgba(255, 255, 255, 0.06); }

        th button.sort-btn { all: unset; cursor: pointer; display: inline-flex; align-items: center; gap: 0.35rem; font-weight: 700; width: 100%; }
        th button.sort-btn::after { content: '↕'; font-size: 0.75rem; opacity: 0.7; }
        th button.sort-btn[data-order="asc"]::after { content: '↑'; opacity: 1; }
        th button.sort-btn[data-order="desc"]::after { content: '↓'; opacity: 1; }

        .sidebar { padding: 1rem; position: sticky; top: 1rem; }
        .sidebar h2 { margin: 0 0 0.8rem; font-size: 1rem; }
        .pie-chart { width: min(220px, 100%); aspect-ratio: 1/1; border-radius: 50%; margin: 0 auto 1rem; background: var(--pie-background); border: 1px solid rgba(255,255,255,0.2); }
        .legend { list-style: none; padding: 0; margin: 0; display: grid; gap: 0.35rem; }
        .legend li { display: flex; justify-content: space-between; gap: 0.5rem; font-size: 0.87rem; }
        .legend-label { display: inline-flex; align-items: center; gap: 0.4rem; }
        .legend-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--dot-color); }

        .empty { padding: 1rem; }

        @media (max-width: 980px) {
            .layout-grid { grid-template-columns: 1fr; }
            .sidebar { position: static; }
        }

        @media (max-width: 760px) {
            .table-container { border: none; background: transparent; }
            table, thead, tbody, tr, th, td { display: block; }
            thead { display: none; }
            table { min-width: 0; }
            tr { margin-bottom: 0.9rem; border: 1px solid rgba(255, 255, 255, 0.18); border-radius: 12px; background: rgba(255, 255, 255, 0.05); padding: 0.4rem 0; }
            td { border: none; padding: 0.55rem 0.85rem; display: flex; justify-content: space-between; gap: 0.75rem; }
            td::before { content: attr(data-label); font-weight: 700; color: #95a4ff; }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <h1>Estadísticas de usuarios de Linkaloo</h1>

    <div class="summary-grid">
        <?php foreach ($summaryCards as $card): ?>
            <article class="summary-card">
                <p class="summary-title"><?= htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="summary-value"><?= (int) $card['usuarios'] ?></p>
                <p class="summary-meta">Links del segmento: <?= (int) $card['links'] ?></p>
                <p class="summary-meta">% del total links: <?= number_format((float) $card['pct'], 2, ',', '.') ?>%</p>
            </article>
        <?php endforeach; ?>
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
                                <?php foreach ($tableHeaders as $header): ?>
                                    <th><button type="button" class="sort-btn" data-key="<?= htmlspecialchars($header['key'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($header['label'], ENT_QUOTES, 'UTF-8') ?></button></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody id="stats-body">
                        <?php foreach ($statsRows as $row): ?>
                            <tr>
                                <td data-label="ID" data-sort="<?= (int) $row['id'] ?>"><?= (int) $row['id'] ?></td>
                                <td data-label="Registro" data-sort="<?= htmlspecialchars((string) ($row['fecha_creacion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= formatDate($row['fecha_creacion'] ?? null) ?></td>
                                <td data-label="Categorías" data-sort="<?= (int) $row['cantidad_categorias'] ?>"><?= (int) $row['cantidad_categorias'] ?></td>
                                <td data-label="Favolinks guardados" data-sort="<?= (int) $row['cantidad_favolinks_guardados'] ?>"><?= (int) $row['cantidad_favolinks_guardados'] ?></td>
                                <td data-label="Primer favolink" data-sort="<?= htmlspecialchars((string) ($row['fecha_primer_favolink'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= formatDate($row['fecha_primer_favolink'] ?? null) ?></td>
                                <td data-label="Último favolink" data-sort="<?= htmlspecialchars((string) ($row['fecha_ultimo_favolink'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= formatDate($row['fecha_ultimo_favolink'] ?? null) ?></td>
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
        if (value === '' || value === '-') return null;
        if (/^\d+$/.test(value)) return Number(value);
        return value;
    }

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            const col = keyIndex[button.dataset.key];
            if (typeof col === 'undefined') return;

            const nextOrder = button.dataset.order === 'asc' ? 'desc' : 'asc';
            buttons.forEach((b) => { if (b !== button) b.removeAttribute('data-order'); });
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

                const cmp = (typeof aVal === 'number' && typeof bVal === 'number')
                    ? aVal - bVal
                    : String(aVal).localeCompare(String(bVal), 'es');
                return nextOrder === 'asc' ? cmp : -cmp;
            });

            rows.forEach((row) => tbody.appendChild(row));
        });
    });
})();
</script>
</body>
</html>