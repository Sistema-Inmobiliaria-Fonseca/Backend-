-- Usuario administrador inicial del panel.
--
-- Credenciales de acceso:
--   email    : admin@inmobiliaria.com
--   password : admin123
--
-- El password es el hash bcrypt de la clave de arriba (cost 12). Es idempotente:
-- se puede ejecutar varias veces sin duplicar el usuario. Cambiá la clave desde
-- la aplicacion antes de publicar el panel.
INSERT INTO usuarios (nombre, email, password, rol, activo) VALUES
    ('Administrador', 'admin@inmobiliaria.com', '$2y$12$yk1Trbunek7e.rC4oxCPx.8T/Z4pgqU8D4WaQkmBQMjMRfXNpAbqS', 'admin', 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), rol = VALUES(rol);