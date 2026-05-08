import os

path = r'c:\Users\mejia\.gemini\antigravity\scratch\SISTEMA-ODONTOLOGICO\agenda.php'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

php_logic = """<?php
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
$mes_nombre = $meses[date('F', strtotime($lunes_fecha))];
$anio = date('Y', strtotime($lunes_fecha));
$titulo_mes = $mes_nombre . ' ' . $anio;

// Botones de navegación
$semana_anterior = date('Y-m-d', strtotime($lunes_fecha . ' - 7 days'));
$semana_siguiente = date('Y-m-d', strtotime($lunes_fecha . ' + 7 days'));
?>"""

import re
# Replace the top PHP block
content = re.sub(r'<\?php.*?(\?>(?=\s*<!DOCTYPE html>))', php_logic + "\n?>", content, count=1, flags=re.DOTALL)

# Replace the Date Navigation Header
new_nav = """<div class="flex items-center gap-4">
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
                </div>"""
content = re.sub(r'<div class="flex items-center gap-4">\s*<h1 class="text-3xl font-black text-slate-800">Agenda.*?</div>\s*</div>', new_nav, content, flags=re.DOTALL)

# Now replace the calendar headers (Lun, Mar, Mié...)
calendar_header = """<div class="calendar-grid border-b border-slate-200 bg-slate-50 shrink-0">
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
                </div>"""
content = re.sub(r'<div class="calendar-grid border-b border-slate-200 bg-slate-50 shrink-0">.*?</div>\s*<div class="flex-1 overflow-y-auto no-scrollbar relative">', calendar_header + '\n                <div class="flex-1 overflow-y-auto no-scrollbar relative">', content, flags=re.DOTALL)

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)
