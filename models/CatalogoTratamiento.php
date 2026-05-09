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
