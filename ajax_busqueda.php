<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once 'config/conexion.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($q === '') {
    echo json_encode([]);
    exit;
}

$search = "%" . $q . "%";
$stmt = $conn->prepare("SELECT id, nombre, dni, telefono FROM pacientes WHERE (nombre LIKE ? OR dni LIKE ?) AND estado_active = 1 OR estado_activo = 1 LIMIT 8");
// Let's check if estado_activo column exists. Yes, we saw it is estado_activo
$stmt = $conn->prepare("SELECT id, nombre, dni, telefono FROM pacientes WHERE (nombre LIKE ? OR dni LIKE ?) AND estado_activo = 1 LIMIT 8");
$stmt->bind_param('ss', $search, $search);
$stmt->execute();
$result = $stmt->get_result();

$pacientes = [];
while ($row = $result->fetch_assoc()) {
    $pacientes[] = [
        'id' => intval($row['id']),
        'nombre' => $row['nombre'],
        'dni' => $row['dni'],
        'telefono' => $row['telefono']
    ];
}

echo json_encode($pacientes, JSON_UNESCAPED_UNICODE);
?>
