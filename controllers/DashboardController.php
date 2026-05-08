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
        $hoy = date('Y-m-d');
        $mes = date('m');
        $anio = date('Y');

        return [
            'total_pacientes' => $this->dashboardModel->getTotalPacientes(),
            'nuevos_mes' => $this->dashboardModel->getPacientesNuevosMes($mes, $anio),
            'citas_mes' => $this->dashboardModel->getCitasMes($mes, $anio),
            'citas_hoy' => $this->dashboardModel->getCitasHoy($hoy)
        ];
    }

    public function getProximasCitas() {
        $hoy = date('Y-m-d');
        return $this->dashboardModel->getProximasCitasHoy($hoy);
    }
}
?>
