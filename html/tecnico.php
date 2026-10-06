<?php
session_start();

if (empty($_SESSION['usuario']['correo'])) {
    header('Location: login_user.php?status=session_expired');
    exit();
}

require_once __DIR__ . '/../php/conexion.php';

class TechnicianDatabaseException extends RuntimeException
{
}

$email = (string) $_SESSION['usuario']['correo'];
$stmtUser = $conn->prepare("
    SELECT usuario_id, nombre, apellido, rol, activo
    FROM `Usuarios`
    WHERE email = ?
    LIMIT 1
");
if (!$stmtUser) {
    error_log('Technician portal user query prepare failed: ' . $conn->error);
    http_response_code(500);
    exit('No se pudo cargar el portal técnico.');
}
$stmtUser->bind_param('s', $email);
if (!$stmtUser->execute()) {
    error_log('Technician portal user query failed: ' . $stmtUser->error);
    http_response_code(500);
    exit('No se pudo cargar el portal técnico.');
}
$technician = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();

if (!$technician || (int) $technician['activo'] !== 1) {
    session_destroy();
    header('Location: login_user.php?status=account_inactive');
    exit();
}
if (strcasecmp((string) $technician['rol'], 'Tecnico') !== 0) {
    header('Location: index.php');
    exit();
}

$technicianId = (int) $technician['usuario_id'];
$technicianName = trim($technician['nombre'] . ' ' . $technician['apellido']);
$_SESSION['usuario']['id'] = $technicianId;
$_SESSION['usuario']['rol'] = 'Tecnico';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function technicianEscape(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function technicianRedirect(string $message, string $type = 'error'): never
{
    $_SESSION['tecnico_flash'] = ['message' => $message, 'type' => $type];
    header('Location: tecnico.php#equipos');
    exit();
}

function technicianValidCost(string $value): bool
{
    return $value === '' || preg_match('/\A\d{1,16}(?:\.\d{1,2})?\z/', $value) === 1;
}

function technicianExecute(mysqli_stmt $stmt): void
{
    if (!$stmt->execute()) {
        throw new TechnicianDatabaseException($stmt->error);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    if (!isset($_SESSION['csrf_token']) || !hash_equals((string) $_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('La sesión del formulario venció. Recarga el panel e inténtalo de nuevo.');
    }

    $equipmentId = filter_var($_POST['equipo_id'] ?? null, FILTER_VALIDATE_INT);
    $action = (string) ($_POST['action'] ?? '');
    if (!$equipmentId || $equipmentId < 1) {
        technicianRedirect('Selecciona un equipo válido.');
    }

    $stmtEquipment = $conn->prepare('SELECT equipo_id FROM `Equipos` WHERE equipo_id = ? LIMIT 1');
    if (!$stmtEquipment) {
        error_log('Technician equipment validation prepare failed: ' . $conn->error);
        technicianRedirect('No se pudo validar el equipo. Inténtalo de nuevo.');
    }
    $stmtEquipment->bind_param('i', $equipmentId);
    if (!$stmtEquipment->execute()) {
        error_log('Technician equipment validation failed: ' . $stmtEquipment->error);
        $stmtEquipment->close();
        technicianRedirect('No se pudo validar el equipo. Inténtalo de nuevo.');
    }
    $equipmentExists = (bool) $stmtEquipment->get_result()->fetch_assoc();
    $stmtEquipment->close();
    if (!$equipmentExists) {
        technicianRedirect('El equipo ya no existe en el inventario.');
    }

    if ($action === 'diagnosis') {
        $description = trim((string) ($_POST['descripcion'] ?? ''));
        $requiresRepair = (string) ($_POST['requiere_repara'] ?? '');
        $estimatedCost = trim((string) ($_POST['costo_estimado'] ?? ''));
        if (
            $description === '' || mb_strlen($description, 'UTF-8') > 250 ||
            !in_array($requiresRepair, ['0', '1'], true) ||
            !technicianValidCost($estimatedCost)
        ) {
            technicianRedirect('Completa el diagnóstico y revisa el costo estimado.');
        }

        $requiresRepairValue = (int) $requiresRepair;
        $estimatedCostValue = $estimatedCost === '' ? null : $estimatedCost;
        $newEquipmentState = $requiresRepairValue === 1 ? 'En Reparación' : 'Listo para entrega';

        try {
            $conn->begin_transaction();
            $stmtDiagnosis = $conn->prepare("
                INSERT INTO `Diagnosticos`
                    (equipo_id, tecnico_id, descripcion, requiere_repara, costo_estimado)
                VALUES (?, ?, ?, ?, ?)
            ");
            if (!$stmtDiagnosis) {
                throw new TechnicianDatabaseException($conn->error);
            }
            $stmtDiagnosis->bind_param(
                'iisis',
                $equipmentId,
                $technicianId,
                $description,
                $requiresRepairValue,
                $estimatedCostValue
            );
            technicianExecute($stmtDiagnosis);
            $stmtDiagnosis->close();

            $stmtState = $conn->prepare('UPDATE `Equipos` SET estado_actual = ? WHERE equipo_id = ?');
            if (!$stmtState) {
                throw new TechnicianDatabaseException($conn->error);
            }
            $stmtState->bind_param('si', $newEquipmentState, $equipmentId);
            technicianExecute($stmtState);
            $stmtState->close();
            $conn->commit();
        } catch (Throwable $error) {
            $conn->rollback();
            error_log('Technician diagnosis save failed: ' . $error->getMessage());
            technicianRedirect('No se pudo guardar el diagnóstico. Revisa la base de datos e inténtalo de nuevo.');
        }
        technicianRedirect('El diagnóstico quedó registrado.', 'success');
    }

    if ($action === 'repair') {
        $description = trim((string) ($_POST['descripcion_reparacion'] ?? ''));
        $parts = trim((string) ($_POST['repuestos_usados'] ?? ''));
        $cost = trim((string) ($_POST['costo_real'] ?? ''));
        $completed = (string) ($_POST['completada'] ?? '');
        if (
            $description === '' || mb_strlen($description, 'UTF-8') > 250 ||
            mb_strlen($parts, 'UTF-8') > 100 ||
            !technicianValidCost($cost) ||
            !in_array($completed, ['0', '1'], true)
        ) {
            technicianRedirect('Completa el trabajo realizado y revisa los repuestos y el costo.');
        }

        $partsValue = $parts === '' ? '' : $parts;
        $costValue = $cost === '' ? '' : $cost;
        $completedValue = (int) $completed;
        $repairState = $completedValue === 1 ? 'Completada' : 'En reparación';
        $equipmentState = $completedValue === 1 ? 'Reacondicionado' : 'En Reparación';

        try {
            $conn->begin_transaction();
            $stmtRepair = $conn->prepare("
                INSERT INTO `Reparaciones`
                    (equipo_id, tecnico_id, descripcion, repuestos_usados, costo_real, estado, fecha_fin)
                VALUES (?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), ?, IF(? = 1, CURRENT_TIMESTAMP, NULL))
            ");
            if (!$stmtRepair) {
                throw new TechnicianDatabaseException($conn->error);
            }
            $stmtRepair->bind_param(
                'iissssi',
                $equipmentId,
                $technicianId,
                $description,
                $partsValue,
                $costValue,
                $repairState,
                $completedValue
            );
            technicianExecute($stmtRepair);
            $stmtRepair->close();

            $stmtState = $conn->prepare('UPDATE `Equipos` SET estado_actual = ? WHERE equipo_id = ?');
            if (!$stmtState) {
                throw new TechnicianDatabaseException($conn->error);
            }
            $stmtState->bind_param('si', $equipmentState, $equipmentId);
            technicianExecute($stmtState);
            $stmtState->close();
            $conn->commit();
        } catch (Throwable $error) {
            $conn->rollback();
            error_log('Technician repair save failed: ' . $error->getMessage());
            technicianRedirect('No se pudo guardar la reparación. Revisa la base de datos e inténtalo de nuevo.');
        }
        technicianRedirect('La reparación quedó registrada.', 'success');
    }

    technicianRedirect('La acción solicitada no es válida.');
}

$flash = $_SESSION['tecnico_flash'] ?? null;
unset($_SESSION['tecnico_flash']);

$summary = [
    'total' => 0,
    'pendientes' => 0,
    'reparacion' => 0,
    'diagnosticos' => 0
];
$resultSummary = $conn->query("
    SELECT
        (SELECT COUNT(*) FROM `Equipos`) AS total,
        (SELECT COUNT(*) FROM `Equipos`
         WHERE estado_actual IN ('Recibido', 'Pendiente', 'En Diagnóstico')) AS pendientes,
        (SELECT COUNT(*) FROM `Equipos`
         WHERE estado_actual = 'En Reparación') AS reparacion,
        (SELECT COUNT(*) FROM `Diagnosticos` WHERE tecnico_id = {$technicianId}) AS diagnosticos
");
if (!$resultSummary) {
    error_log('Technician summary query failed: ' . $conn->error);
    http_response_code(500);
    exit('No se pudo cargar el resumen del inventario.');
}
$summary = array_map('intval', $resultSummary->fetch_assoc());

$equipment = [];
$resultEquipment = $conn->query("
    SELECT e.equipo_id, e.marca, e.modelo, e.serial, e.estado_actual,
           e.descripcion, e.fecha_recepcion, t.nombre AS tipo_nombre,
           (SELECT d.descripcion FROM `Diagnosticos` d
            WHERE d.equipo_id = e.equipo_id
            ORDER BY d.fecha DESC, d.diagnostico_id DESC LIMIT 1) AS ultimo_diagnostico,
           (SELECT d.fecha FROM `Diagnosticos` d
            WHERE d.equipo_id = e.equipo_id
            ORDER BY d.fecha DESC, d.diagnostico_id DESC LIMIT 1) AS fecha_diagnostico,
           (SELECT r.estado FROM `Reparaciones` r
            WHERE r.equipo_id = e.equipo_id
            ORDER BY r.fecha_inicio DESC, r.reparacion_id DESC LIMIT 1) AS estado_reparacion
    FROM `Equipos` e
    LEFT JOIN `TiposEquipo` t ON t.tipo_id = e.tipo_id
    ORDER BY e.fecha_recepcion DESC, e.equipo_id DESC
    LIMIT 100
");
if (!$resultEquipment) {
    error_log('Technician equipment query failed: ' . $conn->error);
    http_response_code(500);
    exit('No se pudo cargar el inventario.');
}
while ($row = $resultEquipment->fetch_assoc()) {
    $equipment[] = $row;
}

$activity = [];
$stmtActivity = $conn->prepare("
    SELECT actividad.tipo, actividad.equipo, actividad.detalle, actividad.fecha
    FROM (
        SELECT 'Diagnóstico' AS tipo,
               CONCAT_WS(' ', e.marca, e.modelo) AS equipo,
               d.descripcion AS detalle,
               d.fecha AS fecha
        FROM `Diagnosticos` d
        INNER JOIN `Equipos` e ON e.equipo_id = d.equipo_id
        WHERE d.tecnico_id = ?
        UNION ALL
        SELECT 'Reparación' AS tipo,
               CONCAT_WS(' ', e.marca, e.modelo) AS equipo,
               CONCAT_WS(' · ', r.descripcion, NULLIF(r.repuestos_usados, '')) AS detalle,
               r.fecha_inicio AS fecha
        FROM `Reparaciones` r
        INNER JOIN `Equipos` e ON e.equipo_id = r.equipo_id
        WHERE r.tecnico_id = ?
    ) AS actividad
    ORDER BY actividad.fecha DESC
    LIMIT 15
");
if (!$stmtActivity) {
    error_log('Technician activity query prepare failed: ' . $conn->error);
    http_response_code(500);
    exit('No se pudo cargar la actividad reciente.');
}
$stmtActivity->bind_param('ii', $technicianId, $technicianId);
if (!$stmtActivity->execute()) {
    error_log('Technician activity query failed: ' . $stmtActivity->error);
    http_response_code(500);
    exit('No se pudo cargar la actividad reciente.');
}
$resultActivity = $stmtActivity->get_result();
while ($row = $resultActivity->fetch_assoc()) {
    $activity[] = $row;
}
$stmtActivity->close();
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#07110d" />
    <title>Panel Técnico | EcoTech</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap"
        rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"
        integrity="sha384-/o6I2CkkWC//PSjvWC/eYN7l3xM3tJm8ZzVkCOfp//W05QcE3mlGskpoHB6XqI+B" crossorigin="anonymous" />
    <link rel="stylesheet" href="../css/user_panel.css" />
    <link rel="stylesheet" href="../css/tecnico.css" />
</head>

<body class="portal-body technician-body">
    <div class="portal-shell">
        <aside class="portal-sidebar" aria-label="Navegación del panel técnico">
            <a class="portal-brand" href="index.php" aria-label="EcoTech, volver al sitio">
                <span class="portal-brand-mark"><i class="fas fa-leaf"></i></span>
                <span><em>Eco</em>Tech</span>
            </a>
            <p class="portal-sidebar-caption">Centro técnico</p>
            <nav class="portal-nav" aria-label="Secciones del panel">
                <a href="#resumen" class="portal-nav-link"><i class="fas fa-chart-line"></i><span>Resumen</span></a>
                <a href="#equipos" class="portal-nav-link"><i class="fas fa-screwdriver-wrench"></i><span>Inventario</span></a>
                <a href="#actividad" class="portal-nav-link"><i class="fas fa-clock-rotate-left"></i><span>Mi actividad</span></a>
            </nav>
            <div class="portal-account">
                <span class="portal-avatar"><?php echo technicianEscape(mb_strtoupper(mb_substr($technicianName, 0, 1, 'UTF-8'), 'UTF-8')); ?></span>
                <span class="portal-account-info">
                    <strong><?php echo technicianEscape($technicianName); ?></strong>
                    <small>Técnico EcoTech</small>
                </span>
                <a href="../php/logout.php" class="portal-logout" aria-label="Cerrar sesión" title="Cerrar sesión">
                    <i class="fas fa-arrow-right-from-bracket"></i>
                </a>
            </div>
        </aside>

        <main class="portal-main">
            <header class="portal-topbar">
                <div>
                    <span class="portal-breadcrumb">EcoTech <i class="fas fa-chevron-right"></i> Operaciones técnicas</span>
                    <p class="portal-topbar-subtitle">Evalúa, diagnostica y registra la reparación de equipos.</p>
                </div>
                <a href="index.php" class="portal-site-link"><i class="fas fa-arrow-up-right-from-square"></i> Ver sitio</a>
            </header>

            <?php if (is_array($flash)) { ?>
                <output class="technician-alert <?php echo ($flash['type'] ?? '') === 'success' ? 'is-success' : 'is-error'; ?>" aria-live="polite">
                    <?php echo technicianEscape($flash['message'] ?? ''); ?>
                </output>
            <?php } ?>

            <section class="technician-hero" id="resumen">
                <div>
                    <span class="portal-eyebrow"><span></span> Taller EcoTech</span>
                    <h1>Hola, <?php echo technicianEscape($technician['nombre']); ?></h1>
                    <p>Gestiona el diagnóstico y la reparación de los equipos registrados en el inventario.</p>
                    <a href="#equipos" class="portal-button portal-button-primary">Revisar inventario <i class="fas fa-arrow-down"></i></a>
                </div>
                <div class="technician-hero-icon" aria-hidden="true"><i class="fas fa-screwdriver-wrench"></i></div>
            </section>

            <section class="technician-metrics" aria-label="Resumen del inventario">
                <article class="technician-metric">
                    <span class="technician-metric-icon"><i class="fas fa-boxes-stacked"></i></span>
                    <div><small>Equipos registrados</small><strong><?php echo $summary['total'] ?? 0; ?></strong></div>
                </article>
                <article class="technician-metric">
                    <span class="technician-metric-icon is-amber"><i class="fas fa-clipboard-check"></i></span>
                    <div><small>Pendientes de evaluación</small><strong><?php echo $summary['pendientes'] ?? 0; ?></strong></div>
                </article>
                <article class="technician-metric">
                    <span class="technician-metric-icon is-blue"><i class="fas fa-toolbox"></i></span>
                    <div><small>En reparación</small><strong><?php echo $summary['reparacion'] ?? 0; ?></strong></div>
                </article>
                <article class="technician-metric">
                    <span class="technician-metric-icon is-purple"><i class="fas fa-file-circle-check"></i></span>
                    <div><small>Mis diagnósticos</small><strong><?php echo $summary['diagnosticos'] ?? 0; ?></strong></div>
                </article>
            </section>

            <section class="technician-panel" id="equipos">
                <div class="technician-section-heading">
                    <div>
                        <span class="portal-eyebrow">Control de equipos</span>
                        <h2>Inventario de trabajo</h2>
                        <p>Registra hallazgos, estima costos y documenta las reparaciones.</p>
                    </div>
                    <label class="technician-search">
                        <i class="fas fa-magnifying-glass"></i>
                        <input type="search" id="equipment-search" placeholder="Buscar equipo o estado" aria-label="Buscar en el inventario" />
                    </label>
                </div>

                <?php if (!$equipment) { ?>
                    <div class="technician-empty">
                        <i class="fas fa-box-open"></i>
                        <strong>No hay equipos registrados todavía</strong>
                        <span>Cuando se agreguen equipos al inventario, aparecerán aquí para su revisión.</span>
                    </div>
                <?php } else { ?>
                    <div class="technician-equipment-list" id="equipment-list">
                        <?php foreach ($equipment as $item) { ?>
                            <?php
                            $equipmentLabel = trim(($item['marca'] ?? '') . ' ' . ($item['modelo'] ?? ''));
                            if ($equipmentLabel === '') {
                                $equipmentLabel = 'Equipo #' . (int) $item['equipo_id'];
                            }
                            ?>
                            <article class="technician-equipment-card" data-search="<?php echo technicianEscape(mb_strtolower($equipmentLabel . ' ' . ($item['tipo_nombre'] ?? '') . ' ' . $item['estado_actual'], 'UTF-8')); ?>">
                                <div class="technician-equipment-summary">
                                    <span class="technician-device-icon"><i class="fas fa-laptop"></i></span>
                                    <div class="technician-equipment-copy">
                                        <span class="technician-equipment-type"><?php echo technicianEscape($item['tipo_nombre'] ?? 'Equipo'); ?> · #<?php echo (int) $item['equipo_id']; ?></span>
                                        <h3><?php echo technicianEscape($equipmentLabel); ?></h3>
                                        <p>
                                            <?php echo technicianEscape($item['serial'] ?: 'Sin número de serie'); ?>
                                            <span aria-hidden="true"> · </span>
                                            Recibido <?php echo technicianEscape(date('d/m/Y', strtotime($item['fecha_recepcion']))); ?>
                                        </p>
                                    </div>
                                    <span class="technician-state"><?php echo technicianEscape($item['estado_actual']); ?></span>
                                </div>
                                <?php if (!empty($item['ultimo_diagnostico'])) { ?>
                                    <p class="technician-last-work"><i class="fas fa-clipboard-list"></i>
                                        Último diagnóstico: <?php echo technicianEscape($item['ultimo_diagnostico']); ?>
                                    </p>
                                <?php } ?>
                                <div class="technician-work-forms">
                                    <details class="technician-form-disclosure">
                                        <summary><i class="fas fa-stethoscope"></i> Registrar diagnóstico</summary>
                                        <form method="post" action="tecnico.php#equipos" class="technician-form">
                                            <input type="hidden" name="csrf_token" value="<?php echo technicianEscape($_SESSION['csrf_token']); ?>" />
                                            <input type="hidden" name="action" value="diagnosis" />
                                            <input type="hidden" name="equipo_id" value="<?php echo (int) $item['equipo_id']; ?>" />
                                            <label>
                                                <span>Hallazgos y diagnóstico <b>*</b></span>
                                                <textarea name="descripcion" maxlength="250" rows="3" required placeholder="Describe el estado del equipo y las pruebas realizadas"></textarea>
                                            </label>
                                            <div class="technician-form-row">
                                                <label>
                                                    <span>¿Requiere reparación?</span>
                                                    <select name="requiere_repara" required>
                                                        <option value="1">Sí, requiere reparación</option>
                                                        <option value="0">No, está listo para entrega</option>
                                                    </select>
                                                </label>
                                                <label>
                                                    <span>Costo estimado</span>
                                                    <input type="number" name="costo_estimado" min="0" step="0.01" placeholder="Opcional" />
                                                </label>
                                            </div>
                                            <button class="portal-button portal-button-primary" type="submit">Guardar diagnóstico</button>
                                        </form>
                                    </details>
                                    <details class="technician-form-disclosure">
                                        <summary><i class="fas fa-wrench"></i> Registrar reparación</summary>
                                        <form method="post" action="tecnico.php#equipos" class="technician-form">
                                            <input type="hidden" name="csrf_token" value="<?php echo technicianEscape($_SESSION['csrf_token']); ?>" />
                                            <input type="hidden" name="action" value="repair" />
                                            <input type="hidden" name="equipo_id" value="<?php echo (int) $item['equipo_id']; ?>" />
                                            <label>
                                                <span>Trabajo realizado <b>*</b></span>
                                                <textarea name="descripcion_reparacion" maxlength="250" rows="3" required placeholder="Describe las tareas y resultados de la reparación"></textarea>
                                            </label>
                                            <div class="technician-form-row technician-form-row-three">
                                                <label>
                                                    <span>Repuestos utilizados</span>
                                                    <input type="text" name="repuestos_usados" maxlength="100" placeholder="Opcional" />
                                                </label>
                                                <label>
                                                    <span>Costo real</span>
                                                    <input type="number" name="costo_real" min="0" step="0.01" placeholder="Opcional" />
                                                </label>
                                                <label>
                                                    <span>Estado del trabajo</span>
                                                    <select name="completada" required>
                                                        <option value="0">En progreso</option>
                                                        <option value="1">Completada</option>
                                                    </select>
                                                </label>
                                            </div>
                                            <button class="portal-button portal-button-primary" type="submit">Guardar reparación</button>
                                        </form>
                                    </details>
                                </div>
                            </article>
                        <?php } ?>
                    </div>
                    <div class="technician-empty" id="equipment-no-results" hidden>
                        <i class="fas fa-magnifying-glass"></i>
                        <strong>No hay coincidencias</strong>
                        <span>Prueba con otro nombre, tipo o estado.</span>
                    </div>
                <?php } ?>
            </section>

            <section class="technician-panel" id="actividad">
                <div class="technician-section-heading">
                    <div>
                        <span class="portal-eyebrow">Seguimiento personal</span>
                        <h2>Mi actividad reciente</h2>
                        <p>Tus diagnósticos y reparaciones más recientes.</p>
                    </div>
                </div>
                <div class="technician-activity-list">
                    <?php if (!$activity) { ?>
                        <div class="technician-empty">
                            <i class="fas fa-clock-rotate-left"></i>
                            <strong>Aún no hay actividad registrada</strong>
                            <span>Los trabajos que registres se mostrarán en esta sección.</span>
                        </div>
                    <?php } else { ?>
                        <?php foreach ($activity as $entry) { ?>
                            <article class="technician-activity-item">
                                <span class="technician-activity-icon <?php echo $entry['tipo'] === 'Reparación' ? 'is-repair' : ''; ?>">
                                    <i class="fas <?php echo $entry['tipo'] === 'Reparación' ? 'fa-wrench' : 'fa-stethoscope'; ?>"></i>
                                </span>
                                <div>
                                    <strong><?php echo technicianEscape($entry['tipo']); ?> · <?php echo technicianEscape($entry['equipo'] ?: 'Equipo'); ?></strong>
                                    <p><?php echo technicianEscape($entry['detalle'] ?: 'Sin detalle adicional'); ?></p>
                                </div>
                                <time><?php echo technicianEscape(date('d/m/Y H:i', strtotime($entry['fecha']))); ?></time>
                            </article>
                        <?php } ?>
                    <?php } ?>
                </div>
            </section>

            <footer class="technician-footer">EcoTech · Registro de mantenimiento responsable</footer>
        </main>
    </div>
    <script>
        const equipmentSearch = document.getElementById("equipment-search");
        equipmentSearch?.addEventListener("input", () => {
            const term = equipmentSearch.value.trim().toLocaleLowerCase("es");
            const cards = [...document.querySelectorAll(".technician-equipment-card")];
            let visible = 0;
            cards.forEach((card) => {
                const match = card.dataset.search.includes(term);
                card.hidden = !match;
                visible += match ? 1 : 0;
            });
            const noResults = document.getElementById("equipment-no-results");
            if (noResults) noResults.hidden = cards.length === 0 || visible > 0;
        });
    </script>
</body>

</html>
