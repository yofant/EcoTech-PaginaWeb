<?php
class EcotechAuditException extends RuntimeException
{
}

function ecotechAuditActorLabel(): string
{
    $user = $_SESSION['usuario'] ?? [];
    $userId = (int) ($user['id'] ?? 0);
    $role = trim((string) ($user['rol'] ?? ''));
    $name = trim((string) ($user['nombre'] ?? ''));
    $email = trim((string) ($user['correo'] ?? ''));
    $identity = $name !== '' ? $name : $email;

    if ($userId > 0) {
        $label = sprintf('#%d %s%s', $userId, $role !== '' ? $role : 'Usuario', $identity !== '' ? ' · ' . $identity : '');
    } else {
        $label = 'Web sin autenticar';
    }

    return mb_substr($label, 0, 100, 'UTF-8');
}

function ecotechSetAuditActor(mysqli $conn, ?string $actor = null): void
{
    $actor ??= ecotechAuditActorLabel();
    $stmt = $conn->prepare('SET @ecotech_actor = ?');
    if (!$stmt) {
        error_log('Audit actor setup failed: ' . $conn->error);
        throw new EcotechAuditException('No se pudo identificar al actor de la operación.');
    }
    $stmt->bind_param('s', $actor);
    if (!$stmt->execute()) {
        error_log('Audit actor setup failed: ' . $stmt->error);
        $stmt->close();
        throw new EcotechAuditException('No se pudo identificar al actor de la operación.');
    }
    $stmt->close();
}

function ecotechLogAuditEvent(
    mysqli $conn,
    string $table,
    string $operation,
    ?int $recordId,
    string $detail
): void {
    $detail = mb_substr($detail, 0, 100, 'UTF-8');
    $stmt = $conn->prepare("
        INSERT INTO `Auditoria`
            (tabla_afectada, operacion, registro_id, usuario_sql, detalle)
        VALUES (?, ?, ?, COALESCE(NULLIF(@ecotech_actor, ''), CURRENT_USER()), ?)
    ");
    if (!$stmt) {
        error_log('Audit event prepare failed: ' . $conn->error);
        throw new EcotechAuditException('No se pudo registrar la actividad en auditoría.');
    }
    $stmt->bind_param('ssis', $table, $operation, $recordId, $detail);
    if (!$stmt->execute()) {
        error_log('Audit event insert failed: ' . $stmt->error);
        $stmt->close();
            throw new EcotechAuditException('No se pudo registrar la actividad en auditoría.');
    }
    $stmt->close();
}
