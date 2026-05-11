<?php
require_once 'config/conexion.php';
require_once 'models/CatalogoTratamiento.php';
require_once 'models/Presupuesto.php';
require_once 'models/Pago.php';

class PresupuestoController {
    private $catalogoModel;
    private $presupuestoModel;
    private $pagoModel;

    public function __construct() {
        global $conn;
        $this->catalogoModel = new CatalogoTratamiento($conn);
        $this->presupuestoModel = new Presupuesto($conn);
        $this->pagoModel = new Pago($conn);
    }

    // --- Catálogo ---
    public function getCatalogo() {
        return $this->catalogoModel->getAll();
    }

    public function getTratamientosPorEstado($estado) {
        return $this->catalogoModel->getByEstadoOdontograma($estado);
    }

    // --- Presupuestos ---
    public function getPresupuestosPaciente($paciente_id) {
        return $this->presupuestoModel->getByPaciente($paciente_id);
    }

    /**
     * Obtiene TODOS los presupuestos con datos del paciente (para la vista global).
     */
    public function getTodosPresupuestos() {
        return $this->presupuestoModel->getAll();
    }

    public function getPresupuesto($id) {
        $presupuesto = $this->presupuestoModel->getById($id);
        if ($presupuesto) {
            $presupuesto['items'] = $this->presupuestoModel->getItems($id);
        }
        return $presupuesto;
    }

    /**
     * Crea un presupuesto vacío en borrador para agregar ítems manualmente.
     */
    public function crearVacio($paciente_id, $doctor_id = null) {
        $fecha_emision = date('Y-m-d');
        $fecha_vigencia = date('Y-m-d', strtotime('+30 days'));
        
        $presupuesto_id = $this->presupuestoModel->create(
            $paciente_id, $doctor_id, $fecha_emision, $fecha_vigencia,
            0, 0, 0, 0, ''
        );

        return $presupuesto_id;
    }

    /**
     * Genera un presupuesto automático basado en los hallazgos del odontograma.
     * AGRUPA por diente para evitar duplicados (múltiples caras = 1 tratamiento).
     */
    public function generarDesdeOdontograma($paciente_id, $hallazgos, $doctor_id = null) {
        $fecha_emision = date('Y-m-d');
        $fecha_vigencia = date('Y-m-d', strtotime('+30 days'));
        
        // Crear el presupuesto en borrador
        $presupuesto_id = $this->presupuestoModel->create(
            $paciente_id, $doctor_id, $fecha_emision, $fecha_vigencia,
            0, 0, 0, 0, 'Presupuesto generado automáticamente desde odontograma'
        );

        if (!$presupuesto_id) return false;

        $subtotal = 0;

        // Agrupar hallazgos por diente+estado para evitar duplicados
        $dientes_procesados = [];
        foreach ($hallazgos as $hallazgo) {
            $estado = $hallazgo['estado'] ?? '';
            $diente = $hallazgo['diente_numero'] ?? null;

            if (empty($estado)) continue;

            // Clave única: diente + estado
            $clave = $diente . '_' . $estado;
            if (isset($dientes_procesados[$clave])) continue;
            $dientes_procesados[$clave] = true;

            // Buscar tratamientos sugeridos del catálogo
            $tratamientos = $this->catalogoModel->getByEstadoOdontograma($estado);
            
            if (!empty($tratamientos)) {
                // Tomar el primer tratamiento sugerido como predeterminado
                $trat = $tratamientos[0];
                $precio = $trat['precio_base'];
                $item_subtotal = $precio * 1;

                $this->presupuestoModel->addItem(
                    $presupuesto_id,
                    $trat['id'],
                    $diente,
                    $trat['nombre'] . ($diente ? " (Pieza #{$diente})" : ''),
                    1,
                    $precio,
                    null,
                    $item_subtotal
                );

                $subtotal += $item_subtotal;
            }
        }

        // Actualizar totales del presupuesto
        $this->presupuestoModel->updateTotales($presupuesto_id, $subtotal, 0, 0, $subtotal);

        return $presupuesto_id;
    }

    /**
     * Recalcula los totales del presupuesto incluyendo descuentos.
     */
    public function recalcularTotales($presupuesto_id, $descuento_porcentaje = 0) {
        $items = $this->presupuestoModel->getItems($presupuesto_id);
        $subtotal = 0;

        foreach ($items as $item) {
            $precio_final = $item['precio_ajustado'] ?? $item['precio_unitario'];
            $subtotal += $precio_final * $item['cantidad'];
        }

        $descuento_monto = $subtotal * ($descuento_porcentaje / 100);
        $total = $subtotal - $descuento_monto;

        return $this->presupuestoModel->updateTotales($presupuesto_id, $subtotal, $descuento_porcentaje, $descuento_monto, $total);
    }

    /**
     * Agrega un ítem al presupuesto.
     */
    public function agregarItem($presupuesto_id, $data) {
        $tratamiento_id = !empty($data['tratamiento_id']) ? intval($data['tratamiento_id']) : null;
        $diente_numero = !empty($data['diente_numero']) ? intval($data['diente_numero']) : null;
        $descripcion = $data['descripcion'] ?? 'Tratamiento';
        $cantidad = intval($data['cantidad'] ?? 1);
        $precio_unitario = floatval($data['precio_unitario'] ?? 0);
        $precio_ajustado = isset($data['precio_ajustado']) && $data['precio_ajustado'] !== null ? floatval($data['precio_ajustado']) : null;
        $subtotal = ($precio_ajustado ?? $precio_unitario) * $cantidad;

        $result = $this->presupuestoModel->addItem(
            $presupuesto_id, $tratamiento_id, $diente_numero,
            $descripcion, $cantidad, $precio_unitario, $precio_ajustado, $subtotal
        );

        if ($result) {
            $presupuesto = $this->presupuestoModel->getById($presupuesto_id);
            $this->recalcularTotales($presupuesto_id, $presupuesto['descuento_porcentaje']);
        }

        return $result;
    }

    /**
     * Actualiza un ítem existente del presupuesto.
     */
    public function actualizarItem($item_id, $data) {
        $descripcion = $data['descripcion'] ?? 'Tratamiento';
        $cantidad = intval($data['cantidad'] ?? 1);
        $precio_unitario = floatval($data['precio_unitario'] ?? 0);
        $precio_ajustado = isset($data['precio_ajustado']) && $data['precio_ajustado'] !== null ? floatval($data['precio_ajustado']) : null;
        $subtotal = ($precio_ajustado ?? $precio_unitario) * $cantidad;

        return $this->presupuestoModel->updateItem($item_id, $descripcion, $cantidad, $precio_unitario, $precio_ajustado, $subtotal);
    }

    /**
     * Elimina un ítem y recalcula totales.
     */
    public function eliminarItem($presupuesto_id, $item_id) {
        $result = $this->presupuestoModel->removeItem($item_id);
        if ($result) {
            $presupuesto = $this->presupuestoModel->getById($presupuesto_id);
            $this->recalcularTotales($presupuesto_id, $presupuesto['descuento_porcentaje']);
        }
        return $result;
    }

    public function cambiarEstado($id, $estado) {
        return $this->presupuestoModel->updateEstado($id, $estado);
    }

    public function eliminar($id) {
        return $this->presupuestoModel->delete($id);
    }

    // --- PAGOS ---

    /**
     * Registra un pago y actualiza saldos del presupuesto.
     * Retorna el pago registrado o false.
     */
    public function registrarPago($presupuesto_id, $data) {
        $presupuesto = $this->presupuestoModel->getById($presupuesto_id);
        if (!$presupuesto) return false;

        $monto = floatval($data['monto'] ?? 0);
        if ($monto <= 0) return false;

        $saldo_actual = floatval($presupuesto['total']) - floatval($presupuesto['monto_pagado']);
        if ($monto > $saldo_actual + 0.01) return false; // No permitir sobrepago

        $metodo = $data['metodo_pago'] ?? 'Efectivo';
        $tipo = $data['tipo'] ?? 'Parcial';
        $comprobante_tipo = $data['comprobante_tipo'] ?? 'Boleta';
        $comprobante_numero = null;
        if ($comprobante_tipo !== 'Ninguno') {
            $comprobante_numero = $this->pagoModel->getSiguienteComprobante($comprobante_tipo);
        }
        $notas = $data['notas'] ?? '';
        $registrado_por = $data['registrado_por'] ?? null;

        $pago_id = $this->pagoModel->registrar(
            $presupuesto_id, $presupuesto['paciente_id'], $monto,
            $metodo, $tipo, $comprobante_tipo, $comprobante_numero,
            $notas, $registrado_por
        );

        if ($pago_id) {
            // Actualizar saldos en el presupuesto
            $this->actualizarSaldos($presupuesto_id);
            return $this->pagoModel->getById($pago_id);
        }
        return false;
    }

    /**
     * Recalcula monto_pagado y saldo_pendiente del presupuesto.
     */
    public function actualizarSaldos($presupuesto_id) {
        $total_pagado = $this->pagoModel->getTotalPagado($presupuesto_id);
        $presupuesto = $this->presupuestoModel->getById($presupuesto_id);
        $saldo = floatval($presupuesto['total']) - $total_pagado;

        global $conn;
        $sql = "UPDATE presupuestos SET monto_pagado = ?, saldo_pendiente = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ddi", $total_pagado, $saldo, $presupuesto_id);
        return $stmt->execute();
    }

    public function getPagosPresupuesto($presupuesto_id) {
        return $this->pagoModel->getByPresupuesto($presupuesto_id);
    }

    public function getPagosPaciente($paciente_id) {
        return $this->pagoModel->getByPaciente($paciente_id);
    }

    /**
     * Info financiera completa de un presupuesto.
     */
    public function getResumenFinanciero($presupuesto_id) {
        $presupuesto = $this->getPresupuesto($presupuesto_id);
        if (!$presupuesto) return null;

        $pagos = $this->pagoModel->getByPresupuesto($presupuesto_id);
        $total_pagado = $this->pagoModel->getTotalPagado($presupuesto_id);
        $saldo = floatval($presupuesto['total']) - $total_pagado;
        $porcentaje_pagado = floatval($presupuesto['total']) > 0 ? ($total_pagado / floatval($presupuesto['total'])) * 100 : 0;

        return [
            'presupuesto' => $presupuesto,
            'pagos' => $pagos,
            'total_presupuesto' => floatval($presupuesto['total']),
            'total_pagado' => $total_pagado,
            'saldo_pendiente' => $saldo,
            'porcentaje_pagado' => round($porcentaje_pagado, 1),
            'esta_pagado' => $saldo <= 0.01
        ];
    }
}
?>
