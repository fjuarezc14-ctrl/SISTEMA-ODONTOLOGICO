<?php
require_once 'includes/auth_guard.php';

require_once 'config/conexion.php';
require_once 'models/Pago.php';
require_once 'models/Paciente.php';
require_once 'models/Presupuesto.php';

$pago_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($pago_id <= 0) die("ID de pago no válido.");

$pagoModel = new Pago($conn);
$pago = $pagoModel->getById($pago_id);
if (!$pago) die("Pago no encontrado.");

$pacienteModel = new Paciente($conn);
$paciente = $pacienteModel->getById($pago['paciente_id']);

$presupuestoModel = new Presupuesto($conn);
$presupuesto = $presupuestoModel->getById($pago['presupuesto_id']);

$nombre_clinica = "MahuDent Clínica Odontológica";
$fecha_pago = new DateTime($pago['fecha_pago']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Pago #<?php echo str_pad($pago_id, 6, '0', STR_PAD_LEFT); ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700;900&display=swap');
        body { font-family: 'Roboto', Arial, sans-serif; background-color: #f4f4f4; color: #333; margin: 0; padding: 0; }
        .receipt-container { width: 148mm; min-height: 210mm; background: white; margin: 20px auto; padding: 15mm; box-sizing: border-box; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { text-align: center; border-bottom: 2px dashed #ccc; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { color: #0f766e; margin: 0; font-size: 22px; text-transform: uppercase; }
        .header p { margin: 5px 0 0 0; font-size: 12px; color: #666; }
        .receipt-title { text-align: center; font-size: 18px; font-weight: 900; margin-bottom: 20px; letter-spacing: 1px; color: #333; }
        .info-grid { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 20px; }
        .info-col { width: 48%; }
        .info-col p { margin: 5px 0; border-bottom: 1px solid #f0f0f0; padding-bottom: 3px; }
        .info-col strong { color: #555; display: inline-block; width: 80px; }
        .amount-box { background-color: #f8fafc; border: 2px solid #0f766e; border-radius: 8px; padding: 20px; text-align: center; margin-bottom: 20px; }
        .amount-box .label { font-size: 12px; color: #666; text-transform: uppercase; font-weight: bold; }
        .amount-box .value { font-size: 32px; font-weight: 900; color: #0f766e; margin-top: 5px; }
        .details { font-size: 13px; margin-bottom: 30px; }
        .details p { margin: 5px 0; }
        .signatures { margin-top: 60px; display: flex; justify-content: space-between; }
        .signature-box { width: 45%; text-align: center; border-top: 1px solid #000; padding-top: 5px; font-size: 12px; font-weight: bold; }
        .print-btn { display: block; width: 200px; margin: 20px auto; padding: 10px; background-color: #0f766e; color: white; text-align: center; text-decoration: none; font-weight: bold; border-radius: 5px; cursor: pointer; border: none; }
        .footer { text-align: center; font-size: 10px; color: #999; margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px; }
        @media print {
            body { background: white; margin: 0; }
            .receipt-container { margin: 0; box-shadow: none; width: 100%; border: none; }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>

    <button onclick="window.print()" class="print-btn">🖨️ Imprimir Recibo</button>

    <div class="receipt-container">
        <div class="header">
            <h1><?php echo $nombre_clinica; ?></h1>
            <p>RUC: 20123456789 | Av. Principal 123, Ciudad</p>
            <p>Tel: +51 987 654 321</p>
        </div>

        <div class="receipt-title">
            RECIBO DE CAJA N° <?php echo str_pad($pago_id, 6, '0', STR_PAD_LEFT); ?>
        </div>

        <div class="info-grid">
            <div class="info-col">
                <p><strong>Paciente:</strong> <?php echo htmlspecialchars($paciente['nombre']); ?></p>
                <p><strong>DNI:</strong> <?php echo htmlspecialchars($paciente['dni']); ?></p>
                <p><strong>Atención:</strong> Presupuesto #<?php echo str_pad($presupuesto['id'], 5, '0', STR_PAD_LEFT); ?></p>
            </div>
            <div class="info-col">
                <p><strong>Fecha:</strong> <?php echo $fecha_pago->format('d/m/Y'); ?></p>
                <p><strong>Hora:</strong> <?php echo $fecha_pago->format('H:i'); ?></p>
                <p><strong>Cajero:</strong> Administración</p>
            </div>
        </div>

        <div class="amount-box">
            <div class="label">Monto Pagado (<?php echo htmlspecialchars($pago['metodo_pago']); ?>)</div>
            <div class="value">S/ <?php echo number_format($pago['monto'], 2); ?></div>
        </div>

        <div class="details">
            <p><strong>Tipo de Pago:</strong> <?php echo htmlspecialchars($pago['tipo']); ?></p>
            <p><strong>Total Tratamiento:</strong> S/ <?php echo number_format($presupuesto['total'], 2); ?></p>
            <p><strong>Saldo Pendiente:</strong> S/ <?php echo number_format($presupuesto['saldo_pendiente'], 2); ?></p>
            <?php if (!empty($pago['notas'])): ?>
                <p><strong>Notas:</strong> <?php echo htmlspecialchars($pago['notas']); ?></p>
            <?php endif; ?>
        </div>

        <div class="signatures">
            <div class="signature-box">
                Firma Conforme<br>
                Paciente
            </div>
            <div class="signature-box">
                Sello y Firma<br>
                <?php echo $nombre_clinica; ?>
            </div>
        </div>

        <div class="footer">
            Generado automáticamente por el Sistema MahuDent. Documento válido como comprobante de pago interno.
        </div>
    </div>
</body>
</html>
