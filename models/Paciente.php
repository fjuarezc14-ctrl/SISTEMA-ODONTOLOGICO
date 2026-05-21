<?php
class Paciente {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($dni, $nombre, $telefono, $email, $alergias = null, $fecha_nacimiento = null, $lugar_nacimiento = null, $sexo = null, $direccion = null, $procedencia = null, $ocupacion = null, $contacto_emergencia = null, $telefono_emergencia = null) {
        $sql = "INSERT INTO pacientes (dni, nombre, telefono, email, alergias, fecha_nacimiento, lugar_nacimiento, sexo, direccion, procedencia, ocupacion, contacto_emergencia, telefono_emergencia, estado_activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssssssssssss", $dni, $nombre, $telefono, $email, $alergias, $fecha_nacimiento, $lugar_nacimiento, $sexo, $direccion, $procedencia, $ocupacion, $contacto_emergencia, $telefono_emergencia);
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
    public function update($id, $dni, $nombre, $telefono, $email, $alergias = null, $fecha_nacimiento = null, $lugar_nacimiento = null, $sexo = null, $direccion = null, $procedencia = null, $ocupacion = null, $contacto_emergencia = null, $telefono_emergencia = null) {
        $sql = "UPDATE pacientes SET dni = ?, nombre = ?, telefono = ?, email = ?, alergias = ?, fecha_nacimiento = ?, lugar_nacimiento = ?, sexo = ?, direccion = ?, procedencia = ?, ocupacion = ?, contacto_emergencia = ?, telefono_emergencia = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssssssssssssi", $dni, $nombre, $telefono, $email, $alergias, $fecha_nacimiento, $lugar_nacimiento, $sexo, $direccion, $procedencia, $ocupacion, $contacto_emergencia, $telefono_emergencia, $id);
        return $stmt->execute() ? true : $this->conn->error;
    }

    public function softDelete($id) {
        $sql = "UPDATE pacientes SET estado_activo = 0 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute() ? true : $this->conn->error;
    }

    public function restore($id) {
        $sql = "UPDATE pacientes SET estado_activo = 1 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute() ? true : $this->conn->error;
    }

    public function getInhabilitados() {
        $sql = "SELECT * FROM pacientes WHERE estado_activo = 0 ORDER BY fecha_registro DESC";
        return $this->conn->query($sql);
    }
}
?>
