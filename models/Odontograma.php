<?php
class Odontograma {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getByPaciente($paciente_id) {
        $sql = "SELECT * FROM odontograma_estado WHERE paciente_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $paciente_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $hallazgos = [];
        while($row = $result->fetch_assoc()) {
            $hallazgos[] = $row;
        }
        return $hallazgos;
    }

    public function store($paciente_id, $diente_numero, $cara_afectada, $estado, $notas) {
        // Verificar si ya existe un registro para ese diente y esa cara
        $sqlCheck = "SELECT id FROM odontograma_estado WHERE paciente_id = ? AND diente_numero = ? AND cara_afectada = ?";
        $stmtCheck = $this->conn->prepare($sqlCheck);
        $stmtCheck->bind_param("iis", $paciente_id, $diente_numero, $cara_afectada);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();

        if ($resultCheck->num_rows > 0) {
            // Actualizar si ya existe
            $row = $resultCheck->fetch_assoc();
            
            // Si el estado es vacío, significa que queremos "Limpiar" ese hallazgo (Borrarlo)
            if (empty($estado)) {
                return $this->delete($row['id']);
            }

            $sqlUpdate = "UPDATE odontograma_estado SET estado = ?, notas = ? WHERE id = ?";
            $stmtUpdate = $this->conn->prepare($sqlUpdate);
            $stmtUpdate->bind_param("ssi", $estado, $notas, $row['id']);
            return $stmtUpdate->execute();
        } else {
            // Si el estado es vacío, no insertamos nada
            if (empty($estado)) return true;

            // Insertar nuevo registro
            $sqlInsert = "INSERT INTO odontograma_estado (paciente_id, diente_numero, cara_afectada, estado, notas) VALUES (?, ?, ?, ?, ?)";
            $stmtInsert = $this->conn->prepare($sqlInsert);
            $stmtInsert->bind_param("iisss", $paciente_id, $diente_numero, $cara_afectada, $estado, $notas);
            return $stmtInsert->execute();
        }
    }

    public function delete($id) {
        $sql = "DELETE FROM odontograma_estado WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function saveBatch($paciente_id, $items) {
        $this->conn->begin_transaction();
        try {
            $sqlDelete = "DELETE FROM odontograma_estado WHERE paciente_id = ?";
            $stmtDel = $this->conn->prepare($sqlDelete);
            $stmtDel->bind_param("i", $paciente_id);
            $stmtDel->execute();

            if (!empty($items) && is_array($items)) {
                $sqlIns = "INSERT INTO odontograma_estado (paciente_id, diente_numero, cara_afectada, estado, notas) VALUES (?, ?, ?, ?, ?)";
                $stmtIns = $this->conn->prepare($sqlIns);
                foreach ($items as $item) {
                    $diente = intval($item['diente_numero'] ?? $item['tooth'] ?? 0);
                    $cara = strval($item['cara_afectada'] ?? $item['surface'] ?? '0');
                    $estado = strval($item['estado'] ?? $item['damage'] ?? '');
                    $notas = strval($item['notas'] ?? $item['note'] ?? '');
                    if ($diente > 0) {
                        $stmtIns->bind_param("iisss", $paciente_id, $diente, $cara, $estado, $notas);
                        $stmtIns->execute();
                    }
                }
            }
            $this->conn->commit();
            return true;
        } catch (\Throwable $e) {
            $this->conn->rollback();
            return false;
        }
    }
}
?>
