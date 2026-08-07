<?php
header('Content-Type: application/json');
require_once '../config/conexion.php';
require_once '../models/Odontograma.php';

require_once __DIR__ . '/../includes/session_init.php';
if(!isset($_SESSION['usuario_id'])) {
    echo json_encode(["status" => "error", "message" => "No autorizado"]);
    exit;
}

$odontogramaModel = new Odontograma($conn);

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if(isset($_GET['paciente_id'])) {
        $datos = $odontogramaModel->getEstadoByPaciente($_GET['paciente_id']);
        echo json_encode(["status" => "success", "data" => $datos]);
    } else {
        echo json_encode(["status" => "error", "message" => "Falta paciente_id"]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if(isset($input['paciente_id']) && isset($input['diente_numero']) && isset($input['cara_afectada']) && isset($input['estado'])) {
        $notas = isset($input['notas']) ? $input['notas'] : '';
        $exito = $odontogramaModel->saveEstado($input['paciente_id'], $input['diente_numero'], $input['cara_afectada'], $input['estado'], $notas);
        if($exito) {
            echo json_encode(["status" => "success", "message" => "Estado guardado correctamente"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Error al guardar"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Método no soportado"]);
}
?>
