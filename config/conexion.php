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

// Auto-migración silenciosa (se ejecuta una sola vez por sesión para optimizar rendimiento)
if (empty($_SESSION['sys_db_synced_2026'])) {
    $check_doctora = $conn->query("SELECT id FROM usuarios WHERE usuario = 'lorena' AND colegiatura = '55272'");
    if ($check_doctora && $check_doctora->num_rows === 0) {
        $conn->query("UPDATE usuarios SET nombre = 'LORENA ESPINOZA GUTIERREZ', usuario = 'lorena', rol = 'Admin', colegiatura = '55272' WHERE id = 2 OR usuario = 'yomarin'");
        $conn->query("UPDATE usuarios SET nombre = 'LORENA ESPINOZA GUTIERREZ', rol = 'Admin', colegiatura = '55272' WHERE usuario = 'lorena'");
    }

    $check_sellante = $conn->query("SELECT id FROM catalogo_tratamientos WHERE nombre = 'Sellante Dental'");
    if ($check_sellante && $check_sellante->num_rows === 0) {
        $conn->query("INSERT INTO catalogo_tratamientos (nombre, precio_base, categoria, estado_odontograma, activo) VALUES ('Sellante Dental', 40.00, 'Prevencion', 'sellante', 1)");
    }
    $check_corona_temp = $conn->query("SELECT id FROM catalogo_tratamientos WHERE nombre = 'Corona Temporal'");
    if ($check_corona_temp && $check_corona_temp->num_rows === 0) {
        $conn->query("INSERT INTO catalogo_tratamientos (nombre, precio_base, categoria, estado_odontograma, activo) VALUES ('Corona Temporal', 90.00, 'Protesis Fija', 'corona_temporal', 1)");
    }
    $check_diastema = $conn->query("SELECT id FROM catalogo_tratamientos WHERE nombre = 'Cierre de Diastema'");
    if ($check_diastema && $check_diastema->num_rows === 0) {
        $conn->query("INSERT INTO catalogo_tratamientos (nombre, precio_base, categoria, estado_odontograma, activo) VALUES ('Cierre de Diastema', 100.00, 'Estetica', 'diastema', 1)");
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['sys_db_synced_2026'] = true;
    }
}
?>