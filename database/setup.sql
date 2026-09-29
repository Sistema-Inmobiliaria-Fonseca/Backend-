-- Base de datos para el backend de la inmobiliaria (MySQL 8).
-- Ejecutar una sola vez: mysql -u root < database/setup.sql

CREATE DATABASE IF NOT EXISTS `inmobiliaria`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Las tablas se crean en la etapa siguiente, una por migration en database/migrations/.
