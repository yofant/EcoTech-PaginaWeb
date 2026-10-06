<?php
session_start();


if (!isset($_SESSION['usuario'])) {
    header("Location: login_user.php?status=session_expired");
    exit();
}

if (strcasecmp((string) ($_SESSION['usuario']['rol'] ?? ''), 'Administrador') !== 0) {
    header("Location: login_user.php?status=admin_only");
    exit();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include("../php/conexion.php");

$adminNombre = $_SESSION['usuario']['nombre'] ?: 'Administrador';
$panelesPermitidos = ['resumen', 'usuarios', 'empresas', 'acciones', 'estado', 'puntos'];
$activePanel = $_GET['panel'] ?? 'resumen';

if (!in_array($activePanel, $panelesPermitidos, true)) {
    $activePanel = 'resumen';
}

include("../php/admin_usuarios.php");
include("../php/admin_empresas.php");
include("../php/admin_estados.php");
include("../php/admin_acciones.php");
include("../php/admin_dashboard_data.php");
include("../php/admin_puntos.php");

$metricas = [
    'total' => 0,
    'admins' => 0,
    'clientes' => 0,
    'operadores' => 0
];

$consultaMetricas = "
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN LOWER(rol) = 'administrador' THEN 1 ELSE 0 END) AS admins,
        SUM(CASE WHEN LOWER(rol) = 'tecnico' THEN 1 ELSE 0 END) AS clientes,
        SUM(CASE WHEN LOWER(rol) = 'operador' THEN 1 ELSE 0 END) AS operadores
    FROM `Usuarios`
";

$resultadoMetricas = $conn->query($consultaMetricas);

if (!$resultadoMetricas) {
    die("Error al consultar las metricas de usuarios: " . $conn->error);
}
if ($resultadoMetricas->num_rows > 0) {
    $filaMetricas = $resultadoMetricas->fetch_assoc();
    $metricas['total'] = (int) ($filaMetricas['total'] ?? 0);
    $metricas['admins'] = (int) ($filaMetricas['admins'] ?? 0);
    $metricas['clientes'] = (int) ($filaMetricas['clientes'] ?? 0);
    $metricas['operadores'] = (int) ($filaMetricas['operadores'] ?? 0);
}

$conn->close();
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#07110d" />
    <title>Panel de administración | EcoTech</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap"
        rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../css/admin.css" />
</head>

<body class="admin-body">
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <div class="admin-sidebar-top">
                <a href="index.php" class="admin-brand" aria-label="EcoTech, volver al sitio">
                    <span class="admin-brand-mark"><i class="fas fa-leaf"></i></span>
                    <span><span class="brand-eco">Eco</span>Tech</span>
                </a>
                <p class="admin-caption">Centro de administración</p>
            </div>

            <p class="admin-nav-label">Espacio de trabajo</p>
            <nav class="admin-nav">
                <a href="#resumen" class="admin-nav-link <?php echo $activePanel === 'resumen' ? 'active' : ''; ?>" data-panel-target="resumen" aria-current="<?php echo $activePanel === 'resumen' ? 'page' : 'false'; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Resumen</span>
                </a>
                <a href="#usuarios" class="admin-nav-link <?php echo $activePanel === 'usuarios' ? 'active' : ''; ?>" data-panel-target="usuarios" aria-current="<?php echo $activePanel === 'usuarios' ? 'page' : 'false'; ?>">
                    <i class="fas fa-users"></i>
                    <span>Usuarios</span>
                </a>
                <a href="#empresas" class="admin-nav-link <?php echo $activePanel === 'empresas' ? 'active' : ''; ?>" data-panel-target="empresas" aria-current="<?php echo $activePanel === 'empresas' ? 'page' : 'false'; ?>">
                    <i class="fas fa-hand-holding-heart"></i>
                    <span>Donantes</span>
                </a>
                <a href="#acciones" class="admin-nav-link <?php echo $activePanel === 'acciones' ? 'active' : ''; ?>" data-panel-target="acciones" aria-current="<?php echo $activePanel === 'acciones' ? 'page' : 'false'; ?>">
                    <i class="fas fa-boxes-stacked"></i>
                    <span>Operaciones</span>
                </a>
                <a href="#estado" class="admin-nav-link <?php echo $activePanel === 'estado' ? 'active' : ''; ?>" data-panel-target="estado" aria-current="<?php echo $activePanel === 'estado' ? 'page' : 'false'; ?>">
                    <i class="fas fa-list-check"></i>
                    <span>Estados</span>
                </a>
                <a href="#puntos" class="admin-nav-link <?php echo $activePanel === 'puntos' ? 'active' : ''; ?>" data-panel-target="puntos" aria-current="<?php echo $activePanel === 'puntos' ? 'page' : 'false'; ?>">
                    <i class="fas fa-location-dot"></i>
                    <span>Puntos de entrega</span>
                </a>
            </nav>

            <div class="sidebar-card">
                <span class="sidebar-card-avatar" aria-hidden="true"><?php echo htmlspecialchars(strtoupper(substr($adminNombre, 0, 1))); ?></span>
                <div class="sidebar-card-copy">
                    <span class="sidebar-card-label">Sesión activa</span>
                    <strong><?php echo htmlspecialchars($adminNombre); ?></strong>
                    <span class="role-badge">Administrador</span>
                </div>
            </div>
        </aside>

        <main class="admin-main">
            <section class="hero-panel">
                <div class="hero-content">
                    <p class="eyebrow"><span class="hero-status-dot"></span> Centro de control EcoTech</p>
                    <h1>Hola, <?php echo htmlspecialchars($adminNombre); ?></h1>
                    <p class="hero-copy">
                        Administra usuarios, donantes e inventario desde un solo lugar.
                    </p>
                </div>

                <div class="hero-actions">
                    <a href="index.php" class="btn-admin btn-admin-secondary"><i class="fas fa-arrow-up-right-from-square"></i> Ver sitio</a>
                    <a href="../php/logout.php" class="btn-admin btn-admin-primary"><i class="fas fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
                </div>
            </section>

            <section class="metrics-grid" id="metricas-resumen">
                <article class="metric-card">
                    <div class="metric-icon"><i class="fas fa-users"></i></div>
                    <div>
                        <p class="metric-label">Usuarios registrados</p>
                        <h2><?php echo $metricas['total']; ?></h2>
                    </div>
                </article>

                <article class="metric-card">
                    <div class="metric-icon"><i class="fas fa-user-shield"></i></div>
                    <div>
                        <p class="metric-label">Administradores</p>
                        <h2><?php echo $metricas['admins']; ?></h2>
                    </div>
                </article>

                <article class="metric-card">
                    <div class="metric-icon"><i class="fas fa-user"></i></div>
                    <div>
                        <p class="metric-label">Tecnicos</p>
                        <h2><?php echo $metricas['clientes']; ?></h2>
                    </div>
                </article>

                <article class="metric-card">
                    <div class="metric-icon"><i class="fas fa-user-gear"></i></div>
                    <div>
                        <p class="metric-label">Operadores</p>
                        <h2><?php echo $metricas['operadores']; ?></h2>
                    </div>
                </article>
            </section>

            <section class="content-grid">
                <div class="panel-stack">
                    <article class="panel-card wide-card dashboard-panel <?php echo $activePanel === 'resumen' ? 'is-active' : ''; ?>" id="resumen">
                        <div class="panel-heading panel-heading-chart">
                            <div class="panel-heading-text">
                                <p class="panel-kicker">Resumen Dashboard</p>
                                <h2>Dashboard de la plataforma</h2>
                            </div>

                            <span class="panel-tag">Administrador</span>
                        </div>

                        <div class="action-board">
                            <div class="action-board-item action-board-item--chart">
                                <div class="action-board-chart-head">
                                    <div class="action-board-icon" aria-hidden="true"><i class="fas fa-gauge-high"></i></div>
                                    <h3>Equipos</h3>
                                </div>
                                <div class="action-board-chart-stage">
                                    <div class="panel-chart-wrap">
                                        <canvas class="panel-chart-canvas" data-admin-chart="equipos" aria-label="Grafico equipos"></canvas>
                                    </div>
                                </div>
                            </div>

                            <div class="action-board-item action-board-item--chart">
                                <div class="action-board-chart-head">
                                    <div class="action-board-icon" aria-hidden="true"><i class="fas fa-gauge-high"></i></div>
                                    <h3>Ubicaciones</h3>
                                </div>
                                <div class="action-board-chart-stage">
                                    <div class="panel-chart-wrap">
                                        <canvas class="panel-chart-canvas" data-admin-chart="ubicaciones" aria-label="Grafico ubicaciones"></canvas>
                                    </div>
                                </div>
                            </div>

                            <div class="action-board-item action-board-item--chart">
                                <div class="action-board-chart-head">
                                    <div class="action-board-icon" aria-hidden="true"><i class="fas fa-gauge-high"></i></div>
                                    <h3>Estados</h3>
                                </div>
                                <div class="action-board-chart-stage">
                                    <div class="panel-chart-wrap">
                                        <canvas class="panel-chart-canvas" data-admin-chart="estados" aria-label="Grafico estados"></canvas>
                                    </div>
                                </div>
                            </div>

                            <div class="action-board-item action-board-item--chart">
                                <div class="action-board-chart-head">
                                    <div class="action-board-icon" aria-hidden="true"><i class="fas fa-gauge-high"></i></div>
                                    <h3>Usuarios</h3>
                                </div>
                                <div class="action-board-chart-stage">
                                    <div class="panel-chart-wrap">
                                        <canvas class="panel-chart-canvas" data-admin-chart="usuarios" aria-label="Grafico usuarios"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="panel-card wide-card dashboard-panel <?php echo $activePanel === 'usuarios' ? 'is-active' : ''; ?>" id="usuarios">
                        <div class="panel-heading">
                            <div>
                                <p class="panel-kicker">Gestion de usuarios</p>
                                <h2>Crear, editar y eliminar usuarios</h2>
                            </div>
                            <span class="panel-tag">Base de datos</span>
                        </div>

                        <?php if ($crudMessage) { ?>
                            <div class="admin-alert admin-alert-<?php echo htmlspecialchars($crudMessageType ?? 'success'); ?>">
                                <?php echo htmlspecialchars($crudMessage); ?>
                            </div>
                        <?php } ?>

                        <div class="users-admin-grid">
                            <div class="users-form-card">
                                <div class="users-form-head">
                                    <h3><?php echo $modoFormulario === 'editar' ? 'Editar usuario' : 'Nuevo usuario'; ?></h3>
                                    <p>
                                        <?php echo $modoFormulario === 'editar'
                                            ? 'Actualiza los datos del usuario seleccionado.'
                                            : 'Registra una nueva cuenta administrativa u operativa.'; ?>
                                    </p>
                                </div>

                                <form action="admin_panel.php?panel=usuarios" method="POST" class="admin-user-form">
                                    <input type="hidden" name="crud_action" value="save_user" />
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) ($usuarioForm['id'] ?? '')); ?>" />

                                    <div class="form-field">
                                        <label for="nombre">Nombre</label>
                                        <input id="nombre" name="nombre" type="text" class="admin-input"
                                            value="<?php echo htmlspecialchars($usuarioForm['nombre'] ?? ''); ?>" required />
                                    </div>

                                    <div class="form-field">
                                        <label for="primer_apellido">Primer apellido</label>
                                        <input id="primer_apellido" name="primer_apellido" type="text" class="admin-input"
                                            value="<?php echo htmlspecialchars($usuarioForm['primer_apellido'] ?? ''); ?>" required />
                                    </div>

                                    <div class="form-field">
                                        <label for="segundo_apellido">Segundo apellido (opcional)</label>
                                        <input id="segundo_apellido" name="segundo_apellido" type="text" class="admin-input"
                                            value="<?php echo htmlspecialchars($usuarioForm['segundo_apellido'] ?? ''); ?>" />
                                    </div>

                                    <div class="form-field">
                                        <label for="correo">Correo electronico</label>
                                        <input id="correo" name="correo" type="email" class="admin-input"
                                            value="<?php echo htmlspecialchars($usuarioForm['correo'] ?? ''); ?>" required />
                                    </div>

                                    <div class="form-field">
                                        <label for="telefono">Telefono</label>
                                        <input id="telefono" name="telefono" type="tel" class="admin-input"
                                            value="<?php echo htmlspecialchars($usuarioForm['telefono'] ?? ''); ?>" maxlength="20" required />
                                    </div>

                                    <div class="form-field">
                                        <label for="rol">Rol</label>
                                        <select id="rol" name="rol" class="admin-input admin-select" required>
                                            <option value="Administrador" <?php echo ($usuarioForm['rol'] ?? '') === 'Administrador' ? 'selected' : ''; ?>>Administrador</option>
                                            <option value="Tecnico" <?php echo ($usuarioForm['rol'] ?? '') === 'Tecnico' ? 'selected' : ''; ?>>Tecnico</option>
                                            <option value="Operador" <?php echo ($usuarioForm['rol'] ?? '') === 'Operador' ? 'selected' : ''; ?>>Operador</option>
                                            <option value="Auditor" <?php echo ($usuarioForm['rol'] ?? '') === 'Auditor' ? 'selected' : ''; ?>>Auditor</option>
                                            <option value="Usuario" <?php echo ($usuarioForm['rol'] ?? '') === 'Usuario' ? 'selected' : ''; ?>>Usuario</option>
                                            <option value="Vendedor" <?php echo ($usuarioForm['rol'] ?? '') === 'Vendedor' ? 'selected' : ''; ?>>Vendedor</option>
                                        </select>
                                    </div>

                                    <div class="form-field">
                                        <label for="contrasena">
                                            <?php echo $modoFormulario === 'editar' ? 'Nueva contrasena (opcional)' : 'Contrasena'; ?>
                                        </label>
                                        <input id="contrasena" name="contrasena" type="password" class="admin-input"
                                            <?php echo $modoFormulario === 'editar' ? '' : 'required'; ?> />
                                    </div>

                                    <div class="admin-form-actions">
                                        <button type="submit" class="btn-admin btn-admin-primary">
                                            <?php echo $modoFormulario === 'editar' ? 'Guardar cambios' : 'Crear usuario'; ?>
                                        </button>
                                        <?php if ($modoFormulario === 'editar') { ?>
                                            <a href="admin_panel.php?panel=usuarios" class="btn-admin btn-admin-secondary">Cancelar</a>
                                        <?php } ?>
                                    </div>
                                </form>
                            </div>

                            <div class="users-table-card">
                                <div class="users-table-head">
                                    <h3>Listado completo</h3>
                                    <p><?php echo count($usuarios); ?> usuarios registrados</p>
                                </div>

                                <div class="table-responsive admin-table-wrap">
                                    <table class="table admin-table admin-table-users">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Nombre completo</th>
                                                <th>Correo</th>
                                                <th>Rol</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($usuarios) > 0) { ?>
                                                <?php foreach ($usuarios as $usuario) { ?>
                                                    <tr>
                                                        <td>#<?php echo (int) ($usuario['id'] ?? 0); ?></td>
                                                        <td>
                                                            <?php
                                                            $nombreCompleto = trim(
                                                                ($usuario['nombre'] ?? '') . ' ' .
                                                                ($usuario['primer_apellido'] ?? '') . ' ' .
                                                                ($usuario['segundo_apellido'] ?? '')
                                                            );
                                                            echo htmlspecialchars($nombreCompleto);
                                                            ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($usuario['correo'] ?? ''); ?></td>
                                                        <td>
                                                            <?php
                                                            $rolClase = strtolower((string) ($usuario['rol'] ?? ''));
                                                            if ($rolClase === 'AAdministrador') {
                                                                $rolClase = 'admin';
                                                            }
                                                            ?>
                                                            <span class="role-pill role-<?php echo htmlspecialchars($rolClase); ?>">
                                                                <?php echo htmlspecialchars(ucfirst($usuario['rol'] ?? 'Sin rol')); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="table-actions">
                                                                <a href="admin_panel.php?panel=usuarios&user_action=edit&id=<?php echo (int) ($usuario['id'] ?? 0); ?>"
                                                                    class="btn-table-action btn-table-edit">
                                                                    Editar
                                                                </a>
                                                                <a href="admin_panel.php?panel=usuarios&user_action=delete&id=<?php echo (int) ($usuario['id'] ?? 0); ?>"
                                                                    class="btn-table-action btn-table-delete"
                                                                    data-confirm-delete>
                                                                    Eliminar
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            <?php } else { ?>
                                                <tr>
                                                    <td colspan="5">No hay usuarios disponibles para mostrar.</td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="panel-card wide-card dashboard-panel <?php echo $activePanel === 'empresas' ? 'is-active' : ''; ?>" id="empresas">
                        <div class="panel-heading">
                            <div>
                                <p class="panel-kicker">Gestion de donantes</p>
                                <h2>Crear, editar y eliminar donantes de equipos</h2>
                            </div>
                            <span class="panel-tag">Donantes</span>
                        </div>

                        <?php if ($crudEmpresaMessage) { ?>
                            <div class="admin-alert admin-alert-<?php echo htmlspecialchars($crudEmpresaMessageType ?? 'success'); ?>">
                                <?php echo htmlspecialchars($crudEmpresaMessage); ?>
                            </div>
                        <?php } ?>

                        <div class="users-admin-grid">
                            <div class="users-form-card">
                                <div class="users-form-head">
                                    <h3><?php echo $modoEmpresaFormulario === 'editar' ? 'Editar donante' : 'Nuevo donante'; ?></h3>
                                    <p>
                                        <?php echo $modoEmpresaFormulario === 'editar'
                                            ? 'Actualiza los datos del donante asociado al inventario.'
                                            : 'Registra un donante. El identificador y la fecha los asigna la base de datos.'; ?>
                                    </p>
                                </div>

                                <form action="admin_panel.php?panel=empresas" method="POST" class="admin-user-form">
                                    <input type="hidden" name="empresa_crud_action" value="save_empresa" />
                                    <?php if ($modoEmpresaFormulario === 'editar') { ?>
                                        <input type="hidden" name="id_empresa" value="<?php echo htmlspecialchars((string) ($empresaForm['id_empresa'] ?? '')); ?>" />
                                    <?php } ?>

                                    <div class="form-field">
                                        <label for="empresa_nombre">Nombre</label>
                                        <input id="empresa_nombre" name="nombre" type="text" class="admin-input"
                                            value="<?php echo htmlspecialchars($empresaForm['nombre'] ?? ''); ?>" maxlength="150" required />
                                    </div>

                                    <div class="form-field">
                                        <label for="empresa_nit">Tipo de donante</label>
                                        <input id="empresa_nit" name="nit" type="text" class="admin-input"
                                            value="<?php echo htmlspecialchars($empresaForm['nit'] ?? 'Empresa'); ?>" maxlength="20" required />
                                    </div>

                                    <div class="form-field">
                                        <label for="empresa_direccion">Direccion</label>
                                        <input id="empresa_direccion" name="direccion" type="text" class="admin-input"
                                            value="<?php echo htmlspecialchars($empresaForm['direccion'] ?? ''); ?>" maxlength="250" required />
                                    </div>

                                    <div class="form-field">
                                        <label for="empresa_telefono">Telefono</label>
                                        <input id="empresa_telefono" name="telefono" type="text" class="admin-input"
                                            value="<?php echo htmlspecialchars($empresaForm['telefono'] ?? ''); ?>" maxlength="20" required />
                                    </div>

                                    <div class="form-field">
                                        <label for="empresa_correo_contacto">Correo electrónico</label>
                                        <input id="empresa_correo_contacto" name="correo_contacto" type="email" class="admin-input"
                                            value="<?php echo htmlspecialchars($empresaForm['correo_contacto'] ?? ''); ?>" maxlength="150" required />
                                    </div>

                                    <div class="admin-form-actions">
                                        <button type="submit" class="btn-admin btn-admin-primary">
                                            <?php echo $modoEmpresaFormulario === 'editar' ? 'Guardar cambios' : 'Registrar donante'; ?>
                                        </button>
                                        <?php if ($modoEmpresaFormulario === 'editar') { ?>
                                            <a href="admin_panel.php?panel=empresas" class="btn-admin btn-admin-secondary">Cancelar</a>
                                        <?php } ?>
                                    </div>
                                </form>
                            </div>

                            <div class="users-table-card">
                                <div class="users-table-head">
                                    <h3>Listado de donantes</h3>
                                    <p><?php echo count($empresas); ?> donantes registrados</p>
                                </div>

                                <div class="table-responsive admin-table-wrap">
                                    <table class="table admin-table admin-table-users">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Nombre</th>
                                                <th>Tipo</th>
                                                <th>Telefono</th>
                                                <th>Correo</th>
                                                <th>Registro</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($empresas) > 0) { ?>
                                                <?php foreach ($empresas as $emp) { ?>
                                                    <tr>
                                                        <td>#<?php echo (int) ($emp['id_empresa'] ?? 0); ?></td>
                                                        <td><?php echo htmlspecialchars($emp['nombre'] ?? ''); ?></td>
                                                        <td><?php echo htmlspecialchars($emp['nit'] ?? ''); ?></td>
                                                        <td><?php echo htmlspecialchars($emp['telefono'] ?? ''); ?></td>
                                                        <td><?php echo htmlspecialchars($emp['correo_contacto'] ?? ''); ?></td>
                                                        <td><?php echo htmlspecialchars($emp['fecha_registro'] ?? ''); ?></td>
                                                        <td>
                                                            <div class="table-actions">
                                                                <a href="admin_panel.php?panel=empresas&empresa_action=edit&id_empresa=<?php echo (int) ($emp['id_empresa'] ?? 0); ?>"
                                                                    class="btn-table-action btn-table-edit">
                                                                    Editar
                                                                </a>
                                                                <a href="admin_panel.php?panel=empresas&empresa_action=delete&id_empresa=<?php echo (int) ($emp['id_empresa'] ?? 0); ?>"
                                                                    class="btn-table-action btn-table-delete"
                                                                    data-confirm-delete>
                                                                    Eliminar
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            <?php } else { ?>
                                                <tr>
                                                    <td colspan="7">No hay donantes registrados.</td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="panel-card wide-card dashboard-panel <?php echo $activePanel === 'acciones' ? 'is-active' : ''; ?>" id="acciones">
                        <div class="panel-heading">
                            <div>
                                <p class="panel-kicker">Panel de acciones</p>
                                <h2>Acciones administrativas</h2>
                            </div>
                            <span class="panel-tag">Administrador</span>
                        </div>

                        <?php if ($crudAccionMessage) { ?>
                            <div class="admin-alert admin-alert-<?php echo htmlspecialchars($crudAccionMessageType ?? 'success'); ?>">
                                <?php echo htmlspecialchars($crudAccionMessage); ?>
                            </div>
                        <?php } ?>

                        <div class="actions-overview-grid">
                            <article class="metric-card compact-metric-card">
                                <div class="metric-icon"><i class="fas fa-boxes-stacked"></i></div>
                                <div>
                                    <p class="metric-label">Equipos</p>
                                    <h2><?php echo $accionesMetricas['activos']; ?></h2>
                                </div>
                            </article>

                            <article class="metric-card compact-metric-card">
                                <div class="metric-icon"><i class="fas fa-file-circle-plus"></i></div>
                                <div>
                                    <p class="metric-label">Diagnosticos</p>
                                    <h2><?php echo $accionesMetricas['reportes']; ?></h2>
                                </div>
                            </article>

                            <article class="metric-card compact-metric-card">
                                <div class="metric-icon"><i class="fas fa-route"></i></div>
                                <div>
                                    <p class="metric-label">Reparaciones</p>
                                    <h2><?php echo $accionesMetricas['movimientos']; ?></h2>
                                </div>
                            </article>
                        </div>

                        <div class="actions-admin-grid">
                            <div class="users-form-card">
                                <div class="users-form-head">
                                    <h3>Actividad del inventario</h3>
                                    <p>Consulta los diagnosticos y reparaciones registrados sobre los equipos.</p>
                                </div>
                                <p>La base de datos actual no contiene una tabla de reportes; se muestran los registros que si forman parte del esquema.</p>
                            </div>

                            <div class="users-table-card">
                                <div class="users-table-head">
                                    <h3>Accesos de administrador</h3>
                                    <p>Funciones clave del rol admin dentro del panel.</p>
                                </div>

                                <div class="quick-links">
                                    <a href="admin_panel.php?panel=usuarios" class="quick-link-card">
                                        <i class="fas fa-users-cog"></i>
                                        <span>Gestionar usuarios</span>
                                    </a>
                                    <a href="admin_panel.php?panel=estado" class="quick-link-card">
                                        <i class="fas fa-list-check"></i>
                                        <span>Ver estados de equipos</span>
                                    </a>
                                    <a href="#acciones-activos" class="quick-link-card">
                                        <i class="fas fa-warehouse"></i>
                                        <span>Supervisar inventario</span>
                                    </a>
                                    <a href="#acciones-historial" class="quick-link-card">
                                        <i class="fas fa-arrow-right-arrow-left"></i>
                                        <span>Revisar reparaciones</span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="action-section-card" id="acciones-activos">
                            <div class="panel-heading">
                                <div>
                                    <p class="panel-kicker">Supervisar inventario</p>
                                    <h2>Equipos registrados</h2>
                                </div>
                                <span class="panel-tag">Inventario</span>
                            </div>

                            <div class="table-responsive admin-table-wrap">
                                <table class="table admin-table admin-table-users">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Serial</th>
                                            <th>Equipo</th>
                                            <th>Tipo</th>
                                            <th>Estado</th>
                                            <th>Ubicacion</th>
                                            <th>Donante</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($activosAdmin) > 0) { ?>
                                            <?php foreach ($activosAdmin as $activo) { ?>
                                                <tr>
                                                    <td>#<?php echo (int) ($activo['id_activo'] ?? 0); ?></td>
                                                    <td><?php echo htmlspecialchars($activo['codigo_qr'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($activo['nombre_activo'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($activo['categoria'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($activo['estado'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($activo['ubicacion'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($activo['empresa'] ?? ''); ?></td>
                                                </tr>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <tr>
                                                <td colspan="7">Todavia no hay equipos registrados en la base de datos.</td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="action-section-card">
                            <div class="panel-heading">
                                <div>
                                    <p class="panel-kicker">Diagnosticos</p>
                                    <h2>Diagnosticos recientes</h2>
                                </div>
                                <span class="panel-tag">Diagnosticos</span>
                            </div>

                            <div class="table-responsive admin-table-wrap">
                                <table class="table admin-table admin-table-users">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Equipo</th>
                                            <th>Descripcion</th>
                                            <th>Fecha</th>
                                            <th>Generado por</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($reportesAdmin) > 0) { ?>
                                            <?php foreach ($reportesAdmin as $reporte) { ?>
                                                <tr>
                                                    <td>#<?php echo (int) ($reporte['id_reporte'] ?? 0); ?></td>
                                                    <td><?php echo htmlspecialchars($reporte['titulo'] ?? ''); ?></td>
                                                    <td class="admin-description-cell"><?php echo htmlspecialchars($reporte['descripcion'] ?? 'Sin descripcion'); ?></td>
                                                    <td><?php echo htmlspecialchars($reporte['fecha_generacion'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($reporte['generado_por_nombre'] ?? 'Sin usuario'); ?></td>
                                                </tr>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <tr>
                                                <td colspan="5">Todavia no hay diagnosticos registrados en el sistema.</td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="action-section-card" id="acciones-historial">
                            <div class="panel-heading">
                                <div>
                                    <p class="panel-kicker">Revisar trazabilidad</p>
                                    <h2>Reparaciones recientes</h2>
                                </div>
                                <span class="panel-tag">Mantenimiento</span>
                            </div>

                            <div class="table-responsive admin-table-wrap">
                                <table class="table admin-table admin-table-users">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Activo</th>
                                            <th>Estado de reparacion</th>
                                            <th>Estado del equipo</th>
                                            <th>Inicio</th>
                                            <th>Descripcion y repuestos</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($historialAdmin) > 0) { ?>
                                            <?php foreach ($historialAdmin as $historial) { ?>
                                                <tr>
                                                    <td>#<?php echo (int) ($historial['id_historial'] ?? 0); ?></td>
                                                    <td><?php echo htmlspecialchars($historial['nombre_activo'] ?? 'Sin activo'); ?></td>
                                                    <td><?php echo htmlspecialchars($historial['estado_anterior_nombre'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($historial['nuevo_estado_nombre'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($historial['fecha_movimiento'] ?? ''); ?></td>
                                                    <td class="admin-description-cell"><?php echo htmlspecialchars($historial['observaciones'] ?? 'Sin observaciones'); ?></td>
                                                </tr>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <tr>
                                                <td colspan="6">Todavia no hay reparaciones registradas.</td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </article>

                    <article class="panel-card wide-card dashboard-panel <?php echo $activePanel === 'estado' ? 'is-active' : ''; ?>" id="estado">
                        <div class="panel-heading">
                            <div>
                                <p class="panel-kicker">Estado del inventario</p>
                                <h2>Estados actuales de los equipos</h2>
                            </div>
                            <span class="panel-tag">Inventario</span>
                        </div>
                        <p class="panel-description">Los estados se guardan directamente en cada equipo y no requieren una tabla separada.</p>
                        <div class="users-table-card">
                                <div class="users-table-head">
                                    <h3>Equipos por estado actual</h3>
                                    <p><?php echo count($estados); ?> estados en uso</p>
                                </div>

                                <div class="table-responsive admin-table-wrap">
                                    <table class="table admin-table admin-table-users">
                                        <thead>
                                            <tr>
                                                <th>Estado actual</th>
                                                <th>Cantidad de equipos</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($estados) > 0) { ?>
                                                <?php foreach ($estados as $estado) { ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($estado['nombre_estado'] ?? ''); ?></td>
                                                        <td><?php echo (int) ($estado['cantidad'] ?? 0); ?></td>
                                                    </tr>
                                                <?php } ?>
                                            <?php } else { ?>
                                                <tr>
                                                    <td colspan="2">No hay equipos registrados en la base de datos.</td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                        </div>
                    </article>

                    <article class="panel-card wide-card dashboard-panel <?php echo $activePanel === 'puntos' ? 'is-active' : ''; ?>" id="puntos">
                        <div class="panel-heading">
                            <div>
                                <p class="panel-kicker">Centros de recolección</p>
                                <h2>Puntos de entrega de equipos</h2>
                            </div>
                            <span class="panel-tag">Ubicaciones</span>
                        </div>

                        <?php if ($puntoAdminMessage) { ?>
                            <div class="admin-alert admin-alert-<?php echo in_array($puntoAdminStatus, ['created', 'deleted'], true) ? 'success' : 'error'; ?>">
                                <?php echo htmlspecialchars($puntoAdminMessage); ?>
                            </div>
                        <?php } ?>

                        <div class="users-admin-grid">
                            <div class="users-form-card">
                                <div class="users-form-head">
                                    <h3>Registrar un punto</h3>
                                    <p>Publica solo ubicaciones y horarios confirmados para recibir equipos.</p>
                                </div>
                                <form action="admin_panel.php?panel=puntos" method="POST" class="admin-user-form">
                                    <input type="hidden" name="punto_action" value="create" />
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>" />
                                    <div class="form-field">
                                        <label for="punto_nombre">Nombre del punto</label>
                                        <input id="punto_nombre" name="nombre" type="text" class="admin-input" maxlength="120" required />
                                    </div>
                                    <div class="form-field">
                                        <label for="punto_ciudad">Ciudad</label>
                                        <select id="punto_ciudad" name="ciudad_id" class="admin-input admin-select">
                                            <option value="0">Selecciona una ciudad</option>
                                            <?php foreach ($ciudadesRecoleccion as $ciudad) { ?>
                                                <option value="<?php echo (int) $ciudad['ciudad_id']; ?>">
                                                    <?php echo htmlspecialchars($ciudad['nombre'] . ', ' . $ciudad['departamento']); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="form-field">
                                        <label for="punto_direccion">Dirección</label>
                                        <input id="punto_direccion" name="direccion" type="text" class="admin-input" maxlength="250" required />
                                    </div>
                                    <div class="form-field">
                                        <label for="punto_horario">Horario de atención</label>
                                        <input id="punto_horario" name="horario" type="text" class="admin-input" maxlength="150"
                                            placeholder="Lunes a viernes, 9:00 a. m. - 5:00 p. m." required />
                                    </div>
                                    <div class="form-field">
                                        <label for="punto_instrucciones">Instrucciones adicionales</label>
                                        <textarea id="punto_instrucciones" name="instrucciones" class="admin-input admin-textarea"
                                            maxlength="250" rows="3"></textarea>
                                    </div>
                                    <div class="admin-form-actions">
                                        <button type="submit" class="btn-admin btn-admin-primary">Guardar punto</button>
                                    </div>
                                </form>
                            </div>

                            <div class="users-table-card">
                                <div class="users-table-head">
                                    <h3>Puntos publicados</h3>
                                    <p><?php echo count($puntosRecoleccion); ?> ubicaciones registradas</p>
                                </div>
                                <div class="table-responsive admin-table-wrap">
                                    <table class="table admin-table admin-table-users">
                                        <thead>
                                            <tr>
                                                <th>Punto</th>
                                                <th>Ciudad</th>
                                                <th>Dirección y horario</th>
                                                <th>Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($puntosRecoleccion) { ?>
                                                <?php foreach ($puntosRecoleccion as $punto) { ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($punto['nombre']); ?></td>
                                                        <td><?php echo htmlspecialchars($punto['ciudad']); ?></td>
                                                        <td>
                                                            <?php echo htmlspecialchars($punto['direccion']); ?><br />
                                                            <small><?php echo htmlspecialchars($punto['horario']); ?></small>
                                                        </td>
                                                        <td>
                                                            <form method="POST" action="admin_panel.php?panel=puntos">
                                                                <input type="hidden" name="punto_action" value="delete" />
                                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>" />
                                                                <input type="hidden" name="punto_id" value="<?php echo (int) $punto['punto_id']; ?>" />
                                                                <button type="submit" class="btn-table-action btn-table-delete" data-confirm-delete>Eliminar</button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            <?php } else { ?>
                                                <tr><td colspan="4">Aún no hay ubicaciones. Agrega puntos verificados para que aparezcan en el portal.</td></tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <div class="secondary-column">
                    <article class="panel-card mini-card">
                        <div class="panel-heading">
                            <div>
                                <p class="panel-kicker">Atajos</p>
                                <h2>Accesos rapidos</h2>
                            </div>
                        </div>

                        <div class="quick-links">
                            <a href="index.php" class="quick-link-card">
                                <i class="fas fa-globe"></i>
                                <span>Ver sitio publico</span>
                            </a>
                            <a href="contacto.html" class="quick-link-card">
                                <i class="fas fa-envelope"></i>
                                <span>Ir a contacto</span>
                            </a>
                            <a href="registro_user.php" class="quick-link-card">
                                <i class="fas fa-user-plus"></i>
                                <span>Registrar usuario</span>
                            </a>
                        </div>
                    </article>

                    <article class="panel-card mini-card">
                        <div class="panel-heading">
                            <div>
                                <p class="panel-kicker">Resumen rapido</p>
                                <h2>Estado del sistema</h2>
                            </div>
                        </div>

                        <div class="summary-list">
                            <div class="summary-row">
                                <span>Base de datos</span>
                                <strong>Conectada</strong>
                            </div>
                            <div class="summary-row">
                                <span>Sesion admin</span>
                                <strong>Activa</strong>
                            </div>
                            <div class="summary-row">
                                <span>Panel visible</span>
                                <strong>Dinamico</strong>
                            </div>
                        </div>
                    </article>
                </div>
            </section>
        </main>
    </div>

    <?php

if (!isset($adminChartData['usuarios'])) {
    $adminChartData['usuarios'] = [
        'labels' => ['Sin datos'],
        'values' => [0]
    ];
}
?>

<script>
    window.ADMIN_CHART_DATA = <?php echo json_encode($adminChartData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>

<!-- Librerías -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<!-- Tus scripts -->
<script src="../Js/admin_panel_cantidad_equipos.js"></script>
<script src="../Js/admin_panel_cantidad_ubicaciones.js"></script>
<script src="../Js/admin_panel_cantidad_estados.js"></script>
<script src="../Js/admin_panel_cantidad_usuarios.js"></script>
<script src="../Js/admin_panel.js"></script>
</body>

</html>
