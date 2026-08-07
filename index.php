<?php
require_once __DIR__ . '/includes/session_init.php';

// Si ya hay sesión iniciada, saltamos directo al dashboard
if(isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

// Mostrar mensaje de timeout si existe
if (isset($_SESSION['timeout_msg'])) {
    $error = $_SESSION['timeout_msg'];
    unset($_SESSION['timeout_msg']);
}

// Lógica para cuando se presiona el botón de "Ingresar"
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include 'config/conexion.php';
    
    $usuario = $_POST['usuario'];
    $password_ingresada = $_POST['password'];

    // Buscamos al usuario en la base de datos
    $sql = "SELECT id, nombre, password, rol, intentos_fallidos, bloqueado_hasta, estado_activo FROM usuarios WHERE usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $fila = $resultado->fetch_assoc();
        
        // 0. Verificar si la cuenta está inhabilitada
        if (isset($fila['estado_activo']) && intval($fila['estado_activo']) === 0) {
            $error = "Tu cuenta está inhabilitada. Comunícate con el Administrador.";
        }
        
        // 1. Verificar si la cuenta está bloqueada
        if ($error === "" && $fila['bloqueado_hasta']) {
            $tiempo_bloqueo = strtotime($fila['bloqueado_hasta']);
            $ahora = time();
            
            if ($ahora < $tiempo_bloqueo) {
                $minutos_restantes = ceil(($tiempo_bloqueo - $ahora) / 60);
                $error = "Cuenta bloqueada por seguridad. Inténtalo de nuevo en {$minutos_restantes} minuto(s).";
            } else {
                // El bloqueo expiró, limpiar contadores
                $stmt_clear = $conn->prepare("UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = ?");
                $stmt_clear->bind_param("i", $fila['id']);
                $stmt_clear->execute();
                $fila['intentos_fallidos'] = 0;
            }
        }

        // Solo procedemos si no está bloqueado
        if ($error === "") {
            // 2. Verificamos si la contraseña coincide
            if (password_verify($password_ingresada, $fila['password'])) {
                // Login exitoso: Limpiar intentos
                $stmt_clear = $conn->prepare("UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = ?");
                $stmt_clear->bind_param("i", $fila['id']);
                $stmt_clear->execute();
                
                // Regenerar ID de Sesión (Session Fixation Protection)
                session_regenerate_id(true);

                $_SESSION['usuario_id'] = $fila['id'];
                $_SESSION['usuario_nombre'] = $fila['nombre'];
                $_SESSION['usuario_rol'] = $fila['rol'];
                header("Location: dashboard.php");
                exit;
            } else {
                // Login fallido: Incrementar intentos
                $intentos = intval($fila['intentos_fallidos']) + 1;
                
                if ($intentos >= 5) {
                    $error = "Demasiados intentos fallidos. Cuenta bloqueada por 15 minutos.";
                    $stmt_fail = $conn->prepare("UPDATE usuarios SET intentos_fallidos = ?, bloqueado_hasta = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = ?");
                    $stmt_fail->bind_param("ii", $intentos, $fila['id']);
                    $stmt_fail->execute();
                } else {
                    $intentos_restantes = 5 - $intentos;
                    $error = "Contraseña incorrecta. Te quedan {$intentos_restantes} intento(s).";
                    $stmt_fail = $conn->prepare("UPDATE usuarios SET intentos_fallidos = ? WHERE id = ?");
                    $stmt_fail->bind_param("ii", $intentos, $fila['id']);
                    $stmt_fail->execute();
                }
            }
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
    </style>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#3a596a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="MahuDent">
    <link rel="apple-touch-icon" href="icono-192x192.png">
</head>
<body class="flex items-center justify-center h-screen bg-slate-100">

    <div class="bg-white p-10 rounded-3xl shadow-2xl w-full max-w-md border border-slate-100">
        
        <div class="flex flex-col items-center mb-8">
            <div class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center overflow-hidden shadow-lg mb-4 border border-slate-100">
                <img src="assets/logo_icon.png" alt="Logo MahuDent" class="w-full h-full object-contain p-2">
            </div>
            <img src="assets/logo_text_dark.png" alt="MahuDent" class="h-10 w-auto object-contain mb-2">
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
                    <input type="text" name="usuario" required placeholder="Ej. admin" class="w-full pl-12 pr-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-teal-200 focus:border-teal-500 outline-none transition-all text-sm font-medium text-slate-700">
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Contraseña</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i data-lucide="lock" class="w-5 h-5 text-slate-400"></i>
                    </div>
                    <input type="password" name="password" required placeholder="••••••••" class="w-full pl-12 pr-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-teal-200 focus:border-teal-500 outline-none transition-all text-sm font-medium text-slate-700">
                </div>
            </div>

            <button type="submit" class="w-full bg-brand hover:bg-teal-800 text-white py-3.5 rounded-xl font-bold shadow-lg transition-all hover:scale-[1.02] hover:shadow-teal-900/30 flex items-center justify-center gap-2">
                Ingresar al Sistema <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </button>
        </form>

    </div>

    <script>
        lucide.createIcons();
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(reg => console.log('Service Worker registrado con éxito', reg))
                    .catch(err => console.error('Error al registrar el Service Worker', err));
            });
        }
    </script>
</body>
</html>
