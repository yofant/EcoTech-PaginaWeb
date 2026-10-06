<?php
if (!defined('ECOTECH_ADMIN_USUARIOS')) {
    define('ECOTECH_ADMIN_USUARIOS', true);

    $rolesPermitidosUsuarios = ['Administrador', 'Tecnico', 'Operador', 'Auditor', 'Usuario', 'Vendedor'];
    $crudMessage = null;
    $crudMessageType = null;
    $modoFormulario = 'crear';
    $usuarioForm = [
        'id' => '',
        'nombre' => '',
        'primer_apellido' => '',
        'segundo_apellido' => '',
        'correo' => '',
        'telefono' => '',
        'rol' => 'Operador',
        'contrasena' => ''
    ];
    $usuarios = [];

    if (!function_exists('adminUsuariosRedirect')) {
        function adminUsuariosRedirect($params = [])
        {
            $query = http_build_query(array_merge(['panel' => 'usuarios'], $params));
            header("Location: ../html/admin_panel.php?$query");
            exit();
        }
    }

    $estadoCrud = $_GET['crud_status'] ?? '';
    if ($estadoCrud === 'created') {
        $crudMessage = 'Usuario creado correctamente.';
        $crudMessageType = 'success';
    } elseif ($estadoCrud === 'updated') {
        $crudMessage = 'Usuario actualizado correctamente.';
        $crudMessageType = 'success';
    } elseif ($estadoCrud === 'deleted') {
        $crudMessage = 'Usuario eliminado correctamente.';
        $crudMessageType = 'success';
    } elseif ($estadoCrud === 'duplicate') {
        $crudMessage = 'El correo ingresado ya existe en la base de datos.';
        $crudMessageType = 'error';
    } elseif ($estadoCrud === 'invalid') {
        $crudMessage = 'No fue posible completar la operacion. Revisa los datos enviados.';
        $crudMessageType = 'error';
    } elseif ($estadoCrud === 'self_delete') {
        $crudMessage = 'No puedes eliminar tu propia cuenta mientras la sesion este activa.';
        $crudMessageType = 'warning';
    } elseif ($estadoCrud === 'not_found') {
        $crudMessage = 'El usuario solicitado no existe o ya fue eliminado.';
        $crudMessageType = 'warning';
    } elseif ($estadoCrud === 'db_error') {
        $crudMessage = 'Ocurrio un error con la base de datos al procesar la solicitud.';
        $crudMessageType = 'error';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['crud_action'] ?? '') === 'save_user')) {
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $primerApellido = trim($_POST['primer_apellido'] ?? '');
        $segundoApellido = trim($_POST['segundo_apellido'] ?? '');
        $apellido = trim($primerApellido . ' ' . $segundoApellido);
        $correo = trim($_POST['correo'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $rol = trim($_POST['rol'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';
        $modoFormulario = $id > 0 ? 'editar' : 'crear';

        $usuarioForm = [
            'id' => $id,
            'nombre' => $nombre,
            'primer_apellido' => $primerApellido,
            'segundo_apellido' => $segundoApellido,
            'correo' => $correo,
            'telefono' => $telefono,
            'rol' => $rol,
            'contrasena' => ''
        ];

        if (
            $nombre === '' ||
            $apellido === '' ||
            !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
            strlen($correo) > 150 ||
            $telefono === '' ||
            strlen($telefono) > 20 ||
            !in_array($rol, $rolesPermitidosUsuarios, true) ||
            ($id === 0 && $contrasena === '')
        ) {
            $crudMessage = 'Completa todos los campos obligatorios antes de guardar.';
            $crudMessageType = 'error';
        } else {
            if ($id > 0) {
                $consultaActual = $conn->prepare("SELECT email FROM `Usuarios` WHERE usuario_id = ?");
                $consultaActual->bind_param("i", $id);
                $consultaActual->execute();
                $usuarioActual = $consultaActual->get_result()->fetch_assoc();
                $consultaActual->close();

                if (!$usuarioActual) {
                    adminUsuariosRedirect(['crud_status' => 'not_found']);
                }

                if ($contrasena !== '') {
                    $hash = password_hash($contrasena, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("
                        UPDATE `Usuarios`
                        SET nombre = ?, apellido = ?, email = ?, telefono = ?, rol = ?, password_hash = ?
                        WHERE usuario_id = ?
                    ");
                    $stmt->bind_param("ssssssi", $nombre, $apellido, $correo, $telefono, $rol, $hash, $id);
                } else {
                    $stmt = $conn->prepare("
                        UPDATE `Usuarios`
                        SET nombre = ?, apellido = ?, email = ?, telefono = ?, rol = ?
                        WHERE usuario_id = ?
                    ");
                    $stmt->bind_param("sssssi", $nombre, $apellido, $correo, $telefono, $rol, $id);
                }

                if ($stmt->execute()) {
                    if (($usuarioActual['email'] ?? '') === ($_SESSION['usuario']['correo'] ?? '')) {
                        $_SESSION['usuario']['nombre'] = $nombre;
                        $_SESSION['usuario']['apellido'] = $apellido;
                        $_SESSION['usuario']['correo'] = $correo;
                        $_SESSION['usuario']['rol'] = $rol;
                    }
                    $stmt->close();
                    adminUsuariosRedirect(['crud_status' => 'updated']);
                }

                $codigoError = $stmt->errno;
                $stmt->close();
                adminUsuariosRedirect(['crud_status' => $codigoError === 1062 ? 'duplicate' : 'db_error']);
            }

            $hash = password_hash($contrasena, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("
                INSERT INTO `Usuarios` (nombre, apellido, email, telefono, rol, password_hash)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("ssssss", $nombre, $apellido, $correo, $telefono, $rol, $hash);

            if ($stmt->execute()) {
                $stmt->close();
                adminUsuariosRedirect(['crud_status' => 'created']);
            }

            $codigoError = $stmt->errno;
            $stmt->close();
            adminUsuariosRedirect(['crud_status' => $codigoError === 1062 ? 'duplicate' : 'db_error']);
        }
    }

    if (($_GET['user_action'] ?? '') === 'delete' && isset($_GET['id'])) {
        $idEliminar = (int) $_GET['id'];
        $consultaEliminar = $conn->prepare("SELECT email FROM `Usuarios` WHERE usuario_id = ?");
        $consultaEliminar->bind_param("i", $idEliminar);
        $consultaEliminar->execute();
        $usuarioEliminar = $consultaEliminar->get_result()->fetch_assoc();
        $consultaEliminar->close();

        if (!$usuarioEliminar) {
            adminUsuariosRedirect(['crud_status' => 'not_found']);
        }

        if (($usuarioEliminar['email'] ?? '') === ($_SESSION['usuario']['correo'] ?? '')) {
            adminUsuariosRedirect(['crud_status' => 'self_delete']);
        }

        $stmtEliminar = $conn->prepare("DELETE FROM `Usuarios` WHERE usuario_id = ?");
        $stmtEliminar->bind_param("i", $idEliminar);
        if ($stmtEliminar->execute()) {
            $stmtEliminar->close();
            adminUsuariosRedirect(['crud_status' => 'deleted']);
        }

        $stmtEliminar->close();
        adminUsuariosRedirect(['crud_status' => 'db_error']);
    }

    if (($_GET['user_action'] ?? '') === 'edit' && isset($_GET['id'])) {
        $idEditar = (int) $_GET['id'];
        $consultaEditar = $conn->prepare("
            SELECT usuario_id AS id, nombre, apellido, email, telefono, rol
            FROM `Usuarios`
            WHERE usuario_id = ?
        ");
        $consultaEditar->bind_param("i", $idEditar);
        $consultaEditar->execute();
        $usuarioEditar = $consultaEditar->get_result()->fetch_assoc();
        $consultaEditar->close();

        if (!$usuarioEditar) {
            adminUsuariosRedirect(['crud_status' => 'not_found']);
        }

        $apellidos = preg_split('/\s+/', trim($usuarioEditar['apellido'] ?? ''), 2);
        $modoFormulario = 'editar';
        $usuarioForm = [
            'id' => $usuarioEditar['id'],
            'nombre' => $usuarioEditar['nombre'] ?? '',
            'primer_apellido' => $apellidos[0] ?? '',
            'segundo_apellido' => $apellidos[1] ?? '',
            'correo' => $usuarioEditar['email'] ?? '',
            'telefono' => $usuarioEditar['telefono'] ?? '',
            'rol' => $usuarioEditar['rol'] ?? 'Operador',
            'contrasena' => ''
        ];
    }

    $resultadoUsuarios = $conn->query("
        SELECT usuario_id AS id, nombre, apellido, email, telefono, rol
        FROM `Usuarios`
        ORDER BY usuario_id DESC
    ");
    if (!$resultadoUsuarios) {
        die("Error al consultar usuarios: " . $conn->error);
    }

    while ($fila = $resultadoUsuarios->fetch_assoc()) {
        $apellidos = preg_split('/\s+/', trim($fila['apellido'] ?? ''), 2);
        $fila['primer_apellido'] = $apellidos[0] ?? '';
        $fila['segundo_apellido'] = $apellidos[1] ?? '';
        $fila['correo'] = $fila['email'];
        $usuarios[] = $fila;
    }
}
?>
