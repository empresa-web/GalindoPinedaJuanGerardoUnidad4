CREATE DATABASE IF NOT EXISTS donaciones_u4 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE donaciones_u4;

CREATE TABLE IF NOT EXISTS campanas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    categoria VARCHAR(100) NOT NULL,
    descripcion TEXT NOT NULL,
    meta DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS aportaciones (
    id VARCHAR(32) PRIMARY KEY,
    campana_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(200) NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_aportaciones_campana FOREIGN KEY (campana_id) REFERENCES campanas(id),
    CONSTRAINT chk_monto_positivo CHECK (monto >= 10 AND monto <= 100000),
    INDEX ix_aportaciones_fecha (fecha),
    INDEX ix_aportaciones_campana (campana_id)
) ENGINE=InnoDB;

INSERT INTO campanas (id, titulo, categoria, descripcion, meta) VALUES
(1, 'Mochilas para aprender', 'Educación', 'Material y mochilas para estudiantes que inician un nuevo ciclo escolar.', 15000.00),
(2, 'Alimentos para familias', 'Comunidad', 'Apoyos alimentarios para familias en situación de necesidad.', 12000.00),
(3, 'Huerto comunitario', 'Medio ambiente', 'Herramientas y semillas para un huerto educativo sustentable.', 18000.00)
ON DUPLICATE KEY UPDATE id = id;
