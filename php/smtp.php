<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();
require_once __DIR__ . '/conexion.php';
ob_start();

require __DIR__ . '/../vendor/autoload.php';

function ecotech_redirect_to_index(string $query): void
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '/php/smtp.php';
    $base   = rtrim(str_replace('\\', '/', dirname(dirname($script))), '/');
    if ($base === '/' || $base === '') {
        $base = '';
    }
    $path = ($base === '' ? '' : $base) . '/html/index.php' . $query;
    header('Location: ' . $scheme . '://' . $host . $path, true, 303);
}

// Validar que el formulario se haya enviado correctamente
if (empty($_POST['nombre']) || empty($_POST['email']) || empty($_POST['mensaje'])) {
    ob_end_clean();
    ecotech_redirect_to_index('?status=incomplete');
    exit();
}

$nombre  = trim($_POST['nombre']);
$email   = trim($_POST['email']);
$mensaje = trim($_POST['mensaje']);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    ob_end_clean();
    ecotech_redirect_to_index('?status=invalid_email');
    exit();
}

$actorContacto = mb_substr('Contacto web · ' . $email, 0, 100, 'UTF-8');
ecotechSetAuditActor($conn, $actorContacto);
try {
    ecotechLogAuditEvent($conn, 'Contacto', 'ENVIO', null, 'Formulario de contacto recibido de ' . $nombre);
} catch (RuntimeException $e) {
    ob_end_clean();
    http_response_code(503);
    exit('No se pudo registrar la solicitud de contacto. Inténtalo de nuevo más tarde.');
}

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'email-smtp.us-east-2.amazonaws.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'AKIA52ZYEPYVA4PUWUA4';
    $mail->Password   = 'BG/WqQkO9SPh5I1njS8Y5FbSHf2ASj2VHnPxjVJnARex';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('yofan.tellez@cun.edu.co', 'Formulario Web');
    $mail->addAddress('yofan.tellez@cun.edu.co');

    $mail->isHTML(true);
    $mail->Subject = 'Nuevo mensaje de Ecotech WEB';
    $mail->Body    = "
        <h3>Nuevo mensaje</h3>
        <p><strong>Nombre:</strong> " . htmlspecialchars($nombre, ENT_QUOTES | ENT_HTML5, 'UTF-8') . "</p>
        <p><strong>Email:</strong> " . htmlspecialchars($email, ENT_QUOTES | ENT_HTML5, 'UTF-8') . "</p>
        <p><strong>Mensaje:</strong><br>" . nl2br(htmlspecialchars($mensaje, ENT_QUOTES | ENT_HTML5, 'UTF-8')) . "</p>
    ";

    $mail->send();
    ob_end_clean();
    ecotech_redirect_to_index('?status=contact_sent');
    exit();
} catch (Throwable $e) {
    ob_end_clean();
    ecotech_redirect_to_index('?status=contact_error');
    exit();
}
