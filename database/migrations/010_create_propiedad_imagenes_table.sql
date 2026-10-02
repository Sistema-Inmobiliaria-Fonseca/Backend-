-- Imagenes de cada propiedad. El orden arranca en 1 y define cual es la principal
-- (la de menor orden). Al borrar la propiedad se borran sus imagenes en cascada.
CREATE TABLE IF NOT EXISTS propiedad_imagenes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    propiedad_id INT UNSIGNED NOT NULL,
    nombre_archivo VARCHAR(190) NOT NULL,
    nombre_original VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    tamano INT UNSIGNED NOT NULL,
    orden SMALLINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_propiedad_imagenes_archivo (nombre_archivo),
    UNIQUE KEY uq_propiedad_imagenes_orden (propiedad_id, orden),
    CONSTRAINT chk_propiedad_imagenes_orden CHECK (orden >= 1),
    CONSTRAINT fk_propiedad_imagenes_propiedad
        FOREIGN KEY (propiedad_id) REFERENCES propiedades (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;