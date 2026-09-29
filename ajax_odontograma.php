<?php
require_once 'includes/auth_guard.php';header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Lee el JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

require_once 'controllers/OdontogramaController.php';
$controller = new OdontogramaController();

if (isset($data['action']) && $data['action'] === 'save_batch') {
    $paciente_id = $data['paciente_id'] ?? null;
    $items = $data['items'] ?? [];
    $resultado = $controller->saveBatch($paciente_id, $items);
} else {
    $resultado = $controller->store($data);
}

if ($resultado) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al guardar en la base de datos']);
}
?>