<?php
require_once 'includes/auth_guard.php';// Si no hay sesión iniciada, redirige al login
if(!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'controllers/PresupuestoController.php';
require_once 'controllers/PacienteController.php';
$controller = new PresupuestoController();
$presupuestos = $controller->getTodosPresupuestos();

$pacienteCtrl = new PacienteController();
$pacientes = $pacienteCtrl->index();

// Calcular estadísticas
$totalPendientes = 0;
$docsPendientes = 0;
$totalAprobadosMes = 0;
$mesActual = date('m');
$anioActual = date('Y');

$totalAprobados = 0;
$totalRechazados = 0;

$cuentasPorCobrar = 0;

foreach ($presupuestos as $p) {
    $total = floatval($p['total']);
    $pagado = floatval($p['monto_pagado'] ?? 0);
    $saldo = $total - $pagado;

    if ($p['estado'] == 'Enviado' || $p['estado'] == 'Borrador') {
        $totalPendientes += $total;
        $docsPendientes++;
    }
    
    $fechaP = strtotime($p['fecha_creacion'] ?? $p['fecha_emision']);
    if ($p['estado'] == 'Aprobado') {
        if (date('m', $fechaP) == $mesActual && date('Y', $fechaP) == $anioActual) {
            $totalAprobadosMes += $total;
        }
        $totalAprobados++;
        if ($saldo > 0) {
            $cuentasPorCobrar += $saldo;
        }
    }

    if ($p['estado'] == 'Rechazado') {
        $totalRechazados++;
    }
}

$tasaCierre = ($totalAprobados + $totalRechazados) > 0 ? ($totalAprobados / ($totalAprobados + $totalRechazados)) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión Global de Presupuestos - MahuDent</title>
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
    </style>
</head>
<body class="flex h-screen overflow-hidden">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        
        <?php $page_title = 'Presupuestos'; include 'includes/header.php'; ?>

        <div class="flex-1 overflow-y-auto p-4 md:p-8">
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Cotizaciones Pend.</p>
                    <div class="flex items-end gap-2">
                        <span class="text-2xl font-black text-slate-800">S/ <?php echo number_format($totalPendientes, 2); ?></span>
                        <span class="text-xs font-bold text-orange-500 mb-1 flex items-center gap-1">
                            <i data-lucide="clock" class="w-3 h-3"></i> <?php echo $docsPendientes; ?> Docs
                        </span>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 border-l-4 border-l-red-500">
                    <p class="text-xs font-bold text-red-400 uppercase tracking-widest mb-1">Cuentas por Cobrar</p>
                    <div class="flex items-end gap-2">
                        <span class="text-2xl font-black text-red-600">S/ <?php echo number_format($cuentasPorCobrar, 2); ?></span>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Ventas (Mes)</p>
                    <div class="flex items-end gap-2">
                        <span class="text-2xl font-black text-brand">S/ <?php echo number_format($totalAprobadosMes, 2); ?></span>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Tasa de Cierre</p>
                    <div class="flex items-end gap-2">
                        <span class="text-2xl font-black text-slate-800"><?php echo round($tasaCierre); ?>%</span>
                        <div class="w-16 h-2 bg-slate-100 rounded-full mb-2 overflow-hidden">
                            <div class="bg-brand h-full" style="width: <?php echo $tasaCierre; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-slate-50/50">
                    <div class="relative w-full md:w-96">
                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                        <input type="text" id="busquedaGlobal" placeholder="Buscar por paciente o N° presupuesto..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-teal-200 focus:border-teal-500">
                    </div>
                    <div class="flex gap-2 w-full md:w-auto">
                        <button onclick="document.getElementById('modalSeleccionarPaciente').classList.remove('hidden'); document.getElementById('modalSeleccionarPaciente').classList.add('flex');" class="flex-1 md:flex-none px-4 py-2.5 bg-brand text-white rounded-xl text-sm font-bold shadow-lg hover:bg-teal-800 transition flex items-center justify-center gap-2">
                            <i data-lucide="plus" class="w-4 h-4"></i> Nueva Venta / Presupuesto
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse" id="tablaPresupuestos">
                        <thead>
                            <tr class="text-[11px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">
                                <th class="px-6 py-4">N° Doc</th>
                                <th class="px-6 py-4">Paciente</th>
                                <th class="px-6 py-4">Fecha</th>
                                <th class="px-6 py-4 text-right">Total</th>
                                <th class="px-6 py-4 text-right">Saldo</th>
                                <th class="px-6 py-4 text-center">Estado</th>
                                <th class="px-6 py-4"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php if (empty($presupuestos)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-slate-400 font-medium">No se encontraron presupuestos registrados.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($presupuestos as $p): 
                                    $badgeClass = 'bg-slate-100 text-slate-500';
                                    switch($p['estado']) {
                                        case 'Aprobado': $badgeClass = 'bg-emerald-100 text-emerald-700'; break;
                                        case 'Enviado': $badgeClass = 'bg-blue-100 text-blue-700'; break;
                                        case 'Borrador': $badgeClass = 'bg-amber-100 text-amber-700'; break;
                                        case 'Rechazado': $badgeClass = 'bg-red-100 text-red-700'; break;
                                    }
                                    
                                    $total = floatval($p['total']);
                                    $pagado = floatval($p['monto_pagado'] ?? 0);
                                    $saldo = $total - $pagado;

                                    $onclickAttr = "";
                                    if (isset($p['paciente_estado_activo']) && $p['paciente_estado_activo'] == 0) {
                                        $onclickAttr = "onclick=\"alert('Este presupuesto pertenece a un paciente inhabilitado. Debe restaurar al paciente desde el módulo de Pacientes para ver los detalles.')\"";
                                    } else {
                                        $onclickAttr = "onclick=\"location.href='paciente_detalle.php?id=" . $p['paciente_id'] . "&open_presupuesto=" . $p['id'] . "#presupuestos'\"";
                                    }
                                ?>
                                <tr class="hover:bg-slate-50/80 transition-colors group cursor-pointer" <?php echo $onclickAttr; ?>>
                                    <td class="px-6 py-4 text-sm font-bold text-brand">#PR-<?php echo str_pad($p['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500">
                                                <?php 
                                                    $parts = explode(' ', $p['paciente_nombre']);
                                                    echo strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
                                                ?>
                                            </div>
                                            <span class="text-sm font-bold text-slate-700"><?php echo htmlspecialchars($p['paciente_nombre']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-500"><?php echo date('d M Y', strtotime($p['fecha_emision'])); ?></td>
                                    <td class="px-6 py-4 text-sm font-black text-slate-800 text-right">S/ <?php echo number_format($total, 2); ?></td>
                                    <td class="px-6 py-4 text-sm font-black text-right <?php echo $saldo > 0 ? 'text-red-600' : 'text-teal-600'; ?>">
                                        <?php echo $saldo > 0 ? 'S/ ' . number_format($saldo, 2) : 'Pagado'; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex justify-center">
                                            <span class="px-3 py-1 rounded-full <?php echo $badgeClass; ?> text-[10px] font-bold uppercase tracking-wider"><?php echo $p['estado']; ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right flex justify-end gap-2">
                                        <?php if ($saldo > 0): ?>
                                            <button onclick="event.stopPropagation(); location.href='paciente_detalle.php?id=<?php echo $p['paciente_id']; ?>&open_presupuesto=<?php echo $p['id']; ?>&action=pay#presupuestos'" 
                                                class="px-3 py-1.5 bg-teal-600 hover:bg-teal-700 text-white rounded-lg transition font-bold text-xs flex items-center gap-1 shadow-sm">
                                                <i data-lucide="banknote" class="w-3.5 h-3.5"></i> Pagar
                                            </button>
                                        <?php endif; ?>
                                        <button class="p-2 hover:bg-brand-light rounded-lg text-slate-400 hover:text-brand transition"><i data-lucide="eye" class="w-4 h-4"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="p-6 border-t border-slate-100 flex items-center justify-between bg-slate-50/30 text-xs font-bold text-slate-500 uppercase">
                    <span>Mostrando <?php echo count($presupuestos); ?> presupuestos</span>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Seleccionar Paciente para Nuevo Presupuesto -->
    <div id="modalSeleccionarPaciente" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md transform transition-transform duration-300 overflow-hidden">
            <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                <h3 class="font-bold text-slate-800 flex items-center gap-2">
                    <i data-lucide="user-plus" class="w-5 h-5 text-brand"></i> Buscar Paciente
                </h3>
                <button onclick="document.getElementById('modalSeleccionarPaciente').classList.add('hidden'); document.getElementById('modalSeleccionarPaciente').classList.remove('flex');" class="text-slate-400 hover:text-red-500 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="p-6">
                <p class="text-sm text-slate-500 mb-4">Para crear un presupuesto o realizar una venta directa, primero debe seleccionar al paciente correspondiente:</p>
                
                <div class="mb-6">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Seleccione el paciente</label>
                    <select id="selectNuevoPacientePresupuesto" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-200 focus:border-teal-500 bg-white font-medium text-slate-700 text-sm">
                        <option value="">Buscar paciente...</option>
                        <?php if($pacientes && $pacientes->num_rows > 0): ?>
                            <?php while($p = $pacientes->fetch_assoc()): ?>
                                <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nombre'] . ' (' . $p['dni'] . ')'); ?></option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="flex gap-3">
                    <button onclick="document.getElementById('modalSeleccionarPaciente').classList.add('hidden'); document.getElementById('modalSeleccionarPaciente').classList.remove('flex');" class="flex-1 px-4 py-2.5 bg-slate-100 text-slate-600 rounded-xl font-bold text-sm hover:bg-teal-200 transition">Cancelar</button>
                    <button onclick="irANuevoPresupuesto()" class="flex-1 px-4 py-2.5 bg-brand text-white rounded-xl font-bold text-sm hover:bg-teal-800 transition shadow-lg flex items-center justify-center gap-2">
                        Continuar <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </button>
                </div>
                
                <div class="mt-6 pt-4 border-t border-slate-100 text-center">
                    <p class="text-xs text-slate-500">¿El paciente es nuevo?</p>
                    <a href="pacientes.php" class="text-brand font-bold text-sm hover:underline mt-1 inline-block">Ir a crear paciente</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        document.addEventListener('DOMContentLoaded', function() {
            new TomSelect("#selectNuevoPacientePresupuesto", {
                create: false,
                sortField: {
                    field: "text",
                    direction: "asc"
                },
                placeholder: "Buscar por nombre o DNI...",
                maxOptions: 50
            });
        });

        function irANuevoPresupuesto() {
            const pacienteId = document.getElementById('selectNuevoPacientePresupuesto').value;
            if (!pacienteId) {
                alert('Por favor, seleccione un paciente de la lista.');
                return;
            }
            window.location.href = 'paciente_detalle.php?id=' + pacienteId + '&open_new_presupuesto=1#presupuestos';
        }

        // Filtro de búsqueda simple
        document.getElementById('busquedaGlobal').addEventListener('keyup', function() {
            const query = this.value.toLowerCase();
            const rows = document.querySelectorAll('#tablaPresupuestos tbody tr');
            
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
