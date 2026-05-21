<?php
require_once 'config/conexion.php';
require_once 'models/Cita.php';

class CitaController {
    private $citaModel;
    
    public function __construct() {
        global $conn;
        $this->citaModel = new Cita($conn);
    }
    
    // Obtiene las citas de la semana (Lunes a Domingo) basada en una fecha
    public function getAgendaSemanal($fecha_referencia = null) {
        if(!$fecha_referencia) {
            $fecha_referencia = date('Y-m-d');
        }
        
        $dia_semana_ref = date('N', strtotime($fecha_referencia)); // 1 (Lunes) a 7 (Domingo)
        
        // Calcular fecha del Lunes de esta semana
        $dias_para_lunes = $dia_semana_ref - 1;
        $lunes = date('Y-m-d', strtotime("$fecha_referencia -$dias_para_lunes days"));
        
        // Calcular fecha del Domingo de esta semana
        $domingo = date('Y-m-d', strtotime($lunes . ' + 6 days'));
        
        $citas_planas = $this->citaModel->getCitasSemana($lunes, $domingo);
        
        // Agrupar las citas por día (1=Lunes ... 7=Domingo)
        $agenda = [
            1 => [], 2 => [], 3 => [], 4 => [], 5 => [], 6 => [], 7 => []
        ];
        
        foreach($citas_planas as $cita) {
            $dia_semana = date('N', strtotime($cita['fecha']));
            if($dia_semana >= 1 && $dia_semana <= 7) {
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
                $fecha_hora_cita = strtotime($cita['fecha'] . ' ' . $cita['hora_inicio']);
                $es_pasada = ($fecha_hora_cita < time());

                if($cita['estado'] == 'Completada') {
                    $cita['css_color'] = 'bg-emerald-50 border-emerald-500 text-emerald-800';
                    $cita['css_text_light'] = 'text-emerald-600';
                } elseif($cita['estado'] == 'Cancelada') {
                    $cita['css_color'] = 'bg-red-50/70 border-red-300 text-red-500 opacity-60 z-0 hover:z-50';
                    $cita['css_text_light'] = 'text-red-400';
                } elseif($cita['estado'] == 'En Curso') {
                    $cita['css_color'] = 'bg-indigo-50 border-indigo-500 text-indigo-800 animate-pulse';
                    $cita['css_text_light'] = 'text-indigo-600';
                    $cita['motivo'] = '[EN SALA] ' . $cita['motivo'];
                } else {
                    // Pendiente: azul o naranja dependiendo si ya pasó la hora
                    if ($es_pasada) {
                        $cita['css_color'] = 'bg-orange-50 border-orange-500 text-orange-800';
                        $cita['css_text_light'] = 'text-orange-600';
                        $cita['motivo'] = '[ATRASADA] ' . $cita['motivo'];
                    } else {
                        // Pendiente standard uses a clean Sky blue instead of brand teal
                        $cita['css_color'] = 'bg-sky-50 border-sky-400 text-sky-800';
                        $cita['css_text_light'] = 'text-sky-600';
                    }
                }
                
                $agenda[$dia_semana][] = $cita;
            }
        }
        
        return [
            'lunes_fecha' => $lunes,
            'domingo_fecha' => $domingo,
            'dias' => $agenda
        ];
    }

    public function store($data) {
        // Obtenemos el ID del doctor activo en la sesión
        $doctor_id = $_SESSION['usuario_id'];
        
        // Validar que la cita no sea en el pasado
        $fecha_hora_cita = strtotime($data['fecha'] . ' ' . $data['hora_inicio']);
        if ($fecha_hora_cita < time()) {
            return "No se pueden programar citas en el pasado.";
        }

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

    public function cambiarEstado($id, $estado) {
        // Podríamos añadir validaciones aquí
        return $this->citaModel->updateEstado($id, $estado);
    }

    public function reprogramar($id, $fecha, $hora_inicio, $hora_fin) {
        // Validar que la nueva fecha no sea un día anterior al actual
        if(strtotime($fecha) < strtotime(date('Y-m-d'))) {
            return "No se puede reprogramar a una fecha pasada.";
        }
        
        $duracion = strtotime($hora_fin) - strtotime($hora_inicio);
        if($duracion < 900) { // 15 minutos
            return "La cita debe durar al menos 15 minutos.";
        }

        // Obtener la cita actual para saber el doctor_id
        $citaActual = $this->citaModel->getById($id);
        if(!$citaActual) return "La cita no existe.";
        
        $doctor_id = $citaActual['doctor_id'];

        // Verificar si hay cruce de horarios ignorando la cita actual
        $cruce = $this->citaModel->verificarCruceExcluyendo($doctor_id, $fecha, $hora_inicio, $hora_fin, $id);
        if ($cruce) {
            return "El doctor ya tiene otra cita programada que se cruza con este horario.";
        }

        $actualizadoFechas = $this->citaModel->updateFechas($id, $fecha, $hora_inicio, $hora_fin);
        if ($actualizadoFechas === true) {
            // Restaurar estado a Pendiente en caso estuviera Cancelada
            $this->citaModel->updateEstado($id, 'Pendiente');
            return true;
        }
        return $actualizadoFechas;
    }

    public function delete($id) {
        return $this->citaModel->delete($id);
    }
}
?>
