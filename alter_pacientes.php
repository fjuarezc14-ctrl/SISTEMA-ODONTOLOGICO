<?php
require_once 'config/conexion.php';
$queries = [
    "ALTER TABLE pacientes ADD COLUMN fecha_nacimiento DATE NULL AFTER nombre;",
    "ALTER TABLE pacientes ADD COLUMN sexo VARCHAR(15) NULL AFTER fecha_nacimiento;",
    "ALTER TABLE pacientes ADD COLUMN direccion VARCHAR(255) NULL AFTER email;",
    "ALTER TABLE pacientes ADD COLUMN ocupacion VARCHAR(100) NULL AFTER direccion;",
    "ALTER TABLE pacientes ADD COLUMN motivo_consulta TEXT NULL AFTER ocupacion;"
];

foreach ($queries as $q) {
    if ($conn->query($q)) {
        echo "Exito: $q\n";
    } else {
        echo "Error en: $q - " . $conn->error . "\n";
    }
}
?>
