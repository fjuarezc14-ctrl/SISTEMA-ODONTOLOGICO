<?php
require_once 'includes/auth_guard.php';

require_once 'controllers/CitaController.php';
require_once 'controllers/PacienteController.php';

$citaCtrl = new CitaController();
$pacienteCtrl = new PacienteController();

$pre_paciente_id = isset($_GET['paciente_id']) ? intval($_GET['paciente_id']) : 0;
$pre_fecha       = isset($_GET['pre_fecha'])    ? $_GET['pre_fecha']           : '';
$pre_hora        = isset($_GET['pre_hora'])      ? $_GET['pre_hora']            : '';

$mensaje_toast = '';
$toast_type    = 'success';
$mantener_modal_abierto = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion'])) {
    if ($_POST['accion'] == 'nueva_cita') {
        $resultado = $citaCtrl->store($_POST);
        if ($resultado === true) {
            $mensaje_toast = '¡Cita agendada con éxito!';
            $toast_type    = 'success';
        } else {
            $mensaje_toast = 'Error al guardar: ' . htmlspecialchars($resultado);
            $toast_type    = 'error';
            $mantener_modal_abierto = true;
            if (isset($_POST['paciente_id'])) $pre_paciente_id = intval($_POST['paciente_id']);
        }
    } elseif ($_POST['accion'] == 'cambiar_estado') {
        $resultado = $citaCtrl->cambiarEstado($_POST['cita_id'], $_POST['nuevo_estado']);
        if ($resultado === true) {
            $estado = htmlspecialchars($_POST['nuevo_estado']);
            $mensaje_toast = "Estado actualizado a «{$estado}».";
            $toast_type    = 'success';
        } else {
            $mensaje_toast = 'Error al actualizar: ' . htmlspecialchars($resultado);
            $toast_type    = 'error';
        }
    } elseif ($_POST['accion'] == 'reprogramar_cita') {
        $resultado = $citaCtrl->reprogramar($_POST['cita_id'], $_POST['fecha'], $_POST['hora_inicio'], $_POST['hora_fin']);
        if ($resultado === true) {
            $mensaje_toast = 'Cita reprogramada con éxito.';
            $toast_type    = 'info';
        } else {
            $mensaje_toast = 'Error al reprogramar: ' . htmlspecialchars($resultado);
            $toast_type    = 'error';
        }
    } elseif ($_POST['accion'] == 'eliminar_cita') {
        if ($_SESSION['usuario_rol'] === 'Admin') {
            $resultado = $citaCtrl->delete($_POST['cita_id']);
            if ($resultado === true) {
                $mensaje_toast = 'Cita eliminada del registro.';
                $toast_type    = 'warning';
            } else {
                $mensaje_toast = 'Error al eliminar: ' . htmlspecialchars($resultado);
                $toast_type    = 'error';
            }
        } else {
            $mensaje_toast = 'Acceso denegado: solo el Administrador puede eliminar citas.';
            $toast_type    = 'error';
        }
    }
}

// Obtener fecha de referencia y vista desde GET
$fecha_ref  = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
$vista      = isset($_GET['vista']) ? $_GET['vista'] : 'semana'; // 'semana' | 'dia'

$datos_agenda  = $citaCtrl->getAgendaSemanal($fecha_ref);
$agenda        = $datos_agenda['dias'];
$lunes_fecha   = $datos_agenda['lunes_fecha'];
$pacientes     = $pacienteCtrl->index();

// Generar array de días de la semana actual
$dias_semana = [];
$nombres_dias = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
for ($i = 0; $i < 7; $i++) {
    $fecha_dia = date('Y-m-d', strtotime($lunes_fecha . " +$i days"));
    $dias_semana[$i + 1] = [
        'nombre' => $nombres_dias[$i],
        'numero' => date('d', strtotime($fecha_dia)),
        'fecha'  => $fecha_dia,
        'es_hoy' => ($fecha_dia == date('Y-m-d'))
    ];
}

// Índice del día actual en la semana (para vista Día)
$dia_actual_idx = 1; // default Lunes
if ($vista === 'dia') {
    // Encontrar qué columna corresponde a fecha_ref
    foreach ($dias_semana as $idx => $dia) {
        if ($dia['fecha'] === $fecha_ref) {
            $dia_actual_idx = $idx;
            break;
        }
    }
    // Si fecha_ref no está en la semana actual, usamos Lunes
}
$dia_vista = $dias_semana[$dia_actual_idx];

// Mes en español
$meses = [
    'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo',
    'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio',
    'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre',
    'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
];
$meses_cortos = [
    'Enero' => 'Ene', 'Febrero' => 'Feb', 'Marzo' => 'Mar',
    'Abril' => 'Abr', 'Mayo' => 'May', 'Junio' => 'Jun',
    'Julio' => 'Jul', 'Agosto' => 'Ago', 'Septiembre' => 'Sep',
    'Octubre' => 'Oct', 'Noviembre' => 'Nov', 'Diciembre' => 'Dic'
];

$mes_inicio  = $meses[date('F', strtotime($lunes_fecha))];
$anio_inicio = date('Y', strtotime($lunes_fecha));
$domingo_fecha = date('Y-m-d', strtotime($lunes_fecha . ' + 6 days'));
$mes_fin     = $meses[date('F', strtotime($domingo_fecha))];
$anio_fin    = date('Y', strtotime($domingo_fecha));

if ($mes_inicio == $mes_fin) {
    $titulo_mes = $mes_inicio . ' ' . $anio_inicio;
} elseif ($anio_inicio == $anio_fin) {
    $titulo_mes = $mes_inicio . ' – ' . $mes_fin . ' ' . $anio_inicio;
} else {
    $titulo_mes = $mes_inicio . ' ' . $anio_inicio . ' – ' . $mes_fin . ' ' . $anio_fin;
}

// En vista "Día" mostramos solo el día seleccionado
if ($vista === 'dia') {
    $titulo_mes = $dias_semana[$dia_actual_idx]['nombre'] . ' ' .
                  $dias_semana[$dia_actual_idx]['numero'] . ' de ' .
                  $meses[date('F', strtotime($dias_semana[$dia_actual_idx]['fecha']))] . ' ' .
                  date('Y', strtotime($dias_semana[$dia_actual_idx]['fecha']));
}

// Botones de navegación — en vista día avanza 1 día; en semana 7 días
if ($vista === 'dia') {
    $nav_anterior  = date('Y-m-d', strtotime($fecha_ref . ' -1 day'));
    $nav_siguiente = date('Y-m-d', strtotime($fecha_ref . ' +1 day'));
} else {
    $nav_anterior  = date('Y-m-d', strtotime($lunes_fecha . ' -7 days'));
    $nav_siguiente = date('Y-m-d', strtotime($lunes_fecha . ' +7 days'));
}

// Generar franjas horarias de 15 min para selectores
function generarOpciones15min($valorSeleccionado = '') {
    $opciones = '';
    for ($h = 8; $h <= 20; $h++) {
        for ($m = 0; $m < 60; $m += 15) {
            if ($h === 20 && $m > 0) break;
            $time = sprintf('%02d:%02d', $h, $m);
            $selected = ($time === $valorSeleccionado) ? ' selected' : '';
            $opciones .= "<option value=\"{$time}\"{$selected}>{$time}</option>";
        }
    }
    return $opciones;
}

$val_inicio = isset($_POST['hora_inicio']) ? htmlspecialchars($_POST['hora_inicio']) : ($pre_hora ?: '');
$val_fin    = isset($_POST['hora_fin'])    ? htmlspecialchars($_POST['hora_fin'])    : '';
// Si hay hora de inicio sugerida, fin = +1 hora
if ($pre_hora && !$val_fin) {
    $fin_ts = strtotime($pre_hora) + 3600;
    $val_fin = ($fin_ts <= strtotime('20:00')) ? date('H:i', $fin_ts) : '20:00';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda – MahuDent</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        teal: {
                            50: '#f5f3fa', 100: '#ede8f7', 200: '#d7cde6',
                            300: '#c5b5e4', 400: '#ab92d6', 500: '#937ec2',
                            600: '#7e64ab', 700: '#3a596a', 800: '#2f4958', 900: '#1b2d38',
                        }
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <!-- Toast & Confirm helper -->
    <script src="assets/js/toast_alerts.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap');
        :root { --brand-primary: #3a596a; --brand-secondary: #ede8f7; --brand-accent: #937ec2; }
        body { font-family: 'Montserrat', sans-serif; background-color: #f8fafc; }
        .bg-brand   { background-color: var(--brand-primary); }
        .text-brand { color: var(--brand-primary); }
        .bg-brand-light { background-color: var(--brand-secondary); }

        /* Calendar grid */
        .calendar-grid         { display: grid; grid-template-columns: 60px repeat(7, 1fr); min-width: 780px; }
        .calendar-grid-day     { display: grid; grid-template-columns: 60px 1fr; }

        /* Hover cells */
        .hora-cell {
            position: relative;
            height: 60px;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.15s;
            cursor: pointer;
        }
        .hora-cell:hover { background: rgba(58,89,106,0.05); }
        .hora-cell:hover .cell-add-hint { opacity: 1; }
        .cell-add-hint {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.15s;
            pointer-events: none;
        }
        .cell-add-hint span {
            background: var(--brand-primary);
            color: white;
            font-size: 10px;
            font-weight: 700;
            padding: 3px 9px;
            border-radius: 20px;
            white-space: nowrap;
            box-shadow: 0 2px 8px rgba(58,89,106,0.25);
        }

        /* No scrollbar */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* Month popover */
        #monthPopover {
            position: absolute;
            top: calc(100% + 10px);
            left: 50%;
            transform: translateX(-50%) translateY(-6px);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease, transform 0.2s ease;
            z-index: 9999;
        }
        #monthPopover.open {
            opacity: 1;
            pointer-events: all;
            transform: translateX(-50%) translateY(0);
        }

        /* Select dropdown */
        .time-select {
            width: 100%;
            padding: 9px 12px;
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            font-family: 'Montserrat', sans-serif;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            background: white;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%233a596a' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            cursor: pointer;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .time-select:focus {
            outline: none;
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 3px rgba(58,89,106,0.12);
        }

        /* View toggle pills */
        .view-pill {
            padding: 6px 16px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            background: transparent;
            color: #64748b;
        }
        .view-pill.active {
            background: var(--brand-primary);
            color: white;
            box-shadow: 0 2px 8px rgba(58,89,106,0.3);
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

<?php include 'includes/sidebar.php'; ?>

<main class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php $page_title = 'Agenda'; include 'includes/header.php'; ?>

    <div class="flex-1 flex flex-col p-4 md:p-6 overflow-hidden">

        <!-- ── TOP BAR ─────────────────────────────────────────────── -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-3 shrink-0">

            <!-- Izquierda: Título + Navegación -->
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="text-2xl font-black text-slate-800">Agenda</h1>
                <div class="h-5 w-px bg-slate-300 hidden md:block"></div>

                <!-- Navegación de semana/día -->
                <div class="flex items-center gap-2">
                    <a href="?fecha=<?php echo $nav_anterior; ?>&vista=<?php echo $vista; ?>"
                       class="p-2 border border-slate-200 rounded-xl hover:bg-teal-100 text-slate-600 transition" title="<?php echo $vista==='dia' ? 'Día anterior' : 'Semana anterior'; ?>">
                        <i data-lucide="chevron-left" class="w-4 h-4"></i>
                    </a>

                    <!-- Botón del mes/período — Abre Popover -->
                    <div class="relative" id="monthPickerWrapper">
                        <button type="button" id="monthPickerBtn"
                                class="flex items-center gap-2 font-bold text-slate-700 hover:text-brand transition px-3 py-2 rounded-xl hover:bg-teal-50"
                                onclick="toggleMonthPopover()">
                            <i data-lucide="calendar" class="w-4 h-4 text-slate-400"></i>
                            <span class="text-base"><?php echo $titulo_mes; ?></span>
                            <i data-lucide="chevron-down" class="w-3 h-3 text-slate-400" id="monthArrow"></i>
                        </button>

                        <!-- Popover de Navegación de Mes/Año → Mini-Calendario -->
                        <div id="monthPopover"
                             class="bg-white rounded-2xl shadow-2xl border border-slate-100 p-4" style="width:280px;">

                            <!-- Navegación mes dentro del popover -->
                            <div class="flex items-center justify-between mb-3">
                                <button onclick="popoverNavMes(-1)" class="p-1.5 hover:bg-slate-100 rounded-xl transition">
                                    <i data-lucide="chevron-left" class="w-4 h-4 text-slate-500"></i>
                                </button>
                                <button onclick="toggleModoAnio()" id="popoverMesAnioLabel"
                                        class="font-black text-slate-800 text-sm hover:text-brand transition px-2"></button>
                                <button onclick="popoverNavMes(1)" class="p-1.5 hover:bg-slate-100 rounded-xl transition">
                                    <i data-lucide="chevron-right" class="w-4 h-4 text-slate-500"></i>
                                </button>
                            </div>

                            <!-- Grid de días de la semana (encabezados) -->
                            <div class="grid grid-cols-7 text-center mb-1.5" id="popoverDayHeaders">
                                <?php foreach (['Lu','Ma','Mi','Ju','Vi','Sá','Do'] as $dh): ?>
                                <div class="text-[9px] font-black text-slate-400 uppercase py-0.5"><?php echo $dh; ?></div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Grid de días (dinámico via JS) -->
                            <div class="grid grid-cols-7 gap-0.5" id="miniCalBody"></div>

                            <!-- Modo selección de año: grid de meses (oculto por defecto) -->
                            <div id="modoAnioGrid" class="hidden grid-cols-4 gap-1.5 mt-2"></div>

                            <div class="mt-3 pt-2 border-t border-slate-100">
                                <button onclick="irAHoy()" class="w-full py-1.5 text-xs font-bold text-brand hover:bg-teal-50 rounded-xl transition">
                                    Ir a Hoy
                                </button>
                            </div>
                        </div>
                    </div>

                    <a href="?fecha=<?php echo $nav_siguiente; ?>&vista=<?php echo $vista; ?>"
                       class="p-2 border border-slate-200 rounded-xl hover:bg-teal-100 text-slate-600 transition" title="<?php echo $vista==='dia' ? 'Día siguiente' : 'Semana siguiente'; ?>">
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </a>

                    <a href="?fecha=<?php echo date('Y-m-d'); ?>&vista=<?php echo $vista; ?>"
                       class="px-3 py-2 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-teal-200 transition text-xs">
                        Hoy
                    </a>
                </div>
            </div>

            <!-- Derecha: Toggle de Vista + Botón Nueva Cita -->
            <div class="flex items-center gap-3">
                <!-- Toggle Vista -->
                <?php
                    // Día "inteligente" para el toggle semana→día:
                    // Si hoy está en la semana visible → mostrar hoy. Si no → mostrar fecha_ref.
                    $hoy_str = date('Y-m-d');
                    $dia_toggle = ($hoy_str >= $lunes_fecha && $hoy_str <= $domingo_fecha)
                                  ? $hoy_str : $fecha_ref;
                ?>
                <div class="flex items-center gap-1 bg-slate-100 rounded-xl p-1">
                    <a href="?fecha=<?php echo $fecha_ref; ?>&amp;vista=semana"
                       class="view-pill <?php echo $vista==='semana' ? 'active' : ''; ?>">
                        <i data-lucide="calendar-days" class="w-3.5 h-3.5 inline mr-1"></i>Semana
                    </a>
                    <a href="?fecha=<?php echo $dia_toggle; ?>&amp;vista=dia"
                       class="view-pill <?php echo $vista==='dia' ? 'active' : ''; ?>">
                        <i data-lucide="calendar" class="w-3.5 h-3.5 inline mr-1"></i>Día
                    </a>
                </div>

                <!-- Nueva Cita -->
                <button onclick="abrirNuevaCita()" id="btnNuevaCita"
                        class="bg-brand hover:bg-teal-800 text-white px-4 py-2.5 rounded-xl font-bold flex items-center gap-2 shadow-lg transition-all hover:scale-105 text-sm">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Nueva Cita
                </button>
            </div>
        </div>

        <!-- ── CALENDARIO ──────────────────────────────────────────── -->
        <div id="agendaWrapper"
             class="flex-1 bg-white rounded-2xl shadow-sm border border-slate-200 flex flex-col overflow-x-auto overflow-y-hidden transition-all duration-300">

            <?php
            // En vista DÍA solo mostramos la columna del día seleccionado
            $col_class     = $vista === 'dia' ? 'calendar-grid-day' : 'calendar-grid';
            $dias_a_mostrar = $vista === 'dia' ? [$dia_actual_idx => $dia_vista] : $dias_semana;
            ?>

            <!-- Cabecera de días -->
            <div class="<?php echo $col_class; ?> border-b border-slate-200 bg-slate-50 shrink-0">
                <!-- Celda hora -->
                <div class="p-3 border-r border-slate-200">
                    <span class="text-[10px] text-slate-400 font-bold">HORA</span>
                </div>
                <?php foreach ($dias_a_mostrar as $i => $dia): ?>
                <div class="border-r border-slate-200 text-center <?php echo $dia['es_hoy'] ? 'relative overflow-hidden' : ($i==7 ? 'bg-amber-50/40' : ''); ?> <?php echo $vista==='semana' ? 'cursor-pointer group hover:bg-teal-50/60 transition' : ''; ?>"
                     <?php if ($vista==='semana'): ?>
                     onclick="window.location.href='?fecha=<?php echo $dia['fecha']; ?>&amp;vista=dia'"
                     title="Ver solo <?php echo $dia['nombre']; ?> <?php echo $dia['numero']; ?>"
                     <?php endif; ?>
                     style="padding: 8px 12px;">
                    <?php if ($dia['es_hoy']): ?>
                        <div class="absolute top-0 left-0 w-full h-1 bg-brand"></div>
                        <p class="text-xs font-bold text-brand uppercase mt-1"><?php echo $dia['nombre']; ?></p>
                        <p class="text-xl font-black text-brand bg-brand-light w-10 h-10 mx-auto flex items-center justify-center rounded-full mt-1"><?php echo $dia['numero']; ?></p>
                    <?php else: ?>
                        <p class="text-xs font-bold <?php echo $i==7 ? 'text-amber-600' : 'text-slate-500 group-hover:text-brand'; ?> uppercase transition"><?php echo $dia['nombre']; ?></p>
                        <p class="text-xl font-black <?php echo $i==7 ? 'text-amber-700' : 'text-slate-700 group-hover:text-brand'; ?> transition"><?php echo $dia['numero']; ?></p>
                    <?php endif; ?>
                    <?php if ($vista === 'semana'): ?>
                    <div class="mt-1 flex items-center justify-center gap-2">
                        <button onclick="event.stopPropagation(); abrirNuevaCitaDia('<?php echo $dia['fecha']; ?>')"
                                class="text-[9px] font-bold text-brand/60 hover:text-brand hover:bg-teal-100 px-2 py-0.5 rounded-full transition"
                                title="Nueva cita este día">
                            + Cita
                        </button>
                        <span class="text-[8px] text-slate-300 group-hover:text-teal-300 transition hidden sm:inline">
                            <i data-lucide="external-link" class="w-2.5 h-2.5 inline"></i>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Cuerpo: horas + celdas -->
            <div class="flex-1 overflow-y-auto no-scrollbar relative">

                <?php
                    $current_hour      = (int)date('H');
                    $current_minute    = (int)date('i');
                    $mostrar_linea     = ($current_hour >= 8 && $current_hour < 20);
                    $minutos_desde_8am = (($current_hour - 8) * 60) + $current_minute;
                    $linea_roja_top    = max(0, $minutos_desde_8am);
                    $hora_actual_fmt   = date('H:i');
                ?>
                <?php if ($mostrar_linea): ?>
                <div class="absolute w-full flex items-center z-20 pointer-events-none"
                     style="top: <?php echo $linea_roja_top; ?>px;">
                    <div class="w-14 text-right pr-2">
                        <span class="text-[10px] font-bold text-red-500 bg-white/90 px-1 rounded shadow-sm"><?php echo $hora_actual_fmt; ?></span>
                    </div>
                    <div class="flex-1 border-t-2 border-red-500 border-dashed relative">
                        <div class="absolute -left-1 -top-1.5 w-3 h-3 bg-red-500 rounded-full shadow-sm"></div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="<?php echo $col_class; ?>">

                    <!-- Columna de horas (etiquetas) -->
                    <div class="border-r border-slate-200 flex flex-col bg-white">
                        <?php for ($h = 8; $h <= 20; $h++): ?>
                        <div class="h-[60px] border-b border-slate-100 flex justify-end pr-2 pt-1">
                            <span class="text-xs text-slate-400 font-medium"><?php echo sprintf('%02d:00', $h); ?></span>
                        </div>
                        <?php endfor; ?>
                    </div>

                    <!-- Columnas de días con celdas clickeables -->
                    <?php foreach ($dias_a_mostrar as $col_idx => $dia):
                        $es_sab_dom = ($col_idx >= 6);
                        $bg_col     = $es_sab_dom ? 'bg-amber-50/30' : 'bg-white';
                    ?>
                    <div class="border-r border-slate-200 relative <?php echo $bg_col; ?>">

                        <!-- Celdas interactivas por hora -->
                        <?php for ($h = 8; $h <= 20; $h++):
                            $hora_str = sprintf('%02d:00', $h);
                        ?>
                        <div class="hora-cell"
                             onclick="abrirNuevaCitaDiaHora('<?php echo $dia['fecha']; ?>', '<?php echo $hora_str; ?>')"
                             title="Agendar a las <?php echo $hora_str; ?>">
                            <div class="cell-add-hint">
                                <span>+ <?php echo $hora_str; ?></span>
                            </div>
                        </div>
                        <?php endfor; ?>

                        <!-- Citas del día (absolutas sobre las celdas) -->
                        <?php foreach ($agenda[$col_idx] as $cita): ?>
                        <div onclick='event.stopPropagation(); abrirDetalleCita(<?php echo htmlspecialchars(json_encode($cita), ENT_QUOTES, "UTF-8"); ?>)'
                             class="absolute w-[92%] left-[4%] border-l-4 rounded-lg p-1.5 shadow-sm hover:shadow-md transition cursor-pointer z-10 overflow-hidden hover:z-50 <?php echo $cita['css_color']; ?>"
                             style="top: <?php echo $cita['css_top']; ?>px; height: <?php echo $cita['css_height']; ?>px;">
                            <?php if ($cita['css_height'] <= 35): ?>
                                <div class="flex items-center gap-1 h-full overflow-hidden">
                                    <p class="text-[9px] font-black uppercase shrink-0"><?php echo date("H:i", strtotime($cita['hora_inicio'])); ?></p>
                                    <p class="text-[10px] font-bold truncate leading-none mt-px"><?php echo htmlspecialchars($cita['paciente_nombre']); ?></p>
                                </div>
                            <?php else: ?>
                                <p class="text-[10px] font-bold uppercase mb-0.5 truncate"><?php echo date("H:i", strtotime($cita['hora_inicio'])) . ' – ' . date("H:i", strtotime($cita['hora_fin'])); ?></p>
                                <p class="text-xs font-bold leading-tight truncate"><?php echo htmlspecialchars($cita['paciente_nombre']); ?></p>
                                <?php if ($cita['css_height'] >= 55): ?>
                                <p class="text-[10px] <?php echo $cita['css_text_light']; ?> truncate mt-0.5"><?php echo htmlspecialchars($cita['motivo']); ?></p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>

                </div><!-- /.calendar-grid -->
            </div>
        </div><!-- /#agendaWrapper -->

    </div><!-- /.flex-1 -->
</main>

<!-- ═══════════════════════════════════════════════════════════
     PANEL LATERAL — Nueva Cita (slide-in desde la derecha)
═══════════════════════════════════════════════════════════════ -->
<div id="modal-nueva-cita"
     class="fixed inset-y-0 right-0 w-full max-w-sm bg-white shadow-[-12px_0_40px_rgba(0,0,0,0.12)] z-50 hidden flex-col border-l border-slate-200 transition-transform transform translate-x-full duration-300">
    <div class="flex-1 flex flex-col h-full overflow-hidden">
        <div class="bg-slate-50 px-5 py-4 border-b border-slate-200 flex justify-between items-center shrink-0">
            <h2 class="text-lg font-black text-slate-800 flex items-center gap-2">
                <i data-lucide="calendar-plus" class="text-brand w-5 h-5"></i>
                <span id="panelTituloNuevaCita">Agendar Cita</span>
            </h2>
            <button onclick="cerrarNuevaCita()" class="text-slate-400 hover:text-red-500 p-2 rounded-xl transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="p-5 overflow-y-auto flex-1">
            <form method="POST" action="" class="space-y-4" id="formNuevaCita">
                <input type="hidden" name="accion" value="nueva_cita">

                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1.5">Paciente</label>
                    <select name="paciente_id" id="pacienteSelect" required
                            class="w-full rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-200 focus:border-teal-500 bg-white text-sm">
                        <option value="">Buscar paciente por nombre o DNI...</option>
                        <?php if ($pacientes && $pacientes->num_rows > 0): ?>
                            <?php while ($p = $pacientes->fetch_assoc()): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo ($p['id'] == $pre_paciente_id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['nombre'] . ' – ' . $p['dni']); ?>
                            </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1.5">Fecha</label>
                    <input type="date" name="fecha" id="inputFechaCita"
                           min="<?php echo date('Y-m-d'); ?>"
                           value="<?php echo isset($_POST['fecha']) ? htmlspecialchars($_POST['fecha']) : ($pre_fecha ?: date('Y-m-d')); ?>"
                           required
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-200 focus:border-teal-500 text-sm">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1.5">Hora Inicio</label>
                        <select name="hora_inicio" id="selectHoraInicio" required class="time-select">
                            <option value="">-- Inicio --</option>
                            <?php echo generarOpciones15min($val_inicio); ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1.5">Hora Fin</label>
                        <select name="hora_fin" id="selectHoraFin" required class="time-select">
                            <option value="">-- Fin --</option>
                            <?php echo generarOpciones15min($val_fin); ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-1.5">Motivo / Tratamiento</label>
                    <input type="text" name="motivo"
                           value="<?php echo isset($_POST['motivo']) ? htmlspecialchars($_POST['motivo']) : ''; ?>"
                           placeholder="Ej. Profilaxis, Consulta..."
                           required
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-200 focus:border-teal-500 text-sm">
                </div>

                <div class="pt-2 flex gap-3">
                    <button type="button" onclick="cerrarNuevaCita()"
                            class="flex-1 px-4 py-3 border border-slate-200 text-slate-600 rounded-xl font-bold hover:bg-teal-100 transition text-sm">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-3 bg-brand hover:bg-teal-800 text-white rounded-xl font-bold shadow-lg transition text-sm flex items-center justify-center gap-2">
                        <i data-lucide="check" class="w-4 h-4"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     MODAL DETALLE / GESTIÓN DE CITA
═══════════════════════════════════════════════════════════════ -->
<div id="modalDetalleCita"
     class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-50 transition-opacity opacity-0">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden transform scale-95 transition-transform duration-300 mx-4">

        <!-- Cabecera del modal -->
        <div class="bg-slate-50 border-b border-slate-100 p-6 flex justify-between items-start relative overflow-hidden">
            <div class="absolute -right-10 -top-10 w-32 h-32 bg-brand/5 rounded-full blur-2xl"></div>
            <div class="relative z-10">
                <div class="flex items-center gap-2 mb-1">
                    <span id="detalle_estado_badge" class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider"></span>
                </div>
                <h3 class="text-xl font-black text-slate-800" id="detalle_paciente_nombre"></h3>
                <p class="text-sm font-medium text-slate-500 flex items-center gap-1 mt-1">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                    <span id="detalle_horario">00:00 – 00:00</span>
                    <span class="mx-2 text-slate-300">|</span>
                    <i data-lucide="phone" class="w-4 h-4"></i>
                    <span id="detalle_telefono" class="ml-1">---</span>
                    <button onclick="enviarRecordatorioWhatsApp()"
                            class="ml-2 bg-[#25D366] text-white hover:bg-[#128C7E] p-1 rounded-full shadow-sm transition hover:scale-110"
                            title="Enviar recordatorio por WhatsApp">
                        <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                    </button>
                </p>
            </div>
            <button onclick="cerrarDetalleCita()"
                    class="text-slate-700 hover:text-white bg-slate-100 hover:bg-red-500 p-2 rounded-full shadow-sm border border-slate-200 transition-all hover:scale-110 relative z-10">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="p-6 flex flex-col gap-5">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Motivo de Consulta</p>
                <p class="text-slate-700 font-medium" id="detalle_motivo"></p>
            </div>
            <div class="h-px bg-slate-100 w-full"></div>

            <div id="acciones_cita_container" class="flex flex-col gap-4">

                <!-- Ir a ficha/atender -->
                <?php if ($_SESSION['usuario_rol'] === 'Recepcionista'): ?>
                <a id="btn_atender_paciente" href="#"
                   class="w-full bg-slate-800 hover:bg-slate-900 text-white font-black py-3 px-4 rounded-xl shadow-lg transition transform hover:-translate-y-0.5 flex items-center justify-center gap-3 uppercase tracking-wider text-sm">
                    <i data-lucide="folder-open" class="w-5 h-5"></i> Ver Ficha del Paciente
                </a>
                <?php else: ?>
                <a id="btn_atender_paciente" href="#"
                   class="w-full bg-brand hover:bg-teal-800 text-white font-black py-3 px-4 rounded-xl shadow-lg transition transform hover:-translate-y-0.5 flex items-center justify-center gap-3 uppercase tracking-wider text-sm">
                    <i data-lucide="stethoscope" class="w-5 h-5"></i> Atender Paciente
                </a>
                <?php endif; ?>

                <div class="h-px bg-slate-100 w-full"></div>

                <!-- Cambiar estado rápido (sin confirm, con toast) -->
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Cambiar Estado Rápido</p>
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" onclick="cambiarEstadoCita('Completada')"
                                class="flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl border-2 border-emerald-200 text-emerald-700 font-bold hover:bg-emerald-50 transition text-sm">
                            <i data-lucide="check-circle-2" class="w-4 h-4"></i> Completada
                        </button>
                        <button type="button" onclick="cambiarEstadoCita('Cancelada')"
                                class="flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl border-2 border-red-200 text-red-600 font-bold hover:bg-red-50 transition text-sm">
                            <i data-lucide="x-circle" class="w-4 h-4"></i> Cancelada
                        </button>
                    </div>
                </div>

                <!-- Reprogramar -->
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Reprogramar</p>
                    <div class="grid grid-cols-3 gap-2">
                        <input type="date" id="rep_fecha"
                               class="col-span-1 px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
                        <select id="rep_inicio" class="time-select col-span-1">
                            <option value="">Inicio</option>
                            <?php echo generarOpciones15min(); ?>
                        </select>
                        <select id="rep_fin" class="time-select col-span-1">
                            <option value="">Fin</option>
                            <?php echo generarOpciones15min(); ?>
                        </select>
                    </div>
                    <button type="button" onclick="reprogramarCita()"
                            class="w-full mt-2 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-sm transition flex items-center justify-center gap-2">
                        <i data-lucide="calendar-clock" class="w-4 h-4"></i> Actualizar Fecha/Hora
                    </button>
                </div>

                <!-- Eliminar (Admin) — única acción con confirmación real -->
                <?php if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'Admin'): ?>
                <div class="mt-1">
                    <button type="button" onclick="eliminarCitaAdmin()"
                            class="w-full py-2.5 px-4 rounded-xl text-red-500 font-bold hover:bg-red-50 transition text-sm flex items-center justify-center gap-2">
                        <i data-lucide="trash-2" class="w-4 h-4"></i> Eliminar Cita (Error de Registro)
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <div id="cita_bloqueada_msg"
                 class="hidden bg-emerald-50 text-emerald-700 p-4 rounded-xl border border-emerald-200 flex items-center gap-3">
                <i data-lucide="lock" class="w-5 h-5 shrink-0"></i>
                <p class="text-xs font-medium">Esta cita está <strong>Completada</strong> y pertenece al historial clínico. No se puede modificar.</p>
            </div>
        </div>
    </div>
</div>

<!-- ─── FORMULARIOS OCULTOS (para POSTs) ─── -->
<form id="formCambiarEstado" method="POST" action="" style="display:none">
    <input type="hidden" name="accion" value="cambiar_estado">
    <input type="hidden" name="cita_id" id="frmEstado_cita_id">
    <input type="hidden" name="nuevo_estado" id="frmEstado_nuevo_estado">
</form>
<form id="formReprogramar" method="POST" action="" style="display:none">
    <input type="hidden" name="accion" value="reprogramar_cita">
    <input type="hidden" name="cita_id" id="frmRep_cita_id">
    <input type="hidden" name="fecha" id="frmRep_fecha">
    <input type="hidden" name="hora_inicio" id="frmRep_inicio">
    <input type="hidden" name="hora_fin" id="frmRep_fin">
</form>
<form id="formEliminar" method="POST" action="" style="display:none">
    <input type="hidden" name="accion" value="eliminar_cita">
    <input type="hidden" name="cita_id" id="frmEliminar_cita_id">
</form>

<script>
// ═══════════════════════════════════════════════════════════
//  TOAST al cargar si hubo POST (mensaje desde PHP)
// ═══════════════════════════════════════════════════════════
<?php if ($mensaje_toast): ?>
document.addEventListener('DOMContentLoaded', () => {
    showToast(<?php echo json_encode($mensaje_toast); ?>, <?php echo json_encode($toast_type); ?>);
});
<?php endif; ?>

// ═══════════════════════════════════════════════════════════
//  VARIABLES GLOBALES
// ═══════════════════════════════════════════════════════════
const USUARIO_ROL = "<?php echo $_SESSION['usuario_rol']; ?>";
let citaActualSeleccionada = null;
let panelAbierto = false;

// ═══════════════════════════════════════════════════════════
//  PANEL LATERAL — NUEVA CITA
// ═══════════════════════════════════════════════════════════
function abrirNuevaCita() {
    abrirNuevaCitaDiaHora('', '');
}

function abrirNuevaCitaDia(fecha) {
    abrirNuevaCitaDiaHora(fecha, '');
}

function abrirNuevaCitaDiaHora(fecha, hora) {
    const panel    = document.getElementById('modal-nueva-cita');
    const wrapper  = document.getElementById('agendaWrapper');
    const inputFecha  = document.getElementById('inputFechaCita');
    const selInicio   = document.getElementById('selectHoraInicio');
    const selFin      = document.getElementById('selectHoraFin');
    const titulo      = document.getElementById('panelTituloNuevaCita');

    // Pre-llenar campos
    if (fecha) inputFecha.value = fecha;
    if (hora) {
        selInicio.value = hora;
        // Autosugerir fin = inicio + 1 hora
        const [hh, mm] = hora.split(':').map(Number);
        const finMin   = hh * 60 + mm + 60;
        const finHH    = Math.floor(finMin / 60);
        const finMM    = finMin % 60;
        const finStr   = `${String(finHH).padStart(2,'0')}:${String(finMM).padStart(2,'0')}`;
        selFin.value   = finStr;
        titulo.textContent = `Agendar – ${hora}`;
    } else {
        titulo.textContent = 'Agendar Cita';
    }

    if (!panelAbierto) {
        panel.classList.remove('hidden');
        panel.classList.add('flex');
        wrapper.style.marginRight = '384px';
        setTimeout(() => {
            panel.classList.remove('translate-x-full');
            panel.classList.add('translate-x-0');
        }, 10);
        panelAbierto = true;
    }
}

function cerrarNuevaCita() {
    const panel   = document.getElementById('modal-nueva-cita');
    const wrapper = document.getElementById('agendaWrapper');
    panel.classList.remove('translate-x-0');
    panel.classList.add('translate-x-full');
    wrapper.style.marginRight = '0';
    setTimeout(() => {
        panel.classList.add('hidden');
        panel.classList.remove('flex');
    }, 300);
    panelAbierto = false;
}

// ═══════════════════════════════════════════════════════════
//  MINI-CALENDARIO POPOVER — Variables de estado
// ═══════════════════════════════════════════════════════════
const FECHA_REF      = "<?php echo $fecha_ref; ?>";       // Fecha actual de referencia
const VISTA_ACTUAL   = "<?php echo $vista; ?>";            // 'semana' | 'dia'
const LUNES_FECHA    = "<?php echo $lunes_fecha; ?>";      // Lunes de la semana visible
const DOMINGO_FECHA  = "<?php echo $domingo_fecha; ?>";    // Domingo de la semana visible

let popoverAnio = <?php echo (int)date('Y', strtotime($fecha_ref)); ?>;
let popoverMes  = <?php echo (int)date('m', strtotime($fecha_ref)); ?>;
let modoAnioActivo = false; // false = mini-cal, true = grid de meses

const MESES_NOMBRES  = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
const MESES_CORTOS   = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

// ─── Formato YYYY-MM-DD ───────────────────────────────────
function fmtFecha(y, m, d) {
    return `${y}-${String(m).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
}
function getTodayStr() {
    const t = new Date();
    return fmtFecha(t.getFullYear(), t.getMonth() + 1, t.getDate());
}

// ─── Renderizar mini-calendario ───────────────────────────
function renderMiniCalendar() {
    const year  = popoverAnio;
    const month = popoverMes;

    // Actualizar etiqueta del header
    const labelEl = document.getElementById('popoverMesAnioLabel');
    if (labelEl) labelEl.textContent = MESES_NOMBRES[month - 1] + ' ' + year;

    // Primer día del mes (0=Dom, 1=Lun, ...)
    const firstDay = new Date(year, month - 1, 1);
    const daysInMonth = new Date(year, month, 0).getDate();
    let startDow = firstDay.getDay();
    startDow = startDow === 0 ? 6 : startDow - 1; // Convertir a lunes=0

    const todayStr  = getTodayStr();
    let cells = [];

    // Celdas vacías antes del día 1
    for (let i = 0; i < startDow; i++) {
        cells.push('<div></div>');
    }

    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = fmtFecha(year, month, d);
        const isToday     = (dateStr === todayStr);
        const isRef       = (dateStr === FECHA_REF);
        const inWeek      = (dateStr >= LUNES_FECHA && dateStr <= DOMINGO_FECHA);
        const esDomSab    = (() => { const dow = new Date(year, month - 1, d).getDay(); return dow === 0 || dow === 6; })();

        let cls = 'w-8 h-8 flex items-center justify-center rounded-xl text-xs font-bold cursor-pointer transition mx-auto ';

        if (isToday && isRef && VISTA_ACTUAL === 'dia') {
            cls += 'bg-brand text-white shadow-md ring-2 ring-brand ring-offset-1';
        } else if (isToday) {
            cls += 'bg-brand text-white shadow-md';
        } else if (isRef && VISTA_ACTUAL === 'dia') {
            cls += 'ring-2 ring-brand bg-teal-50 text-brand';
        } else if (inWeek && VISTA_ACTUAL === 'semana') {
            cls += 'bg-teal-50 text-brand';
        } else if (esDomSab) {
            cls += 'text-amber-600 hover:bg-amber-50';
        } else {
            cls += 'text-slate-700 hover:bg-teal-50 hover:text-brand';
        }

        cells.push(`<button type="button" class="${cls}" onclick="irADia('${dateStr}')">${d}</button>`);
    }

    const body = document.getElementById('miniCalBody');
    if (body) {
        body.innerHTML = cells.join('');
        body.className = 'grid grid-cols-7 gap-0.5';
    }

    // Ocultar grid de meses
    const modoGrid = document.getElementById('modoAnioGrid');
    const dayHeaders = document.getElementById('popoverDayHeaders');
    if (modoGrid) { modoGrid.classList.add('hidden'); modoGrid.classList.remove('grid'); }
    if (dayHeaders) dayHeaders.classList.remove('hidden');
    modoAnioActivo = false;

    lucide.createIcons();
}

// ─── Grid de meses (modo año) ─────────────────────────────
function renderModoAnio() {
    const modoGrid   = document.getElementById('modoAnioGrid');
    const body       = document.getElementById('miniCalBody');
    const dayHeaders = document.getElementById('popoverDayHeaders');
    const labelEl    = document.getElementById('popoverMesAnioLabel');

    if (labelEl) labelEl.textContent = String(popoverAnio);
    if (body)       body.innerHTML = '';
    if (dayHeaders) dayHeaders.classList.add('hidden');

    const cells = MESES_CORTOS.map((m, idx) => {
        const isActive = (idx + 1 === popoverMes && popoverAnio === <?php echo (int)date('Y', strtotime($fecha_ref)); ?>);
        const cls = isActive
            ? 'py-1.5 rounded-xl text-xs font-black bg-brand text-white shadow-sm'
            : 'py-1.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-teal-50 hover:text-brand transition';
        return `<button class="${cls}" onclick="elegirMes(${idx + 1})">${m}</button>`;
    }).join('');

    if (modoGrid) {
        modoGrid.innerHTML = cells;
        modoGrid.classList.remove('hidden');
        modoGrid.classList.add('grid');
        modoGrid.style.gridTemplateColumns = 'repeat(4, 1fr)';
        modoGrid.style.gap = '6px';
        modoGrid.style.marginTop = '8px';
    }
    modoAnioActivo = true;
}

function elegirMes(mes) {
    popoverMes = mes;
    renderMiniCalendar();
}

function toggleModoAnio() {
    if (modoAnioActivo) {
        renderMiniCalendar();
    } else {
        renderModoAnio();
    }
}

// ─── Navegar mes en el popover ────────────────────────────
function popoverNavMes(delta) {
    if (modoAnioActivo) {
        // En modo año: navegar por años
        popoverAnio += delta;
        renderModoAnio();
        return;
    }
    popoverMes += delta;
    if (popoverMes > 12) { popoverMes = 1; popoverAnio++; }
    if (popoverMes < 1)  { popoverMes = 12; popoverAnio--; }
    renderMiniCalendar();
}

// ─── Ir a un día específico ────────────────────────────────
// En semana: muestra la semana que contiene ese día.
// En día: muestra ese día exacto.
function irADia(fecha) {
    cerrarPopover();
    window.location.href = `?fecha=${fecha}&vista=${VISTA_ACTUAL}`;
}

function irAHoy() {
    cerrarPopover();
    window.location.href = `?fecha=${getTodayStr()}&vista=${VISTA_ACTUAL}`;
}

// ─── Abrir / Cerrar popover ────────────────────────────────
function toggleMonthPopover() {
    const pop   = document.getElementById('monthPopover');
    const arrow = document.getElementById('monthArrow');
    const isOpen = pop.classList.contains('open');
    if (isOpen) {
        cerrarPopover();
    } else {
        pop.classList.add('open');
        arrow.style.transform = 'rotate(180deg)';
        renderMiniCalendar(); // Renderiza el mini-cal al abrir
    }
}

function cerrarPopover() {
    const pop   = document.getElementById('monthPopover');
    const arrow = document.getElementById('monthArrow');
    if (pop)   pop.classList.remove('open');
    if (arrow) arrow.style.transform = 'rotate(0)';
    modoAnioActivo = false;
}

// Cerrar popover si click fuera
document.addEventListener('click', (e) => {
    const wrapper = document.getElementById('monthPickerWrapper');
    if (wrapper && !wrapper.contains(e.target)) cerrarPopover();
});

// ═══════════════════════════════════════════════════════════
//  MODAL DETALLE CITA
// ═══════════════════════════════════════════════════════════
function abrirDetalleCita(cita) {
    citaActualSeleccionada = cita;
    const modal = document.getElementById('modalDetalleCita');
    const inner = modal.querySelector('div');

    // Llenar UI
    document.getElementById('detalle_paciente_nombre').innerText = cita.paciente_nombre;
    document.getElementById('detalle_horario').innerText  = cita.hora_inicio.substring(0,5) + ' – ' + cita.hora_fin.substring(0,5);
    document.getElementById('detalle_telefono').innerText = cita.paciente_telefono || 'Sin Teléfono';
    document.getElementById('detalle_motivo').innerText   = cita.motivo || 'Sin motivo especificado';

    // Badge de estado
    const badge = document.getElementById('detalle_estado_badge');
    const estadoMostrar = cita.estado_visual || cita.estado;
    badge.innerText = estadoMostrar;
    badge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider ';
    if (cita.estado === 'Completada')       badge.className += 'bg-emerald-100 text-emerald-700';
    else if (cita.estado === 'Cancelada')   badge.className += 'bg-red-100 text-red-700';
    else if (estadoMostrar === 'Atrasada')  badge.className += 'bg-orange-100 text-orange-700';
    else                                     badge.className += 'bg-blue-100 text-blue-700';

    // Link atender
    const btnAtender = document.getElementById('btn_atender_paciente');
    if (USUARIO_ROL === 'Recepcionista') {
        btnAtender.href = `paciente_detalle.php?id=${cita.paciente_id}`;
    } else {
        btnAtender.href = `paciente_detalle.php?id=${cita.paciente_id}&cita_id=${cita.id}`;
    }

    // Reprogramar selects
    document.getElementById('rep_fecha').value  = cita.fecha;
    document.getElementById('rep_inicio').value = cita.hora_inicio.substring(0,5);
    document.getElementById('rep_fin').value    = cita.hora_fin.substring(0,5);

    // Bloqueo si completada
    const acciones  = document.getElementById('acciones_cita_container');
    const bloqueado = document.getElementById('cita_bloqueada_msg');
    if (cita.estado === 'Completada') {
        acciones.classList.add('hidden');
        bloqueado.classList.remove('hidden');
        bloqueado.classList.add('flex');
    } else {
        acciones.classList.remove('hidden');
        bloqueado.classList.add('hidden');
        bloqueado.classList.remove('flex');
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        inner.classList.remove('scale-95');
    }, 10);
    if (typeof lucide !== 'undefined') lucide.createIcons();
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

// ═══════════════════════════════════════════════════════════
//  ACCIONES DE CITA (via AJAX-like forms, sin confirm excepto eliminar)
// ═══════════════════════════════════════════════════════════
function cambiarEstadoCita(nuevoEstado) {
    if (!citaActualSeleccionada) return;
    cerrarDetalleCita();
    document.getElementById('frmEstado_cita_id').value     = citaActualSeleccionada.id;
    document.getElementById('frmEstado_nuevo_estado').value = nuevoEstado;
    document.getElementById('formCambiarEstado').submit();
}

function reprogramarCita() {
    if (!citaActualSeleccionada) return;
    const fecha  = document.getElementById('rep_fecha').value;
    const inicio = document.getElementById('rep_inicio').value;
    const fin    = document.getElementById('rep_fin').value;

    if (!fecha || !inicio || !fin) {
        showToast('Completa la fecha y ambas horas para reprogramar.', 'warning');
        return;
    }
    if (inicio >= fin) {
        showToast('La hora de inicio debe ser anterior a la hora de fin.', 'warning');
        return;
    }

    document.getElementById('frmRep_cita_id').value = citaActualSeleccionada.id;
    document.getElementById('frmRep_fecha').value   = fecha;
    document.getElementById('frmRep_inicio').value  = inicio;
    document.getElementById('frmRep_fin').value     = fin;
    cerrarDetalleCita();
    document.getElementById('formReprogramar').submit();
}

function eliminarCitaAdmin() {
    if (!citaActualSeleccionada) return;
    // Esta acción SÍ es destructiva e irreversible → usamos showConfirm
    showConfirm(
        'Esta cita se eliminará permanentemente del sistema. Esta acción no se puede deshacer.',
        function() {
            document.getElementById('frmEliminar_cita_id').value = citaActualSeleccionada.id;
            cerrarDetalleCita();
            document.getElementById('formEliminar').submit();
        },
        null,
        { title: '¿Eliminar esta cita?', confirmText: 'Sí, eliminar', type: 'danger' }
    );
}

// ═══════════════════════════════════════════════════════════
//  WHATSAPP
// ═══════════════════════════════════════════════════════════
function enviarRecordatorioWhatsApp() {
    if (!citaActualSeleccionada) return;
    const c = citaActualSeleccionada;
    let phone = c.paciente_telefono ? c.paciente_telefono.replace(/\s+/g, '').replace(/\D/g, '') : '';
    if (!phone) {
        showToast('El paciente no tiene un número de teléfono registrado.', 'warning');
        return;
    }
    if (phone.length === 9) phone = '51' + phone;
    else if (phone.startsWith('0')) phone = '51' + phone.substring(1);

    const parts = c.fecha.split('-');
    const fecha = `${parts[2]}/${parts[1]}/${parts[0]}`;
    const hora  = c.hora_inicio.substring(0, 5);
    const msg   = `Hola *${c.paciente_nombre}*, te contactamos de *MahuDent* para recordarte tu cita:\n*Fecha:* ${fecha}\n*Hora:* ${hora}\nPor favor, conf\xEDrmame si asistir\xE1s. _Si necesitas cambiar el d\xEDa, av\xEDsame con tiempo._\n\xA1Te esperamos!`;
    window.open(`https://wa.me/${phone}?text=${encodeURIComponent(msg)}`, '_blank');
}

// ═══════════════════════════════════════════════════════════
//  ESCAPE y click-fuera para cerrar modales
// ═══════════════════════════════════════════════════════════
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        if (panelAbierto) cerrarNuevaCita();
        if (!document.getElementById('modalDetalleCita').classList.contains('hidden')) cerrarDetalleCita();
    }
});

document.getElementById('modalDetalleCita').addEventListener('click', (e) => {
    if (e.target === document.getElementById('modalDetalleCita')) cerrarDetalleCita();
});

// ═══════════════════════════════════════════════════════════
//  INICIALIZACIÓN
// ═══════════════════════════════════════════════════════════
lucide.createIcons();

document.addEventListener('DOMContentLoaded', () => {
    // TomSelect para búsqueda de pacientes
    new TomSelect('#pacienteSelect', {
        create: false,
        sortField: { field: 'text', direction: 'asc' },
        placeholder: 'Buscar paciente por nombre o DNI...'
    });

    <?php if ($pre_paciente_id > 0 || $mantener_modal_abierto): ?>
    setTimeout(() => abrirNuevaCitaDiaHora(<?php echo json_encode($pre_fecha ?: date('Y-m-d')); ?>, <?php echo json_encode($pre_hora ?: ''); ?>), 150);
    <?php endif; ?>
});
</script>

</body>
</html>
