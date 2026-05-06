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

$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'nueva_cita') {
    $resultado = $citaCtrl->store($_POST);
    if($resultado === true) {
        $mensaje = "<div class='bg-emerald-100 text-emerald-700 p-3 rounded-xl mb-4 font-bold text-sm border border-emerald-200'>¡Cita guardada con éxito!</div>";
    } else {
        $mensaje = "<div class='bg-red-100 text-red-700 p-3 rounded-xl mb-4 font-bold text-sm border border-red-200'>Error al guardar: " . htmlspecialchars($resultado) . "</div>";
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
                        <span class="text-lg font-bold text-slate-700 w-40 text-center"><?php echo $titulo_mes; ?></span>
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

            <div class="flex-1 bg-white rounded-2xl shadow-sm border border-slate-200 flex flex-col overflow-hidden">
                
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
                    
                    <div class="absolute w-full flex items-center z-10" style="top: 150px;">
                        <div class="w-14 text-right pr-2">
                            <span class="text-[10px] font-bold text-red-500">10:30</span>
                        </div>
                        <div class="flex-1 border-t-2 border-red-500 border-dashed relative">
                            <div class="absolute -left-1 -top-1.5 w-3 h-3 bg-red-500 rounded-full"></div>
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
                            <div class="absolute w-[95%] left-[2.5%] border-l-4 rounded p-1.5 shadow-sm hover:shadow-md transition cursor-pointer z-0 <?php echo $cita['css_color']; ?>" 
                                 style="top: <?php echo $cita['css_top']; ?>px; height: <?php echo $cita['css_height']; ?>px;">
                                <p class="text-[10px] font-bold uppercase mb-0.5"><?php echo date("H:i", strtotime($cita['hora_inicio'])) . ' - ' . date("H:i", strtotime($cita['hora_fin'])); ?></p>
                                <p class="text-xs font-bold leading-tight"><?php echo htmlspecialchars($cita['paciente_nombre']); ?></p>
                                <p class="text-[10px] <?php echo $cita['css_text_light']; ?> truncate"><?php echo htmlspecialchars($cita['motivo']); ?></p>
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
                            <div class="absolute w-[95%] left-[2.5%] border-l-4 rounded p-1.5 shadow-sm hover:shadow-md transition cursor-pointer z-0 <?php echo $cita['css_color']; ?>" 
                                 style="top: <?php echo $cita['css_top']; ?>px; height: <?php echo $cita['css_height']; ?>px;">
                                <p class="text-[10px] font-bold uppercase mb-0.5"><?php echo date("H:i", strtotime($cita['hora_inicio'])) . ' - ' . date("H:i", strtotime($cita['hora_fin'])); ?></p>
                                <p class="text-xs font-bold leading-tight"><?php echo htmlspecialchars($cita['paciente_nombre']); ?></p>
                                <p class="text-[10px] <?php echo $cita['css_text_light']; ?> truncate"><?php echo htmlspecialchars($cita['motivo']); ?></p>
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
                            <div class="absolute w-[95%] left-[2.5%] border-l-4 rounded p-1.5 shadow-sm hover:shadow-md transition cursor-pointer z-0 <?php echo $cita['css_color']; ?>" 
                                 style="top: <?php echo $cita['css_top']; ?>px; height: <?php echo $cita['css_height']; ?>px;">
                                <p class="text-[10px] font-bold uppercase mb-0.5"><?php echo date("H:i", strtotime($cita['hora_inicio'])) . ' - ' . date("H:i", strtotime($cita['hora_fin'])); ?></p>
                                <p class="text-xs font-bold leading-tight"><?php echo htmlspecialchars($cita['paciente_nombre']); ?></p>
                                <p class="text-[10px] <?php echo $cita['css_text_light']; ?> truncate"><?php echo htmlspecialchars($cita['motivo']); ?></p>
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
                            <div class="absolute w-[95%] left-[2.5%] border-l-4 rounded p-1.5 shadow-sm hover:shadow-md transition cursor-pointer z-0 <?php echo $cita['css_color']; ?>" 
                                 style="top: <?php echo $cita['css_top']; ?>px; height: <?php echo $cita['css_height']; ?>px;">
                                <p class="text-[10px] font-bold uppercase mb-0.5"><?php echo date("H:i", strtotime($cita['hora_inicio'])) . ' - ' . date("H:i", strtotime($cita['hora_fin'])); ?></p>
                                <p class="text-xs font-bold leading-tight"><?php echo htmlspecialchars($cita['paciente_nombre']); ?></p>
                                <p class="text-[10px] <?php echo $cita['css_text_light']; ?> truncate"><?php echo htmlspecialchars($cita['motivo']); ?></p>
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
                            <div class="absolute w-[95%] left-[2.5%] border-l-4 rounded p-1.5 shadow-sm hover:shadow-md transition cursor-pointer z-0 <?php echo $cita['css_color']; ?>" 
                                 style="top: <?php echo $cita['css_top']; ?>px; height: <?php echo $cita['css_height']; ?>px;">
                                <p class="text-[10px] font-bold uppercase mb-0.5"><?php echo date("H:i", strtotime($cita['hora_inicio'])) . ' - ' . date("H:i", strtotime($cita['hora_fin'])); ?></p>
                                <p class="text-xs font-bold leading-tight"><?php echo htmlspecialchars($cita['paciente_nombre']); ?></p>
                                <p class="text-[10px] <?php echo $cita['css_text_light']; ?> truncate"><?php echo htmlspecialchars($cita['motivo']); ?></p>
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
                            <div class="absolute w-[95%] left-[2.5%] border-l-4 rounded p-1.5 shadow-sm hover:shadow-md transition cursor-pointer z-0 <?php echo $cita['css_color']; ?>" 
                                 style="top: <?php echo $cita['css_top']; ?>px; height: <?php echo $cita['css_height']; ?>px;">
                                <p class="text-[10px] font-bold uppercase mb-0.5"><?php echo date("H:i", strtotime($cita['hora_inicio'])) . ' - ' . date("H:i", strtotime($cita['hora_fin'])); ?></p>
                                <p class="text-xs font-bold leading-tight"><?php echo htmlspecialchars($cita['paciente_nombre']); ?></p>
                                <p class="text-[10px] <?php echo $cita['css_text_light']; ?> truncate"><?php echo htmlspecialchars($cita['motivo']); ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>


                    </div>
                </div>
            </div>

        </div>
    
    <!-- Modal Nueva Cita -->
    <div id="modal-nueva-cita" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4 transition-opacity">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]">
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
                        <select name="paciente_id" required class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30 bg-white">
                            <option value="">Seleccione un paciente...</option>
                            <?php if($pacientes && $pacientes->num_rows > 0): ?>
                                <?php while($p = $pacientes->fetch_assoc()): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nombre'] . ' - ' . $p['dni']); ?></option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Fecha</label>
                        <input type="date" name="fecha" required class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Hora Inicio</label>
                            <input type="time" name="hora_inicio" required class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Hora Fin</label>
                            <input type="time" name="hora_fin" required class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Motivo / Tratamiento</label>
                        <input type="text" name="motivo" placeholder="Ej. Profilaxis" required class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
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
        // Inicializar iconos
        lucide.createIcons();

        function toggleModalCita() {
            const modal = document.getElementById('modal-nueva-cita');
            modal.classList.toggle('hidden');
            modal.classList.toggle('flex');
        }

    </script>
</body>
</html>
