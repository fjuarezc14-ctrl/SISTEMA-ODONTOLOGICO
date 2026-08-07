<?php
// Configurar zona horaria por defecto para todo el sistema PHP
date_default_timezone_set('America/Lima'); // UTC-5

$host = "db";
$user = "root";
$password = "mahudent_dev_secret_pass_2026";
$dbname = "mahudent_db";

// Cargar configuración local/producción si existe (no sobreescrita por git pull)
if (file_exists(__DIR__ . '/conexion.local.php')) {
    include __DIR__ . '/conexion.local.php';
}

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Configurar zona horaria para la sesión de MySQL
$conn->query("SET time_zone = '-05:00'");
$conn->set_charset("utf8mb4");

// Auto-migración silenciosa para los datos de la Doctora (se ejecuta automáticamente al hacer pull y abrir el sistema)
$check_doctora = $conn->query("SELECT id FROM usuarios WHERE usuario = 'lorena' AND colegiatura = '55272'");
if ($check_doctora && $check_doctora->num_rows === 0) {
    $conn->query("UPDATE usuarios SET nombre = 'LORENA ESPINOZA GUTIERREZ', usuario = 'lorena', rol = 'Admin', colegiatura = '55272' WHERE id = 2 OR usuario = 'yomarin'");
    $conn->query("UPDATE usuarios SET nombre = 'LORENA ESPINOZA GUTIERREZ', rol = 'Admin', colegiatura = '55272' WHERE usuario = 'lorena'");
}
?>