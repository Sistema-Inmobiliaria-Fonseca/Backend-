-- Catálogo geográfico: países.
-- Datos precargados y de solo lectura para la API (no hay ABM de países).
-- Es idempotente: se puede ejecutar varias veces.
INSERT IGNORE INTO paises (nombre, codigo_iso) VALUES
    ('Argentina', 'ARG'),
    ('Bolivia', 'BOL'),
    ('Brasil', 'BRA'),
    ('Chile', 'CHL'),
    ('Colombia', 'COL'),
    ('Paraguay', 'PRY'),
    ('Uruguay', 'URY');
