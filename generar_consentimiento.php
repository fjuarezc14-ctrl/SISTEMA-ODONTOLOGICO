<?php
require_once 'includes/auth_guard.php';

require_once 'config/conexion.php';
require_once 'models/Paciente.php';

$id_paciente = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_paciente <= 0) {
    die("ID de paciente no válido.");
}

$pacienteModel = new Paciente($conn);
$paciente = $pacienteModel->getById($id_paciente);

if (!$paciente) {
    die("Paciente no encontrado.");
}

// Datos
$nombre_clinica = "MahuDent Clínica Odontológica";
$fecha_actual = date('d/m/Y');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consentimiento Informado - <?php echo htmlspecialchars($paciente['nombre']); ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');
        body {
            font-family: 'Roboto', Arial, sans-serif;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .a4-container {
            width: 210mm;
            min-height: 297mm;
            background: white;
            margin: 20px auto;
            padding: 25mm 20mm;
            box-sizing: border-box;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0f766e;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #0f766e;
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 12px;
            color: #666;
        }
        .title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
            text-decoration: underline;
        }
        .patient-info {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .patient-info p {
            margin: 5px 0;
        }
        .content {
            font-size: 13px;
            text-align: justify;
        }
        .content h3 {
            font-size: 14px;
            color: #0f766e;
            margin-top: 15px;
            margin-bottom: 5px;
        }
        .signatures {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            width: 45%;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 60px;
            padding-top: 5px;
            font-weight: bold;
            font-size: 14px;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            color: #999;
            margin-top: 40px;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
        .print-btn {
            display: block;
            width: 200px;
            margin: 20px auto;
            padding: 10px;
            background-color: #0f766e;
            color: white;
            text-align: center;
            text-decoration: none;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            border: none;
        }
        @media print {
            body { background: white; margin: 0; padding: 0; }
            .a4-container { margin: 0; padding: 15mm; box-shadow: none; border: none; width: 100%; }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>

    <button onclick="window.print()" class="print-btn">🖨️ Imprimir Consentimiento</button>

    <div class="a4-container">
        <div class="header">
            <h1><?php echo $nombre_clinica; ?></h1>
            <p>Documento Legal y Clínico de Protección de Datos y Tratamiento</p>
        </div>

        <div class="title">CONSENTIMIENTO INFORMADO Y PROTECCIÓN DE DATOS</div>

        <div class="patient-info">
            <p><strong>Paciente:</strong> <?php echo htmlspecialchars($paciente['nombre']); ?></p>
            <p><strong>DNI/Documento:</strong> <?php echo htmlspecialchars($paciente['dni']); ?></p>
            <p><strong>Fecha de Registro:</strong> <?php echo $fecha_actual; ?></p>
        </div>

        <div class="content">
            <p>Por el presente documento, yo, el paciente o representante legal arriba mencionado, declaro libre y voluntariamente lo siguiente:</p>

            <h3>1. Tratamiento de Datos Personales (Ley de Protección de Datos)</h3>
            <p>Autorizo a <strong><?php echo $nombre_clinica; ?></strong> a la recopilación, almacenamiento y tratamiento de mis datos personales y médicos generales. Entiendo que esta información es estrictamente confidencial y será utilizada de manera exclusiva para la creación de mi Historia Clínica Odontológica, diagnósticos, presupuestos y comunicación de recordatorios de citas.</p>

            <h3>2. Autorización de Tomas Radiográficas y Fotográficas</h3>
            <p>Autorizo a los profesionales de la clínica a realizar tomas radiográficas, fotografías intraorales y extraorales necesarias para mi diagnóstico, planificación de tratamiento, seguimiento de la evolución clínica y propósitos de archivo médico. Entiendo que dicho material formará parte integral de mi historia clínica.</p>

            <h3>3. Evaluación Clínica Inicial</h3>
            <p>Doy mi consentimiento para que el personal odontológico calificado realice una evaluación clínica y odontograma inicial. Entiendo que cualquier tratamiento específico, intervención quirúrgica o procedimiento especializado requerirá un consentimiento adicional o la aprobación formal de un presupuesto detallado previo a su ejecución.</p>

            <h3>4. Compromiso de Veracidad</h3>
            <p>Declaro que los antecedentes médicos, alergias y condiciones de salud que he proporcionado (o proporcionaré) al personal de la clínica son verdaderos y no he ocultado información que pudiera poner en riesgo mi salud durante la atención odontológica.</p>
            
            <p style="margin-top: 20px;">He leído y comprendido el contenido de este documento, y al firmarlo, acepto los términos expuestos.</p>
        </div>

        <div class="signatures">
            <div class="signature-box">
                <div class="signature-line">
                    Firma del Paciente / Apoderado<br>
                    DNI: <?php echo htmlspecialchars($paciente['dni']); ?>
                </div>
            </div>
            <div class="signature-box">
                <div class="signature-line">
                    Firma / Sello de la Clínica<br>
                    <?php echo $nombre_clinica; ?>
                </div>
            </div>
        </div>

        <div class="footer">
            Generado automáticamente por el Sistema MahuDent el <?php echo $fecha_actual; ?>. Este documento debe archivarse en formato físico o digital.
        </div>
    </div>
</body>
</html>
