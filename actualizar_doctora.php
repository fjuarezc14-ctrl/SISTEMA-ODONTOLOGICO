<?php
require_once 'config/conexion.php';

echo "Iniciando actualización de datos de la Doctora...\n";

// 1. Intentar actualizar el usuario Admin (ID 1)
$stmt1 = $conn->prepare("UPDATE usuarios SET nombre = 'Dra. Lorena Espinoza Gutierrez', colegiatura = '55272' WHERE id = 1 OR (rol = 'Admin' AND usuario = 'admin')");
if ($stmt1 && $stmt1->execute()) {
    echo "Éxito: Se actualizó el usuario Administrador General (ID 1) a Dra. Lorena Espinoza Gutierrez (COP: 55272).\n";
} else {
    echo "Error al actualizar usuario Admin: " . $conn->error . "\n";
}

// 2. Intentar actualizar el usuario Dentista (ID 2)
$stmt2 = $conn->prepare("UPDATE usuarios SET nombre = 'Dra. Lorena Espinoza Gutierrez', usuario = 'lorena', colegiatura = '55272' WHERE id = 2 OR (rol = 'Dentista' AND usuario = 'yomarin')");
if ($stmt2 && $stmt2->execute()) {
    echo "Éxito: Se actualizó el usuario Dentista (ID 2) a Dra. Lorena Espinoza Gutierrez (COP: 55272).\n";
} else {
    echo "Error al actualizar usuario Dentista: " . $conn->error . "\n";
}

echo "Proceso finalizado.\n";
?>
