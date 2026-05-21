<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div id="sys-sidebar-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/50 z-40 hidden backdrop-blur-sm transition-opacity opacity-0 md:hidden"></div>
<aside id="sys-sidebar" class="fixed inset-y-0 left-0 w-64 bg-brand text-white flex flex-col shadow-2xl z-50 transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 ease-in-out shrink-0">
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
        <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl <?php echo ($current_page == 'dashboard.php') ? 'bg-white text-brand font-bold shadow-lg transition-transform hover:scale-105' : 'text-brand-secondary hover:bg-white/10 hover:text-white transition-colors'; ?>">
            <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
            Panel Principal
        </a>
        <a href="pacientes.php" class="flex items-center gap-3 px-4 py-3 rounded-xl <?php echo ($current_page == 'pacientes.php') ? 'bg-white text-brand font-bold shadow-lg transition-transform hover:scale-105' : 'text-brand-secondary hover:bg-white/10 hover:text-white transition-colors'; ?>">
            <i data-lucide="users" class="w-5 h-5"></i>
            Pacientes
        </a>
        <a href="agenda.php" class="flex items-center gap-3 px-4 py-3 rounded-xl <?php echo ($current_page == 'agenda.php') ? 'bg-white text-brand font-bold shadow-lg transition-transform hover:scale-105' : 'text-brand-secondary hover:bg-white/10 hover:text-white transition-colors'; ?>">
            <i data-lucide="calendar-days" class="w-5 h-5"></i>
            Agenda
        </a>
        <a href="presupuestos.php" class="flex items-center gap-3 px-4 py-3 rounded-xl <?php echo ($current_page == 'presupuestos.php') ? 'bg-white text-brand font-bold shadow-lg transition-transform hover:scale-105' : 'text-brand-secondary hover:bg-white/10 hover:text-white transition-colors'; ?>">
            <i data-lucide="file-text" class="w-5 h-5"></i>
            Presupuestos
        </a>
        <?php if(isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] !== 'Dentista'): ?>
        <a href="caja.php" class="flex items-center gap-3 px-4 py-3 rounded-xl <?php echo ($current_page == 'caja.php') ? 'bg-white text-brand font-bold shadow-lg transition-transform hover:scale-105' : 'text-brand-secondary hover:bg-white/10 hover:text-white transition-colors'; ?>">
            <i data-lucide="calculator" class="w-5 h-5"></i>
            Caja / Reportes
        </a>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'Admin'): ?>
        <a href="personal.php" class="flex items-center gap-3 px-4 py-3 rounded-xl <?php echo ($current_page == 'personal.php') ? 'bg-white text-brand font-bold shadow-lg transition-transform hover:scale-105' : 'text-brand-secondary hover:bg-white/10 hover:text-white transition-colors'; ?>">
            <i data-lucide="shield-check" class="w-5 h-5"></i>
            Personal y Accesos
        </a>
        <?php endif; ?>

    </nav>
    
    <div class="p-4 border-t border-white/10 shrink-0">
        <a href="logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-brand-secondary hover:bg-red-500 hover:text-white transition-colors font-bold">
            <i data-lucide="log-out" class="w-5 h-5"></i>
            Cerrar Sesión
        </a>
    </div>
</aside>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sys-sidebar');
        const overlay = document.getElementById('sys-sidebar-overlay');
        
        if (sidebar.classList.contains('-translate-x-full')) {
            // Abrir
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
            setTimeout(() => overlay.classList.remove('opacity-0'), 10);
        } else {
            // Cerrar
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('opacity-0');
            setTimeout(() => overlay.classList.add('hidden'), 300);
        }
    }
</script>
