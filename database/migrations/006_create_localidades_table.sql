-- Catálogo geográfico: localidades. Es la unidad que se selecciona en una propiedad.
CREATE TABLE IF NOT EXISTS localidades (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    provincia_id INT UNSIGNED NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_localidades_provincia_nombre (provincia_id, nombre),
    KEY idx_localidades_provincia (provincia_id),
    CONSTRAINT fk_localidades_provincia
        FOREIGN KEY (provincia_id) REFERENCES provincias (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
