<?php
require 'config/conexion.php';
$stmt = $conn->prepare('SELECT pi.id as item_id, pi.descripcion as tratamiento, pi.diente_numero as pieza, pi.subtotal as total, pi.realizado, pi.fecha_realizado, p.nombre as presupuesto_nombre FROM presupuesto_items pi JOIN presupuestos p ON p.id = pi.presupuesto_id WHERE p.paciente_id = ? ORDER BY pi.realizado ASC, pi.fecha_realizado DESC, p.id DESC');
if (!$stmt) die($conn->error);
$pid = 4;
$stmt->bind_param('i', $pid);
$stmt->execute();
$r = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
echo count($r);
