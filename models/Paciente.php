<?php
class Paciente {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($dni, $nombre, $telefono, $email) {
        $sql = "INSERT INTO pacientes (dni, nombre, telefono, email) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssss", $dni, $nombre, $telefono, $email);
        return $stmt->execute() ? true : $this->conn->error;
    }

    public function getAll() {
        $sql = "SELECT * FROM pacientes ORDER BY fecha_registro DESC";
        return $this->conn->query($sql);
    }
}
?>
