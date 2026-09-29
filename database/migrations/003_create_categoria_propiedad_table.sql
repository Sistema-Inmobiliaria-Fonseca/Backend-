-- Tabla intermedia: una propiedad tiene muchas categorías y una categoría muchas propiedades.
CREATE TABLE IF NOT EXISTS categoria_propiedad (
    categoria_id INT UNSIGNED NOT NULL,
    propiedad_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (categoria_id, propiedad_id),
    KEY idx_categoria_propiedad_propiedad (propiedad_id),
    CONSTRAINT fk_categoria_propiedad_categoria
        FOREIGN KEY (categoria_id) REFERENCES categorias (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_categoria_propiedad_propiedad
        FOREIGN KEY (propiedad_id) REFERENCES propiedades (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
