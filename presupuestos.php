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

    <aside class="w-64 bg-brand text-white flex flex-col shadow-2xl z-20 hidden md:flex shrink-0">
        <div class="h-20 flex items-center px-6 border-b border-white/10 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-brand shadow-md">
                    <i data-lucide="smile" class="w-6 h-6"></i>
                </div>
                <div class="flex flex-col">
                    <span class="text-xl font-black tracking-widest text-white uppercase leading-tight">MAHUDENT</span>
                    <span class="text-[9px] text-brand-secondary tracking-widest uppercase">Clínica Odontológica</span>
                </div>
            </div>
        </div>

        <nav class="flex-1 py-6 px-3 space-y-2 overflow-y-auto">
            <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-brand-secondary hover:bg-white/10 hover:text-white transition-colors">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Panel Principal
            </a>
            <a href="pacientes.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-brand-secondary hover:bg-white/10 hover:text-white transition-colors">
                <i data-lucide="users" class="w-5 h-5"></i> Pacientes
            </a>
            <a href="agenda.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-brand-secondary hover:bg-white/10 hover:text-white transition-colors">
                <i data-lucide="calendar-days" class="w-5 h-5"></i> Agenda
            </a>
            <a href="presupuestos.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-white text-brand font-bold shadow-lg transition-transform hover:scale-105">
                <i data-lucide="file-text" class="w-5 h-5"></i> Presupuestos
            </a>
        </nav>
        
        <div class="p-4 border-t border-white/10 shrink-0">
            <a href="logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-brand-secondary hover:bg-red-500 hover:text-white transition-colors font-bold">
                <i data-lucide="log-out" class="w-5 h-5"></i> Cerrar Sesión
            </a>
        </div>
    </aside>

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        
        <header class="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-8 shadow-sm shrink-0">
            <h1 class="text-xl font-black text-slate-800 uppercase tracking-tight">Gestión de Presupuestos</h1>
            
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-3 border-l pl-6">
                    <img src="https://images.unsplash.com/photo-1559839734-2b71ea197ec2?auto=format&fit=crop&w=100&q=80" alt="Dr. Perfil" class="w-10 h-10 rounded-full object-cover border-2 border-teal-500">
                    <div class="hidden md:block">
                        <p class="text-sm font-bold text-slate-800"><?php echo $_SESSION['usuario_nombre']; ?></p>
                        <p class="text-xs text-brand-accent font-medium"><?php echo $_SESSION['usuario_rol']; ?></p>
                    </div>
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Pendientes</p>
                    <div class="flex items-end gap-2">
                        <span class="text-3xl font-black text-slate-800">S/ 12,450</span>
                        <span class="text-xs font-bold text-orange-500 mb-1.5 flex items-center gap-1">
                            <i data-lucide="clock" class="w-3 h-3"></i> 14 Docs
                        </span>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Aprobados (Mes)</p>
                    <div class="flex items-end gap-2">
                        <span class="text-3xl font-black text-brand">S/ 45,800</span>
                        <span class="text-xs font-bold text-emerald-500 mb-1.5 flex items-center gap-1">
                            <i data-lucide="trending-up" class="w-3 h-3"></i> +12%
                        </span>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Tasa de Cierre</p>
                    <div class="flex items-end gap-2">
                        <span class="text-3xl font-black text-slate-800">68%</span>
                        <div class="w-24 h-2 bg-slate-100 rounded-full mb-3 overflow-hidden">
                            <div class="bg-brand h-full w-[68%]"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-slate-50/50">
                    <div class="relative w-full md:w-96">
                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                        <input type="text" placeholder="Buscar por paciente o N° presupuesto..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand/20">
                    </div>
                    <div class="flex gap-2 w-full md:w-auto">
                        <button class="flex-1 md:flex-none px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-600 hover:bg-slate-50 flex items-center justify-center gap-2">
                            <i data-lucide="filter" class="w-4 h-4"></i> Filtros
                        </button>
                        <button class="flex-1 md:flex-none px-4 py-2.5 bg-brand text-white rounded-xl text-sm font-bold shadow-lg hover:bg-teal-800 transition flex items-center justify-center gap-2">
                            <i data-lucide="plus" class="w-4 h-4"></i> Nuevo Presupuesto
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
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
                            <tr class="hover:bg-slate-50/80 transition-colors group">
                                <td class="px-6 py-4 text-sm font-bold text-brand">#PR-2026-045</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500">AR</div>
                                        <span class="text-sm font-bold text-slate-700">Ana María Robles</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-500">12 Abr 2026</td>
                                <td class="px-6 py-4 text-sm font-black text-slate-800 text-right">S/ 1,200.00</td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-center">
                                        <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-bold uppercase tracking-wider">Aprobado</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button class="p-2 hover:bg-brand-light rounded-lg text-slate-400 hover:text-brand transition"><i data-lucide="more-vertical" class="w-4 h-4"></i></button>
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-50/80 transition-colors group">
                                <td class="px-6 py-4 text-sm font-bold text-brand">#PR-2026-044</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500">CP</div>
                                        <span class="text-sm font-bold text-slate-700">Carlos Pantoja</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-500">10 Abr 2026</td>
                                <td class="px-6 py-4 text-sm font-black text-slate-800 text-right">S/ 4,500.00</td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-center">
                                        <span class="px-3 py-1 rounded-full bg-orange-100 text-orange-700 text-[10px] font-bold uppercase tracking-wider">Pendiente</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button class="p-2 hover:bg-brand-light rounded-lg text-slate-400 hover:text-brand transition"><i data-lucide="more-vertical" class="w-4 h-4"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-6 border-t border-slate-100 flex items-center justify-between bg-slate-50/30 text-xs font-bold text-slate-500 uppercase">
                    <span>Mostrando 2 de 154 presupuestos</span>
                    <div class="flex gap-2">
                        <button class="w-8 h-8 border border-slate-200 rounded flex items-center justify-center hover:bg-white transition"><i data-lucide="chevron-left" class="w-4 h-4"></i></button>
                        <button class="w-8 h-8 bg-brand text-white rounded flex items-center justify-center">1</button>
                        <button class="w-8 h-8 border border-slate-200 rounded flex items-center justify-center hover:bg-white transition">2</button>
                        <button class="w-8 h-8 border border-slate-200 rounded flex items-center justify-center hover:bg-white transition"><i data-lucide="chevron-right" class="w-4 h-4"></i></button>
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