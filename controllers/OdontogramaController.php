<?php
require_once 'config/conexion.php';
require_once 'models/Odontograma.php';

class OdontogramaController {
    private $odontogramaModel;

    public function __construct() {
        global $conn;
        $this->odontogramaModel = new Odontograma($conn);
    }

    public function getByPaciente($paciente_id) {
        return $this->odontogramaModel->getByPaciente($paciente_id);
    }

    public function store($data) {
        $paciente_id = $data['paciente_id'] ?? null;
        $diente_numero = $data['diente_numero'] ?? null;
        $cara_afectada = $data['cara_afectada'] ?? null;
        $estado = $data['estado'] ?? '';
        $notas = $data['notas'] ?? '';

        if (!$paciente_id || !$diente_numero || !$cara_afectada) {
            return false;
        }

        return $this->odontogramaModel->store($paciente_id, $diente_numero, $cara_afectada, $estado, $notas);
    }
}
?>
