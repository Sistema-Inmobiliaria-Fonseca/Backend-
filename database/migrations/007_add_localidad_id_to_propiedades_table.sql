-- La propiedad se ubica en una localidad del catálogo (País -> Provincia -> Localidad).
-- Columna opcional: si se borra una localidad, la propiedad queda sin ubicación.
ALTER TABLE propiedades
    ADD COLUMN localidad_id INT UNSIGNED NULL AFTER nombre,
    ADD KEY idx_propiedades_localidad (localidad_id),
    ADD CONSTRAINT fk_propiedades_localidad
        FOREIGN KEY (localidad_id) REFERENCES localidades (id)
        ON DELETE SET NULL ON UPDATE CASCADE;
