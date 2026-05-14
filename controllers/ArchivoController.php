<?php
require_once __DIR__ . '/../models/Archivo.php';

class ArchivoController {
    private $archivoModel;

    public function __construct() {
        global $conn; // Asumiendo que se incluye config/conexion.php antes
        $this->archivoModel = new Archivo($conn);
    }

    public function getArchivosPaciente($paciente_id) {
        return $this->archivoModel->getByPaciente($paciente_id);
    }

    public function subirArchivo($paciente_id, $data, $file) {
        $tipo = $data['tipo'] ?? 'Documento';
        $descripcion = $data['descripcion'] ?? '';
        $subido_por = $data['subido_por'] ?? null;
        
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['success' => false, 'error' => 'Parámetros de subida inválidos.'];
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return ['success' => false, 'error' => 'No se seleccionó ningún archivo.'];
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['success' => false, 'error' => 'El archivo excede el límite de tamaño.'];
            default:
                return ['success' => false, 'error' => 'Error desconocido al subir.'];
        }

        if ($file['size'] > 10485760) { // 10 MB limit
            return ['success' => false, 'error' => 'El archivo no debe exceder los 10MB.'];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowedMimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/pdf' => 'pdf'
        ];

        if (!array_key_exists($mime, $allowedMimeTypes)) {
            return ['success' => false, 'error' => 'Formato no permitido. Solo JPG, PNG y PDF.'];
        }

        // Crear directorio si no existe
        $uploadDir = __DIR__ . '/../uploads/pacientes/' . $paciente_id . '/';
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                return ['success' => false, 'error' => 'Error al crear directorio de almacenamiento.'];
            }
        }

        $extension = $allowedMimeTypes[$mime];
        $nombre_original = basename($file['name']);
        
        // Generar un nombre único para evitar colisiones
        $nombre_seguro = time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $ruta_destino = $uploadDir . $nombre_seguro;
        
        // Guardar la ruta relativa para la DB
        $ruta_db = 'uploads/pacientes/' . $paciente_id . '/' . $nombre_seguro;

        if (move_uploaded_file($file['tmp_name'], $ruta_destino)) {
            $archivo_id = $this->archivoModel->registrar(
                $paciente_id, $tipo, $nombre_original, $ruta_db, $descripcion, $file['size'], $subido_por
            );
            if ($archivo_id) {
                return ['success' => true, 'archivo_id' => $archivo_id];
            } else {
                unlink($ruta_destino); // Rollback
                return ['success' => false, 'error' => 'Error al guardar registro en base de datos.'];
            }
        }

        return ['success' => false, 'error' => 'Error al mover el archivo subido.'];
    }

    public function eliminarArchivo($archivo_id) {
        $archivo = $this->archivoModel->getById($archivo_id);
        if (!$archivo) return ['success' => false, 'error' => 'Archivo no encontrado.'];

        $ruta_fisica = __DIR__ . '/../' . $archivo['ruta_archivo'];
        
        if ($this->archivoModel->delete($archivo_id)) {
            if (file_exists($ruta_fisica)) {
                unlink($ruta_fisica);
            }
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => 'No se pudo eliminar el registro.'];
    }
}
?>
