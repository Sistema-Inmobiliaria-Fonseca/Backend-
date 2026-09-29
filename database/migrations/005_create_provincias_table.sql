-- Catálogo geográfico: provincias (división de primer nivel de cada país).
CREATE TABLE IF NOT EXISTS provincias (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    pais_id INT UNSIGNED NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    codigo VARCHAR(16) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_provincias_codigo (codigo),
    UNIQUE KEY uq_provincias_pais_nombre (pais_id, nombre),
    KEY idx_provincias_pais (pais_id),
    CONSTRAINT fk_provincias_pais
        FOREIGN KEY (pais_id) REFERENCES paises (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
