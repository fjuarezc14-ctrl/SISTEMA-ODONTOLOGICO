<?php
class Presupuesto {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getByPaciente($paciente_id) {
        $sql = "SELECT p.*, u.nombre as doctor_nombre 
                FROM presupuestos p 
                LEFT JOIN usuarios u ON p.doctor_id = u.id 
                WHERE p.paciente_id = ? 
                ORDER BY p.fecha_creacion DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $paciente_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $presupuestos = [];
        while ($row = $result->fetch_assoc()) {
            $presupuestos[] = $row;
        }
        return $presupuestos;
    }

    public function getAll() {
        $sql = "SELECT p.*, u.nombre as doctor_nombre, pac.nombre as paciente_nombre, pac.dni as paciente_dni, pac.estado_activo as paciente_estado_activo
                FROM presupuestos p 
                LEFT JOIN usuarios u ON p.doctor_id = u.id 
                LEFT JOIN pacientes pac ON p.paciente_id = pac.id
                ORDER BY p.fecha_creacion DESC";
        $result = $this->conn->query($sql);
        $presupuestos = [];
        while ($row = $result->fetch_assoc()) {
            $presupuestos[] = $row;
        }
        return $presupuestos;
    }

    public function getById($id) {
        $sql = "SELECT p.*, u.nombre as doctor_nombre, pac.nombre as paciente_nombre, pac.dni as paciente_dni
                FROM presupuestos p 
                LEFT JOIN usuarios u ON p.doctor_id = u.id 
                LEFT JOIN pacientes pac ON p.paciente_id = pac.id
                WHERE p.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }

    public function create($paciente_id, $doctor_id, $fecha_emision, $fecha_vigencia, $subtotal, $descuento_porcentaje, $descuento_monto, $total, $notas) {
        $sql = "INSERT INTO presupuestos (paciente_id, doctor_id, fecha_emision, fecha_vigencia, subtotal, descuento_porcentaje, descuento_monto, total, notas) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iissdddds", $paciente_id, $doctor_id, $fecha_emision, $fecha_vigencia, $subtotal, $descuento_porcentaje, $descuento_monto, $total, $notas);
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        return false;
    }

    public function updateTotales($id, $subtotal, $descuento_porcentaje, $descuento_monto, $total) {
        $sql = "UPDATE presupuestos SET subtotal = ?, descuento_porcentaje = ?, descuento_monto = ?, total = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ddddi", $subtotal, $descuento_porcentaje, $descuento_monto, $total, $id);
        return $stmt->execute();
    }

    public function updateEstado($id, $estado) {
        $sql = "UPDATE presupuestos SET estado = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $estado, $id);
        return $stmt->execute();
    }

    public function delete($id) {
        $sql = "DELETE FROM presupuestos WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // --- Ítems del Presupuesto ---

    public function getItems($presupuesto_id) {
        $sql = "SELECT pi.*, ct.nombre as tratamiento_nombre, ct.categoria 
                FROM presupuesto_items pi 
                LEFT JOIN catalogo_tratamientos ct ON pi.tratamiento_id = ct.id 
                WHERE pi.presupuesto_id = ? 
                ORDER BY pi.id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $presupuesto_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        return $items;
    }

    public function addItem($presupuesto_id, $tratamiento_id, $diente_numero, $descripcion, $cantidad, $precio_unitario, $precio_ajustado, $subtotal) {
        $sql = "INSERT INTO presupuesto_items (presupuesto_id, tratamiento_id, diente_numero, descripcion, cantidad, precio_unitario, precio_ajustado, subtotal) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiisiddd", $presupuesto_id, $tratamiento_id, $diente_numero, $descripcion, $cantidad, $precio_unitario, $precio_ajustado, $subtotal);
        return $stmt->execute() ? $this->conn->insert_id : false;
    }

    public function updateItem($id, $descripcion, $cantidad, $precio_unitario, $precio_ajustado, $subtotal) {
        $sql = "UPDATE presupuesto_items SET descripcion = ?, cantidad = ?, precio_unitario = ?, precio_ajustado = ?, subtotal = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sidddi", $descripcion, $cantidad, $precio_unitario, $precio_ajustado, $subtotal, $id);
        return $stmt->execute();
    }

    public function removeItem($id) {
        $sql = "DELETE FROM presupuesto_items WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
?>
