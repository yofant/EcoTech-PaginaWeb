<?php
$resultadoEquiposChart = $conn->query("
    SELECT COALESCE(t.nombre, 'Sin tipo') AS etiqueta, COUNT(*) AS total
    FROM `Equipos` e
    LEFT JOIN `TiposEquipo` t ON t.tipo_id = e.tipo_id
    GROUP BY COALESCE(t.nombre, 'Sin tipo')
    ORDER BY total DESC, etiqueta ASC
    LIMIT 6
");

if (!$resultadoEquiposChart) {
    die("Error al consultar los tipos de equipo: " . $conn->error);
}

while ($fila = $resultadoEquiposChart->fetch_assoc()) {
    $adminChartData['equipos']['labels'][] = (string) $fila['etiqueta'];
    $adminChartData['equipos']['values'][] = (int) $fila['total'];
}
if ($adminChartData['equipos']['labels'] === []) {
    $adminChartData['equipos'] = adminChartFallback('Sin equipos');
}
?>
