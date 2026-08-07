<?php
ob_start(); // Buffer desde el inicio absoluto
ini_set('display_errors', '0');
error_reporting(E_ALL);
require_once 'includes/auth_guard.php';header('Content-Type: application/json; charset=utf-8');

ob_end_clean(); // Limpiar cualquier output previo (BOM, warnings, etc.)
ob_start();     // Reiniciar buffer limpio

if (!isset($_SESSION['usuario_id'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);
if (!$data || !isset($data['accion'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Datos inválidos'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once 'config/conexion.php';
ob_end_clean(); // Limpiar por si conexion.php emitió algo

$J = JSON_UNESCAPED_UNICODE;

switch ($data['accion']) {

    // ── Guardar antecedentes clínicos del paciente ──────────────────────────
    case 'guardar_antecedentes':
        $pid = intval($data['paciente_id'] ?? 0);
        if (!$pid) { echo json_encode(['success' => false, 'error' => 'ID requerido'], $J); break; }

        $allowedFields = [
            'padece_enfermedad', 'enfermedades_cronicas',
            'consume_medicamentos', 'medicamentos_detalle',
            'alergia_medicamentos', 'alergia_medicamentos_detalle',
            'antecedentes_familiares', 'antecedentes_familiares_detalle',
            'alergia_anestesia', 'embarazada', 'sangran_encias',
            'ultima_visita_dentista', 'ultima_visita_motivo',
            'frecuencia_cepillado',
            'usa_cepillo', 'usa_pasta_dental', 'usa_hilo_dental', 'usa_enjuague',
            'alergias'
        ];

        $setParts = [];
        $values   = [];

        foreach ($allowedFields as $f) {
            if (!array_key_exists($f, $data)) continue;
            $val = $data[$f];
            if ($val === '' || $val === 'null' || $val === null) $val = null;
            $setParts[] = "`$f` = ?";
            $values[]   = $val;
        }

        if (empty($setParts)) { echo json_encode(['success' => false, 'error' => 'Sin cambios'], $J); break; }

        $values[] = $pid;
        $sql = "UPDATE pacientes SET " . implode(', ', $setParts) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);

        // Bind dinámico
        $refs = [];
        foreach ($values as &$v) $refs[] = &$v;
        $types = str_repeat('s', count($values) - 1) . 'i';
        array_unshift($refs, $types);
        call_user_func_array([$stmt, 'bind_param'], $refs);

        $ok = $stmt->execute();
        echo json_encode(['success' => $ok], $J);
        break;

    // ── Registrar signos vitales por sesión ──────────────────────────────────
    case 'registrar_signos':
        $pid  = intval($data['paciente_id'] ?? 0);
        $cid  = !empty($data['cita_id']) ? intval($data['cita_id']) : null;
        $pa   = trim($data['presion_arterial'] ?? '') ?: null;
        $pulso = !empty($data['pulso']) ? intval($data['pulso']) : null;
        $fc   = !empty($data['frecuencia_cardiaca']) ? intval($data['frecuencia_cardiaca']) : null;
        $fr   = !empty($data['frecuencia_resp']) ? intval($data['frecuencia_resp']) : null;
        $temp = !empty($data['temperatura']) ? floatval($data['temperatura']) : null;
        $obs  = trim($data['observaciones'] ?? '') ?: null;
        $reg  = intval($_SESSION['usuario_id']);

        if (!$pid) { echo json_encode(['success' => false, 'error' => 'ID requerido'], $J); break; }

        $sql = "INSERT INTO signos_vitales
                (paciente_id, cita_id, presion_arterial, pulso, frecuencia_cardiaca, frecuencia_resp, temperatura, observaciones, registrado_por)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        // Usar 'ssssssssl' — todo string es seguro para nullables en mysqli
        $stmt->bind_param('sssssssss', $pid, $cid, $pa, $pulso, $fc, $fr, $temp, $obs, $reg);
        $ok = $stmt->execute();
        if (!$ok) {
            echo json_encode(['success' => false, 'error' => $stmt->error], $J);
        } else {
            echo json_encode(['success' => true, 'id' => $conn->insert_id], $J);
        }
        break;

    // ── Obtener historial de signos vitales ──────────────────────────────────
    case 'obtener_signos':
        $pid   = intval($data['paciente_id'] ?? 0);
        $limit = intval($data['limit'] ?? 8);
        if (!$pid) { echo json_encode(['success' => false, 'error' => 'ID requerido'], $J); break; }

        $stmt = $conn->prepare(
            "SELECT sv.*, u.nombre as registrado_nombre
             FROM signos_vitales sv
             LEFT JOIN usuarios u ON u.id = sv.registrado_por
             WHERE sv.paciente_id = ?
             ORDER BY sv.fecha_registro DESC
             LIMIT ?"
        );
        $stmt->bind_param('ii', $pid, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($r = $result->fetch_assoc()) $rows[] = $r;
        echo json_encode(['success' => true, 'signos' => $rows], $J);
        break;

    // ── Marcar ítem del presupuesto como realizado o pendiente ───────────────
    case 'marcar_item_realizado':
        $item_id  = intval($data['item_id'] ?? 0);
        $realizado = intval($data['realizado'] ?? 0);
        $fecha     = $realizado ? date('Y-m-d') : null;
        if (!$item_id) { echo json_encode(['success' => false, 'error' => 'ID requerido'], $J); break; }
        $stmt = $conn->prepare("UPDATE presupuesto_items SET realizado = ?, fecha_realizado = ? WHERE id = ?");
        $stmt->bind_param('ssi', $realizado, $fecha, $item_id);
        $ok = $stmt->execute();
        echo json_encode(['success' => $ok, 'fecha' => $fecha], $J);
        break;
    // ── Guardar Receta Médica ───────────────
    case 'guardar_receta':
        if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'Recepcionista') {
            echo json_encode(['success' => false, 'error' => 'No autorizado para guardar recetas'], $J);
            break;
        }
        $paciente_id = intval($data['paciente_id'] ?? 0);
        $diagnostico = trim($data['diagnostico'] ?? '');
        $contenido   = trim($data['contenido'] ?? '');
        $doctor_id   = $_SESSION['usuario_id'];
        
        if (!$paciente_id || !$contenido) {
            echo json_encode(['success' => false, 'error' => 'Paciente y contenido son obligatorios'], $J);
            break;
        }
        
        $stmt = $conn->prepare("INSERT INTO recetas (paciente_id, doctor_id, diagnostico, contenido) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('iiss', $paciente_id, $doctor_id, $diagnostico, $contenido);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $stmt->insert_id], $J);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error], $J);
        }
        break;

    // ── Eliminar Receta ───────────────
    case 'eliminar_receta':
        if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'Recepcionista') {
            echo json_encode(['success' => false, 'error' => 'No autorizado para eliminar recetas'], $J);
            break;
        }
        $id = intval($data['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID requerido'], $J);
            break;
        }
        $stmt = $conn->prepare("DELETE FROM recetas WHERE id = ?");
        $stmt->bind_param('i', $id);
        echo json_encode(['success' => $stmt->execute()], $J);
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Acción no reconocida'], $J);
}
?>
