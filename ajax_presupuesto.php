<?php
// Suprimir warnings que contaminarían el JSON output
ini_set('display_errors', '0');
error_reporting(E_ERROR | E_PARSE);

ob_start();
require_once 'includes/auth_guard.php';header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['accion'])) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos o acción no especificada'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once 'controllers/PresupuestoController.php';
$controller = new PresupuestoController();
$accion = $data['accion'];

ob_end_clean();

$J = JSON_UNESCAPED_UNICODE; // shorthand para no repetir

switch ($accion) {

    case 'obtener_catalogo':
        $catalogo = $controller->getCatalogo();
        echo json_encode(['success' => true, 'catalogo' => $catalogo], $J);
        break;

    case 'actualizar_item_catalogo':
        if ($_SESSION['usuario_rol'] !== 'Admin') {
            echo json_encode(['success' => false, 'error' => 'No autorizado'], $J);
            break;
        }
        $id = $data['id'] ?? null;
        $nombre = $data['nombre'] ?? '';
        $precio_base = $data['precio_base'] ?? 0;
        $categoria = $data['categoria'] ?? null;
        $result = $controller->actualizarItemCatalogo($id, $nombre, $precio_base, $categoria);
        echo json_encode($result, $J);
        break;

    case 'crear_item_catalogo':
        if ($_SESSION['usuario_rol'] !== 'Admin') {
            echo json_encode(['success' => false, 'error' => 'No autorizado'], $J);
            break;
        }
        $nombre = $data['nombre'] ?? '';
        $precio_base = $data['precio_base'] ?? 0;
        $categoria = $data['categoria'] ?? null;
        $result = $controller->crearItemCatalogo($nombre, $precio_base, $categoria);
        echo json_encode($result, $J);
        break;

    case 'generar_desde_odontograma':
        $paciente_id = $data['paciente_id'] ?? null;
        $hallazgos = $data['hallazgos'] ?? [];
        $doctor_id = $_SESSION['usuario_id'];

        if (!$paciente_id || empty($hallazgos)) {
            echo json_encode(['success' => false, 'error' => 'Faltan datos del paciente o hallazgos'], $J);
            break;
        }

        $presupuesto_id = $controller->generarDesdeOdontograma($paciente_id, $hallazgos, $doctor_id);
        if ($presupuesto_id) {
            $presupuesto = $controller->getPresupuesto($presupuesto_id);
            echo json_encode(['success' => true, 'presupuesto' => $presupuesto], $J);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al generar el presupuesto'], $J);
        }
        break;

    case 'obtener_presupuesto':
        $id = $data['presupuesto_id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID de presupuesto requerido'], $J);
            break;
        }
        $presupuesto = $controller->getPresupuesto($id);
        if ($presupuesto) {
            echo json_encode(['success' => true, 'presupuesto' => $presupuesto], $J);
        } else {
            echo json_encode(['success' => false, 'error' => 'Presupuesto no encontrado'], $J);
        }
        break;

    case 'listar_presupuestos':
        $paciente_id = $data['paciente_id'] ?? null;
        if (!$paciente_id) {
            echo json_encode(['success' => false, 'error' => 'ID de paciente requerido'], $J);
            break;
        }
        $presupuestos = $controller->getPresupuestosPaciente($paciente_id);
        echo json_encode(['success' => true, 'presupuestos' => $presupuestos], $J);
        break;

    case 'agregar_item':
        $presupuesto_id = $data['presupuesto_id'] ?? null;
        if (!$presupuesto_id) {
            echo json_encode(['success' => false, 'error' => 'ID de presupuesto requerido'], $J);
            break;
        }
        $result = $controller->agregarItem($presupuesto_id, $data);
        if ($result) {
            $presupuesto = $controller->getPresupuesto($presupuesto_id);
            echo json_encode(['success' => true, 'presupuesto' => $presupuesto], $J);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al agregar ítem'], $J);
        }
        break;

    case 'actualizar_item':
        $item_id = $data['item_id'] ?? null;
        $presupuesto_id = $data['presupuesto_id'] ?? null;
        if (!$item_id) {
            echo json_encode(['success' => false, 'error' => 'ID de ítem requerido'], $J);
            break;
        }
        $result = $controller->actualizarItem($item_id, $data);
        if ($result && $presupuesto_id) {
            $controller->recalcularTotales($presupuesto_id, $data['descuento_porcentaje'] ?? 0);
            $presupuesto = $controller->getPresupuesto($presupuesto_id);
            echo json_encode(['success' => true, 'presupuesto' => $presupuesto], $J);
        } else {
            echo json_encode(['success' => (bool)$result], $J);
        }
        break;

    case 'eliminar_item':
        $item_id = $data['item_id'] ?? null;
        $presupuesto_id = $data['presupuesto_id'] ?? null;
        if (!$item_id || !$presupuesto_id) {
            echo json_encode(['success' => false, 'error' => 'IDs requeridos'], $J);
            break;
        }
        $result = $controller->eliminarItem($presupuesto_id, $item_id);
        if ($result) {
            $presupuesto = $controller->getPresupuesto($presupuesto_id);
            echo json_encode(['success' => true, 'presupuesto' => $presupuesto], $J);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al eliminar ítem'], $J);
        }
        break;

    case 'aplicar_descuento':
        $presupuesto_id = $data['presupuesto_id'] ?? null;
        $descuento_porcentaje = $data['descuento_porcentaje'] ?? 0;
        if (!$presupuesto_id) {
            echo json_encode(['success' => false, 'error' => 'ID de presupuesto requerido'], $J);
            break;
        }
        $result = $controller->recalcularTotales($presupuesto_id, $descuento_porcentaje);
        if ($result) {
            $presupuesto = $controller->getPresupuesto($presupuesto_id);
            echo json_encode(['success' => true, 'presupuesto' => $presupuesto], $J);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al aplicar descuento'], $J);
        }
        break;

    case 'cambiar_estado':
        $presupuesto_id = $data['presupuesto_id'] ?? null;
        $estado = $data['estado'] ?? null;
        if (!$presupuesto_id || !$estado) {
            echo json_encode(['success' => false, 'error' => 'ID y estado requeridos'], $J);
            break;
        }
        $result = $controller->cambiarEstado($presupuesto_id, $estado);
        echo json_encode(['success' => (bool)$result], $J);
        break;

    case 'eliminar_presupuesto':
        $presupuesto_id = $data['presupuesto_id'] ?? null;
        if (!$presupuesto_id) {
            echo json_encode(['success' => false, 'error' => 'ID de presupuesto requerido'], $J);
            break;
        }
        $result = $controller->eliminar($presupuesto_id);
        echo json_encode(['success' => (bool)$result], $J);
        break;

    case 'crear_vacio':
        $paciente_id = $data['paciente_id'] ?? null;
        $doctor_id = $_SESSION['usuario_id'];
        if (!$paciente_id) {
            echo json_encode(['success' => false, 'error' => 'ID de paciente requerido'], $J);
            break;
        }
        $presupuesto_id = $controller->crearVacio($paciente_id, $doctor_id);
        if ($presupuesto_id) {
            $presupuesto = $controller->getPresupuesto($presupuesto_id);
            echo json_encode(['success' => true, 'presupuesto' => $presupuesto], $J);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al crear presupuesto'], $J);
        }
        break;

    case 'listar_todos':
        $presupuestos = $controller->getTodosPresupuestos();
        echo json_encode(['success' => true, 'presupuestos' => $presupuestos], $J);
        break;

    case 'registrar_pago':
        $presupuesto_id = $data['presupuesto_id'] ?? null;
        if (!$presupuesto_id) {
            echo json_encode(['success' => false, 'error' => 'ID de presupuesto requerido'], $J);
            break;
        }
        $data['registrado_por'] = $_SESSION['usuario_id'];
        $pago = $controller->registrarPago($presupuesto_id, $data);
        if ($pago) {
            $resumen = $controller->getResumenFinanciero($presupuesto_id);
            echo json_encode(['success' => true, 'pago' => $pago, 'resumen' => $resumen], $J);
        } else {
            echo json_encode(['success' => false, 'error' => 'No se pudo registrar el pago. Verifique que el monto no exceda el saldo.'], $J);
        }
        break;

    case 'eliminar_pago':
        $pago_id = $data['pago_id'] ?? null;
        $presupuesto_id = $data['presupuesto_id'] ?? null;
        if (!$pago_id || !$presupuesto_id) {
            echo json_encode(['success' => false, 'error' => 'ID de pago y presupuesto requeridos'], $J);
            break;
        }
        $res = $controller->eliminarPago($pago_id, $presupuesto_id);
        if ($res) {
            echo json_encode(['success' => true], $J);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al eliminar el pago'], $J);
        }
        break;

    case 'listar_pagos':
        $presupuesto_id = $data['presupuesto_id'] ?? null;
        if (!$presupuesto_id) {
            echo json_encode(['success' => false, 'error' => 'ID de presupuesto requerido'], $J);
            break;
        }
        $pagos = $controller->getPagosPresupuesto($presupuesto_id);
        echo json_encode(['success' => true, 'pagos' => $pagos], $J);
        break;

    case 'resumen_financiero':
        $presupuesto_id = $data['presupuesto_id'] ?? null;
        if (!$presupuesto_id) {
            echo json_encode(['success' => false, 'error' => 'ID de presupuesto requerido'], $J);
            break;
        }
        $resumen = $controller->getResumenFinanciero($presupuesto_id);
        if ($resumen) {
            echo json_encode(['success' => true, 'resumen' => $resumen], $J);
        } else {
            echo json_encode(['success' => false, 'error' => 'Presupuesto no encontrado'], $J);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Acción no reconocida: ' . $accion], $J);
}
?>
