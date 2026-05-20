-- =============================================
-- CRM Operativo QUANTUN Digital
-- Esquema de Base de Datos
-- =============================================

CREATE DATABASE IF NOT EXISTS crm_quantun
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE crm_quantun;

-- 0. Tabla de Usuarios (Autenticación)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'agente') DEFAULT 'agente',
    avatar_url VARCHAR(255) DEFAULT NULL,
    activo TINYINT(1) DEFAULT 1,
    ultimo_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 1. Tabla de Leads o Prospectos
CREATE TABLE IF NOT EXISTS leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    whatsapp VARCHAR(20) NOT NULL,
    email VARCHAR(150) DEFAULT NULL,
    servicio_interes VARCHAR(50) NOT NULL,
    presupuesto DECIMAL(10,2) DEFAULT 0.00,
    url_actual VARCHAR(255) DEFAULT NULL,
    fuente ENUM('manual', 'wordpress', 'landing', 'referido', 'otro') DEFAULT 'manual',
    estado ENUM('nuevo', 'contactado', 'en_negociacion', 'ganado', 'perdido') DEFAULT 'nuevo',
    notas TEXT DEFAULT NULL,
    asignado_a INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (asignado_a) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 2. Tabla de Servicios (Catálogo)
CREATE TABLE IF NOT EXISTS servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT DEFAULT NULL,
    precio_base DECIMAL(10,2) DEFAULT 0.00,
    categoria VARCHAR(50) DEFAULT NULL,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. Tabla de Facturas / Comprobantes
CREATE TABLE IF NOT EXISTS facturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_archivo VARCHAR(255) NOT NULL,
    archivo_url VARCHAR(255) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    peso_bytes INT DEFAULT 0,
    subido_por INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subido_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 4. Tabla de Transacciones (Núcleo Financiero)
CREATE TABLE IF NOT EXISTS transacciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('ingreso', 'egreso') NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    concepto VARCHAR(255) NOT NULL,
    descripcion TEXT DEFAULT NULL,
    fecha_vencimiento DATE DEFAULT NULL,
    estado ENUM('pendiente', 'pagado', 'vencido') DEFAULT 'pendiente',
    lead_id INT DEFAULT NULL,
    servicio_id INT DEFAULT NULL,
    factura_id INT DEFAULT NULL,
    registrado_por INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE SET NULL,
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE SET NULL,
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 5. Tabla de Log de Actividad (Auditoría)
CREATE TABLE IF NOT EXISTS actividad_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT DEFAULT NULL,
    accion VARCHAR(100) NOT NULL,
    entidad VARCHAR(50) NOT NULL,
    entidad_id INT DEFAULT NULL,
    detalles TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Índices para optimización
CREATE INDEX idx_leads_estado ON leads(estado);
CREATE INDEX idx_leads_created ON leads(created_at);
CREATE INDEX idx_transacciones_tipo ON transacciones(tipo);
CREATE INDEX idx_transacciones_estado ON transacciones(estado);
CREATE INDEX idx_transacciones_fecha ON transacciones(fecha_vencimiento);
CREATE INDEX idx_actividad_usuario ON actividad_log(usuario_id);
CREATE INDEX idx_actividad_entidad ON actividad_log(entidad, entidad_id);
