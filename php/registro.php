<?php
session_start();
require_once __DIR__ . '/conexion.php';

$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim(
    ($_POST['primer_apellido'] ?? '') . ' ' .
    ($_POST['segundo_apellido'] ?? '')
);
$correo = trim($_POST['correo'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$contrasena = $_POST['contrasena'] ?? '';
$rol = trim($_POST['rol'] ?? 'Usuario');

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    $nombre === '' ||
    $apellido === '' ||
    !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
    strlen($correo) > 150 ||
    $telefono === '' ||
    strlen($telefono) > 20 ||
    $contrasena === '' ||
    !in_array($rol, ['Usuario', 'Vendedor'], true)
) {
    header("Location: ../html/registro_user.php?status=incomplete");
    exit();
}

$actorRegistro = mb_substr('Registro web · ' . $correo, 0, 100, 'UTF-8');
ecotechSetAuditActor($conn, $actorRegistro);
$hash = password_hash($contrasena, PASSWORD_DEFAULT);
$stmt = $conn->prepare("
    INSERT INTO `Usuarios` (nombre, apellido, email, telefono, rol, password_hash)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("ssssss", $nombre, $apellido, $correo, $telefono, $rol, $hash);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: ../html/registro_user.php?status=user_create");
    exit();
}

$status = $stmt->errno === 1062 ? 'duplicate_email' : 'failed';
$stmt->close();
$conn->close();
header("Location: ../html/registro_user.php?status=$status");
exit();
