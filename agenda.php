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
    <title>Agenda Interactiva - MahuDent</title>
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
        
        /* CSS personalizado para la cuadrícula del calendario */
        .calendar-grid {
            display: grid;
            grid-template-columns: 60px repeat(6, 1fr); /* Hora + 6 días (Lun-Sáb) */
            min-width: 800px; /* Para asegurar el scroll en móviles */
        }
        
        /* Ocultar scrollbar para un look más limpio */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        
        <?php $page_title = 'Agenda'; include 'includes/header.php'; ?>

        <div class="flex-1 flex flex-col p-8 overflow-hidden">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 shrink-0">
                <div class="flex items-center gap-4">
                    <h1 class="text-3xl font-black text-slate-800">Agenda</h1>
                    <div class="h-6 w-px bg-slate-300 hidden md:block"></div>
                    <div class="flex items-center gap-3">
                        <button class="p-2 border border-slate-200 rounded-lg hover:bg-slate-50 text-slate-600 transition">
                            <i data-lucide="chevron-left" class="w-5 h-5"></i>
                        </button>
                        <span class="text-lg font-bold text-slate-700 w-40 text-center">Abril 2026</span>
                        <button class="p-2 border border-slate-200 rounded-lg hover:bg-slate-50 text-slate-600 transition">
                            <i data-lucide="chevron-right" class="w-5 h-5"></i>
                        </button>
                        <button class="px-4 py-2 bg-slate-100 text-slate-600 font-bold rounded-lg hover:bg-slate-200 transition text-sm">
                            Hoy
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="flex bg-slate-100 p-1 rounded-lg border border-slate-200">
                        <button class="px-4 py-1.5 text-sm font-bold rounded-md text-slate-500 hover:text-brand transition">Día</button>
                        <button class="px-4 py-1.5 text-sm font-bold rounded-md bg-white text-brand shadow-sm border border-slate-200">Semana</button>
                        <button class="px-4 py-1.5 text-sm font-bold rounded-md text-slate-500 hover:text-brand transition">Mes</button>
                    </div>
                    <button class="bg-brand hover:bg-teal-800 text-white px-5 py-2.5 rounded-xl font-bold flex items-center gap-2 shadow-lg transition-all hover:scale-105 hover:shadow-teal-900/30">
                        <i data-lucide="plus" class="w-5 h-5"></i>
                        Nueva Cita
                    </button>
                </div>
            </div>

            <div class="flex-1 bg-white rounded-2xl shadow-sm border border-slate-200 flex flex-col overflow-hidden">
                
                <div class="calendar-grid border-b border-slate-200 bg-slate-50 shrink-0">
                    <div class="p-3 border-r border-slate-200">
                        <span class="text-xs text-slate-400 font-bold">HORA</span>
                    </div>
                    <div class="p-3 border-r border-slate-200 text-center">
                        <p class="text-xs font-bold text-slate-500 uppercase">Lun</p>
                        <p class="text-xl font-black text-slate-700">27</p>
                    </div>
                    <div class="p-3 border-r border-slate-200 text-center relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-full h-1 bg-brand"></div>
                        <p class="text-xs font-bold text-brand uppercase mt-1">Mar</p>
                        <p class="text-xl font-black text-brand bg-brand-light w-10 h-10 mx-auto flex items-center justify-center rounded-full mt-1">28</p>
                    </div>
                    <div class="p-3 border-r border-slate-200 text-center">
                        <p class="text-xs font-bold text-slate-500 uppercase">Mié</p>
                        <p class="text-xl font-black text-slate-700">29</p>
                    </div>
                    <div class="p-3 border-r border-slate-200 text-center">
                        <p class="text-xs font-bold text-slate-500 uppercase">Jue</p>
                        <p class="text-xl font-black text-slate-700">30</p>
                    </div>
                    <div class="p-3 border-r border-slate-200 text-center">
                        <p class="text-xs font-bold text-slate-500 uppercase">Vie</p>
                        <p class="text-xl font-black text-slate-700">01</p>
                    </div>
                    <div class="p-3 text-center">
                        <p class="text-xs font-bold text-slate-500 uppercase">Sáb</p>
                        <p class="text-xl font-black text-slate-700">02</p>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto no-scrollbar relative">
                    
                    <div class="absolute w-full flex items-center z-10" style="top: 150px;">
                        <div class="w-14 text-right pr-2">
                            <span class="text-[10px] font-bold text-red-500">10:30</span>
                        </div>
                        <div class="flex-1 border-t-2 border-red-500 border-dashed relative">
                            <div class="absolute -left-1 -top-1.5 w-3 h-3 bg-red-500 rounded-full"></div>
                        </div>
                    </div>

                    <div class="calendar-grid">
                        
                        <div class="border-r border-slate-200 flex flex-col bg-white">
                            <div class="h-[60px] border-b border-slate-100 flex justify-end pr-2 pt-1"><span class="text-xs text-slate-400 font-medium">08:00</span></div>
                            <div class="h-[60px] border-b border-slate-100 flex justify-end pr-2 pt-1"><span class="text-xs text-slate-400 font-medium">09:00</span></div>
                            <div class="h-[60px] border-b border-slate-100 flex justify-end pr-2 pt-1"><span class="text-xs text-slate-400 font-medium">10:00</span></div>
                            <div class="h-[60px] border-b border-slate-100 flex justify-end pr-2 pt-1"><span class="text-xs text-slate-400 font-medium">11:00</span></div>
                            <div class="h-[60px] border-b border-slate-100 flex justify-end pr-2 pt-1"><span class="text-xs text-slate-400 font-medium">12:00</span></div>
                            <div class="h-[60px] border-b border-slate-100 flex justify-end pr-2 pt-1"><span class="text-xs text-slate-400 font-medium">13:00</span></div>
                            <div class="h-[60px] border-b border-slate-100 flex justify-end pr-2 pt-1"><span class="text-xs text-slate-400 font-medium">14:00</span></div>
                            <div class="h-[60px] border-b border-slate-100 flex justify-end pr-2 pt-1"><span class="text-xs text-slate-400 font-medium">15:00</span></div>
                        </div>

                        <div class="border-r border-slate-200 relative bg-white">
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="absolute w-[95%] left-[2.5%] top-[125px] h-[55px] bg-emerald-50 border-l-4 border-emerald-500 rounded p-1.5 shadow-sm hover:shadow-md transition cursor-pointer z-0">
                                <p class="text-xs font-bold text-emerald-800 leading-tight">Juan Pérez</p>
                                <p class="text-[10px] text-emerald-600">Revisión General</p>
                            </div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                        </div>

                        <div class="border-r border-slate-200 relative bg-slate-50/50">
                            <div class="absolute w-[95%] left-[2.5%] top-[10px] h-[80px] bg-brand-light border-l-4 border-brand rounded p-1.5 shadow-sm hover:shadow-md transition cursor-pointer z-0">
                                <p class="text-[10px] font-bold text-brand uppercase mb-0.5">08:15 - 09:30</p>
                                <p class="text-xs font-bold text-teal-900 leading-tight">Ana María Robles</p>
                                <p class="text-[10px] text-brand">Profilaxis profunda</p>
                            </div>
                            
                            <div class="absolute w-[95%] left-[2.5%] top-[190px] h-[55px] bg-orange-50 border-l-4 border-orange-400 rounded p-1.5 shadow-sm hover:shadow-md transition cursor-pointer z-0">
                                <p class="text-xs font-bold text-orange-900 leading-tight">Carlos Mendoza</p>
                                <p class="text-[10px] text-orange-700">Sin confirmar</p>
                            </div>

                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                        </div>

                        <div class="border-r border-slate-200 relative bg-white">
                            <div class="absolute w-[95%] left-[2.5%] top-[245px] h-[115px] bg-slate-100 border-l-4 border-slate-400 rounded p-2 flex items-center justify-center cursor-not-allowed z-0 opacity-70">
                                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Hora de Almuerzo</p>
                            </div>
                            
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                        </div>

                        <div class="border-r border-slate-200 relative bg-white">
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                        </div>

                        <div class="border-r border-slate-200 relative bg-white">
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="absolute w-[95%] left-[2.5%] top-[65px] h-[115px] bg-blue-50 border-l-4 border-blue-500 rounded p-1.5 shadow-sm hover:shadow-md transition cursor-pointer z-0">
                                <p class="text-[10px] font-bold text-blue-600 uppercase mb-0.5">09:00 - 11:00</p>
                                <p class="text-xs font-bold text-blue-900 leading-tight">Luis Álvarez</p>
                                <p class="text-[10px] text-blue-700">Endodoncia 1ra fase</p>
                            </div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                        </div>

                        <div class="relative bg-slate-50">
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                            <div class="h-[60px] border-b border-slate-100"></div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </main>

    <script>
        // Inicializar iconos
        lucide.createIcons();
    </script>
</body>
</html>
