<?php
require_once 'config/conexion.php';

echo "Iniciando actualización de datos de la Doctora...\n";

// 1. Intentar actualizar el usuario Yerson (ID 2 o login 'yomarin') para convertirlo en el usuario de la Doctora 'lorena'
$stmt1 = $conn->prepare("UPDATE usuarios SET nombre = 'LORENA ESPINOZA GUTIERREZ', usuario = 'lorena', rol = 'Admin', colegiatura = '55272' WHERE id = 2 OR usuario = 'yomarin'");
if ($stmt1 && $stmt1->execute()) {
    if ($conn->affected_rows > 0) {
        echo "Éxito: Se convirtió/actualizó la cuenta a 'lorena' (Nombre: LORENA ESPINOZA GUTIERREZ, COP: 55272, Rol: Administrador General).\n";
    } else {
        echo "Aviso: No se requirieron cambios de conversión para 'yomarin'.\n";
    }
} else {
    echo "Error al convertir usuario yomarin: " . $conn->error . "\n";
}

// 2. Por seguridad, si ya existía una cuenta independiente con el usuario 'lorena', asegurar que tenga la colegiatura y datos correctos
$stmt2 = $conn->prepare("UPDATE usuarios SET nombre = 'LORENA ESPINOZA GUTIERREZ', rol = 'Admin', colegiatura = '55272' WHERE usuario = 'lorena'");
if ($stmt2 && $stmt2->execute()) {
    if ($conn->affected_rows > 0) {
        echo "Éxito: Se actualizaron los datos adicionales del usuario 'lorena' (COP: 55272, Rol: Administrador General).\n";
    }
} else {
    echo "Error al actualizar usuario 'lorena': " . $conn->error . "\n";
}

echo "Nota: La cuenta de soporte 'admin' (Administrador General) se ha mantenido intacta para pruebas y soporte de desarrollo.\n";
echo "Proceso finalizado.\n";
?>
