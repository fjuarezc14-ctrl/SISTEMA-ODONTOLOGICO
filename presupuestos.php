<?php
session_start();
// Si no hay sesión iniciada, redirige al login
if(!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'controllers/PresupuestoController.php';
$controller = new PresupuestoController();
$presupuestos = $controller->getTodosPresupuestos();

// Calcular estadísticas
$totalPendientes = 0;
$docsPendientes = 0;
$totalAprobadosMes = 0;
$mesActual = date('m');
$anioActual = date('Y');

$totalAprobados = 0;
$totalRechazados = 0;

foreach ($presupuestos as $p) {
    if ($p['estado'] == 'Enviado' || $p['estado'] == 'Borrador') {
        $totalPendientes += $p['total'];
        $docsPendientes++;
    }
    
    $fechaP = strtotime($p['fecha_creacion']);
    if ($p['estado'] == 'Aprobado') {
        if (date('m', $fechaP) == $mesActual && date('Y', $fechaP) == $anioActual) {
            $totalAprobadosMes += $p['total'];
        }
        $totalAprobados++;
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
    </style>
</head>
<body class="flex h-screen overflow-hidden">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        
        <?php $page_title = 'Presupuestos'; include 'includes/header.php'; ?>

        <div class="flex-1 overflow-y-auto p-8">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Pendientes</p>
                    <div class="flex items-end gap-2">
                        <span class="text-3xl font-black text-slate-800">S/ <?php echo number_format($totalPendientes, 2); ?></span>
                        <span class="text-xs font-bold text-orange-500 mb-1.5 flex items-center gap-1">
                            <i data-lucide="clock" class="w-3 h-3"></i> <?php echo $docsPendientes; ?> Docs
                        </span>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Aprobados (Mes)</p>
                    <div class="flex items-end gap-2">
                        <span class="text-3xl font-black text-brand">S/ <?php echo number_format($totalAprobadosMes, 2); ?></span>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Tasa de Cierre</p>
                    <div class="flex items-end gap-2">
                        <span class="text-3xl font-black text-slate-800"><?php echo round($tasaCierre); ?>%</span>
                        <div class="w-24 h-2 bg-slate-100 rounded-full mb-3 overflow-hidden">
                            <div class="bg-brand h-full" style="width: <?php echo $tasaCierre; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-slate-50/50">
                    <div class="relative w-full md:w-96">
                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                        <input type="text" id="busquedaGlobal" placeholder="Buscar por paciente o N° presupuesto..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand/20">
                    </div>
                    <div class="flex gap-2 w-full md:w-auto">
                        <button class="flex-1 md:flex-none px-4 py-2.5 bg-brand text-white rounded-xl text-sm font-bold shadow-lg hover:bg-teal-800 transition flex items-center justify-center gap-2" onclick="location.href='pacientes.php'">
                            <i data-lucide="plus" class="w-4 h-4"></i> Nuevo Presupuesto
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
                                ?>
                                <tr class="hover:bg-slate-50/80 transition-colors group cursor-pointer" onclick="location.href='paciente_detalle.php?id=<?php echo $p['paciente_id']; ?>#presupuestos'">
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
                                    <td class="px-6 py-4 text-sm font-black text-slate-800 text-right">S/ <?php echo number_format($p['total'], 2); ?></td>
                                    <td class="px-6 py-4">
                                        <div class="flex justify-center">
                                            <span class="px-3 py-1 rounded-full <?php echo $badgeClass; ?> text-[10px] font-bold uppercase tracking-wider"><?php echo $p['estado']; ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
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

    <script>
        lucide.createIcons();

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
