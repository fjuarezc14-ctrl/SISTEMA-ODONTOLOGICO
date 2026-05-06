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
    
    public function show($id) {
        return $this->pacienteModel->getById($id);
    }
    
    public function store($data) {
        if(empty(trim($data['dni'])) || empty(trim($data['nombre']))) {
            return "El DNI y el Nombre son campos obligatorios.";
        }
        
        $existente = $this->pacienteModel->getByDni($data['dni']);
        if ($existente) {
            return "El paciente con DNI " . $data['dni'] . " ya está registrado en el sistema.";
        }

        return $this->pacienteModel->create(
            $data['dni'], 
            $data['nombre'], 
            $data['telefono'], 
            $data['email']
        );
    }
}
?>
