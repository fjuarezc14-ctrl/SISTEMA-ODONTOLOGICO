<?php
class Archivo {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getByPaciente($paciente_id) {
        $sql = "SELECT a.*, u.nombre as subido_por_nombre 
                FROM archivos_clinicos a
                LEFT JOIN usuarios u ON a.subido_por = u.id
                WHERE a.paciente_id = ?
                ORDER BY a.fecha_subida DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $paciente_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $archivos = [];
        while ($row = $result->fetch_assoc()) {
            $archivos[] = $row;
        }
        return $archivos;
    }

    public function getById($id) {
        $sql = "SELECT * FROM archivos_clinicos WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }

    public function registrar($paciente_id, $tipo, $nombre_archivo, $ruta_archivo, $descripcion, $tamano, $subido_por) {
        $sql = "INSERT INTO archivos_clinicos (paciente_id, tipo, nombre_archivo, ruta_archivo, descripcion, tamano, subido_por)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("issssis", $paciente_id, $tipo, $nombre_archivo, $ruta_archivo, $descripcion, $tamano, $subido_por);
        
        // Fix string format: i(paciente) s(tipo) s(nombre) s(ruta) s(desc) i(tamano) i(subido_por)
        // Correct format is issssii
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("issssii", $paciente_id, $tipo, $nombre_archivo, $ruta_archivo, $descripcion, $tamano, $subido_por);
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        return false;
    }

    public function delete($id) {
        $sql = "DELETE FROM archivos_clinicos WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
?>
