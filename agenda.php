<?php
session_start();
if(!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'controllers/CitaController.php';
require_once 'controllers/PacienteController.php';

$citaCtrl = new CitaController();
$pacienteCtrl = new PacienteController();

$pre_paciente_id = isset($_GET['paciente_id']) ? intval($_GET['paciente_id']) : 0;

$mensaje = "";
$mantener_modal_abierto = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion'])) {
    if ($_POST['accion'] == 'nueva_cita') {
        $resultado = $citaCtrl->store($_POST);
        if($resultado === true) {
            $mensaje = "<div class='bg-emerald-100 text-emerald-700 p-3 rounded-xl mb-4 font-bold text-sm border border-emerald-200'>¡Cita guardada con éxito!</div>";
        } else {
            $mensaje = "<div class='bg-red-100 text-red-700 p-3 rounded-xl mb-4 font-bold text-sm border border-red-200'>Error al guardar: " . htmlspecialchars($resultado) . "</div>";
            $mantener_modal_abierto = true;
            if (isset($_POST['paciente_id'])) {
                $pre_paciente_id = intval($_POST['paciente_id']);
            }
        }
    } else if ($_POST['accion'] == 'cambiar_estado') {
        $resultado = $citaCtrl->cambiarEstado($_POST['cita_id'], $_POST['nuevo_estado']);
        if($resultado === true) {
            $mensaje = "<div class='bg-blue-100 text-blue-700 p-3 rounded-xl mb-4 font-bold text-sm border border-blue-200'>Estado actualizado con éxito.</div>";
        } else {
            $mensaje = "<div class='bg-red-100 text-red-700 p-3 rounded-xl mb-4 font-bold text-sm border border-red-200'>Error al actualizar: " . htmlspecialchars($resultado) . "</div>";
        }
    } else if ($_POST['accion'] == 'reprogramar_cita') {
        $resultado = $citaCtrl->reprogramar($_POST['cita_id'], $_POST['fecha'], $_POST['hora_inicio'], $_POST['hora_fin']);
        if($resultado === true) {
            $mensaje = "<div class='bg-blue-100 text-blue-700 p-3 rounded-xl mb-4 font-bold text-sm border border-blue-200'>Cita reprogramada con éxito.</div>";
        } else {
            $mensaje = "<div class='bg-red-100 text-red-700 p-3 rounded-xl mb-4 font-bold text-sm border border-red-200'>Error al reprogramar: " . htmlspecialchars($resultado) . "</div>";
        }
    } else if ($_POST['accion'] == 'eliminar_cita') {
        $resultado = $citaCtrl->delete($_POST['cita_id']);
        if($resultado === true) {
            $mensaje = "<div class='bg-gray-100 text-gray-700 p-3 rounded-xl mb-4 font-bold text-sm border border-gray-300'>Cita eliminada físicamente.</div>";
        } else {
            $mensaje = "<div class='bg-red-100 text-red-700 p-3 rounded-xl mb-4 font-bold text-sm border border-red-200'>Error al eliminar: " . htmlspecialchars($resultado) . "</div>";
        }
    }
}

// Obtener fecha de referencia desde GET o usar HOY
$fecha_ref = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');

$datos_agenda = $citaCtrl->getAgendaSemanal($fecha_ref);
$agenda = $datos_agenda['dias'];
$lunes_fecha = $datos_agenda['lunes_fecha'];
$pacientes = $pacienteCtrl->index();

// Generar array de días de la semana actual
$dias_semana = [];
$nombres_dias = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
for($i=0; $i<6; $i++) {
    $fecha_dia = date('Y-m-d', strtotime($lunes_fecha . " +$i days"));
    $dias_semana[$i+1] = [
        'nombre' => $nombres_dias[$i],
        'numero' => date('d', strtotime($fecha_dia)),
        'fecha' => $fecha_dia,
        'es_hoy' => ($fecha_dia == date('Y-m-d'))
    ];
}

// Mes en español
$meses = ['January'=>'Enero', 'February'=>'Febrero', 'March'=>'Marzo', 'April'=>'Abril', 'May'=>'Mayo', 'June'=>'Junio', 'July'=>'Julio', 'August'=>'Agosto', 'September'=>'Septiembre', 'October'=>'Octubre', 'November'=>'Noviembre', 'December'=>'Diciembre'];

$mes_inicio = $meses[date('F', strtotime($lunes_fecha))];
$anio_inicio = date('Y', strtotime($lunes_fecha));

$sabado_fecha = date('Y-m-d', strtotime($lunes_fecha . ' + 5 days'));
$mes_fin = $meses[date('F', strtotime($sabado_fecha))];
$anio_fin = date('Y', strtotime($sabado_fecha));

if ($mes_inicio == $mes_fin) {
    $titulo_mes = $mes_inicio . ' ' . $anio_inicio;
} else if ($anio_inicio == $anio_fin) {
    $titulo_mes = $mes_inicio . ' - ' . $mes_fin . ' ' . $anio_inicio;
} else {
    $titulo_mes = $mes_inicio . ' ' . $anio_inicio . ' - ' . $mes_fin . ' ' . $anio_fin;
}

// Botones de navegación
$semana_anterior = date('Y-m-d', strtotime($lunes_fecha . ' - 7 days'));
$semana_siguiente = date('Y-m-d', strtotime($lunes_fecha . ' + 7 days'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda Interactiva - MahuDent</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap');
        
        :root {
            --brand-primary: #0f766e; 
            --brand-secondary: #ccfbf1; 
            --brand-accent: #14b8a6; 
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #f8fafc; 
        }
        
        .bg-brand { background-color: var(--brand-primary); }
        .text-brand { color: var(--brand-primary); }
        .bg-brand-light { background-color: var(--brand-secondary); }
        
        /* CSS personalizado para la cuadrícula del calendario */
        .calendar-grid {
            display: grid;
            grid-template-columns: 60px repeat(6, 1fr); /* Hora + 6 días (Lun-Sáb) */
            min-width: 800px; /* Para asegurar el scroll en móviles */
        }
        
        /* Ocultar scrollbar para un look más limpio */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        
        <?php $page_title = 'Agenda'; include 'includes/header.php'; ?>

        <div class="flex-1 flex flex-col p-8 overflow-hidden">
            <?php echo $mensaje; ?>
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 shrink-0">
                <div class="flex items-center gap-4">
                    <h1 class="text-3xl font-black text-slate-800">Agenda</h1>
                    <div class="h-6 w-px bg-slate-300 hidden md:block"></div>
                    <div class="flex items-center gap-3">
                        <a href="?fecha=<?php echo $semana_anterior; ?>" class="p-2 border border-slate-200 rounded-lg hover:bg-slate-50 text-slate-600 transition">
                            <i data-lucide="chevron-left" class="w-5 h-5"></i>
                        </a>
                        <div class="relative group">
                            <button type="button" class="flex items-center gap-2 text-lg font-bold text-slate-700 hover:text-brand transition cursor-pointer" onclick="document.getElementById('datePickerAgenda').showPicker()">
                                <?php echo $titulo_mes; ?> <i data-lucide="calendar" class="w-4 h-4 text-slate-400"></i>
                            </button>
                            <input type="date" id="datePickerAgenda" class="absolute opacity-0 pointer-events-none -top-10" value="<?php echo $fecha_ref; ?>" onchange="window.location.href='?fecha='+this.value">
                        </div>
                        <a href="?fecha=<?php echo $semana_siguiente; ?>" class="p-2 border border-slate-200 rounded-lg hover:bg-slate-50 text-slate-600 transition">
                            <i data-lucide="chevron-right" class="w-5 h-5"></i>
                        </a>
                        <a href="?fecha=<?php echo date('Y-m-d'); ?>" class="px-4 py-2 bg-slate-100 text-slate-600 font-bold rounded-lg hover:bg-slate-200 transition text-sm">
                            Hoy
                        </a>
                    </div>
                </div>

                <div class="flex items-center gap-4">

                    <button onclick="toggleModalCita()" class="bg-brand hover:bg-teal-800 text-white px-5 py-2.5 rounded-xl font-bold flex items-center gap-2 shadow-lg transition-all hover:scale-105 hover:shadow-teal-900/30">
                        <i data-lucide="plus" class="w-5 h-5"></i>
                        Nueva Cita
                    </button>
                </div>
            </div>

            <div id="agendaWrapper" class="flex-1 bg-white rounded-2xl shadow-sm border border-slate-200 flex flex-col overflow-x-auto overflow-y-hidden transition-all duration-300 origin-left">
                
                <div class="calendar-grid border-b border-slate-200 bg-slate-50 shrink-0">
                    <div class="p-3 border-r border-slate-200">
                        <span class="text-xs text-slate-400 font-bold">HORA</span>
                    </div>
                    <?php for($i=1; $i<=6; $i++): ?>
                    <div class="p-3 border-r border-slate-200 text-center <?php echo $dias_semana[$i]['es_hoy'] ? 'relative overflow-hidden' : ''; ?>">
                        <?php if($dias_semana[$i]['es_hoy']): ?>
                            <div class="absolute top-0 left-0 w-full h-1 bg-brand"></div>
                            <p class="text-xs font-bold text-brand uppercase mt-1"><?php echo $dias_semana[$i]['nombre']; ?></p>
                            <p class="text-xl font-black text-brand bg-brand-light w-10 h-10 mx-auto flex items-center justify-center rounded-full mt-1"><?php echo $dias_semana[$i]['numero']; ?></p>
                        <?php else: ?>
                            <p class="text-xs font-bold text-slate-500 uppercase"><?php echo $dias_semana[$i]['nombre']; ?></p>
                            <p class="text-xl font-black text-slate-700"><?php echo $dias_semana[$i]['numero']; ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endfor; ?>
                </div>
                <div class="flex-1 overflow-y-auto no-scrollbar relative">
                    
                    <?php
                        $current_hour = (int)date('H');
                        $current_minute = (int)date('i');
                        // El calendario empieza a las 08:00 (480 minutos)
                        $minutos_desde_8am = (($current_hour - 8) * 60) + $current_minute;
                        $linea_roja_top = max(0, $minutos_desde_8am); 
                        $hora_actual_formato = date('H:i');
                    ?>
                    <div class="absolute w-full flex items-center z-20 pointer-events-none" style="top: <?php echo $linea_roja_top; ?>px;">
                        <div class="w-14 text-right pr-2">
                            <span class="text-[10px] font-bold text-red-500 bg-white/90 px-1 rounded shadow-sm"><?php echo $hora_actual_formato; ?></span>
                        </div>
                        <div class="flex-1 border-t-2 border-red-500 border-dashed relative">
                            <div class="absolute -left-1 -top-1.5 w-3 h-3 bg-red-500 rounded-full shadow-sm"></div>
                        </div>
                    </div>

                    <div class="calendar-grid">
                        
                        <div class="border-r border-slate-200 flex flex-col bg-white">
                            <div class="h-[60px] border-b border-slate-100 flex justify-end pr-2 pt-1"><span class="text-xs text-slate-400 font-medium">08:00</span></div>
                            <div class="h-[60px] border-b border-slate-100 flex justify-end pr-2 pt-1"><span class="text-xs text-slate-400 font-medium">09:00</span></div>
                            <div class="h-[60px] border-b border-slate-100 flex justify-end pr-2 pt-1"><span class="text-xs text-slate-400 font-medium">10:00</span></div>
                            <div class="h-[60px] border-b border-slate-100 flex justify-end pr-2 pt-1"><span class="text-xs text-slate-400 font-medium">11:00</span></div>
                            <div class="h-[60px] border-b border-slate-100 flex justify-end pr-2 pt-1"><span class="text-xs text-slate-400 font-medium">12:00</span></div>
                            <div class="h-[60px] border-b border-slate-100 flex justify-end pr-2 pt-1"><span class="text-xs text-slate-400 font-medium">13:00</span></div>
                            <div class="h-[60px] border-b border-slate-100 flex justify-end pr-2 pt-1"><span class="text-xs text-slate-400 font-medium">14:00</span></div>
                            <div class="h-[60px] border-b border-slate-100 flex justify-end pr-2 pt-1"><span class="text-xs text-slate-400 font-medium">15:00</span></div>
                        </div>

                        <div class="border-r border-slate-200 relative bg-white">
                            <!-- Líneas de horas -->
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            
                            <!-- Citas dinámicas -->
                            <?php foreach($agenda[1] as $cita): ?>
                            <div onclick='abrirDetalleCita(<?php echo htmlspecialchars(json_encode($cita), ENT_QUOTES, "UTF-8"); ?>)' class="absolute w-[95%] left-[2.5%] border-l-4 rounded p-1.5 shadow-sm hover:shadow-md transition cursor-pointer z-10 overflow-hidden hover:z-50 <?php echo $cita['css_color']; ?>" 
                                 style="top: <?php echo $cita['css_top']; ?>px; height: <?php echo $cita['css_height']; ?>px;">
                                <?php if ($cita['css_height'] <= 35): ?>
                                    <div class="flex items-center gap-1 h-full overflow-hidden">
                                        <p class="text-[9px] font-black uppercase shrink-0"><?php echo date("H:i", strtotime($cita['hora_inicio'])); ?></p>
                                        <p class="text-[10px] font-bold truncate leading-none mt-px"><?php echo htmlspecialchars($cita['paciente_nombre']); ?></p>
                                    </div>
                                <?php else: ?>
                                    <p class="text-[10px] font-bold uppercase mb-0.5 truncate"><?php echo date("H:i", strtotime($cita['hora_inicio'])) . ' - ' . date("H:i", strtotime($cita['hora_fin'])); ?></p>
                                    <p class="text-xs font-bold leading-tight truncate"><?php echo htmlspecialchars($cita['paciente_nombre']); ?></p>
                                    <?php if ($cita['css_height'] >= 55): ?>
                                    <p class="text-[10px] <?php echo $cita['css_text_light']; ?> truncate mt-0.5"><?php echo htmlspecialchars($cita['motivo']); ?></p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
<div class="border-r border-slate-200 relative bg-slate-50/50">
                            <!-- Líneas de horas -->
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            
                            <!-- Citas dinámicas -->
                            <?php foreach($agenda[2] as $cita): ?>
                            <div onclick='abrirDetalleCita(<?php echo htmlspecialchars(json_encode($cita), ENT_QUOTES, "UTF-8"); ?>)' class="absolute w-[95%] left-[2.5%] border-l-4 rounded p-1.5 shadow-sm hover:shadow-md transition cursor-pointer z-10 overflow-hidden hover:z-50 <?php echo $cita['css_color']; ?>" 
                                 style="top: <?php echo $cita['css_top']; ?>px; height: <?php echo $cita['css_height']; ?>px;">
                                <?php if ($cita['css_height'] <= 35): ?>
                                    <div class="flex items-center gap-1 h-full overflow-hidden">
                                        <p class="text-[9px] font-black uppercase shrink-0"><?php echo date("H:i", strtotime($cita['hora_inicio'])); ?></p>
                                        <p class="text-[10px] font-bold truncate leading-none mt-px"><?php echo htmlspecialchars($cita['paciente_nombre']); ?></p>
                                    </div>
                                <?php else: ?>
                                    <p class="text-[10px] font-bold uppercase mb-0.5 truncate"><?php echo date("H:i", strtotime($cita['hora_inicio'])) . ' - ' . date("H:i", strtotime($cita['hora_fin'])); ?></p>
                                    <p class="text-xs font-bold leading-tight truncate"><?php echo htmlspecialchars($cita['paciente_nombre']); ?></p>
                                    <?php if ($cita['css_height'] >= 55): ?>
                                    <p class="text-[10px] <?php echo $cita['css_text_light']; ?> truncate mt-0.5"><?php echo htmlspecialchars($cita['motivo']); ?></p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
<div class="border-r border-slate-200 relative bg-white">
                            <!-- Líneas de horas -->
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            
                            <!-- Citas dinámicas -->
                            <?php foreach($agenda[3] as $cita): ?>
                            <div onclick='abrirDetalleCita(<?php echo htmlspecialchars(json_encode($cita), ENT_QUOTES, "UTF-8"); ?>)' class="absolute w-[95%] left-[2.5%] border-l-4 rounded p-1.5 shadow-sm hover:shadow-md transition cursor-pointer z-10 overflow-hidden hover:z-50 <?php echo $cita['css_color']; ?>" 
                                 style="top: <?php echo $cita['css_top']; ?>px; height: <?php echo $cita['css_height']; ?>px;">
                                <?php if ($cita['css_height'] <= 35): ?>
                                    <div class="flex items-center gap-1 h-full overflow-hidden">
                                        <p class="text-[9px] font-black uppercase shrink-0"><?php echo date("H:i", strtotime($cita['hora_inicio'])); ?></p>
                                        <p class="text-[10px] font-bold truncate leading-none mt-px"><?php echo htmlspecialchars($cita['paciente_nombre']); ?></p>
                                    </div>
                                <?php else: ?>
                                    <p class="text-[10px] font-bold uppercase mb-0.5 truncate"><?php echo date("H:i", strtotime($cita['hora_inicio'])) . ' - ' . date("H:i", strtotime($cita['hora_fin'])); ?></p>
                                    <p class="text-xs font-bold leading-tight truncate"><?php echo htmlspecialchars($cita['paciente_nombre']); ?></p>
                                    <?php if ($cita['css_height'] >= 55): ?>
                                    <p class="text-[10px] <?php echo $cita['css_text_light']; ?> truncate mt-0.5"><?php echo htmlspecialchars($cita['motivo']); ?></p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
<div class="border-r border-slate-200 relative bg-white">
                            <!-- Líneas de horas -->
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            
                            <!-- Citas dinámicas -->
                            <?php foreach($agenda[4] as $cita): ?>
                            <div onclick='abrirDetalleCita(<?php echo htmlspecialchars(json_encode($cita), ENT_QUOTES, "UTF-8"); ?>)' class="absolute w-[95%] left-[2.5%] border-l-4 rounded p-1.5 shadow-sm hover:shadow-md transition cursor-pointer z-10 overflow-hidden hover:z-50 <?php echo $cita['css_color']; ?>" 
                                 style="top: <?php echo $cita['css_top']; ?>px; height: <?php echo $cita['css_height']; ?>px;">
                                <?php if ($cita['css_height'] <= 35): ?>
                                    <div class="flex items-center gap-1 h-full overflow-hidden">
                                        <p class="text-[9px] font-black uppercase shrink-0"><?php echo date("H:i", strtotime($cita['hora_inicio'])); ?></p>
                                        <p class="text-[10px] font-bold truncate leading-none mt-px"><?php echo htmlspecialchars($cita['paciente_nombre']); ?></p>
                                    </div>
                                <?php else: ?>
                                    <p class="text-[10px] font-bold uppercase mb-0.5 truncate"><?php echo date("H:i", strtotime($cita['hora_inicio'])) . ' - ' . date("H:i", strtotime($cita['hora_fin'])); ?></p>
                                    <p class="text-xs font-bold leading-tight truncate"><?php echo htmlspecialchars($cita['paciente_nombre']); ?></p>
                                    <?php if ($cita['css_height'] >= 55): ?>
                                    <p class="text-[10px] <?php echo $cita['css_text_light']; ?> truncate mt-0.5"><?php echo htmlspecialchars($cita['motivo']); ?></p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
<div class="border-r border-slate-200 relative bg-white">
                            <!-- Líneas de horas -->
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            
                            <!-- Citas dinámicas -->
                            <?php foreach($agenda[5] as $cita): ?>
                            <div onclick='abrirDetalleCita(<?php echo htmlspecialchars(json_encode($cita), ENT_QUOTES, "UTF-8"); ?>)' class="absolute w-[95%] left-[2.5%] border-l-4 rounded p-1.5 shadow-sm hover:shadow-md transition cursor-pointer z-10 overflow-hidden hover:z-50 <?php echo $cita['css_color']; ?>" 
                                 style="top: <?php echo $cita['css_top']; ?>px; height: <?php echo $cita['css_height']; ?>px;">
                                <?php if ($cita['css_height'] <= 35): ?>
                                    <div class="flex items-center gap-1 h-full overflow-hidden">
                                        <p class="text-[9px] font-black uppercase shrink-0"><?php echo date("H:i", strtotime($cita['hora_inicio'])); ?></p>
                                        <p class="text-[10px] font-bold truncate leading-none mt-px"><?php echo htmlspecialchars($cita['paciente_nombre']); ?></p>
                                    </div>
                                <?php else: ?>
                                    <p class="text-[10px] font-bold uppercase mb-0.5 truncate"><?php echo date("H:i", strtotime($cita['hora_inicio'])) . ' - ' . date("H:i", strtotime($cita['hora_fin'])); ?></p>
                                    <p class="text-xs font-bold leading-tight truncate"><?php echo htmlspecialchars($cita['paciente_nombre']); ?></p>
                                    <?php if ($cita['css_height'] >= 55): ?>
                                    <p class="text-[10px] <?php echo $cita['css_text_light']; ?> truncate mt-0.5"><?php echo htmlspecialchars($cita['motivo']); ?></p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
<div class="border-r border-slate-200 relative bg-slate-50">
                            <!-- Líneas de horas -->
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            
                            <!-- Citas dinámicas -->
                            <?php foreach($agenda[6] as $cita): ?>
                            <div onclick='abrirDetalleCita(<?php echo htmlspecialchars(json_encode($cita), ENT_QUOTES, "UTF-8"); ?>)' class="absolute w-[95%] left-[2.5%] border-l-4 rounded p-1.5 shadow-sm hover:shadow-md transition cursor-pointer z-10 overflow-hidden hover:z-50 <?php echo $cita['css_color']; ?>" 
                                 style="top: <?php echo $cita['css_top']; ?>px; height: <?php echo $cita['css_height']; ?>px;">
                                <?php if ($cita['css_height'] <= 35): ?>
                                    <div class="flex items-center gap-1 h-full overflow-hidden">
                                        <p class="text-[9px] font-black uppercase shrink-0"><?php echo date("H:i", strtotime($cita['hora_inicio'])); ?></p>
                                        <p class="text-[10px] font-bold truncate leading-none mt-px"><?php echo htmlspecialchars($cita['paciente_nombre']); ?></p>
                                    </div>
                                <?php else: ?>
                                    <p class="text-[10px] font-bold uppercase mb-0.5 truncate"><?php echo date("H:i", strtotime($cita['hora_inicio'])) . ' - ' . date("H:i", strtotime($cita['hora_fin'])); ?></p>
                                    <p class="text-xs font-bold leading-tight truncate"><?php echo htmlspecialchars($cita['paciente_nombre']); ?></p>
                                    <?php if ($cita['css_height'] >= 55): ?>
                                    <p class="text-[10px] <?php echo $cita['css_text_light']; ?> truncate mt-0.5"><?php echo htmlspecialchars($cita['motivo']); ?></p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>


                    </div>
                </div>
            </div>

        </div>
    
    <!-- Panel Lateral Nueva Cita -->
    <div id="modal-nueva-cita" class="fixed inset-y-0 right-0 w-full max-w-md bg-white shadow-[-10px_0_30px_rgba(0,0,0,0.1)] z-50 hidden flex-col border-l border-slate-200 transition-transform transform translate-x-full duration-300">
        <div class="flex-1 flex flex-col h-full overflow-hidden">
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center shrink-0">
                <h2 class="text-xl font-black text-slate-800 flex items-center gap-2">
                    <i data-lucide="calendar-plus" class="text-brand w-5 h-5"></i> Agendar Cita
                </h2>
                <button onclick="toggleModalCita()" class="text-slate-400 hover:text-red-500 p-2 rounded-xl transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="p-6 overflow-y-auto">
                <form method="POST" action="" class="space-y-4">
                    <input type="hidden" name="accion" value="nueva_cita">
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Paciente</label>
                        <select name="paciente_id" id="pacienteSelect" required class="w-full rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30 bg-white">
                            <option value="">Seleccione un paciente...</option>
                            <?php if($pacientes && $pacientes->num_rows > 0): ?>
                                <?php while($p = $pacientes->fetch_assoc()): ?>
                                    <option value="<?php echo $p['id']; ?>" <?php echo ($p['id'] == $pre_paciente_id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['nombre'] . ' - ' . $p['dni']); ?></option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Fecha</label>
                        <input type="date" name="fecha" min="<?php echo date('Y-m-d'); ?>" value="<?php echo isset($_POST['fecha']) ? htmlspecialchars($_POST['fecha']) : ''; ?>" required class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Hora Inicio</label>
                            <input type="time" name="hora_inicio" value="<?php echo isset($_POST['hora_inicio']) ? htmlspecialchars($_POST['hora_inicio']) : ''; ?>" required class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Hora Fin</label>
                            <input type="time" name="hora_fin" value="<?php echo isset($_POST['hora_fin']) ? htmlspecialchars($_POST['hora_fin']) : ''; ?>" required class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Motivo / Tratamiento</label>
                        <input type="text" name="motivo" value="<?php echo isset($_POST['motivo']) ? htmlspecialchars($_POST['motivo']) : ''; ?>" placeholder="Ej. Profilaxis" required class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                    </div>

                    <div class="pt-4 flex gap-3">
                        <button type="button" onclick="toggleModalCita()" class="flex-1 px-4 py-3 border border-slate-200 text-slate-600 rounded-xl font-bold hover:bg-slate-50 transition">Cancelar</button>
                        <button type="submit" class="flex-1 px-4 py-3 bg-brand hover:bg-teal-800 text-white rounded-xl font-bold shadow-lg transition">Guardar Cita</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    </main>

    <script>
        // Inicializar iconos al final

        function toggleModalCita() {
            const modal = document.getElementById('modal-nueva-cita');
            const wrapper = document.getElementById('agendaWrapper');
            
            if (modal.classList.contains('hidden')) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                wrapper.style.marginRight = '448px'; // max-w-md width
                setTimeout(() => {
                    modal.classList.remove('translate-x-full');
                    modal.classList.add('translate-x-0');
                }, 10);
            } else {
                modal.classList.remove('translate-x-0');
                modal.classList.add('translate-x-full');
                wrapper.style.marginRight = '0';
                setTimeout(() => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }, 300);
            }
        }
    </script>

    <!-- Modal Detalle/Edición de Cita -->
    <div id="modalDetalleCita" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-50 transition-opacity opacity-0">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden transform scale-95 transition-transform duration-300">
            <div class="bg-slate-50 border-b border-slate-100 p-6 flex justify-between items-center relative overflow-hidden">
                <div class="absolute -right-10 -top-10 w-32 h-32 bg-brand/5 rounded-full blur-2xl"></div>
                
                <div class="relative z-10">
                    <div class="flex items-center gap-2 mb-1">
                        <span id="detalle_estado_badge" class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider"></span>
                    </div>
                    <h3 class="text-xl font-black text-slate-800" id="detalle_paciente_nombre">Nombre del Paciente</h3>
                    <p class="text-sm font-medium text-slate-500 flex items-center gap-1 mt-1">
                        <i data-lucide="clock" class="w-4 h-4"></i> <span id="detalle_horario">00:00 - 00:00</span>
                        <span class="mx-2 text-slate-300">|</span>
                        <i data-lucide="phone" class="w-4 h-4"></i> <span id="detalle_telefono">---</span>
                    </p>
                </div>
                
                <button onclick="cerrarDetalleCita()" class="text-slate-700 hover:text-white bg-slate-100 hover:bg-red-500 p-2 rounded-full shadow-sm border border-slate-200 transition-all hover:scale-110 relative z-10">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <div class="p-6 flex flex-col gap-5">
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Motivo de Consulta</p>
                    <p class="text-slate-700 font-medium" id="detalle_motivo"></p>                <div class="h-px bg-slate-100 w-full my-1"></div>

                <div id="acciones_cita_container" class="flex flex-col gap-4">
                    <!-- Botón Principal Clínico -->
                    <a id="btn_atender_paciente" href="#" class="w-full bg-brand hover:bg-teal-800 text-white font-black py-3 px-4 rounded-xl shadow-lg transition transform hover:-translate-y-0.5 hover:shadow-xl flex items-center justify-center gap-3 uppercase tracking-wider text-sm">
                        <i data-lucide="stethoscope" class="w-5 h-5"></i> Atender Paciente
                    </a>
                    
                    <div class="h-px bg-slate-100 w-full my-1"></div>

                    <!-- Form Cambiar Estado -->
                    <form method="POST" action="" class="flex flex-col gap-2">
                        <input type="hidden" name="accion" value="cambiar_estado">
                        <input type="hidden" name="cita_id" id="detalle_cita_id">
                        
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Cambiar Estado Rápido</p>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="submit" name="nuevo_estado" value="Completada" class="flex items-center justify-center gap-2 py-2 px-4 rounded-xl border-2 border-emerald-200 text-emerald-700 font-bold hover:bg-emerald-50 transition text-sm">
                                <i data-lucide="check-circle-2" class="w-4 h-4"></i> Completada
                            </button>
                            <button type="submit" name="nuevo_estado" value="Cancelada" class="flex items-center justify-center gap-2 py-2 px-4 rounded-xl border-2 border-red-200 text-red-600 font-bold hover:bg-red-50 transition text-sm">
                                <i data-lucide="x-circle" class="w-4 h-4"></i> Cancelada
                            </button>
                        </div>
                    </form>

                    <!-- Form Reprogramar -->
                    <form method="POST" action="" class="flex flex-col gap-2">
                        <input type="hidden" name="accion" value="reprogramar_cita">
                        <input type="hidden" name="cita_id" id="reprogramar_cita_id">
                        
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-2">Reprogramar</p>
                        <div class="grid grid-cols-3 gap-2">
                            <input type="date" name="fecha" id="rep_fecha" required class="col-span-1 px-3 py-2 rounded-lg border border-slate-200 text-sm">
                            <input type="time" name="hora_inicio" id="rep_inicio" required class="col-span-1 px-3 py-2 rounded-lg border border-slate-200 text-sm">
                            <input type="time" name="hora_fin" id="rep_fin" required class="col-span-1 px-3 py-2 rounded-lg border border-slate-200 text-sm">
                        </div>
                        <button type="submit" class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-sm transition">Actualizar Fecha/Hora</button>
                    </form>
                    
                    <!-- Form Eliminar -->
                    <form method="POST" action="" class="mt-2" onsubmit="return confirm('¿Estás seguro de ELIMINAR esta cita físicamente? Esto no se puede deshacer.');">
                        <input type="hidden" name="accion" value="eliminar_cita">
                        <input type="hidden" name="cita_id" id="eliminar_cita_id">
                        <button type="submit" class="w-full py-2 flex items-center justify-center gap-2 text-red-500 hover:text-red-700 font-bold text-xs uppercase tracking-wider transition">
                            <i data-lucide="trash-2" class="w-3 h-3"></i> Eliminar cita (Error)
                        </button>
                    </form>
                </div>
                
                <div id="cita_bloqueada_msg" class="hidden bg-emerald-50 text-emerald-700 p-4 rounded-xl border border-emerald-200 flex items-center gap-3">
                    <i data-lucide="lock" class="w-5 h-5 shrink-0"></i>
                    <p class="text-xs font-medium">Esta cita está <strong>Completada</strong> y ha pasado a formar parte del historial clínico. No se puede modificar ni eliminar.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function abrirDetalleCita(cita) {
            const modal = document.getElementById('modalDetalleCita');
            const inner = modal.querySelector('div');
            
            // Llenar IDs
            document.getElementById('detalle_cita_id').value = cita.id;
            document.getElementById('reprogramar_cita_id').value = cita.id;
            document.getElementById('eliminar_cita_id').value = cita.id;
            
            // Link de Atención Clínica
            document.getElementById('btn_atender_paciente').href = `paciente_detalle.php?id=${cita.paciente_id}&cita_id=${cita.id}`;
            
            // Llenar inputs de reprogramar
            document.getElementById('rep_fecha').value = cita.fecha;
            document.getElementById('rep_inicio').value = cita.hora_inicio.substring(0,5);
            document.getElementById('rep_fin').value = cita.hora_fin.substring(0,5);
            
            // Llenar UI visual
            document.getElementById('detalle_paciente_nombre').innerText = cita.paciente_nombre;
            document.getElementById('detalle_horario').innerText = cita.hora_inicio.substring(0,5) + ' - ' + cita.hora_fin.substring(0,5);
            document.getElementById('detalle_telefono').innerText = cita.paciente_telefono || 'Sin Teléfono';
            document.getElementById('detalle_motivo').innerText = cita.motivo || 'Sin motivo especificado';
            
            const badge = document.getElementById('detalle_estado_badge');
            // Usar estado_visual si existe (creado en PHP para 'Atrasada')
            let estadoMostrar = cita.estado_visual ? cita.estado_visual : cita.estado;
            badge.innerText = estadoMostrar;
            
            // Colores del badge
            badge.className = "px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider ";
            if(cita.estado === 'Completada') badge.className += "bg-emerald-100 text-emerald-700";
            else if(cita.estado === 'Cancelada') badge.className += "bg-red-100 text-red-700";
            else if(estadoMostrar === 'Atrasada') badge.className += "bg-orange-100 text-orange-700";
            else badge.className += "bg-blue-100 text-blue-700";

            // Lógica de Bloqueo Clínico
            const accionesContainer = document.getElementById('acciones_cita_container');
            const bloqueadaMsg = document.getElementById('cita_bloqueada_msg');
            
            if (cita.estado === 'Completada') {
                accionesContainer.classList.add('hidden');
                bloqueadaMsg.classList.remove('hidden');
            } else {
                accionesContainer.classList.remove('hidden');
                bloqueadaMsg.classList.add('hidden');
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                inner.classList.remove('scale-95');
            }, 10);
        }

        function cerrarDetalleCita() {
            const modal = document.getElementById('modalDetalleCita');
            const inner = modal.querySelector('div');
            
            modal.classList.add('opacity-0');
            inner.classList.add('scale-95');
            
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }, 300);
        }
        
        // Inicializar TODOS los iconos una vez que todo el DOM está cargado
        lucide.createIcons();

        document.addEventListener('DOMContentLoaded', () => {
            // Inicializar buscador de pacientes
            new TomSelect('#pacienteSelect', {
                create: false,
                sortField: {
                    field: "text",
                    direction: "asc"
                },
                placeholder: 'Buscar paciente por nombre o DNI...'
            });
        });

        <?php if ($pre_paciente_id > 0 || $mantener_modal_abierto): ?>
        // Abrir automáticamente el modal si se seleccionó desde perfil de paciente o hubo un error
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(toggleModalCita, 100);
        });
        <?php endif; ?>
    </script>

</body>
</html>
