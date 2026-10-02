-- La propiedad puede quedar vendida: el estado pasa a tener tres valores.
-- La definicion original (migration 002) no se modifica; el CHECK se reemplaza.
ALTER TABLE propiedades
    DROP CONSTRAINT chk_propiedades_estado,
    ADD CONSTRAINT chk_propiedades_estado CHECK (estado IN ('disponible', 'alquilada', 'vendida'));