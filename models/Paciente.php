<?php
class Paciente {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($dni, $nombre, $telefono, $email, $alergias = null) {
        $sql = "INSERT INTO pacientes (dni, nombre, telefono, email, alergias, estado_activo) VALUES (?, ?, ?, ?, ?, 1)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssss", $dni, $nombre, $telefono, $email, $alergias);
        return $stmt->execute() ? true : $this->conn->error;
    }

    public function getAll() {
        $sql = "SELECT * FROM pacientes WHERE estado_activo = 1 ORDER BY fecha_registro DESC";
        return $this->conn->query($sql);
    }

    public function getById($id) {
        $sql = "SELECT * FROM pacientes WHERE id = ? AND estado_activo = 1";
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
    public function update($id, $dni, $nombre, $telefono, $email, $alergias = null) {
        $sql = "UPDATE pacientes SET dni = ?, nombre = ?, telefono = ?, email = ?, alergias = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssssi", $dni, $nombre, $telefono, $email, $alergias, $id);
        return $stmt->execute() ? true : $this->conn->error;
    }

    public function softDelete($id) {
        $sql = "UPDATE pacientes SET estado_activo = 0 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute() ? true : $this->conn->error;
    }
}
?>
