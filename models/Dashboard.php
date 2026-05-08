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

    public function getPacientesNuevosMes($mes, $anio) {
        $sql = "SELECT COUNT(*) as total FROM pacientes WHERE MONTH(fecha_registro) = ? AND YEAR(fecha_registro) = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $mes, $anio);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'];
    }

    public function getCitasMes($mes, $anio) {
        $sql = "SELECT COUNT(*) as total FROM citas WHERE MONTH(fecha) = ? AND YEAR(fecha) = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $mes, $anio);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'];
    }

    public function getCitasHoy($fecha_hoy) {
        $sql = "SELECT COUNT(*) as total FROM citas WHERE fecha = ? AND estado = 'Pendiente'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $fecha_hoy);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'];
    }

    public function getProximasCitasHoy($fecha_hoy) {
        $sql = "SELECT c.*, p.nombre as paciente_nombre 
                FROM citas c 
                JOIN pacientes p ON c.paciente_id = p.id 
                WHERE c.fecha = ? AND c.estado = 'Pendiente' 
                ORDER BY c.hora_inicio ASC LIMIT 5";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $fecha_hoy);
        $stmt->execute();
        return $stmt->get_result();
    }
}
?>
