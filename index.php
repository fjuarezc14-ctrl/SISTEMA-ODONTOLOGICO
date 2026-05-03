<?php
session_start();

// Si ya hay sesión iniciada, saltamos directo al dashboard
if(isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

// Lógica para cuando se presiona el botón de "Ingresar"
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include 'config/conexion.php';
    
    $usuario = $_POST['usuario'];
    $password_ingresada = $_POST['password'];

    // Buscamos al usuario en la base de datos
    $sql = "SELECT id, nombre, password, rol FROM usuarios WHERE usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $fila = $resultado->fetch_assoc();
        // Verificamos si la contraseña coincide con la encriptada
        if (password_verify($password_ingresada, $fila['password'])) {
            $_SESSION['usuario_id'] = $fila['id'];
            $_SESSION['usuario_nombre'] = $fila['nombre'];
            $_SESSION['usuario_rol'] = $fila['rol'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Contraseña incorrecta.";
        }
    } else {
        $error = "El usuario no existe.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - MahuDent</title>
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
<body class="flex items-center justify-center h-screen bg-slate-100">

    <div class="bg-white p-10 rounded-3xl shadow-2xl w-full max-w-md border border-slate-100">
        
        <div class="flex flex-col items-center mb-8">
            <div class="w-16 h-16 bg-brand rounded-2xl flex items-center justify-center text-white shadow-lg mb-4">
                <i data-lucide="smile" class="w-10 h-10"></i>
            </div>
            <h1 class="text-3xl font-black tracking-widest text-slate-800 uppercase leading-tight">MAHUDENT</h1>
            <p class="text-xs text-brand font-bold tracking-widest uppercase">Clínica Odontológica</p>
        </div>

        <?php if($error != ""): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 flex items-center gap-3">
                <i data-lucide="alert-circle" class="w-5 h-5"></i>
                <p class="text-sm font-bold"><?php echo $error; ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-6">
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Usuario</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i data-lucide="user" class="w-5 h-5 text-slate-400"></i>
                    </div>
                    <input type="text" name="usuario" required placeholder="Ej. admin" class="w-full pl-12 pr-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-brand/30 outline-none transition-all text-sm font-medium text-slate-700">
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Contraseña</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i data-lucide="lock" class="w-5 h-5 text-slate-400"></i>
                    </div>
                    <input type="password" name="password" required placeholder="••••••••" class="w-full pl-12 pr-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-brand/30 outline-none transition-all text-sm font-medium text-slate-700">
                </div>
            </div>

            <button type="submit" class="w-full bg-brand hover:bg-teal-800 text-white py-3.5 rounded-xl font-bold shadow-lg transition-all hover:scale-[1.02] hover:shadow-teal-900/30 flex items-center justify-center gap-2">
                Ingresar al Sistema <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </button>
        </form>

    </div>

    <script>lucide.createIcons();</script>
</body>
</html>