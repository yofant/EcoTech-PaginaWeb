<?php
session_start();

if (!empty($_SESSION['usuario']['correo'])) {
    require_once __DIR__ . '/conexion.php';
    try {
        ecotechLogAuditEvent(
            $conn,
            'Autenticacion',
            'LOGOUT',
            isset($_SESSION['usuario']['id']) ? (int) $_SESSION['usuario']['id'] : null,
            'Cierre de sesión'
        );
    } catch (RuntimeException $error) {
        error_log('Logout audit failed: ' . $error->getMessage());
        http_response_code(503);
        exit('No se pudo registrar el cierre de sesión. Inténtalo de nuevo.');
    }
}

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

header("Location: ../html/login_user.php?status=logout");
exit();
