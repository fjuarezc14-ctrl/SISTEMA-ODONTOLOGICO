<?php
// Configurar zona horaria por defecto para todo el sistema PHP
date_default_timezone_set('America/Lima'); // UTC-5

$host = "db";
$user = "root";
$password = "";
$dbname = "mahudent_db";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Configurar zona horaria para la sesión de MySQL
$conn->query("SET time_zone = '-05:00'");
$conn->set_charset("utf8mb4");
?>