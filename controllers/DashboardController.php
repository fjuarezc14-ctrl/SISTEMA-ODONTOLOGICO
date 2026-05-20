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

        $nuevos_mes = $this->dashboardModel->getPacientesNuevosMes($mes, $anio);
        $nuevos_anterior = $this->dashboardModel->getPacientesNuevosMesAnterior($mes, $anio);
        
        // Calcular porcentaje de crecimiento
        $porcentaje = 0;
        if ($nuevos_anterior > 0) {
            $porcentaje = round((($nuevos_mes - $nuevos_anterior) / $nuevos_anterior) * 100);
        } elseif ($nuevos_mes > 0) {
            $porcentaje = 100;
        }

        return [
            'total_pacientes' => $this->dashboardModel->getTotalPacientes(),
            'nuevos_mes' => $nuevos_mes,
            'nuevos_porcentaje' => $porcentaje,
            'citas_mes' => $this->dashboardModel->getCitasMes($mes, $anio),
            'citas_hoy' => $this->dashboardModel->getCitasHoy($hoy),
            'ingresos_mes' => $this->dashboardModel->getIngresosMes($mes, $anio),
            'presupuestos_activos' => $this->dashboardModel->getPresupuestosActivos(),
            'citas_completadas_semana' => $this->dashboardModel->getCitasCompletadasSemana()
        ];
    }

    public function getProximasCitas() {
        $hoy = date('Y-m-d');
        return $this->dashboardModel->getProximasCitasHoy($hoy);
    }

    public function getChartCitas7Dias() {
        return $this->dashboardModel->getCitasUltimos7Dias();
    }

    public function getChartIngresos6Meses() {
        return $this->dashboardModel->getIngresosUltimos6Meses();
    }

    public function getTratamientosTop() {
        return $this->dashboardModel->getTratamientosMasSolicitados();
    }
}
?>
