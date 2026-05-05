<?php
$host = "db";
$user = "root";
$password = "";
$dbname = "mahudent_db"; // Asegúrate de que este sea el nombre de tu base de datos

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>