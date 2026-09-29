-- Propiedades del sistema inmobiliario.
CREATE TABLE IF NOT EXISTS propiedades (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(200) NOT NULL,
    metros_cuadrados DECIMAL(10,2) NULL,
    valor DECIMAL(14,2) NULL,
    cantidad_habitaciones TINYINT UNSIGNED NOT NULL DEFAULT 0,
    cantidad_ambientes TINYINT UNSIGNED NOT NULL DEFAULT 0,
    descripcion TEXT NULL,
    apto_credito TINYINT(1) NOT NULL DEFAULT 0,
    estado VARCHAR(20) NOT NULL DEFAULT 'disponible',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_propiedades_estado (estado),
    CONSTRAINT chk_propiedades_estado CHECK (estado IN ('disponible', 'alquilada'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
