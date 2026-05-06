<?php
class Dashboard {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getTotalPacientes() {
        $sql = "SELECT COUNT(*) as total FROM pacientes";
        $result = $this->conn->query($sql);
        return $result->fetch_assoc()['total'];
    }

    public function getPacientesNuevosMes() {
        $sql = "SELECT COUNT(*) as total FROM pacientes WHERE MONTH(fecha_registro) = MONTH(CURRENT_DATE()) AND YEAR(fecha_registro) = YEAR(CURRENT_DATE())";
        $result = $this->conn->query($sql);
        return $result->fetch_assoc()['total'];
    }

    public function getCitasMes() {
        $sql = "SELECT COUNT(*) as total FROM citas WHERE MONTH(fecha) = MONTH(CURRENT_DATE()) AND YEAR(fecha) = YEAR(CURRENT_DATE())";
        $result = $this->conn->query($sql);
        return $result->fetch_assoc()['total'];
    }

    public function getCitasHoy() {
        $sql = "SELECT COUNT(*) as total FROM citas WHERE fecha = CURRENT_DATE() AND estado = 'Pendiente'";
        $result = $this->conn->query($sql);
        return $result->fetch_assoc()['total'];
    }

    public function getProximasCitasHoy() {
        $sql = "SELECT c.*, p.nombre as paciente_nombre 
                FROM citas c 
                JOIN pacientes p ON c.paciente_id = p.id 
                WHERE c.fecha = CURRENT_DATE() AND c.estado = 'Pendiente' 
                ORDER BY c.hora_inicio ASC LIMIT 5";
        return $this->conn->query($sql);
    }
}
?>
