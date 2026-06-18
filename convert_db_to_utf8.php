<?php
require_once 'config/conexion.php';

// Solo permitir acceso a usuarios logueados como Administrador
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'Admin') {
    die("Acceso denegado: Solo el Administrador principal puede ejecutar esta herramienta de migración.");
}

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Migración de Base de Datos - MahuDent</title>
    <script src='https://cdn.tailwindcss.com'></script>
    <link href='https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;800&display=swap' rel='stylesheet'>
    <style>body { font-family: 'Montserrat', sans-serif; }</style>
</head>
<body class='bg-slate-50 min-h-screen flex items-center justify-center p-6'>
    <div class='bg-white rounded-3xl shadow-2xl p-8 max-w-2xl w-full border border-slate-100'>
        <h2 class='text-2xl font-black text-slate-800 mb-2 flex items-center gap-2'>
            <span class='text-teal-600'>⚙️</span> MIGRACIÓN UTF-8 MB4
        </h2>
        <p class='text-xs text-slate-500 font-semibold mb-6 uppercase tracking-wider'>Ajustando base de datos a codificación universal para evitar pérdida de textos y tildes</p>
        
        <div class='bg-slate-900 text-slate-200 font-mono text-xs p-5 rounded-2xl overflow-y-auto max-h-96 space-y-2 border border-slate-950 shadow-inner'>";

$tablas = [
    'pacientes',
    'citas',
    'historial_evolutivo',
    'odontograma_estado',
    'pagos',
    'presupuesto_items',
    'presupuestos',
    'recetas',
    'usuarios',
    'catalogo_tratamientos',
    'archivos_clinicos',
    'signos_vitales'
];

$errors = 0;
foreach ($tablas as $tabla) {
    echo "<div><span class='text-slate-400'>[PROCESANDO]</span> Tabla: <strong>$tabla</strong>...</div>";
    
    // SQL para convertir toda la tabla y sus columnas de texto a utf8mb4_unicode_ci
    $sql = "ALTER TABLE `$tabla` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
    
    if ($conn->query($sql)) {
        echo "<div class='text-emerald-400'>[EXITO] Tabla <strong>$tabla</strong> convertida a utf8mb4_unicode_ci.</div>";
    } else {
        $errors++;
        echo "<div class='text-red-400'>[ERROR] Falló en tabla <strong>$tabla</strong>: " . htmlspecialchars($conn->error) . "</div>";
    }
    echo "<div class='h-px bg-slate-800 my-1'></div>";
}

echo "</div>";

if ($errors === 0) {
    echo "
        <div class='mt-6 bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-2xl text-sm font-semibold'>
            🎉 ¡Migración completada con éxito! Todas las tablas ahora soportan tildes, caracteres especiales y emojis sin pérdidas ni corrupciones de texto.
        </div>";
} else {
    echo "
        <div class='mt-6 bg-red-50 border border-red-200 text-red-800 p-4 rounded-2xl text-sm font-semibold'>
            ⚠️ La migración finalizó con $errors error(es). Verifique los detalles en la consola de comandos superior.
        </div>";
}

echo "
        <div class='mt-8 flex justify-end'>
            <a href='pacientes.php' class='px-6 py-3 bg-teal-600 hover:bg-teal-700 text-white font-bold rounded-xl shadow-md transition text-sm'>
                Volver al Directorio
            </a>
        </div>
    </div>
</body>
</html>";
?>
