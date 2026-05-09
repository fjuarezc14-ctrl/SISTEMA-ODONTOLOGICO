<?php
session_start();
// Si no hay sesiÃ³n iniciada, redirige al login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// 1. Conectamos a la base de datos (Usamos la ruta relativa correcta)
require_once 'controllers/PacienteController.php';

// Verificamos si vino un ID de paciente por la URL
if (!isset($_GET['id'])) {
    header("Location: pacientes.php");
    exit;
}

$paciente_id = $_GET['id'];

// Usamos el Controlador para obtener los datos
$pacienteCtrl = new PacienteController();
$paciente = $pacienteCtrl->show($paciente_id);

require_once 'controllers/OdontogramaController.php';
$odontogramaCtrl = new OdontogramaController();
$hallazgos = $odontogramaCtrl->getByPaciente($paciente_id);

require_once 'controllers/EvolucionController.php';
$evolucionCtrl = new EvolucionController();
$historial_evolutivo = $evolucionCtrl->getByPaciente($paciente_id);

$cita_id_activa = $_GET['cita_id'] ?? null;

if (!$paciente) {
    // Paciente no encontrado
    header("Location: pacientes.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial ClÃ­nico - <?php echo htmlspecialchars($paciente['nombre']); ?> - MahuDent</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <script async src="https://unpkg.com/es-module-shims@1.6.3/dist/es-module-shims.js"></script>
    <script type="importmap">
      {
        "imports": {
          "three": "https://unpkg.com/three@0.150.1/build/three.module.js",
          "three/addons/": "https://unpkg.com/three@0.150.1/examples/jsm/"
        }
      }
    </script>

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
            overflow: hidden;
        }

        .bg-brand {
            background-color: var(--brand-primary);
        }

        .text-brand {
            color: var(--brand-primary);
        }

        .bg-brand-light {
            background-color: var(--brand-secondary);
        }

        /* Estilos Odontograma General (Grilla) */
        .diente-svg {
            cursor: pointer;
            transition: transform 0.2s;
        }

        .diente-svg:hover {
            transform: scale(1.15);
            filter: drop-shadow(0 6px 8px rgba(15, 118, 110, 0.2));
        }

        /* Estilos Diagrama AnatÃ³mico 2D (Modal Derecha) */
        .cara-diente-2d {
            fill: #f8fafc;
            stroke: #cbd5e1;
            stroke-width: 2.5;
            cursor: pointer;
            transition: all 0.2s;
        }

        .cara-diente-2d:hover {
            fill: #e2e8f0;
            filter: brightness(0.95);
        }

        /* Colores dinÃ¡micos para tratamientos (Se aplican tanto al 2D como al 3D) */
        .estado-caries {
            fill: #ef4444 !important;
            stroke: #b91c1c !important;
        }

        /* Rojo */
        .estado-resina {
            fill: #3b82f6 !important;
            stroke: #1d4ed8 !important;
        }

        /* Azul */
        .estado-corona {
            fill: #f59e0b !important;
            stroke: #b45309 !important;
        }

        /* Naranja */
        .estado-ausente {
            fill: #94a3b8 !important;
            stroke: #475569 !important;
            opacity: 0.5;
        }

        /* Gris */

        /* Contenedor del visor 3D */
        #three-container {
            width: 100%;
            height: 100%;
            min-height: 250px;
            cursor: grab;
            position: relative;
        }

        #three-container:active {
            cursor: grabbing;
        }

        #three-container canvas {
            outline: none;
            display: block;
            border-radius: 1rem;
        }

        /* Preloader del 3D */
        #loading-3d {
            position: absolute;
            inset: 0;
            background: rgba(248, 250, 252, 0.9);
            display: flex;
            flex-direction: column;
            items-center;
            justify-content: center;
            z-index: 10;
            gap: 1rem;
            border-radius: 1rem;
        }
    </style>
</head>

<body class="flex h-screen">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden bg-slate-50">

        <?php $page_title = 'Detalles';
        include 'includes/header.php'; ?>

        <div class="flex-1 overflow-y-auto p-8 relative">
            <?php if ($cita_id_activa): ?>
            <div class="bg-brand text-white p-4 rounded-2xl mb-6 shadow-md flex justify-between items-center border border-teal-600 relative overflow-hidden">
                <div class="absolute -right-10 -top-10 w-32 h-32 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
                <div class="relative z-10 flex items-center gap-3">
                    <div class="bg-white/20 p-2 rounded-xl"><i data-lucide="stethoscope" class="w-6 h-6"></i></div>
                    <div>
                        <h4 class="font-black text-lg uppercase tracking-wider">Cita en curso</h4>
                        <p class="text-sm text-teal-100 font-medium">Registra los hallazgos en el Odontograma y guarda una nota en el Historial Evolutivo para finalizar.</p>
                    </div>
                </div>
                <button onclick="document.getElementById('historial_evolutivo').scrollIntoView({behavior: 'smooth'})" class="relative z-10 bg-white text-brand px-5 py-2.5 rounded-xl font-bold shadow-sm hover:shadow-md hover:-translate-y-0.5 transition active:scale-95 text-sm uppercase tracking-wider">
                    Ir al Historial
                </button>
            </div>
            <?php endif; ?>
            <div class="flex flex-col lg:flex-row gap-8">

                <div class="w-full lg:w-1/3 xl:w-1/4 space-y-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="bg-brand h-24 relative">
                            <div class="absolute -bottom-10 left-6">
                                <div
                                    class="w-20 h-20 bg-white rounded-full p-1 shadow-md border-2 border-brand-secondary">
                                    <div
                                        class="w-full h-full bg-slate-200 rounded-full flex items-center justify-center text-slate-500 font-bold text-2xl uppercase">
                                        <?php echo substr($paciente['nombre'], 0, 2); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="pt-12 px-6 pb-6">
                            <h2 class="text-xl font-black text-slate-800">
                                <?php echo htmlspecialchars($paciente['nombre']); ?></h2>
                            <p class="text-sm text-slate-500 font-medium mb-4">DNI:
                                <?php echo htmlspecialchars($paciente['dni']); ?></p>
                            <div class="space-y-3 pt-4 border-t border-slate-100">
                                <div class="flex items-center gap-3 text-sm text-slate-600"><i data-lucide="phone"
                                        class="w-4 h-4 text-slate-400"></i><span
                                        class="font-medium"><?php echo htmlspecialchars($paciente['telefono']); ?></span>
                                </div>
                                <div class="flex items-center gap-3 text-sm text-slate-600"><i data-lucide="mail"
                                        class="w-4 h-4 text-slate-400"></i><span
                                        class="font-medium"><?php echo htmlspecialchars($paciente['email']); ?></span>
                                </div>
                            </div>
                            <?php if (!empty(trim($paciente['alergias'] ?? ''))): ?>
                            <div class="mt-4 bg-red-50 border border-red-200 rounded-xl p-4">
                                <div class="flex items-start gap-3">
                                    <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 mt-0.5 shrink-0"></i>
                                    <div>
                                        <h5 class="text-xs font-black text-red-700 uppercase tracking-wider mb-1">Alerta MÃ©dica / Alergias</h5>
                                        <p class="text-sm font-bold text-red-600"><?php echo nl2br(htmlspecialchars($paciente['alergias'])); ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="w-full lg:w-2/3 xl:w-3/4 flex flex-col gap-6">
                    <div class="flex gap-2 border-b border-slate-200 shrink-0">
                        <button
                            class="px-6 py-3 border-b-2 border-brand text-brand font-bold text-sm bg-brand-light/30 rounded-t-lg transition">Historia
                            y Odontograma</button>
                        <a href="presupuestos.php"
                            class="px-6 py-3 border-b-2 border-transparent text-slate-500 font-bold text-sm hover:text-slate-700 hover:bg-slate-50 rounded-t-lg transition">Presupuestos</a>
                    </div>

                    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-8">
                        <div class="flex justify-between items-center mb-10">
                            <div>
                                <h2 class="text-2xl font-black text-slate-800">Odontograma General</h2>
                                <p class="text-sm text-slate-500">Selecciona un diente para registrar hallazgos.</p>
                            </div>
                            <div class="flex gap-4 bg-slate-50 p-3 rounded-xl border border-slate-100 shadow-inner">
                                <div class="flex items-center gap-2 text-xs font-bold text-slate-600"><span
                                        class="w-3 h-3 bg-red-500 rounded-full shadow-sm"></span> Caries</div>
                                <div class="flex items-center gap-2 text-xs font-bold text-slate-600"><span
                                        class="w-3 h-3 bg-blue-500 rounded-full shadow-sm"></span> Resina</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-16 gap-2 max-w-5xl mx-auto overflow-x-auto pb-8">
                            <div class="flex justify-center gap-1.5 md:gap-3 mb-8">
                                <?php for ($i = 18; $i >= 11; $i--): ?>
                                    <div class="text-center group">
                                        <span
                                            class="text-[11px] font-bold text-slate-400 block mb-2 group-hover:text-brand transition"><?php echo $i; ?></span>
                                        <svg width="35" height="45" viewBox="0 0 40 50" class="diente-svg"
                                            onclick="abrirModalDiente(<?php echo $i; ?>)">
                                            <path id="path-diente-<?php echo $i; ?>" d="M10 5 Q20 0 30 5 L35 35 Q20 45 5 35 Z" fill="#f8fafc" stroke="#94a3b8"
                                                stroke-width="1.5" />
                                        </svg>
                                    </div>
                                <?php endfor; ?>
                                <div class="w-6 border-l-2 border-dashed border-slate-200 mx-2"></div>
                                <?php for ($i = 21; $i <= 28; $i++): ?>
                                    <div class="text-center group">
                                        <span
                                            class="text-[11px] font-bold text-slate-400 block mb-2 group-hover:text-brand transition"><?php echo $i; ?></span>
                                        <svg width="35" height="45" viewBox="0 0 40 50" class="diente-svg"
                                            onclick="abrirModalDiente(<?php echo $i; ?>)">
                                            <path id="path-diente-<?php echo $i; ?>" d="M10 5 Q20 0 30 5 L35 35 Q20 45 5 35 Z" fill="#f8fafc" stroke="#94a3b8"
                                                stroke-width="1.5" />
                                        </svg>
                                    </div>
                                <?php endfor; ?>
                            </div>

                            <div class="flex justify-center gap-1.5 md:gap-3">
                                <?php for ($i = 48; $i >= 41; $i--): ?>
                                    <div class="text-center group">
                                        <svg width="35" height="45" viewBox="0 0 40 50" class="diente-svg"
                                            onclick="abrirModalDiente(<?php echo $i; ?>)">
                                            <path id="path-diente-<?php echo $i; ?>" d="M5 15 Q20 5 35 15 L30 45 Q20 50 10 45 Z" fill="#f8fafc"
                                                stroke="#94a3b8" stroke-width="1.5" />
                                        </svg>
                                        <span
                                            class="text-[11px] font-bold text-slate-400 block mt-2 group-hover:text-brand transition"><?php echo $i; ?></span>
                                    </div>
                                <?php endfor; ?>
                                <div class="w-6 border-l-2 border-dashed border-slate-200 mx-2"></div>
                                <?php for ($i = 31; $i <= 38; $i++): ?>
                                    <div class="text-center group">
                                        <svg width="35" height="45" viewBox="0 0 40 50" class="diente-svg"
                                            onclick="abrirModalDiente(<?php echo $i; ?>)">
                                            <path id="path-diente-<?php echo $i; ?>" d="M5 15 Q20 5 35 15 L30 45 Q20 50 10 45 Z" fill="#f8fafc"
                                                stroke="#94a3b8" stroke-width="1.5" />
                                        </svg>
                                        <span
                                            class="text-[11px] font-bold text-slate-400 block mt-2 group-hover:text-brand transition"><?php echo $i; ?></span>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- HISTORIAL EVOLUTIVO -->
                    <div id="historial_evolutivo" class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden mt-6">
                        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                            <div>
                                <h3 class="text-lg font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                                    <i data-lucide="clipboard-list" class="w-5 h-5 text-brand"></i> Historial Evolutivo
                                </h3>
                                <p class="text-sm text-slate-500 font-medium mt-1">BitÃ¡cora cronolÃ³gica de tratamientos realizados.</p>
                            </div>
                        </div>

                        <div class="p-6">
                            <?php if ($cita_id_activa): ?>
                            <div class="bg-blue-50 border border-blue-100 rounded-2xl p-5 mb-8">
                                <h4 class="font-bold text-blue-800 mb-3 text-sm uppercase tracking-wider flex items-center gap-2">
                                    <i data-lucide="pen-line" class="w-4 h-4"></i> AÃ±adir Nota ClÃ­nica (SesiÃ³n Actual)
                                </h4>
                                <textarea id="nota_evolucion" rows="3" placeholder="Ej: Profilaxis completa. Se detectÃ³ caries superficial en pieza 14, se procede con curaciÃ³n de resina simple. Paciente estable." class="w-full bg-white border-2 border-blue-100 rounded-xl p-4 text-slate-700 outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition mb-3"></textarea>
                                <button onclick="guardarEvolucion(<?php echo $cita_id_activa; ?>)" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl shadow-md transition flex items-center justify-center gap-2 text-sm uppercase tracking-wider">
                                    <i data-lucide="save" class="w-4 h-4"></i> Guardar EvoluciÃ³n y Finalizar Cita
                                </button>
                            </div>
                            <?php else: ?>
                            <div class="bg-slate-50 border border-slate-100 rounded-2xl p-5 mb-8">
                                <h4 class="font-bold text-slate-700 mb-3 text-sm uppercase tracking-wider flex items-center gap-2">
                                    <i data-lucide="plus-circle" class="w-4 h-4"></i> AÃ±adir Nota RÃ¡pida (Sin cita)
                                </h4>
                                <textarea id="nota_evolucion" rows="2" placeholder="AÃ±adir una observaciÃ³n general al paciente..." class="w-full bg-white border-2 border-slate-200 rounded-xl p-3 text-slate-700 outline-none focus:ring-2 focus:ring-slate-500/20 focus:border-slate-400 transition mb-3 text-sm"></textarea>
                                <button onclick="guardarEvolucion(null)" class="w-full sm:w-auto bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 px-5 rounded-xl shadow-md transition flex items-center justify-center gap-2 text-xs uppercase tracking-wider">
                                    Guardar Nota RÃ¡pida
                                </button>
                            </div>
                            <?php endif; ?>

                            <div class="space-y-4 relative before:absolute before:inset-0 before:ml-5 before:-translate-x-px md:before:mx-auto md:before:translate-x-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-slate-200 before:to-transparent">
                                <?php if(empty($historial_evolutivo)): ?>
                                    <div class="text-center py-8 text-slate-400 font-medium text-sm">
                                        No hay registros evolutivos previos para este paciente.
                                    </div>
                                <?php else: ?>
                                    <?php foreach($historial_evolutivo as $nota): ?>
                                    <div class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group is-active">
                                        <div class="flex items-center justify-center w-10 h-10 rounded-full border-4 border-white bg-slate-100 text-slate-500 shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2">
                                            <i data-lucide="<?php echo $nota['cita_id'] ? 'calendar-check' : 'sticky-note'; ?>" class="w-4 h-4"></i>
                                        </div>
                                        <div class="w-[calc(100%-4rem)] md:w-[calc(50%-2.5rem)] p-4 rounded-2xl border border-slate-100 bg-white shadow-sm hover:shadow-md transition">
                                            <div class="flex items-center justify-between space-x-2 mb-1">
                                                <div class="font-bold text-slate-800 text-sm"><?php echo htmlspecialchars($nota['doctor_nombre'] ?? 'Dr. General'); ?></div>
                                                <time class="font-medium text-brand text-xs"><?php echo date('d M, Y', strtotime($nota['fecha'])); ?></time>
                                            </div>
                                            <div class="text-slate-600 text-sm leading-relaxed mt-2"><?php echo nl2br(htmlspecialchars($nota['descripcion'])); ?></div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- PRESUPUESTOS -->
                    <div id="seccion_presupuestos" class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden mt-6">
                        <div class="p-6 border-b border-slate-100 flex flex-wrap justify-between items-center bg-slate-50/50 gap-4">
                            <div>
                                <h3 class="text-lg font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                                    <i data-lucide="receipt" class="w-5 h-5 text-emerald-600"></i> Presupuestos
                                </h3>
                                <p class="text-sm text-slate-500 font-medium mt-1">Proformas y cotizaciones de tratamiento.</p>
                            </div>
                            <div class="flex gap-2">
                                <button onclick="generarPresupuestoDesdeOdontograma()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-5 rounded-xl shadow-md transition flex items-center gap-2 text-xs uppercase tracking-wider">
                                    <i data-lucide="zap" class="w-4 h-4"></i> Generar desde Odontograma
                                </button>
                                <button onclick="abrirModalNuevoPresupuesto()" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-2.5 px-5 rounded-xl transition flex items-center gap-2 text-xs uppercase tracking-wider">
                                    <i data-lucide="plus" class="w-4 h-4"></i> Nuevo Manual
                                </button>
                            </div>
                        </div>

                        <div class="p-6">
                            <!-- Presupuesto Activo / Editor -->
                            <div id="editorPresupuesto" class="hidden">
                                <div class="flex flex-wrap justify-between items-center mb-4 gap-3">
                                    <h4 class="font-black text-slate-800 text-sm uppercase tracking-wider flex items-center gap-2">
                                        <i data-lucide="file-text" class="w-4 h-4 text-emerald-600"></i>
                                        Presupuesto #<span id="presupuestoId">-</span>
                                        <span id="presupuestoEstadoBadge" class="text-xs bg-amber-100 text-amber-700 px-3 py-1 rounded-full font-bold ml-2">Borrador</span>
                                    </h4>
                                    <div class="flex gap-2">
                                        <button onclick="agregarItemManual()" class="bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold py-2 px-4 rounded-lg transition text-xs flex items-center gap-1">
                                            <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i> AÃ±adir Ãtem
                                        </button>
                                        <button onclick="cerrarEditorPresupuesto()" class="bg-slate-50 hover:bg-slate-100 text-slate-500 font-bold py-2 px-4 rounded-lg transition text-xs flex items-center gap-1">
                                            <i data-lucide="x" class="w-3.5 h-3.5"></i> Cerrar
                                        </button>
                                    </div>
                                </div>

                                <!-- Tabla de Ãtems -->
                                <div class="overflow-x-auto rounded-xl border border-slate-200">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="bg-slate-50 text-slate-500 uppercase text-xs tracking-wider">
                                                <th class="text-left p-3 font-bold">Tratamiento</th>
                                                <th class="text-center p-3 font-bold w-16">Pieza</th>
                                                <th class="text-center p-3 font-bold w-16">Cant.</th>
                                                <th class="text-right p-3 font-bold w-28">Precio Base</th>
                                                <th class="text-right p-3 font-bold w-28">Precio Ajustado</th>
                                                <th class="text-right p-3 font-bold w-28">Subtotal</th>
                                                <th class="text-center p-3 font-bold w-16">AcciÃ³n</th>
                                            </tr>
                                        </thead>
                                        <tbody id="presupuestoItemsBody" class="divide-y divide-slate-100">
                                            <!-- Se llena dinÃ¡micamente -->
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Totales y Descuento -->
                                <div class="mt-4 flex flex-col md:flex-row justify-between gap-4">
                                    <div class="flex items-center gap-3 bg-amber-50 border border-amber-200 rounded-xl p-4">
                                        <i data-lucide="percent" class="w-5 h-5 text-amber-600"></i>
                                        <label class="text-sm font-bold text-amber-800">Descuento:</label>
                                        <input type="number" id="descuentoPorcentaje" value="0" min="0" max="100" step="0.5"
                                            class="w-20 bg-white border border-amber-300 rounded-lg p-2 text-center font-bold text-amber-800 outline-none focus:ring-2 focus:ring-amber-400"
                                            onchange="aplicarDescuento()">
                                        <span class="text-sm font-bold text-amber-700">%</span>
                                    </div>
                                    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-right space-y-1 min-w-[220px]">
                                        <div class="flex justify-between text-sm text-slate-600">
                                            <span class="font-medium">Subtotal:</span>
                                            <span id="presupuestoSubtotal" class="font-bold">S/ 0.00</span>
                                        </div>
                                        <div class="flex justify-between text-sm text-amber-600">
                                            <span class="font-medium">Descuento:</span>
                                            <span id="presupuestoDescuento" class="font-bold">- S/ 0.00</span>
                                        </div>
                                        <div class="border-t border-emerald-200 pt-1 flex justify-between">
                                            <span class="font-black text-emerald-800 uppercase text-sm">Total:</span>
                                            <span id="presupuestoTotal" class="font-black text-emerald-800 text-lg">S/ 0.00</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Acciones del Presupuesto -->
                                <div class="mt-4 flex flex-wrap gap-2 justify-end">
                                    <button onclick="cambiarEstadoPresupuesto('Enviado')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-5 rounded-xl shadow transition text-xs uppercase tracking-wider flex items-center gap-2">
                                        <i data-lucide="send" class="w-4 h-4"></i> Marcar como Enviado
                                    </button>
                                    <button onclick="cambiarEstadoPresupuesto('Aprobado')" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-5 rounded-xl shadow transition text-xs uppercase tracking-wider flex items-center gap-2">
                                        <i data-lucide="check-circle" class="w-4 h-4"></i> Aprobar
                                    </button>
                                    <button onclick="eliminarPresupuestoActivo()" class="bg-red-50 hover:bg-red-100 text-red-600 font-bold py-2.5 px-5 rounded-xl transition text-xs uppercase tracking-wider flex items-center gap-2">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i> Eliminar
                                    </button>
                                </div>
                            </div>

                            <!-- Lista de Presupuestos Anteriores -->
                            <div id="listaPresupuestos">
                                <div id="listaPresupuestosVacia" class="text-center py-10 text-slate-400">
                                    <i data-lucide="file-x" class="w-12 h-12 mx-auto mb-3 opacity-50"></i>
                                    <p class="font-bold text-sm">No hay presupuestos registrados.</p>
                                    <p class="text-xs mt-1">Genera uno desde el Odontograma o crea uno manual.</p>
                                </div>
                                <div id="listaPresupuestosItems" class="space-y-3 hidden">
                                    <!-- Se llena dinÃ¡micamente -->
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <!-- Modal Agregar Ãtem al Presupuesto -->
    <div id="modalAgregarItem" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg transform scale-95 transition-transform duration-300 overflow-hidden">
            <div class="bg-slate-50 border-b border-slate-100 p-5 flex justify-between items-center">
                <h3 class="text-lg font-black text-slate-800 flex items-center gap-2">
                    <i data-lucide="plus-circle" class="w-5 h-5 text-emerald-600"></i> Agregar Tratamiento
                </h3>
                <button onclick="cerrarModalItem()" class="text-slate-400 hover:text-red-500 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Seleccionar del CatÃ¡logo</label>
                    <select id="itemCatalogoSelect" onchange="seleccionarCatalogo()" class="w-full bg-white border-2 border-slate-200 rounded-xl p-3 text-sm font-medium outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="">-- Seleccionar tratamiento --</option>
                    </select>
                </div>
                <div class="border-t border-slate-100 pt-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">DescripciÃ³n</label>
                    <input type="text" id="itemDescripcion" placeholder="Nombre del tratamiento" class="w-full bg-white border-2 border-slate-200 rounded-xl p-3 text-sm font-medium outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Pieza Dental</label>
                        <input type="number" id="itemPieza" placeholder="-" min="11" max="85" class="w-full bg-white border-2 border-slate-200 rounded-xl p-3 text-sm font-medium text-center outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Cantidad</label>
                        <input type="number" id="itemCantidad" value="1" min="1" class="w-full bg-white border-2 border-slate-200 rounded-xl p-3 text-sm font-medium text-center outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Precio (S/)</label>
                        <input type="number" id="itemPrecio" value="0" step="0.01" min="0" class="w-full bg-white border-2 border-slate-200 rounded-xl p-3 text-sm font-bold text-center outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>
            </div>
            <div class="bg-slate-50 border-t border-slate-100 p-5 flex justify-end gap-3">
                <button onclick="cerrarModalItem()" class="px-5 py-2.5 bg-white border border-slate-200 text-slate-600 font-bold rounded-xl text-sm hover:bg-slate-50 transition">Cancelar</button>
                <button onclick="confirmarAgregarItem()" class="px-5 py-2.5 bg-emerald-600 text-white font-bold rounded-xl text-sm hover:bg-emerald-700 shadow-md transition flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i> Agregar al Presupuesto
                </button>
            </div>
        </div>
    </div>

    <div id="modalDiente"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4 opacity-0 transition-opacity duration-300">
        <div
            class="bg-white rounded-3xl shadow-2xl p-8 w-full max-w-5xl transform scale-95 transition-transform duration-300 overflow-y-auto max-h-[95vh]">

            <div class="flex justify-between items-center mb-6 border-b border-slate-100 pb-4 shrink-0">
                <h3 class="text-2xl font-black text-slate-800 uppercase flex items-center gap-3">
                    <div
                        class="bg-brand-light text-brand w-12 h-12 rounded-2xl flex items-center justify-center shadow-md border-2 border-white">
                        <span id="numDienteTitulo" class="text-2xl">0</span></div>
                    Registro ClÃ­nico Detallado
                </h3>
                <button onclick="cerrarModalDiente()"
                    class="w-10 h-10 bg-slate-50 hover:bg-red-50 text-slate-400 hover:text-red-500 rounded-full flex items-center justify-center transition shadow-inner"><i
                        data-lucide="x"></i></button>
            </div>

            <div class="flex flex-col lg:flex-row gap-8">

                <div
                    class="w-full lg:w-1/2 bg-slate-100 rounded-3xl p-4 flex flex-col items-center justify-center border-2 border-slate-200 shadow-inner relative min-h-[350px]">

                    <div id="loading-3d">
                        <div class="w-10 h-10 border-4 border-slate-200 border-t-brand rounded-full animate-spin"></div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Cargando Modelo 3D...</p>
                    </div>

                    <div id="three-container" title="Arrastra para girar 360Â°, haz clic para marcar cara"></div>

                    <div
                        class="absolute bottom-4 left-4 right-4 bg-white/70 backdrop-blur-sm p-3 rounded-xl border border-white/50 text-center shadow-sm pointer-events-none z-10">
                        <p
                            class="text-[11px] font-black text-teal-900 uppercase tracking-widest flex items-center justify-center gap-2">
                            <i data-lucide="rotate-3d" class="w-4 h-4"></i> Vista 3D Interactiva 360Â°
                        </p>
                        <p class="text-[10px] text-teal-700 mt-1">Gira el diente y haz clic directamente en la cara
                            afectada.</p>
                    </div>
                </div>

                <div class="w-full lg:w-1/2 flex flex-col gap-6">

                    <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100 shadow-inner">
                        <label class="block text-xs font-black text-slate-500 uppercase mb-3 tracking-widest">1.
                            Configurar tipo de Hallazgo / Tratamiento</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <select id="estadoDiente"
                                class="w-full bg-white border-2 border-slate-200 rounded-xl p-3 font-bold text-slate-700 outline-none focus:ring-2 focus:ring-brand focus:border-brand shadow-sm transition">
                                <option value="caries">Caries (Pendiente - Rojo)</option>
                                <option value="resina">Resina (Realizado - Azul)</option>
                                <option value="corona">Corona / IncrustaciÃ³n (Naranja)</option>
                                <option value="ausente">Pieza Ausente (Gris)</option>
                                <option value="">Limpiar / Normal</option>
                            </select>
                            <input type="text" id="notasDiente" placeholder="Notas opcionales (ej: Caries profunda)"
                                class="w-full bg-white border-2 border-slate-200 rounded-xl p-3 text-sm font-medium outline-none focus:ring-2 focus:ring-brand focus:border-brand shadow-sm transition">
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col items-center">
                        <label
                            class="block text-xs font-black text-slate-500 uppercase mb-5 tracking-widest text-center">2.
                            VisualizaciÃ³n AnatÃ³mica (Clic para marcar manualmente)</label>

                        <div class="flex justify-center relative scale-110">
                            <svg viewBox="0 0 100 120" id="svg-diente-2d"
                                style="width: 140px; height: 160px; cursor: pointer; filter: drop-shadow(0px 4px 6px rgba(0,0,0,0.1));">
                                <path data-cara="Raiz_Izquierda"
                                    d="M 35 65 C 25 105, 45 115, 45 65" 
                                    class="cara-diente-2d" onclick="pintarCara2D(this)" />
                                <path data-cara="Raiz_Derecha"
                                    d="M 65 65 C 75 105, 55 115, 55 65" 
                                    class="cara-diente-2d" onclick="pintarCara2D(this)" />

                                <path data-cara="Oclusal"
                                    d="M 25 25 C 40 10, 60 10, 75 25 L 65 35 C 55 25, 45 25, 35 35 Z"
                                    class="cara-diente-2d" onclick="pintarCara2D(this)" />
                                <path data-cara="Mesial"
                                    d="M 25 25 C 10 40, 20 65, 35 65 L 40 50 C 30 50, 25 40, 35 35 Z"
                                    class="cara-diente-2d" onclick="pintarCara2D(this)" />
                                <path data-cara="Distal"
                                    d="M 75 25 C 90 40, 80 65, 65 65 L 60 50 C 70 50, 75 40, 65 35 Z"
                                    class="cara-diente-2d" onclick="pintarCara2D(this)" />
                                <path data-cara="Lingual"
                                    d="M 35 65 C 45 70, 55 70, 65 65 L 60 50 C 55 55, 45 55, 40 50 Z"
                                    class="cara-diente-2d" onclick="pintarCara2D(this)" />
                                <path data-cara="Vestibular"
                                    d="M 35 35 C 45 25, 55 25, 65 35 L 60 50 C 55 55, 45 55, 40 50 Z"
                                    class="cara-diente-2d" onclick="pintarCara2D(this)" />
                            </svg>
                        </div>
                    </div>

                    <button onclick="guardarHallazgos()"
                        class="w-full bg-brand text-white py-4 rounded-2xl font-black shadow-lg hover:bg-teal-800 transition uppercase tracking-widest flex justify-center items-center gap-3 hover:scale-[1.02] active:scale-95">
                        <i data-lucide="save" class="w-6 h-6"></i> Guardar Hallazgos en Historial
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>

    <script type="module">
        import * as THREE from 'three';
        import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
        import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';

        let scene, camera, renderer, controls, raycaster, mouse;
        let toothModel = null;
        let container = document.getElementById('three-container');
        let loadingScreen = document.getElementById('loading-3d');

        const meshToCaraMap = {
            'Cara_Oclusal': 'Oclusal',
            'Cara_Vestibular': 'Vestibular',
            'Cara_Lingual': 'Lingual',
            'Cara_Mesial': 'Mesial',
            'Cara_Distal': 'Distal'
        };

        const coloresTratamiento = {
            'caries': 0xef4444, 'resina': 0x3b82f6, 'corona': 0xf59e0b,
            'ausente': 0x94a3b8, 'normal': 0xffffff
        };

        init3D();
        animate3D();

        function init3D() {
            scene = new THREE.Scene();
            scene.background = new THREE.Color(0xf1f5f9);

            camera = new THREE.PerspectiveCamera(45, container.clientWidth / container.clientHeight, 0.1, 1000);
            camera.position.set(0, 2, 5);

            renderer = new THREE.WebGLRenderer({ antialias: true });
            renderer.setPixelRatio(window.devicePixelRatio);
            renderer.setSize(container.clientWidth, container.clientHeight);
            container.appendChild(renderer.domElement);

            controls = new OrbitControls(camera, renderer.domElement);
            controls.enableDamping = true;
            controls.dampingFactor = 0.05;

            const ambientLight = new THREE.AmbientLight(0xffffff, 0.7);
            scene.add(ambientLight);
            const directionalLight = new THREE.DirectionalLight(0xffffff, 0.6);
            directionalLight.position.set(2, 4, 3);
            scene.add(directionalLight);

            raycaster = new THREE.Raycaster();
            mouse = new THREE.Vector2();

            const loader = new GLTFLoader();

            loader.load('assets/models/molar.gltf', function (gltf) {
                toothModel = gltf.scene;

                // --- MAGIA PARA CENTRAR Y ENFOCAR EL MODELO AUTOMÃTICAMENTE ---

                // 1. Calculamos la "caja invisible" que envuelve a tu modelo y su centro real
                const box = new THREE.Box3().setFromObject(toothModel);
                const center = box.getCenter(new THREE.Vector3());
                const size = box.getSize(new THREE.Vector3());

                // 2. Forzamos al diente a moverse al centro absoluto (0,0,0) de tu pantalla
                toothModel.position.sub(center);

                // 3. Calculamos la dimensiÃ³n mÃ¡s grande del diente para ajustar la cÃ¡mara
                const maxDim = Math.max(size.x, size.y, size.z);

                // 4. Ubicamos la cÃ¡mara a la distancia perfecta (ni muy cerca ni muy lejos)
                camera.position.set(0, maxDim * 0.5, maxDim * 2.5);

                // 5. Le decimos a los controles que el eje de rotaciÃ³n sea el centro absoluto
                controls.target.set(0, 0, 0);

                // 6. Ponemos lÃ­mites al zoom para que el doctor no se "meta" dentro del diente ni lo pierda de vista
                controls.minDistance = maxDim * 1.2; // LÃ­mite para acercarse
                controls.maxDistance = maxDim * 4;   // LÃ­mite para alejarse

                controls.update();
                // -------------------------------------------------------------

                // Agregamos luces adicionales para que se vean bien los detalles anatÃ³micos
                toothModel.traverse(function (child) {
                    if (child.isMesh) {
                        child.castShadow = true;
                        child.receiveShadow = true;
                        // Ajuste para materiales GLTF
                        if (child.material) {
                            child.material.side = THREE.DoubleSide; // Asegura que no haya huecos invisibles
                        }
                    }
                });

                scene.add(toothModel);
                loadingScreen.style.display = 'none';

            }, undefined, function (error) {
                console.warn('Modelo no encontrado. Generando diente de prueba.');
                generarDiente3DDePrueba();
            });

            renderer.domElement.addEventListener('click', onDocumentClick, false);
            window.addEventListener('resize', onWindowResize, false);
        }

        function generarDiente3DDePrueba() {
            toothModel = new THREE.Group();
            const mat = new THREE.MeshStandardMaterial({ color: 0xffffff, roughness: 0.2 });

            // FunciÃ³n para crear cada "cara" del diente como un bloque independiente
            const crearCara = (w, h, d, x, y, z, nombre) => {
                const mesh = new THREE.Mesh(new THREE.BoxGeometry(w, h, d), mat.clone());
                mesh.position.set(x, y, z);
                mesh.name = nombre;

                // Agregamos bordes para que se vea mejor
                const edges = new THREE.LineSegments(new THREE.EdgesGeometry(mesh.geometry), new THREE.LineBasicMaterial({ color: 0x94a3b8 }));
                mesh.add(edges);
                toothModel.add(mesh);
            };

            // Creamos un diente abstracto (Cubo con 5 tapas cliqueables)
            crearCara(1.4, 0.2, 1.4, 0, 0.7, 0, 'Cara_Oclusal'); // Arriba
            crearCara(1.4, 1.4, 0.2, 0, 0, 0.7, 'Cara_Vestibular'); // Frente
            crearCara(1.4, 1.4, 0.2, 0, 0, -0.7, 'Cara_Lingual'); // Atras
            crearCara(0.2, 1.4, 1.4, -0.7, 0, 0, 'Cara_Mesial'); // Izquierda
            crearCara(0.2, 1.4, 1.4, 0.7, 0, 0, 'Cara_Distal'); // Derecha

            scene.add(toothModel);
            loadingScreen.style.display = 'none';
        }

        function onWindowResize() {
            if (!container) return;
            camera.aspect = container.clientWidth / container.clientHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(container.clientWidth, container.clientHeight);
        }

        function animate3D() {
            requestAnimationFrame(animate3D);
            if (controls) controls.update();
            if (renderer && scene && camera) renderer.render(scene, camera);
        }

        function onDocumentClick(event) {
            if (!toothModel) return;
            const rect = renderer.domElement.getBoundingClientRect();
            mouse.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
            mouse.y = - ((event.clientY - rect.top) / rect.height) * 2 + 1;
            raycaster.setFromCamera(mouse, camera);

            // Buscamos intersecciones con cualquier parte del modelo 3D
            const intersects = raycaster.intersectObjects(toothModel.children, true);

            if (intersects.length > 0) {
                const clickedMesh = intersects[0].object;

                // Obtenemos el color seleccionado en el menÃº desplegable
                const estado = document.getElementById('estadoDiente').value;
                const colorHex = coloresTratamiento[estado] || coloresTratamiento['normal'];

                // MAGIA PARA PINTAR EL MODELO IMPORTADO:
                // Temporalmente desactivado a peticiÃ³n del usuario hasta tener el modelo correcto
                /*
                if (clickedMesh.material) {
                    clickedMesh.material = clickedMesh.material.clone();
                    jQuery(clickedMesh.material.color).animate({
                        r: ((colorHex >> 16) & 0xFF) / 255,
                        g: ((colorHex >> 8) & 0xFF) / 255,
                        b: (colorHex & 0xFF) / 255
                    }, 200);
                }
                */

                // Como el modelo 3D es de una sola pieza, al hacerle clic marcaremos
                // por defecto el centro (Oclusal) en el diagrama 2D para mantenerlos conectados.
                const path2D = document.querySelector(`#svg-diente-2d path[data-cara="Oclusal"], #svg-diente-2d circle[data-cara="Oclusal"]`);
                if (path2D) pintarCara2D(path2D, true);
            }
        }

        window.pintarDiente3D = function(estado, nombreCara = null) {
            if (!toothModel) return;
            const colorHex = coloresTratamiento[estado] || coloresTratamiento['normal'];
            
            // 1. Intentar pintar solo la malla especÃ­fica (Si el modelo GLTF estÃ¡ dividido)
            if (nombreCara) {
                const nombreMeshBuscado = 'Cara_' + nombreCara;
                toothModel.traverse(function (child) {
                    if (child.isMesh && child.name === nombreMeshBuscado && child.material) {
                        child.material = child.material.clone();
                        jQuery(child.material.color).animate({
                            r: ((colorHex >> 16) & 0xFF) / 255,
                            g: ((colorHex >> 8) & 0xFF) / 255,
                            b: (colorHex & 0xFF) / 255
                        }, 200);
                    }
                });
            }
        };

        window.reinit3D = function () { if (renderer) onWindowResize(); }
    </script>

    <script>
        const pacienteId = <?php echo $paciente_id; ?>;
        let hallazgosOdontograma = <?php echo json_encode($hallazgos); ?>;
        
        const modal = document.getElementById('modalDiente');
        const numDienteTitulo = document.getElementById('numDienteTitulo');

        function renderizarGrilla() {
            // Limpiar todos los dientes primero
            document.querySelectorAll('.diente-svg path').forEach(path => {
                path.classList.remove('estado-caries', 'estado-resina', 'estado-ausente', 'estado-corona');
            });

            // Agrupar hallazgos por diente para encontrar el estado predominante
            const estadosPorDiente = {};
            const notasPorDiente = {};
            hallazgosOdontograma.forEach(h => {
                if (!estadosPorDiente[h.diente_numero]) estadosPorDiente[h.diente_numero] = [];
                estadosPorDiente[h.diente_numero].push(h.estado);
                
                if (h.notas && h.notas.trim() !== '') {
                    if (!notasPorDiente[h.diente_numero]) notasPorDiente[h.diente_numero] = [];
                    notasPorDiente[h.diente_numero].push(`[${h.cara_afectada}] ${h.notas}`);
                }
            });

            for (const [diente, estados] of Object.entries(estadosPorDiente)) {
                const pathEl = document.getElementById(`path-diente-${diente}`);
                if (pathEl) {
                    // Prioridad de pintado en la grilla general:
                    let estadoFinal = '';
                    if (estados.includes('ausente')) estadoFinal = 'ausente';
                    else if (estados.includes('caries')) estadoFinal = 'caries';
                    else if (estados.includes('corona')) estadoFinal = 'corona';
                    else if (estados.includes('resina')) estadoFinal = 'resina';
                    
                    if (estadoFinal) {
                        pathEl.classList.add(`estado-${estadoFinal}`);
                    }
                    
                    const notas = notasPorDiente[diente];
                    if (notas && notas.length > 0) {
                        pathEl.parentElement.parentElement.setAttribute('title', notas.join('\n'));
                        // AÃ±adir indicador visual (un borde o clase especial opcional)
                        pathEl.parentElement.parentElement.classList.add('cursor-help');
                    } else {
                        pathEl.parentElement.parentElement.removeAttribute('title');
                        pathEl.parentElement.parentElement.classList.remove('cursor-help');
                    }
                }
            }
        }

        // Ejecutar al cargar la pÃ¡gina
        renderizarGrilla();

        function abrirModalDiente(num) {
            numDienteTitulo.innerText = num;

            // Limpiar el mapa anatÃ³mico 2D y resetear formulario
            document.querySelectorAll('.cara-diente-2d').forEach(el => {
                el.classList.remove('estado-caries', 'estado-resina', 'estado-ausente', 'estado-corona');
            });
            document.getElementById('estadoDiente').value = "";
            document.getElementById('notasDiente').value = "";

            // Pre-llenar el mapa 2D con los hallazgos de ESTE diente
            const hallazgosDiente = hallazgosOdontograma.filter(h => h.diente_numero == num);
            
            // Pre-llenar notas si existen
            const hallazgoConNota = hallazgosDiente.find(h => h.notas && h.notas.trim() !== '');
            if (hallazgoConNota) {
                document.getElementById('notasDiente').value = hallazgoConNota.notas;
            }
            
            // Determinar el "peor" estado para pintar el 3D
            let estado3D = 'normal';
            const estados = hallazgosDiente.map(h => h.estado);
            if (estados.includes('caries')) estado3D = 'caries';
            else if (estados.includes('corona')) estado3D = 'corona';
            else if (estados.includes('resina')) estado3D = 'resina';
            else if (estados.includes('ausente')) estado3D = 'ausente';

            hallazgosDiente.forEach(h => {
                if(h.estado) {
                    const el = document.querySelector(`.cara-diente-2d[data-cara="${h.cara_afectada}"]`);
                    if(el) el.classList.add(`estado-${h.estado}`);
                }
            });

            // Pintar el modelo 3D segÃºn el estado guardado
            if(window.pintarDiente3D) {
                // Si el modelo 3D no estÃ¡ dividido, esto pintarÃ¡ todo. 
                // Si sÃ­ lo estÃ¡, no pintarÃ¡ nada porque no le pasamos nombreCara. 
                // AsÃ­ que, de manera inteligente, repintamos las caras especÃ­ficas si las hay.
                window.pintarDiente3D(estado3D); // Pinta bloque entero por defecto
                
                hallazgosDiente.forEach(h => {
                    if (h.estado) window.pintarDiente3D(h.estado, h.cara_afectada);
                });
            }

            // Mostramos modal con animaciÃ³n
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modal.children[0].classList.remove('scale-95');
                modal.children[0].classList.add('scale-100');
                if (window.reinit3D) window.reinit3D();
            }, 10);
        }

        function cerrarModalDiente() {
            modal.classList.add('opacity-0');
            modal.children[0].classList.remove('scale-100');
            modal.children[0].classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
                document.getElementById('notasDiente').value = "";
            }, 300);
        }

        function pintarCara2D(elemento, isFrom3D = false) {
            const estado = document.getElementById('estadoDiente').value;
            const nombreCara = elemento.getAttribute('data-cara');

            elemento.classList.remove('estado-caries', 'estado-resina', 'estado-ausente', 'estado-corona');
            if (estado !== "") {
                elemento.classList.add('estado-' + estado);
            }
            
            // Sincronizar el modelo 3D cuando el usuario interactÃºa con el 2D
            if (!isFrom3D && window.pintarDiente3D) {
                window.pintarDiente3D(estado || 'normal', nombreCara);
                if (!estado) {
                    // Si limpiamos una cara, podrÃ­amos necesitar forzar un repintado general si es un bloque sÃ³lido
                    window.pintarDiente3D('normal');
                }
            }
        }

        async function guardarHallazgos() {
            const diente = document.getElementById('numDienteTitulo').innerText;
            const estadoGlobal = document.getElementById('estadoDiente').value;
            const notasGlobales = document.getElementById('notasDiente').value;
            
            // Recopilar todas las caras que tienen un estado activo en el diagrama 2D
            let carasGuardadas = 0;
            const promesas = [];

            document.querySelectorAll('.cara-diente-2d').forEach(cara => {
                let estadoCara = '';
                if (cara.classList.contains('estado-caries')) estadoCara = 'caries';
                else if (cara.classList.contains('estado-resina')) estadoCara = 'resina';
                else if (cara.classList.contains('estado-corona')) estadoCara = 'corona';
                else if (cara.classList.contains('estado-ausente')) estadoCara = 'ausente';
                
                // Vamos a enviar un UPDATE para cada cara (incluso vacÃ­as para limpiar)
                const payload = {
                    paciente_id: pacienteId,
                    diente_numero: parseInt(diente),
                    cara_afectada: cara.getAttribute('data-cara'),
                    estado: estadoCara,
                    notas: estadoCara ? notasGlobales : '' // Notas solo si hay estado
                };

                promesas.push(
                    fetch('ajax_odontograma.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    }).then(res => res.json())
                );
            });

            try {
                const resultados = await Promise.all(promesas);
                
                // Actualizar cache local para redibujar la grilla
                document.querySelectorAll('.cara-diente-2d').forEach(cara => {
                    const nombreCara = cara.getAttribute('data-cara');
                    let estadoCara = '';
                    if (cara.classList.contains('estado-caries')) estadoCara = 'caries';
                    else if (cara.classList.contains('estado-resina')) estadoCara = 'resina';
                    else if (cara.classList.contains('estado-corona')) estadoCara = 'corona';
                    else if (cara.classList.contains('estado-ausente')) estadoCara = 'ausente';

                    // Eliminar hallazgos antiguos de esta cara
                    hallazgosOdontograma = hallazgosOdontograma.filter(h => !(h.diente_numero == diente && h.cara_afectada == nombreCara));
                    
                    // AÃ±adir si hay nuevo
                    if (estadoCara) {
                        hallazgosOdontograma.push({
                            diente_numero: parseInt(diente),
                            cara_afectada: nombreCara,
                            estado: estadoCara,
                            notas: notasGlobales
                        });
                    }
                });

                renderizarGrilla();
                cerrarModalDiente();
                alert('Hallazgos guardados exitosamente.');
                
            } catch (error) {
                console.error("Error al guardar:", error);
                alert("OcurriÃ³ un error al guardar los hallazgos.");
            }
        }

        async function guardarEvolucion(cita_id) {
            const nota = document.getElementById('nota_evolucion').value.trim();
            if (!nota) {
                alert('Por favor, escriba una nota evolutiva antes de guardar.');
                return;
            }
            
            const btn = event.currentTarget;
            const textOriginal = btn.innerHTML;
            btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Guardando...';
            btn.disabled = true;

            try {
                const response = await fetch('ajax_evolucion.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        paciente_id: <?php echo $paciente_id; ?>,
                        cita_id: cita_id,
                        descripcion: nota
                    })
                });

                const result = await response.json();
                if (result.success) {
                    if (cita_id) {
                        alert('EvoluciÃ³n guardada y cita completada con Ã©xito.');
                        window.location.href = `paciente_detalle.php?id=<?php echo $paciente_id; ?>`;
                    } else {
                        window.location.reload();
                    }
                } else {
                    alert('Error: ' + result.error);
                    btn.innerHTML = textOriginal;
                    btn.disabled = false;
                    lucide.createIcons();
                }
            } catch (error) {
                console.error(error);
                alert('Error de conexiÃ³n.');
                btn.innerHTML = textOriginal;
                btn.disabled = false;
                lucide.createIcons();
            }
        }
    </script>

    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

    <script>
        // --- MÃ“DULO DE PRESUPUESTOS ---
        let presupuestoActivo = null;
        let catalogoTratamientos = [];

        async function cargarCatalogo() {
            try {
                const res = await fetch('ajax_presupuesto.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({accion: 'obtener_catalogo'})
                });
                const data = await res.json();
                if (data.success) {
                    catalogoTratamientos = data.catalogo;
                    poblarSelectCatalogo();
                }
            } catch(e) { console.error('Error cargando catÃ¡logo:', e); }
        }

        function poblarSelectCatalogo() {
            const select = document.getElementById('itemCatalogoSelect');
            if (!select) return;
            select.innerHTML = '<option value="">-- Seleccionar tratamiento --</option>';
            
            let categoriaActual = '';
            catalogoTratamientos.forEach(t => {
                if (t.categoria !== categoriaActual) {
                    if (categoriaActual !== '') select.innerHTML += '</optgroup>';
                    select.innerHTML += `<optgroup label="${t.categoria}">`;
                    categoriaActual = t.categoria;
                }
                select.innerHTML += `<option value="${t.id}" data-precio="${t.precio_base}" data-nombre="${t.nombre}">
                    ${t.nombre} â€” S/ ${parseFloat(t.precio_base).toFixed(2)}
                </option>`;
            });
            if (categoriaActual !== '') select.innerHTML += '</optgroup>';
        }

        function seleccionarCatalogo() {
            const select = document.getElementById('itemCatalogoSelect');
            const option = select.options[select.selectedIndex];
            if (option.value) {
                document.getElementById('itemDescripcion').value = option.dataset.nombre;
                document.getElementById('itemPrecio').value = parseFloat(option.dataset.precio).toFixed(2);
            }
        }

        async function cargarListaPresupuestos() {
            try {
                const res = await fetch('ajax_presupuesto.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({accion: 'listar_presupuestos', paciente_id: pacienteId})
                });
                const data = await res.json();
                if (data.success) renderizarListaPresupuestos(data.presupuestos);
            } catch(e) { console.error('Error cargando presupuestos:', e); }
        }

        function renderizarListaPresupuestos(presupuestos) {
            const vacia = document.getElementById('listaPresupuestosVacia');
            const lista = document.getElementById('listaPresupuestosItems');
            if (!presupuestos || presupuestos.length === 0) {
                vacia.classList.remove('hidden');
                lista.classList.add('hidden');
                return;
            }
            vacia.classList.add('hidden');
            lista.classList.remove('hidden');

            const badgeColors = {
                'Borrador': 'bg-amber-100 text-amber-700',
                'Enviado': 'bg-blue-100 text-blue-700',
                'Aprobado': 'bg-emerald-100 text-emerald-700',
                'Rechazado': 'bg-red-100 text-red-700',
                'Vencido': 'bg-slate-100 text-slate-500'
            };

            lista.innerHTML = presupuestos.map(p => `
                <div class="flex items-center justify-between bg-slate-50 rounded-xl p-4 hover:bg-slate-100 transition cursor-pointer group" onclick="abrirPresupuesto(${p.id})">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                            <i data-lucide="file-text" class="w-5 h-5 text-emerald-600"></i>
                        </div>
                        <div>
                            <h5 class="font-bold text-slate-800 text-sm">Presupuesto #${p.id}</h5>
                            <p class="text-xs text-slate-500">${formatearFecha(p.fecha_emision)} Â· ${p.doctor_nombre || 'Sin doctor'}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-bold px-3 py-1 rounded-full ${badgeColors[p.estado] || 'bg-slate-100 text-slate-500'}">${p.estado}</span>
                        <span class="font-black text-emerald-700 text-sm">S/ ${parseFloat(p.total).toFixed(2)}</span>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400 group-hover:text-slate-600 transition"></i>
                    </div>
                </div>
            `).join('');
            lucide.createIcons();
        }

        // --- GeneraciÃ³n automÃ¡tica ---
        async function generarPresupuestoDesdeOdontograma() {
            if (!hallazgosOdontograma || hallazgosOdontograma.length === 0) {
                alert('No hay hallazgos en el odontograma. Marque al menos un diente antes de generar.');
                return;
            }
            const hallazgosConEstado = hallazgosOdontograma.filter(h => h.estado && h.estado !== '');
            if (hallazgosConEstado.length === 0) {
                alert('No hay hallazgos activos en el odontograma.');
                return;
            }
            // Contar dientes Ãºnicos
            const dentesUnicos = [...new Set(hallazgosConEstado.map(h => h.diente_numero + '_' + h.estado))];
            if (!confirm(`Se generarÃ¡ un presupuesto basado en ${dentesUnicos.length} hallazgo(s) del odontograma. Â¿Continuar?`)) return;

            try {
                const res = await fetch('ajax_presupuesto.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        accion: 'generar_desde_odontograma',
                        paciente_id: pacienteId,
                        hallazgos: hallazgosConEstado
                    })
                });
                const data = await res.json();
                if (data.success) {
                    presupuestoActivo = data.presupuesto;
                    mostrarEditorPresupuesto(data.presupuesto);
                    cargarListaPresupuestos();
                } else {
                    alert('Error: ' + (data.error || 'No se pudo generar'));
                }
            } catch(e) {
                console.error(e);
                alert('Error de conexiÃ³n al generar presupuesto.');
            }
        }

        // --- Nuevo Manual ---
        async function abrirModalNuevoPresupuesto() {
            try {
                const res = await fetch('ajax_presupuesto.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({accion: 'crear_vacio', paciente_id: pacienteId})
                });
                const data = await res.json();
                if (data.success) {
                    presupuestoActivo = data.presupuesto;
                    mostrarEditorPresupuesto(data.presupuesto);
                    cargarListaPresupuestos();
                } else {
                    alert('Error: ' + (data.error || 'No se pudo crear'));
                }
            } catch(e) { console.error(e); alert('Error de conexiÃ³n.'); }
        }

        // --- Abrir existente ---
        async function abrirPresupuesto(id) {
            try {
                const res = await fetch('ajax_presupuesto.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({accion: 'obtener_presupuesto', presupuesto_id: id})
                });
                const data = await res.json();
                if (data.success) {
                    presupuestoActivo = data.presupuesto;
                    mostrarEditorPresupuesto(data.presupuesto);
                } else {
                    alert('Error: ' + (data.error || 'No encontrado'));
                }
            } catch(e) { console.error(e); alert('Error al cargar presupuesto.'); }
        }

        // --- Editor ---
        function mostrarEditorPresupuesto(presupuesto) {
            document.getElementById('editorPresupuesto').classList.remove('hidden');
            document.getElementById('listaPresupuestos').classList.add('hidden');
            document.getElementById('presupuestoId').innerText = presupuesto.id;
            
            const badgeEl = document.getElementById('presupuestoEstadoBadge');
            const badgeColors = {
                'Borrador': 'bg-amber-100 text-amber-700',
                'Enviado': 'bg-blue-100 text-blue-700',
                'Aprobado': 'bg-emerald-100 text-emerald-700',
                'Rechazado': 'bg-red-100 text-red-700',
                'Vencido': 'bg-slate-100 text-slate-500'
            };
            badgeEl.className = `text-xs px-3 py-1 rounded-full font-bold ml-2 ${badgeColors[presupuesto.estado] || 'bg-slate-100 text-slate-500'}`;
            badgeEl.innerText = presupuesto.estado;
            document.getElementById('descuentoPorcentaje').value = presupuesto.descuento_porcentaje || 0;

            renderizarItemsPresupuesto(presupuesto.items || []);
            actualizarTotalesUI(presupuesto);
            document.getElementById('seccion_presupuestos').scrollIntoView({behavior: 'smooth'});
            lucide.createIcons();
        }

        function renderizarItemsPresupuesto(items) {
            const tbody = document.getElementById('presupuestoItemsBody');
            if (!items || items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-slate-400 text-sm">No hay Ã­tems. Use el botÃ³n <b>AÃ±adir Ãtem</b> para agregar tratamientos.</td></tr>';
                return;
            }
            tbody.innerHTML = items.map(item => {
                const precioMostrado = item.precio_ajustado ?? item.precio_unitario;
                return `
                <tr class="hover:bg-slate-50 transition">
                    <td class="p-3 font-medium text-slate-700">${item.descripcion}</td>
                    <td class="p-3 text-center text-slate-500">${item.diente_numero || '-'}</td>
                    <td class="p-3 text-center text-slate-700 font-bold">${item.cantidad}</td>
                    <td class="p-3 text-right text-slate-500">S/ ${parseFloat(item.precio_unitario).toFixed(2)}</td>
                    <td class="p-3 text-right">
                        <input type="number" value="${parseFloat(precioMostrado).toFixed(2)}" step="0.01" min="0"
                            class="w-24 text-right bg-blue-50 border border-blue-200 rounded-lg p-1.5 font-bold text-blue-800 outline-none focus:ring-2 focus:ring-blue-400 text-sm"
                            onchange="actualizarPrecioItem(${item.id}, this.value)">
                    </td>
                    <td class="p-3 text-right font-bold text-slate-800">S/ ${parseFloat(item.subtotal).toFixed(2)}</td>
                    <td class="p-3 text-center">
                        <button onclick="eliminarItem(${item.id})" class="text-slate-400 hover:text-red-500 transition p-1 rounded-lg hover:bg-red-50">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </td>
                </tr>`;
            }).join('');
            lucide.createIcons();
        }

        function actualizarTotalesUI(p) {
            document.getElementById('presupuestoSubtotal').innerText = `S/ ${parseFloat(p.subtotal).toFixed(2)}`;
            document.getElementById('presupuestoDescuento').innerText = `- S/ ${parseFloat(p.descuento_monto).toFixed(2)}`;
            document.getElementById('presupuestoTotal').innerText = `S/ ${parseFloat(p.total).toFixed(2)}`;
        }

        // --- Modal Agregar Ãtem ---
        function agregarItemManual() {
            if (!presupuestoActivo) return;
            const modal = document.getElementById('modalAgregarItem');
            // Reset form
            document.getElementById('itemCatalogoSelect').value = '';
            document.getElementById('itemDescripcion').value = '';
            document.getElementById('itemPieza').value = '';
            document.getElementById('itemCantidad').value = '1';
            document.getElementById('itemPrecio').value = '0';
            // Mostrar
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => modal.querySelector('div').classList.remove('scale-95'), 10);
            lucide.createIcons();
        }

        function cerrarModalItem() {
            const modal = document.getElementById('modalAgregarItem');
            modal.querySelector('div').classList.add('scale-95');
            setTimeout(() => { modal.classList.add('hidden'); modal.classList.remove('flex'); }, 200);
        }

        async function confirmarAgregarItem() {
            const descripcion = document.getElementById('itemDescripcion').value.trim();
            const precio = parseFloat(document.getElementById('itemPrecio').value) || 0;
            const cantidad = parseInt(document.getElementById('itemCantidad').value) || 1;
            const pieza = document.getElementById('itemPieza').value || null;
            const catalogoId = document.getElementById('itemCatalogoSelect').value || null;

            if (!descripcion) { alert('Ingrese una descripciÃ³n del tratamiento.'); return; }
            if (precio <= 0) { alert('El precio debe ser mayor a 0.'); return; }

            try {
                const res = await fetch('ajax_presupuesto.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        accion: 'agregar_item',
                        presupuesto_id: presupuestoActivo.id,
                        tratamiento_id: catalogoId,
                        diente_numero: pieza,
                        descripcion: descripcion + (pieza ? ` (Pieza #${pieza})` : ''),
                        cantidad: cantidad,
                        precio_unitario: precio
                    })
                });
                const data = await res.json();
                if (data.success) {
                    presupuestoActivo = data.presupuesto;
                    renderizarItemsPresupuesto(data.presupuesto.items);
                    actualizarTotalesUI(data.presupuesto);
                    cerrarModalItem();
                } else {
                    alert('Error: ' + (data.error || 'No se pudo agregar'));
                }
            } catch(e) { console.error(e); alert('Error de conexiÃ³n.'); }
        }

        // --- Operaciones sobre Ã­tems ---
        async function actualizarPrecioItem(itemId, nuevoPrecio) {
            if (!presupuestoActivo) return;
            // Encontrar item actual para mantener descripciÃ³n
            const item = (presupuestoActivo.items || []).find(i => i.id == itemId);
            try {
                const res = await fetch('ajax_presupuesto.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        accion: 'actualizar_item',
                        item_id: itemId,
                        presupuesto_id: presupuestoActivo.id,
                        precio_ajustado: parseFloat(nuevoPrecio),
                        precio_unitario: item ? parseFloat(item.precio_unitario) : parseFloat(nuevoPrecio),
                        descripcion: item ? item.descripcion : 'Tratamiento',
                        cantidad: item ? item.cantidad : 1,
                        descuento_porcentaje: parseFloat(document.getElementById('descuentoPorcentaje').value) || 0
                    })
                });
                const data = await res.json();
                if (data.success) {
                    presupuestoActivo = data.presupuesto;
                    renderizarItemsPresupuesto(data.presupuesto.items);
                    actualizarTotalesUI(data.presupuesto);
                }
            } catch(e) { console.error(e); }
        }

        async function eliminarItem(itemId) {
            if (!presupuestoActivo || !confirm('Â¿Eliminar este Ã­tem?')) return;
            try {
                const res = await fetch('ajax_presupuesto.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({accion: 'eliminar_item', item_id: itemId, presupuesto_id: presupuestoActivo.id})
                });
                const data = await res.json();
                if (data.success) {
                    presupuestoActivo = data.presupuesto;
                    renderizarItemsPresupuesto(data.presupuesto.items);
                    actualizarTotalesUI(data.presupuesto);
                }
            } catch(e) { console.error(e); }
        }

        async function aplicarDescuento() {
            if (!presupuestoActivo) return;
            try {
                const res = await fetch('ajax_presupuesto.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        accion: 'aplicar_descuento',
                        presupuesto_id: presupuestoActivo.id,
                        descuento_porcentaje: parseFloat(document.getElementById('descuentoPorcentaje').value) || 0
                    })
                });
                const data = await res.json();
                if (data.success) { presupuestoActivo = data.presupuesto; actualizarTotalesUI(data.presupuesto); }
            } catch(e) { console.error(e); }
        }

        // --- Estado y eliminaciÃ³n ---
        async function cambiarEstadoPresupuesto(nuevoEstado) {
            if (!presupuestoActivo || !confirm(`Â¿Cambiar estado a "${nuevoEstado}"?`)) return;
            try {
                const res = await fetch('ajax_presupuesto.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({accion: 'cambiar_estado', presupuesto_id: presupuestoActivo.id, estado: nuevoEstado})
                });
                const data = await res.json();
                if (data.success) {
                    presupuestoActivo.estado = nuevoEstado;
                    mostrarEditorPresupuesto(presupuestoActivo);
                    cargarListaPresupuestos();
                }
            } catch(e) { console.error(e); }
        }

        async function eliminarPresupuestoActivo() {
            if (!presupuestoActivo || !confirm('Â¿Eliminar este presupuesto permanentemente?')) return;
            try {
                const res = await fetch('ajax_presupuesto.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({accion: 'eliminar_presupuesto', presupuesto_id: presupuestoActivo.id})
                });
                const data = await res.json();
                if (data.success) {
                    presupuestoActivo = null;
                    cerrarEditorPresupuesto();
                    cargarListaPresupuestos();
                }
            } catch(e) { console.error(e); }
        }

        function cerrarEditorPresupuesto() {
            document.getElementById('editorPresupuesto').classList.add('hidden');
            document.getElementById('listaPresupuestos').classList.remove('hidden');
            presupuestoActivo = null;
        }

        function formatearFecha(fecha) {
            if (!fecha) return '-';
            const d = new Date(fecha + 'T00:00:00');
            const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
            return `${d.getDate()} ${meses[d.getMonth()]}, ${d.getFullYear()}`;
        }

        // Inicializar mÃ³dulo
        cargarCatalogo();
        cargarListaPresupuestos();
    </script>

</body>

</html>
