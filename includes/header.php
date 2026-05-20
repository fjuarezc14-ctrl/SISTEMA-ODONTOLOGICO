<?php
$page_title = isset($page_title) ? $page_title : '';
$show_search = isset($show_search) ? $show_search : false;

// Obtener notificaciones dinámicas si existe la conexión
$notificaciones = [];
if (isset($conn)) {
    // 1. Citas de hoy
    $hoy = date('Y-m-d');
    $stmtCitas = $conn->prepare("SELECT COUNT(*) as total FROM citas WHERE fecha = ? AND estado IN ('Pendiente', 'Confirmada')");
    if ($stmtCitas) {
        $stmtCitas->bind_param("s", $hoy);
        $stmtCitas->execute();
        $totalCitas = $stmtCitas->get_result()->fetch_assoc()['total'];
        if ($totalCitas > 0) {
            $notificaciones[] = [
                'tipo' => 'citas',
                'titulo' => 'Citas de hoy',
                'descripcion' => "Tienes $totalCitas " . ($totalCitas == 1 ? "cita pendiente" : "citas pendientes") . " para hoy.",
                'icono' => 'calendar',
                'color' => 'text-blue-600 bg-blue-50',
                'link' => 'agenda.php'
            ];
        }
    }

    // 2. Presupuestos sin pagar o borradores
    $resultPres = $conn->query("SELECT COUNT(*) as total FROM presupuestos WHERE estado = 'Enviado'");
    if ($resultPres) {
        $totalPres = $resultPres->fetch_assoc()['total'];
        if ($totalPres > 0) {
            $notificaciones[] = [
                'tipo' => 'presupuestos',
                'titulo' => 'Presupuestos enviados',
                'descripcion' => "Hay $totalPres " . ($totalPres == 1 ? "presupuesto esperando" : "presupuestos esperando") . " aprobación.",
                'icono' => 'file-text',
                'color' => 'text-amber-600 bg-amber-50',
                'link' => 'presupuestos.php'
            ];
        }
    }
}

// Si no hay notificaciones, poner un mensaje por defecto
if (empty($notificaciones)) {
    $notificaciones[] = [
        'tipo' => 'info',
        'titulo' => '¡Todo al día!',
        'descripcion' => 'No tienes alertas ni notificaciones pendientes hoy.',
        'icono' => 'check-circle',
        'color' => 'text-teal-600 bg-teal-50',
        'link' => '#'
    ];
}

$notif_count = count($notificaciones);
if (isset($notificaciones[0]['tipo']) && $notificaciones[0]['tipo'] === 'info') {
    $notif_count = 0;
}
?>
<header class="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-8 shadow-sm shrink-0 relative z-40">
    <!-- Buscador interactivo -->
    <?php if ($show_search): ?>
        <div class="flex items-center gap-4 w-1/2 relative">
            <i data-lucide="search" class="w-5 h-5 text-slate-400"></i>
            <input type="text" id="sys-search-input" placeholder="Buscar pacientes por nombre o DNI..." class="w-full bg-transparent outline-none text-slate-600 placeholder-slate-400 font-semibold text-sm">
            
            <!-- Resultados flotantes -->
            <div id="sys-search-results" class="absolute left-0 right-0 top-full mt-2 bg-white rounded-2xl shadow-xl border border-slate-100 hidden max-h-80 overflow-y-auto z-50">
                <div class="p-3 text-xs font-bold text-slate-400 border-b border-slate-50 uppercase tracking-wider">Resultados de búsqueda</div>
                <div id="sys-search-items" class="divide-y divide-slate-50">
                    <!-- Dinámico -->
                </div>
            </div>
        </div>
    <?php else: ?>
        <h1 class="text-xl font-black text-slate-800 uppercase tracking-tight"><?php echo htmlspecialchars($page_title); ?></h1>
    <?php endif; ?>
    
    <div class="flex items-center gap-6">
        <!-- Notificaciones -->
        <div class="relative">
            <button id="btn-notifications" class="relative text-slate-400 hover:text-brand transition-colors p-2 rounded-xl hover:bg-slate-50">
                <i data-lucide="bell" class="w-6 h-6"></i>
                <?php if ($notif_count > 0): ?>
                    <span class="absolute top-1.5 right-1.5 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white animate-pulse"></span>
                <?php endif; ?>
            </button>

            <!-- Panel de Notificaciones Flotante -->
            <div id="panel-notifications" class="absolute right-0 top-full mt-3 bg-white rounded-3xl shadow-xl border border-slate-100 w-80 hidden flex-col overflow-hidden z-50 transition-all transform scale-95 opacity-0 duration-200 origin-top-right">
                <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <span class="font-extrabold text-slate-800 text-sm">Notificaciones</span>
                    <?php if ($notif_count > 0): ?>
                        <span class="bg-red-100 text-red-600 text-[10px] font-black px-2 py-0.5 rounded-full uppercase tracking-wider"><?php echo $notif_count; ?> Alertas</span>
                    <?php endif; ?>
                </div>
                <div class="divide-y divide-slate-50 max-h-72 overflow-y-auto">
                    <?php foreach ($notificaciones as $n): ?>
                        <a href="<?php echo $n['link']; ?>" class="flex gap-4 p-4 hover:bg-slate-50 transition-colors">
                            <div class="w-10 h-10 rounded-xl <?php echo $n['color']; ?> flex items-center justify-center shrink-0">
                                <i data-lucide="<?php echo $n['icono']; ?>" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-800 text-xs mb-0.5"><?php echo htmlspecialchars($n['titulo']); ?></h4>
                                <p class="text-slate-500 text-[11px] font-medium leading-normal"><?php echo htmlspecialchars($n['descripcion']); ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 border-l pl-6">
            <img src="https://images.unsplash.com/photo-1559839734-2b71ea197ec2?auto=format&fit=crop&w=100&q=80" alt="Perfil" class="w-10 h-10 rounded-full object-cover border-2 border-teal-500">
            <div class="hidden md:block">
                <p class="text-sm font-bold text-slate-800"><?php echo isset($_SESSION['usuario_nombre']) ? htmlspecialchars($_SESSION['usuario_nombre']) : 'Usuario'; ?></p>
                <p class="text-xs text-brand font-medium"><?php echo isset($_SESSION['usuario_rol']) ? htmlspecialchars($_SESSION['usuario_rol']) : 'Rol'; ?></p>
            </div>
        </div>
    </div>
</header>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // --- Lógica del Buscador ---
    const searchInput = document.getElementById('sys-search-input');
    const searchResults = document.getElementById('sys-search-results');
    const searchItems = document.getElementById('sys-search-items');

    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const query = searchInput.value.trim();
            
            if (query.length < 2) {
                searchResults.classList.add('hidden');
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch(`ajax_busqueda.php?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        searchItems.innerHTML = '';
                        if (data.length === 0) {
                            searchItems.innerHTML = '<div class="p-4 text-center text-xs text-slate-400 font-semibold">No se encontraron pacientes</div>';
                        } else {
                            data.forEach(p => {
                                const div = document.createElement('a');
                                div.href = `paciente_detalle.php?id=${p.id}`;
                                div.className = "flex items-center justify-between p-3 hover:bg-slate-50 transition-colors";
                                div.innerHTML = `
                                    <div>
                                        <p class="font-bold text-slate-800 text-xs">${p.nombre}</p>
                                        <p class="text-[10px] text-slate-400 font-semibold">DNI: ${p.dni}</p>
                                    </div>
                                    <span class="text-[10px] bg-teal-50 text-teal-700 px-2.5 py-0.5 rounded-full font-black uppercase tracking-wider">Ver Historia</span>
                                `;
                                searchItems.appendChild(div);
                            });
                        }
                        searchResults.classList.remove('hidden');
                    });
            }, 300);
        });

        // Cerrar al hacer click afuera
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.classList.add('hidden');
            }
        });
    }

    // --- Lógica de Notificaciones ---
    const btnNotifications = document.getElementById('btn-notifications');
    const panelNotifications = document.getElementById('panel-notifications');

    if (btnNotifications && panelNotifications) {
        btnNotifications.addEventListener('click', function(e) {
            e.stopPropagation();
            const isHidden = panelNotifications.classList.contains('hidden');
            if (isHidden) {
                panelNotifications.classList.remove('hidden');
                setTimeout(() => {
                    panelNotifications.classList.remove('scale-95', 'opacity-0');
                    panelNotifications.classList.add('scale-100', 'opacity-100');
                }, 10);
            } else {
                panelNotifications.classList.remove('scale-100', 'opacity-100');
                panelNotifications.classList.add('scale-95', 'opacity-0');
                setTimeout(() => {
                    panelNotifications.classList.add('hidden');
                }, 200);
            }
        });

        document.addEventListener('click', function(e) {
            if (!btnNotifications.contains(e.target) && !panelNotifications.contains(e.target)) {
                panelNotifications.classList.remove('scale-100', 'opacity-100');
                panelNotifications.classList.add('scale-95', 'opacity-0');
                setTimeout(() => {
                    panelNotifications.classList.add('hidden');
                }, 200);
            }
        });
    }
});
</script>
