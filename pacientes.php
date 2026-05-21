<?php
require_once 'includes/auth_guard.php';// Si no hay sesión, al login
if(!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'controllers/PacienteController.php';
$controller = new PacienteController();

$mensaje = "";

// 2. Si el doctor envió el formulario de "Nuevo Paciente", lo guardamos a través del Controlador
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion'])) {
    if ($_POST['accion'] == 'nuevo_paciente') {
        $resultado = $controller->store($_POST);
        if($resultado === true) {
            $mensaje = "<div class='bg-emerald-100 text-emerald-700 p-3 rounded-xl mb-4 font-bold text-sm border border-emerald-200'>¡Paciente guardado con éxito!</div>";
        } else {
            $mensaje = "<div class='bg-red-100 text-red-700 p-3 rounded-xl mb-4 font-bold text-sm border border-red-200'>Error al guardar: " . htmlspecialchars($resultado) . "</div>";
        }
    } else if ($_POST['accion'] == 'editar_paciente') {
        $resultado = $controller->update($_POST['paciente_id'], $_POST);
        if($resultado === true) {
            $mensaje = "<div class='bg-blue-100 text-blue-700 p-3 rounded-xl mb-4 font-bold text-sm border border-blue-200'>¡Paciente actualizado con éxito!</div>";
        } else {
            $mensaje = "<div class='bg-red-100 text-red-700 p-3 rounded-xl mb-4 font-bold text-sm border border-red-200'>Error al actualizar: " . htmlspecialchars($resultado) . "</div>";
        }
    } else if ($_POST['accion'] == 'eliminar_paciente') {
        if ($_SESSION['usuario_rol'] === 'Admin') {
            $resultado = $controller->delete($_POST['paciente_id']);
            if($resultado === true) {
                $mensaje = "<div class='bg-gray-100 text-gray-700 p-3 rounded-xl mb-4 font-bold text-sm border border-gray-300'>Paciente oculto/eliminado del registro.</div>";
            } else {
                $mensaje = "<div class='bg-red-100 text-red-700 p-3 rounded-xl mb-4 font-bold text-sm border border-red-200'>Error al eliminar: " . htmlspecialchars($resultado) . "</div>";
            }
        } else {
            $mensaje = "<div class='bg-red-100 text-red-700 p-3 rounded-xl mb-4 font-bold text-sm border border-red-200'>Acceso denegado: Solo el Administrador puede inhabilitar pacientes.</div>";
        }
    } else if ($_POST['accion'] == 'restaurar_paciente' && $_SESSION['usuario_rol'] === 'Admin') {
        $resultado = $controller->restore($_POST['paciente_id']);
        if($resultado === true) {
            $mensaje = "<div class='bg-emerald-100 text-emerald-700 p-3 rounded-xl mb-4 font-bold text-sm border border-emerald-200'>Paciente restaurado con éxito.</div>";
        } else {
            $mensaje = "<div class='bg-red-100 text-red-700 p-3 rounded-xl mb-4 font-bold text-sm border border-red-200'>Error al restaurar: " . htmlspecialchars($resultado) . "</div>";
        }
    }
}

// 3. Obtenemos la lista de pacientes
$ver_inhabilitados = isset($_GET['inhabilitados']) && $_GET['inhabilitados'] == '1' && $_SESSION['usuario_rol'] === 'Admin';
if ($ver_inhabilitados) {
    $resultado_pacientes = $controller->inhabilitados();
} else {
    $resultado_pacientes = $controller->index();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pacientes - MahuDent</title>
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
        
        <?php $page_title = 'Directorio de Pacientes'; include 'includes/header.php'; ?>

        <div class="flex-1 overflow-y-auto p-4 md:p-8 relative">
            
            <?php echo $mensaje; // Aquí mostramos si se guardó con éxito ?>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-slate-50/50">
                    <div class="relative w-full md:w-96">
                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                        <input type="text" placeholder="Buscar por nombre o DNI..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand/20">
                    </div>
                    <div class="flex flex-col md:flex-row gap-3 w-full md:w-auto">
                        <?php if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'Admin'): ?>
                            <?php if ($ver_inhabilitados): ?>
                                <a href="pacientes.php" class="w-full md:w-auto px-5 py-2.5 bg-slate-100 text-slate-700 rounded-xl text-sm font-bold shadow-sm hover:bg-slate-200 transition flex items-center justify-center gap-2">
                                    <i data-lucide="users" class="w-4 h-4"></i> Ver Activos
                                </a>
                            <?php else: ?>
                                <a href="pacientes.php?inhabilitados=1" class="w-full md:w-auto px-5 py-2.5 bg-red-50 text-red-600 border border-red-200 rounded-xl text-sm font-bold shadow-sm hover:bg-red-100 transition flex items-center justify-center gap-2">
                                    <i data-lucide="user-x" class="w-4 h-4"></i> Inhabilitados
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <button onclick="abrirModal()" class="w-full md:w-auto px-5 py-2.5 bg-brand text-white rounded-xl text-sm font-bold shadow-lg hover:bg-teal-800 transition flex items-center justify-center gap-2">
                            <i data-lucide="user-plus" class="w-4 h-4"></i> Nuevo Paciente
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-[11px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">
                                <th class="px-6 py-4">DNI</th>
                                <th class="px-6 py-4">Paciente</th>
                                <th class="px-6 py-4">Teléfono</th>
                                <th class="px-6 py-4">Registro</th>
                                <th class="px-6 py-4 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            
                            <?php if($resultado_pacientes->num_rows > 0): ?>
                                <?php while($paciente = $resultado_pacientes->fetch_assoc()): ?>
                                <tr class="hover:bg-slate-50/80 transition-colors group cursor-pointer" onclick="location.href='paciente_detalle.php?id=<?php echo $paciente['id']; ?>'">
                                    <td class="px-6 py-4 text-sm font-bold text-slate-500"><?php echo htmlspecialchars($paciente['dni']); ?></td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-brand-light flex items-center justify-center text-[10px] font-bold text-brand uppercase">
                                                <?php echo substr($paciente['nombre'], 0, 2); ?>
                                            </div>
                                            <span class="text-sm font-bold text-slate-700"><?php echo htmlspecialchars($paciente['nombre']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600"><?php echo htmlspecialchars($paciente['telefono']); ?></td>
                                    <td class="px-6 py-4 text-sm text-slate-400">
                                        <?php echo date("d M Y", strtotime($paciente['fecha_registro'])); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex justify-center gap-2">
                                            <?php if ($ver_inhabilitados): ?>
                                                <form method="POST" action="pacientes.php" class="inline" onsubmit="event.stopPropagation(); return confirm('¿Seguro que deseas restaurar a este paciente? Volverá a estar activo en el sistema.');">
                                                    <input type="hidden" name="accion" value="restaurar_paciente">
                                                    <input type="hidden" name="paciente_id" value="<?php echo $paciente['id']; ?>">
                                                    <button type="submit" onclick="event.stopPropagation()" class="p-2 bg-slate-100 hover:bg-emerald-600 hover:text-white rounded-lg text-slate-500 transition tooltip" title="Restaurar Paciente">
                                                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <a href="paciente_detalle.php?id=<?php echo $paciente['id']; ?>" class="p-2 bg-slate-100 hover:bg-brand hover:text-white rounded-lg text-slate-500 transition tooltip" title="Ver Historia" onclick="event.stopPropagation()">
                                                    <i data-lucide="folder-open" class="w-4 h-4"></i>
                                                </a>
                                                <button onclick='event.stopPropagation(); abrirModalEdicion(<?php echo htmlspecialchars(json_encode($paciente), ENT_QUOTES, "UTF-8"); ?>)' class="p-2 bg-slate-100 hover:bg-blue-600 hover:text-white rounded-lg text-slate-500 transition tooltip" title="Editar Paciente">
                                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-slate-400 text-sm">
                                        No hay pacientes registrados aún. ¡Agrega el primero!
                                    </td>
                                </tr>
                            <?php endif; ?>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="modalNuevoPaciente" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl overflow-hidden transform scale-95 opacity-0 transition-all duration-300 flex flex-col max-h-[90vh]" id="modalContent">
                
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="text-lg font-black text-slate-800 flex items-center gap-2">
                        <i data-lucide="user-plus" class="text-brand w-5 h-5"></i> Registrar Paciente
                    </h3>
                    <button onclick="cerrarModal()" class="text-slate-400 hover:text-red-500 transition">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto">
                    <form method="POST" action="" class="space-y-4">
                        <input type="hidden" name="accion" value="nuevo_paciente">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">DNI *</label>
                                <input type="text" name="dni" required class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nombres y Apellidos *</label>
                                <input type="text" name="nombre" required class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Teléfono</label>
                                <input type="text" name="telefono" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Correo Electrónico</label>
                                <input type="email" name="email" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Fecha de Nacimiento</label>
                                <input type="date" name="fecha_nacimiento" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Sexo</label>
                                <select name="sexo" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                                    <option value="">Seleccione...</option>
                                    <option value="Masculino">Masculino</option>
                                    <option value="Femenino">Femenino</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Ocupación</label>
                                <input type="text" name="ocupacion" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Dirección / Residencia</label>
                                <input type="text" name="direccion" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Lugar de Nacimiento</label>
                                <input type="text" name="lugar_nacimiento" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Procedencia</label>
                                <input type="text" name="procedencia" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Contacto de Emergencia</label>
                                <input type="text" name="contacto_emergencia" placeholder="Nombre del familiar/amigo" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Teléfono de Emergencia</label>
                                <input type="text" name="telefono_emergencia" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-red-500 uppercase mb-1 flex items-center gap-1"><i data-lucide="alert-triangle" class="w-3 h-3"></i> Alergias / Condiciones Médicas</label>
                            <input type="text" name="alergias" placeholder="Ej: Penicilina, Hipertensión..." class="w-full px-4 py-2 rounded-xl border border-red-200 focus:outline-none focus:ring-2 focus:ring-red-500/30 placeholder:text-red-200 text-sm">
                        </div>

                        <div class="pt-4 flex gap-3">
                            <button type="button" onclick="cerrarModal()" class="flex-1 px-4 py-3 border border-slate-200 text-slate-600 rounded-xl font-bold hover:bg-slate-50 transition">Cancelar</button>
                            <button type="submit" class="flex-1 px-4 py-3 bg-brand hover:bg-teal-800 text-white rounded-xl font-bold shadow-lg transition">Guardar Paciente</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </main>

    <!-- Modal Editar Paciente -->
    <div id="modalEditarPaciente" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-50 transition-opacity opacity-0 p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl overflow-hidden transform scale-95 transition-transform duration-300 flex flex-col max-h-[90vh]">
            <div class="bg-slate-50 border-b border-slate-100 p-6 flex justify-between items-center">
                <h3 class="text-xl font-black text-slate-800 flex items-center gap-2">
                    <i data-lucide="edit" class="w-6 h-6 text-blue-600"></i> Editar Paciente
                </h3>
                <button onclick="cerrarModalEdicion()" class="text-slate-400 hover:text-red-500 transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            
            <form method="POST" action="pacientes.php" class="p-6 flex flex-col gap-5 overflow-y-auto">
                <input type="hidden" name="accion" value="editar_paciente">
                <input type="hidden" name="paciente_id" id="edit_paciente_id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">DNI *</label>
                        <input type="text" name="dni" id="edit_dni" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-medium text-slate-700 outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nombres y Apellidos *</label>
                        <input type="text" name="nombre" id="edit_nombre" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-medium text-slate-700 outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Teléfono</label>
                        <input type="text" name="telefono" id="edit_telefono" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-medium text-slate-700 outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Email</label>
                        <input type="email" name="email" id="edit_email" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-medium text-slate-700 outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Fecha Nacimiento</label>
                        <input type="date" name="fecha_nacimiento" id="edit_fecha_nacimiento" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-medium text-slate-700 outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Sexo</label>
                        <select name="sexo" id="edit_sexo" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-medium text-slate-700 outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition">
                            <option value="">Seleccione...</option>
                            <option value="Masculino">Masculino</option>
                            <option value="Femenino">Femenino</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Ocupación</label>
                        <input type="text" name="ocupacion" id="edit_ocupacion" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-medium text-slate-700 outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Dirección</label>
                        <input type="text" name="direccion" id="edit_direccion" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-medium text-slate-700 outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Lugar Nacimiento</label>
                        <input type="text" name="lugar_nacimiento" id="edit_lugar_nacimiento" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-medium text-slate-700 outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Procedencia</label>
                        <input type="text" name="procedencia" id="edit_procedencia" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-medium text-slate-700 outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Contacto Emergencia</label>
                        <input type="text" name="contacto_emergencia" id="edit_contacto_emergencia" placeholder="Nombre de familiar" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-medium text-slate-700 outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Tel. Emergencia</label>
                        <input type="text" name="telefono_emergencia" id="edit_telefono_emergencia" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-medium text-slate-700 outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-red-500 uppercase mb-2 flex items-center gap-1"><i data-lucide="alert-triangle" class="w-3 h-3"></i> Alergias / Condiciones</label>
                    <input type="text" name="alergias" id="edit_alergias" placeholder="Ej: Penicilina, Hipertensión..." class="w-full bg-red-50 border border-red-200 rounded-xl p-3 font-medium text-red-700 outline-none focus:ring-2 focus:ring-red-600 focus:border-red-600 transition placeholder:text-red-300 text-sm">
                </div>

                <div class="mt-4 flex flex-col md:flex-row justify-between items-center gap-4">
                    <?php if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'Admin'): ?>
                    <button type="button" onclick="confirmarEliminacion(document.getElementById('edit_paciente_id').value)" class="text-red-500 hover:text-red-600 font-bold text-sm flex items-center gap-2 transition px-2 py-2">
                        <i data-lucide="trash-2" class="w-4 h-4"></i> Inhabilitar Paciente
                    </button>
                    <?php else: ?>
                    <div></div>
                    <?php endif; ?>
                    <div class="flex gap-3 w-full md:w-auto">
                        <button type="button" onclick="cerrarModalEdicion()" class="flex-1 md:flex-none bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-3 px-6 rounded-xl transition">Cancelar</button>
                        <button type="submit" class="flex-1 md:flex-none bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg shadow-blue-600/30 transition">Guardar Cambios</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Eliminar Paciente -->
    <div id="modalEliminarPaciente" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-50 transition-opacity opacity-0">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden transform scale-95 transition-transform duration-300 p-6 text-center">
            <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="alert-triangle" class="w-10 h-10 text-red-600"></i>
            </div>
            <h3 class="text-xl font-black text-slate-800 mb-2">¿Eliminar Paciente?</h3>
            <p class="text-slate-500 text-sm mb-6">Esta acción ocultará al paciente de la lista. Puedes revertirlo desde la base de datos si es necesario.</p>
            
            <form method="POST" action="pacientes.php" class="flex gap-3">
                <input type="hidden" name="accion" value="eliminar_paciente">
                <input type="hidden" name="paciente_id" id="delete_paciente_id">
                
                <button type="button" onclick="cerrarModalEliminacion()" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-3 px-4 rounded-xl transition">Cancelar</button>
                <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg shadow-red-600/30 transition">Sí, eliminar</button>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function abrirModal() {
            const modal = document.getElementById('modalNuevoPaciente');
            const inner = modal.querySelector('div');
            modal.classList.remove('hidden');
            // Simular apertura inicial previa
            setTimeout(() => {
                inner.classList.remove('scale-95', 'opacity-0');
                inner.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function cerrarModal() {
            const modal = document.getElementById('modalNuevoPaciente');
            const inner = modal.querySelector('div');
            inner.classList.remove('scale-100', 'opacity-100');
            inner.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        function abrirModalEdicion(paciente) {
            const modal = document.getElementById('modalEditarPaciente');
            
            document.getElementById('edit_paciente_id').value = paciente.id;
            document.getElementById('edit_dni').value = paciente.dni;
            document.getElementById('edit_nombre').value = paciente.nombre;
            document.getElementById('edit_telefono').value = paciente.telefono;
            document.getElementById('edit_email').value = paciente.email;
            document.getElementById('edit_fecha_nacimiento').value = paciente.fecha_nacimiento || '';
            document.getElementById('edit_sexo').value = paciente.sexo || '';
            document.getElementById('edit_ocupacion').value = paciente.ocupacion || '';
            document.getElementById('edit_direccion').value = paciente.direccion || '';
            document.getElementById('edit_lugar_nacimiento').value = paciente.lugar_nacimiento || '';
            document.getElementById('edit_procedencia').value = paciente.procedencia || '';
            document.getElementById('edit_contacto_emergencia').value = paciente.contacto_emergencia || '';
            document.getElementById('edit_telefono_emergencia').value = paciente.telefono_emergencia || '';
            document.getElementById('edit_alergias').value = paciente.alergias || '';

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modal.querySelector('div').classList.remove('scale-95');
            }, 10);
        }

        function cerrarModalEdicion() {
            const modal = document.getElementById('modalEditarPaciente');
            modal.classList.add('opacity-0');
            modal.querySelector('div').classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }, 300);
        }

        function confirmarEliminacion(id) {
            const modal = document.getElementById('modalEliminarPaciente');
            document.getElementById('delete_paciente_id').value = id;

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modal.querySelector('div').classList.remove('scale-95');
            }, 10);
        }

        function cerrarModalEliminacion() {
            const modal = document.getElementById('modalEliminarPaciente');
            modal.classList.add('opacity-0');
            modal.querySelector('div').classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }, 300);
        }
    </script>
</body>
</html>
