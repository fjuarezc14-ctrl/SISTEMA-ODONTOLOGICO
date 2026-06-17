<?php
require_once 'includes/auth_guard.php';// Si no hay sesión iniciada, redirige al login
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

if ($cita_id_activa) {
    require_once 'controllers/CitaController.php';
    $citaCtrlAux = new CitaController();
    $citaCtrlAux->cambiarEstado($cita_id_activa, 'En Curso');
}

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
    <title>Historial Clínico - <?php echo htmlspecialchars($paciente['nombre']); ?> - MahuDent</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        teal: {
                            50: '#f5f3fa',
                            100: '#ede8f7',
                            200: '#d7cde6',
                            300: '#c5b5e4',
                            400: '#ab92d6',
                            500: '#937ec2',
                            600: '#7e64ab',
                            700: '#3a596a',
                            800: '#2f4958',
                            900: '#1b2d38',
                        }
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

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
            --brand-primary: #3a596a;
            --brand-secondary: #ede8f7;
            --brand-accent: #937ec2;
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

        /* Estilos Diagrama Anatómico 2D (Modal Derecha) */
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

        /* Colores dinámicos para tratamientos (Se aplican tanto al 2D como al 3D) */
        .estado-caries { fill: #ef4444 !important; stroke: #b91c1c !important; }
        .estado-extraccion_indicada { fill: #b91c1c !important; stroke: #7f1d1d !important; }
        .estado-restauracion_defectuosa { fill: #f97316 !important; stroke: #c2410c !important; }
        .estado-fractura { fill: #e11d48 !important; stroke: #be123c !important; }
        
        .estado-resina { fill: #3b82f6 !important; stroke: #1d4ed8 !important; }
        .estado-endodoncia { fill: #a855f7 !important; stroke: #7e22ce !important; }
        .estado-corona { fill: #06b6d4 !important; stroke: #0891b2 !important; }
        .estado-implante { fill: #475569 !important; stroke: #334155 !important; }
        .estado-sellante { fill: #10b981 !important; stroke: #047857 !important; }
        
        .estado-ausente { fill: #cbd5e1 !important; stroke: #94a3b8 !important; opacity: 0.8; }

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

        <div class="flex-1 overflow-y-auto p-4 md:p-8 relative">
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
                                <div class="flex items-center gap-3 text-sm text-slate-600 justify-between">
                                    <div class="flex items-center gap-3">
                                        <i data-lucide="phone" class="w-4 h-4 text-slate-400"></i>
                                        <span class="font-medium" id="paciente_telefono_span"><?php echo htmlspecialchars($paciente['telefono'] ?? '-'); ?></span>
                                    </div>
                                    <?php if (!empty($paciente['telefono'])): ?>
                                    <button onclick="enviarWhatsAppPaciente('<?php echo htmlspecialchars(addslashes($paciente['nombre'])); ?>')" class="bg-[#25D366] hover:bg-[#128C7E] text-white p-1.5 rounded-lg shadow-sm transition hover:scale-105 tooltip" title="Enviar WhatsApp">
                                        <i data-lucide="message-circle" class="w-4 h-4"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <div class="flex items-center gap-3 text-sm text-slate-600"><i data-lucide="mail"
                                        class="w-4 h-4 text-slate-400"></i><span
                                        class="font-medium"><?php echo htmlspecialchars($paciente['email'] ?? '-'); ?></span>
                                </div>
                                
                                <?php
                                    $edad = '-';
                                    if (!empty($paciente['fecha_nacimiento'])) {
                                        $fecha_nac = new DateTime($paciente['fecha_nacimiento']);
                                        $hoy = new DateTime();
                                        $edad = $hoy->diff($fecha_nac)->y . ' años';
                                    }
                                ?>
                                <div class="flex flex-wrap gap-2 pt-2">
                                    <span class="px-2 py-1 bg-slate-100 text-slate-600 text-[10px] font-bold rounded-md uppercase border border-slate-200" title="Edad / Fecha Nacimiento">
                                        <?php echo $edad; ?>
                                    </span>
                                    <span class="px-2 py-1 bg-slate-100 text-slate-600 text-[10px] font-bold rounded-md uppercase border border-slate-200" title="Sexo">
                                        <?php echo htmlspecialchars($paciente['sexo'] ?: 'No especificado'); ?>
                                    </span>
                                    <span class="px-2 py-1 bg-slate-100 text-slate-600 text-[10px] font-bold rounded-md uppercase border border-slate-200" title="Lugar de Nacimiento">
                                        <?php echo htmlspecialchars($paciente['lugar_nacimiento'] ?: 'S/L'); ?>
                                    </span>
                                </div>

                                <div class="text-xs text-slate-500 font-medium">
                                    <span class="block mb-1"><strong>Procedencia:</strong> <?php echo htmlspecialchars($paciente['procedencia'] ?: '-'); ?></span>
                                    <span class="block mb-1"><strong>Ocupación:</strong> <?php echo htmlspecialchars($paciente['ocupacion'] ?: '-'); ?></span>
                                    <span class="block"><strong>Dirección:</strong> <?php echo htmlspecialchars($paciente['direccion'] ?: '-'); ?></span>
                                </div>
                                
                                <div class="bg-blue-50/50 border border-blue-100 p-3 rounded-xl">
                                    <h5 class="text-[10px] font-black text-blue-700 uppercase tracking-wider mb-1 flex items-center gap-1"><i data-lucide="users" class="w-3 h-3"></i> Contacto de Emergencia</h5>
                                    <p class="text-xs text-slate-600 font-bold"><?php echo htmlspecialchars($paciente['contacto_emergencia'] ?: 'No registrado'); ?></p>
                                    <p class="text-xs text-slate-500"><?php echo htmlspecialchars($paciente['telefono_emergencia'] ?: '-'); ?></p>
                                </div>
                            </div>

                            <?php
                                $alergias_texto = trim($paciente['alergias'] ?? '');
                                $alergias_lower = strtolower($alergias_texto);
                                $tiene_alergias = !empty($alergias_texto) && !in_array($alergias_lower, ['ninguna', 'no', 'no tiene', 'sin alergias', 'ninguno']);
                            ?>
                            <div class="mt-4 <?php echo $tiene_alergias ? 'bg-red-50 border-red-200' : 'bg-slate-50 border-slate-200'; ?> border rounded-xl p-4">
                                <div class="flex items-start gap-3">
                                    <?php if ($tiene_alergias): ?>
                                        <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 mt-0.5 shrink-0"></i>
                                        <div>
                                            <h5 class="text-xs font-black text-red-700 uppercase tracking-wider mb-1">Alerta Médica / Alergias</h5>
                                            <p class="text-sm font-bold text-red-600"><?php echo nl2br(htmlspecialchars($alergias_texto)); ?></p>
                                        </div>
                                    <?php else: ?>
                                        <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-500 mt-0.5 shrink-0"></i>
                                        <div>
                                            <h5 class="text-xs font-black text-slate-500 uppercase tracking-wider mb-1">Alerta Médica / Alergias</h5>
                                            <p class="text-sm font-bold text-slate-400"><?php echo $alergias_texto ?: 'Ninguna registrada'; ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($paciente['embarazada'] == '1'): ?>
                                <div class="mt-3 bg-pink-100/50 border border-pink-200 rounded-lg p-2 flex items-center gap-2">
                                    <i data-lucide="heart" class="w-4 h-4 text-pink-500 fill-pink-500"></i>
                                    <span class="text-[11px] font-black text-pink-700 uppercase tracking-wider">Paciente Embarazada</span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Acciones Rápidas -->
                            <div class="mt-6 pt-6 border-t border-slate-100 flex flex-col gap-2">
                                <?php if ($_SESSION['usuario_rol'] !== 'Recepcionista'): ?>
                                <button onclick="document.getElementById('modalNuevaReceta').classList.remove('hidden')" class="w-full bg-teal-50 hover:bg-teal-200 text-teal-700 font-bold py-2.5 px-4 rounded-xl shadow-sm transition flex items-center justify-center gap-2 text-xs uppercase tracking-wider">
                                    <i data-lucide="file-text" class="w-4 h-4"></i> Generar Receta
                                </button>
                                <?php endif; ?>
                                <a href="agenda.php?paciente_id=<?php echo $paciente_id; ?>" class="w-full bg-brand hover:bg-teal-700 text-white font-bold py-2.5 px-4 rounded-xl shadow-md transition flex items-center justify-center gap-2 text-xs uppercase tracking-wider">
                                    <i data-lucide="calendar-plus" class="w-4 h-4"></i> Agendar Cita
                                </a>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="w-full lg:w-2/3 xl:w-3/4 flex flex-col gap-6">
                    <div class="flex gap-2 border-b border-slate-200 shrink-0 overflow-x-auto">
                        <button onclick="window.scrollTo({top: 0, behavior: 'smooth'})" class="px-6 py-3 border-b-2 border-brand text-brand font-bold text-sm bg-brand-light/30 rounded-t-lg transition whitespace-nowrap">Historia y Odontograma</button>
                        <button onclick="document.getElementById('seccion_triaje').scrollIntoView({behavior: 'smooth'})" class="px-6 py-3 border-b-2 border-transparent text-slate-500 font-bold text-sm hover:text-slate-700 hover:bg-teal-100 rounded-t-lg transition whitespace-nowrap">Antecedentes y Triaje</button>
                        <button onclick="document.getElementById('seccion_presupuestos').scrollIntoView({behavior: 'smooth'})" class="px-6 py-3 border-b-2 border-transparent text-slate-500 font-bold text-sm hover:text-slate-700 hover:bg-teal-100 rounded-t-lg transition whitespace-nowrap">Presupuestos</button>
                        <button onclick="document.getElementById('seccion_recetas').scrollIntoView({behavior: 'smooth'})" class="px-6 py-3 border-b-2 border-transparent text-slate-500 font-bold text-sm hover:text-slate-700 hover:bg-teal-100 rounded-t-lg transition whitespace-nowrap">Recetas</button>
                        <button onclick="document.getElementById('seccion_archivos').scrollIntoView({behavior: 'smooth'})" class="px-6 py-3 border-b-2 border-transparent text-slate-500 font-bold text-sm hover:text-slate-700 hover:bg-teal-100 rounded-t-lg transition whitespace-nowrap">Radiografías / Archivos</button>
                    </div>

                    <div class="bg-white rounded-3xl shadow-md border border-slate-200 border-t-4 border-t-teal-500 p-8 mb-8">
                        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-10">
                            <div>
                                <h2 class="text-2xl font-black text-slate-800">Odontograma General</h2>
                                <p class="text-sm text-slate-500">Selecciona un diente para registrar hallazgos.</p>
                            </div>
                            <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 shadow-inner w-full lg:w-auto">
                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-x-4 gap-y-2">
                                    <div class="flex items-center gap-2 text-[11px] font-bold text-slate-600"><span class="w-3 h-3 bg-red-500 rounded-full shadow-sm shrink-0"></span> Caries</div>
                                    <div class="flex items-center gap-2 text-[11px] font-bold text-slate-600"><span class="w-3 h-3 bg-red-700 rounded-full shadow-sm shrink-0"></span> Extracción</div>
                                    <div class="flex items-center gap-2 text-[11px] font-bold text-slate-600"><span class="w-3 h-3 bg-orange-500 rounded-full shadow-sm shrink-0"></span> Defectuosa</div>
                                    <div class="flex items-center gap-2 text-[11px] font-bold text-slate-600"><span class="w-3 h-3 bg-rose-600 rounded-full shadow-sm shrink-0"></span> Fractura</div>
                                    <div class="flex items-center gap-2 text-[11px] font-bold text-slate-600"><span class="w-3 h-3 bg-blue-500 rounded-full shadow-sm shrink-0"></span> Resina</div>
                                    <div class="flex items-center gap-2 text-[11px] font-bold text-slate-600"><span class="w-3 h-3 bg-purple-500 rounded-full shadow-sm shrink-0"></span> Endodoncia</div>
                                    <div class="flex items-center gap-2 text-[11px] font-bold text-slate-600"><span class="w-3 h-3 bg-cyan-500 rounded-full shadow-sm shrink-0"></span> Corona</div>
                                    <div class="flex items-center gap-2 text-[11px] font-bold text-slate-600"><span class="w-3 h-3 bg-slate-600 rounded-full shadow-sm shrink-0"></span> Implante</div>
                                    <div class="flex items-center gap-2 text-[11px] font-bold text-slate-600"><span class="w-3 h-3 bg-emerald-500 rounded-full shadow-sm shrink-0"></span> Sellante</div>
                                    <div class="flex items-center gap-2 text-[11px] font-bold text-slate-600"><span class="w-3 h-3 bg-slate-300 rounded-full shadow-sm shrink-0"></span> Ausente</div>
                                </div>
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
                            
                            <!-- Divisor y Dentadura Infantil -->
                            <div class="border-t border-slate-100 my-8 pt-6">
                                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest text-center mb-6">Dentadura Infantil / Temporal</h3>
                                
                                <!-- Superior Infantil -->
                                <div class="flex justify-center gap-1.5 md:gap-3 mb-6">
                                    <?php for ($i = 55; $i >= 51; $i--): ?>
                                        <div class="text-center group">
                                            <span class="text-[11px] font-bold text-slate-400 block mb-2 group-hover:text-brand transition"><?php echo $i; ?></span>
                                            <svg width="35" height="45" viewBox="0 0 40 50" class="diente-svg"
                                                onclick="abrirModalDiente(<?php echo $i; ?>)">
                                                <path id="path-diente-<?php echo $i; ?>" d="M10 5 Q20 0 30 5 L35 35 Q20 45 5 35 Z" fill="#f8fafc" stroke="#94a3b8"
                                                    stroke-width="1.5" />
                                            </svg>
                                        </div>
                                    <?php endfor; ?>
                                    <div class="w-6 border-l-2 border-dashed border-slate-200 mx-2"></div>
                                    <?php for ($i = 61; $i <= 65; $i++): ?>
                                        <div class="text-center group">
                                            <span class="text-[11px] font-bold text-slate-400 block mb-2 group-hover:text-brand transition"><?php echo $i; ?></span>
                                            <svg width="35" height="45" viewBox="0 0 40 50" class="diente-svg"
                                                onclick="abrirModalDiente(<?php echo $i; ?>)">
                                                <path id="path-diente-<?php echo $i; ?>" d="M10 5 Q20 0 30 5 L35 35 Q20 45 5 35 Z" fill="#f8fafc" stroke="#94a3b8"
                                                    stroke-width="1.5" />
                                            </svg>
                                        </div>
                                    <?php endfor; ?>
                                </div>

                                <!-- Inferior Infantil -->
                                <div class="flex justify-center gap-1.5 md:gap-3">
                                    <?php for ($i = 85; $i >= 81; $i--): ?>
                                        <div class="text-center group">
                                            <svg width="35" height="45" viewBox="0 0 40 50" class="diente-svg"
                                                onclick="abrirModalDiente(<?php echo $i; ?>)">
                                                <path id="path-diente-<?php echo $i; ?>" d="M5 15 Q20 5 35 15 L30 45 Q20 50 10 45 Z" fill="#f8fafc"
                                                    stroke="#94a3b8" stroke-width="1.5" />
                                            </svg>
                                            <span class="text-[11px] font-bold text-slate-400 block mt-2 group-hover:text-brand transition"><?php echo $i; ?></span>
                                        </div>
                                    <?php endfor; ?>
                                    <div class="w-6 border-l-2 border-dashed border-slate-200 mx-2"></div>
                                    <?php for ($i = 71; $i <= 75; $i++): ?>
                                        <div class="text-center group">
                                            <svg width="35" height="45" viewBox="0 0 40 50" class="diente-svg"
                                                onclick="abrirModalDiente(<?php echo $i; ?>)">
                                                <path id="path-diente-<?php echo $i; ?>" d="M5 15 Q20 5 35 15 L30 45 Q20 50 10 45 Z" fill="#f8fafc"
                                                    stroke="#94a3b8" stroke-width="1.5" />
                                            </svg>
                                            <span class="text-[11px] font-bold text-slate-400 block mt-2 group-hover:text-brand transition"><?php echo $i; ?></span>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- HISTORIAL EVOLUTIVO -->
                    <div id="historial_evolutivo" class="bg-white rounded-3xl shadow-md border border-slate-200 border-t-4 border-t-indigo-500 overflow-hidden mb-8">
                        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                            <div>
                                <h3 class="text-lg font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                                    <i data-lucide="clipboard-list" class="w-5 h-5 text-brand"></i> Historial Evolutivo
                                </h3>
                                <p class="text-sm text-slate-500 font-medium mt-1">Bitácora cronológica de tratamientos realizados.</p>
                            </div>
                        </div>

                        <div class="p-6">
                            <?php if ($_SESSION['usuario_rol'] !== 'Recepcionista'): ?>
                                <?php if ($cita_id_activa): ?>
                                <div class="bg-blue-50 border border-blue-100 rounded-2xl p-5 mb-8">
                                    <h4 class="font-bold text-blue-800 mb-3 text-sm uppercase tracking-wider flex items-center gap-2">
                                        <i data-lucide="pen-line" class="w-4 h-4"></i> Añadir Nota Clínica (Sesión Actual)
                                    </h4>
                                    <textarea id="nota_evolucion" rows="3" placeholder="Ej: Profilaxis completa. Se detectó caries superficial en pieza 14, se procede con curación de resina simple. Paciente estable." class="w-full bg-white border-2 border-blue-100 rounded-xl p-4 text-slate-700 outline-none focus:ring-2 focus:ring-teal-200 focus:border-teal-500 transition mb-3"></textarea>
                                    <button onclick="guardarEvolucion(<?php echo $cita_id_activa; ?>)" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl shadow-md transition flex items-center justify-center gap-2 text-sm uppercase tracking-wider">
                                        <i data-lucide="save" class="w-4 h-4"></i> Guardar Evolución y Finalizar Cita
                                    </button>
                                </div>
                                <?php else: ?>
                                <div class="bg-slate-50 border border-slate-100 rounded-2xl p-5 mb-8">
                                    <h4 class="font-bold text-slate-700 mb-3 text-sm uppercase tracking-wider flex items-center gap-2">
                                        <i data-lucide="plus-circle" class="w-4 h-4"></i> Añadir Nota Rápida (Sin cita)
                                    </h4>
                                    <textarea id="nota_evolucion" rows="2" placeholder="Añadir una observación general al paciente..." class="w-full bg-white border-2 border-slate-200 rounded-xl p-3 text-slate-700 outline-none focus:ring-2 focus:ring-teal-200 focus:border-teal-500 transition mb-3 text-sm"></textarea>
                                    <button onclick="guardarEvolucion(null)" class="w-full sm:w-auto bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 px-5 rounded-xl shadow-md transition flex items-center justify-center gap-2 text-xs uppercase tracking-wider">
                                        Guardar Nota Rápida
                                    </button>
                                </div>
                                <?php endif; ?>
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

                    <!-- TRIAJE Y SIGNOS VITALES -->
                    <?php
                    $stmt_signos = $conn->prepare("SELECT * FROM signos_vitales WHERE paciente_id = ? ORDER BY fecha_registro DESC LIMIT 1");
                    if ($stmt_signos) {
                        $stmt_signos->bind_param('i', $paciente_id);
                        $stmt_signos->execute();
                        $res_signos = $stmt_signos->get_result();
                        $ultimo_signo = $res_signos->fetch_assoc() ?: [];
                    } else { $ultimo_signo = []; }
                    ?>
                    <div id="seccion_triaje" class="bg-white rounded-3xl shadow-md border border-slate-200 border-t-4 border-t-rose-500 overflow-hidden mb-8">
                        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                            <h3 class="text-lg font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                                <i data-lucide="activity" class="w-5 h-5 text-rose-500"></i> Triaje / Signos Vitales
                            </h3>
                            <div class="flex gap-2">
                                <button onclick="verHistorialTriaje()" class="bg-slate-100 hover:bg-teal-200 text-slate-700 font-bold py-2 px-4 rounded-xl shadow-sm transition text-xs flex items-center gap-2">
                                    <i data-lucide="history" class="w-4 h-4"></i> Ver Historial
                                </button>
                                <?php if ($_SESSION['usuario_rol'] !== 'Recepcionista'): ?>
                                <button id="btnGuardarTriaje" onclick="guardarTriaje()" class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2 px-4 rounded-xl shadow-sm transition text-xs flex items-center gap-2">
                                    <i data-lucide="save" class="w-4 h-4"></i> Guardar
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
                                <div class="bg-slate-50 p-3 rounded-lg border border-slate-100 focus-within:border-rose-300 focus-within:ring-1 focus-within:ring-rose-200 transition">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">P.A. (mmHg)</label>
                                    <input type="text" id="det_pa" class="w-full bg-transparent border-0 border-b-2 border-slate-200 focus:border-rose-500 focus:ring-0 p-1 font-bold text-slate-800" placeholder="<?php echo htmlspecialchars($ultimo_signo['presion_arterial'] ?? 'Ej: 120/80'); ?>">
                                </div>
                                <div class="bg-slate-50 p-3 rounded-lg border border-slate-100 focus-within:border-rose-300 focus-within:ring-1 focus-within:ring-rose-200 transition">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Pulso (lpm)</label>
                                    <input type="text" id="det_pulso" class="w-full bg-transparent border-0 border-b-2 border-slate-200 focus:border-rose-500 focus:ring-0 p-1 font-bold text-slate-800" placeholder="<?php echo htmlspecialchars($ultimo_signo['pulso'] ?? 'Ej: 75'); ?>">
                                </div>
                                <div class="bg-slate-50 p-3 rounded-lg border border-slate-100 focus-within:border-rose-300 focus-within:ring-1 focus-within:ring-rose-200 transition">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">F.C. (lpm)</label>
                                    <input type="text" id="det_fc" class="w-full bg-transparent border-0 border-b-2 border-slate-200 focus:border-rose-500 focus:ring-0 p-1 font-bold text-slate-800" placeholder="<?php echo htmlspecialchars($ultimo_signo['frecuencia_cardiaca'] ?? 'Ej: 80'); ?>">
                                </div>
                                <div class="bg-slate-50 p-3 rounded-lg border border-slate-100 focus-within:border-rose-300 focus-within:ring-1 focus-within:ring-rose-200 transition">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">F.R. (rpm)</label>
                                    <input type="text" id="det_fr" class="w-full bg-transparent border-0 border-b-2 border-slate-200 focus:border-rose-500 focus:ring-0 p-1 font-bold text-slate-800" placeholder="<?php echo htmlspecialchars($ultimo_signo['frecuencia_resp'] ?? 'Ej: 16'); ?>">
                                </div>
                                <div class="bg-slate-50 p-3 rounded-lg border border-slate-100 focus-within:border-rose-300 focus-within:ring-1 focus-within:ring-rose-200 transition">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Temp. (°C)</label>
                                    <input type="text" id="det_temp" class="w-full bg-transparent border-0 border-b-2 border-slate-200 focus:border-rose-500 focus:ring-0 p-1 font-bold text-slate-800" placeholder="<?php echo htmlspecialchars($ultimo_signo['temperatura'] ?? 'Ej: 36.5'); ?>">
                                </div>
                            </div>
                            <p class="text-[10px] font-medium text-slate-400 mt-3 flex items-center gap-1"><i data-lucide="info" class="w-3 h-3"></i> Escriba en los campos para registrar nuevos valores. Los textos en gris muestran el último registro.</p>
                        </div>
                    </div>

                    <!-- ANTECEDENTES CLINICOS -->
                    <div id="seccion_antecedentes" class="bg-white rounded-3xl shadow-md border border-slate-200 border-t-4 border-t-amber-500 overflow-hidden mb-8">
                        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                            <h3 class="text-lg font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                                <i data-lucide="clipboard-list" class="w-5 h-5 text-indigo-600"></i> Antecedentes Clínicos
                            </h3>
                            <div class="flex gap-2">
                                <?php if ($_SESSION['usuario_rol'] !== 'Recepcionista'): ?>
                                <button id="btnEditarAntecedentes" onclick="toggleEditAntecedentes()" class="bg-slate-100 hover:bg-teal-200 text-slate-700 font-bold py-2 px-4 rounded-xl shadow-sm transition text-xs flex items-center gap-2">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i> Editar
                                </button>
                                <?php endif; ?>
                                <button id="btnGuardarAntecedentes" onclick="guardarAntecedentes()" class="hidden bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-xl shadow-sm transition text-xs flex items-center gap-2">
                                    <i data-lucide="save" class="w-4 h-4"></i> Guardar
                                </button>
                            </div>
                        </div>
                        
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 text-sm">
                                <div class="space-y-4">
                                    <div class="flex flex-col">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="font-bold text-slate-600">¿Padece de alguna enfermedad?</span>
                                            <div class="flex gap-3">
                                                <label class="flex items-center gap-1"><input type="radio" name="padece_enfermedad" value="1" class="antecedentes-radio" disabled <?php echo $paciente['padece_enfermedad'] == '1' ? 'checked' : ''; ?>> SÍ</label>
                                                <label class="flex items-center gap-1"><input type="radio" name="padece_enfermedad" value="0" class="antecedentes-radio" disabled <?php echo $paciente['padece_enfermedad'] == '0' ? 'checked' : ''; ?>> NO</label>
                                            </div>
                                        </div>
                                        <input type="text" id="det_enfermedades_cronicas" class="antecedentes-input w-full bg-transparent border-0 border-b border-slate-200 p-1 text-xs" placeholder="Ninguna" value="<?php echo htmlspecialchars($paciente['enfermedades_cronicas']); ?>" readonly>
                                    </div>
                                    <div class="flex flex-col">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="font-bold text-slate-600">¿Consume medicamentos?</span>
                                            <div class="flex gap-3">
                                                <label class="flex items-center gap-1"><input type="radio" name="consume_medicamentos" value="1" class="antecedentes-radio" disabled <?php echo $paciente['consume_medicamentos'] == '1' ? 'checked' : ''; ?>> SÍ</label>
                                                <label class="flex items-center gap-1"><input type="radio" name="consume_medicamentos" value="0" class="antecedentes-radio" disabled <?php echo $paciente['consume_medicamentos'] == '0' ? 'checked' : ''; ?>> NO</label>
                                            </div>
                                        </div>
                                        <input type="text" id="det_medicamentos_detalle" class="antecedentes-input w-full bg-transparent border-0 border-b border-slate-200 p-1 text-xs" placeholder="Ninguno" value="<?php echo htmlspecialchars($paciente['medicamentos_detalle']); ?>" readonly>
                                    </div>
                                    <div class="flex flex-col">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="font-bold text-slate-600">¿Alérgico a algún medicamento?</span>
                                            <div class="flex gap-3">
                                                <label class="flex items-center gap-1"><input type="radio" name="alergia_medicamentos" value="1" class="antecedentes-radio" disabled <?php echo $paciente['alergia_medicamentos'] == '1' ? 'checked' : ''; ?>> SÍ</label>
                                                <label class="flex items-center gap-1"><input type="radio" name="alergia_medicamentos" value="0" class="antecedentes-radio" disabled <?php echo $paciente['alergia_medicamentos'] == '0' ? 'checked' : ''; ?>> NO</label>
                                            </div>
                                        </div>
                                        <input type="text" id="det_alergia_medicamentos_detalle" class="antecedentes-input w-full bg-transparent border-0 border-b border-slate-200 p-1 text-xs" placeholder="Ninguna" value="<?php echo htmlspecialchars($paciente['alergia_medicamentos_detalle']); ?>" readonly>
                                    </div>
                                    <div class="flex justify-between items-center py-2 border-b border-slate-100">
                                        <span class="font-bold text-slate-600">¿Alérgico a la anestesia?</span>
                                        <div class="flex gap-3">
                                            <label class="flex items-center gap-1"><input type="radio" name="alergia_anestesia" value="1" class="antecedentes-radio" disabled <?php echo $paciente['alergia_anestesia'] == '1' ? 'checked' : ''; ?>> SÍ</label>
                                            <label class="flex items-center gap-1"><input type="radio" name="alergia_anestesia" value="0" class="antecedentes-radio" disabled <?php echo $paciente['alergia_anestesia'] == '0' ? 'checked' : ''; ?>> NO</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <div class="flex flex-col">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="font-bold text-slate-600">¿Antecedentes Familiares?</span>
                                            <div class="flex gap-3">
                                                <label class="flex items-center gap-1"><input type="radio" name="antecedentes_familiares" value="1" class="antecedentes-radio" disabled <?php echo $paciente['antecedentes_familiares'] == '1' ? 'checked' : ''; ?>> SÍ</label>
                                                <label class="flex items-center gap-1"><input type="radio" name="antecedentes_familiares" value="0" class="antecedentes-radio" disabled <?php echo $paciente['antecedentes_familiares'] == '0' ? 'checked' : ''; ?>> NO</label>
                                            </div>
                                        </div>
                                        <input type="text" id="det_antecedentes_familiares_detalle" class="antecedentes-input w-full bg-transparent border-0 border-b border-slate-200 p-1 text-xs" placeholder="Ninguno" value="<?php echo htmlspecialchars($paciente['antecedentes_familiares_detalle']); ?>" readonly>
                                    </div>
                                    <div class="flex justify-between items-center py-2 border-b border-slate-100">
                                        <span class="font-bold text-slate-600">¿Está embarazada?</span>
                                        <div class="flex gap-3">
                                            <label class="flex items-center gap-1"><input type="radio" name="embarazada" value="1" class="antecedentes-radio" disabled <?php echo $paciente['embarazada'] == '1' ? 'checked' : ''; ?>> SÍ</label>
                                            <label class="flex items-center gap-1"><input type="radio" name="embarazada" value="0" class="antecedentes-radio" disabled <?php echo $paciente['embarazada'] == '0' ? 'checked' : ''; ?>> NO</label>
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center py-2 border-b border-slate-100">
                                        <span class="font-bold text-slate-600">¿Le sangran las encías?</span>
                                        <div class="flex gap-3">
                                            <label class="flex items-center gap-1"><input type="radio" name="sangran_encias" value="1" class="antecedentes-radio" disabled <?php echo $paciente['sangran_encias'] == '1' ? 'checked' : ''; ?>> SÍ</label>
                                            <label class="flex items-center gap-1"><input type="radio" name="sangran_encias" value="0" class="antecedentes-radio" disabled <?php echo $paciente['sangran_encias'] == '0' ? 'checked' : ''; ?>> NO</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mt-6">
                                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                                    <label class="block font-bold mb-1 text-slate-600">Última visita al dentista:</label>
                                    <input type="date" id="det_ultima_visita_dentista" class="antecedentes-input w-full bg-transparent border-0 border-b-2 border-slate-200 p-1 text-xs mb-3 font-medium text-slate-800" value="<?php echo $paciente['ultima_visita_dentista']; ?>" readonly>
                                    <label class="block font-bold mb-1 text-slate-600">Motivo:</label>
                                    <input type="text" id="det_ultima_visita_motivo" class="antecedentes-input w-full bg-transparent border-0 border-b-2 border-slate-200 p-1 text-xs font-medium text-slate-800" placeholder="Ej: Chequeo" value="<?php echo htmlspecialchars($paciente['ultima_visita_motivo']); ?>" readonly>
                                </div>
                                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                                    <label class="block font-bold mb-1 text-slate-600">Frecuencia de cepillado:</label>
                                    <input type="text" id="det_frecuencia_cepillado" class="antecedentes-input w-full bg-transparent border-0 border-b-2 border-slate-200 p-1 text-xs mb-3 font-medium text-slate-800" placeholder="Ej: 3 veces al día" value="<?php echo htmlspecialchars($paciente['frecuencia_cepillado']); ?>" readonly>
                                    <label class="block font-bold mb-1 text-slate-600">Utiliza:</label>
                                    <div class="flex gap-3 text-xs flex-wrap mt-2">
                                        <label class="flex items-center gap-1 font-medium"><input type="checkbox" id="det_usa_cepillo" class="antecedentes-radio" disabled <?php echo $paciente['usa_cepillo'] ? 'checked' : ''; ?>> Cepillo</label>
                                        <label class="flex items-center gap-1 font-medium"><input type="checkbox" id="det_usa_pasta_dental" class="antecedentes-radio" disabled <?php echo $paciente['usa_pasta_dental'] ? 'checked' : ''; ?>> Pasta</label>
                                        <label class="flex items-center gap-1 font-medium"><input type="checkbox" id="det_usa_hilo_dental" class="antecedentes-radio" disabled <?php echo $paciente['usa_hilo_dental'] ? 'checked' : ''; ?>> Hilo</label>
                                        <label class="flex items-center gap-1 font-medium"><input type="checkbox" id="det_usa_enjuague" class="antecedentes-radio" disabled <?php echo $paciente['usa_enjuague'] ? 'checked' : ''; ?>> Enjuague</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="seccion_presupuestos" class="bg-white rounded-3xl shadow-md border border-slate-200 border-t-4 border-t-emerald-500 overflow-hidden mb-8">
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
                                <button onclick="abrirModalNuevoPresupuesto()" class="bg-blue-50 text-blue-700 hover:bg-teal-200 font-bold py-2.5 px-4 rounded-xl transition text-xs flex items-center justify-center gap-2">
                                    <i data-lucide="plus" class="w-4 h-4"></i> Venta Directa
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
                                        <button onclick="agregarItemManual()" class="bg-blue-50 hover:bg-teal-200 text-blue-700 font-bold py-2 px-4 rounded-lg transition text-xs flex items-center gap-1">
                                            <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i> Añadir Ítem
                                        </button>
                                        <button onclick="cerrarEditorPresupuesto()" class="bg-slate-50 hover:bg-slate-100 text-slate-500 font-bold py-2 px-4 rounded-lg transition text-xs flex items-center gap-1">
                                            <i data-lucide="x" class="w-3.5 h-3.5"></i> Cerrar
                                        </button>
                                    </div>
                                </div>

                                <!-- Tabla de Ítems -->
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
                                                <th class="text-center p-3 font-bold w-16">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody id="presupuestoItemsBody" class="divide-y divide-slate-100">
                                            <!-- Se llena dinámicamente -->
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
                                        <!-- Sección Pagos y Saldo -->
                                        <div class="border-t border-emerald-200 pt-1 flex justify-between mt-1 text-sm">
                                            <span class="font-medium text-emerald-700">A cuenta:</span>
                                            <span id="presupuestoPagado" class="font-bold text-emerald-700">S/ 0.00</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="font-black text-red-600 uppercase">Saldo:</span>
                                            <span id="presupuestoSaldo" class="font-black text-red-600 text-lg">S/ 0.00</span>
                                        </div>
                                        <div id="presupuestoAdelantoSugerido" class="text-[10px] text-slate-400 text-center mt-1 font-medium border-t border-emerald-100 pt-1">
                                            Adelanto Sugerido (50%): S/ 0.00
                                        </div>
                                    </div>
                                </div>

                                <!-- Historial de Pagos -->
                                <div class="mt-6 border-t border-slate-100 pt-4 hidden" id="seccionPagos">
                                    <div class="flex justify-between items-center mb-3">
                                        <h5 class="font-bold text-slate-700 text-sm flex items-center gap-2"><i data-lucide="receipt" class="w-4 h-4"></i> Historial de Pagos</h5>
                                    </div>
                                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                                        <table class="w-full text-xs text-left">
                                            <thead class="bg-slate-50 text-slate-500 uppercase">
                                                <tr>
                                                    <th class="p-2 font-bold">Fecha</th>
                                                    <th class="p-2 font-bold">Tipo</th>
                                                    <th class="p-2 font-bold">Método</th>
                                                    <th class="p-2 font-bold">Comprobante</th>
                                                    <th class="text-right p-2 font-bold">Monto</th>
                                                </tr>
                                            </thead>
                                            <tbody id="pagosBody" class="divide-y divide-slate-100">
                                                <!-- Se llena por JS -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Acciones del Presupuesto -->
                                <div class="mt-4 flex flex-wrap gap-2 justify-end">
                                    <button onclick="abrirModalPago()" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2.5 px-5 rounded-xl shadow transition text-xs uppercase tracking-wider flex items-center gap-2">
                                        <i data-lucide="banknote" class="w-4 h-4"></i> Registrar Pago
                                    </button>
                                    <button onclick="imprimirPresupuesto()" class="bg-slate-600 hover:bg-slate-700 text-white font-bold py-2.5 px-5 rounded-xl shadow transition text-xs uppercase tracking-wider flex items-center gap-2">
                                        <i data-lucide="printer" class="w-4 h-4"></i> Imprimir
                                    </button>
                                    <button id="btnEnviarPresupuesto" onclick="abrirModalCompartir()" class="bg-indigo-500 hover:bg-indigo-600 text-white font-bold py-2.5 px-5 rounded-xl shadow transition text-xs uppercase tracking-wider flex items-center gap-2">
                                        <i data-lucide="share-2" class="w-4 h-4"></i> Enviar Propuesta
                                    </button>
                                    <button id="btnAprobarPresupuesto" onclick="cambiarEstadoPresupuesto('Aprobado')" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-5 rounded-xl shadow transition text-xs uppercase tracking-wider flex items-center gap-2">
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
                                    <!-- Se llena dinámicamente -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ═══════════ RECETAS MEDICAS ═══════════ -->
                    <?php
                    $stmt_recetas = $conn->prepare("SELECT r.*, u.nombre as doctor_nombre, u.colegiatura FROM recetas r JOIN usuarios u ON r.doctor_id = u.id WHERE r.paciente_id = ? ORDER BY r.fecha DESC");
                    if ($stmt_recetas) {
                        $stmt_recetas->bind_param('i', $paciente_id);
                        $stmt_recetas->execute();
                        $recetas = $stmt_recetas->get_result()->fetch_all(MYSQLI_ASSOC);
                        $stmt_recetas->close();
                    } else { $recetas = []; }
                    ?>
                    <div id="seccion_recetas" class="bg-white rounded-3xl shadow-md border border-slate-200 border-t-4 border-t-blue-500 overflow-hidden mb-8">
                        <div class="p-6 border-b border-slate-100 flex flex-wrap justify-between items-center bg-slate-50/50 gap-4">
                            <div class="flex items-center gap-3">
                                <i data-lucide="file-signature" class="w-5 h-5 text-sky-600"></i>
                                <div>
                                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-widest">Recetas Médicas</h3>
                                    <p class="text-xs text-slate-400">Prescripciones e indicaciones para el paciente</p>
                                </div>
                            </div>
                            <?php if ($_SESSION['usuario_rol'] !== 'Recepcionista'): ?>
                            <button onclick="document.getElementById('modalNuevaReceta').classList.remove('hidden')" class="bg-sky-600 hover:bg-sky-700 text-white font-bold py-2.5 px-5 rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition flex items-center gap-2 text-sm">
                                <i data-lucide="plus" class="w-4 h-4"></i> Nueva Receta
                            </button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="p-6">
                            <?php if (empty($recetas)): ?>
                            <div class="text-center py-10">
                                <i data-lucide="file-text" class="w-12 h-12 text-slate-200 mx-auto mb-3"></i>
                                <p class="text-slate-400 font-medium">No hay recetas registradas.</p>
                            </div>
                            <?php else: ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <?php foreach ($recetas as $receta): ?>
                                <div class="bg-white border border-slate-200 rounded-2xl p-5 hover:shadow-md transition group relative overflow-hidden">
                                    <div class="absolute top-0 right-0 w-16 h-16 bg-sky-50 rounded-bl-full -z-10 transition group-hover:bg-sky-100"></div>
                                    <div class="flex justify-between items-start mb-3">
                                        <div class="bg-slate-100 text-slate-600 text-[10px] font-black px-2.5 py-1 rounded-md uppercase tracking-wider">
                                            <?php echo date('d/m/Y', strtotime($receta['fecha'])); ?>
                                        </div>
                                        <div class="flex gap-1">
                                            <button onclick="imprimirReceta(<?php echo $receta['id']; ?>)" class="text-sky-600 bg-teal-50 p-1.5 rounded-lg hover:bg-teal-200 hover:text-teal-700 transition" title="Imprimir">
                                                <i data-lucide="printer" class="w-4 h-4"></i>
                                            </button>
                                            <?php if ($_SESSION['usuario_rol'] !== 'Recepcionista'): ?>
                                            <button type="button" onclick="eliminarReceta(<?php echo $receta['id']; ?>)" class="text-red-500 bg-red-50 p-1.5 rounded-lg hover:bg-red-500 hover:text-white transition" title="Eliminar">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="text-sm text-slate-700 line-clamp-3 mb-4 min-h-[3rem] whitespace-pre-wrap leading-relaxed"><?php echo htmlspecialchars($receta['contenido']); ?></div>
                                    
                                    <div class="flex items-center gap-2 pt-3 border-t border-slate-100">
                                        <div class="w-6 h-6 rounded-full bg-slate-200 flex items-center justify-center text-[10px] font-bold text-slate-500">Dr</div>
                                        <p class="text-[10px] font-bold text-slate-500 line-clamp-1"><?php echo htmlspecialchars($receta['doctor_nombre']); ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- ARCHIVOS CLINICOS -->
                    <div id="seccion_archivos" class="bg-white rounded-3xl shadow-md border border-slate-200 border-t-4 border-t-purple-500 overflow-hidden mb-8">
                        <div class="p-6 border-b border-slate-100 flex flex-wrap justify-between items-center bg-slate-50/50 gap-4">
                            <div>
                                <h3 class="text-lg font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                                    <i data-lucide="folder-open" class="w-5 h-5 text-indigo-600"></i> Archivos Clínicos
                                </h3>
                                <p class="text-sm text-slate-500 font-medium mt-1">Radiografías, fotos intraorales y documentos.</p>
                            </div>
                            <div class="flex gap-2 w-full md:w-auto">
                                <a href="generar_consentimiento.php?id=<?php echo $paciente_id; ?>" target="_blank" class="flex-1 md:flex-none bg-white border-2 border-slate-200 text-slate-700 hover:bg-teal-100 font-bold py-2.5 px-4 rounded-xl shadow-sm transition flex items-center justify-center gap-2 text-xs uppercase tracking-wider">
                                    <i data-lucide="printer" class="w-4 h-4"></i> Consentimiento
                                </a>
                                <button onclick="abrirModalSubirArchivo()" class="flex-1 md:flex-none bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-5 rounded-xl shadow-md transition flex items-center justify-center gap-2 text-xs uppercase tracking-wider">
                                    <i data-lucide="upload-cloud" class="w-4 h-4"></i> Subir Archivo
                                </button>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="flex gap-2 mb-6 overflow-x-auto pb-2" id="archivos_filters">
                                <button onclick="filtrarArchivos('Todos')" class="btn-filter px-4 py-2 text-sm font-bold rounded-lg bg-indigo-50 text-indigo-700 shadow-sm border border-indigo-100 whitespace-nowrap">Todos</button>
                                <button onclick="filtrarArchivos('Radiografía')" class="btn-filter px-4 py-2 text-sm font-bold rounded-lg text-slate-500 hover:text-slate-800 transition whitespace-nowrap">Radiografías</button>
                                <button onclick="filtrarArchivos('Foto Intraoral')" class="btn-filter px-4 py-2 text-sm font-bold rounded-lg text-slate-500 hover:text-slate-800 transition whitespace-nowrap">Fotos Intraorales</button>
                                <button onclick="filtrarArchivos('Documento')" class="btn-filter px-4 py-2 text-sm font-bold rounded-lg text-slate-500 hover:text-slate-800 transition whitespace-nowrap">Documentos</button>
                            </div>
                            
                            <div id="listaArchivosGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <!-- Cards dinámicas aquí -->
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    


    </main>

    <!-- Modal Nueva Receta -->
    <div id="modalNuevaReceta" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl w-full max-w-2xl overflow-hidden shadow-2xl animate-fade-in-up">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-sky-100 text-sky-600 rounded-xl flex items-center justify-center"><i data-lucide="file-signature" class="w-5 h-5"></i></div>
                    <h3 class="font-black text-slate-800 text-lg">Nueva Receta Médica</h3>
                </div>
                <button onclick="document.getElementById('modalNuevaReceta').classList.add('hidden')" class="text-slate-400 hover:bg-slate-100 hover:text-slate-600 p-2 rounded-xl transition"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="p-6">
                <form id="formNuevaReceta" onsubmit="guardarNuevaReceta(event)">
                    <div class="mb-5">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Rp. / Indicaciones</label>
                        <textarea id="nueva_receta_contenido" required rows="10" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-teal-200 focus:border-teal-500 transition font-medium text-slate-700 resize-none" placeholder="Escriba aquí todo su texto para la receta...&#10;&#10;Ejemplo:&#10;1. Paracetamol 500mg # 10&#10;   1 Tab V.O C/8 Horas por 3 días"></textarea>
                        <p class="text-[10px] text-slate-400 mt-2">Puede usar saltos de línea libremente. Todo lo que escriba aquí se imprimirá directamente en la receta, sin divisiones.</p>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="button" onclick="document.getElementById('modalNuevaReceta').classList.add('hidden')" class="px-5 py-2.5 text-slate-500 font-bold hover:bg-teal-100 rounded-xl transition">Cancelar</button>
                        <button type="submit" id="btnGuardarNuevaReceta" class="bg-sky-600 hover:bg-sky-700 text-white font-bold px-6 py-2.5 rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition flex items-center gap-2">
                            <i data-lucide="save" class="w-4 h-4"></i> Guardar Receta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Registrar Pago -->
    <div id="modalRegistrarPago" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md transform scale-95 transition-transform duration-300 overflow-hidden">
            <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                <h3 class="font-black text-slate-800 flex items-center gap-2"><i data-lucide="banknote" class="w-5 h-5 text-teal-600"></i> Registrar Pago</h3>
                <button onclick="cerrarModalPago()" class="text-slate-400 hover:text-slate-600 transition"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="p-5 space-y-4">
                <div class="flex justify-between text-sm bg-slate-50 p-3 rounded-lg border border-slate-100">
                    <span class="font-bold text-slate-600">Saldo Pendiente:</span>
                    <span id="pagoSaldoPendiente" class="font-black text-red-600">S/ 0.00</span>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Monto a Pagar (S/)</label>
                    <input type="number" id="pagoMonto" step="0.01" min="0.01" class="w-full border-2 border-slate-200 rounded-xl p-2.5 text-lg font-bold text-slate-800 outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-200">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Tipo de Pago</label>
                        <select id="pagoTipo" class="w-full border-2 border-slate-200 rounded-xl p-2.5 text-sm font-bold text-slate-700 outline-none focus:border-teal-500">
                            <option value="Adelanto">Adelanto</option>
                            <option value="Parcial" selected>Pago Parcial</option>
                            <option value="Saldo Final">Saldo Final</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Método</label>
                        <select id="pagoMetodo" class="w-full border-2 border-slate-200 rounded-xl p-2.5 text-sm font-bold text-slate-700 outline-none focus:border-teal-500">
                            <option value="Efectivo">Efectivo</option>
                            <option value="Tarjeta">Tarjeta</option>
                            <option value="Transferencia">Transferencia</option>
                            <option value="Yape/Plin">Yape/Plin</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Comprobante</label>
                        <select id="pagoComprobante" class="w-full border-2 border-slate-200 rounded-xl p-2.5 text-sm font-bold text-slate-700 outline-none focus:border-teal-500">
                            <option value="Boleta">Boleta</option>
                            <option value="Factura">Factura</option>
                            <option value="Ninguno">Ninguno</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Notas Adicionales</label>
                    <input type="text" id="pagoNotas" placeholder="Ej: Pago adelantado para inicio de brackets..." class="w-full border-2 border-slate-200 rounded-xl p-2 text-sm text-slate-700 outline-none focus:border-teal-500">
                </div>
            </div>
            <div class="p-5 border-t border-slate-100 bg-slate-50 flex gap-3 justify-end">
                <button onclick="cerrarModalPago()" class="px-5 py-2.5 rounded-xl font-bold text-slate-600 hover:bg-teal-200 transition text-sm">Cancelar</button>
                <button onclick="confirmarRegistrarPago()" class="px-5 py-2.5 rounded-xl font-bold text-white bg-teal-600 hover:bg-teal-700 shadow-md transition text-sm">Registrar Pago</button>
            </div>
        </div>
    </div>

    <!-- Modal Selección de Hallazgos para Presupuesto -->
    <div id="modalImportarPresupuesto" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md transform scale-95 transition-all duration-300 overflow-hidden">
            <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                <h3 class="font-black text-slate-800 flex items-center gap-2"><i data-lucide="file-check" class="w-5 h-5 text-teal-600"></i> Generar Presupuesto</h3>
                <button onclick="cerrarModalImportar()" class="text-slate-400 hover:text-red-500 transition"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            
            <div class="p-5 space-y-4">
                <p class="text-xs text-slate-500 font-medium">Seleccione los hallazgos del odontograma que desea incluir en el nuevo presupuesto:</p>
                
                <!-- Opciones de Selección Rápida -->
                <div class="flex gap-2">
                    <button type="button" onclick="seleccionarImportar('todo')" class="flex-1 px-3 py-2 bg-slate-100 text-slate-700 rounded-xl text-[10px] font-bold hover:bg-slate-200 transition">Importar Todo</button>
                    <button type="button" id="btnImportarRecientes" onclick="seleccionarImportar('recientes')" class="flex-1 px-3 py-2 bg-teal-50 text-teal-700 rounded-xl text-[10px] font-bold hover:bg-teal-100 transition">Solo Recientes</button>
                    <button type="button" onclick="seleccionarImportar('ninguno')" class="flex-1 px-3 py-2 bg-slate-100 text-slate-700 rounded-xl text-[10px] font-bold hover:bg-slate-200 transition">Desmarcar Todos</button>
                </div>
                
                <!-- Lista de Hallazgos con Checkboxes -->
                <div class="border border-slate-150 rounded-xl max-h-56 overflow-y-auto bg-slate-50/50 p-2 shadow-inner">
                    <div id="listaImportarCheckboxes" class="divide-y divide-slate-100">
                        <!-- Dinámico -->
                    </div>
                </div>
            </div>
            <div class="p-5 border-t border-slate-100 bg-slate-50 flex gap-3 justify-end">
                <button type="button" onclick="cerrarModalImportar()" class="px-5 py-2.5 rounded-xl font-bold text-slate-600 hover:bg-teal-200 transition text-sm">Cancelar</button>
                <button type="button" onclick="enviarPresupuestoSeleccionado()" class="px-5 py-2.5 rounded-xl font-bold text-white bg-teal-600 hover:bg-teal-700 shadow-md transition flex items-center justify-center gap-2 text-sm">
                    <i data-lucide="file-plus" class="w-4 h-4"></i> Crear Presupuesto
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Compartir Presupuesto -->
    <div id="modalCompartirPresupuesto" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm transform scale-95 transition-transform duration-300 overflow-hidden">
            <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                <h3 class="font-black text-slate-800 flex items-center gap-2"><i data-lucide="share-2" class="w-5 h-5 text-indigo-600"></i> Enviar Propuesta</h3>
                <button onclick="cerrarModalCompartir()" class="text-slate-400 hover:text-red-500 transition"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="p-5 flex flex-col gap-3">
                <p class="text-xs text-slate-500 font-medium text-center mb-2">Seleccione el medio para enviar al paciente. El estado pasará automáticamente a "Enviado".</p>
                <button onclick="enviarPorWhatsApp()" class="w-full bg-[#25D366] hover:bg-[#128C7E] text-white font-bold py-3.5 px-4 rounded-xl shadow-md transition flex items-center justify-center gap-3">
                    <i data-lucide="message-circle" class="w-5 h-5"></i> Enviar por WhatsApp
                </button>
                <button onclick="enviarPorEmail()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 px-4 rounded-xl shadow-md transition flex items-center justify-center gap-3">
                    <i data-lucide="mail" class="w-5 h-5"></i> Enviar por Email
                </button>
            </div>
            <div class="p-4 border-t border-slate-100 bg-slate-50">
                <button onclick="cerrarModalCompartir()" class="w-full px-5 py-2.5 rounded-xl font-bold text-slate-600 hover:bg-teal-200 transition text-sm">Cancelar</button>
            </div>
        </div>
    </div>

    <!-- Modal Agregar Ítem al Presupuesto -->
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
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Seleccionar del Catálogo</label>
                    <select id="itemCatalogoSelect" onchange="seleccionarCatalogo()" class="w-full bg-white border-2 border-slate-200 rounded-xl p-3 text-sm font-medium outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="">-- Seleccionar tratamiento --</option>
                    </select>
                </div>
                <div class="border-t border-slate-100 pt-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Descripción</label>
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
                <button onclick="cerrarModalItem()" class="px-5 py-2.5 bg-white border border-slate-200 text-slate-600 font-bold rounded-xl text-sm hover:bg-teal-100 transition">Cancelar</button>
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
                    Registro Clínico Detallado
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

                    <div id="three-container" title="Arrastra para girar 360°, haz clic para marcar cara"></div>

                    <div
                        class="absolute bottom-4 left-4 right-4 bg-white/70 backdrop-blur-sm p-3 rounded-xl border border-white/50 text-center shadow-sm pointer-events-none z-10">
                        <p
                            class="text-[11px] font-black text-teal-900 uppercase tracking-widest flex items-center justify-center gap-2">
                            <i data-lucide="rotate-3d" class="w-4 h-4"></i> Vista 3D Interactiva 360°
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
                            <select id="estadoDiente" class="w-full bg-white border-2 border-slate-200 rounded-xl p-3 font-bold text-slate-700 outline-none focus:ring-2 focus:ring-brand focus:border-brand shadow-sm transition">
                                <optgroup label="Pendientes / Patologías">
                                    <option value="caries">Caries (Rojo)</option>
                                    <option value="extraccion_indicada">Extracción Indicada (Rojo Oscuro)</option>
                                    <option value="restauracion_defectuosa">Restauración Defectuosa (Naranja)</option>
                                    <option value="fractura">Fractura (Rosa/Rojo)</option>
                                </optgroup>
                                <optgroup label="Sanos / Existentes">
                                    <option value="resina">Resina / Amalgama (Azul)</option>
                                    <option value="endodoncia">Endodoncia (Morado)</option>
                                    <option value="corona">Corona / Incrustación (Celeste)</option>
                                    <option value="implante">Implante (Gris Oscuro)</option>
                                    <option value="ausente">Pieza Ausente (Gris Claro)</option>
                                    <option value="sellante">Sellante (Verde)</option>
                                </optgroup>
                                <optgroup label="Acción">
                                    <option value="">Limpiar / Normal</option>
                                </optgroup>
                            </select>
                            <input type="text" id="notasDiente" placeholder="Notas opcionales (ej: Caries profunda)"
                                class="w-full bg-white border-2 border-slate-200 rounded-xl p-3 text-sm font-medium outline-none focus:ring-2 focus:ring-brand focus:border-brand shadow-sm transition">
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col items-center">
                        <label
                            class="block text-xs font-black text-slate-500 uppercase mb-5 tracking-widest text-center">2.
                            Visualización Anatómica (Clic para marcar manualmente)</label>

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

                    <?php if ($_SESSION['usuario_rol'] !== 'Recepcionista'): ?>
                    <button onclick="guardarHallazgos()"
                        class="bg-brand hover:bg-teal-800 text-white font-bold py-4 rounded-xl shadow-lg transition transform hover:scale-[1.02] flex items-center justify-center gap-3">
                        <i data-lucide="save" class="w-6 h-6"></i> Guardar Hallazgos en Historial
                    </button>
                    <?php endif; ?>
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

                // --- MAGIA PARA CENTRAR Y ENFOCAR EL MODELO AUTOMÁTICAMENTE ---

                // 1. Calculamos la "caja invisible" que envuelve a tu modelo y su centro real
                const box = new THREE.Box3().setFromObject(toothModel);
                const center = box.getCenter(new THREE.Vector3());
                const size = box.getSize(new THREE.Vector3());

                // 2. Forzamos al diente a moverse al centro absoluto (0,0,0) de tu pantalla
                toothModel.position.sub(center);

                // 3. Calculamos la dimensión más grande del diente para ajustar la cámara
                const maxDim = Math.max(size.x, size.y, size.z);

                // 4. Ubicamos la cámara a la distancia perfecta (ni muy cerca ni muy lejos)
                camera.position.set(0, maxDim * 0.5, maxDim * 2.5);

                // 5. Le decimos a los controles que el eje de rotación sea el centro absoluto
                controls.target.set(0, 0, 0);

                // 6. Ponemos límites al zoom para que el doctor no se "meta" dentro del diente ni lo pierda de vista
                controls.minDistance = maxDim * 1.2; // Límite para acercarse
                controls.maxDistance = maxDim * 4;   // Límite para alejarse

                controls.update();
                // -------------------------------------------------------------

                // Agregamos luces adicionales para que se vean bien los detalles anatómicos
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

            // Función para crear cada "cara" del diente como un bloque independiente
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

                // Obtenemos el color seleccionado en el menú desplegable
                const estado = document.getElementById('estadoDiente').value;
                const colorHex = coloresTratamiento[estado] || coloresTratamiento['normal'];

                // MAGIA PARA PINTAR EL MODELO IMPORTADO:
                // Temporalmente desactivado a petición del usuario hasta tener el modelo correcto
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
            
            // 1. Intentar pintar solo la malla específica (Si el modelo GLTF está dividido)
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
        let hallazgosModificadosRecientemente = [];
        
        const modal = document.getElementById('modalDiente');
        const numDienteTitulo = document.getElementById('numDienteTitulo');

        function renderizarGrilla() {
            // Limpiar todos los dientes primero
            document.querySelectorAll('.diente-svg path').forEach(path => {
                path.classList.remove('estado-caries', 'estado-extraccion_indicada', 'estado-restauracion_defectuosa', 'estado-fractura', 'estado-resina', 'estado-endodoncia', 'estado-corona', 'estado-implante', 'estado-ausente', 'estado-sellante');
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
                    else if (estados.includes('extraccion_indicada')) estadoFinal = 'extraccion_indicada';
                    else if (estados.includes('fractura')) estadoFinal = 'fractura';
                    else if (estados.includes('corona')) estadoFinal = 'corona';
                    else if (estados.includes('endodoncia')) estadoFinal = 'endodoncia';
                    else if (estados.includes('resina')) estadoFinal = 'resina';
                    else if (estados.includes('implante')) estadoFinal = 'implante';
                    else if (estados.includes('sellante')) estadoFinal = 'sellante';
                    else if (estados.includes('restauracion_defectuosa')) estadoFinal = 'restauracion_defectuosa';
                    
                    if (estadoFinal) {
                        pathEl.classList.add(`estado-${estadoFinal}`);
                    }
                    
                    const nombresEstados = {
                        'caries': 'Caries', 'extraccion_indicada': 'Extracción Indicada',
                        'restauracion_defectuosa': 'Restauración Defectuosa', 'fractura': 'Fractura',
                        'endodoncia': 'Endodoncia', 'resina': 'Resina/Amalgama',
                        'corona': 'Corona/Incrustación', 'implante': 'Implante',
                        'sellante': 'Sellante', 'ausente': 'Ausente'
                    };
                    
                    let titulos = [...new Set(estados.filter(e => e !== '').map(e => nombresEstados[e] || e))];
                    let tooltipText = titulos.join(', ');
                    
                    const notas = notasPorDiente[diente];
                    if (notas && notas.length > 0 && notas[0] !== "") {
                        tooltipText += (tooltipText ? '\n' : '') + 'Notas: ' + notas.join(' | ');
                    }
                    
                    if (tooltipText) {
                        pathEl.parentElement.parentElement.setAttribute('title', tooltipText);
                        pathEl.parentElement.parentElement.classList.add('cursor-help');
                    } else {
                        pathEl.parentElement.parentElement.removeAttribute('title');
                        pathEl.parentElement.parentElement.classList.remove('cursor-help');
                    }
                }
            }
        }

        // Ejecutar al cargar la página
        renderizarGrilla();

        function abrirModalDiente(num) {
            numDienteTitulo.innerText = num;

            // Limpiar el mapa anatómico 2D y resetear formulario
            document.querySelectorAll('.cara-diente-2d').forEach(el => {
                el.classList.remove('estado-caries', 'estado-extraccion_indicada', 'estado-restauracion_defectuosa', 'estado-fractura', 'estado-resina', 'estado-endodoncia', 'estado-corona', 'estado-implante', 'estado-ausente', 'estado-sellante');
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
            else if (estados.includes('extraccion_indicada')) estado3D = 'extraccion_indicada';
            else if (estados.includes('fractura')) estado3D = 'fractura';
            else if (estados.includes('restauracion_defectuosa')) estado3D = 'restauracion_defectuosa';
            else if (estados.includes('corona')) estado3D = 'corona';
            else if (estados.includes('endodoncia')) estado3D = 'endodoncia';
            else if (estados.includes('resina')) estado3D = 'resina';
            else if (estados.includes('implante')) estado3D = 'implante';
            else if (estados.includes('sellante')) estado3D = 'sellante';
            else if (estados.includes('ausente')) estado3D = 'ausente';

            hallazgosDiente.forEach(h => {
                if(h.estado) {
                    const el = document.querySelector(`.cara-diente-2d[data-cara="${h.cara_afectada}"]`);
                    if(el) el.classList.add(`estado-${h.estado}`);
                }
            });

            // Pintar el modelo 3D según el estado guardado
            if(window.pintarDiente3D) {
                // Si el modelo 3D no está dividido, esto pintará todo. 
                // Si sí lo está, no pintará nada porque no le pasamos nombreCara. 
                // Así que, de manera inteligente, repintamos las caras específicas si las hay.
                window.pintarDiente3D(estado3D); // Pinta bloque entero por defecto
                
                hallazgosDiente.forEach(h => {
                    if (h.estado) window.pintarDiente3D(h.estado, h.cara_afectada);
                });
            }

            // Mostramos modal con animación
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

            elemento.classList.remove('estado-caries', 'estado-extraccion_indicada', 'estado-restauracion_defectuosa', 'estado-fractura', 'estado-resina', 'estado-endodoncia', 'estado-corona', 'estado-implante', 'estado-ausente', 'estado-sellante');
            if (estado !== "") {
                elemento.classList.add('estado-' + estado);
            }
            
            // Sincronizar el modelo 3D cuando el usuario interactúa con el 2D
            if (!isFrom3D && window.pintarDiente3D) {
                window.pintarDiente3D(estado || 'normal', nombreCara);
                if (!estado) {
                    // Si limpiamos una cara, podríamos necesitar forzar un repintado general si es un bloque sólido
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
                else if (cara.classList.contains('estado-extraccion_indicada')) estadoCara = 'extraccion_indicada';
                else if (cara.classList.contains('estado-restauracion_defectuosa')) estadoCara = 'restauracion_defectuosa';
                else if (cara.classList.contains('estado-fractura')) estadoCara = 'fractura';
                else if (cara.classList.contains('estado-resina')) estadoCara = 'resina';
                else if (cara.classList.contains('estado-endodoncia')) estadoCara = 'endodoncia';
                else if (cara.classList.contains('estado-corona')) estadoCara = 'corona';
                else if (cara.classList.contains('estado-implante')) estadoCara = 'implante';
                else if (cara.classList.contains('estado-ausente')) estadoCara = 'ausente';
                else if (cara.classList.contains('estado-sellante')) estadoCara = 'sellante';
                
                // Vamos a enviar un UPDATE para cada cara (incluso vacías para limpiar)
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
                    else if (cara.classList.contains('estado-extraccion_indicada')) estadoCara = 'extraccion_indicada';
                    else if (cara.classList.contains('estado-restauracion_defectuosa')) estadoCara = 'restauracion_defectuosa';
                    else if (cara.classList.contains('estado-fractura')) estadoCara = 'fractura';
                    else if (cara.classList.contains('estado-resina')) estadoCara = 'resina';
                    else if (cara.classList.contains('estado-endodoncia')) estadoCara = 'endodoncia';
                    else if (cara.classList.contains('estado-corona')) estadoCara = 'corona';
                    else if (cara.classList.contains('estado-implante')) estadoCara = 'implante';
                    else if (cara.classList.contains('estado-ausente')) estadoCara = 'ausente';
                    else if (cara.classList.contains('estado-sellante')) estadoCara = 'sellante';

                    // Eliminar hallazgos antiguos de esta cara
                    hallazgosOdontograma = hallazgosOdontograma.filter(h => !(h.diente_numero == diente && h.cara_afectada == nombreCara));
                    hallazgosModificadosRecientemente = hallazgosModificadosRecientemente.filter(h => !(h.diente_numero == diente && h.cara_afectada == nombreCara));
                    
                    // Añadir si hay nuevo
                    if (estadoCara) {
                        const nuevoH = {
                            diente_numero: parseInt(diente),
                            cara_afectada: nombreCara,
                            estado: estadoCara,
                            notas: notasGlobales
                        };
                        hallazgosOdontograma.push(nuevoH);
                        hallazgosModificadosRecientemente.push(nuevoH);
                    }
                });

                renderizarGrilla();
                cerrarModalDiente();
                alert('Hallazgos guardados exitosamente.');
                
            } catch (error) {
                console.error("Error al guardar:", error);
                alert("Ocurrió un error al guardar los hallazgos.");
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
                        alert('Evolución guardada y cita completada con éxito.');
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
                alert('Error de conexión.');
                btn.innerHTML = textOriginal;
                btn.disabled = false;
                lucide.createIcons();
            }
        }
    </script>

    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

    <script>
        // --- MÓDULO DE PRESUPUESTOS ---
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
            } catch(e) { console.error('Error cargando catálogo:', e); }
        }

        let tomSelectInstance = null;

        function poblarSelectCatalogo() {
            const select = document.getElementById('itemCatalogoSelect');
            if (!select) return;
            
            if (tomSelectInstance) {
                tomSelectInstance.destroy();
                tomSelectInstance = null;
            }

            select.innerHTML = '<option value="">-- Seleccionar tratamiento o insumo --</option>';

            tomSelectInstance = new TomSelect('#itemCatalogoSelect', {
                create: false,
                maxOptions: 200,
                lockOptgroupOrder: true,
                placeholder: "Buscar tratamiento o insumo...",
                render: {
                    optgroup_header: function(data, escape) {
                        return '<div style="padding:6px 10px;font-weight:800;font-size:11px;text-transform:uppercase;color:#475569;background:#f1f5f9;border-bottom:1px solid #e2e8f0;">' + escape(data.label) + '</div>';
                    },
                    option: function(data, escape) {
                        return '<div style="padding:8px 12px;font-size:13px;border-bottom:1px solid #f8fafc;">' + escape(data.text) + '</div>';
                    }
                },
                onChange: function(value) {
                    if (!value) return;
                    const item = catalogoTratamientos.find(t => String(t.id) === String(value));
                    if (item) {
                        document.getElementById('itemDescripcion').value = item.nombre;
                        document.getElementById('itemPrecio').value = parseFloat(item.precio_base).toFixed(2);
                    }
                }
            });

            // Build groups and options programmatically
            const categorias = [];
            catalogoTratamientos.forEach(t => {
                if (!categorias.includes(t.categoria)) {
                    categorias.push(t.categoria);
                    tomSelectInstance.addOptionGroup(t.categoria, { label: t.categoria });
                }
                tomSelectInstance.addOption({
                    value: String(t.id),
                    text: t.nombre + ' - S/ ' + parseFloat(t.precio_base).toFixed(2),
                    optgroup: t.categoria,
                    $order: categorias.indexOf(t.categoria) * 1000 + catalogoTratamientos.indexOf(t)
                });
            });
            tomSelectInstance.refreshOptions(false);
        }

        function seleccionarCatalogo() {
            // Handled inline via onChange above
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

            lista.innerHTML = presupuestos.map(p => {
                const total = parseFloat(p.total);
                const pagado = parseFloat(p.monto_pagado || 0);
                const saldo = total - pagado;
                
                return `
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between bg-slate-50 rounded-xl p-4 hover:bg-slate-100 transition cursor-pointer group gap-4" onclick="abrirPresupuesto(${p.id})">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center shrink-0">
                            <i data-lucide="file-text" class="w-5 h-5 text-emerald-600"></i>
                        </div>
                        <div>
                            <h5 class="font-bold text-slate-800 text-sm">Presupuesto #${p.id}</h5>
                            <p class="text-xs text-slate-500">${formatearFecha(p.fecha_emision)} · ${p.doctor_nombre || 'Sin doctor'}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap sm:flex-nowrap items-center gap-3 w-full sm:w-auto justify-end">
                        <span class="text-xs font-bold px-3 py-1 rounded-full ${badgeColors[p.estado] || 'bg-slate-100 text-slate-500'}">${p.estado}</span>
                        
                        <div class="text-right ml-2 mr-2">
                            <div class="font-black text-emerald-700 text-sm leading-tight">Total: S/ ${total.toFixed(2)}</div>
                            ${saldo > 0 ? `<div class="font-black text-red-500 text-xs leading-tight">Saldo: S/ ${saldo.toFixed(2)}</div>` : `<div class="font-bold text-teal-600 text-[10px] uppercase">Pagado</div>`}
                        </div>

                        ${saldo > 0 ? `
                        <button onclick="event.stopPropagation(); abrirPresupuesto(${p.id}).then(() => setTimeout(abrirModalPago, 300))" 
                            class="bg-teal-600 hover:bg-teal-700 text-white font-bold p-2 rounded-lg shadow transition flex items-center gap-1 text-xs">
                            <i data-lucide="banknote" class="w-4 h-4"></i> Pagar
                        </button>
                        ` : ''}
                        
                        <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400 group-hover:text-slate-600 transition hidden sm:block"></i>
                    </div>
                </div>
            `}).join('');
            lucide.createIcons();
        }

        // --- Generación automática ---
        function generarPresupuestoDesdeOdontograma() {
            abrirModalImportar();
        }

        function cerrarModalImportar() {
            const modal = document.getElementById('modalImportarPresupuesto');
            modal.querySelector('div').classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }, 300);
        }

        function abrirModalImportar() {
            if (!hallazgosOdontograma || hallazgosOdontograma.length === 0) {
                alert('No hay hallazgos en el odontograma. Marque al menos un diente antes de generar.');
                return;
            }
            const activeHallazgos = hallazgosOdontograma.filter(h => h.estado && h.estado !== '');
            if (activeHallazgos.length === 0) {
                alert('No hay hallazgos activos en el odontograma.');
                return;
            }
            
            // Agrupar por diente y por estado
            const agrupados = {};
            activeHallazgos.forEach(h => {
                const clave = h.diente_numero + '_' + h.estado;
                if (!agrupados[clave]) {
                    agrupados[clave] = {
                        diente_numero: h.diente_numero,
                        estado: h.estado,
                        caras: [],
                        notas: h.notas,
                        es_reciente: false
                    };
                }
                agrupados[clave].caras.push(h.cara_afectada);
                
                const esReciente = hallazgosModificadosRecientemente.some(r => r.diente_numero === h.diente_numero && r.cara_afectada === h.cara_afectada && r.estado === h.estado);
                if (esReciente) {
                    agrupados[clave].es_reciente = true;
                }
            });
            
            const nombresEstados = {
                'caries': 'Caries', 'extraccion_indicada': 'Extracción Indicada',
                'restauracion_defectuosa': 'Restauración Defectuosa', 'fractura': 'Fractura',
                'endodoncia': 'Endodoncia', 'resina': 'Resina/Amalgama',
                'corona': 'Corona/Incrustación', 'implante': 'Implante',
                'sellante': 'Sellante', 'ausente': 'Ausente'
            };
            
            const container = document.getElementById('listaImportarCheckboxes');
            container.innerHTML = '';
            
            Object.values(agrupados).forEach(h => {
                const labelEstado = nombresEstados[h.estado] || h.estado;
                const carasTexto = h.caras.join(', ');
                
                const label = document.createElement('label');
                label.className = "flex items-center gap-3 p-3 hover:bg-white rounded-xl transition cursor-pointer select-none border-b border-slate-100 last:border-0";
                label.innerHTML = `
                    <input type="checkbox" data-diente="${h.diente_numero}" data-estado="${h.estado}" data-caras='${JSON.stringify(h.caras)}' data-reciente="${h.es_reciente ? '1' : '0'}" checked class="chk-import-hallazgo w-4 h-4 text-brand bg-slate-100 border-slate-300 rounded focus:ring-brand">
                    <div class="flex-1 text-xs font-semibold text-slate-700">
                        Diente #${h.diente_numero} - <span class="${h.estado === 'caries' || h.estado === 'extraccion_indicada' || h.estado === 'fractura' ? 'text-red-600' : 'text-brand'}">${labelEstado}</span>
                        <span class="text-[10px] text-slate-400 block font-bold uppercase mt-0.5">Caras: ${carasTexto} ${h.es_reciente ? '<span class="text-teal-600 bg-teal-50 px-1 py-0.5 rounded text-[8px] font-black lowercase ml-1">reciente</span>' : ''}</span>
                    </div>
                `;
                container.appendChild(label);
            });
            
            // Habilitar o deshabilitar botón de recientes
            const btnRecientes = document.getElementById('btnImportarRecientes');
            if (hallazgosModificadosRecientemente.length === 0) {
                btnRecientes.classList.add('opacity-50', 'pointer-events-none');
            } else {
                btnRecientes.classList.remove('opacity-50', 'pointer-events-none');
            }
            
            const modal = document.getElementById('modalImportarPresupuesto');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => modal.querySelector('div').classList.remove('scale-95'), 10);
            lucide.createIcons();
        }

        function seleccionarImportar(tipo) {
            document.querySelectorAll('.chk-import-hallazgo').forEach(chk => {
                if (tipo === 'todo') {
                    chk.checked = true;
                } else if (tipo === 'ninguno') {
                    chk.checked = false;
                } else if (tipo === 'recientes') {
                    chk.checked = (chk.getAttribute('data-reciente') === '1');
                }
            });
        }

        async function enviarPresupuestoSeleccionado() {
            const selectedHallazgos = [];
            document.querySelectorAll('.chk-import-hallazgo:checked').forEach(chk => {
                const diente = parseInt(chk.getAttribute('data-diente'));
                const estado = chk.getAttribute('data-estado');
                const caras = JSON.parse(chk.getAttribute('data-caras'));
                
                caras.forEach(cara => {
                    selectedHallazgos.push({
                        diente_numero: diente,
                        estado: estado,
                        cara_afectada: cara
                    });
                });
            });
            
            if (selectedHallazgos.length === 0) {
                alert('Seleccione al menos un hallazgo para generar el presupuesto.');
                return;
            }
            
            cerrarModalImportar();
            
            try {
                const res = await fetch('ajax_presupuesto.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        accion: 'generar_desde_odontograma',
                        paciente_id: pacienteId,
                        hallazgos: selectedHallazgos
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
                alert('Error de conexión al generar presupuesto.');
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
            } catch(e) { console.error(e); alert('Error de conexión.'); }
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
                    return true;
                } else {
                    alert('Error: ' + (data.error || 'No encontrado'));
                    return false;
                }
            } catch(e) { console.error(e); alert('Error al cargar presupuesto.'); return false; }
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
            
            const bloqueado = (presupuesto.estado === 'Aprobado' || parseFloat(presupuesto.monto_pagado || 0) > 0);
            
            // Habilitar/Deshabilitar inputs y botones clave
            document.getElementById('descuentoPorcentaje').value = presupuesto.descuento_porcentaje || 0;
            document.getElementById('descuentoPorcentaje').disabled = bloqueado;
            
            const btnAdd = document.getElementById('btnAnadirItemManual');
            if (btnAdd) {
                if (bloqueado) btnAdd.classList.add('hidden');
                else btnAdd.classList.remove('hidden');
            }
            
            const btnAprobar = document.getElementById('btnAprobarPresupuesto');
            const btnEnviar = document.getElementById('btnEnviarPresupuesto');
            
            if (btnAprobar) {
                if (presupuesto.estado === 'Aprobado') {
                    btnAprobar.classList.add('hidden');
                } else {
                    btnAprobar.classList.remove('hidden');
                }
            }
            if (btnEnviar) {
                if (presupuesto.estado === 'Aprobado') {
                    btnEnviar.classList.add('hidden');
                } else {
                    btnEnviar.classList.remove('hidden');
                }
            }

            renderizarItemsPresupuesto(presupuesto.items || [], bloqueado);
            actualizarTotalesUI(presupuesto);
            cargarPagos(presupuesto.id);
            document.getElementById('seccion_presupuestos').scrollIntoView({behavior: 'smooth'});
            lucide.createIcons();
        }

        function renderizarItemsPresupuesto(items, bloqueado = false) {
            const tbody = document.getElementById('presupuestoItemsBody');
            if (!items || items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-slate-400 text-sm">No hay ítems. Use el botón <b>Añadir Ítem</b> para agregar tratamientos.</td></tr>';
                return;
            }
            tbody.innerHTML = items.map(item => {
                const precioMostrado = item.precio_ajustado ?? item.precio_unitario;
                return `
                <tr class="hover:bg-teal-100 transition">
                    <td class="p-3 font-medium text-slate-700">${item.descripcion}</td>
                    <td class="p-3 text-center text-slate-500">${item.diente_numero || '-'}</td>
                    <td class="p-3 text-center text-slate-700 font-bold">${item.cantidad}</td>
                    <td class="p-3 text-right text-slate-500">S/ ${parseFloat(item.precio_unitario).toFixed(2)}</td>
                    <td class="p-3 text-right">
                        <input type="number" value="${parseFloat(precioMostrado).toFixed(2)}" step="0.01" min="0" ${bloqueado ? 'disabled' : ''}
                            class="w-24 text-right bg-blue-50 border border-blue-200 rounded-lg p-1.5 font-bold text-blue-800 outline-none focus:ring-2 focus:ring-blue-400 text-sm"
                            onchange="actualizarPrecioItem(${item.id}, this.value)">
                    </td>
                    <td class="p-3 text-right font-bold text-slate-800">S/ ${parseFloat(item.subtotal).toFixed(2)}</td>
                    <td class="p-3 text-center">
                        ${!bloqueado ? `
                        <button onclick="eliminarItem(${item.id})" class="text-slate-400 hover:text-red-500 transition p-1 rounded-lg hover:bg-red-50">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>` : ''}
                    </td>
                </tr>`;
            }).join('');
            lucide.createIcons();
        }

        function actualizarTotalesUI(p) {
            const total = parseFloat(p.total);
            document.getElementById('presupuestoSubtotal').innerText = `S/ ${parseFloat(p.subtotal).toFixed(2)}`;
            document.getElementById('presupuestoDescuento').innerText = `- S/ ${parseFloat(p.descuento_monto).toFixed(2)}`;
            document.getElementById('presupuestoTotal').innerText = `S/ ${total.toFixed(2)}`;
            
            const pagado = parseFloat(p.monto_pagado || 0);
            const saldo = total - pagado; // Fix: usar la diferencia real entre total y pagado
            
            document.getElementById('presupuestoPagado').innerText = `S/ ${pagado.toFixed(2)}`;
            document.getElementById('presupuestoSaldo').innerText = `S/ ${saldo.toFixed(2)}`;
            
            const adelantoElem = document.getElementById('presupuestoAdelantoSugerido');
            if (adelantoElem) {
                adelantoElem.innerText = `Adelanto Sugerido (50%): S/ ${(total * 0.50).toFixed(2)}`;
            }

            // Actualizar modal de pago si está abierto
            const modalSaldo = document.getElementById('pagoSaldoPendiente');
            if(modalSaldo) modalSaldo.innerText = `S/ ${saldo.toFixed(2)}`;
        }

        // --- Modal Agregar Ítem ---
        function agregarItemManual() {
            if (!presupuestoActivo) return;
            const modal = document.getElementById('modalAgregarItem');
            // Reset form
            if (tomSelectInstance) {
                tomSelectInstance.clear();
            } else {
                document.getElementById('itemCatalogoSelect').value = '';
            }
            document.getElementById('itemDescripcion').value = '';
            document.getElementById('itemPieza').value = '';
            document.getElementById('itemCantidad').value = '1';
            document.getElementById('itemPrecio').value = '0';
            // Mostrar
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => {
                modal.querySelector('div').classList.remove('scale-95');
                if (tomSelectInstance) {
                    tomSelectInstance.focus();
                }
            }, 10);
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

            if (!descripcion) { alert('Ingrese una descripción del tratamiento.'); return; }
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
            } catch(e) { console.error(e); alert('Error de conexión.'); }
        }

        // --- Operaciones sobre ítems ---
        async function actualizarPrecioItem(itemId, nuevoPrecio) {
            if (!presupuestoActivo) return;
            // Encontrar item actual para mantener descripción
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
            if (!presupuestoActivo || !confirm('¿Eliminar este ítem?')) return;
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

        // --- Estado y eliminación ---
        async function cambiarEstadoPresupuesto(nuevoEstado, pedirConfirmacion = true) {
            if (!presupuestoActivo) return;
            if (pedirConfirmacion && !confirm(`¿Cambiar estado a "${nuevoEstado}"?`)) return;
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
            if (!presupuestoActivo || !confirm('¿Eliminar este presupuesto permanentemente?')) return;
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

        function imprimirPresupuesto() {
            if (!presupuestoActivo) return;
            window.open('imprimir_presupuesto.php?id=' + presupuestoActivo.id + '&print=1', '_blank');
        }

        function abrirModalCompartir() {
            if (!presupuestoActivo) return;
            const modal = document.getElementById('modalCompartirPresupuesto');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => modal.querySelector('div').classList.remove('scale-95'), 10);
        }

        function cerrarModalCompartir() {
            const modal = document.getElementById('modalCompartirPresupuesto');
            modal.querySelector('div').classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }, 300);
        }

        function marcarPresupuestoComoEnviado() {
            if (presupuestoActivo && presupuestoActivo.estado === 'Borrador') {
                cambiarEstadoPresupuesto('Enviado', false);
            }
            cerrarModalCompartir();
        }

        function obtenerMensajePresupuesto() {
            const total = parseFloat(presupuestoActivo.total).toFixed(2);
            return `Hola *<?php echo htmlspecialchars(addslashes($paciente['nombre'])); ?>*, te adjuntamos el detalle de tu presupuesto odontológico por un total de *S/ ${total}*.\n_Quedamos atentos a cualquier duda._\nAtte: *MahuDent*.`;
        }

        function enviarPorWhatsApp() {
            const telefonoSpan = document.getElementById('paciente_telefono_span');
            if (!telefonoSpan) return;
            
            let phone = telefonoSpan.innerText.trim();
            if (phone === '-' || phone === '') {
                alert('El paciente no tiene un número de teléfono válido registrado.');
                return;
            }
            
            // Clean phone number
            phone = phone.replace(/\s+/g, '').replace(/\D/g, '');
            if (phone.length === 9) {
                phone = '51' + phone;
            } else if (phone.startsWith('0')) {
                phone = '51' + phone.substring(1);
            }

            const msj = obtenerMensajePresupuesto();
            const url = `https://wa.me/${phone}?text=${encodeURIComponent(msj)}`;
            window.open(url, '_blank');
            marcarPresupuestoComoEnviado();
        }

        function enviarPorEmail() {
            const email = "<?php echo htmlspecialchars($paciente['email']); ?>";
            if (!email) {
                alert("El paciente no tiene un correo electrónico registrado.");
                return;
            }
            const msj = obtenerMensajePresupuesto();
            const subject = "Detalle de Presupuesto Odontológico - MahuDent";
            const url = `mailto:${email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(msj)}`;
            window.open(url, '_blank');
            marcarPresupuestoComoEnviado();
        }

        async function cerrarEditorPresupuesto() {
            // Si el presupuesto está en borrador y su total es 0, asumimos que fue descartado
            if (presupuestoActivo && presupuestoActivo.estado === 'Borrador' && parseFloat(presupuestoActivo.total) === 0) {
                try {
                    await fetch('ajax_presupuesto.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({accion: 'eliminar_presupuesto', presupuesto_id: presupuestoActivo.id})
                    });
                } catch(e) {}
            }
            
            document.getElementById('editorPresupuesto').classList.add('hidden');
            document.getElementById('listaPresupuestos').classList.remove('hidden');
            presupuestoActivo = null;
            
            // Recargar para ocultar el eliminado
            cargarListaPresupuestos();
        }

        // --- PAGOS ---
        async function cargarPagos(presupuesto_id) {
            try {
                const res = await fetch('ajax_presupuesto.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({accion: 'listar_pagos', presupuesto_id: presupuesto_id})
                });
                const data = await res.json();
                if (data.success) {
                    renderizarPagos(data.pagos);
                }
            } catch(e) { console.error('Error cargando pagos:', e); }
        }

        function renderizarPagos(pagos) {
            const seccion = document.getElementById('seccionPagos');
            const tbody = document.getElementById('pagosBody');
            
            if (!pagos || pagos.length === 0) {
                seccion.classList.add('hidden');
                return;
            }
            
            seccion.classList.remove('hidden');
            tbody.innerHTML = pagos.map(p => {
                const fecha = new Date(p.fecha_pago).toLocaleDateString('es-ES', {day: '2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'});
                return `
                <tr class="hover:bg-teal-100">
                    <td class="p-2 font-medium text-slate-600">${fecha}</td>
                    <td class="p-2"><span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase ${p.tipo === 'Adelanto' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'}">${p.tipo}</span></td>
                    <td class="p-2 text-slate-600">${p.metodo_pago}</td>
                    <td class="p-2 text-slate-500 font-mono text-xs">${p.comprobante_numero || '-'}</td>
                    <td class="p-2 text-right font-black text-emerald-600">S/ ${parseFloat(p.monto).toFixed(2)}</td>
                    <td class="p-2 text-center">
                        <a href="imprimir_pago.php?id=${p.id}" target="_blank" class="text-slate-400 hover:text-brand transition" title="Imprimir Boleta">
                            <i data-lucide="printer" class="w-4 h-4 inline"></i>
                        </a>
                    </td>
                </tr>`;
            }).join('');
            // Se debe recrear los íconos de lucide ya que insertamos HTML dinámico
            lucide.createIcons();
        }

        function abrirModalPago() {
            if (!presupuestoActivo) return;
            const total = parseFloat(presupuestoActivo.total || 0);
            const pagado = parseFloat(presupuestoActivo.monto_pagado || 0);
            const saldo = total - pagado;
            
            if (saldo <= 0) {
                alert('Este presupuesto ya está totalmente pagado o su total es 0 (añada tratamientos primero).');
                return;
            }
            
            const modal = document.getElementById('modalRegistrarPago');
            document.getElementById('pagoMonto').value = saldo.toFixed(2);
            document.getElementById('pagoMonto').max = saldo.toFixed(2);
            document.getElementById('pagoNotas').value = '';
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => modal.querySelector('div').classList.remove('scale-95'), 10);
        }

        function cerrarModalPago() {
            const modal = document.getElementById('modalRegistrarPago');
            modal.querySelector('div').classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }, 300);
        }

        async function confirmarRegistrarPago() {
            if (!presupuestoActivo) return;
            const monto = parseFloat(document.getElementById('pagoMonto').value);
            const total = parseFloat(presupuestoActivo.total || 0);
            const pagado = parseFloat(presupuestoActivo.monto_pagado || 0);
            const saldo = total - pagado;
            
            if (!monto || monto <= 0) { alert('Ingrese un monto válido.'); return; }
            if (monto > saldo + 0.01) { alert('El monto no puede ser mayor al saldo pendiente.'); return; }
            
            const data = {
                accion: 'registrar_pago',
                presupuesto_id: presupuestoActivo.id,
                monto: monto,
                tipo: document.getElementById('pagoTipo').value,
                metodo_pago: document.getElementById('pagoMetodo').value,
                comprobante_tipo: document.getElementById('pagoComprobante').value,
                notas: document.getElementById('pagoNotas').value
            };

            try {
                const res = await fetch('ajax_presupuesto.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                const response = await res.json();
                if (response.success) {
                    // Actualizar presupuestoActivo con los nuevos saldos
                    presupuestoActivo = response.resumen.presupuesto;
                    actualizarTotalesUI(presupuestoActivo);
                    renderizarPagos(response.resumen.pagos);
                    cerrarModalPago();
                    
                    // Si el estado es Borrador o Enviado, lo pasamos a Aprobado automáticamente
                    if (presupuestoActivo.estado === 'Borrador' || presupuestoActivo.estado === 'Enviado') {
                        cambiarEstadoPresupuesto('Aprobado', false);
                    } else {
                        cargarListaPresupuestos(); // Actualiza la lista si no cambió el estado
                    }
                    
                    alert('Pago registrado exitosamente.');
                } else {
                    alert('Error: ' + response.error);
                }
            } catch(e) { console.error(e); alert('Error de conexión.'); }
        }

        function formatearFecha(fecha) {
            if (!fecha) return '-';
            const d = new Date(fecha + 'T00:00:00');
            const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
            return `${d.getDate()} ${meses[d.getMonth()]}, ${d.getFullYear()}`;
        }

        // Inicializar módulo
        cargarCatalogo();
        
        // Auto-abrir presupuesto si viene en URL
        const urlParams = new URLSearchParams(window.location.search);
        const openPresupuestoId = urlParams.get('open_presupuesto');
        const openNewPresupuesto = urlParams.get('open_new_presupuesto');
        
        if (openPresupuestoId) {
            const seccionPresupuestos = document.getElementById('seccion_presupuestos');
            if (seccionPresupuestos) seccionPresupuestos.scrollIntoView({behavior: 'smooth'});
            
            abrirPresupuesto(openPresupuestoId).then(abierto => {
                if (abierto && urlParams.get('action') === 'pay') {
                    // Dar un pequeño timeout para asegurar que renderizó
                    setTimeout(abrirModalPago, 300);
                }
            });
        } else if (openNewPresupuesto) {
            const seccionPresupuestos = document.getElementById('seccion_presupuestos');
            if (seccionPresupuestos) seccionPresupuestos.scrollIntoView({behavior: 'smooth'});
            cargarListaPresupuestos().then(() => {
                setTimeout(abrirModalNuevoPresupuesto, 500);
            });
        } else {
            cargarListaPresupuestos();
        }
    </script>


    <!-- Modal Subir Archivo -->
    <div id="modalSubirArchivo" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[60] hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md transform transition-transform duration-300 overflow-hidden">
            <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                <h3 class="font-bold text-slate-800 flex items-center gap-2">
                    <i data-lucide="upload-cloud" class="w-5 h-5 text-indigo-600"></i> Subir Archivo
                </h3>
                <button onclick="cerrarModalSubirArchivo()" class="text-slate-400 hover:text-red-500 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form id="formSubirArchivo" class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Tipo de Archivo</label>
                    <select id="archivoTipo" required class="w-full border-2 border-slate-200 rounded-xl p-2.5 text-sm font-bold text-slate-700 outline-none focus:border-indigo-500">
                        <option value="Radiografía">Radiografía</option>
                        <option value="Foto Intraoral">Foto Intraoral</option>
                        <option value="Documento">Documento (PDF)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Título / Descripción</label>
                    <input type="text" id="archivoDescripcion" required placeholder="Ej: Panorámica inicial..." class="w-full border-2 border-slate-200 rounded-xl p-2.5 text-sm text-slate-700 outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Seleccionar Archivo (Max 10MB)</label>
                    <input type="file" id="archivoFile" required accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition cursor-pointer border-2 border-slate-200 rounded-xl">
                </div>
                <div class="pt-4 flex gap-3 justify-end">
                    <button type="button" onclick="cerrarModalSubirArchivo()" class="px-5 py-2.5 rounded-xl font-bold text-slate-600 hover:bg-teal-200 transition text-sm">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md transition text-sm flex items-center gap-2">
                        <span id="textoBtnSubir">Guardar</span>
                        <div id="loaderBtnSubir" class="hidden w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lightbox Visor de Imágenes -->
    <div id="lightboxVisor" class="fixed inset-0 bg-black/95 z-[70] hidden items-center justify-center p-4">
        <button onclick="cerrarLightbox()" class="absolute top-6 right-6 text-white/50 hover:text-white transition bg-black/50 p-2 rounded-full backdrop-blur-sm">
            <i data-lucide="x" class="w-6 h-6"></i>
        </button>
        <div class="absolute bottom-6 left-6 text-white/80">
            <h3 id="lightboxTitulo" class="font-bold text-lg"></h3>
            <p id="lightboxFecha" class="text-xs"></p>
        </div>
        <img id="lightboxImg" src="" class="max-w-full max-h-full object-contain rounded-lg shadow-2xl transition-transform duration-300" style="transform: scale(1);">
        
        <!-- Controles de Zoom -->
        <div class="absolute bottom-6 right-6 flex gap-2">
            <button onclick="zoomImg(-0.2)" class="w-10 h-10 bg-white/10 hover:bg-white/20 text-white rounded-full flex items-center justify-center backdrop-blur-sm transition">
                <i data-lucide="zoom-out" class="w-5 h-5"></i>
            </button>
            <button onclick="zoomImg(0.2)" class="w-10 h-10 bg-white/10 hover:bg-white/20 text-white rounded-full flex items-center justify-center backdrop-blur-sm transition">
                <i data-lucide="zoom-in" class="w-5 h-5"></i>
            </button>
            <button onclick="zoomImg(0, true)" class="w-10 h-10 bg-white/10 hover:bg-white/20 text-white rounded-full flex items-center justify-center backdrop-blur-sm transition">
                <i data-lucide="maximize" class="w-4 h-4"></i>
            </button>
        </div>
    </div>

    <!-- Modal Historial Triaje -->
    <div id="modalHistorialTriaje" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-3xl w-full max-w-2xl overflow-hidden shadow-2xl transform transition-transform duration-300">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="text-xl font-black text-slate-800 flex items-center gap-2">
                    <i data-lucide="history" class="w-6 h-6 text-indigo-600"></i> Historial de Triaje
                </h3>
                <button onclick="cerrarHistorialTriaje()" class="text-slate-400 hover:text-slate-600 transition bg-white hover:bg-slate-100 p-2 rounded-xl shadow-sm">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="p-6 max-h-[60vh] overflow-y-auto">
                <div id="triajeHistorialContainer" class="space-y-4">
                    <!-- JS rellena aquí -->
                </div>
            </div>
        </div>
    </div>

    <script>
        function cerrarHistorialTriaje() {
            document.getElementById('modalHistorialTriaje').classList.add('hidden');
            document.getElementById('modalHistorialTriaje').classList.remove('flex');
        }

        async function verHistorialTriaje() {
            const m = document.getElementById('modalHistorialTriaje');
            m.classList.remove('hidden');
            m.classList.add('flex');
            
            const cont = document.getElementById('triajeHistorialContainer');
            cont.innerHTML = '<div class="text-center text-slate-400 py-8 text-sm"><i data-lucide="loader" class="w-6 h-6 animate-spin mx-auto mb-2"></i> Cargando historial...</div>';
            lucide.createIcons();

            try {
                const res = await fetch('ajax_clinico.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({accion: 'obtener_signos', paciente_id: <?php echo intval($paciente_id); ?>})
                });
                const data = await res.json();
                
                if (data.success) {
                    if (data.signos.length === 0) {
                        cont.innerHTML = '<div class="text-center text-slate-400 py-8 text-sm font-bold">No hay registros de triaje para este paciente.</div>';
                        return;
                    }

                    cont.innerHTML = data.signos.map(s => `
                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="flex justify-between items-center mb-3 border-b border-slate-200 pb-2">
                                <span class="text-xs font-bold text-slate-500 flex items-center gap-1"><i data-lucide="calendar" class="w-3.5 h-3.5"></i> ${s.fecha_registro}</span>
                                <span class="text-[10px] font-bold bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-md uppercase">${s.registrado_nombre || 'Dr.'}</span>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-2 text-sm">
                                <div><span class="block text-[10px] font-bold text-slate-400 uppercase">P.A.</span><span class="font-bold text-slate-700">${s.presion_arterial || '-'}</span></div>
                                <div><span class="block text-[10px] font-bold text-slate-400 uppercase">Pulso</span><span class="font-bold text-slate-700">${s.pulso || '-'}</span></div>
                                <div><span class="block text-[10px] font-bold text-slate-400 uppercase">F.C.</span><span class="font-bold text-slate-700">${s.frecuencia_cardiaca || '-'}</span></div>
                                <div><span class="block text-[10px] font-bold text-slate-400 uppercase">F.R.</span><span class="font-bold text-slate-700">${s.frecuencia_resp || '-'}</span></div>
                                <div><span class="block text-[10px] font-bold text-slate-400 uppercase">Temp.</span><span class="font-bold text-slate-700">${s.temperatura || '-'} °C</span></div>
                            </div>
                        </div>
                    `).join('');
                    lucide.createIcons();
                } else {
                    cont.innerHTML = '<div class="text-center text-red-500 py-8 text-sm font-bold">Error al cargar el historial.</div>';
                }
            } catch (e) {
                cont.innerHTML = '<div class="text-center text-red-500 py-8 text-sm font-bold">Error de red.</div>';
            }
        }

        let currentZoom = 1;
        let listaArchivosGlobal = [];
        let filtroActual = 'Todos';

        function abrirModalSubirArchivo() {
            document.getElementById('formSubirArchivo').reset();
            const m = document.getElementById('modalSubirArchivo');
            m.classList.remove('hidden');
            m.classList.add('flex');
        }

        function cerrarModalSubirArchivo() {
            const m = document.getElementById('modalSubirArchivo');
            m.classList.add('hidden');
            m.classList.remove('flex');
        }

        document.getElementById('formSubirArchivo').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btnSubir = document.getElementById('textoBtnSubir');
            const loader = document.getElementById('loaderBtnSubir');
            
            btnSubir.classList.add('hidden');
            loader.classList.remove('hidden');

            const fileInput = document.getElementById('archivoFile');
            const file = fileInput.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('paciente_id', pacienteId);
            formData.append('tipo', document.getElementById('archivoTipo').value);
            formData.append('descripcion', document.getElementById('archivoDescripcion').value);
            formData.append('archivo', file);

            try {
                const res = await fetch('ajax_archivos.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    cerrarModalSubirArchivo();
                    cargarListaArchivos();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (err) {
                alert('Error de conexión');
            } finally {
                btnSubir.classList.remove('hidden');
                loader.classList.add('hidden');
            }
        });

        async function cargarListaArchivos() {
            try {
                const res = await fetch('ajax_archivos.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ accion: 'listar', paciente_id: pacienteId })
                });
                const data = await res.json();
                if (data.success) {
                    listaArchivosGlobal = data.archivos;
                    renderizarArchivos();
                }
            } catch (err) { console.error('Error cargando archivos:', err); }
        }

        function renderizarArchivos() {
            const grid = document.getElementById('listaArchivosGrid');
            grid.innerHTML = '';
            
            const filtrados = filtroActual === 'Todos' 
                ? listaArchivosGlobal 
                : listaArchivosGlobal.filter(a => a.tipo === filtroActual);

            if (filtrados.length === 0) {
                grid.innerHTML = `<div class="col-span-full py-10 text-center text-slate-400 font-medium text-sm">No hay archivos para mostrar en esta categoría.</div>`;
                return;
            }

            filtrados.forEach(a => {
                const isPdf = a.ruta_archivo.toLowerCase().endsWith('.pdf');
                const thumb = isPdf ? '' : `<img src="${a.ruta_archivo}" class="w-full h-full object-cover opacity-80 group-hover:scale-105 transition-transform duration-500">`;
                const icon = a.tipo === 'Radiografía' ? 'image' : (a.tipo === 'Foto Intraoral' ? 'camera' : 'file-text');
                
                const formatSize = (bytes) => (bytes / (1024*1024)).toFixed(2) + ' MB';
                const formatDate = (f) => f ? f.split(' ')[0] : '';

                let html = `
                <div class="group border border-slate-200 rounded-xl overflow-hidden hover:shadow-lg transition-shadow bg-white flex flex-col relative">
                    <button onclick="eliminarArchivo(${a.id})" class="absolute top-2 right-2 w-8 h-8 bg-white text-red-500 rounded-full shadow-md flex items-center justify-center opacity-0 group-hover:opacity-100 hover:bg-red-50 transition z-10" title="Eliminar">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                    <div class="relative h-40 ${isPdf ? 'bg-slate-50' : 'bg-slate-900'} flex items-center justify-center overflow-hidden">
                        ${isPdf ? `<div class="w-16 h-16 bg-red-100 rounded-2xl flex items-center justify-center text-red-500 mb-2 shadow-sm"><i data-lucide="file-text" class="w-8 h-8"></i></div><span class="absolute bottom-4 text-xs font-bold text-slate-400 uppercase tracking-widest">Documento PDF</span>` : thumb}
                        
                        <div class="absolute top-3 left-3 ${isPdf ? 'bg-white/80 border text-slate-600' : 'bg-black/60 text-white'} backdrop-blur-sm text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider flex items-center gap-1">
                            <i data-lucide="${icon}" class="w-3 h-3"></i> ${a.tipo}
                        </div>
                        <div class="absolute inset-0 ${isPdf ? 'bg-white/40' : 'bg-black/40'} opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3">
                            ${!isPdf ? `<button onclick="abrirLightbox('${a.ruta_archivo}', '${a.descripcion}')" class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-slate-800 hover:text-indigo-600 hover:scale-110 shadow-md transition"><i data-lucide="eye" class="w-5 h-5"></i></button>` : ''}
                            <a href="${a.ruta_archivo}" download="${a.nombre_archivo}" class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-slate-800 hover:text-indigo-600 hover:scale-110 shadow-md transition"><i data-lucide="download" class="w-5 h-5"></i></a>
                        </div>
                    </div>
                    <div class="p-4 flex-1 flex flex-col justify-between">
                        <div>
                            <h4 class="font-bold text-slate-800 text-sm truncate" title="${a.descripcion}">${a.descripcion}</h4>
                            <p class="text-[10px] text-slate-400 mt-1 truncate" title="${a.nombre_archivo}">${a.nombre_archivo}</p>
                        </div>
                        <div class="flex justify-between items-center mt-4 pt-4 border-t border-slate-100">
                            <span class="text-[10px] font-bold text-slate-400">${formatDate(a.fecha_subida)}</span>
                            <span class="text-[10px] font-bold text-slate-400">${formatSize(a.tamano)}</span>
                        </div>
                    </div>
                </div>`;
                grid.innerHTML += html;
            });
            lucide.createIcons();
        }

        function filtrarArchivos(tipo) {
            filtroActual = tipo;
            document.querySelectorAll('.btn-filter').forEach(b => {
                b.className = "btn-filter px-4 py-2 text-sm font-bold rounded-lg text-slate-500 hover:text-slate-800 transition whitespace-nowrap";
            });
            event.target.className = "btn-filter px-4 py-2 text-sm font-bold rounded-lg bg-indigo-50 text-indigo-700 shadow-sm border border-indigo-100 whitespace-nowrap";
            renderizarArchivos();
        }

        async function eliminarArchivo(id) {
            if (!confirm('¿Está seguro de eliminar este archivo permanentemente?')) return;
            try {
                const res = await fetch('ajax_archivos.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ accion: 'eliminar', archivo_id: id })
                });
                const data = await res.json();
                if (data.success) {
                    cargarListaArchivos();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch(e) { alert('Error de conexión'); }
        }

        let panX = 0;
        let panY = 0;
        let isDragging = false;
        let startX = 0;
        let startY = 0;
        const lightboxImg = document.getElementById('lightboxImg');

        function actualizarTransform() {
            lightboxImg.style.transform = `translate(${panX}px, ${panY}px) scale(${currentZoom})`;
        }

        function abrirLightbox(ruta, desc) {
            lightboxImg.src = ruta;
            document.getElementById('lightboxTitulo').innerText = desc;
            currentZoom = 1;
            panX = 0;
            panY = 0;
            actualizarTransform();
            const m = document.getElementById('lightboxVisor');
            m.classList.remove('hidden');
            m.classList.add('flex');
        }

        function cerrarLightbox() {
            const m = document.getElementById('lightboxVisor');
            m.classList.add('hidden');
            m.classList.remove('flex');
            lightboxImg.src = '';
        }

        function zoomImg(delta, reset=false) {
            if (reset) {
                currentZoom = 1;
                panX = 0;
                panY = 0;
            } else {
                currentZoom = Math.max(0.5, Math.min(5, currentZoom + delta));
            }
            actualizarTransform();
        }

        lightboxImg.addEventListener('mousedown', (e) => {
            isDragging = true;
            startX = e.clientX - panX;
            startY = e.clientY - panY;
            lightboxImg.style.cursor = 'grabbing';
        });

        window.addEventListener('mouseup', () => {
            isDragging = false;
            lightboxImg.style.cursor = 'grab';
        });

        window.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            e.preventDefault();
            panX = e.clientX - startX;
            panY = e.clientY - startY;
            actualizarTransform();
        });

        lightboxImg.style.cursor = 'grab';

        // Cargar lista al iniciar
        document.addEventListener('DOMContentLoaded', () => {
            cargarListaArchivos();
        });

        // Modal Receta
        function abrirModalReceta() {
            const modal = document.getElementById('modalReceta');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => modal.querySelector('div').classList.remove('scale-95'), 10);
            document.getElementById('medicamentos_receta').value = '';
            document.getElementById('indicaciones_receta').value = '';
        }

        function cerrarModalReceta() {
            const modal = document.getElementById('modalReceta');
            modal.querySelector('div').classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }, 300);
        }

        
        // ═══════ ANTECEDENTES CLINICOS Y TRIAJE ═══════
        function toggleEditAntecedentes() {
            const inputs = document.querySelectorAll('.antecedentes-input');
            const radios = document.querySelectorAll('.antecedentes-radio');
            const btnEditar = document.getElementById('btnEditarAntecedentes');
            const btnGuardar = document.getElementById('btnGuardarAntecedentes');
            
            let isReadOnly = inputs[0].hasAttribute('readonly');
            
            if (isReadOnly) {
                inputs.forEach(el => {
                    el.removeAttribute('readonly');
                    el.classList.add('bg-white', 'border-indigo-200');
                    el.classList.remove('border-0', 'bg-transparent');
                });
                radios.forEach(el => el.removeAttribute('disabled'));
                btnEditar.classList.add('hidden');
                btnGuardar.classList.remove('hidden');
                btnGuardar.classList.add('flex');
            }
        }

        async function guardarTriaje() {
            const btn = document.getElementById('btnGuardarTriaje');
            const iconHTML = btn.innerHTML;
            btn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin"></i> Guardando...';
            btn.disabled = true;

            const payloadSignos = {
                accion: 'registrar_signos',
                paciente_id: PACIENTE_ID,
                presion_arterial: document.getElementById('det_pa').value.trim(),
                pulso: document.getElementById('det_pulso').value.trim(),
                frecuencia_cardiaca: document.getElementById('det_fc').value.trim(),
                frecuencia_resp: document.getElementById('det_fr').value.trim(),
                temperatura: document.getElementById('det_temp').value.trim(),
                observaciones: ''
            };

            try {
                const res = await fetch('ajax_clinico.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payloadSignos) });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'desconocido'));
                }
            } catch(e) {
                alert('Error de red al guardar triaje');
            } finally {
                btn.innerHTML = iconHTML;
                btn.disabled = false;
            }
        }

        async function guardarAntecedentes() {
            const btn = document.getElementById('btnGuardarAntecedentes');
            btn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin"></i> Guardando...';
            btn.disabled = true;

            const getRadio = (name) => { const el = document.querySelector(`input[name="${name}"]:checked`); return el ? el.value : null; };
            const getVal = (id) => { const el = document.getElementById(id); return el ? el.value.trim() : null; };
            const getCheck = (id) => { const el = document.getElementById(id); return el ? (el.checked ? 1 : 0) : 0; };
            
            const payloadAntecedentes = {
                accion: 'guardar_antecedentes', 
                paciente_id: PACIENTE_ID,
                padece_enfermedad: getRadio('padece_enfermedad'), enfermedades_cronicas: getVal('det_enfermedades_cronicas'),
                consume_medicamentos: getRadio('consume_medicamentos'), medicamentos_detalle: getVal('det_medicamentos_detalle'),
                alergia_medicamentos: getRadio('alergia_medicamentos'), alergia_medicamentos_detalle: getVal('det_alergia_medicamentos_detalle'),
                antecedentes_familiares: getRadio('antecedentes_familiares'), antecedentes_familiares_detalle: getVal('det_antecedentes_familiares_detalle'),
                alergia_anestesia: getRadio('alergia_anestesia'), embarazada: getRadio('embarazada'), sangran_encias: getRadio('sangran_encias'),
                ultima_visita_dentista: getVal('det_ultima_visita_dentista'), ultima_visita_motivo: getVal('det_ultima_visita_motivo'),
                frecuencia_cepillado: getVal('det_frecuencia_cepillado'),
                usa_cepillo: getCheck('det_usa_cepillo'), usa_pasta_dental: getCheck('det_usa_pasta_dental'),
                usa_hilo_dental: getCheck('det_usa_hilo_dental'), usa_enjuague: getCheck('det_usa_enjuague')
            };

            try {
                const res = await fetch('ajax_clinico.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payloadAntecedentes) });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'desconocido'));
                }
            } catch(e) { 
                alert('Error de red al guardar antecedentes'); 
                btn.innerHTML = '<i data-lucide="save" class="w-4 h-4"></i> Guardar';
                btn.disabled = false;
            }
        }
        // ═══════ RECETAS MEDICAS ═══════
        const PACIENTE_ID = <?php echo intval($paciente_id); ?>;

        async function guardarNuevaReceta(e) {
            e.preventDefault();
            const btn = document.getElementById('btnGuardarNuevaReceta');
            const originalText = btn.innerHTML;
            btn.innerHTML = 'Guardando...';
            btn.disabled = true;

            const payload = {
                accion: 'guardar_receta',
                paciente_id: PACIENTE_ID,
                diagnostico: '', // Removido
                contenido: document.getElementById('nueva_receta_contenido').value
            };

            try {
                const res = await fetch('ajax_clinico.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Desconocido'));
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            } catch(e) {
                alert('Error de red al guardar la receta');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        async function eliminarReceta(id) {
            if (!confirm('¿Estás seguro de eliminar esta receta? Esta acción no se puede deshacer.')) return;
            try {
                const res = await fetch('ajax_clinico.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({accion: 'eliminar_receta', id: id})
                });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error al eliminar');
                }
            } catch(e) {
                alert('Error de red');
            }
        }

        function imprimirReceta(id) {
            window.open('imprimir_receta.php?id=' + id, '_blank', 'width=800,height=900');
        }

        function enviarWhatsAppPaciente(nombrePaciente) {
            const telefonoSpan = document.getElementById('paciente_telefono_span');
            if (!telefonoSpan) return;
            
            let phone = telefonoSpan.innerText.trim();
            if (phone === '-' || phone === '') {
                alert('El paciente no tiene un número de teléfono registrado.');
                return;
            }
            
            // Clean phone number
            phone = phone.replace(/\s+/g, '').replace(/\D/g, '');
            if (phone.length === 9) {
                phone = '51' + phone;
            } else if (phone.startsWith('0')) {
                phone = '51' + phone.substring(1);
            }

            const mensaje = `Hola *${nombrePaciente}*, esperamos que est\xE9s muy bien tras tu tratamiento en *MahuDent*.\n_Cualquier molestia o para agendar tu pr\xF3xima revisi\xF3n, solo cont\xE1ctanos._\n\xA1Seguimos en contacto!`;
            
            const url = `https://wa.me/${phone}?text=${encodeURIComponent(mensaje)}`;
            window.open(url, '_blank');
        }

        // Cerrar modales al hacer clic en el fondo oscuro (backdrop)
        window.addEventListener('click', function(e) {
            const modals = [
                { id: 'modalDiente', close: cerrarModalDiente },
                { id: 'modalAgregarItem', close: cerrarModalItem },
                { id: 'modalRegistrarPago', close: cerrarModalPago },
                { id: 'modalCompartirPresupuesto', close: cerrarModalCompartir },
                { id: 'modalSubirArchivo', close: cerrarModalSubirArchivo },
                { id: 'modalHistorialTriaje', close: () => {
                    const m = document.getElementById('modalHistorialTriaje');
                    m.classList.add('hidden'); m.classList.remove('flex');
                }},
                { id: 'modalNuevaReceta', close: () => {
                    const m = document.getElementById('modalNuevaReceta');
                    m.classList.add('hidden');
                }},
                { id: 'lightboxVisor', close: typeof cerrarLightbox === 'function' ? cerrarLightbox : () => {} },
                { id: 'historialTriajeVisor', close: typeof cerrarHistorialTriaje === 'function' ? cerrarHistorialTriaje : () => {} },
                { id: 'modalImportarPresupuesto', close: cerrarModalImportar }
            ];
            
            modals.forEach(m => {
                const el = document.getElementById(m.id);
                if (el && e.target === el && !el.classList.contains('hidden')) {
                    m.close();
                }
            });
        });

        // Cerrar modales con la tecla Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modals = [
                    { id: 'modalDiente', close: cerrarModalDiente },
                    { id: 'modalAgregarItem', close: cerrarModalItem },
                    { id: 'modalRegistrarPago', close: cerrarModalPago },
                    { id: 'modalCompartirPresupuesto', close: cerrarModalCompartir },
                    { id: 'modalSubirArchivo', close: cerrarModalSubirArchivo },
                    { id: 'modalHistorialTriaje', close: () => {
                        const m = document.getElementById('modalHistorialTriaje');
                        m.classList.add('hidden'); m.classList.remove('flex');
                    }},
                    { id: 'modalNuevaReceta', close: () => {
                        const m = document.getElementById('modalNuevaReceta');
                        m.classList.add('hidden');
                    }},
                    { id: 'lightboxVisor', close: typeof cerrarLightbox === 'function' ? cerrarLightbox : () => {} },
                    { id: 'historialTriajeVisor', close: typeof cerrarHistorialTriaje === 'function' ? cerrarHistorialTriaje : () => {} },
                    { id: 'modalImportarPresupuesto', close: cerrarModalImportar }
                ];
                modals.forEach(m => {
                    const el = document.getElementById(m.id);
                    if (el && !el.classList.contains('hidden')) {
                        m.close();
                    }
                });
            }
        });
    </script>

</body>

</html>
