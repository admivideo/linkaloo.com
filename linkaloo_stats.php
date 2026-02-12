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
        return $date->format('Y-m-d H:i:s');
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

        .empty {
            padding: 1rem;
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

    <div class="table-container">
        <?php if (!$statsRows): ?>
            <div class="empty">No hay datos para mostrar.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha de creación</th>
                        <th>Cantidad de categorías</th>
                        <th>Cantidad de favolinks guardados</th>
                        <th>Fecha del primer favolink</th>
                        <th>Fecha del último favolink</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($statsRows as $row): ?>
                    <tr>
                        <td data-label="ID"><?= (int) $row['id'] ?></td>
                        <td data-label="Fecha de creación"><?= formatDate($row['fecha_creacion'] ?? null) ?></td>
                        <td data-label="Cantidad de categorías"><?= (int) $row['cantidad_categorias'] ?></td>
                        <td data-label="Cantidad de favolinks guardados"><?= (int) $row['cantidad_favolinks_guardados'] ?></td>
                        <td data-label="Fecha del primer favolink"><?= formatDate($row['fecha_primer_favolink'] ?? null) ?></td>
                        <td data-label="Fecha del último favolink"><?= formatDate($row['fecha_ultimo_favolink'] ?? null) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
