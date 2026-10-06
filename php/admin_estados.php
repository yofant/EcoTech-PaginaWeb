<?php
if (!defined('ECOTECH_ADMIN_ESTADOS')) {
    define('ECOTECH_ADMIN_ESTADOS', true);
    $estados = [];

    $resultadoEstados = $conn->query("
        SELECT estado_actual AS nombre_estado, COUNT(*) AS cantidad
        FROM `Equipos`
        GROUP BY estado_actual
        ORDER BY cantidad DESC, estado_actual ASC
    ");
    if (!$resultadoEstados) {
        die("Error al consultar estados de equipos: " . $conn->error);
    }
    while ($filaEstado = $resultadoEstados->fetch_assoc()) {
        $estados[] = $filaEstado;
    }
}
?>
