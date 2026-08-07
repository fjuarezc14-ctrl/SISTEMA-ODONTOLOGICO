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

// Leer JSON payload
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

require_once 'controllers/EvolucionController.php';
$controller = new EvolucionController();

$resultado = $controller->store($data);

if ($resultado) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al guardar la evolución']);
}
?>
