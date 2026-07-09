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
            $presupuesto['token'] = md5($id . 'mahudent_shared_presupuesto_salt_2026');
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
    private function buscarTratamientoPorNombre($nombre) {
        global $conn;
        $sql = "SELECT id, nombre, precio_base FROM catalogo_tratamientos WHERE nombre LIKE ? AND activo = 1 LIMIT 1";
        $stmt = $conn->prepare($sql);
        $term = '%' . $nombre . '%';
        $stmt->bind_param("s", $term);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->num_rows > 0 ? $res->fetch_assoc() : null;
    }

    /**
     * Genera un presupuesto automático basado en los hallazgos del odontograma.
     * Agrupa y calcula tratamientos de forma inteligente (simple/compuesta/compleja, extracciones anatómicas).
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

        // Agrupar hallazgos por diente para analizarlos
        $dientes_hallazgos = [];
        foreach ($hallazgos as $hallazgo) {
            $diente = $hallazgo['diente_numero'] ?? null;
            if (!$diente) continue;
            
            if (!isset($dientes_hallazgos[$diente])) {
                $dientes_hallazgos[$diente] = [];
            }
            $dientes_hallazgos[$diente][] = $hallazgo;
        }

        foreach ($dientes_hallazgos as $diente => $caras) {
            // Contamos caras con caries/fractura/defectuosa (patologías de curación)
            $caras_patologia_restauracion = [];
            $otras_patologias = [];
            
            foreach ($caras as $cara) {
                $estado = $cara['estado'] ?? '';
                if (empty($estado)) continue;
                
                if ($estado === 'caries' || $estado === 'fractura' || $estado === 'restauracion_defectuosa') {
                    $caras_patologia_restauracion[] = $cara['cara_afectada'];
                } else {
                    $otras_patologias[$estado] = true;
                }
            }
            
            // 1. Procesar Restauraciones (Curaciones)
            if (!empty($caras_patologia_restauracion)) {
                $cant_caras = count(array_unique($caras_patologia_restauracion));
                
                if ($cant_caras === 1) {
                    $nombre_buscar = 'Curacion Simple';
                } elseif ($cant_caras === 2) {
                    $nombre_buscar = 'Curacion Compuesta';
                } else {
                    $nombre_buscar = 'Curacion Compleja';
                }
                
                $trat = $this->buscarTratamientoPorNombre($nombre_buscar);
                if (!$trat) {
                    if ($cant_caras === 1) {
                        $nombre_buscar = 'Resina Simple';
                    } else {
                        $nombre_buscar = 'Resina Compuesta';
                    }
                    $trat = $this->buscarTratamientoPorNombre($nombre_buscar);
                }
                
                if ($trat) {
                    $precio = $trat['precio_base'];
                    $item_subtotal = $precio * 1;
                    $this->presupuestoModel->addItem(
                        $presupuesto_id,
                        $trat['id'],
                        $diente,
                        $trat['nombre'] . " (Pieza #{$diente})",
                        1,
                        $precio,
                        null,
                        $item_subtotal
                    );
                    $subtotal += $item_subtotal;
                }
            }
            
            // 2. Procesar otras patologías (e.g. extracción indicada, endodoncia)
            foreach (array_keys($otras_patologias) as $estado) {
                $trat = null;
                
                if ($estado === 'extraccion_indicada') {
                    $diente_int = intval($diente);
                    $es_incisivo_canino = in_array($diente_int, [11, 12, 13, 21, 22, 23, 31, 32, 33, 41, 42, 43, 51, 52, 53, 61, 62, 63, 71, 72, 73, 81, 82, 83]);
                    $es_premolar = in_array($diente_int, [14, 15, 24, 25, 34, 35, 44, 45]);
                    $es_molar = in_array($diente_int, [16, 17, 26, 27, 36, 37, 46, 47, 54, 55, 64, 65, 74, 75, 84, 85]);
                    $es_tercera = in_array($diente_int, [18, 28, 38, 48]);
                    
                    if ($es_incisivo_canino) {
                        $trat = $this->buscarTratamientoPorNombre('Extraccion Incisivo');
                    } elseif ($es_premolar) {
                        $trat = $this->buscarTratamientoPorNombre('Extraccion Premolar');
                    } elseif ($es_molar) {
                        $trat = $this->buscarTratamientoPorNombre('Extraccion Molar');
                    } elseif ($es_tercera) {
                        $trat = $this->buscarTratamientoPorNombre('Extraccion Tercera');
                    }
                    
                    if (!$trat) {
                        $trat = $this->buscarTratamientoPorNombre('Extraccion Simple');
                    }
                } elseif ($estado === 'endodoncia') {
                    $diente_int = intval($diente);
                    $es_anterior = in_array($diente_int, [11, 12, 13, 21, 22, 23, 31, 32, 33, 41, 42, 43, 51, 52, 53, 61, 62, 63, 71, 72, 73, 81, 82, 83]);
                    $es_premolar = in_array($diente_int, [14, 15, 24, 25, 34, 35, 44, 45]);
                    
                    if ($es_anterior) {
                        $trat = $this->buscarTratamientoPorNombre('Endodoncia Anterior');
                        if (!$trat) $trat = $this->buscarTratamientoPorNombre('Endodoncia Incisivo');
                    } elseif ($es_premolar) {
                        $trat = $this->buscarTratamientoPorNombre('Endodoncia Premolar');
                    } else {
                        $trat = $this->buscarTratamientoPorNombre('Endodoncia Molar');
                    }
                } else {
                    $sugeridos = $this->catalogoModel->getByEstadoOdontograma($estado);
                    if (!empty($sugeridos)) {
                        $trat = $sugeridos[0];
                    }
                }
                
                if ($trat) {
                    $precio = $trat['precio_base'];
                    $item_subtotal = $precio * 1;
                    $this->presupuestoModel->addItem(
                        $presupuesto_id,
                        $trat['id'],
                        $diente,
                        $trat['nombre'] . " (Pieza #{$diente})",
                        1,
                        $precio,
                        null,
                        $item_subtotal
                    );
                    $subtotal += $item_subtotal;
                }
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

        $res = $this->presupuestoModel->updateTotales($presupuesto_id, $subtotal, $descuento_porcentaje, $descuento_monto, $total);
        $this->actualizarSaldos($presupuesto_id);
        return $res;
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
            
            // Auto-aprobar presupuesto si se realiza un pago
            if ($presupuesto['estado'] !== 'Aprobado') {
                $this->presupuestoModel->updateEstado($presupuesto_id, 'Aprobado');
            }
            
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

    public function eliminarPago($pago_id, $presupuesto_id) {
        $res = $this->pagoModel->delete($pago_id);
        if ($res) {
            $this->actualizarSaldos($presupuesto_id);
        }
        return $res;
    }
}
?>
