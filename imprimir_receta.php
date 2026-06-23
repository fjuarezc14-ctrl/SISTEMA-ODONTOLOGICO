<?php
require_once 'config/conexion.php';
require_once 'includes/auth_guard.php';if (!isset($_SESSION['usuario_id'])) die("No autorizado");

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
    <title>Receta M&#233;dica - <?php echo htmlspecialchars($receta['paciente_nombre']); ?></title>
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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap');
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; background: white; }
            @page { size: A4; margin: 0; }
            #btn-print { display: none; }
        }
        body { font-family: 'Montserrat', sans-serif; background: #f1f5f9; min-height: 100vh; padding: 2rem; }
        .hoja { background: white; max-width: 210mm; min-height: 297mm; margin: 0 auto; box-shadow: 0 10px 30px rgb(0 0 0 / 0.12); position: relative; padding: 0; box-sizing: border-box; overflow: hidden; z-index: 1; }
        .hoja::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-10deg);
            width: 130mm;
            height: 130mm;
            background-image: url('assets/logo_watermark.png');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            pointer-events: none;
            z-index: -1;
        }
        @media print { .hoja { box-shadow: none; margin: 0; min-height: 100%; } body { padding: 0; } }
    </style>
</head>
<body>
    <button id="btn-print" onclick="window.print()" class="fixed bottom-8 right-8 bg-teal-700 text-white px-6 py-3 rounded-full shadow-lg font-bold hover:bg-teal-800 transition flex items-center gap-2 z-50">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg> Imprimir
    </button>

    <div class="hoja">
        <!-- Header con franja teal -->
        <div style="background: linear-gradient(135deg, #3a596a 0%, #937ec2 100%); padding: 28px 40px; position: relative;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div style="display:flex; align-items:center; gap:14px;">
                    <div style="width:48px;height:48px;background:white;border-radius:12px;display:flex;align-items:center;justify-content:center;overflow:hidden;padding:4px;">
                        <img src="assets/logo_icon.png" alt="Logo" style="width:100%;height:100%;object-fit:contain;">
                    </div>
                    <div style="display:flex;flex-direction:column;justify-content:center;">
                        <img src="assets/logo_text.png" alt="MahuDent" style="height:22px;width:auto;object-fit:contain;">
                        <p style="font-family:'Montserrat',sans-serif;font-weight:600;font-size:9px;color:#ede8f7;letter-spacing:2px;margin:4px 0 0 0;">CLÍNICA ODONTOLÓGICA</p>
                    </div>
                </div>
                <div style="text-align:right;">
                    <p style="font-family:'Montserrat',sans-serif;font-weight:800;font-size:18px;color:white;margin:0;">RECETA M&#201;DICA</p>
                    <p style="font-family:'Montserrat',sans-serif;font-weight:500;font-size:11px;color:#ede8f7;margin:4px 0 0 0;">N&#176;<?php echo str_pad($receta['id'], 5, '0', STR_PAD_LEFT); ?></p>
                </div>
            </div>
        </div>

        <!-- Datos del paciente -->
        <div style="padding: 0 40px;">
            <div style="margin-top:24px; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden;">
                <table style="width:100%; font-family:'Montserrat',sans-serif; font-size:13px; border-collapse:collapse;">
                    <tbody>
                        <tr style="background:#f8fafc;">
                            <td style="font-weight:800; color:#3a596a; padding:10px 14px; border-bottom:1px solid #e2e8f0; width:22%; text-transform:uppercase; font-size:11px; letter-spacing:0.5px;">Paciente</td>
                            <td style="padding:10px 14px; border-bottom:1px solid #e2e8f0; border-left:1px solid #e2e8f0; font-weight:600; color:#1e293b;" colspan="3"><?php echo htmlspecialchars($receta['paciente_nombre']); ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight:800; color:#3a596a; padding:10px 14px; border-bottom:1px solid #e2e8f0; text-transform:uppercase; font-size:11px; letter-spacing:0.5px;">Edad</td>
                            <td style="padding:10px 14px; border-bottom:1px solid #e2e8f0; border-left:1px solid #e2e8f0; font-weight:500; color:#334155;"><?php echo htmlspecialchars($edad); ?></td>
                            <td style="font-weight:800; color:#3a596a; padding:10px 14px; border-bottom:1px solid #e2e8f0; border-left:1px solid #e2e8f0; background:#f8fafc; text-transform:uppercase; font-size:11px; letter-spacing:0.5px; width:15%;">Fecha</td>
                            <td style="padding:10px 14px; border-bottom:1px solid #e2e8f0; border-left:1px solid #e2e8f0; font-weight:500; color:#334155;"><?php echo date('d/m/Y', strtotime($receta['fecha'])); ?></td>
                        </tr>
                        <tr style="background:#f8fafc;">
                            <td style="font-weight:800; color:#3a596a; padding:10px 14px; text-transform:uppercase; font-size:11px; letter-spacing:0.5px;">Diagn&#243;stico</td>
                            <td style="padding:10px 14px; border-left:1px solid #e2e8f0; font-weight:500; color:#334155;" colspan="3"><?php echo htmlspecialchars($receta['diagnostico']); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Prescripcion -->
            <div style="margin-top:28px; min-height:420px;">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:20px; border-bottom:2px solid #e2e8f0; padding-bottom:10px;">
                    <span style="font-family:serif; font-style:italic; font-size:32px; font-weight:bold; color:#3a596a;">Rp.</span>
                    <span style="font-family:'Montserrat',sans-serif; font-size:11px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:1px;">Prescripci&#243;n</span>
                </div>
                
                <div style="font-family:'Montserrat',sans-serif; font-size:14px; line-height:2; color:#334155; padding-left:20px; white-space:pre-wrap; font-weight:500;"><?php echo htmlspecialchars($receta['contenido']); ?></div>
            </div>
        </div>

        <!-- Firma del doctor -->
        <div style="position:absolute; bottom:100px; left:0; right:0; text-align:center;">
            <div style="width:260px; margin:0 auto; border-top:2px solid #334155; padding-top:10px;">
                <p style="font-family:'Montserrat',sans-serif; font-weight:800; font-size:15px; color:#1e293b; margin:0;">Dr. <?php echo htmlspecialchars($receta['doctor_nombre']); ?></p>
                <p style="font-family:'Montserrat',sans-serif; font-weight:700; font-size:11px; color:#3a596a; text-transform:uppercase; letter-spacing:2px; margin:5px 0 0 0;">Odont&#243;logo</p>
                <?php if ($receta['colegiatura']): ?>
                <p style="font-family:'Montserrat',sans-serif; font-weight:500; font-size:11px; color:#64748b; margin:3px 0 0 0;">COP: <?php echo htmlspecialchars($receta['colegiatura']); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer con brand color -->
        <div style="position:absolute; bottom:30px; left:30px; right:30px; background:linear-gradient(135deg, #3a596a, #937ec2); border-radius:999px; padding:10px 24px; display:flex; justify-content:space-between; align-items:center; font-family:'Montserrat',sans-serif; font-size:11px; font-weight:600; color:white;">
            <div style="display:flex; align-items:center; gap:6px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                <span>Cel/WhatsApp: 941124848</span>
            </div>
            <div style="display:flex; align-items:center; gap:6px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                <span>Instagram: @mahudent</span>
            </div>
            <div style="display:flex; align-items:center; gap:6px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                <span>Jr. San Sebastián 116</span>
            </div>
        </div>
    </div>
    <script>
        if (window.location.search.includes('print=1')) {
            setTimeout(() => window.print(), 500);
        }
    </script>
</body>
</html>
