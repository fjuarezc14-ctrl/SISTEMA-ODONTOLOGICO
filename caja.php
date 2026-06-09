<?php
require_once 'includes/auth_guard.php';
if($_SESSION['usuario_rol'] !== 'Admin') {
    header("Location: dashboard.php");
    exit;
}

require_once 'config/conexion.php';
require_once 'models/Pago.php';

$pagoModel = new Pago($conn);

// Fechas para el filtro
$fecha_hoy = date('Y-m-d');
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : $fecha_hoy;
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : $fecha_hoy;

// Obtener pagos
$pagos = $pagoModel->getPagosPorFecha($fecha_inicio, $fecha_fin);

// Calcular totales por método
$totales = [
    'Efectivo' => 0,
    'Tarjeta' => 0,
    'Transferencia' => 0,
    'Yape/Plin' => 0,
    'Total_General' => 0
];

foreach ($pagos as $p) {
    $monto = floatval($p['monto']);
    $metodo = $p['metodo_pago'];
    if (isset($totales[$metodo])) {
        $totales[$metodo] += $monto;
    } else {
        // En caso de que se haya guardado algún método distinto, sumarlo a Efectivo u otro default
        $totales['Efectivo'] += $monto;
    }
    $totales['Total_General'] += $monto;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caja Diaria - MahuDent</title>
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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap');
        :root { --brand-primary: #3a596a; --brand-secondary: #ede8f7; --brand-accent: #937ec2; }
        body { font-family: 'Montserrat', sans-serif; background-color: #f8fafc; }
        .bg-brand { background-color: var(--brand-primary); }
        .text-brand { color: var(--brand-primary); }
        .border-brand { border-color: var(--brand-primary); }
    </style>
</head>
<body class="flex h-screen overflow-hidden">
    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <?php $page_title = 'Caja Diaria / Reportes'; include 'includes/header.php'; ?>

        <div class="flex-1 overflow-y-auto p-4 md:p-8">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <!-- Cabecera de Reporte para Impresión -->
                <div class="hidden print:block border-b-2 border-slate-800 pb-4 mb-6">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <img src="assets/logo_icon.png" alt="Logo" class="w-12 h-12 rounded-xl object-contain p-1 border border-slate-200 bg-white">
                            <div>
                                <img src="assets/logo_text_dark.png" alt="MahuDent" class="h-6 w-auto object-contain">
                                <p class="text-[9px] text-slate-500 font-bold uppercase tracking-wider mt-0.5">Clínica Odontológica</p>
                                <p class="text-[10px] text-slate-400 mt-0.5">Jr. San Sebastián 116 | Cel/WhatsApp: 941124848</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <h2 class="text-lg font-black text-slate-800 uppercase">Reporte de Caja Diaria</h2>
                            <p class="text-xs text-slate-500 mt-1"><strong>Periodo:</strong> <?php echo date('d/m/Y', strtotime($fecha_inicio)); ?> al <?php echo date('d/m/Y', strtotime($fecha_fin)); ?></p>
                            <p class="text-[9px] text-slate-400 mt-0.5">Generado el: <?php echo date('d/m/Y h:i A'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex flex-wrap items-end justify-between gap-4 print:hidden">
                    <form method="GET" class="flex flex-wrap items-end gap-4 w-full md:w-auto">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Desde</label>
                            <input type="date" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>" class="border-2 border-slate-200 rounded-xl p-2.5 text-sm font-bold text-slate-700 outline-none focus:border-teal-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Hasta</label>
                            <input type="date" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>" class="border-2 border-slate-200 rounded-xl p-2.5 text-sm font-bold text-slate-700 outline-none focus:border-teal-500">
                        </div>
                        <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 px-5 rounded-xl shadow-md transition flex items-center gap-2 text-sm h-[44px]">
                            <i data-lucide="filter" class="w-4 h-4"></i> Filtrar
                        </button>
                        
                        <?php if ($fecha_inicio !== $fecha_hoy || $fecha_fin !== $fecha_hoy): ?>
                            <a href="caja.php" class="text-sm font-bold text-teal-600 hover:text-teal-700 flex items-center h-[44px] ml-2">Limpiar</a>
                        <?php endif; ?>
                    </form>
                    
                    <button onclick="window.print()" class="bg-white border-2 border-slate-200 text-slate-700 hover:bg-slate-50 font-bold py-2 px-4 rounded-xl transition flex items-center gap-2 text-sm shadow-sm print:hidden">
                        <i data-lucide="printer" class="w-4 h-4"></i> Imprimir Reporte
                    </button>
                </div>

                <!-- Dashboard de Totales -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                        <div class="flex items-center gap-3 mb-2 text-emerald-600">
                            <i data-lucide="banknote" class="w-5 h-5"></i>
                            <h3 class="font-bold text-xs uppercase tracking-wider text-slate-500">Efectivo</h3>
                        </div>
                        <p class="text-2xl font-black text-slate-800">S/ <?php echo number_format($totales['Efectivo'], 2); ?></p>
                    </div>
                    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                        <div class="flex items-center gap-3 mb-2 text-blue-600">
                            <i data-lucide="credit-card" class="w-5 h-5"></i>
                            <h3 class="font-bold text-xs uppercase tracking-wider text-slate-500">Tarjeta</h3>
                        </div>
                        <p class="text-2xl font-black text-slate-800">S/ <?php echo number_format($totales['Tarjeta'], 2); ?></p>
                    </div>
                    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                        <div class="flex items-center gap-3 mb-2 text-indigo-600">
                            <i data-lucide="building" class="w-5 h-5"></i>
                            <h3 class="font-bold text-xs uppercase tracking-wider text-slate-500">Transf.</h3>
                        </div>
                        <p class="text-2xl font-black text-slate-800">S/ <?php echo number_format($totales['Transferencia'], 2); ?></p>
                    </div>
                    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
                        <div class="flex items-center gap-3 mb-2 text-purple-600">
                            <i data-lucide="smartphone" class="w-5 h-5"></i>
                            <h3 class="font-bold text-xs uppercase tracking-wider text-slate-500">Yape/Plin</h3>
                        </div>
                        <p class="text-2xl font-black text-slate-800">S/ <?php echo number_format($totales['Yape/Plin'], 2); ?></p>
                    </div>
                    <div class="bg-brand text-white p-5 rounded-2xl shadow-md border border-teal-800 relative overflow-hidden">
                        <div class="absolute -right-4 -bottom-4 opacity-10">
                            <i data-lucide="wallet" class="w-24 h-24"></i>
                        </div>
                        <div class="relative z-10">
                            <h3 class="font-bold text-xs uppercase tracking-wider text-teal-100 mb-2">Ingreso Total</h3>
                            <p class="text-3xl font-black">S/ <?php echo number_format($totales['Total_General'], 2); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Movimientos -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                        <h3 class="text-lg font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                            <i data-lucide="list" class="w-5 h-5 text-teal-600"></i> Movimientos Registrados
                        </h3>
                        <span class="bg-teal-100 text-teal-800 text-xs font-bold px-3 py-1 rounded-full border border-teal-200">
                            <?php echo count($pagos); ?> transacciones
                        </span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 text-xs uppercase tracking-widest font-bold">
                                    <th class="p-4">Hora / Fecha</th>
                                    <th class="p-4">Paciente</th>
                                    <th class="p-4">Comprobante</th>
                                    <th class="p-4">Método</th>
                                    <th class="p-4">Tipo Pago</th>
                                    <th class="p-4">Registrado por</th>
                                    <th class="p-4 text-right">Monto</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-100">
                                <?php if(empty($pagos)): ?>
                                <tr>
                                    <td colspan="7" class="p-8 text-center text-slate-400">
                                        <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-3 opacity-50"></i>
                                        <p class="font-bold">No hay pagos registrados en este periodo de tiempo.</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach($pagos as $p): 
                                        $hora = date('h:i A', strtotime($p['fecha_pago']));
                                        $fecha = date('d M Y', strtotime($p['fecha_pago']));
                                        
                                        $color_metodo = 'bg-slate-100 text-slate-600';
                                        if ($p['metodo_pago'] == 'Efectivo') $color_metodo = 'bg-emerald-100 text-emerald-700 border border-emerald-200';
                                        if ($p['metodo_pago'] == 'Tarjeta') $color_metodo = 'bg-blue-100 text-blue-700 border border-blue-200';
                                        if ($p['metodo_pago'] == 'Transferencia') $color_metodo = 'bg-indigo-100 text-indigo-700 border border-indigo-200';
                                        if ($p['metodo_pago'] == 'Yape/Plin') $color_metodo = 'bg-purple-100 text-purple-700 border border-purple-200';
                                    ?>
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="p-4">
                                            <div class="font-bold text-slate-800"><?php echo $hora; ?></div>
                                            <div class="text-xs text-slate-400 font-medium"><?php echo $fecha; ?></div>
                                        </td>
                                        <td class="p-4">
                                            <a href="paciente_detalle.php?id=<?php echo $p['paciente_id']; ?>" class="font-bold text-brand hover:underline flex items-center gap-2" title="Ir al perfil">
                                                <?php echo htmlspecialchars($p['paciente_nombre']); ?>
                                            </a>
                                            <div class="text-xs text-slate-400">DNI: <?php echo htmlspecialchars($p['paciente_dni']); ?></div>
                                        </td>
                                        <td class="p-4">
                                            <?php if ($p['comprobante_tipo'] != 'Ninguno'): ?>
                                                <div class="font-bold text-slate-700"><?php echo $p['comprobante_tipo']; ?></div>
                                                <div class="text-xs text-slate-500"><?php echo $p['comprobante_numero']; ?></div>
                                            <?php else: ?>
                                                <span class="text-slate-400 italic text-xs">Recibo Interno</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-4">
                                            <span class="px-2.5 py-1 rounded-md text-xs font-bold <?php echo $color_metodo; ?>">
                                                <?php echo $p['metodo_pago']; ?>
                                            </span>
                                        </td>
                                        <td class="p-4">
                                            <div class="text-slate-700 font-medium"><?php echo $p['tipo']; ?></div>
                                            <?php if($p['notas']): ?>
                                                <div class="text-[10px] text-slate-400 max-w-[150px] truncate" title="<?php echo htmlspecialchars($p['notas']); ?>">
                                                    <?php echo htmlspecialchars($p['notas']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-4 text-xs font-medium text-slate-500">
                                            <?php echo htmlspecialchars($p['registrado_nombre'] ?? 'Sistema'); ?>
                                        </td>
                                        <td class="p-4 text-right">
                                            <div class="font-black text-slate-800 text-base">S/ <?php echo number_format($p['monto'], 2); ?></div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();
    </script>
    
    <!-- Estilos para impresión -->
    <style type="text/css" media="print">
        @page { size: landscape; margin: 1cm; }
        body * { visibility: hidden; }
        main, main * { visibility: visible; }
        main { position: absolute; left: 0; top: 0; width: 100%; }
        main > div { padding: 0 !important; }
        header, .print\:hidden, #sys-sidebar, #sys-sidebar-overlay { display: none !important; }
        
        .shadow-sm, .shadow-md, .shadow-xl { box-shadow: none !important; border-radius: 8px !important; }
        .border-slate-200, .border-slate-100 { border-color: #cbd5e1 !important; }
        
        /* Forzar impresión de colores de fondo en navegadores */
        .bg-brand { background-color: #3a596a !important; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .bg-slate-50 { background-color: #f8fafc !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .bg-teal-100 { background-color: #ede8f7 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        
        /* Evitar saltos de página huérfanos */
        tr { page-break-inside: avoid; break-inside: avoid; }
        thead { display: table-header-group; }
        .max-w-7xl { max-width: 100% !important; width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .space-y-6 > * + * { margin-top: 12px !important; }
    </style>
</body>
</html>
