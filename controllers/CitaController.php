<?php
require_once 'config/conexion.php';
require_once 'models/Cita.php';

class CitaController {
    private $citaModel;
    
    public function __construct() {
        global $conn;
        $this->citaModel = new Cita($conn);
    }
    
    // Obtiene las citas de la semana (Lunes a Sábado) basada en una fecha
    public function getAgendaSemanal($fecha_referencia = null) {
        if(!$fecha_referencia) {
            $fecha_referencia = date('Y-m-d');
        }
        
        $dia_semana_ref = date('N', strtotime($fecha_referencia)); // 1 (Lunes) a 7 (Domingo)
        
        // Calcular fecha del Lunes de esta semana
        $dias_para_lunes = $dia_semana_ref - 1;
        $lunes = date('Y-m-d', strtotime("$fecha_referencia -$dias_para_lunes days"));
        
        // Calcular fecha del Sábado de esta semana
        $sabado = date('Y-m-d', strtotime($lunes . ' + 5 days'));
        
        $citas_planas = $this->citaModel->getCitasSemana($lunes, $sabado);
        
        // Agrupar las citas por día (1=Lunes, 2=Martes... 6=Sábado) para pintarlas en las columnas HTML
        $agenda = [
            1 => [], 2 => [], 3 => [], 4 => [], 5 => [], 6 => []
        ];
        
        foreach($citas_planas as $cita) {
            $dia_semana = date('N', strtotime($cita['fecha']));
            if($dia_semana <= 6) { // Ignoramos domingos si los hubiera
                // Calcular posición en píxeles
                // 08:00 = top 0px. Cada hora son 60px. Cada minuto 1px.
                list($hora_inicio, $min_inicio, $seg) = explode(':', $cita['hora_inicio']);
                list($hora_fin, $min_fin, $seg_fin) = explode(':', $cita['hora_fin']);
                
                $minutos_inicio_total = ($hora_inicio * 60) + $min_inicio;
                $minutos_fin_total = ($hora_fin * 60) + $min_fin;
                
                // Base 08:00 = 480 minutos
                $top_px = $minutos_inicio_total - 480;
                // Si la cita es antes de las 8am, la ponemos al tope
                if($top_px < 0) $top_px = 0;
                
                $duracion_px = $minutos_fin_total - $minutos_inicio_total;
                
                $cita['css_top'] = $top_px;
                $cita['css_height'] = $duracion_px;
                
                // Colores según estado o tipo
                if($cita['estado'] == 'Completada') {
                    $cita['css_color'] = 'bg-emerald-50 border-emerald-500 text-emerald-800';
                    $cita['css_text_light'] = 'text-emerald-600';
                } elseif($cita['estado'] == 'Cancelada') {
                    $cita['css_color'] = 'bg-red-50 border-red-500 text-red-800';
                    $cita['css_text_light'] = 'text-red-600';
                } else {
                    // Pendiente: azul o naranja dependiendo
                    $cita['css_color'] = 'bg-brand-light border-brand text-teal-900';
                    $cita['css_text_light'] = 'text-brand';
                }
                
                $agenda[$dia_semana][] = $cita;
            }
        }
        
        return [
            'lunes_fecha' => $lunes,
            'dias' => $agenda
        ];
    }

    public function store($data) {
        // Obtenemos el ID del doctor activo en la sesión
        $doctor_id = $_SESSION['usuario_id'];
        
        // Validar que la hora de fin sea mayor a la de inicio
        if (strtotime($data['hora_fin']) <= strtotime($data['hora_inicio'])) {
            return "La hora de finalización debe ser mayor a la hora de inicio.";
        }

        // Verificar si hay cruce de horarios
        $cruce = $this->citaModel->verificarCruce($doctor_id, $data['fecha'], $data['hora_inicio'], $data['hora_fin']);
        if ($cruce) {
            return "El doctor ya tiene otra cita programada que se cruza con este horario.";
        }
        
        return $this->citaModel->create(
            $data['paciente_id'],
            $doctor_id,
            $data['fecha'],
            $data['hora_inicio'],
            $data['hora_fin'],
            $data['motivo']
        );
    }
}
?>
