<?php
require_once 'config/conexion.php';
$result = $conn->query("DESCRIBE pacientes");
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
