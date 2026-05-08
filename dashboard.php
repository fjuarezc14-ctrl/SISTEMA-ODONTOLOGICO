<?php
session_start();
require_once 'controllers/DashboardController.php';
$dashboardCtrl = new DashboardController();
$stats = $dashboardCtrl->getStats();
$citas_hoy = $dashboardCtrl->getProximasCitas();

// Si no hay sesión iniciada, redirige al login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Clínico - MahuDent</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap');

        :root {
            /* Paleta Clínica MahuDent */
            --brand-primary: #0f766e;
            --brand-secondary: #ccfbf1;
            --brand-accent: #14b8a6;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #f8fafc;
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

        .text-brand-accent {
            color: var(--brand-accent);
        }
    </style>
</head>

<body class="flex h-screen overflow-hidden relative">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <?php $show_search = true;
        include 'includes/header.php'; ?>

        <div class="flex-1 overflow-y-auto p-8">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 gap-4">
                <div>
                    <h1 class="text-3xl font-black text-slate-800 mb-1">Resumen General</h1>
                    <p class="text-slate-500 font-medium">Monitorea la actividad de tu clínica en tiempo real.</p>
                </div>
                <button onclick="toggleModal()"
                    class="bg-brand hover:bg-teal-800 text-white px-6 py-3 rounded-xl font-bold flex items-center gap-2 shadow-lg transition-all hover:scale-105 hover:shadow-teal-900/30">
                    <i data-lucide="user-plus" class="w-5 h-5"></i>
                    Nuevo Paciente
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div
                    class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between hover:shadow-md transition-shadow">
                    <div>
                        <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Total Pacientes</p>
                        <p class="text-3xl font-black text-slate-800">
                            <?php echo number_format($stats["total_pacientes"]); ?>
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-slate-50 text-slate-400 flex items-center justify-center">
                        <i data-lucide="users" class="w-6 h-6"></i>
                    </div>
                </div>

                <div
                    class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between hover:shadow-md transition-shadow">
                    <div>
                        <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Nuevos (Este mes)</p>
                        <div class="flex items-baseline gap-2">
                            <p class="text-3xl font-black text-brand"><?php echo $stats["nuevos_mes"]; ?></p>
                            <span
                                class="text-xs font-bold text-green-500 bg-green-50 px-2 py-0.5 rounded-full">+12%</span>
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-brand-light text-brand flex items-center justify-center">
                        <i data-lucide="trending-up" class="w-6 h-6"></i>
                    </div>
                </div>

                <div
                    class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between hover:shadow-md transition-shadow">
                    <div>
                        <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Citas Programadas
                            (Mes)</p>
                        <p class="text-3xl font-black text-slate-800"><?php echo $stats["citas_mes"]; ?></p>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center">
                        <i data-lucide="calendar" class="w-6 h-6"></i>
                    </div>
                </div>

                <div
                    class="bg-white p-6 rounded-2xl border border-orange-100 shadow-sm flex items-center justify-between hover:shadow-md transition-shadow relative overflow-hidden">
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

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h2 class="text-lg font-black text-slate-800">Pacientes Atendidos</h2>
                            <p class="text-xs text-slate-400 font-medium">Volumen de atenciones concretadas</p>
                        </div>
                        <div class="flex bg-slate-50 p-1 rounded-lg border border-slate-200">
                            <button
                                class="px-3 py-1 text-xs font-bold rounded-md text-slate-500 hover:text-brand transition">Día</button>
                            <button
                                class="px-3 py-1 text-xs font-bold rounded-md bg-white text-brand shadow-sm border border-slate-200">Semana</button>
                            <button
                                class="px-3 py-1 text-xs font-bold rounded-md text-slate-500 hover:text-brand transition">Mes</button>
                        </div>
                    </div>

                    <div class="relative h-64 w-full">
                        <canvas id="atencionesChart"></canvas>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex flex-col">
                    <h2 class="text-lg font-black text-slate-800 mb-1">Próximas Citas (Hoy)</h2>
                    <p class="text-xs text-slate-400 font-medium mb-6">Agenda inmediata</p>

                    <div class="space-y-4 overflow-y-auto flex-1">
                        <?php if ($citas_hoy->num_rows > 0): ?>
                            <?php while ($cita = $citas_hoy->fetch_assoc()): ?>
                                <div
                                    class="flex items-start gap-4 p-3 rounded-xl hover:bg-slate-50 border border-transparent hover:border-slate-100 transition">
                                    <div class="bg-brand-light text-brand px-3 py-2 rounded-lg text-center shrink-0">
                                        <p class="text-xs font-bold"><?php echo date("h:i", strtotime($cita['hora_inicio'])); ?>
                                        </p>
                                        <p class="text-[10px] uppercase font-bold">
                                            <?php echo date("A", strtotime($cita['hora_inicio'])); ?>
                                        </p>
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-800 text-sm">
                                            <?php echo htmlspecialchars($cita['paciente_nombre']); ?>
                                        </p>
                                        <p class="text-xs text-slate-500"><?php echo htmlspecialchars($cita['motivo']); ?></p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-sm text-slate-500 text-center py-4">No hay más citas pendientes hoy.</p>
                        <?php endif; ?>
                    </div>
                    <a href="agenda.php"
                        class="block w-full mt-4 py-2 text-center text-sm font-bold text-brand hover:text-teal-800 border border-slate-200 rounded-lg hover:bg-slate-50 transition">
                        Ver agenda completa
                    </a>
                </div>
            </div>

        </div>
    </main>

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
                        <p class="text-xs text-slate-500 font-medium">Completa los datos iniciales para la historia
                            clínica.</p>
                    </div>
                </div>
                <button onclick="toggleModal()"
                    class="text-slate-400 hover:text-red-500 hover:bg-red-50 p-2 rounded-xl transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <div class="p-8 overflow-y-auto">
                <form class="space-y-6">
                    <h3 class="text-sm font-bold text-brand uppercase tracking-wider border-b border-slate-100 pb-2">
                        Datos Personales</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-bold text-slate-600">Nombres y Apellidos *</label>
                            <input type="text" placeholder="Ej. Carlos Mendoza"
                                class="px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-brand/30 outline-none transition-all text-sm">
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-bold text-slate-600">DNI *</label>
                            <input type="text" placeholder="Número de documento"
                                class="px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-brand/30 outline-none transition-all text-sm">
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-bold text-slate-600">Teléfono / Celular *</label>
                            <input type="tel" placeholder="+51 987 654 321"
                                class="px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-brand/30 outline-none transition-all text-sm">
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-bold text-slate-600">Correo Electrónico</label>
                            <input type="email" placeholder="correo@ejemplo.com"
                                class="px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-brand/30 outline-none transition-all text-sm">
                        </div>
                    </div>
                    <h3
                        class="text-sm font-bold text-orange-600 uppercase tracking-wider border-b border-slate-100 pb-2 mt-8">
                        Alertas Clínicas</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-bold text-slate-600">Alergias Conocidas</label>
                            <input type="text" placeholder="Ej. Penicilina (Opcional)"
                                class="px-4 py-3 rounded-xl border border-orange-200 bg-orange-50 focus:bg-white focus:ring-2 focus:ring-orange-300 outline-none transition-all text-sm">
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-xs font-bold text-slate-600">Enfermedades Crónicas</label>
                            <input type="text" placeholder="Ej. Diabetes (Opcional)"
                                class="px-4 py-3 rounded-xl border border-orange-200 bg-orange-50 focus:bg-white focus:ring-2 focus:ring-orange-300 outline-none transition-all text-sm">
                        </div>
                    </div>
                </form>
            </div>
            <div class="bg-slate-50 px-8 py-5 border-t border-slate-200 flex justify-end gap-3 shrink-0">
                <button onclick="toggleModal()"
                    class="px-6 py-2.5 rounded-xl text-slate-600 font-bold hover:bg-slate-200 transition-colors">Cancelar</button>
                <button
                    class="bg-brand hover:bg-teal-800 text-white px-8 py-2.5 rounded-xl font-bold shadow-lg shadow-teal-900/20 transition-all hover:scale-105 flex items-center gap-2">
                    <i data-lucide="save" class="w-5 h-5"></i> Guardar Paciente
                </button>
            </div>
        </div>
    </div>

    <script>
        // 1. Inicializar iconos de Lucide
        lucide.createIcons();

        // 2. Función del Modal
        function toggleModal() {
            const modal = document.getElementById('modal-nuevo-paciente');
            modal.classList.toggle('hidden');
            modal.classList.toggle('flex');
        }

        // 3. Configuración del Gráfico (Chart.js)
        document.addEventListener("DOMContentLoaded", function () {
            const ctx = document.getElementById('atencionesChart').getContext('2d');

            // Colores corporativos (MahuDent)
            const brandColor = '#14b8a6'; // Turquesa vibrante
            const brandLight = '#ccfbf1'; // Turquesa claro

            new Chart(ctx, {
                type: 'bar', // Puede ser 'line', 'bar', etc.
                data: {
                    labels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'],
                    datasets: [{
                        label: 'Pacientes Atendidos',
                        data: [12, 18, 15, 22, 14, 28], // Datos de ejemplo
                        backgroundColor: brandColor,
                        borderRadius: 6, // Bordes redondeados en las barras
                        hoverBackgroundColor: '#0f766e', // Color más oscuro al pasar el mouse
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }, // Oculta la leyenda superior
                        tooltip: {
                            backgroundColor: '#05192d',
                            padding: 12,
                            titleFont: { family: 'Montserrat', size: 14 },
                            bodyFont: { family: 'Montserrat', size: 13 }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9', drawBorder: false }, // Líneas guía muy suaves
                            ticks: { font: { family: 'Montserrat' }, color: '#94a3b8' }
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