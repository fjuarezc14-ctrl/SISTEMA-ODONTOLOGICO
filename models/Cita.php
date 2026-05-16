<?php
class Cita {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Obtener las citas de una semana específica (basada en una fecha)
    public function getCitasSemana($fecha_inicio, $fecha_fin) {
        $sql = "SELECT c.*, p.nombre as paciente_nombre, p.telefono as paciente_telefono 
                FROM citas c 
                JOIN pacientes p ON c.paciente_id = p.id 
                WHERE c.fecha >= ? AND c.fecha <= ?
                ORDER BY c.fecha ASC, c.hora_inicio ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $citas = [];
        while($row = $result->fetch_assoc()) {
            $citas[] = $row;
        }
        return $citas;
    }

    // Crear una nueva cita
    public function create($paciente_id, $doctor_id, $fecha, $hora_inicio, $hora_fin, $motivo) {
        $sql = "INSERT INTO citas (paciente_id, doctor_id, fecha, hora_inicio, hora_fin, motivo, estado) VALUES (?, ?, ?, ?, ?, ?, 'Pendiente')";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iissss", $paciente_id, $doctor_id, $fecha, $hora_inicio, $hora_fin, $motivo);
        return $stmt->execute() ? true : $this->conn->error;
    }

    // Verificar si existe cruce de horarios para un doctor en una fecha
    public function verificarCruce($doctor_id, $fecha, $hora_inicio, $hora_fin) {
        $sql = "SELECT id FROM citas 
                WHERE doctor_id = ? 
                AND fecha = ? 
                AND estado != 'Cancelada'
                AND (
                    (hora_inicio < ? AND hora_fin > ?) OR
                    (hora_inicio < ? AND hora_fin > ?) OR
                    (hora_inicio >= ? AND hora_fin <= ?)
                )";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isssssss", $doctor_id, $fecha, $hora_fin, $hora_inicio, $hora_inicio, $hora_inicio, $hora_inicio, $hora_fin);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    // Verificar si existe cruce de horarios excluyendo una cita específica (para reprogramación)
    public function verificarCruceExcluyendo($doctor_id, $fecha, $hora_inicio, $hora_fin, $cita_id_excluida) {
        $sql = "SELECT id FROM citas 
                WHERE doctor_id = ? 
                AND fecha = ? 
                AND id != ?
                AND estado != 'Cancelada'
                AND (
                    (hora_inicio < ? AND hora_fin > ?) OR
                    (hora_inicio < ? AND hora_fin > ?) OR
                    (hora_inicio >= ? AND hora_fin <= ?)
                )";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isissssss", $doctor_id, $fecha, $cita_id_excluida, $hora_fin, $hora_inicio, $hora_inicio, $hora_inicio, $hora_inicio, $hora_fin);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }


    public function getById($id) {
        $sql = "SELECT c.*, p.nombre as paciente_nombre 
                FROM citas c 
                JOIN pacientes p ON c.paciente_id = p.id 
                WHERE c.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }

    public function updateEstado($id, $estado) {
        $sql = "UPDATE citas SET estado = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $estado, $id);
        return $stmt->execute() ? true : $this->conn->error;
    }

    public function updateFechas($id, $fecha, $hora_inicio, $hora_fin) {
        $sql = "UPDATE citas SET fecha = ?, hora_inicio = ?, hora_fin = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssi", $fecha, $hora_inicio, $hora_fin, $id);
        return $stmt->execute() ? true : $this->conn->error;
    }

    public function delete($id) {
        $sql = "DELETE FROM citas WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute() ? true : $this->conn->error;
    }
}
?>
