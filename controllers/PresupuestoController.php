<?php
require_once 'config/conexion.php';
require_once 'models/CatalogoTratamiento.php';
require_once 'models/Presupuesto.php';

class PresupuestoController {
    private $catalogoModel;
    private $presupuestoModel;

    public function __construct() {
        global $conn;
        $this->catalogoModel = new CatalogoTratamiento($conn);
        $this->presupuestoModel = new Presupuesto($conn);
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
}
?>
