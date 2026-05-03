<?php
session_start();
// Si no hay sesión, al login
if(!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// 1. Conectamos a la base de datos
include 'config/conexion.php';

$mensaje = "";

// 2. Si el doctor envió el formulario de "Nuevo Paciente", lo guardamos
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'nuevo_paciente') {
    $dni = $_POST['dni'];
    $nombre = $_POST['nombre'];
    $telefono = $_POST['telefono'];
    $email = $_POST['email'];

    $sql_insert = "INSERT INTO pacientes (dni, nombre, telefono, email) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql_insert);
    $stmt->bind_param("ssss", $dni, $nombre, $telefono, $email);
    
    if($stmt->execute()) {
        $mensaje = "<div class='bg-emerald-100 text-emerald-700 p-3 rounded-xl mb-4 font-bold text-sm border border-emerald-200'>¡Paciente guardado con éxito!</div>";
    } else {
        $mensaje = "<div class='bg-red-100 text-red-700 p-3 rounded-xl mb-4 font-bold text-sm border border-red-200'>Error al guardar: " . $conn->error . "</div>";
    }
}

// 3. Obtenemos la lista de todos los pacientes para mostrarlos en la tabla
$sql_select = "SELECT * FROM pacientes ORDER BY fecha_registro DESC";
$resultado_pacientes = $conn->query($sql_select);
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
            <a href="pacientes.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-white text-brand font-bold shadow-lg transition-transform hover:scale-105">
                <i data-lucide="users" class="w-5 h-5"></i> Pacientes
            </a>
            <a href="agenda.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-brand-secondary hover:bg-white/10 hover:text-white transition-colors">
                <i data-lucide="calendar-days" class="w-5 h-5"></i> Agenda
            </a>
            <a href="presupuestos.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-brand-secondary hover:bg-white/10 hover:text-white transition-colors">
                <i data-lucide="file-text" class="w-5 h-5"></i> Presupuestos
            </a>
        </nav>
        
        <div class="p-4 border-t border-white/10 shrink-0">
            <a href="logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-brand-secondary hover:bg-red-500 hover:text-white transition-colors font-bold">
                <i data-lucide="log-out" class="w-5 h-5"></i> Cerrar Sesión
            </a>
        </div>
    </aside>

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
        
        <header class="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-8 shadow-sm shrink-0">
            <h1 class="text-xl font-black text-slate-800 uppercase tracking-tight">Directorio de Pacientes</h1>
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-3 border-l pl-6">
                    <img src="https://images.unsplash.com/photo-1559839734-2b71ea197ec2?auto=format&fit=crop&w=100&q=80" alt="Dr. Perfil" class="w-10 h-10 rounded-full object-cover border-2 border-teal-500">
                    <div class="hidden md:block">
                        <p class="text-sm font-bold text-slate-800"><?php echo $_SESSION['usuario_nombre']; ?></p>
                        <p class="text-xs text-brand font-medium"><?php echo $_SESSION['usuario_rol']; ?></p>
                    </div>
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8 relative">
            
            <?php echo $mensaje; // Aquí mostramos si se guardó con éxito ?>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-slate-50/50">
                    <div class="relative w-full md:w-96">
                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                        <input type="text" placeholder="Buscar por nombre o DNI..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-brand/20">
                    </div>
                    
                    <button onclick="abrirModal()" class="w-full md:w-auto px-5 py-2.5 bg-brand text-white rounded-xl text-sm font-bold shadow-lg hover:bg-teal-800 transition flex items-center justify-center gap-2">
                        <i data-lucide="user-plus" class="w-4 h-4"></i> Nuevo Paciente
                    </button>
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
                                <tr class="hover:bg-slate-50/80 transition-colors group">
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
                                            <a href="paciente_detalle.php?id=<?php echo $paciente['id']; ?>" class="p-2 bg-slate-100 hover:bg-brand hover:text-white rounded-lg text-slate-500 transition tooltip" title="Ver Historia">
                                                <i data-lucide="folder-open" class="w-4 h-4"></i>
                                            </a>
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

        <div id="modalNuevoPaciente" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 hidden flex items-center justify-center">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden transform scale-95 opacity-0 transition-all duration-300" id="modalContent">
                
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="text-lg font-black text-slate-800 flex items-center gap-2">
                        <i data-lucide="user-plus" class="text-brand w-5 h-5"></i> Registrar Paciente
                    </h3>
                    <button onclick="cerrarModal()" class="text-slate-400 hover:text-red-500 transition">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <div class="p-6">
                    <form method="POST" action="" class="space-y-4">
                        <input type="hidden" name="accion" value="nuevo_paciente">
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">DNI</label>
                            <input type="text" name="dni" required class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nombre Completo</label>
                            <input type="text" name="nombre" required class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Teléfono</label>
                                <input type="text" name="telefono" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Correo Electrónico</label>
                                <input type="email" name="email" class="w-full px-4 py-2 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/30">
                            </div>
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

    <script>
        lucide.createIcons();

        // Funciones para abrir y cerrar la ventana flotante (Modal)
        const modal = document.getElementById('modalNuevoPaciente');
        const modalContent = document.getElementById('modalContent');

        function abrirModal() {
            modal.classList.remove('hidden');
            setTimeout(() => {
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function cerrarModal() {
            modalContent.classList.remove('scale-100', 'opacity-100');
            modalContent.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }
    </script>
</body>
</html>