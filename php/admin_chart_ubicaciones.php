<?php
$resultadoUbicacionesChart = $conn->query("
    SELECT COALESCE(c.nombre, 'Sin ciudad') AS etiqueta, COUNT(*) AS total
    FROM `Equipos` e
    LEFT JOIN `Donantes` d ON d.donante_id = e.donante_id
    LEFT JOIN `Ciudades` c ON c.ciudad_id = d.ciudad_id
    GROUP BY COALESCE(c.nombre, 'Sin ciudad')
    ORDER BY total DESC, etiqueta ASC
    LIMIT 6
");

if (!$resultadoUbicacionesChart) {
    die("Error al consultar la distribucion de equipos por ciudad: " . $conn->error);
}

while ($fila = $resultadoUbicacionesChart->fetch_assoc()) {
    $adminChartData['ubicaciones']['labels'][] = (string) $fila['etiqueta'];
    $adminChartData['ubicaciones']['values'][] = (int) $fila['total'];
}
if ($adminChartData['ubicaciones']['labels'] === []) {
    $adminChartData['ubicaciones'] = adminChartFallback('Sin ubicaciones');
}
?>
