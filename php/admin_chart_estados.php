<?php
$resultadoEstadosChart = $conn->query("
    SELECT estado_actual AS etiqueta, COUNT(*) AS total
    FROM `Equipos`
    GROUP BY estado_actual
    ORDER BY total DESC, etiqueta ASC
    LIMIT 6
");

if (!$resultadoEstadosChart) {
    die("Error al consultar los estados de equipos: " . $conn->error);
}

while ($fila = $resultadoEstadosChart->fetch_assoc()) {
    $adminChartData['estados']['labels'][] = (string) $fila['etiqueta'];
    $adminChartData['estados']['values'][] = (int) $fila['total'];
}
if ($adminChartData['estados']['labels'] === []) {
    $adminChartData['estados'] = adminChartFallback('Sin estados');
}
?>
