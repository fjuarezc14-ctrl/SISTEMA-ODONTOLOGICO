<?php
require_once 'config/conexion.php';
require_once 'models/Evolucion.php';

class EvolucionController {
    private $evolucionModel;

    public function __construct() {
        global $conn;
        $this->evolucionModel = new Evolucion($conn);
    }

    public function getByPaciente($paciente_id) {
        return $this->evolucionModel->getByPaciente($paciente_id);
    }

    public function store($data) {
        $paciente_id = $data['paciente_id'] ?? null;
        $cita_id = $data['cita_id'] ?? null; // Puede ser null si es una evolución rápida sin cita
        $descripcion = $data['descripcion'] ?? null;
        $doctor_id = $_SESSION['usuario_id'] ?? null; // Asumimos que la sesión tiene el ID del doctor logueado

        if (!$paciente_id || !$descripcion || !$doctor_id) {
            return false;
        }

        return $this->evolucionModel->store($paciente_id, $cita_id, $descripcion, $doctor_id);
    }
}
?>
