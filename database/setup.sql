-- Base de datos para el backend de la inmobiliaria (MySQL 8 / MariaDB 10.4).
-- Ejecutar una sola vez: mysql -u root < database/setup.sql

CREATE DATABASE IF NOT EXISTS `inmobiliaria`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Las tablas se crean en la etapa siguiente, una por migration en database/migrations/.
-- Entre ellas:
--   002_create_propiedades_table.sql       propiedades (estado: disponible, alquilada, vendida)
--   010_create_propiedad_imagenes_table.sql propiedad_imagenes (orden desde 1, unica por propiedad)
--
-- El estado 'vendida' se agrega en la migration 009, que reemplaza el CHECK original.
--
-- Las imagenes se guardan en storage/uploads/propiedades/ y se sirven por la API en
-- /uploads/propiedades/{nombre_archivo}. Esa carpeta debe existir y ser escribible por PHP:
--   mkdir -p storage/uploads/propiedades
--
-- Seed de datos (usuarios, catalogo, geografia): php bin/console migrate:fresh
-- o, sin borrar las tablas: php bin/console migrate && php bin/console seed