<?php
class Odontograma {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getEstadoByPaciente($paciente_id) {
        $sql = "SELECT diente_numero, cara_afectada, estado, notas FROM odontograma_estado WHERE paciente_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $paciente_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $datos = [];
        while($row = $result->fetch_assoc()) {
            $datos[] = $row;
        }
        return $datos;
    }

    public function saveEstado($paciente_id, $diente, $cara, $estado, $notas) {
        $sql = "INSERT INTO odontograma_estado (paciente_id, diente_numero, cara_afectada, estado, notas) 
                VALUES (?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE estado = VALUES(estado), notas = VALUES(notas)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iisss", $paciente_id, $diente, $cara, $estado, $notas);
        return $stmt->execute();
    }
}
?>
