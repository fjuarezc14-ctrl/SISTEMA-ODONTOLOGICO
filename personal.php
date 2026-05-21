<?php
require_once 'includes/auth_guard.php';if(!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'Admin') {
    header("Location: dashboard.php");
    exit;
}
require_once 'config/conexion.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Personal - MahuDent</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap');
        :root { --brand-primary: #0f766e; --brand-secondary: #ccfbf1; --brand-accent: #14b8a6; }
        body { font-family: 'Montserrat', sans-serif; background-color: #f8fafc; }
        .bg-brand { background-color: var(--brand-primary); }
        .text-brand { color: var(--brand-primary); }
    </style>
</head>
<body class="flex h-screen overflow-hidden">
    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        <?php $page_title = 'Personal y Accesos'; include 'includes/header.php'; ?>

        <div class="flex-1 overflow-y-auto p-4 md:p-8 relative">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-xl font-black text-slate-800 uppercase tracking-tight">Directorio de Usuarios</h2>
                    <p class="text-sm text-slate-500 font-medium mt-1">Gestiona los doctores, recepcionistas y sus permisos en el sistema.</p>
                </div>
                <button onclick="abrirModalUsuario()" class="bg-brand hover:bg-teal-800 text-white font-bold py-2.5 px-5 rounded-xl shadow-md transition flex items-center gap-2 text-sm">
                    <i data-lucide="user-plus" class="w-4 h-4"></i> Nuevo Usuario
                </button>
            </div>

            <!-- Tabla de Usuarios -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 text-xs uppercase tracking-widest font-bold">
                                <th class="p-4">Usuario / Nombre</th>
                                <th class="p-4">Rol en el Sistema</th>
                                <th class="p-4">Colegiatura</th>
                                <th class="p-4 text-center">Estado</th>
                                <th class="p-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="usuarios-tbody" class="text-sm divide-y divide-slate-100">
                            <tr>
                                <td colspan="5" class="p-8 text-center text-slate-400">
                                    <i data-lucide="loader-2" class="w-8 h-8 animate-spin mx-auto mb-2 text-brand"></i>
                                    Cargando usuarios...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Formulario Usuario -->
    <div id="modal-usuario" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <h3 id="modal-titulo" class="text-lg font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                    <i data-lucide="user-cog" class="w-5 h-5 text-brand"></i> <span id="modal-title-text">Nuevo Usuario</span>
                </h3>
                <button onclick="cerrarModalUsuario()" class="text-slate-400 hover:text-red-500 transition-colors p-1"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="p-6">
                <form id="form-usuario" onsubmit="guardarUsuario(event)" class="space-y-4">
                    <input type="hidden" id="usuario_id">
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nombre Completo</label>
                        <input type="text" id="nombre" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-slate-700 outline-none focus:ring-2 focus:ring-brand/30 focus:bg-white transition text-sm">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Usuario (Login)</label>
                            <input type="text" id="usuario" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-slate-700 outline-none focus:ring-2 focus:ring-brand/30 focus:bg-white transition text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Rol</label>
                            <select id="rol" required onchange="toggleColegiatura()" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-slate-700 outline-none focus:ring-2 focus:ring-brand/30 focus:bg-white transition text-sm font-semibold cursor-pointer">
                                <option value="Dentista">Dentista / Doctor</option>
                                <option value="Recepcionista">Recepcionista</option>
                                <option value="Admin">Administrador General</option>
                            </select>
                        </div>
                    </div>

                    <div id="div-password">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Contraseña <span id="pass-hint" class="text-[10px] lowercase font-normal">(Requerida para nuevos)</span></label>
                        <input type="password" id="password" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-slate-700 outline-none focus:ring-2 focus:ring-brand/30 focus:bg-white transition text-sm" placeholder="••••••••">
                    </div>

                    <div id="div-colegiatura">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">N° Colegiatura (COP)</label>
                        <input type="text" id="colegiatura" placeholder="Ej. 12345" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-slate-700 outline-none focus:ring-2 focus:ring-brand/30 focus:bg-white transition text-sm">
                    </div>

                    <div class="pt-4 flex gap-3">
                        <button type="button" onclick="cerrarModalUsuario()" class="flex-1 bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 font-bold py-3 rounded-xl transition text-sm">Cancelar</button>
                        <button type="submit" class="flex-1 bg-brand hover:bg-teal-800 text-white font-bold py-3 rounded-xl shadow-md transition flex items-center justify-center gap-2 text-sm">
                            <i data-lucide="save" class="w-4 h-4"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        lucide.createIcons();

        function toggleColegiatura() {
            const rol = document.getElementById('rol').value;
            const divCol = document.getElementById('div-colegiatura');
            if(rol === 'Dentista') {
                divCol.classList.remove('hidden');
            } else {
                divCol.classList.add('hidden');
                document.getElementById('colegiatura').value = '';
            }
        }

        function abrirModalUsuario(usuario = null) {
            document.getElementById('modal-usuario').classList.remove('hidden');
            const form = document.getElementById('form-usuario');
            form.reset();
            
            if(usuario) {
                document.getElementById('modal-title-text').innerText = 'Editar Usuario';
                document.getElementById('usuario_id').value = usuario.id;
                document.getElementById('nombre').value = usuario.nombre;
                document.getElementById('usuario').value = usuario.usuario;
                document.getElementById('rol').value = usuario.rol;
                document.getElementById('colegiatura').value = usuario.colegiatura || '';
                
                document.getElementById('div-password').classList.add('hidden');
                document.getElementById('password').required = false;
            } else {
                document.getElementById('modal-title-text').innerText = 'Nuevo Usuario';
                document.getElementById('usuario_id').value = '';
                document.getElementById('div-password').classList.remove('hidden');
                document.getElementById('password').required = true;
            }
            toggleColegiatura();
        }

        function cerrarModalUsuario() {
            document.getElementById('modal-usuario').classList.add('hidden');
        }

        async function cargarUsuarios() {
            try {
                const res = await fetch('ajax_personal.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({accion: 'listar_usuarios'})
                });
                const data = await res.json();
                
                const tbody = document.getElementById('usuarios-tbody');
                tbody.innerHTML = '';
                
                if (data.success && data.data.length > 0) {
                    data.data.forEach(u => {
                        let badgeRol = '';
                        if(u.rol === 'Admin') badgeRol = '<span class="bg-purple-100 text-purple-700 border border-purple-200 px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider">Admin</span>';
                        else if(u.rol === 'Dentista') badgeRol = '<span class="bg-blue-100 text-blue-700 border border-blue-200 px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider">Dentista</span>';
                        else badgeRol = '<span class="bg-emerald-100 text-emerald-700 border border-emerald-200 px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider">Recepción</span>';
                        
                        let badgeEstado = parseInt(u.estado_activo) === 1 
                            ? `<button onclick="toggleEstado(${u.id}, 0)" class="text-[10px] font-bold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-200 hover:bg-emerald-100 transition" title="Haz clic para inhabilitar">ACTIVO</button>`
                            : `<button onclick="toggleEstado(${u.id}, 1)" class="text-[10px] font-bold px-2.5 py-1 rounded-full bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 transition" title="Haz clic para habilitar">INACTIVO</button>`;

                        if (parseInt(u.id) === 1) badgeEstado = `<span class="text-[10px] font-bold px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 border border-slate-200">INMUTABLE</span>`;
                        
                        const tr = document.createElement('tr');
                        tr.className = "hover:bg-slate-50 transition-colors " + (parseInt(u.estado_activo) === 0 ? "opacity-60" : "");
                        tr.innerHTML = `
                            <td class="p-4">
                                <div class="font-bold text-slate-800 text-sm">${u.nombre}</div>
                                <div class="text-xs text-slate-400 font-medium">@${u.usuario}</div>
                            </td>
                            <td class="p-4">${badgeRol}</td>
                            <td class="p-4 text-xs font-semibold text-slate-500">${u.colegiatura || '-'}</td>
                            <td class="p-4 text-center">${badgeEstado}</td>
                            <td class="p-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button onclick='abrirModalUsuario(${JSON.stringify(u)})' class="text-slate-400 hover:text-blue-500 p-1.5 rounded-lg hover:bg-blue-50 transition" title="Editar">
                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    </button>
                                    <button onclick="resetPassword(${u.id}, '${u.nombre}')" class="text-slate-400 hover:text-amber-500 p-1.5 rounded-lg hover:bg-amber-50 transition" title="Cambiar Contraseña">
                                        <i data-lucide="key" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                    lucide.createIcons();
                } else {
                    tbody.innerHTML = '<tr><td colspan="5" class="p-8 text-center text-slate-400 font-bold">No hay usuarios</td></tr>';
                }
            } catch (e) {
                console.error(e);
            }
        }

        async function guardarUsuario(e) {
            e.preventDefault();
            const id = document.getElementById('usuario_id').value;
            const accion = id ? 'actualizar_usuario' : 'crear_usuario';
            
            const payload = {
                accion: accion,
                id: id,
                nombre: document.getElementById('nombre').value,
                usuario: document.getElementById('usuario').value,
                rol: document.getElementById('rol').value,
                password: document.getElementById('password').value,
                colegiatura: document.getElementById('colegiatura').value
            };

            try {
                const res = await fetch('ajax_personal.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if(data.success) {
                    cerrarModalUsuario();
                    cargarUsuarios();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch(e) {
                alert('Error de red');
            }
        }

        async function resetPassword(id, nombre) {
            const nueva = prompt(`Ingrese nueva contraseña para ${nombre}:`);
            if(!nueva) return;
            
            try {
                const res = await fetch('ajax_personal.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({accion: 'cambiar_password', id: id, password: nueva})
                });
                const data = await res.json();
                if(data.success) alert('Contraseña actualizada correctamente');
                else alert('Error: ' + data.error);
            } catch(e) {
                alert('Error de red');
            }
        }

        async function toggleEstado(id, nuevoEstado) {
            if(!confirm('¿Seguro que deseas cambiar el estado de acceso de este usuario?')) return;
            try {
                const res = await fetch('ajax_personal.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({accion: 'toggle_estado', id: id, estado: nuevoEstado})
                });
                const data = await res.json();
                if(data.success) cargarUsuarios();
                else alert('Error: ' + data.error);
            } catch(e) {
                alert('Error de red');
            }
        }

        // Init
        document.addEventListener('DOMContentLoaded', cargarUsuarios);
    </script>
</body>
</html>
