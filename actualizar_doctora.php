<?php
require_once 'config/conexion.php';

echo "Iniciando actualización de datos de la Doctora...\n";

// 1. Intentar actualizar el usuario Yerson (ID 2) o buscar por rol Dentista
$stmt = $conn->prepare("UPDATE usuarios SET nombre = 'Dra. Lorena Espinoza Gutierrez', usuario = 'lorena', colegiatura = '55272' WHERE id = 2 OR (rol = 'Dentista' AND usuario = 'yomarin')");
if ($stmt && $stmt->execute()) {
    if ($conn->affected_rows > 0) {
        echo "Éxito: Se actualizó el usuario Dentista a Dra. Lorena Espinoza Gutierrez (COP: 55272).\n";
    } else {
        echo "Aviso: No se encontraron usuarios para actualizar (posiblemente ya actualizados).\n";
    }
} else {
    echo "Error al actualizar la tabla de usuarios: " . $conn->error . "\n";
}

echo "Proceso finalizado.\n";
?>
