<?php
$servername = "localhost";   // Servidor MySQL
$username   = "root";       // Usuario MySQL
$password   = "";           // Contraseña MySQL
$database   = "ecotech";    // Nombre de la base

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

if (!$conn->set_charset("utf8mb4")) {
    die("Error al configurar la codificación de la base de datos: " . $conn->error);
}

require_once __DIR__ . '/php/auditoria.php';
try {
    ecotechSetAuditActor($conn);
} catch (RuntimeException $error) {
    http_response_code(503);
    die($error->getMessage());
}