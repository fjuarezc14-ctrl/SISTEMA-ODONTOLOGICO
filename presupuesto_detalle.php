<?php
session_start();
// Si no hay sesión iniciada, redirige al login
if(!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presupuestos - MahuDent</title>
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
        
        <?php $page_title = 'Detalles'; include 'includes/header.php'; ?>

        <div class="flex-1 overflow-y-auto p-8">
            <div class="flex flex-col lg:flex-row gap-8">
                
                <div class="w-full lg:w-1/3 xl:w-1/4 space-y-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="bg-brand h-24 relative">
                            <div class="absolute -bottom-10 left-6">
                                <div class="w-20 h-20 bg-white rounded-full p-1 shadow-md border-2 border-brand-secondary">
                                    <div class="w-full h-full bg-slate-200 rounded-full flex items-center justify-center text-slate-500 font-bold text-2xl">AR</div>
                                </div>
                            </div>
                            <div class="absolute top-4 right-4 bg-green-500 text-white text-[10px] font-bold px-2 py-1 rounded-full uppercase tracking-wider flex items-center gap-1 shadow-sm">
                                <div class="w-1.5 h-1.5 bg-white rounded-full"></div> Activo
                            </div>
                        </div>
                        <div class="pt-12 px-6 pb-6">
                            <h2 class="text-xl font-black text-slate-800">Ana María Robles</h2>
                            <p class="text-sm text-slate-500 font-medium mb-4">DNI: 76543210</p>
                            <div class="space-y-3 pt-4 border-t border-slate-100">
                                <div class="flex items-center gap-3 text-sm text-slate-600">
                                    <i data-lucide="phone" class="w-4 h-4 text-slate-400"></i>
                                    <span class="font-medium">+51 987 654 321</span>
                                </div>
                                <div class="flex items-center gap-3 text-sm text-slate-600">
                                    <i data-lucide="mail" class="w-4 h-4 text-slate-400"></i>
                                    <span class="font-medium">ana.robles@email.com</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="w-full lg:w-2/3 xl:w-3/4 flex flex-col gap-6">
                    
                    <div class="flex gap-2 border-b border-slate-200 shrink-0">
                        <a href="pacientes.php" class="px-6 py-3 border-b-2 border-transparent text-slate-500 font-bold text-sm hover:text-slate-700 hover:bg-slate-50 rounded-t-lg transition">Historia y Odontograma</a>
                        <button class="px-6 py-3 border-b-2 border-brand text-brand font-bold text-sm bg-brand-light/30 rounded-t-lg transition">Presupuestos (2)</button>
                        <a href="radiografias.php" class="px-6 py-3 border-b-2 border-transparent text-slate-500 font-bold text-sm hover:text-slate-700 hover:bg-slate-50 rounded-t-lg transition">Radiografías / Archivos</a>
                    </div>

                    <div class="flex flex-col xl:flex-row gap-6">
                        
                        <div class="w-full xl:w-1/3 flex flex-col gap-4">
                            <button class="w-full bg-brand hover:bg-teal-800 text-white px-4 py-3 rounded-xl font-bold flex items-center justify-center gap-2 shadow-sm transition">
                                <i data-lucide="plus" class="w-5 h-5"></i> Nuevo Presupuesto
                            </button>

                            <div class="bg-white rounded-xl shadow-sm border border-slate-200 flex flex-col overflow-hidden">
                                <div class="p-4 border-b border-slate-100 bg-brand-light/20 border-l-4 border-l-brand cursor-pointer hover:bg-slate-50 transition">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="font-bold text-slate-800">#PR-1042</h4>
                                        <span class="bg-orange-100 text-orange-600 text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider">Pendiente</span>
                                    </div>
                                    <p class="text-xs text-slate-500 mb-2">Creado: 28 Abr 2026</p>
                                    <p class="text-lg font-black text-brand">S/ 1,200.00</p>
                                </div>
                                
                                <div class="p-4 border-b border-slate-100 border-l-4 border-l-transparent cursor-pointer hover:bg-slate-50 transition opacity-70">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="font-bold text-slate-800">#PR-0981</h4>
                                        <span class="bg-emerald-100 text-emerald-600 text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider">Aprobado</span>
                                    </div>
                                    <p class="text-xs text-slate-500 mb-2">Creado: 05 Mar 2026</p>
                                    <p class="text-lg font-black text-slate-700">S/ 150.00</p>
                                </div>
                            </div>
                        </div>

                        <div class="w-full xl:w-2/3 bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
                            
                            <div class="flex justify-between items-start border-b border-slate-200 pb-6 mb-6">
                                <div>
                                    <h3 class="text-2xl font-black text-slate-800">Presupuesto #PR-1042</h3>
                                    <p class="text-sm text-slate-500 mt-1"><?php echo $_SESSION['usuario_nombre']; ?> - MahuDent S.A.C.</p>
                                </div>
                                <div class="flex gap-2">
                                    <button class="p-2 text-slate-400 hover:text-brand hover:bg-slate-50 rounded-lg border border-slate-200 transition tooltip" title="Imprimir PDF">
                                        <i data-lucide="printer" class="w-5 h-5"></i>
                                    </button>
                                    <button class="p-2 text-slate-400 hover:text-brand hover:bg-slate-50 rounded-lg border border-slate-200 transition tooltip" title="Enviar por WhatsApp">
                                        <i data-lucide="message-circle" class="w-5 h-5"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                                            <th class="p-3 font-bold rounded-tl-lg">Tratamiento / Descripción</th>
                                            <th class="p-3 font-bold">Pieza</th>
                                            <th class="p-3 font-bold text-right">Cant.</th>
                                            <th class="p-3 font-bold text-right rounded-tr-lg">Precio (S/)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-sm text-slate-700">
                                        <tr class="border-b border-slate-100">
                                            <td class="p-3 font-medium">Endodoncia Multirradicular<br><span class="text-xs text-slate-400">Tratamiento de conductos</span></td>
                                            <td class="p-3"><span class="bg-slate-100 text-slate-600 px-2 py-1 rounded font-bold text-xs">36</span></td>
                                            <td class="p-3 text-right">1</td>
                                            <td class="p-3 text-right font-bold">400.00</td>
                                        </tr>
                                        <tr class="border-b border-slate-100">
                                            <td class="p-3 font-medium">Corona de Porcelana Pura<br><span class="text-xs text-slate-400">Rehabilitación post-endodoncia</span></td>
                                            <td class="p-3"><span class="bg-slate-100 text-slate-600 px-2 py-1 rounded font-bold text-xs">36</span></td>
                                            <td class="p-3 text-right">1</td>
                                            <td class="p-3 text-right font-bold">800.00</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-6 flex justify-end">
                                <div class="w-1/2 min-w-[250px] bg-slate-50 p-4 rounded-xl border border-slate-100">
                                    <div class="flex justify-between text-sm mb-2 text-slate-600">
                                        <span>Subtotal</span>
                                        <span class="font-bold">S/ 1,200.00</span>
                                    </div>
                                    <div class="flex justify-between text-sm mb-3 text-slate-600">
                                        <span>Descuento</span>
                                        <span class="font-bold text-red-500">- S/ 0.00</span>
                                    </div>
                                    <div class="flex justify-between text-lg pt-3 border-t border-slate-200 text-slate-800">
                                        <span class="font-black">Total a Pagar</span>
                                        <span class="font-black text-brand">S/ 1,200.00</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8 flex gap-4">
                                <button class="flex-1 bg-brand hover:bg-teal-800 text-white py-3 rounded-xl font-bold shadow-md transition">
                                    Aprobar Presupuesto
                                </button>
                                <button class="px-6 py-3 border border-slate-300 text-slate-600 hover:bg-slate-50 rounded-xl font-bold transition">
                                    Editar
                                </button>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
