<?php
$puntoAdminMessage = null;
$puntosRecoleccion = [];
$ciudadesRecoleccion = [];
$puntoAdminStatus = $_GET['tipo_status'] ?? '';

if ($puntoAdminStatus === 'created') {
    $puntoAdminMessage = 'El punto de entrega quedó registrado.';
} elseif ($puntoAdminStatus === 'deleted') {
    $puntoAdminMessage = 'El punto de entrega fue eliminado.';
} elseif ($puntoAdminStatus === 'invalid') {
    $puntoAdminMessage = 'Completa nombre, dirección y horario.';
} elseif ($puntoAdminStatus === 'db_error') {
    $puntoAdminMessage = 'No se pudo completar la operación con la base de datos.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['punto_action'])) {
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');
    if (!isset($_SESSION['csrf_token']) || !hash_equals((string) $_SESSION['csrf_token'], $csrfToken)) {
        die('La sesión del formulario venció. Recarga la página e inténtalo de nuevo.');
    }

    if ($_POST['punto_action'] === 'create') {
        $nombre = trim($_POST['nombre'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $horario = trim($_POST['horario'] ?? '');
        $instrucciones = trim($_POST['instrucciones'] ?? '');
        $ciudadId = (int) ($_POST['ciudad_id'] ?? 0);

        if (
            $nombre === '' || strlen($nombre) > 120 ||
            $direccion === '' || strlen($direccion) > 250 ||
            $horario === '' || strlen($horario) > 150 ||
            strlen($instrucciones) > 250
        ) {
            header('Location: admin_panel.php?panel=puntos&tipo_status=invalid');
            exit();
        }

        $stmt = $conn->prepare("
            INSERT INTO `PuntosRecoleccion` (nombre, ciudad_id, direccion, horario, instrucciones)
            VALUES (?, NULLIF(?, 0), ?, ?, NULLIF(?, ''))
        ");
        $stmt->bind_param('sisss', $nombre, $ciudadId, $direccion, $horario, $instrucciones);
        if (!$stmt->execute()) {
            $stmt->close();
            header('Location: admin_panel.php?panel=puntos&tipo_status=db_error');
            exit();
        }
        $stmt->close();
        header('Location: admin_panel.php?panel=puntos&tipo_status=created');
        exit();
    }

    if ($_POST['punto_action'] === 'delete') {
        $puntoId = (int) ($_POST['punto_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM `PuntosRecoleccion` WHERE punto_id = ?");
        $stmt->bind_param('i', $puntoId);
        if (!$stmt->execute()) {
            $stmt->close();
            header('Location: admin_panel.php?panel=puntos&tipo_status=db_error');
            exit();
        }
        $deleted = $stmt->affected_rows > 0;
        $stmt->close();
        header('Location: admin_panel.php?panel=puntos&tipo_status=' . ($deleted ? 'deleted' : 'db_error'));
        exit();
    }
}

$resultadoCiudades = $conn->query("SELECT ciudad_id, nombre, departamento FROM `Ciudades` ORDER BY nombre");
if (!$resultadoCiudades) {
    die('Error al consultar ciudades: ' . $conn->error);
}
while ($ciudad = $resultadoCiudades->fetch_assoc()) {
    $ciudadesRecoleccion[] = $ciudad;
}

$resultadoPuntos = $conn->query("
    SELECT p.punto_id, p.nombre, p.direccion, p.horario, p.instrucciones, p.activo,
           COALESCE(c.nombre, 'Sin ciudad') AS ciudad
    FROM `PuntosRecoleccion` p
    LEFT JOIN `Ciudades` c ON c.ciudad_id = p.ciudad_id
    ORDER BY p.activo DESC, c.nombre, p.nombre
");
if (!$resultadoPuntos) {
    die('Error al consultar puntos de entrega: ' . $conn->error);
}
while ($punto = $resultadoPuntos->fetch_assoc()) {
    $puntosRecoleccion[] = $punto;
}
