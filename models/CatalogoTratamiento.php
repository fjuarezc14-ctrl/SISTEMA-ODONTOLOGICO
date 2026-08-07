<?php
class CatalogoTratamiento {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $sql = "SELECT * FROM catalogo_tratamientos WHERE activo = 1 ORDER BY categoria, nombre";
        $result = $this->conn->query($sql);
        $tratamientos = [];
        while ($row = $result->fetch_assoc()) {
            $tratamientos[] = $row;
        }
        return $tratamientos;
    }

    public function getById($id) {
        $sql = "SELECT * FROM catalogo_tratamientos WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }

    /**
     * Verifica si ya existe un tratamiento con el mismo nombre (activo), excluyendo opcionalmente un id.
     * Retorna true si existe un conflicto/duplicado.
     */
    public function existeNombre($nombre, $excluir_id = null) {
        $sql = "SELECT id FROM catalogo_tratamientos WHERE nombre = ? AND activo = 1";
        $params = [$nombre];
        $types = "s";
        if ($excluir_id !== null) {
            $sql .= " AND id != ?";
            $params[] = $excluir_id;
            $types .= "i";
        }
        $sql .= " LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    public function getByEstadoOdontograma($estado) {
        $sql = "SELECT * FROM catalogo_tratamientos WHERE estado_odontograma = ? AND activo = 1 ORDER BY nombre";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $estado);
        $stmt->execute();
        $result = $stmt->get_result();
        $tratamientos = [];
        while ($row = $result->fetch_assoc()) {
            $tratamientos[] = $row;
        }
        return $tratamientos;
    }

    public function create($nombre, $descripcion, $precio_base, $categoria, $estado_odontograma = null) {
        $sql = "INSERT INTO catalogo_tratamientos (nombre, descripcion, precio_base, categoria, estado_odontograma) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssdss", $nombre, $descripcion, $precio_base, $categoria, $estado_odontograma);
        return $stmt->execute() ? $this->conn->insert_id : false;
    }

    public function update($id, $nombre, $descripcion, $precio_base, $categoria, $estado_odontograma = null) {
        $sql = "UPDATE catalogo_tratamientos SET nombre = ?, descripcion = ?, precio_base = ?, categoria = ?, estado_odontograma = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssdssi", $nombre, $descripcion, $precio_base, $categoria, $estado_odontograma, $id);
        return $stmt->execute();
    }

    public function delete($id) {
        $sql = "UPDATE catalogo_tratamientos SET activo = 0 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
?>
