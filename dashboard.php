<?php
require_once 'includes/auth_guard.php';
require_once 'controllers/DashboardController.php';
$dashboardCtrl = new DashboardController();
$stats = $dashboardCtrl->getStats();
$citas_hoy = $dashboardCtrl->getProximasCitas();
$chart_citas = $dashboardCtrl->getChartCitas7Dias();
$chart_ingresos = $dashboardCtrl->getChartIngresos6Meses();
$top_tratamientos = $dashboardCtrl->getTratamientosTop();

// Preparar datos de graficos para JS
$chart_citas_labels = [];
$chart_citas_data = [];
$diasNombres = ['Dom','Lun','Mar','Mie','Jue','Vie','Sab'];
foreach ($chart_citas as $dia => $total) {
    $numDia = date('w', strtotime($dia));
    $chart_citas_labels[] = $diasNombres[$numDia] . ' ' . date('d', strtotime($dia));
    $chart_citas_data[] = $total;
}

$chart_ingresos_labels = [];
$chart_ingresos_data = [];
$mesesNombres = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
foreach ($chart_ingresos as $mes => $total) {
    $numMes = intval(date('m', strtotime($mes . '-01')));
    $chart_ingresos_labels[] = $mesesNombres[$numMes];
    $chart_ingresos_data[] = $total;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Clinico - MahuDent</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        teal: {
                            50: '#f5f3fa',
                            100: '#ede8f7',
                            200: '#dcd3ef',
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
        }

        .bg-brand { background-color: var(--brand-primary); }
        .text-brand { color: var(--brand-primary); }
        .bg-brand-light { background-color: var(--brand-secondary); }
        .text-brand-accent { color: var(--brand-accent); }
    </style>
</head>

<body class="flex h-screen overflow-hidden relative">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <?php $show_search = true;
        include 'includes/header.php'; ?>

        <div class="flex-1 overflow-y-auto p-4 md:p-8">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 gap-4">
                <div>
                    <h1 class="text-3xl font-black text-slate-800 mb-1">Resumen General</h1>
                    <p class="text-slate-500 font-medium">Monitorea la actividad de tu cl&#237;nica en tiempo real.</p>
                </div>
                <button onclick="toggleModal()"
                    class="bg-brand hover:bg-teal-800 text-white px-6 py-3 rounded-xl font-bold flex items-center gap-2 shadow-lg transition-all hover:scale-105 hover:shadow-teal-900/30">
                    <i data-lucide="user-plus" class="w-5 h-5"></i>
                    Nuevo Paciente
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between hover:shadow-md transition-shadow">
                    <div>
                        <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Total Pacientes</p>
                        <p class="text-3xl font-black text-slate-800"><?php echo number_format($stats["total_pacientes"]); ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-slate-50 text-slate-400 flex items-center justify-center">
                        <i data-lucide="users" class="w-6 h-6"></i>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between hover:shadow-md transition-shadow">
                    <div>
                        <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Nuevos (Este mes)</p>
                        <div class="flex items-baseline gap-2">
                            <p class="text-3xl font-black text-brand"><?php echo $stats["nuevos_mes"]; ?></p>
                            <?php 
                            $pct = $stats['nuevos_porcentaje'];
                            if ($pct > 0): ?>
                                <span class="text-xs font-bold text-green-500 bg-green-50 px-2 py-0.5 rounded-full">+<?php echo $pct; ?>%</span>
                            <?php elseif ($pct < 0): ?>
                                <span class="text-xs font-bold text-red-500 bg-red-50 px-2 py-0.5 rounded-full"><?php echo $pct; ?>%</span>
                            <?php else: ?>
                                <span class="text-xs font-bold text-slate-400 bg-slate-50 px-2 py-0.5 rounded-full">0%</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-brand-light text-brand flex items-center justify-center">
                        <i data-lucide="trending-up" class="w-6 h-6"></i>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between hover:shadow-md transition-shadow">
                    <div>
                        <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Citas Programadas (Mes)</p>
                        <p class="text-3xl font-black text-slate-800"><?php echo $stats["citas_mes"]; ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center">
                        <i data-lucide="calendar" class="w-6 h-6"></i>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-orange-100 shadow-sm flex items-center justify-between hover:shadow-md transition-shadow relative overflow-hidden">
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-orange-400"></div>
                    <div>
                        <p class="text-orange-600 text-xs font-bold uppercase tracking-wider mb-1">Por Atender Hoy</p>
                        <p class="text-3xl font-black text-orange-500"><?php echo $stats["citas_hoy"]; ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-orange-50 text-orange-500 flex items-center justify-center">
                        <i data-lucide="clock" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>

            <!-- Second Row: Income + Presupuestos -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-gradient-to-br from-teal-700 to-teal-900 p-6 rounded-2xl shadow-lg text-white flex items-center justify-between">
                    <div>
                        <p class="text-teal-200 text-xs font-bold uppercase tracking-wider mb-1">Ingresos del Mes</p>
                        <p class="text-3xl font-black">S/ <?php echo number_format($stats["ingresos_mes"], 2); ?></p>
                        <p class="text-teal-300 text-xs font-medium mt-1">Pagos recibidos en <?php echo date('F Y'); ?></p>
                    </div>
                    <div class="w-14 h-14 rounded-2xl bg-white/10 flex items-center justify-center">
                        <i data-lucide="banknote" class="w-7 h-7"></i>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between hover:shadow-md transition-shadow">
                    <div>
                        <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Presupuestos Activos</p>
                        <p class="text-3xl font-black text-indigo-600"><?php echo $stats["presupuestos_activos"]; ?></p>
                        <p class="text-slate-400 text-xs font-medium mt-1">Borrador + Enviado + Aprobado</p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-indigo-50 text-indigo-500 flex items-center justify-center">
                        <i data-lucide="file-text" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Citas ultimos 7 dias -->
                <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h2 class="text-lg font-black text-slate-800">Citas - &#218;ltimos 7 d&#237;as</h2>
                            <p class="text-xs text-slate-400 font-medium">Volumen de citas programadas por d&#237;a</p>
                        </div>
                    </div>
                    <div class="relative h-64 w-full">
                        <canvas id="citasChart"></canvas>
                    </div>
                </div>

                <!-- Proximas Citas -->
                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col">
                    <h2 class="text-lg font-black text-slate-800 mb-1">Pr&#243;ximas Citas (Hoy)</h2>
                    <p class="text-xs text-slate-400 font-medium mb-6">Agenda inmediata</p>

                    <div class="space-y-4 overflow-y-auto flex-1">
                        <?php if ($citas_hoy->num_rows > 0): ?>
                            <?php while ($cita = $citas_hoy->fetch_assoc()): ?>
                                <div class="flex items-start gap-4 p-3 rounded-xl hover:bg-slate-50 border border-transparent hover:border-slate-100 transition">
                                    <div class="bg-brand-light text-brand px-3 py-2 rounded-lg text-center shrink-0">
                                        <p class="text-xs font-bold"><?php echo date("h:i", strtotime($cita['hora_inicio'])); ?></p>
                                        <p class="text-[10px] uppercase font-bold"><?php echo date("A", strtotime($cita['hora_inicio'])); ?></p>
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-800 text-sm"><?php echo htmlspecialchars($cita['paciente_nombre']); ?></p>
                                        <p class="text-xs text-slate-500"><?php echo htmlspecialchars($cita['motivo']); ?></p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <div class="w-16 h-16 mx-auto bg-slate-50 rounded-full flex items-center justify-center mb-3">
                                    <i data-lucide="calendar-check" class="w-8 h-8 text-slate-300"></i>
                                </div>
                                <p class="text-sm text-slate-400 font-medium">No hay citas pendientes hoy.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <a href="agenda.php"
                        class="block w-full mt-4 py-2 text-center text-sm font-bold text-brand hover:text-teal-800 border border-slate-200 rounded-lg hover:bg-slate-50 transition">
                        Ver agenda completa
                    </a>
                </div>
            </div>

            <!-- Bottom Row: Ingresos Chart + Top Tratamientos -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h2 class="text-lg font-black text-slate-800">Ingresos - &#218;ltimos 6 Meses</h2>
                            <p class="text-xs text-slate-400 font-medium">Evoluci&#243;n de los pagos recibidos</p>
                        </div>
                    </div>
                    <div class="relative h-64 w-full">
                        <canvas id="ingresosChart"></canvas>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
                    <h2 class="text-lg font-black text-slate-800 mb-1">Top Tratamientos</h2>
                    <p class="text-xs text-slate-400 font-medium mb-6">Los m&#225;s solicitados</p>
                    <div class="space-y-4">
                        <?php if (!empty($top_tratamientos)): ?>
                            <?php 
                            $maxCant = $top_tratamientos[0]['cantidad'] ?? 1;
                            foreach ($top_tratamientos as $i => $trat): 
                                $porcent = round(($trat['cantidad'] / $maxCant) * 100);
                                $colors = ['bg-teal-500', 'bg-blue-500', 'bg-indigo-500', 'bg-amber-500', 'bg-rose-500'];
                            ?>
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="font-bold text-slate-700 truncate max-w-[180px]"><?php echo htmlspecialchars($trat['descripcion']); ?></span>
                                    <span class="font-black text-slate-800"><?php echo $trat['cantidad']; ?></span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2.5">
                                    <div class="<?php echo $colors[$i % 5]; ?> h-2.5 rounded-full transition-all duration-500" style="width: <?php echo $porcent; ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <div class="w-16 h-16 mx-auto bg-slate-50 rounded-full flex items-center justify-center mb-3">
                                    <i data-lucide="bar-chart-3" class="w-8 h-8 text-slate-300"></i>
                                </div>
                                <p class="text-sm text-slate-400 font-medium">Sin datos de tratamientos a&#250;n.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- Modal Nuevo Paciente -->
    <div id="modal-nuevo-paciente"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4 transition-opacity">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="bg-slate-50 px-8 py-5 border-b border-slate-200 flex justify-between items-center shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-brand-light text-brand flex items-center justify-center">
                        <i data-lucide="user-plus" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-slate-800">Registrar Nuevo Paciente</h2>
                        <p class="text-xs text-slate-500 font-medium">Completa los datos iniciales para la historia cl&#237;nica.</p>
                    </div>
                </div>
                <button onclick="toggleModal()"
                    class="text-slate-400 hover:text-red-500 hover:bg-red-50 p-2 rounded-xl transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <div class="p-8 overflow-y-auto">
                <form method="POST" action="pacientes.php" class="space-y-4">
                        <input type="hidden" name="accion" value="nuevo_paciente">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">DNI *</label>
                                <input type="text" name="dni" required class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nombres y Apellidos *</label>
                                <input type="text" name="nombre" required class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Teléfono</label>
                                <input type="text" name="telefono" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Correo Electrónico</label>
                                <input type="email" name="email" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Fecha de Nacimiento</label>
                                <input type="date" name="fecha_nacimiento" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Sexo</label>
                                <select name="sexo" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                                    <option value="">Seleccione...</option>
                                    <option value="Masculino">Masculino</option>
                                    <option value="Femenino">Femenino</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Ocupación</label>
                                <input type="text" name="ocupacion" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Dirección / Residencia</label>
                                <input type="text" name="direccion" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Lugar de Nacimiento</label>
                                <input type="text" name="lugar_nacimiento" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Procedencia</label>
                                <input type="text" name="procedencia" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Contacto de Emergencia</label>
                                <input type="text" name="contacto_emergencia" placeholder="Nombre del familiar/amigo" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Teléfono de Emergencia</label>
                                <input type="text" name="telefono_emergencia" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-red-500 uppercase mb-1 flex items-center gap-1"><i data-lucide="alert-triangle" class="w-3 h-3"></i> Alergias / Condiciones Médicas</label>
                            <input type="text" name="alergias" placeholder="Ej: Penicilina, Hipertensión..." class="w-full px-4 py-2 rounded-xl border border-red-200 focus:outline-none focus:ring-2 focus:ring-red-500/30 placeholder:text-red-200 text-sm">
                        </div>

                        <div class="pt-4 flex gap-3">
                            <button type="button" onclick="toggleModal()" class="flex-1 px-4 py-3 border border-slate-200 text-slate-600 rounded-xl font-bold hover:bg-slate-50 transition">Cancelar</button>
                            <button type="submit" class="flex-1 px-4 py-3 bg-brand hover:bg-teal-800 text-white rounded-xl font-bold shadow-lg transition">Guardar Paciente</button>
                        </div>
                    </form>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function toggleModal() {
            const modal = document.getElementById('modal-nuevo-paciente');
            modal.classList.toggle('hidden');
            modal.classList.toggle('flex');
        }

        document.addEventListener("DOMContentLoaded", function () {
            const brandColor = '#937ec2';
            const brandDark = '#3a596a';

            // --- Citas Chart (Bar) ---
            const ctxCitas = document.getElementById('citasChart').getContext('2d');
            new Chart(ctxCitas, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($chart_citas_labels); ?>,
                    datasets: [{
                        label: 'Citas',
                        data: <?php echo json_encode($chart_citas_data); ?>,
                        backgroundColor: brandColor,
                        borderRadius: 8,
                        hoverBackgroundColor: brandDark,
                        maxBarThickness: 40
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#05192d',
                            padding: 12,
                            titleFont: { family: 'Montserrat', size: 13, weight: 'bold' },
                            bodyFont: { family: 'Montserrat', size: 12 }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1, font: { family: 'Montserrat' }, color: '#94a3b8' },
                            grid: { color: '#f1f5f9', drawBorder: false }
                        },
                        x: {
                            grid: { display: false, drawBorder: false },
                            ticks: { font: { family: 'Montserrat', weight: 'bold' }, color: '#64748b' }
                        }
                    }
                }
            });

            // --- Ingresos Chart (Line/Area) ---
            const ctxIngresos = document.getElementById('ingresosChart').getContext('2d');
            const gradient = ctxIngresos.createLinearGradient(0, 0, 0, 260);
            gradient.addColorStop(0, 'rgba(20, 184, 166, 0.25)');
            gradient.addColorStop(1, 'rgba(20, 184, 166, 0.02)');

            new Chart(ctxIngresos, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($chart_ingresos_labels); ?>,
                    datasets: [{
                        label: 'Ingresos S/',
                        data: <?php echo json_encode($chart_ingresos_data); ?>,
                        borderColor: brandColor,
                        backgroundColor: gradient,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: brandDark,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        borderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#05192d',
                            padding: 12,
                            titleFont: { family: 'Montserrat', size: 13, weight: 'bold' },
                            bodyFont: { family: 'Montserrat', size: 12 },
                            callbacks: {
                                label: function(context) {
                                    return 'S/ ' + context.parsed.y.toFixed(2);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { 
                                font: { family: 'Montserrat' }, color: '#94a3b8',
                                callback: function(value) { return 'S/' + value; }
                            },
                            grid: { color: '#f1f5f9', drawBorder: false }
                        },
                        x: {
                            grid: { display: false, drawBorder: false },
                            ticks: { font: { family: 'Montserrat', weight: 'bold' }, color: '#64748b' }
                        }
                    }
                }
            });
        });
    </script>
</body>

</html>