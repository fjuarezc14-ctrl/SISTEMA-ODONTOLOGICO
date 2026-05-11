<?php
class Pago {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getByPresupuesto($presupuesto_id) {
        $sql = "SELECT p.*, u.nombre as registrado_nombre 
                FROM pagos p 
                LEFT JOIN usuarios u ON p.registrado_por = u.id
                WHERE p.presupuesto_id = ? 
                ORDER BY p.fecha_pago DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $presupuesto_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $pagos = [];
        while ($row = $result->fetch_assoc()) {
            $pagos[] = $row;
        }
        return $pagos;
    }

    public function getByPaciente($paciente_id) {
        $sql = "SELECT p.*, pr.id as presupuesto_num, u.nombre as registrado_nombre
                FROM pagos p
                LEFT JOIN presupuestos pr ON p.presupuesto_id = pr.id
                LEFT JOIN usuarios u ON p.registrado_por = u.id
                WHERE p.paciente_id = ?
                ORDER BY p.fecha_pago DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $paciente_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $pagos = [];
        while ($row = $result->fetch_assoc()) {
            $pagos[] = $row;
        }
        return $pagos;
    }

    public function registrar($presupuesto_id, $paciente_id, $monto, $metodo_pago, $tipo, $comprobante_tipo, $comprobante_numero, $notas, $registrado_por) {
        $sql = "INSERT INTO pagos (presupuesto_id, paciente_id, monto, metodo_pago, tipo, comprobante_tipo, comprobante_numero, notas, registrado_por)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iidssssi", $presupuesto_id, $paciente_id, $monto, $metodo_pago, $tipo, $comprobante_tipo, $comprobante_numero, $notas, $registrado_por);
        
        // Fix: necesitamos 9 params pero string "iidssssi" = 8 chars
        // presupuesto_id(i), paciente_id(i), monto(d), metodo_pago(s), tipo(s), comprobante_tipo(s), comprobante_numero(s), notas(s), registrado_por(i)
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iidsssssi", $presupuesto_id, $paciente_id, $monto, $metodo_pago, $tipo, $comprobante_tipo, $comprobante_numero, $notas, $registrado_por);
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        return false;
    }

    public function getById($id) {
        $sql = "SELECT p.*, u.nombre as registrado_nombre,
                       pac.nombre as paciente_nombre, pac.dni as paciente_dni
                FROM pagos p
                LEFT JOIN usuarios u ON p.registrado_por = u.id
                LEFT JOIN pacientes pac ON p.paciente_id = pac.id
                WHERE p.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }

    /**
     * Calcula totales de pagos de un presupuesto
     */
    public function getTotalPagado($presupuesto_id) {
        $sql = "SELECT COALESCE(SUM(monto), 0) as total_pagado FROM pagos WHERE presupuesto_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $presupuesto_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return floatval($row['total_pagado']);
    }

    /**
     * Obtiene el siguiente numero de comprobante
     */
    public function getSiguienteComprobante($tipo = 'Boleta') {
        $prefijo = $tipo === 'Factura' ? 'F' : 'B';
        $sql = "SELECT comprobante_numero FROM pagos WHERE comprobante_tipo = ? AND comprobante_numero IS NOT NULL ORDER BY id DESC LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $tipo);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $num = intval(preg_replace('/[^0-9]/', '', $row['comprobante_numero']));
            return $prefijo . '-' . str_pad($num + 1, 6, '0', STR_PAD_LEFT);
        }
        return $prefijo . '-000001';
    }
}
?>
