<?php
session_start();
require_once 'config/conexion.php';
require_once 'controllers/ArchivoController.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$controller = new ArchivoController();

// Manejar POST con FormData (archivos subidos)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $paciente_id = intval($_POST['paciente_id'] ?? 0);
    if (!$paciente_id) {
        echo json_encode(['success' => false, 'error' => 'ID de paciente requerido']);
        exit;
    }

    $data = [
        'tipo' => $_POST['tipo'] ?? 'Radiografía',
        'descripcion' => $_POST['descripcion'] ?? '',
        'subido_por' => $_SESSION['usuario_id']
    ];

    $resultado = $controller->subirArchivo($paciente_id, $data, $_FILES['archivo']);
    echo json_encode($resultado);
    exit;
}

// Manejar JSON payload para listar y eliminar
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$accion = $data['accion'] ?? '';

switch ($accion) {
    case 'listar':
        $paciente_id = $data['paciente_id'] ?? null;
        if (!$paciente_id) {
            echo json_encode(['success' => false, 'error' => 'ID de paciente requerido']);
            break;
        }
        $archivos = $controller->getArchivosPaciente($paciente_id);
        echo json_encode(['success' => true, 'archivos' => $archivos]);
        break;

    case 'eliminar':
        $archivo_id = $data['archivo_id'] ?? null;
        if (!$archivo_id) {
            echo json_encode(['success' => false, 'error' => 'ID de archivo requerido']);
            break;
        }
        $resultado = $controller->eliminarArchivo($archivo_id);
        echo json_encode($resultado);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
}
?>
