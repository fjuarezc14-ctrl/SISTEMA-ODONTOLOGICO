<?php
session_start();
if(!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'config/conexion.php';
require_once 'models/Paciente.php';

$id_paciente = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
$medicamentos = isset($_POST['medicamentos']) ? $_POST['medicamentos'] : (isset($_GET['med']) ? $_GET['med'] : '');
$indicaciones = isset($_POST['indicaciones']) ? $_POST['indicaciones'] : (isset($_GET['ind']) ? $_GET['ind'] : '');

if ($id_paciente <= 0) die("ID de paciente no válido.");

$pacienteModel = new Paciente($conn);
$paciente = $pacienteModel->getById($id_paciente);
if (!$paciente) die("Paciente no encontrado.");

$nombre_clinica = "MahuDent Clínica Odontológica";
$fecha_actual = date('d/m/Y');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Receta Médica - <?php echo htmlspecialchars($paciente['nombre']); ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');
        body { font-family: 'Roboto', Arial, sans-serif; color: #333; line-height: 1.6; margin: 0; padding: 0; background-color: #f4f4f4; }
        .a5-container {
            width: 148mm;
            min-height: 210mm;
            background: white;
            margin: 20px auto;
            padding: 15mm 15mm;
            box-sizing: border-box;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
        }
        .header { text-align: center; border-bottom: 2px solid #0f766e; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { color: #0f766e; margin: 0; font-size: 22px; text-transform: uppercase; letter-spacing: 1px; }
        .header p { margin: 3px 0 0 0; font-size: 11px; color: #666; }
        .title { text-align: center; font-size: 16px; font-weight: bold; margin-bottom: 15px; letter-spacing: 2px; }
        .patient-info { font-size: 13px; margin-bottom: 20px; border-bottom: 1px dashed #ccc; padding-bottom: 10px; }
        .patient-info table { width: 100%; }
        .patient-info td { padding: 3px 0; }
        
        .prescription-body { min-height: 100mm; }
        .section-title { font-size: 14px; font-weight: bold; color: #0f766e; margin-bottom: 10px; border-left: 3px solid #0f766e; padding-left: 8px; }
        .content-box { font-size: 13px; white-space: pre-wrap; margin-bottom: 20px; padding: 10px; background: #f8fafc; border-radius: 5px; border: 1px solid #e2e8f0; }
        
        .footer-rx { position: absolute; bottom: 20mm; left: 15mm; right: 15mm; display: flex; justify-content: space-between; align-items: flex-end; }
        .signature-line { border-top: 1px solid #000; width: 60px; text-align: center; padding-top: 5px; font-size: 12px; font-weight: bold; }
        .validity { font-size: 10px; color: #999; text-align: center; position: absolute; bottom: 10mm; left: 0; right: 0; }
        
        .print-btn { display: block; width: 200px; margin: 20px auto; padding: 10px; background-color: #0f766e; color: white; text-align: center; text-decoration: none; font-weight: bold; border-radius: 5px; cursor: pointer; border: none; }
        
        @media print {
            body { background: white; margin: 0; padding: 0; }
            .a5-container { margin: 0; padding: 10mm; box-shadow: none; border: none; width: 100%; min-height: auto; }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>

    <button onclick="window.print()" class="print-btn">🖨️ Imprimir Receta</button>

    <div class="a5-container">
        <div class="header">
            <h1><?php echo $nombre_clinica; ?></h1>
            <p>Odontología Especializada Integral</p>
            <p>Av. Principal 123, Ciudad | Tel: 987 654 321</p>
        </div>

        <div class="title">RECETA MÉDICA</div>

        <div class="patient-info">
            <table>
                <tr>
                    <td style="width: 70%;"><strong>Paciente:</strong> <?php echo htmlspecialchars($paciente['nombre']); ?></td>
                    <td style="width: 30%; text-align: right;"><strong>Fecha:</strong> <?php echo $fecha_actual; ?></td>
                </tr>
                <tr>
                    <td><strong>DNI:</strong> <?php echo htmlspecialchars($paciente['dni']); ?></td>
                    <?php 
                        $edad = '-';
                        if (!empty($paciente['fecha_nacimiento'])) {
                            $fecha_nac = new DateTime($paciente['fecha_nacimiento']);
                            $hoy = new DateTime();
                            $edad = $hoy->diff($fecha_nac)->y . ' años';
                        }
                    ?>
                    <td style="text-align: right;"><strong>Edad:</strong> <?php echo $edad; ?></td>
                </tr>
            </table>
        </div>

        <div class="prescription-body">
            <div class="section-title">Rp. (Medicamentos)</div>
            <div class="content-box">
<?php echo htmlspecialchars($medicamentos ?: 'Ningún medicamento prescrito.'); ?>
            </div>

            <div class="section-title">Indicaciones</div>
            <div class="content-box">
<?php echo htmlspecialchars($indicaciones ?: 'Ninguna indicación adicional.'); ?>
            </div>
        </div>

        <div class="footer-rx">
            <div>
                <p style="margin:0; font-size: 11px; color:#555;"><strong>Próxima cita:</strong> _______________</p>
            </div>
            <div class="signature-line" style="width: 150px;">
                Firma y Sello<br>Odontólogo Tratante
            </div>
        </div>
        
        <div class="validity">
            * Esta receta tiene validez por 3 días a partir de su emisión.
        </div>
    </div>
</body>
</html>
