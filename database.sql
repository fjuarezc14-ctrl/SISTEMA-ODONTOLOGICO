-- Creación de la base de datos MahuDent
CREATE DATABASE IF NOT EXISTS mahudent_db;
USE mahudent_db;

-- 1. Tabla de Usuarios (Para Login y Roles)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Guardará el hash de la contraseña
    rol ENUM('Admin', 'Recepcionista', 'Dentista') DEFAULT 'Dentista',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Tabla de Pacientes
CREATE TABLE IF NOT EXISTS pacientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dni VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    email VARCHAR(100),
    alergias TEXT, -- Alertas clínicas
    enfermedades_cronicas TEXT, -- Alertas clínicas
    estado_activo BOOLEAN DEFAULT 1, -- Para Soft-Delete
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Tabla de Citas (Agenda)
CREATE TABLE IF NOT EXISTS citas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    doctor_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    motivo VARCHAR(255),
    estado ENUM('Pendiente', 'Confirmada', 'Completada', 'Cancelada') DEFAULT 'Pendiente',
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES usuarios(id)
);

-- 4. Tablas del Módulo 3D (Odontograma Interactivo)
-- Cada paciente puede tener un estado dental actual. 
-- El diente se identifica por su nomenclatura universal (ej. 11, 12, 13, 21, etc.)
CREATE TABLE IF NOT EXISTS odontograma_estado (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    diente_numero INT NOT NULL, -- Ejemplo: 18, 17, ..., 48
    cara_afectada ENUM('Vestibular', 'Lingual/Palatina', 'Mesial', 'Distal', 'Oclusal', 'Corona Completa') NOT NULL,
    estado ENUM('Sano', 'Caries', 'Curación', 'Extracción', 'Implante', 'Endodoncia') DEFAULT 'Sano',
    notas TEXT,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE
);

-- Inserción de Usuario Administrador por Defecto
-- Usuario: admin | Contraseña: password123
INSERT INTO usuarios (nombre, usuario, password, rol) 
VALUES ('Administrador General', 'admin', '$2y$10$V6dsIjYumwaE.LKlMWwprOsNalEYq/RBGBZdMRO0v/U.DxQRQbr/i', 'Admin')
ON DUPLICATE KEY UPDATE nombre=nombre;

-- 5. Tabla de Historial Evolutivo (Notas Clínicas)
CREATE TABLE IF NOT EXISTS historial_evolutivo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    cita_id INT NULL,
    descripcion TEXT NOT NULL,
    doctor_id INT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,
    FOREIGN KEY (cita_id) REFERENCES citas(id) ON DELETE SET NULL,
    FOREIGN KEY (doctor_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- 6. Catálogo de Tratamientos (Precios Base)
CREATE TABLE IF NOT EXISTS catalogo_tratamientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT NULL,
    precio_base DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    categoria VARCHAR(80) NULL, -- Ej: 'Restauración', 'Endodoncia', 'Cirugía', 'Ortodoncia'
    estado_odontograma VARCHAR(50) NULL, -- Mapeo al estado del odontograma: 'caries', 'resina', 'corona', 'ausente'
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 7. Presupuestos
CREATE TABLE IF NOT EXISTS presupuestos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    doctor_id INT NULL,
    fecha_emision DATE NOT NULL,
    fecha_vigencia DATE NULL, -- Validez del presupuesto
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    descuento_porcentaje DECIMAL(5,2) DEFAULT 0.00,
    descuento_monto DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estado ENUM('Borrador', 'Enviado', 'Aprobado', 'Rechazado', 'Vencido') DEFAULT 'Borrador',
    notas TEXT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- 8. Ítems del Presupuesto (Detalle)
CREATE TABLE IF NOT EXISTS presupuesto_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    presupuesto_id INT NOT NULL,
    tratamiento_id INT NULL, -- Referencia al catálogo (puede ser NULL si es ítem manual)
    diente_numero INT NULL, -- Diente asociado
    descripcion VARCHAR(255) NOT NULL, -- Nombre del tratamiento o descripción manual
    cantidad INT NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    precio_ajustado DECIMAL(10,2) NULL, -- Precio final editado por el doctor (NULL = usar unitario)
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (presupuesto_id) REFERENCES presupuestos(id) ON DELETE CASCADE,
    FOREIGN KEY (tratamiento_id) REFERENCES catalogo_tratamientos(id) ON DELETE SET NULL
);

-- Datos Semilla: Catálogo de Tratamientos con precios base
INSERT INTO catalogo_tratamientos (nombre, descripcion, precio_base, categoria, estado_odontograma) VALUES
('Resina Simple', 'Restauración directa con resina compuesta (1 superficie)', 80.00, 'Restauración', 'resina'),
('Resina Compuesta', 'Restauración directa con resina compuesta (2+ superficies)', 120.00, 'Restauración', 'resina'),
('Corona Dental', 'Corona completa de porcelana o metal-porcelana', 350.00, 'Prótesis Fija', 'corona'),
('Endodoncia Anterior', 'Tratamiento de conducto en diente anterior', 200.00, 'Endodoncia', NULL),
('Endodoncia Premolar', 'Tratamiento de conducto en premolar', 250.00, 'Endodoncia', NULL),
('Endodoncia Molar', 'Tratamiento de conducto en molar', 350.00, 'Endodoncia', NULL),
('Extracción Simple', 'Extracción de pieza dental erupcionada', 60.00, 'Cirugía', 'ausente'),
('Extracción Quirúrgica', 'Extracción de pieza retenida o impactada', 150.00, 'Cirugía', 'ausente'),
('Limpieza Dental', 'Profilaxis dental profesional', 50.00, 'Prevención', NULL),
('Blanqueamiento', 'Blanqueamiento dental profesional en consultorio', 200.00, 'Estética', NULL),
('Implante Dental', 'Implante de titanio unitario (sin corona)', 800.00, 'Implantología', NULL),
('Ortodoncia - Brackets', 'Tratamiento completo de ortodoncia con brackets metálicos', 2500.00, 'Ortodoncia', NULL),
('Incrustación Dental', 'Incrustación tipo Inlay/Onlay en cerámica', 250.00, 'Restauración', 'corona'),
('Radiografía Periapical', 'Radiografía individual de una pieza dental', 15.00, 'Diagnóstico', NULL),
('Radiografía Panorámica', 'Radiografía panorámica completa', 40.00, 'Diagnóstico', NULL)
ON DUPLICATE KEY UPDATE nombre=nombre;
