<?php
require_once 'includes/auth_guard.php';if (!isset($_SESSION['usuario_id']) || !isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

require_once 'config/conexion.php';
require_once 'controllers/PresupuestoController.php';

$controller = new PresupuestoController();
$presupuesto_id = intval($_GET['id']);
$resumen = $controller->getResumenFinanciero($presupuesto_id);

if (!$resumen) {
    die("Presupuesto no encontrado.");
}

$p = $resumen['presupuesto'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presupuesto #<?php echo str_pad($p['id'], 5, '0', STR_PAD_LEFT); ?> - MahuDent</title>
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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700;900&display=swap');
        body { font-family: 'Montserrat', sans-serif; background: #e2e8f0; }
        .hoja {
            background: white;
            width: 210mm;
            min-height: 297mm;
            margin: 20mm auto;
            padding: 20mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
        }
        .hoja::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-10deg);
            width: 140mm;
            height: 140mm;
            background-image: url('assets/logo_watermark.png');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            pointer-events: none;
            z-index: 0;
        }
        @media print {
            body { background: white; margin: 0; padding: 0; }
            .hoja { margin: 0; padding: 10mm; box-shadow: none; width: 100%; min-height: auto; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="fixed top-4 right-4 no-print flex gap-2">
        <button onclick="window.print()" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-6 rounded-lg shadow-lg">🖨️ Imprimir</button>
        <button onclick="window.close()" class="bg-slate-800 hover:bg-slate-900 text-white font-bold py-2 px-6 rounded-lg shadow-lg">Cerrar</button>
    </div>

    <div class="hoja relative">
        <!-- Encabezado -->
        <div class="flex justify-between items-start border-b-2 border-teal-600 pb-6 mb-8">
            <div class="flex items-center gap-4">
                <img src="assets/logo_icon.png" alt="Logo" class="w-16 h-16 rounded-xl object-contain p-2 border border-slate-200 bg-white">
                <div>
                    <img src="assets/logo_text_dark.png" alt="MahuDent" class="h-8 w-auto object-contain">
                    <p class="text-slate-500 font-bold text-xs uppercase tracking-wider mt-1">Clínica Odontológica Especializada</p>
                    <p class="text-slate-400 text-xs mt-0.5">Jr. San Sebastián 116 | Cel/WhatsApp: 941124848</p>
                </div>
            </div>
            <div class="text-right">
                <h2 class="text-2xl font-black text-slate-800">PRESUPUESTO</h2>
                <p class="text-teal-600 font-bold text-lg">#PR-<?php echo str_pad($p['id'], 5, '0', STR_PAD_LEFT); ?></p>
                <p class="text-slate-500 text-sm mt-2"><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($p['fecha_emision'])); ?></p>
                <p class="text-slate-500 text-sm"><strong>Válido hasta:</strong> <?php echo date('d/m/Y', strtotime($p['fecha_vigencia'])); ?></p>
            </div>
        </div>

        <!-- Datos del Paciente -->
        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 mb-8">
            <h3 class="font-bold text-slate-800 uppercase text-xs tracking-widest mb-3 border-b border-slate-200 pb-2">Datos del Paciente</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div><span class="text-slate-500">Paciente:</span> <strong class="text-slate-800"><?php echo htmlspecialchars($p['paciente_nombre']); ?></strong></div>
                <div><span class="text-slate-500">DNI:</span> <strong class="text-slate-800"><?php echo htmlspecialchars($p['paciente_dni']); ?></strong></div>
                <div><span class="text-slate-500">Doctor Asignado:</span> <strong class="text-slate-800"><?php echo htmlspecialchars($p['doctor_nombre'] ?? 'Sin asignar'); ?></strong></div>
            </div>
        </div>

        <!-- Detalles del Tratamiento -->
        <h3 class="font-bold text-slate-800 uppercase text-xs tracking-widest mb-3">Detalle de Tratamientos</h3>
        <table class="w-full text-sm mb-8 border-collapse">
            <thead>
                <tr class="bg-teal-600 text-white">
                    <th class="p-3 text-left font-bold border border-teal-700">Descripción</th>
                    <th class="p-3 text-center font-bold border border-teal-700 w-16">Pieza</th>
                    <th class="p-3 text-center font-bold border border-teal-700 w-16">Cant.</th>
                    <th class="p-3 text-right font-bold border border-teal-700 w-28">P. Unitario</th>
                    <th class="p-3 text-right font-bold border border-teal-700 w-28">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 border-b border-slate-200 text-slate-700">
                <?php if (empty($p['items'])): ?>
                    <tr><td colspan="5" class="p-4 text-center text-slate-400">No hay ítems registrados</td></tr>
                <?php else: ?>
                    <?php foreach ($p['items'] as $item): 
                        $precio = $item['precio_ajustado'] ?? $item['precio_unitario'];
                    ?>
                    <tr>
                        <td class="p-3 border-x border-slate-200"><?php echo htmlspecialchars($item['descripcion']); ?></td>
                        <td class="p-3 text-center border-x border-slate-200"><?php echo $item['diente_numero'] ?: '-'; ?></td>
                        <td class="p-3 text-center border-x border-slate-200"><?php echo $item['cantidad']; ?></td>
                        <td class="p-3 text-right border-x border-slate-200">S/ <?php echo number_format($precio, 2); ?></td>
                        <td class="p-3 text-right border-x border-slate-200 font-bold">S/ <?php echo number_format($item['subtotal'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Totales y Pagos -->
        <div class="flex justify-between items-start mb-8">
            <div class="w-1/2">
                <?php if (!empty($resumen['pagos'])): ?>
                <h3 class="font-bold text-slate-800 uppercase text-xs tracking-widest mb-2 border-b border-slate-200 pb-1">Historial de Pagos Realizados</h3>
                <table class="w-full text-xs text-left text-slate-600 border border-slate-200 rounded-lg overflow-hidden">
                    <thead class="bg-slate-100">
                        <tr>
                            <th class="p-2 border-b border-slate-200">Fecha</th>
                            <th class="p-2 border-b border-slate-200">Tipo</th>
                            <th class="p-2 border-b border-slate-200">Método</th>
                            <th class="p-2 border-b border-slate-200 text-right">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($resumen['pagos'] as $pago): ?>
                        <tr>
                            <td class="p-2"><?php echo date('d/m/Y H:i', strtotime($pago['fecha_pago'])); ?></td>
                            <td class="p-2"><?php echo htmlspecialchars($pago['tipo']); ?></td>
                            <td class="p-2"><?php echo htmlspecialchars($pago['metodo_pago']); ?></td>
                            <td class="p-2 text-right font-bold text-emerald-600">S/ <?php echo number_format($pago['monto'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <div class="w-72 bg-slate-50 p-4 rounded-xl border border-slate-200">
                <div class="flex justify-between text-sm mb-2 text-slate-600">
                    <span>Subtotal:</span>
                    <span class="font-bold">S/ <?php echo number_format($p['subtotal'], 2); ?></span>
                </div>
                <div class="flex justify-between text-sm mb-3 text-amber-600">
                    <span>Descuento (<?php echo floatval($p['descuento_porcentaje']); ?>%):</span>
                    <span class="font-bold">- S/ <?php echo number_format($p['descuento_monto'], 2); ?></span>
                </div>
                <div class="flex justify-between text-lg border-t border-slate-200 pt-3 text-teal-800 mb-2">
                    <span class="font-black uppercase">TOTAL:</span>
                    <span class="font-black">S/ <?php echo number_format($p['total'], 2); ?></span>
                </div>
                
                <?php if ($resumen['total_pagado'] > 0): ?>
                <div class="flex justify-between text-sm text-emerald-600 mb-1 border-t border-slate-200 pt-2">
                    <span>A Cuenta (Pagado):</span>
                    <span class="font-bold">S/ <?php echo number_format($resumen['total_pagado'], 2); ?></span>
                </div>
                <div class="flex justify-between text-sm text-red-600">
                    <span class="font-bold">SALDO DEUDOR:</span>
                    <span class="font-black">S/ <?php echo number_format($resumen['saldo_pendiente'], 2); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Condiciones -->
        <div class="border-t-2 border-slate-100 pt-6 text-xs text-slate-500">
            <p class="font-bold mb-1 text-slate-700">Condiciones del Presupuesto:</p>
            <ul class="list-disc pl-4 space-y-1">
                <li>El presente presupuesto tiene una validez de 30 días a partir de la fecha de emisión.</li>
                <li>Los precios están sujetos a cambios si durante el tratamiento clínico se descubren condiciones no previstas.</li>
                <li>Se requiere un adelanto para el inicio de tratamientos prolongados (ortodoncia, prótesis, implantes).</li>
                <li>Los pagos pueden realizarse en Efectivo, Tarjeta, Transferencia Bancaria o Yape/Plin.</li>
            </ul>
        </div>
        
        <div class="mt-16 pt-8 border-t border-slate-200 text-center text-xs text-slate-400">
            Generado por Sistema Odontológico MahuDent - <?php echo date('d/m/Y H:i'); ?>
        </div>
    </div>

    <script>
        // Auto-imprimir al abrir si tiene un parámetro
        if (window.location.search.includes('print=1')) {
            setTimeout(() => window.print(), 500);
        }
    </script>
</body>
</html>
