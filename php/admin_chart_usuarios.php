<?php
$resultadoUsuariosChart = $conn->query("
    SELECT rol AS etiqueta, COUNT(*) AS total
    FROM `Usuarios`
    GROUP BY rol
    ORDER BY total DESC, etiqueta ASC
");

if (!$resultadoUsuariosChart) {
    die("Error al consultar los roles de usuarios: " . $conn->error);
}

while ($fila = $resultadoUsuariosChart->fetch_assoc()) {
    $adminChartData['usuarios']['labels'][] = ucfirst((string) $fila['etiqueta']);
    $adminChartData['usuarios']['values'][] = (int) $fila['total'];
}
if ($adminChartData['usuarios']['labels'] === []) {
    $adminChartData['usuarios'] = adminChartFallback('Sin usuarios');
}
?>
