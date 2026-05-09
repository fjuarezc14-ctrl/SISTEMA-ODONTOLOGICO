<?php
class Evolucion {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getByPaciente($paciente_id) {
        $sql = "SELECT e.*, u.nombre as doctor_nombre 
                FROM historial_evolutivo e 
                LEFT JOIN usuarios u ON e.doctor_id = u.id 
                WHERE e.paciente_id = ? 
                ORDER BY e.fecha DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $paciente_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $historial = [];
        while($row = $result->fetch_assoc()) {
            $historial[] = $row;
        }
        return $historial;
    }

    public function store($paciente_id, $cita_id, $descripcion, $doctor_id) {
        $sql = "INSERT INTO historial_evolutivo (paciente_id, cita_id, descripcion, doctor_id) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iisi", $paciente_id, $cita_id, $descripcion, $doctor_id);
        
        if($stmt->execute()) {
            // Si la evolución viene de una cita, automáticamente marcamos la cita como completada
            if (!empty($cita_id)) {
                $sqlUpdateCita = "UPDATE citas SET estado = 'Completada' WHERE id = ?";
                $stmtUpdate = $this->conn->prepare($sqlUpdateCita);
                $stmtUpdate->bind_param("i", $cita_id);
                $stmtUpdate->execute();
            }
            return true;
        }
        return false;
    }
}
?>
