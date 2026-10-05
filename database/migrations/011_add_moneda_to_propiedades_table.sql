-- Agrega tipo de moneda a las propiedades.
ALTER TABLE propiedades
    ADD COLUMN moneda VARCHAR(10) NULL AFTER valor,
    ADD KEY idx_propiedades_moneda (moneda);
