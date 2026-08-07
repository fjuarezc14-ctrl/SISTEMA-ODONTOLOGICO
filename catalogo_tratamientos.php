<?php
require_once 'includes/auth_guard.php';
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'Admin') {
    header("Location: dashboard.php");
    exit;
}

require_once 'controllers/PresupuestoController.php';
$controller = new PresupuestoController();
$catalogo = $controller->getCatalogo();

// Agrupar por categoría existente
$categorias = [];
foreach ($catalogo as $item) {
    $cat = $item['categoria'] ?: 'Sin categoría';
    if (!isset($categorias[$cat])) {
        $categorias[$cat] = [];
    }
    $categorias[$cat][] = $item;
}
ksort($categorias);

// Lista de categorías existentes para los selects
$listaCategorias = array_keys($categorias);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar y Visualizar Ítems - MahuDent</title>
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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap');
        :root { --brand-primary: #3a596a; --brand-secondary: #ede8f7; --brand-accent: #937ec2; }
        body { font-family: 'Montserrat', sans-serif; background-color: #f8fafc; }
        .bg-brand { background-color: var(--brand-primary); }
        .text-brand { color: var(--brand-primary); }
        .bg-brand-light { background-color: var(--brand-secondary); }
    </style>
</head>
<body class="flex h-screen overflow-hidden">
    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <?php $page_title = 'Editar y Visualizar Ítems'; include 'includes/header.php'; ?>

        <div class="flex-1 overflow-y-auto p-4 md:p-8">
            <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-800">Catálogo de Tratamientos e Insumos</h1>
                    <p class="text-sm text-slate-500 mt-1">Visualiza, agrega y edita los ítems y precios que se usan en los presupuestos. Los cambios aplican a <strong>futuros</strong> presupuestos; no modifican presupuestos ya registrados.</p>
                </div>
                <button onclick="abrirModalNuevo()" class="px-4 py-2.5 bg-brand text-white rounded-xl text-sm font-bold shadow-lg hover:bg-teal-800 transition flex items-center justify-center gap-2">
                    <i data-lucide="plus" class="w-4 h-4"></i> Nuevo Ítem
                </button>
            </div>

            <div class="mb-4">
                <div class="relative w-full md:w-96">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                    <input type="text" id="busquedaCatalogo" placeholder="Buscar ítem..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-teal-200 focus:border-teal-500">
                </div>
            </div>

            <?php foreach ($categorias as $categoria => $items): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6" data-categoria="<?php echo htmlspecialchars($categoria); ?>">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <h2 class="font-black text-slate-700 uppercase tracking-wider text-sm"><?php echo htmlspecialchars($categoria); ?></h2>
                    <span class="text-xs font-bold text-slate-400"><?php echo count($items); ?> ítems</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-[11px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">
                                <th class="px-6 py-3">Nombre</th>
                                <th class="px-6 py-3 w-40">Precio (S/)</th>
                                <th class="px-6 py-3 w-24 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php foreach ($items as $item): ?>
                            <tr class="hover:bg-slate-50/60 transition-colors" data-id="<?php echo $item['id']; ?>">
                                <td class="px-6 py-3">
                                    <span class="text-sm font-bold text-slate-700 item-nombre"><?php echo htmlspecialchars($item['nombre']); ?></span>
                                </td>
                                <td class="px-6 py-3">
                                    <span class="text-sm font-black text-slate-800 item-precio">S/ <?php echo number_format($item['precio_base'], 2); ?></span>
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <button onclick="editarItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars(addslashes($item['nombre'])); ?>', <?php echo $item['precio_base']; ?>, '<?php echo htmlspecialchars(addslashes($item['categoria'])); ?>')" class="p-2 hover:bg-brand-light rounded-lg text-slate-400 hover:text-brand transition" title="Editar">
                                        <i data-lucide="pencil" class="w-4 h-4"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Modal Editar Ítem -->
    <div id="modalEditarItem" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md transform transition-transform duration-300 overflow-hidden">
            <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                <h3 class="font-bold text-slate-800 flex items-center gap-2">
                    <i data-lucide="pencil" class="w-5 h-5 text-brand"></i> Editar Ítem
                </h3>
                <button onclick="cerrarModal('modalEditarItem')" class="text-slate-400 hover:text-red-500 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <input type="hidden" id="editItemId">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nombre</label>
                    <input type="text" id="editItemNombre" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-200 focus:border-teal-500 font-medium text-slate-700 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Categoría</label>
                    <select id="editItemCategoria" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-200 focus:border-teal-500 font-medium text-slate-700 text-sm bg-white">
                        <?php foreach ($listaCategorias as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Precio (S/)</label>
                    <input type="number" id="editItemPrecio" min="0" step="0.01" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-200 focus:border-teal-500 font-medium text-slate-700 text-sm">
                </div>
                <p id="editItemError" class="text-red-500 text-xs font-bold hidden"></p>
                <div class="flex gap-3 pt-2">
                    <button onclick="cerrarModal('modalEditarItem')" class="flex-1 px-4 py-2.5 bg-slate-100 text-slate-600 rounded-xl font-bold text-sm hover:bg-teal-200 transition">Cancelar</button>
                    <button onclick="guardarItem()" class="flex-1 px-4 py-2.5 bg-brand text-white rounded-xl font-bold text-sm hover:bg-teal-800 transition shadow-lg flex items-center justify-center gap-2">
                        Guardar <i data-lucide="check" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nuevo Ítem -->
    <div id="modalNuevoItem" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md transform transition-transform duration-300 overflow-hidden">
            <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                <h3 class="font-bold text-slate-800 flex items-center gap-2">
                    <i data-lucide="plus" class="w-5 h-5 text-brand"></i> Nuevo Ítem
                </h3>
                <button onclick="cerrarModal('modalNuevoItem')" class="text-slate-400 hover:text-red-500 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nombre</label>
                    <input type="text" id="nuevoItemNombre" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-200 focus:border-teal-500 font-medium text-slate-700 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Categoría</label>
                    <select id="nuevoItemCategoria" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-200 focus:border-teal-500 font-medium text-slate-700 text-sm bg-white">
                        <option value="">-- Seleccionar categoría --</option>
                        <?php foreach ($listaCategorias as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Precio (S/)</label>
                    <input type="number" id="nuevoItemPrecio" min="0" step="0.01" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-200 focus:border-teal-500 font-medium text-slate-700 text-sm">
                </div>
                <p id="nuevoItemError" class="text-red-500 text-xs font-bold hidden"></p>
                <div class="flex gap-3 pt-2">
                    <button onclick="cerrarModal('modalNuevoItem')" class="flex-1 px-4 py-2.5 bg-slate-100 text-slate-600 rounded-xl font-bold text-sm hover:bg-teal-200 transition">Cancelar</button>
                    <button onclick="guardarNuevoItem()" class="flex-1 px-4 py-2.5 bg-brand text-white rounded-xl font-bold text-sm hover:bg-teal-800 transition shadow-lg flex items-center justify-center gap-2">
                        Crear <i data-lucide="check" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function cerrarModal(id) {
            const modal = document.getElementById(id);
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function abrirModalNuevo() {
            document.getElementById('nuevoItemNombre').value = '';
            document.getElementById('nuevoItemCategoria').value = '';
            document.getElementById('nuevoItemPrecio').value = '';
            document.getElementById('nuevoItemError').classList.add('hidden');
            const modal = document.getElementById('modalNuevoItem');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function editarItem(id, nombre, precio, categoria) {
            document.getElementById('editItemId').value = id;
            document.getElementById('editItemNombre').value = nombre;
            document.getElementById('editItemCategoria').value = categoria;
            document.getElementById('editItemPrecio').value = parseFloat(precio).toFixed(2);
            document.getElementById('editItemError').classList.add('hidden');
            const modal = document.getElementById('modalEditarItem');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        async function guardarItem() {
            const id = document.getElementById('editItemId').value;
            const nombre = document.getElementById('editItemNombre').value.trim();
            const categoria = document.getElementById('editItemCategoria').value;
            const precio = document.getElementById('editItemPrecio').value;
            const errorEl = document.getElementById('editItemError');

            // Validación frontend
            if (!nombre) {
                errorEl.textContent = 'El nombre es obligatorio.';
                errorEl.classList.remove('hidden');
                return;
            }
            if (precio === '' || isNaN(precio) || parseFloat(precio) < 0) {
                errorEl.textContent = 'El precio debe ser un número mayor o igual a 0.';
                errorEl.classList.remove('hidden');
                return;
            }

            try {
                const res = await fetch('ajax_presupuesto.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        accion: 'actualizar_item_catalogo',
                        id: id,
                        nombre: nombre,
                        categoria: categoria,
                        precio_base: parseFloat(precio)
                    })
                });
                const data = await res.json();
                if (data.success) {
                    // Actualizar fila en la tabla
                    const fila = document.querySelector('tr[data-id="' + id + '"]');
                    if (fila) {
                        fila.querySelector('.item-nombre').textContent = data.item.nombre;
                        fila.querySelector('.item-precio').textContent = 'S/ ' + parseFloat(data.item.precio_base).toFixed(2);
                    }
                    cerrarModal('modalEditarItem');
                    alert('Ítem actualizado correctamente.');
                } else {
                    errorEl.textContent = data.error || 'Error al actualizar.';
                    errorEl.classList.remove('hidden');
                }
            } catch(e) {
                console.error(e);
                errorEl.textContent = 'Error de conexión.';
                errorEl.classList.remove('hidden');
            }
        }

        async function guardarNuevoItem() {
            const nombre = document.getElementById('nuevoItemNombre').value.trim();
            const categoria = document.getElementById('nuevoItemCategoria').value;
            const precio = document.getElementById('nuevoItemPrecio').value;
            const errorEl = document.getElementById('nuevoItemError');

            // Validación frontend
            if (!nombre) {
                errorEl.textContent = 'El nombre es obligatorio.';
                errorEl.classList.remove('hidden');
                return;
            }
            if (!categoria) {
                errorEl.textContent = 'La categoría es obligatoria.';
                errorEl.classList.remove('hidden');
                return;
            }
            if (precio === '' || isNaN(precio) || parseFloat(precio) < 0) {
                errorEl.textContent = 'El precio debe ser un número mayor o igual a 0.';
                errorEl.classList.remove('hidden');
                return;
            }

            try {
                const res = await fetch('ajax_presupuesto.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        accion: 'crear_item_catalogo',
                        nombre: nombre,
                        categoria: categoria,
                        precio_base: parseFloat(precio)
                    })
                });
                const data = await res.json();
                if (data.success) {
                    cerrarModal('modalNuevoItem');
                    alert('Ítem creado correctamente. La página se recargará para mostrarlo.');
                    location.reload();
                } else {
                    errorEl.textContent = data.error || 'Error al crear.';
                    errorEl.classList.remove('hidden');
                }
            } catch(e) {
                console.error(e);
                errorEl.textContent = 'Error de conexión.';
                errorEl.classList.remove('hidden');
            }
        }

        // Filtro de búsqueda
        document.getElementById('busquedaCatalogo').addEventListener('keyup', function() {
            const query = this.value.toLowerCase();
            document.querySelectorAll('[data-categoria]').forEach(bloque => {
                let visible = false;
                bloque.querySelectorAll('tbody tr').forEach(row => {
                    const text = row.innerText.toLowerCase();
                    const match = text.includes(query);
                    row.style.display = match ? '' : 'none';
                    if (match) visible = true;
                });
                bloque.style.display = visible ? '' : 'none';
            });
        });
    </script>
</body>
</html>