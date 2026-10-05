-- Monedas iniciales.
INSERT IGNORE INTO monedas (codigo, nombre, simbolo, pais, activo) VALUES
    ('ARS', 'Peso argentino', '$', 'Argentina', 1),
    ('USD', 'Dolar estadounidense', '$', 'Estados Unidos', 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), simbolo = VALUES(simbolo), pais = VALUES(pais), activo = VALUES(activo);