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

    public function getById($id) {
        $sql = "SELECT * FROM pacientes WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }

    public function getByDni($dni) {
        $sql = "SELECT id FROM pacientes WHERE dni = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $dni);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }
}
?>
