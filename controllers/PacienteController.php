<?php
require_once 'config/conexion.php';
require_once 'models/Paciente.php';

class PacienteController {
    private $pacienteModel;
    
    public function __construct() {
        global $conn;
        $this->pacienteModel = new Paciente($conn);
    }
    
    public function index() {
        return $this->pacienteModel->getAll();
    }
    
    public function store($data) {
        return $this->pacienteModel->create(
            $data['dni'], 
            $data['nombre'], 
            $data['telefono'], 
            $data['email']
        );
    }
}
?>
