<?php
require_once 'config/conexion.php';

echo "Iniciando actualización de datos de la Doctora...\n";

// 1. Intentar actualizar el usuario con login 'lorena' (para asignarle su colegiatura)
$stmt1 = $conn->prepare("UPDATE usuarios SET nombre = 'LORENA ESPINOZA GUTIERREZ', rol = 'Admin', colegiatura = '55272' WHERE usuario = 'lorena'");
if ($stmt1 && $stmt1->execute()) {
    if ($conn->affected_rows > 0) {
        echo "Éxito: Se actualizaron los datos del usuario 'lorena' (Nombre: LORENA ESPINOZA GUTIERREZ, COP: 55272).\n";
    } else {
        echo "Aviso: No se requirieron cambios para el usuario 'lorena' (o no existe aún).\n";
    }
} else {
    echo "Error al actualizar usuario 'lorena': " . $conn->error . "\n";
}

// 2. Por seguridad, si aún existe el usuario 'admin' por defecto (ID 1), migrarlo a 'lorena' con los datos correctos
$stmt2 = $conn->prepare("UPDATE usuarios SET nombre = 'LORENA ESPINOZA GUTIERREZ', usuario = 'lorena', colegiatura = '55272' WHERE id = 1 AND usuario = 'admin'");
if ($stmt2 && $stmt2->execute()) {
    if ($conn->affected_rows > 0) {
        echo "Éxito: Se migró la cuenta 'admin' por defecto a 'lorena' (LORENA ESPINOZA GUTIERREZ, COP: 55272).\n";
    }
} else {
    echo "Error al migrar cuenta por defecto: " . $conn->error . "\n";
}

echo "Proceso finalizado.\n";
?>
