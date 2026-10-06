<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function portalRespond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

function portalPrepare(mysqli $conn, string $sql): mysqli_stmt
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        portalRespond(500, ['error' => 'No fue posible preparar la consulta.']);
    }
    return $stmt;
}

require_once __DIR__ . '/conexion.php';

if (!isset($_SESSION['usuario']['correo'])) {
    portalRespond(401, ['error' => 'Inicia sesión para continuar.']);
}

$correoSesion = (string) $_SESSION['usuario']['correo'];
$stmtUsuario = portalPrepare($conn, "
    SELECT usuario_id, nombre, apellido, email, rol, activo
    FROM `Usuarios`
    WHERE email = ?
    LIMIT 1
");
$stmtUsuario->bind_param('s', $correoSesion);
$stmtUsuario->execute();
$usuario = $stmtUsuario->get_result()->fetch_assoc();
$stmtUsuario->close();

if (!$usuario || (int) $usuario['activo'] !== 1) {
    portalRespond(403, ['error' => 'La cuenta no está activa.']);
}

$usuarioId = (int) $usuario['usuario_id'];
$rol = (string) $usuario['rol'];
$_SESSION['usuario']['id'] = $usuarioId;
$_SESSION['usuario']['rol'] = $rol;

$esOperador = strcasecmp($rol, 'Operador') === 0;
$esUsuarioPortal = in_array($rol, ['Usuario', 'Vendedor'], true);
if (!$esOperador && !$esUsuarioPortal) {
    portalRespond(403, ['error' => 'Este perfil no tiene acceso al portal de usuarios.']);
}

function portalConversation(mysqli $conn, int $conversationId): ?array
{
    global $usuarioId, $esOperador, $esUsuarioPortal;

    $stmt = portalPrepare($conn, "
        SELECT conversacion_id, usuario_id, interlocutor_id, tipo, equipo_id
        FROM `Conversaciones`
        WHERE conversacion_id = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $conversationId);
    $stmt->execute();
    $conversation = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $authorized = $conversation !== null;
    if ($authorized) {
        $isParticipant = (int) $conversation['usuario_id'] === $usuarioId
            || (int) $conversation['interlocutor_id'] === $usuarioId;
        $isAssignedOperatorConversation = !$esOperador
            || ((int) $conversation['interlocutor_id'] === $usuarioId && $conversation['tipo'] === 'operador');
        $isUserPortalConversation = !$esUsuarioPortal
            || $conversation['tipo'] !== 'operador'
            || (int) $conversation['usuario_id'] === $usuarioId
            || (int) $conversation['interlocutor_id'] === $usuarioId;
        $authorized = $isParticipant && $isAssignedOperatorConversation && $isUserPortalConversation;
    }

    return $authorized ? $conversation : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'initial';
    if ($action === 'messages') {
        $conversationId = filter_input(INPUT_GET, 'conversation_id', FILTER_VALIDATE_INT);
        $conversation = $conversationId ? portalConversation($conn, $conversationId) : null;
        if (!$conversation) {
            portalRespond(404, ['error' => 'No se encontró la conversación o no tienes acceso.']);
        }

        $stmtMensajes = portalPrepare($conn, "
            SELECT m.mensaje_id, m.emisor_id, m.contenido, m.fecha_envio,
                   CONCAT_WS(' ', u.nombre, u.apellido) AS emisor_nombre
            FROM `Mensajes` m
            INNER JOIN `Usuarios` u ON u.usuario_id = m.emisor_id
            WHERE m.conversacion_id = ?
            ORDER BY m.mensaje_id DESC
            LIMIT 100
        ");
        $stmtMensajes->bind_param('i', $conversationId);
        $stmtMensajes->execute();
        $resultadoMensajes = $stmtMensajes->get_result();
        $mensajes = [];
        while ($mensaje = $resultadoMensajes->fetch_assoc()) {
            $mensaje['mensaje_id'] = (int) $mensaje['mensaje_id'];
            $mensaje['emisor_id'] = (int) $mensaje['emisor_id'];
            $mensajes[] = $mensaje;
        }
        $stmtMensajes->close();
        $mensajes = array_reverse($mensajes);

        $stmtLeidos = portalPrepare($conn, "
            UPDATE `Mensajes`
            SET leido = 1
            WHERE conversacion_id = ? AND emisor_id <> ?
        ");
        $stmtLeidos->bind_param('ii', $conversationId, $usuarioId);
        $stmtLeidos->execute();
        $stmtLeidos->close();
        portalRespond(200, ['messages' => $mensajes]);
    }

    if ($action !== 'initial') {
        portalRespond(400, ['error' => 'La acción solicitada no es válida.']);
    }

    $tipos = [];
    $resultadoTipos = $conn->query("SELECT tipo_id, nombre FROM `TiposEquipo` ORDER BY nombre");
    if (!$resultadoTipos) {
        portalRespond(500, ['error' => 'No fue posible cargar los tipos de equipo.']);
    }
    while ($tipo = $resultadoTipos->fetch_assoc()) {
        $tipos[] = ['id' => (int) $tipo['tipo_id'], 'nombre' => $tipo['nombre']];
    }

    $equipos = [];
    $resultadoEquipos = $conn->query("
        SELECT e.equipo_id, e.tipo_id, t.nombre AS tipo_nombre, e.marca, e.modelo,
               e.descripcion, e.estado_actual, e.fecha_recepcion,
               e.usuario_id AS vendedor_id,
               CONCAT_WS(' ', u.nombre, u.apellido) AS vendedor_nombre,
               (e.usuario_id = $usuarioId) AS es_mio
        FROM `Equipos` e
        INNER JOIN `TiposEquipo` t ON t.tipo_id = e.tipo_id
        INNER JOIN `Usuarios` u ON u.usuario_id = e.usuario_id
        WHERE e.publicado = 1 AND e.estado_actual = 'Publicado'
          AND u.rol = 'Vendedor' AND u.activo = 1
        ORDER BY e.fecha_recepcion DESC, e.equipo_id DESC
        LIMIT 100
    ");
    if (!$resultadoEquipos) {
        portalRespond(500, ['error' => 'No fue posible cargar los equipos publicados.']);
    }
    while ($equipo = $resultadoEquipos->fetch_assoc()) {
        foreach (['equipo_id', 'tipo_id', 'vendedor_id', 'es_mio'] as $numericKey) {
            $equipo[$numericKey] = (int) $equipo[$numericKey];
        }
        $equipos[] = $equipo;
    }

    $puntos = [];
    $resultadoPuntos = $conn->query("
        SELECT p.punto_id, p.nombre, p.direccion, p.horario, p.instrucciones,
               COALESCE(c.nombre, 'Ciudad por confirmar') AS ciudad,
               COALESCE(c.departamento, '') AS departamento
        FROM `PuntosRecoleccion` p
        LEFT JOIN `Ciudades` c ON c.ciudad_id = p.ciudad_id
        WHERE p.activo = 1
        ORDER BY c.nombre, p.nombre
    ");
    if (!$resultadoPuntos) {
        portalRespond(500, ['error' => 'No fue posible cargar los puntos de recogida.']);
    }
    while ($punto = $resultadoPuntos->fetch_assoc()) {
        $punto['punto_id'] = (int) $punto['punto_id'];
        $puntos[] = $punto;
    }

    $operadores = [];
    if ($esUsuarioPortal) {
        $resultadoOperadores = $conn->query("
            SELECT usuario_id, nombre, apellido
            FROM `Usuarios`
            WHERE rol = 'Operador' AND activo = 1
            ORDER BY nombre, apellido
        ");
        if (!$resultadoOperadores) {
            portalRespond(500, ['error' => 'No fue posible cargar el equipo de recogidas.']);
        }
        while ($operador = $resultadoOperadores->fetch_assoc()) {
            $operador['usuario_id'] = (int) $operador['usuario_id'];
            $operadores[] = $operador;
        }
    }

    $conversaciones = [];
    $sqlConversaciones = $esOperador
        ? "
            SELECT c.conversacion_id, c.usuario_id AS contacto_id, c.tipo, c.equipo_id,
                   CONCAT_WS(' ', u.nombre, u.apellido) AS contacto_nombre,
                   e.modelo AS equipo_modelo, e.marca AS equipo_marca,
                   (SELECT m.contenido FROM `Mensajes` m
                    WHERE m.conversacion_id = c.conversacion_id
                    ORDER BY m.mensaje_id DESC LIMIT 1) AS ultimo_mensaje,
                   (SELECT m.fecha_envio FROM `Mensajes` m
                    WHERE m.conversacion_id = c.conversacion_id
                    ORDER BY m.mensaje_id DESC LIMIT 1) AS ultima_fecha,
                   (SELECT COUNT(*) FROM `Mensajes` m
                    WHERE m.conversacion_id = c.conversacion_id
                      AND m.emisor_id <> ? AND m.leido = 0) AS no_leidos
            FROM `Conversaciones` c
            INNER JOIN `Usuarios` u ON u.usuario_id = c.usuario_id
            LEFT JOIN `Equipos` e ON e.equipo_id = c.equipo_id
            WHERE c.interlocutor_id = ? AND c.tipo = 'operador'
            ORDER BY COALESCE(ultima_fecha, c.fecha_actualizacion) DESC
        "
        : "
            SELECT c.conversacion_id,
                   CASE WHEN c.usuario_id = ? THEN c.interlocutor_id ELSE c.usuario_id END AS contacto_id,
                   c.tipo, c.equipo_id,
                   CASE WHEN c.usuario_id = ? THEN CONCAT_WS(' ', destino.nombre, destino.apellido)
                        ELSE CONCAT_WS(' ', origen.nombre, origen.apellido) END AS contacto_nombre,
                   e.modelo AS equipo_modelo, e.marca AS equipo_marca,
                   (SELECT m.contenido FROM `Mensajes` m
                    WHERE m.conversacion_id = c.conversacion_id
                    ORDER BY m.mensaje_id DESC LIMIT 1) AS ultimo_mensaje,
                   (SELECT m.fecha_envio FROM `Mensajes` m
                    WHERE m.conversacion_id = c.conversacion_id
                    ORDER BY m.mensaje_id DESC LIMIT 1) AS ultima_fecha,
                   (SELECT COUNT(*) FROM `Mensajes` m
                    WHERE m.conversacion_id = c.conversacion_id
                      AND m.emisor_id <> ? AND m.leido = 0) AS no_leidos
            FROM `Conversaciones` c
            INNER JOIN `Usuarios` origen ON origen.usuario_id = c.usuario_id
            INNER JOIN `Usuarios` destino ON destino.usuario_id = c.interlocutor_id
            LEFT JOIN `Equipos` e ON e.equipo_id = c.equipo_id
            WHERE (c.usuario_id = ? OR c.interlocutor_id = ?)
            ORDER BY COALESCE(ultima_fecha, c.fecha_actualizacion) DESC
        ";
    $stmtConversaciones = portalPrepare($conn, $sqlConversaciones);
    if ($esOperador) {
        $stmtConversaciones->bind_param('ii', $usuarioId, $usuarioId);
    } else {
        $stmtConversaciones->bind_param('iiiii', $usuarioId, $usuarioId, $usuarioId, $usuarioId, $usuarioId);
    }
    $stmtConversaciones->execute();
    $resultadoConversaciones = $stmtConversaciones->get_result();
    while ($conversacion = $resultadoConversaciones->fetch_assoc()) {
        foreach (['conversacion_id', 'contacto_id', 'equipo_id', 'no_leidos'] as $numericKey) {
            if ($conversacion[$numericKey] !== null) {
                $conversacion[$numericKey] = (int) $conversacion[$numericKey];
            }
        }
        $conversaciones[] = $conversacion;
    }
    $stmtConversaciones->close();

    portalRespond(200, [
        'user' => [
            'id' => $usuarioId,
            'nombre' => trim($usuario['nombre'] . ' ' . $usuario['apellido']),
            'rol' => $rol
        ],
        'types' => $tipos,
        'equipment' => $equipos,
        'points' => $puntos,
        'operators' => $operadores,
        'conversations' => $conversaciones
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    portalRespond(405, ['error' => 'Método no permitido.']);
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!isset($_SESSION['csrf_token']) || !hash_equals((string) $_SESSION['csrf_token'], $csrfToken)) {
    portalRespond(403, ['error' => 'La sesión del formulario venció. Recarga la página e inténtalo de nuevo.']);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    portalRespond(400, ['error' => 'El cuerpo de la solicitud no es válido.']);
}

$action = $input['action'] ?? '';
if ($action === 'start_chat') {
    if (!$esUsuarioPortal) {
        portalRespond(403, ['error' => 'Solo los usuarios y vendedores pueden iniciar conversaciones.']);
    }

    $type = $input['type'] ?? '';
    $targetId = filter_var($input['target_id'] ?? null, FILTER_VALIDATE_INT);
    $equipmentId = filter_var($input['equipment_id'] ?? null, FILTER_VALIDATE_INT);
    if (!in_array($type, ['vendedor', 'operador'], true) || !$targetId || $targetId === $usuarioId) {
        portalRespond(422, ['error' => 'Selecciona un contacto válido.']);
    }

    if ($type === 'vendedor') {
        if (!$equipmentId) {
            portalRespond(422, ['error' => 'Selecciona un equipo para consultar.']);
        }
        $stmtValidar = portalPrepare($conn, "
            SELECT e.usuario_id
            FROM `Equipos` e
            INNER JOIN `Usuarios` u ON u.usuario_id = e.usuario_id
            WHERE e.equipo_id = ? AND e.usuario_id = ? AND e.publicado = 1
              AND e.estado_actual = 'Publicado' AND u.rol = 'Vendedor' AND u.activo = 1
        ");
        $stmtValidar->bind_param('ii', $equipmentId, $targetId);
    } else {
        $stmtValidar = portalPrepare($conn, "
            SELECT usuario_id
            FROM `Usuarios`
            WHERE usuario_id = ? AND rol = 'Operador' AND activo = 1
        ");
        $stmtValidar->bind_param('i', $targetId);
        $equipmentId = null;
    }
    $stmtValidar->execute();
    $contactoValido = $stmtValidar->get_result()->fetch_assoc();
    $stmtValidar->close();
    if (!$contactoValido) {
        portalRespond(404, ['error' => 'El contacto o equipo seleccionado ya no está disponible.']);
    }

    $equipmentContext = $equipmentId ?? 0;
    $stmtIniciar = portalPrepare($conn, "
        INSERT INTO `Conversaciones` (usuario_id, interlocutor_id, tipo, equipo_id)
        VALUES (?, ?, ?, NULLIF(?, 0))
        ON DUPLICATE KEY UPDATE
            conversacion_id = LAST_INSERT_ID(conversacion_id),
            fecha_actualizacion = CURRENT_TIMESTAMP
    ");
    $stmtIniciar->bind_param('iisi', $usuarioId, $targetId, $type, $equipmentContext);
    if (!$stmtIniciar->execute()) {
        $stmtIniciar->close();
        portalRespond(500, ['error' => 'No fue posible iniciar el chat.']);
    }
    $conversationId = (int) $conn->insert_id;
    $stmtIniciar->close();
    portalRespond(200, ['conversation_id' => $conversationId]);
}

if ($action === 'send_message') {
    $conversationId = filter_var($input['conversation_id'] ?? null, FILTER_VALIDATE_INT);
    $content = trim((string) ($input['content'] ?? ''));
    if (!$conversationId || $content === '' || mb_strlen($content, 'UTF-8') > 2000) {
        portalRespond(422, ['error' => 'El mensaje debe tener entre 1 y 2000 caracteres.']);
    }
    if (!portalConversation($conn, $conversationId)) {
        portalRespond(404, ['error' => 'No se encontró la conversación o no tienes acceso.']);
    }

    $stmtMensaje = portalPrepare($conn, "
        INSERT INTO `Mensajes` (conversacion_id, emisor_id, contenido)
        VALUES (?, ?, ?)
    ");
    $stmtMensaje->bind_param('iis', $conversationId, $usuarioId, $content);
    if (!$stmtMensaje->execute()) {
        $stmtMensaje->close();
        portalRespond(500, ['error' => 'No fue posible enviar el mensaje.']);
    }
    $stmtMensaje->close();
    $stmtActualizar = portalPrepare($conn, "
        UPDATE `Conversaciones`
        SET fecha_actualizacion = CURRENT_TIMESTAMP
        WHERE conversacion_id = ?
    ");
    $stmtActualizar->bind_param('i', $conversationId);
    $stmtActualizar->execute();
    $stmtActualizar->close();
    portalRespond(201, ['sent' => true]);
}

if ($action === 'publish_equipment') {
    if ($rol !== 'Vendedor') {
        portalRespond(403, ['error' => 'Solo las cuentas Vendedor pueden publicar equipos.']);
    }
    $typeId = filter_var($input['type_id'] ?? null, FILTER_VALIDATE_INT);
    $brand = trim((string) ($input['brand'] ?? ''));
    $model = trim((string) ($input['model'] ?? ''));
    $serial = trim((string) ($input['serial'] ?? ''));
    $description = trim((string) ($input['description'] ?? ''));
    if (
        !$typeId || $brand === '' || $model === '' ||
        mb_strlen($brand, 'UTF-8') > 100 ||
        mb_strlen($model, 'UTF-8') > 100 ||
        mb_strlen($serial, 'UTF-8') > 100 ||
        mb_strlen($description, 'UTF-8') > 200
    ) {
        portalRespond(422, ['error' => 'Completa tipo, marca y modelo; revisa el largo de los campos.']);
    }

    $stmtTipo = portalPrepare($conn, "SELECT tipo_id FROM `TiposEquipo` WHERE tipo_id = ?");
    $stmtTipo->bind_param('i', $typeId);
    $stmtTipo->execute();
    $tipoValido = $stmtTipo->get_result()->fetch_assoc();
    $stmtTipo->close();
    if (!$tipoValido) {
        portalRespond(422, ['error' => 'El tipo de equipo seleccionado no existe.']);
    }

    $serialValue = $serial === '' ? null : $serial;
    $stmtPublicar = portalPrepare($conn, "
        INSERT INTO `Equipos`
            (tipo_id, marca, modelo, serial, estado_ingreso, estado_actual, descripcion, usuario_id, publicado)
        VALUES (?, ?, ?, ?, 'Usado', 'Publicado', ?, ?, 1)
    ");
    $stmtPublicar->bind_param('issssi', $typeId, $brand, $model, $serialValue, $description, $usuarioId);
    if (!$stmtPublicar->execute()) {
        $duplicateSerial = $stmtPublicar->errno === 1062;
        $stmtPublicar->close();
        portalRespond($duplicateSerial ? 409 : 500, [
            'error' => $duplicateSerial
                ? 'Ya existe una publicación con ese número de serie.'
                : 'No fue posible publicar el equipo.'
        ]);
    }
    $stmtPublicar->close();
    portalRespond(201, ['published' => true]);
}

portalRespond(400, ['error' => 'La acción solicitada no es válida.']);
