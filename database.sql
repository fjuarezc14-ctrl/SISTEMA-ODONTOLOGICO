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
VALUES ('Administrador General', 'admin', '$2y$10$wN2a3aM2e0aQ9G0/H.g0G.Xw1c.O/U7o6h4z6B2n/J/E.z/O.n/P6', 'Admin')
ON DUPLICATE KEY UPDATE nombre=nombre;
