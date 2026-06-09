<?php
require_once 'includes/auth_guard.php';header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'Admin') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

require_once 'config/conexion.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['accion'])) {
    echo json_encode(['success' => false, 'error' => 'Acción no especificada']);
    exit;
}

switch ($data['accion']) {
    case 'listar_usuarios':
        $sql = "SELECT id, nombre, usuario, rol, colegiatura, estado_activo, bloqueado_hasta FROM usuarios ORDER BY nombre ASC";
        $result = $conn->query($sql);
        $usuarios = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $usuarios[] = $row;
            }
        }
        echo json_encode(['success' => true, 'data' => $usuarios]);
        break;

    case 'crear_usuario':
        $nombre = trim($data['nombre']);
        $usuario = trim($data['usuario']);
        $password = trim($data['password']);
        $rol = trim($data['rol']);
        $colegiatura = trim($data['colegiatura'] ?? '');

        if (!$nombre || !$usuario || !$password || !$rol) {
            echo json_encode(['success' => false, 'error' => 'Faltan campos obligatorios']);
            break;
        }

        // Verificar si el usuario ya existe
        $stmt_check = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $stmt_check->bind_param("s", $usuario);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'El nombre de usuario ya está en uso']);
            break;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre, usuario, password, rol, colegiatura) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nombre, $usuario, $hash, $rol, $colegiatura);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al crear usuario']);
        }
        break;

    case 'actualizar_usuario':
        $id = intval($data['id']);
        $nombre = trim($data['nombre']);
        $usuario = trim($data['usuario']);
        $rol = trim($data['rol']);
        $colegiatura = trim($data['colegiatura'] ?? '');

        // Evitar cambiar el usuario del propio Admin para no bloquearse
        if ($id === 1 && $rol !== 'Admin') {
            echo json_encode(['success' => false, 'error' => 'No puedes cambiar el rol del Administrador principal']);
            break;
        }

        $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, usuario = ?, rol = ?, colegiatura = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $nombre, $usuario, $rol, $colegiatura, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al actualizar usuario']);
        }
        break;

    case 'cambiar_password':
        $id = intval($data['id']);
        $password = trim($data['password']);
        if (!$password) {
            echo json_encode(['success' => false, 'error' => 'La contraseña no puede estar vacía']);
            break;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hash, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al actualizar contraseña']);
        }
        break;

    case 'toggle_estado':
        $id = intval($data['id']);
        if ($id === 1) {
            echo json_encode(['success' => false, 'error' => 'No puedes inhabilitar al Administrador principal']);
            break;
        }
        
        $estado = intval($data['estado']);
        $stmt = $conn->prepare("UPDATE usuarios SET estado_activo = ? WHERE id = ?");
        $stmt->bind_param("ii", $estado, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al cambiar estado']);
        }
        break;

    case 'eliminar_usuario':
        $id = intval($data['id']);
        if ($id === 1) {
            echo json_encode(['success' => false, 'error' => 'No puedes eliminar al Administrador principal']);
            break;
        }
        
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        try {
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                if ($conn->errno == 1451) {
                    echo json_encode(['success' => false, 'error' => 'No se puede eliminar el usuario porque tiene registros asociados (como citas). Se recomienda inhabilitar su acceso en su lugar para conservar el historial.']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Error al eliminar el usuario: ' . $conn->error]);
                }
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'No se puede eliminar el usuario porque tiene registros asociados. Se recomienda inhabilitar su acceso.']);
        }
        break;

    case 'desbloquear_usuario':
        $id = intval($data['id']);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID de usuario inválido.']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al desbloquear el usuario.']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        break;
}
