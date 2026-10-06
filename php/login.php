<?php
const ECOTECH_LOGIN_ANONYMOUS_AUDIT_ACTOR = 'Web sin autenticar';
const ECOTECH_LOGIN_AUDIT_FAILURE_MESSAGE = 'No se pudo registrar el intento de acceso.';

session_start();
require_once __DIR__ . '/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../html/login_user.php");
    exit();
}

$correo = trim($_POST['correo'] ?? '');
$contrasena = $_POST['contrasena'] ?? '';

if ($correo === '' || $contrasena === '') {
    try {
        ecotechSetAuditActor($conn, ECOTECH_LOGIN_ANONYMOUS_AUDIT_ACTOR);
        ecotechLogAuditEvent($conn, 'Autenticacion', 'LOGIN_FAIL', null, 'Intento con campos incompletos');
    } catch (RuntimeException $error) {
        error_log('Incomplete login audit failed: ' . $error->getMessage());
        http_response_code(503);
        exit(ECOTECH_LOGIN_AUDIT_FAILURE_MESSAGE);
    }
    header("Location: ../html/login_user.php?status=error_data");
    exit();
}

$stmt = $conn->prepare("
    SELECT usuario_id, nombre, apellido, email, rol, activo, password_hash
    FROM `Usuarios`
    WHERE email = ?
    LIMIT 1
");
$stmt->bind_param("s", $correo);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$usuario) {
    try {
        ecotechSetAuditActor($conn, ECOTECH_LOGIN_ANONYMOUS_AUDIT_ACTOR);
        ecotechLogAuditEvent($conn, 'Autenticacion', 'LOGIN_FAIL', null, 'Credenciales rechazadas');
    } catch (RuntimeException $error) {
        error_log('Rejected login audit failed: ' . $error->getMessage());
        http_response_code(503);
        exit(ECOTECH_LOGIN_AUDIT_FAILURE_MESSAGE);
    }
    $conn->close();
    header("Location: ../html/login_user.php?status=error_user");
    exit();
}

if ((int) $usuario['activo'] !== 1) {
    try {
        ecotechSetAuditActor($conn, ECOTECH_LOGIN_ANONYMOUS_AUDIT_ACTOR);
        ecotechLogAuditEvent($conn, 'Autenticacion', 'LOGIN_FAIL', (int) $usuario['usuario_id'], 'Intento de acceso a una cuenta inactiva');
    } catch (RuntimeException $error) {
        error_log('Inactive login audit failed: ' . $error->getMessage());
        http_response_code(503);
        exit(ECOTECH_LOGIN_AUDIT_FAILURE_MESSAGE);
    }
    $conn->close();
    header("Location: ../html/login_user.php?status=account_inactive");
    exit();
}

$hashAlmacenado = (string) $usuario['password_hash'];
$contrasenaValida = password_verify($contrasena, $hashAlmacenado);
$hashLegado = hash('sha256', $contrasena);

if (!$contrasenaValida && hash_equals($hashAlmacenado, $hashLegado)) {
    $contrasenaValida = true;
    ecotechSetAuditActor($conn, 'Autenticación web · #' . (int) $usuario['usuario_id']);
    $hashNuevo = password_hash($contrasena, PASSWORD_DEFAULT);
    $stmtActualizar = $conn->prepare("
        UPDATE `Usuarios` SET password_hash = ? WHERE usuario_id = ?
    ");
    $usuarioId = (int) $usuario['usuario_id'];
    $stmtActualizar->bind_param("si", $hashNuevo, $usuarioId);
    if (!$stmtActualizar->execute()) {
        $error = $stmtActualizar->error;
        $stmtActualizar->close();
        $conn->close();
        die("Error al actualizar la contraseña almacenada: " . $error);
    }
    $stmtActualizar->close();
}

if (!$contrasenaValida) {
    try {
        ecotechSetAuditActor($conn, ECOTECH_LOGIN_ANONYMOUS_AUDIT_ACTOR);
        ecotechLogAuditEvent($conn, 'Autenticacion', 'LOGIN_FAIL', (int) $usuario['usuario_id'], 'Credenciales rechazadas');
    } catch (RuntimeException $error) {
        error_log('Rejected login audit failed: ' . $error->getMessage());
        http_response_code(503);
        exit(ECOTECH_LOGIN_AUDIT_FAILURE_MESSAGE);
    }
    $conn->close();
    header("Location: ../html/login_user.php?status=error_pass");
    exit();
}

session_regenerate_id(true);
$_SESSION['usuario'] = [
    'id' => (int) $usuario['usuario_id'],
    'nombre' => $usuario['nombre'] ?? '',
    'apellido' => $usuario['apellido'] ?? '',
    'correo' => $usuario['email'] ?? '',
    'rol' => $usuario['rol'] ?? ''
];

try {
    ecotechSetAuditActor($conn);
    ecotechLogAuditEvent(
        $conn,
        'Autenticacion',
        'LOGIN',
        (int) $usuario['usuario_id'],
        'Inicio de sesión exitoso'
    );
} catch (RuntimeException $error) {
    error_log('Successful login audit failed: ' . $error->getMessage());
    http_response_code(503);
    exit('No se pudo registrar el inicio de sesión. Inténtalo de nuevo más tarde.');
}
$conn->close();

if (strcasecmp((string) $usuario['rol'], 'Administrador') === 0) {
    header("Location: ../html/admin_panel.php");
    exit();
}

if (in_array($usuario['rol'], ['Usuario', 'Vendedor'], true)) {
    header("Location: ../html/user_panel.php");
    exit();
}

if (strcasecmp((string) $usuario['rol'], 'Operador') === 0) {
    header("Location: ../html/operator_panel.php");
    exit();
}

if (strcasecmp((string) $usuario['rol'], 'Tecnico') === 0) {
    header("Location: ../html/tecnico.php");
    exit();
}

if (strcasecmp((string) $usuario['rol'], 'Auditor') === 0) {
    header("Location: ../html/auditor_panel.php");
    exit();
}

header("Location: ../html/index.php");
exit();
