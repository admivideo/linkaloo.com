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

/** @return array{key:string,color:string,sort:int,days_range:string} */
function lastSavedLinkStatus(?string $value): array
{
    if (!$value) {
        return ['key' => 'blue', 'color' => '#3b82f6', 'sort' => 9999, 'days_range' => 'Sin links guardados'];
    }

    try {
        $lastSavedLink = new DateTimeImmutable($value);
        $now = new DateTimeImmutable('now');
        $daysSinceLastSavedLink = (int) floor(($now->getTimestamp() - $lastSavedLink->getTimestamp()) / 86400);
        $daysSinceLastSavedLink = max(0, $daysSinceLastSavedLink);

        if ($daysSinceLastSavedLink <= 3) {
            return ['key' => 'green', 'color' => '#22c55e', 'sort' => $daysSinceLastSavedLink, 'days_range' => '0-3 días'];
        }

        if ($daysSinceLastSavedLink <= 7) {
            return ['key' => 'orange', 'color' => '#f59e0b', 'sort' => $daysSinceLastSavedLink, 'days_range' => '4-7 días'];
        }

        return ['key' => 'red', 'color' => '#ef4444', 'sort' => $daysSinceLastSavedLink, 'days_range' => '+8 días'];
    } catch (Exception $e) {
        return ['key' => 'blue', 'color' => '#3b82f6', 'sort' => 9999, 'days_range' => 'Sin links guardados'];
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
$userEmailColumn = pickColumn($pdo, 'usuarios', ['email', 'correo', 'mail']);
$lastAccessColumn = pickColumn($pdo, 'usuarios', ['ultimo_acceso', 'last_access', 'ultimo_login', 'last_login_at']);
$linkCreatedColumn = pickColumn($pdo, 'links', ['creado_en', 'created_at', 'fecha_creacion']);

$userDateSelect = $userCreatedColumn ? "u.`{$userCreatedColumn}`" : 'NULL';
$lastAccessSelect = $lastAccessColumn ? "u.`{$lastAccessColumn}`" : 'NULL';
$linkMinMaxSelect = $linkCreatedColumn
    ? "MIN(`{$linkCreatedColumn}`) AS fecha_primer_favolink, MAX(`{$linkCreatedColumn}`) AS fecha_ultimo_favolink"
    : 'NULL AS fecha_primer_favolink, NULL AS fecha_ultimo_favolink';

$statsSql = "
    SELECT
        u.id,
        {$userDateSelect} AS fecha_creacion,
        {$lastAccessSelect} AS fecha_ultimo_acceso,
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

$itemsPerPage = 500;
$requestedPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$currentPage = is_int($requestedPage) && $requestedPage > 0 ? $requestedPage : 1;

$totalLinks = 0;
$accessStatusSummary = [
    'green' => ['color' => '#22c55e', 'days_range' => '0-3 días', 'usuarios' => 0],
    'orange' => ['color' => '#f59e0b', 'days_range' => '4-7 días', 'usuarios' => 0],
    'red' => ['color' => '#ef4444', 'days_range' => '+8 días', 'usuarios' => 0],
    'blue' => ['color' => '#3b82f6', 'days_range' => 'Sin links guardados', 'usuarios' => 0],
];
foreach ($statsRows as &$row) {
    $linksGuardados = (int) ($row['cantidad_favolinks_guardados'] ?? 0);
    $totalLinks += $linksGuardados;

    $segmentKey = segmentKeyForLinks($linksGuardados, $segments);
    $resumen[$segmentKey]['usuarios']++;
    $resumen[$segmentKey]['links'] += $linksGuardados;

    $savedLinkStatus = lastSavedLinkStatus($row['fecha_ultimo_favolink'] ?? null);
    $row['estado_ultimo_link'] = $savedLinkStatus;
    if (isset($accessStatusSummary[$savedLinkStatus['key']])) {
        $accessStatusSummary[$savedLinkStatus['key']]['usuarios']++;
    }
}
unset($row);

$totalUsuarios = count($statsRows);
$totalPages = max(1, (int) ceil($totalUsuarios / $itemsPerPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $itemsPerPage;
$paginatedStatsRows = array_slice($statsRows, $offset, $itemsPerPage);

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
    ['key' => 'fecha_creacion', 'label' => 'Registro'],
    ['key' => 'fecha_ultimo_acceso', 'label' => 'Último acceso'],
    ['key' => 'estado_ultimo_link', 'label' => 'Estado último link'],
    ['key' => 'cantidad_categorias', 'label' => 'Categorías'],
    ['key' => 'cantidad_favolinks_guardados', 'label' => 'Favolinks'],
    ['key' => 'fecha_primer_favolink', 'label' => 'Primer favolink'],
    ['key' => 'fecha_ultimo_favolink', 'label' => 'Último favolink'],
];

/** @return array<int, int|string> */
function paginationItems(int $currentPage, int $totalPages): array
{
    if ($totalPages <= 7) {
        return range(1, $totalPages);
    }

    $pages = [1];
    $windowStart = max(2, $currentPage - 1);
    $windowEnd = min($totalPages - 1, $currentPage + 1);

    if ($windowStart > 2) {
        $pages[] = '...';
    }

    for ($page = $windowStart; $page <= $windowEnd; $page++) {
        $pages[] = $page;
    }

    if ($windowEnd < $totalPages - 1) {
        $pages[] = '...';
    }

    $pages[] = $totalPages;

    return $pages;
}

/**
 * Devuelve un valor plano para exportación sin comillas de encapsulado.
 */
function sanitizePlainExportValue(mixed $value): string
{
    $text = (string) ($value ?? '');

    // Fuerza UTF-8 para evitar caracteres inválidos en la exportación.
    if ($text !== '' && !mb_check_encoding($text, 'UTF-8')) {
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8, ISO-8859-1, WINDOWS-1252');
    }

    // Evita saltos de línea/tabs que rompen el formato del archivo.
    $text = str_replace(["\r", "\n", "\t"], ' ', $text);

    // Si el valor viene con comillas envolventes, las quita.
    if (strlen($text) >= 2 && $text[0] === '"' && substr($text, -1) === '"') {
        $text = substr($text, 1, -1);
    }

    return trim($text);
}

/**
 * Escribe una fila CSV sin usar encapsulado por comillas.
 */
function writePlainCsvRow($output, array $row): void
{
    $cleanValues = array_map(static fn($value): string => sanitizePlainExportValue($value), $row);
    fwrite($output, implode(',', $cleanValues) . "\n");
}

$pagination = paginationItems($currentPage, $totalPages);

if (
    isset($_GET['export_welcome_csv'])
    || isset($_GET['export_d0_csv'])
    || isset($_GET['export_d1_csv'])
    || isset($_GET['export_d3_csv'])
    || isset($_GET['export_d7_csv'])
    || isset($_GET['export_d14_csv'])
    || isset($_GET['export_reactivate_csv'])
) {
    if (!$userCreatedColumn) {
        header('HTTP/1.1 500 Internal Server Error');
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'No se pudo exportar el CSV: no se detectó la columna de fecha de creación de usuarios.';
        exit;
    }

    $csvIdSelect = 'u.id';
    $csvEmailSelect = $userEmailColumn ? "u.`{$userEmailColumn}`" : "''";
    $csvCreatedSelect = "u.`{$userCreatedColumn}`";

    if (isset($_GET['export_reactivate_csv'])) {
        $registrationDateCondition = 'DATE(' . $csvCreatedSelect . ') < DATE_SUB(CURDATE(), INTERVAL 14 DAY)';
    } elseif (isset($_GET['export_d14_csv'])) {
        $registrationDateCondition = 'DATE(' . $csvCreatedSelect . ') BETWEEN DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND DATE_SUB(CURDATE(), INTERVAL 8 DAY)';
    } elseif (isset($_GET['export_d7_csv'])) {
        $registrationDateCondition = 'DATE(' . $csvCreatedSelect . ') BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND DATE_SUB(CURDATE(), INTERVAL 3 DAY)';
    } elseif (isset($_GET['export_d3_csv'])) {
        $registrationDateCondition = 'DATE(' . $csvCreatedSelect . ') = DATE_SUB(CURDATE(), INTERVAL 3 DAY)';
    } elseif (isset($_GET['export_d1_csv'])) {
        $registrationDateCondition = 'DATE(' . $csvCreatedSelect . ') = DATE_SUB(CURDATE(), INTERVAL 1 DAY)';
    } else {
        $registrationDateCondition = 'DATE(' . $csvCreatedSelect . ') = CURDATE()';
    }

    $welcomeUsersSql = "
        SELECT
            {$csvIdSelect} AS id,
            {$csvEmailSelect} AS email
        FROM usuarios u
        LEFT JOIN links l ON l.usuario_id = u.id
        WHERE {$registrationDateCondition}
        GROUP BY u.id, email
        HAVING COUNT(l.id) = 0
        ORDER BY u.id ASC
    ";

    $welcomeUsers = $pdo->query($welcomeUsersSql)->fetchAll(PDO::FETCH_ASSOC);

    $isReactivationExport = isset($_GET['export_reactivate_csv']);
    $isD14Export = isset($_GET['export_d14_csv']);
    $isD7Export = isset($_GET['export_d7_csv']);
    $isD3Export = isset($_GET['export_d3_csv']);
    $isD1Export = isset($_GET['export_d1_csv']);
    $targetDate = new DateTimeImmutable($isReactivationExport
        ? 'today -14 days'
        : ($isD14Export ? 'today -14 days' : ($isD7Export ? 'today -7 days' : ($isD3Export ? 'today -3 days' : ($isD1Export ? 'today -1 day' : 'today'))))
    );
    $filenamePrefix = $isReactivationExport
        ? 'REACTIVAR_'
        : ($isD14Export ? 'D14_' : ($isD7Export ? 'D7_' : ($isD3Export ? 'D3_' : ($isD1Export ? 'D1_' : 'D0_'))));
    $filename = $filenamePrefix . $targetDate->format('Y-m-d') . '.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'wb');
    if ($output === false) {
        header('HTTP/1.1 500 Internal Server Error');
        echo 'No se pudo generar el archivo CSV.';
        exit;
    }

    fwrite($output, "\xEF\xBB\xBF");
    writePlainCsvRow($output, ['id', 'email']);

    foreach ($welcomeUsers as $user) {
        $row = [
            (string) ((int) ($user['id'] ?? 0)),
            (string) ($user['email'] ?? ''),
        ];

        writePlainCsvRow($output, $row);
    }

    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Linkaloo estadísticas</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(180deg, #f7fbff 0%, #edf5ff 100%);
            color: #173b74;
        }
        .wrapper { width: min(1200px, 100% - 2rem); margin: 1.5rem auto; }
        h1 { margin: 0 0 1rem; font-size: clamp(1.2rem, 2.5vw, 1.8rem); }
        .header-row { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 0.75rem; margin-bottom: 1rem; }
        .header-row h1 { margin: 0; }
        .welcome-export-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #1f6ad4;
            border-radius: 9px;
            background: #1f6ad4;
            color: #fff;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            padding: 0.58rem 0.9rem;
            transition: background 0.2s ease, transform 0.2s ease;
        }
        .welcome-export-btn:hover { background: #1453af; transform: translateY(-1px); }

        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 0.8rem; margin: 0 0 1rem; }
        .summary-card, .sidebar, .table-container {
            border: 1px solid #d9e8ff;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 12px 28px rgba(39, 95, 180, 0.08);
        }
        .summary-card { padding: 0.75rem; }
        .summary-title { margin: 0; font-size: 0.83rem; color: #5585c5; }
        .summary-value { margin: 0.25rem 0 0; font-size: 1.35rem; font-weight: 700; color: #10428a; }
        .summary-meta { margin: 0.3rem 0 0; font-size: 0.85rem; color: #4c6998; line-height: 1.35; }

        .layout-grid { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 1rem; align-items: start; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 860px; }
        thead { background: #ecf4ff; }
        th, td { padding: 0.8rem; text-align: left; border-bottom: 1px solid #e2edff; font-size: 0.92rem; }
        tbody tr:hover { background: #f2f8ff; }

        th button.sort-btn { all: unset; cursor: pointer; display: inline-flex; align-items: center; gap: 0.35rem; font-weight: 700; width: 100%; }
        th button.sort-btn::after { content: '↕'; font-size: 0.75rem; opacity: 0.7; }
        th button.sort-btn[data-order="asc"]::after { content: '↑'; opacity: 1; }
        th button.sort-btn[data-order="desc"]::after { content: '↓'; opacity: 1; }

        .sidebar { padding: 1rem; position: sticky; top: 1rem; }
        .sidebar h2 { margin: 0 0 0.8rem; font-size: 1rem; color: #0f4a98; }
        .sidebar section + section { margin-top: 1rem; }
        .pie-chart { width: min(220px, 100%); aspect-ratio: 1/1; border-radius: 50%; margin: 0 auto 1rem; background: var(--pie-background); border: 1px solid #d9e8ff; }
        .legend { list-style: none; padding: 0; margin: 0; display: grid; gap: 0.35rem; }
        .legend li { display: flex; justify-content: space-between; gap: 0.5rem; font-size: 0.87rem; }
        .legend-label { display: inline-flex; align-items: center; gap: 0.4rem; }
        .legend-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--dot-color); }
        .status-summary-box { border: 1px solid #d9e8ff; border-radius: 10px; padding: 0.8rem; background: #f7fbff; }
        .status-summary-list { list-style: none; padding: 0; margin: 0; display: grid; gap: 0.45rem; }
        .status-summary-list li { display: flex; justify-content: space-between; gap: 0.6rem; font-size: 0.87rem; }
        .status-summary-meta { color: #42689d; }

        .access-status { display: inline-flex; align-items: center; justify-content: center; width: 100%; }
        .access-status-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--status-color); flex-shrink: 0; }

        .empty { padding: 1rem; }

        .pagination-bar { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 0.75rem; padding: 0.75rem 0.85rem; border-bottom: 1px solid #e2edff; }
        .pagination-info { margin: 0; font-size: 0.86rem; color: #42689d; }
        .pagination-nav { display: inline-flex; align-items: center; flex-wrap: wrap; gap: 0.35rem; }
        .pagination-link,
        .pagination-current,
        .pagination-ellipsis { min-width: 34px; height: 34px; display: inline-flex; justify-content: center; align-items: center; border-radius: 8px; border: 1px solid #d9e8ff; font-size: 0.84rem; }
        .pagination-link { text-decoration: none; color: #0f4a98; background: #fff; }
        .pagination-link:hover { background: #ecf4ff; }
        .pagination-current { color: #ffffff; border-color: #1f6ad4; background: #1f6ad4; font-weight: 700; }
        .pagination-ellipsis { border-color: transparent; color: #6b89b7; background: transparent; }

        @media (max-width: 980px) {
            .layout-grid { grid-template-columns: 1fr; }
            .sidebar { position: static; }
        }

        @media (max-width: 760px) {
            .table-container { border: none; background: transparent; box-shadow: none; }
            table, thead, tbody, tr, th, td { display: block; }
            thead { display: none; }
            table { min-width: 0; }
            tr { margin-bottom: 0.9rem; border: 1px solid #d9e8ff; border-radius: 12px; background: #ffffff; padding: 0.4rem 0; }
            td { border: none; padding: 0.55rem 0.85rem; display: flex; justify-content: space-between; gap: 0.75rem; }
            td::before { content: attr(data-label); font-weight: 700; color: #4f7fbe; }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header-row">
        <h1>Estadísticas de usuarios de Linkaloo</h1>
        <a class="welcome-export-btn" href="?export_d0_csv=1" aria-label="Descargar CSV de usuarios D0 (sin favolinks)">⬇️ D0</a>
        <a class="welcome-export-btn" href="?export_d1_csv=1" aria-label="Descargar CSV de usuarios D1 (sin favolinks)">⬇️ D1</a>
        <a class="welcome-export-btn" href="?export_d3_csv=1" aria-label="Descargar CSV de usuarios D3 (sin favolinks)">⬇️ D3</a>
        <a class="welcome-export-btn" href="?export_d7_csv=1" aria-label="Descargar CSV de usuarios D7 (sin favolinks)">⬇️ D7</a>
        <a class="welcome-export-btn" href="?export_d14_csv=1" aria-label="Descargar CSV de usuarios D14 (registrados entre hace 8 y 14 días, sin favolinks)">⬇️ D14</a>
        <a class="welcome-export-btn" href="?export_reactivate_csv=1" aria-label="Descargar CSV de reactivación (usuarios con más de 14 días y sin favolinks)">🔁 Reactivar</a>
    </div>

    <div class="summary-grid">
        <?php foreach ($summaryCards as $card): ?>
            <article class="summary-card">
                <p class="summary-title"><?= htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="summary-value"><?= (int) $card['usuarios'] ?></p>
                <p class="summary-meta">Links: <?= (int) $card['links'] ?></p>
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
                    <div class="pagination-bar">
                        <p class="pagination-info">
                            Mostrando <?= $offset + 1 ?>-<?= $offset + count($paginatedStatsRows) ?> de <?= $totalUsuarios ?> usuarios (página <?= $currentPage ?> de <?= $totalPages ?>)
                        </p>
                        <nav class="pagination-nav" aria-label="Paginación de usuarios">
                            <?php if ($currentPage > 1): ?>
                                <a class="pagination-link" href="?page=<?= $currentPage - 1 ?>" aria-label="Página anterior">‹</a>
                            <?php endif; ?>

                            <?php foreach ($pagination as $pageItem): ?>
                                <?php if ($pageItem === '...'): ?>
                                    <span class="pagination-ellipsis" aria-hidden="true">…</span>
                                <?php elseif ((int) $pageItem === $currentPage): ?>
                                    <span class="pagination-current" aria-current="page"><?= (int) $pageItem ?></span>
                                <?php else: ?>
                                    <a class="pagination-link" href="?page=<?= (int) $pageItem ?>"><?= (int) $pageItem ?></a>
                                <?php endif; ?>
                            <?php endforeach; ?>

                            <?php if ($currentPage < $totalPages): ?>
                                <a class="pagination-link" href="?page=<?= $currentPage + 1 ?>" aria-label="Página siguiente">›</a>
                            <?php endif; ?>
                        </nav>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <?php foreach ($tableHeaders as $header): ?>
                                    <th><button type="button" class="sort-btn" data-key="<?= htmlspecialchars($header['key'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($header['label'], ENT_QUOTES, 'UTF-8') ?></button></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody id="stats-body">
                        <?php foreach ($paginatedStatsRows as $row): ?>
                            <?php $savedLinkStatus = $row['estado_ultimo_link']; ?>
                            <tr>
                                <td data-label="ID" data-sort="<?= (int) $row['id'] ?>"><?= (int) $row['id'] ?></td>
                                <td data-label="Registro" data-sort="<?= htmlspecialchars((string) ($row['fecha_creacion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= formatDate($row['fecha_creacion'] ?? null) ?></td>
                                <td data-label="Último acceso" data-sort="<?= htmlspecialchars((string) ($row['fecha_ultimo_acceso'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= formatDate($row['fecha_ultimo_acceso'] ?? null) ?></td>
                                <td data-label="Estado último link" data-sort="<?= (int) $savedLinkStatus['sort'] ?>"><span class="access-status"><span class="access-status-dot" style="--status-color: <?= htmlspecialchars($savedLinkStatus['color'], ENT_QUOTES, 'UTF-8') ?>;" title="<?= htmlspecialchars($savedLinkStatus['days_range'], ENT_QUOTES, 'UTF-8') ?>"></span></span></td>
                                <td data-label="Categorías" data-sort="<?= (int) $row['cantidad_categorias'] ?>"><?= (int) $row['cantidad_categorias'] ?></td>
                                <td data-label="Favolinks" data-sort="<?= (int) $row['cantidad_favolinks_guardados'] ?>"><?= (int) $row['cantidad_favolinks_guardados'] ?></td>
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
            <section>
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
            </section>

            <section class="status-summary-box">
                <h2>Resumen de colores (último link)</h2>
                <ul class="status-summary-list">
                    <?php foreach ($accessStatusSummary as $status): ?>
                        <li>
                            <span class="legend-label"><span class="legend-dot" style="--dot-color: <?= htmlspecialchars($status['color'], ENT_QUOTES, 'UTF-8') ?>;"></span><span class="status-summary-meta"><?= htmlspecialchars($status['days_range'], ENT_QUOTES, 'UTF-8') ?></span></span>
                            <strong><?= (int) $status['usuarios'] ?> usuarios</strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
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
        fecha_ultimo_acceso: 2,
        estado_ultimo_link: 3,
        cantidad_categorias: 4,
        cantidad_favolinks_guardados: 5,
        fecha_primer_favolink: 6,
        fecha_ultimo_favolink: 7
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
