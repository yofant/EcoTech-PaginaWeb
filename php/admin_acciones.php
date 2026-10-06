<?php
if (!defined('ECOTECH_ADMIN_ACCIONES')) {
    define('ECOTECH_ADMIN_ACCIONES', true);

    $crudAccionMessage = null;
    $crudAccionMessageType = null;
    $accionesMetricas = [
        'activos' => 0,
        'reportes' => 0,
        'movimientos' => 0
    ];
    $activosAdmin = [];
    $reportesAdmin = [];
    $historialAdmin = [];

    $resultadoAccionesMetricas = $conn->query("
        SELECT
            (SELECT COUNT(*) FROM `Equipos`) AS total_equipos,
            (SELECT COUNT(*) FROM `Diagnosticos`) AS total_diagnosticos,
            (SELECT COUNT(*) FROM `Reparaciones`) AS total_reparaciones
    ");
    if (!$resultadoAccionesMetricas) {
        die("Error al consultar las metricas: " . $conn->error);
    }
    $filaAccionesMetricas = $resultadoAccionesMetricas->fetch_assoc();
    $accionesMetricas['activos'] = (int) ($filaAccionesMetricas['total_equipos'] ?? 0);
    $accionesMetricas['reportes'] = (int) ($filaAccionesMetricas['total_diagnosticos'] ?? 0);
    $accionesMetricas['movimientos'] = (int) ($filaAccionesMetricas['total_reparaciones'] ?? 0);

    $resultadoEquipos = $conn->query("
        SELECT
            e.equipo_id AS id_activo,
            COALESCE(e.serial, 'Sin serial') AS codigo_qr,
            CONCAT_WS(' ', e.marca, e.modelo) AS nombre_activo,
            COALESCE(t.nombre, 'Sin tipo') AS categoria,
            e.estado_actual AS estado,
            COALESCE(c.nombre, 'Sin ciudad') AS ubicacion,
            COALESCE(d.nombre, 'Sin donante') AS empresa
        FROM `Equipos` e
        LEFT JOIN `TiposEquipo` t ON t.tipo_id = e.tipo_id
        LEFT JOIN `Donantes` d ON d.donante_id = e.donante_id
        LEFT JOIN `Ciudades` c ON c.ciudad_id = d.ciudad_id
        ORDER BY e.equipo_id DESC
        LIMIT 50
    ");
    if (!$resultadoEquipos) {
        die("Error al consultar equipos: " . $conn->error);
    }
    while ($filaEquipo = $resultadoEquipos->fetch_assoc()) {
        $activosAdmin[] = $filaEquipo;
    }

    $resultadoDiagnosticos = $conn->query("
        SELECT
            d.diagnostico_id AS id_reporte,
            CONCAT('Diagnostico de ', COALESCE(e.modelo, e.marca, CONCAT('equipo #', e.equipo_id))) AS titulo,
            COALESCE(d.descripcion, 'Sin descripcion') AS descripcion,
            d.fecha AS fecha_generacion,
            CONCAT_WS(' ', u.nombre, u.apellido) AS generado_por_nombre
        FROM `Diagnosticos` d
        INNER JOIN `Equipos` e ON e.equipo_id = d.equipo_id
        INNER JOIN `Usuarios` u ON u.usuario_id = d.tecnico_id
        ORDER BY d.diagnostico_id DESC
        LIMIT 50
    ");
    if (!$resultadoDiagnosticos) {
        die("Error al consultar diagnosticos: " . $conn->error);
    }
    while ($filaDiagnostico = $resultadoDiagnosticos->fetch_assoc()) {
        $reportesAdmin[] = $filaDiagnostico;
    }

    $resultadoReparaciones = $conn->query("
        SELECT
            r.reparacion_id AS id_historial,
            CONCAT_WS(' ', e.marca, e.modelo) AS nombre_activo,
            COALESCE(r.estado, 'Reparacion') AS estado_anterior_nombre,
            e.estado_actual AS nuevo_estado_nombre,
            r.fecha_inicio AS fecha_movimiento,
            CONCAT_WS(' - ', r.descripcion, r.repuestos_usados) AS observaciones
        FROM `Reparaciones` r
        INNER JOIN `Equipos` e ON e.equipo_id = r.equipo_id
        ORDER BY r.reparacion_id DESC
        LIMIT 50
    ");
    if (!$resultadoReparaciones) {
        die("Error al consultar reparaciones: " . $conn->error);
    }
    while ($filaReparacion = $resultadoReparaciones->fetch_assoc()) {
        $historialAdmin[] = $filaReparacion;
    }
}
?>
