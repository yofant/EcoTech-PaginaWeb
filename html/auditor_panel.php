<?php
session_start();

if (empty($_SESSION['usuario']['correo'])) {
    header('Location: login_user.php?status=session_expired');
    exit();
}

require_once __DIR__ . '/../php/conexion.php';

$email = (string) $_SESSION['usuario']['correo'];
$stmtUser = $conn->prepare("
    SELECT usuario_id, nombre, apellido, rol, activo
    FROM `Usuarios`
    WHERE email = ?
    LIMIT 1
");
if (!$stmtUser) {
    error_log('Auditor portal user query prepare failed: ' . $conn->error);
    http_response_code(500);
    exit('No se pudo cargar el panel de auditoría.');
}
$stmtUser->bind_param('s', $email);
if (!$stmtUser->execute()) {
    error_log('Auditor portal user query failed: ' . $stmtUser->error);
    http_response_code(500);
    exit('No se pudo cargar el panel de auditoría.');
}
$auditor = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();

if (!$auditor || (int) $auditor['activo'] !== 1) {
    session_destroy();
    header('Location: login_user.php?status=account_inactive');
    exit();
}
if (strcasecmp((string) $auditor['rol'], 'Auditor') !== 0) {
    header('Location: index.php');
    exit();
}

$auditColumns = [];
$columnResult = $conn->query("SHOW COLUMNS FROM `Auditoria`");
if (!$columnResult) {
    error_log('Auditor schema check failed: ' . $conn->error);
    http_response_code(500);
    exit('No se pudo verificar la configuración del registro de auditoría.');
}
while ($column = $columnResult->fetch_assoc()) {
    $auditColumns[$column['Field']] = true;
}
if (!isset($auditColumns['valores_anteriores'], $auditColumns['valores_nuevos'])) {
    http_response_code(503);
    exit('Falta aplicar la migración db/auditoria_schema.sql para habilitar el historial completo.');
}

function auditorEscape(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function auditorValidDate(string $value): bool
{
    if ($value === '') {
        return true;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}

function auditorFormatSnapshot(?string $snapshot): ?string
{
    if ($snapshot === null || $snapshot === '') {
        return null;
    }

    $decoded = json_decode($snapshot, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return $snapshot;
    }

    return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

$search = trim((string) ($_GET['q'] ?? ''));
$tableFilter = trim((string) ($_GET['tabla'] ?? ''));
$operationFilter = trim((string) ($_GET['operacion'] ?? ''));
$fromDate = trim((string) ($_GET['desde'] ?? ''));
$toDate = trim((string) ($_GET['hasta'] ?? ''));
$page = filter_var($_GET['pagina'] ?? 1, FILTER_VALIDATE_INT);
$page = $page && $page > 0 ? $page : 1;

if (
    mb_strlen($search, 'UTF-8') > 100 ||
    !auditorValidDate($fromDate) ||
    !auditorValidDate($toDate) ||
    ($fromDate !== '' && $toDate !== '' && $fromDate > $toDate)
) {
    http_response_code(400);
    exit('Los filtros ingresados no son válidos.');
}

$tables = [];
$operations = [];
$resultFilters = $conn->query("
    SELECT DISTINCT tabla_afectada, operacion
    FROM `Auditoria`
    ORDER BY tabla_afectada, operacion
");
if (!$resultFilters) {
    error_log('Auditor filter options query failed: ' . $conn->error);
    http_response_code(500);
    exit('No se pudieron cargar los filtros de auditoría.');
}
while ($row = $resultFilters->fetch_assoc()) {
    $tables[$row['tabla_afectada']] = $row['tabla_afectada'];
    $operations[$row['operacion']] = $row['operacion'];
}

if ($tableFilter !== '' && !isset($tables[$tableFilter])) {
    $tableFilter = '';
}
if ($operationFilter !== '' && !isset($operations[$operationFilter])) {
    $operationFilter = '';
}

$summary = ['total' => 0, 'today' => 0, 'tables' => count($tables)];
$resultSummary = $conn->query("
    SELECT COUNT(*) AS total,
           COALESCE(SUM(fecha >= CURRENT_DATE AND fecha < CURRENT_DATE + INTERVAL 1 DAY), 0) AS today
    FROM `Auditoria`
");
if (!$resultSummary) {
    error_log('Auditor summary query failed: ' . $conn->error);
    http_response_code(500);
    exit('No se pudo cargar el resumen de auditoría.');
}
$summary = array_map('intval', $resultSummary->fetch_assoc());
$summary['tables'] = count($tables);

$portalTableResult = $conn->query("
    SELECT COUNT(*) AS total
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name IN ('Conversaciones', 'Mensajes', 'PuntosRecoleccion')
");
if (!$portalTableResult) {
    error_log('Auditor portal schema check failed: ' . $conn->error);
    http_response_code(500);
    exit('No se pudo verificar la configuración del portal.');
}
$hasPortalTables = (int) $portalTableResult->fetch_assoc()['total'] === 3;
$requiredTriggers = $hasPortalTables ? 39 : 27;
$triggerResult = $conn->query("
    SELECT COUNT(*) AS total
    FROM information_schema.triggers
    WHERE trigger_schema = DATABASE()
      AND LEFT(trigger_name, 8) = 'ecotech_'
");
if (!$triggerResult) {
    error_log('Auditor trigger check failed: ' . $conn->error);
    http_response_code(500);
    exit('No se pudo verificar la configuración de auditoría.');
}
$installedTriggers = (int) $triggerResult->fetch_assoc()['total'];
$auditSetupWarning = $installedTriggers < $requiredTriggers;

$where = "
    WHERE (? = '' OR tabla_afectada = ?)
      AND (? = '' OR operacion = ?)
      AND (? = '' OR fecha >= ?)
      AND (? = '' OR fecha < DATE_ADD(?, INTERVAL 1 DAY))
      AND (? = '' OR CAST(registro_id AS CHAR) LIKE ? OR COALESCE(usuario_sql, '') LIKE ?
           OR COALESCE(detalle, '') LIKE ? OR tabla_afectada LIKE ? OR operacion LIKE ?
           OR COALESCE(CAST(valores_anteriores AS CHAR), '') LIKE ?
           OR COALESCE(CAST(valores_nuevos AS CHAR), '') LIKE ?)
";
$searchLike = '%' . $search . '%';
$filterValues = [
    $tableFilter, $tableFilter,
    $operationFilter, $operationFilter,
    $fromDate, $fromDate,
    $toDate, $toDate,
    $search, $searchLike, $searchLike, $searchLike, $searchLike, $searchLike, $searchLike, $searchLike
];
$filterTypes = 'ssssssssssssssss';

$stmtCount = $conn->prepare("SELECT COUNT(*) AS total FROM `Auditoria` {$where}");
if (!$stmtCount) {
    error_log('Auditor count query prepare failed: ' . $conn->error);
    http_response_code(500);
    exit('No se pudo consultar el registro de auditoría.');
}
$stmtCount->bind_param($filterTypes, ...$filterValues);
if (!$stmtCount->execute()) {
    error_log('Auditor count query failed: ' . $stmtCount->error);
    http_response_code(500);
    exit('No se pudo consultar el registro de auditoría.');
}
$filteredTotal = (int) $stmtCount->get_result()->fetch_assoc()['total'];
$stmtCount->close();

$pageSize = 30;
$totalPages = max(1, (int) ceil($filteredTotal / $pageSize));
$page = min($page, $totalPages);
$offset = ($page - 1) * $pageSize;
$stmtRecords = $conn->prepare("
    SELECT auditoria_id, tabla_afectada, operacion, registro_id, usuario_sql, fecha, detalle,
           valores_anteriores, valores_nuevos
    FROM `Auditoria`
    {$where}
    ORDER BY fecha DESC, auditoria_id DESC
    LIMIT ? OFFSET ?
");
if (!$stmtRecords) {
    error_log('Auditor records query prepare failed: ' . $conn->error);
    http_response_code(500);
    exit('No se pudo cargar el registro de auditoría.');
}
$recordTypes = $filterTypes . 'ii';
$recordValues = [...$filterValues, $pageSize, $offset];
$stmtRecords->bind_param($recordTypes, ...$recordValues);
if (!$stmtRecords->execute()) {
    error_log('Auditor records query failed: ' . $stmtRecords->error);
    http_response_code(500);
    exit('No se pudo cargar el registro de auditoría.');
}
$auditRecords = [];
$resultRecords = $stmtRecords->get_result();
while ($record = $resultRecords->fetch_assoc()) {
    $record['valores_anteriores'] = auditorFormatSnapshot($record['valores_anteriores']);
    $record['valores_nuevos'] = auditorFormatSnapshot($record['valores_nuevos']);
    $auditRecords[] = $record;
}
$stmtRecords->close();

$auditorName = trim($auditor['nombre'] . ' ' . $auditor['apellido']);
$queryFilters = [
    'q' => $search,
    'tabla' => $tableFilter,
    'operacion' => $operationFilter,
    'desde' => $fromDate,
    'hasta' => $toDate
];
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#07110d" />
    <title>Panel de Auditoría | EcoTech</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap"
        rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"
        integrity="sha384-/o6I2CkkWC//PSjvWC/eYN7l3xM3tJm8ZzVkCOfp//W05QcE3mlGskpoHB6XqI+B" crossorigin="anonymous" />
    <link rel="stylesheet" href="../css/user_panel.css" />
    <link rel="stylesheet" href="../css/auditor.css" />
</head>

<body class="portal-body auditor-body">
    <div class="portal-shell">
        <aside class="portal-sidebar" aria-label="Navegación del panel de auditoría">
            <a class="portal-brand" href="index.php" aria-label="EcoTech, volver al sitio">
                <span class="portal-brand-mark"><i class="fas fa-leaf"></i></span>
                <span><em>Eco</em>Tech</span>
            </a>
            <p class="portal-sidebar-caption">Supervisión y trazabilidad</p>
            <nav class="portal-nav" aria-label="Secciones del panel">
                <a href="#resumen" class="portal-nav-link"><i class="fas fa-chart-pie"></i><span>Resumen</span></a>
                <a href="#registro" class="portal-nav-link"><i class="fas fa-list-check"></i><span>Registro de auditoría</span></a>
            </nav>
            <div class="portal-account">
                <span class="portal-avatar"><?php echo auditorEscape(mb_strtoupper(mb_substr($auditorName, 0, 1, 'UTF-8'), 'UTF-8')); ?></span>
                <span class="portal-account-info">
                    <strong><?php echo auditorEscape($auditorName); ?></strong>
                    <small>Auditor EcoTech</small>
                </span>
                <a href="../php/logout.php" class="portal-logout" aria-label="Cerrar sesión" title="Cerrar sesión">
                    <i class="fas fa-arrow-right-from-bracket"></i>
                </a>
            </div>
        </aside>

        <main class="portal-main">
            <header class="portal-topbar">
                <div>
                    <span class="portal-breadcrumb">EcoTech <i class="fas fa-chevron-right"></i> Auditoría</span>
                    <p class="portal-topbar-subtitle">Consulta la trazabilidad de las operaciones registradas.</p>
                </div>
                <a href="index.php" class="portal-site-link"><i class="fas fa-arrow-up-right-from-square"></i> Ver sitio</a>
            </header>

            <?php if ($auditSetupWarning) { ?>
                <div class="auditor-setup-warning" role="alert">
                    <i class="fas fa-triangle-exclamation"></i>
                    El historial automático aún no está completo. Importa
                    <code>db/auditoria_triggers.sql</code><?php echo $hasPortalTables ? ' y db/auditoria_portal_triggers.sql' : ''; ?>
                    para registrar los cambios de la aplicación.
                </div>
            <?php } ?>

            <section class="auditor-hero" id="resumen">
                <div>
                    <span class="portal-eyebrow"><span></span> Transparencia operativa</span>
                    <h1>Registro de auditoría</h1>
                    <p>Consulta los eventos almacenados en el sistema, filtra por tabla, operación o fecha, y revisa su detalle.</p>
                    <a href="#registro" class="portal-button portal-button-primary">Explorar registros <i class="fas fa-arrow-down"></i></a>
                </div>
                <div class="auditor-hero-icon" aria-hidden="true"><i class="fas fa-shield-halved"></i></div>
            </section>

            <section class="auditor-metrics" aria-label="Resumen del registro de auditoría">
                <article class="auditor-metric">
                    <span class="auditor-metric-icon"><i class="fas fa-database"></i></span>
                    <div><small>Eventos registrados</small><strong><?php echo number_format($summary['total']); ?></strong></div>
                </article>
                <article class="auditor-metric">
                    <span class="auditor-metric-icon is-blue"><i class="fas fa-calendar-day"></i></span>
                    <div><small>Eventos de hoy</small><strong><?php echo number_format($summary['today']); ?></strong></div>
                </article>
                <article class="auditor-metric">
                    <span class="auditor-metric-icon is-purple"><i class="fas fa-table-list"></i></span>
                    <div><small>Tablas con actividad</small><strong><?php echo number_format($summary['tables']); ?></strong></div>
                </article>
            </section>

            <section class="auditor-panel" id="registro">
                <div class="auditor-section-heading">
                    <div>
                        <span class="portal-eyebrow">Consulta histórica</span>
                        <h2>Actividad del sistema</h2>
                        <p><?php echo number_format($filteredTotal); ?> resultado(s) · ordenados del más reciente al más antiguo</p>
                    </div>
                </div>

                <form class="auditor-filters" method="get" action="auditor_panel.php">
                    <label class="auditor-filter-search">
                        <span>Buscar</span>
                        <input type="search" name="q" maxlength="100" value="<?php echo auditorEscape($search); ?>"
                            placeholder="Detalle, registro, tabla o usuario" />
                    </label>
                    <label>
                        <span>Tabla</span>
                        <select name="tabla">
                            <option value="">Todas las tablas</option>
                            <?php foreach ($tables as $tableName) { ?>
                                <option value="<?php echo auditorEscape($tableName); ?>" <?php echo $tableFilter === $tableName ? 'selected' : ''; ?>>
                                    <?php echo auditorEscape($tableName); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </label>
                    <label>
                        <span>Operación</span>
                        <select name="operacion">
                            <option value="">Todas</option>
                            <?php foreach ($operations as $operationName) { ?>
                                <option value="<?php echo auditorEscape($operationName); ?>" <?php echo $operationFilter === $operationName ? 'selected' : ''; ?>>
                                    <?php echo auditorEscape($operationName); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </label>
                    <label>
                        <span>Desde</span>
                        <input type="date" name="desde" value="<?php echo auditorEscape($fromDate); ?>" />
                    </label>
                    <label>
                        <span>Hasta</span>
                        <input type="date" name="hasta" value="<?php echo auditorEscape($toDate); ?>" />
                    </label>
                    <div class="auditor-filter-actions">
                        <button class="portal-button portal-button-primary" type="submit"><i class="fas fa-filter"></i> Aplicar filtros</button>
                        <a class="auditor-reset-link" href="auditor_panel.php">Limpiar</a>
                    </div>
                </form>

                <div class="auditor-record-list">
                    <?php if (!$auditRecords) { ?>
                        <div class="auditor-empty">
                            <i class="fas fa-folder-open"></i>
                            <strong>No hay eventos para mostrar</strong>
                            <span>Prueba otros filtros o revisa nuevamente cuando se registren operaciones.</span>
                        </div>
                    <?php } else { ?>
                        <?php foreach ($auditRecords as $record) { ?>
                            <article class="auditor-record">
                                <span class="auditor-record-icon"><i class="fas fa-arrow-right-arrow-left"></i></span>
                                <div class="auditor-record-main">
                                    <div class="auditor-record-heading">
                                        <strong><?php echo auditorEscape($record['tabla_afectada']); ?></strong>
                                        <span class="auditor-operation"><?php echo auditorEscape($record['operacion']); ?></span>
                                    </div>
                                    <p><?php echo auditorEscape($record['detalle'] ?: 'Sin detalle disponible'); ?></p>
                                    <div class="auditor-record-meta">
                                        <span><i class="fas fa-hashtag"></i> Registro <?php echo $record['registro_id'] === null ? '—' : (int) $record['registro_id']; ?></span>
                                        <span><i class="fas fa-user"></i> <?php echo auditorEscape($record['usuario_sql'] ?: 'Usuario no especificado'); ?></span>
                                    </div>
                                    <?php if ($record['valores_anteriores'] !== null || $record['valores_nuevos'] !== null) { ?>
                                        <details class="auditor-snapshot">
                                            <summary>Ver datos registrados antes y después</summary>
                                            <div class="auditor-snapshot-grid">
                                                <?php if ($record['valores_anteriores'] !== null) { ?>
                                                    <div>
                                                        <strong>Antes</strong>
                                                        <pre><?php echo auditorEscape($record['valores_anteriores']); ?></pre>
                                                    </div>
                                                <?php } ?>
                                                <?php if ($record['valores_nuevos'] !== null) { ?>
                                                    <div>
                                                        <strong>Después</strong>
                                                        <pre><?php echo auditorEscape($record['valores_nuevos']); ?></pre>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                        </details>
                                    <?php } ?>
                                </div>
                                <time datetime="<?php echo auditorEscape(date('c', strtotime($record['fecha']))); ?>">
                                    <?php echo auditorEscape(date('d/m/Y H:i:s', strtotime($record['fecha']))); ?>
                                </time>
                            </article>
                        <?php } ?>
                    <?php } ?>
                </div>

                <?php if ($totalPages > 1) { ?>
                    <nav class="auditor-pagination" aria-label="Paginación del registro de auditoría">
                        <span>Página <?php echo $page; ?> de <?php echo $totalPages; ?></span>
                        <div>
                            <?php if ($page > 1) { ?>
                                <a href="?<?php echo auditorEscape(http_build_query([...$queryFilters, 'pagina' => $page - 1])); ?>">
                                    <i class="fas fa-arrow-left"></i> Anterior
                                </a>
                            <?php } ?>
                            <?php if ($page < $totalPages) { ?>
                                <a href="?<?php echo auditorEscape(http_build_query([...$queryFilters, 'pagina' => $page + 1])); ?>">
                                    Siguiente <i class="fas fa-arrow-right"></i>
                                </a>
                            <?php } ?>
                        </div>
                    </nav>
                <?php } ?>
            </section>

            <footer class="auditor-footer">EcoTech · Vista de consulta · Los eventos se muestran según los datos registrados en la base.</footer>
        </main>
    </div>
</body>

</html>
