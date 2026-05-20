<?php
require_once 'config/conexion.php';
session_start();
if (!isset($_SESSION['usuario_id'])) die("No autorizado");

$id = intval($_GET['id'] ?? 0);
if (!$id) die("ID requerido");

$stmt = $conn->prepare("SELECT r.*, p.nombre as paciente_nombre, p.fecha_nacimiento, p.telefono as paciente_telefono, u.nombre as doctor_nombre, u.colegiatura FROM recetas r JOIN pacientes p ON r.paciente_id = p.id JOIN usuarios u ON r.doctor_id = u.id WHERE r.id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$receta = $stmt->get_result()->fetch_assoc();
if (!$receta) die("Receta no encontrada");

// Calcular edad
$edad = '';
if ($receta['fecha_nacimiento']) {
    $nace = new DateTime($receta['fecha_nacimiento']);
    $hoy = new DateTime();
    $edad = $hoy->diff($nace)->y . ' años';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Receta Médica - <?php echo htmlspecialchars($receta['paciente_nombre']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; background: white; }
            @page { size: A4; margin: 0; }
            #btn-print { display: none; }
        }
        body { font-family: 'Arial', sans-serif; background: #f1f5f9; min-height: 100vh; padding: 2rem; }
        .hoja { background: white; max-width: 800px; min-height: 1100px; margin: 0 auto; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1); position: relative; padding: 3rem 4rem; box-sizing: border-box; }
        @media print { .hoja { box-shadow: none; margin: 0; min-height: 100%; } body { padding: 0; } }
        
        .header-bg { position: absolute; top: 0; left: 0; right: 0; height: 180px; background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23e0f2fe" fill-opacity="1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,149.3C960,160,1056,160,1152,149.3C1248,139,1344,117,1392,106.7L1440,96L1440,0L1392,0C1344,0,1248,0,1152,0C1056,0,960,0,864,0C768,0,672,0,576,0C480,0,384,0,288,0C192,0,96,0,48,0L0,0Z"></path></svg>') no-repeat top center; background-size: cover; z-index: 0; }
        .footer-bg { position: absolute; bottom: 40px; left: 4rem; right: 4rem; background: #e0f2fe; border-radius: 999px; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; z-index: 10; font-size: 12px; font-weight: bold; color: #0369a1; }
    </style>
</head>
<body>
    <button id="btn-print" onclick="window.print()" class="fixed bottom-8 right-8 bg-sky-600 text-white px-6 py-3 rounded-full shadow-lg font-bold hover:bg-sky-700 transition flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg> Imprimir
    </button>

    <div class="hoja">
        <div class="header-bg"></div>
        
        <div class="relative z-10 flex justify-between items-center mb-10 pt-2">
            <div class="flex items-center gap-3">
                <!-- Logo genérico o de MahuDent -->
                <div class="w-12 h-12 bg-sky-600 rounded-lg flex items-center justify-center text-white font-black text-2xl">M</div>
                <div>
                    <h1 class="text-2xl font-black text-sky-900 leading-none">MAHUDENT</h1>
                    <p class="text-sky-600 font-bold tracking-widest text-xs mt-1">CLÍNICA DENTAL</p>
                </div>
            </div>
            <div class="flex gap-3 text-slate-800">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
            </div>
        </div>

        <div class="relative z-10 mb-8 border border-sky-100 rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <tbody>
                    <tr class="bg-sky-50/50">
                        <td class="font-bold text-sky-900 py-2 px-3 border-b border-sky-100 w-1/4">PACIENTE</td>
                        <td class="py-2 px-3 border-b border-sky-100 border-l" colspan="3"><?php echo htmlspecialchars($receta['paciente_nombre']); ?></td>
                    </tr>
                    <tr>
                        <td class="font-bold text-sky-900 py-2 px-3 border-b border-sky-100 w-1/4">EDAD</td>
                        <td class="py-2 px-3 border-b border-sky-100 border-l"><?php echo htmlspecialchars($edad); ?></td>
                        <td class="font-bold text-sky-900 py-2 px-3 border-b border-sky-100 border-l bg-sky-50/50 w-1/4">FECHA</td>
                        <td class="py-2 px-3 border-b border-sky-100 border-l"><?php echo date('d/m/Y', strtotime($receta['fecha'])); ?></td>
                    </tr>
                    <tr class="bg-sky-50/50">
                        <td class="font-bold text-sky-900 py-2 px-3 border-b border-sky-100 w-1/4">DIAGNÓSTICO</td>
                        <td class="py-2 px-3 border-b border-sky-100 border-l" colspan="3"><?php echo htmlspecialchars($receta['diagnostico']); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="relative z-10">
            <p class="font-serif italic text-2xl font-bold text-slate-800 mb-6">Rp.</p>
            
            <div class="text-slate-800 text-lg leading-loose pl-10 whitespace-pre-wrap font-medium"><?php echo htmlspecialchars($receta['contenido']); ?></div>
        </div>

        <div class="absolute bottom-32 left-0 right-0 text-center">
            <div class="w-64 mx-auto border-t-2 border-slate-800 pt-2">
                <p class="font-bold text-slate-800 text-lg">Dr. <?php echo htmlspecialchars($receta['doctor_nombre']); ?></p>
                <p class="text-slate-600 font-bold uppercase tracking-wider text-sm mt-1">ODONTÓLOGO</p>
                <?php if ($receta['colegiatura']): ?>
                <p class="text-slate-500 font-medium text-sm mt-0.5">COP: <?php echo htmlspecialchars($receta['colegiatura']); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="footer-bg">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                <span>Teléfono de contacto de la clínica</span>
            </div>
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                <span>www.mahudent.com</span>
            </div>
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                <span>Dirección de la clínica</span>
            </div>
        </div>
    </div>
    <script>
        // Auto-print after 500ms
        setTimeout(() => window.print(), 500);
    </script>
</body>
</html>
