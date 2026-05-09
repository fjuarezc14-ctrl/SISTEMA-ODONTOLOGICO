<?php
// Suprimir warnings que contaminarían el JSON output
ini_set('display_errors', '0');
error_reporting(E_ERROR | E_PARSE);

ob_start(); // Buffer output para capturar cualquier warning residual
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['accion'])) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos o acción no especificada']);
    exit;
}

require_once 'controllers/PresupuestoController.php';
$controller = new PresupuestoController();
$accion = $data['accion'];

// Limpiar cualquier output residual (warnings de includes)
ob_end_clean();

switch ($accion) {

    case 'obtener_catalogo':
        $catalogo = $controller->getCatalogo();
        echo json_encode(['success' => true, 'catalogo' => $catalogo]);
        break;

    case 'generar_desde_odontograma':
        $paciente_id = $data['paciente_id'] ?? null;
        $hallazgos = $data['hallazgos'] ?? [];
        $doctor_id = $_SESSION['usuario_id'];

        if (!$paciente_id || empty($hallazgos)) {
            echo json_encode(['success' => false, 'error' => 'Faltan datos del paciente o hallazgos']);
            break;
        }

        $presupuesto_id = $controller->generarDesdeOdontograma($paciente_id, $hallazgos, $doctor_id);
        if ($presupuesto_id) {
            $presupuesto = $controller->getPresupuesto($presupuesto_id);
            echo json_encode(['success' => true, 'presupuesto' => $presupuesto]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al generar el presupuesto']);
        }
        break;

    case 'obtener_presupuesto':
        $id = $data['presupuesto_id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID de presupuesto requerido']);
            break;
        }
        $presupuesto = $controller->getPresupuesto($id);
        if ($presupuesto) {
            echo json_encode(['success' => true, 'presupuesto' => $presupuesto]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Presupuesto no encontrado']);
        }
        break;

    case 'listar_presupuestos':
        $paciente_id = $data['paciente_id'] ?? null;
        if (!$paciente_id) {
            echo json_encode(['success' => false, 'error' => 'ID de paciente requerido']);
            break;
        }
        $presupuestos = $controller->getPresupuestosPaciente($paciente_id);
        echo json_encode(['success' => true, 'presupuestos' => $presupuestos]);
        break;

    case 'agregar_item':
        $presupuesto_id = $data['presupuesto_id'] ?? null;
        if (!$presupuesto_id) {
            echo json_encode(['success' => false, 'error' => 'ID de presupuesto requerido']);
            break;
        }
        $result = $controller->agregarItem($presupuesto_id, $data);
        if ($result) {
            $presupuesto = $controller->getPresupuesto($presupuesto_id);
            echo json_encode(['success' => true, 'presupuesto' => $presupuesto]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al agregar ítem']);
        }
        break;

    case 'actualizar_item':
        $item_id = $data['item_id'] ?? null;
        $presupuesto_id = $data['presupuesto_id'] ?? null;
        if (!$item_id) {
            echo json_encode(['success' => false, 'error' => 'ID de ítem requerido']);
            break;
        }
        $result = $controller->actualizarItem($item_id, $data);
        if ($result && $presupuesto_id) {
            $controller->recalcularTotales($presupuesto_id, $data['descuento_porcentaje'] ?? 0);
            $presupuesto = $controller->getPresupuesto($presupuesto_id);
            echo json_encode(['success' => true, 'presupuesto' => $presupuesto]);
        } else {
            echo json_encode(['success' => $result]);
        }
        break;

    case 'eliminar_item':
        $item_id = $data['item_id'] ?? null;
        $presupuesto_id = $data['presupuesto_id'] ?? null;
        if (!$item_id || !$presupuesto_id) {
            echo json_encode(['success' => false, 'error' => 'IDs requeridos']);
            break;
        }
        $result = $controller->eliminarItem($presupuesto_id, $item_id);
        if ($result) {
            $presupuesto = $controller->getPresupuesto($presupuesto_id);
            echo json_encode(['success' => true, 'presupuesto' => $presupuesto]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al eliminar ítem']);
        }
        break;

    case 'aplicar_descuento':
        $presupuesto_id = $data['presupuesto_id'] ?? null;
        $descuento_porcentaje = $data['descuento_porcentaje'] ?? 0;
        if (!$presupuesto_id) {
            echo json_encode(['success' => false, 'error' => 'ID de presupuesto requerido']);
            break;
        }
        $result = $controller->recalcularTotales($presupuesto_id, $descuento_porcentaje);
        if ($result) {
            $presupuesto = $controller->getPresupuesto($presupuesto_id);
            echo json_encode(['success' => true, 'presupuesto' => $presupuesto]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al aplicar descuento']);
        }
        break;

    case 'cambiar_estado':
        $presupuesto_id = $data['presupuesto_id'] ?? null;
        $estado = $data['estado'] ?? null;
        if (!$presupuesto_id || !$estado) {
            echo json_encode(['success' => false, 'error' => 'ID y estado requeridos']);
            break;
        }
        $result = $controller->cambiarEstado($presupuesto_id, $estado);
        echo json_encode(['success' => $result]);
        break;

    case 'eliminar_presupuesto':
        $presupuesto_id = $data['presupuesto_id'] ?? null;
        if (!$presupuesto_id) {
            echo json_encode(['success' => false, 'error' => 'ID de presupuesto requerido']);
            break;
        }
        $result = $controller->eliminar($presupuesto_id);
        echo json_encode(['success' => $result]);
        break;

    case 'crear_vacio':
        $paciente_id = $data['paciente_id'] ?? null;
        $doctor_id = $_SESSION['usuario_id'];
        if (!$paciente_id) {
            echo json_encode(['success' => false, 'error' => 'ID de paciente requerido']);
            break;
        }
        $presupuesto_id = $controller->crearVacio($paciente_id, $doctor_id);
        if ($presupuesto_id) {
            $presupuesto = $controller->getPresupuesto($presupuesto_id);
            echo json_encode(['success' => true, 'presupuesto' => $presupuesto]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al crear presupuesto']);
        }
        break;

    case 'listar_todos':
        $presupuestos = $controller->getTodosPresupuestos();
        echo json_encode(['success' => true, 'presupuestos' => $presupuestos]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Acción no reconocida: ' . $accion]);
}
?>
