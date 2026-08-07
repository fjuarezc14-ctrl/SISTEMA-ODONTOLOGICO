<?php
require_once 'config/conexion.php';

$sql = "CREATE TABLE IF NOT EXISTS archivos_clinicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    tamano INT NOT NULL,
    fecha_subida DATETIME DEFAULT CURRENT_TIMESTAMP,
    subido_por INT,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,
    FOREIGN KEY (subido_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql)) {
    echo "Table created successfully.";
} else {
    echo "Error: " . $conn->error;
}
?>
