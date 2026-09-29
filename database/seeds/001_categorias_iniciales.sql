-- Categorías iniciales. Es idempotente: se puede ejecutar varias veces.
INSERT IGNORE INTO categorias (nombre, descripcion) VALUES
    ('Lotes', 'Terrenos para construir, con servicios o en zona de desarrollo.'),
    ('Casas', 'Viviendas unifamiliares de uno o varios pisos.'),
    ('Departamentos', 'Viviendas en edificio, monoambiente, dos ambientes o más.'),
    ('Locales', 'Predios comerciales para la venta o el alquiler.'),
    ('Oficinas', 'Espacios de trabajo y oficinas comerciales o profesionales.'),
    ('Campos', 'Campos rurales para ganadería o agricultura.'),
    ('Dúplex', 'Unidades de dos plantas: casas o departamentos dúplex.');
