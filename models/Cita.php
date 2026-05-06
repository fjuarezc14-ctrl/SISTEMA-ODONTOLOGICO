<?php
class Cita {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Obtener las citas de una semana específica (basada en una fecha)
    public function getCitasSemana($fecha_inicio, $fecha_fin) {
        $sql = "SELECT c.*, p.nombre as paciente_nombre 
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
}
?>
