<?php
require_once 'config/conexion.php';
require_once 'models/Dashboard.php';

class DashboardController {
    private $dashboardModel;
    
    public function __construct() {
        global $conn;
        $this->dashboardModel = new Dashboard($conn);
    }
    
    public function getStats() {
        return [
            'total_pacientes' => $this->dashboardModel->getTotalPacientes(),
            'nuevos_mes' => $this->dashboardModel->getPacientesNuevosMes(),
            'citas_mes' => $this->dashboardModel->getCitasMes(),
            'citas_hoy' => $this->dashboardModel->getCitasHoy()
        ];
    }

    public function getProximasCitas() {
        return $this->dashboardModel->getProximasCitasHoy();
    }
}
?>
