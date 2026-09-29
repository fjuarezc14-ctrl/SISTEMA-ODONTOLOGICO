<?php
require_once 'config/conexion.php';
$queries = [
    "ALTER TABLE pacientes DROP COLUMN motivo_consulta;",
    "ALTER TABLE pacientes ADD COLUMN lugar_nacimiento VARCHAR(100) NULL AFTER fecha_nacimiento;",
    "ALTER TABLE pacientes ADD COLUMN procedencia VARCHAR(100) NULL AFTER direccion;",
    "ALTER TABLE pacientes ADD COLUMN contacto_emergencia VARCHAR(150) NULL AFTER procedencia;",
    "ALTER TABLE pacientes ADD COLUMN telefono_emergencia VARCHAR(20) NULL AFTER contacto_emergencia;"
];

foreach ($queries as $q) {
    if ($conn->query($q)) {
        echo "Exito: $q\n";
    } else {
        echo "Error en: $q - " . $conn->error . "\n";
    }
}
?>
