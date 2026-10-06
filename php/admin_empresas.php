<?php
if (!defined('ECOTECH_ADMIN_EMPRESAS')) {
    define('ECOTECH_ADMIN_EMPRESAS', true);

    $crudEmpresaMessage = null;
    $crudEmpresaMessageType = null;
    $modoEmpresaFormulario = 'crear';
    $empresaForm = [
        'id_empresa' => '',
        'nombre' => '',
        'nit' => 'Empresa',
        'direccion' => '',
        'telefono' => '',
        'correo_contacto' => '',
        'fecha_registro' => ''
    ];
    $empresas = [];

    if (!function_exists('adminEmpresasRedirect')) {
        function adminEmpresasRedirect(array $params = []): void
        {
            $query = http_build_query(array_merge(['panel' => 'empresas'], $params));
            header("Location: ../html/admin_panel.php?$query");
            exit();
        }
    }

    $empresaCrudStatus = $_GET['empresa_crud_status'] ?? '';
    if ($empresaCrudStatus === 'created') {
        $crudEmpresaMessage = 'Donante registrado correctamente.';
        $crudEmpresaMessageType = 'success';
    } elseif ($empresaCrudStatus === 'updated') {
        $crudEmpresaMessage = 'Donante actualizado correctamente.';
        $crudEmpresaMessageType = 'success';
    } elseif ($empresaCrudStatus === 'deleted') {
        $crudEmpresaMessage = 'Donante eliminado correctamente.';
        $crudEmpresaMessageType = 'success';
    } elseif ($empresaCrudStatus === 'invalid') {
        $crudEmpresaMessage = 'Completa todos los datos obligatorios antes de guardar.';
        $crudEmpresaMessageType = 'error';
    } elseif ($empresaCrudStatus === 'not_found') {
        $crudEmpresaMessage = 'El donante solicitado no existe o ya fue eliminado.';
        $crudEmpresaMessageType = 'warning';
    } elseif ($empresaCrudStatus === 'db_error') {
        $crudEmpresaMessage = 'Ocurrio un error con la base de datos al procesar el donante.';
        $crudEmpresaMessageType = 'error';
    } elseif ($empresaCrudStatus === 'in_use') {
        $crudEmpresaMessage = 'No se puede eliminar: hay equipos asociados a este donante.';
        $crudEmpresaMessageType = 'warning';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['empresa_crud_action'] ?? '') === 'save_empresa')) {
        $idDonante = (int) ($_POST['id_empresa'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $tipo = trim($_POST['nit'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $email = trim($_POST['correo_contacto'] ?? '');
        $modoEmpresaFormulario = $idDonante > 0 ? 'editar' : 'crear';
        $empresaForm = [
            'id_empresa' => $idDonante > 0 ? (string) $idDonante : '',
            'nombre' => $nombre,
            'nit' => $tipo,
            'direccion' => $direccion,
            'telefono' => $telefono,
            'correo_contacto' => $email,
            'fecha_registro' => ''
        ];

        if (
            $nombre === '' ||
            strlen($nombre) > 150 ||
            $tipo === '' ||
            strlen($tipo) > 20 ||
            $direccion === '' ||
            strlen($direccion) > 250 ||
            $telefono === '' ||
            strlen($telefono) > 20 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            strlen($email) > 150
        ) {
            $crudEmpresaMessage = 'Nombre, tipo, direccion, telefono y correo valido son obligatorios.';
            $crudEmpresaMessageType = 'error';
        } elseif ($idDonante > 0) {
            $stmtExiste = $conn->prepare("SELECT donante_id FROM `Donantes` WHERE donante_id = ?");
            $stmtExiste->bind_param('i', $idDonante);
            $stmtExiste->execute();
            $donanteExiste = $stmtExiste->get_result()->fetch_assoc();
            $stmtExiste->close();
            if (!$donanteExiste) {
                adminEmpresasRedirect(['empresa_crud_status' => 'not_found']);
            }

            $stmt = $conn->prepare("
                UPDATE `Donantes`
                SET tipo = ?, nombre = ?, email = ?, telefono = ?, direccion = ?
                WHERE donante_id = ?
            ");
            $stmt->bind_param("sssssi", $tipo, $nombre, $email, $telefono, $direccion, $idDonante);
            if ($stmt->execute() && $stmt->affected_rows >= 0) {
                $stmt->close();
                adminEmpresasRedirect(['empresa_crud_status' => 'updated']);
            }
            $stmt->close();
            adminEmpresasRedirect(['empresa_crud_status' => 'db_error']);
        } else {
            $stmt = $conn->prepare("
                INSERT INTO `Donantes` (tipo, nombre, email, telefono, direccion)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sssss", $tipo, $nombre, $email, $telefono, $direccion);
            if ($stmt->execute()) {
                $stmt->close();
                adminEmpresasRedirect(['empresa_crud_status' => 'created']);
            }
            $stmt->close();
            adminEmpresasRedirect(['empresa_crud_status' => 'db_error']);
        }
    }

    if (($_GET['empresa_action'] ?? '') === 'delete' && isset($_GET['id_empresa'])) {
        $idDonante = (int) $_GET['id_empresa'];
        $stmt = $conn->prepare("DELETE FROM `Donantes` WHERE donante_id = ?");
        $stmt->bind_param('i', $idDonante);
        if ($stmt->execute()) {
            $deleted = $stmt->affected_rows > 0;
            $stmt->close();
            adminEmpresasRedirect(['empresa_crud_status' => $deleted ? 'deleted' : 'not_found']);
        }
        $errorCode = $stmt->errno;
        $stmt->close();
        adminEmpresasRedirect(['empresa_crud_status' => $errorCode === 1451 ? 'in_use' : 'db_error']);
    }

    if (($_GET['empresa_action'] ?? '') === 'edit' && isset($_GET['id_empresa'])) {
        $idDonante = (int) $_GET['id_empresa'];
        $stmt = $conn->prepare("
            SELECT donante_id, tipo, nombre, email, telefono, direccion, fecha_registro
            FROM `Donantes`
            WHERE donante_id = ?
        ");
        $stmt->bind_param('i', $idDonante);
        $stmt->execute();
        $donante = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$donante) {
            adminEmpresasRedirect(['empresa_crud_status' => 'not_found']);
        }
        $modoEmpresaFormulario = 'editar';
        $empresaForm = [
            'id_empresa' => $donante['donante_id'],
            'nombre' => $donante['nombre'],
            'nit' => $donante['tipo'],
            'direccion' => $donante['direccion'],
            'telefono' => $donante['telefono'],
            'correo_contacto' => $donante['email'],
            'fecha_registro' => $donante['fecha_registro']
        ];
    }

    $resultadoDonantes = $conn->query("
        SELECT donante_id AS id_empresa, nombre, tipo AS nit, direccion, telefono,
               email AS correo_contacto, fecha_registro
        FROM `Donantes`
        ORDER BY donante_id DESC
    ");
    if (!$resultadoDonantes) {
        die("Error al consultar donantes: " . $conn->error);
    }
    while ($donante = $resultadoDonantes->fetch_assoc()) {
        $empresas[] = $donante;
    }
}
?>
