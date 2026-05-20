<?php
require 'config/conexion.php';
$_SESSION['usuario_id'] = 1; // fake session
$data = ['accion'=>'guardar_receta', 'paciente_id'=>4, 'diagnostico'=>'test', 'contenido'=>'test'];
$paciente_id = intval($data['paciente_id'] ?? 0);
$diagnostico = trim($data['diagnostico'] ?? '');
$contenido   = trim($data['contenido'] ?? '');
$doctor_id   = $_SESSION['usuario_id'];

$stmt = $conn->prepare("INSERT INTO recetas (paciente_id, doctor_id, diagnostico, contenido) VALUES (?, ?, ?, ?)");
if (!$stmt) die("Prepare failed: " . $conn->error);
$stmt->bind_param('iiss', $paciente_id, $doctor_id, $diagnostico, $contenido);
if ($stmt->execute()) echo "Success ID " . $stmt->insert_id;
else echo "Execute failed: " . $stmt->error;
