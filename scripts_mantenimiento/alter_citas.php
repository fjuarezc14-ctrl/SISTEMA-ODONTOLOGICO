<?php
require_once 'config/conexion.php';
$sql = "ALTER TABLE citas MODIFY COLUMN estado ENUM('Pendiente', 'Confirmada', 'Completada', 'Cancelada', 'En Curso') DEFAULT 'Pendiente'";
if ($conn->query($sql) === TRUE) {
    echo "ENUM actualizado correctamente.\n";
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
