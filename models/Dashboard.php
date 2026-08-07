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

    public function getPacientesNuevosMesAnterior($mes, $anio) {
        $mesAnterior = $mes - 1;
        $anioAnterior = $anio;
        if ($mesAnterior <= 0) { $mesAnterior = 12; $anioAnterior--; }
        $sql = "SELECT COUNT(*) as total FROM pacientes WHERE MONTH(fecha_registro) = ? AND YEAR(fecha_registro) = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $mesAnterior, $anioAnterior);
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
        $sql = "SELECT COUNT(*) as total FROM citas WHERE fecha = ? AND estado IN ('Pendiente', 'Confirmada')";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $fecha_hoy);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'];
    }

    public function getProximasCitasHoy($fecha_hoy) {
        $sql = "SELECT c.*, p.nombre as paciente_nombre 
                FROM citas c 
                JOIN pacientes p ON c.paciente_id = p.id 
                WHERE c.fecha = ? AND c.estado IN ('Pendiente', 'Confirmada')
                ORDER BY c.hora_inicio ASC LIMIT 5";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $fecha_hoy);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Ingresos totales del mes (pagos recibidos)
    public function getIngresosMes($mes, $anio) {
        $sql = "SELECT COALESCE(SUM(monto), 0) as total FROM pagos WHERE MONTH(fecha_pago) = ? AND YEAR(fecha_pago) = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $mes, $anio);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'];
    }

    // Presupuestos activos (Borrador + Enviado + Aprobado)
    public function getPresupuestosActivos() {
        $sql = "SELECT COUNT(*) as total FROM presupuestos WHERE estado IN ('Borrador', 'Enviado', 'Aprobado')";
        $result = $this->conn->query($sql);
        return $result->fetch_assoc()['total'];
    }

    // Citas completadas esta semana
    public function getCitasCompletadasSemana() {
        $sql = "SELECT COUNT(*) as total FROM citas WHERE estado = 'Completada' AND YEARWEEK(fecha, 1) = YEARWEEK(CURDATE(), 1)";
        $result = $this->conn->query($sql);
        return $result->fetch_assoc()['total'];
    }

    // Datos para grafico: citas por dia en los ultimos 7 dias
    public function getCitasUltimos7Dias() {
        $sql = "SELECT DATE(fecha) as dia, COUNT(*) as total 
                FROM citas 
                WHERE fecha BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND CURDATE()
                GROUP BY DATE(fecha) 
                ORDER BY dia ASC";
        $result = $this->conn->query($sql);
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[$row['dia']] = intval($row['total']);
        }
        // Rellenar dias faltantes con 0
        $dias = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $dias[$d] = $data[$d] ?? 0;
        }
        return $dias;
    }

    // Datos para grafico: ingresos ultimos 6 meses
    public function getIngresosUltimos6Meses() {
        $sql = "SELECT DATE_FORMAT(fecha_pago, '%Y-%m') as mes, COALESCE(SUM(monto), 0) as total 
                FROM pagos 
                WHERE fecha_pago >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) 
                GROUP BY DATE_FORMAT(fecha_pago, '%Y-%m') 
                ORDER BY mes ASC";
        $result = $this->conn->query($sql);
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[$row['mes']] = floatval($row['total']);
        }
        // Rellenar meses faltantes
        $meses = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = date('Y-m', strtotime("-$i months"));
            $meses[$m] = $data[$m] ?? 0;
        }
        return $meses;
    }

    // Distribucion de tratamientos mas solicitados
    public function getTratamientosMasSolicitados() {
        $sql = "SELECT descripcion, COUNT(*) as cantidad 
                FROM presupuesto_items 
                GROUP BY descripcion 
                ORDER BY cantidad DESC 
                LIMIT 5";
        $result = $this->conn->query($sql);
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }
}
?>
