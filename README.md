# Backend - API REST Inmobiliaria

API REST en PHP 8.3 + MySQL (PDO), sin framework. Incluye la base del proyecto, las
migraciones y el ABM de **categorías** y **propiedades** con relación N:M.

## Requisitos

- PHP 8.3 con `pdo_mysql`
- MySQL 8 en ejecución

## Puesta en marcha

1. Configuración de entorno (si no existe `.env`):

   ```
   copy .env.example .env
   ```

2. Crear la base y aplicar migraciones + datos iniciales:

   ```
   php bin\console migrate
   php bin\console seed
   ```

   `bin\console` crea la base si no existe, así que en una instalación nueva alcanza
   con `php bin\console migrate` (no hace falta el paso manual con `database/setup.sql`).

3. Levantar el servidor de desarrollo desde la raíz del proyecto (`Backend-`):

   ```
   C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe -S localhost:8000 -t web web\router.php
   ```

   `web/router.php` es el único punto de entrada (front controller) de la API y también
   sirve de router para el servidor embebido de PHP. En el servidor embebido bloquea el
   acceso a `src/`, `config/`, `bootstrap/`, `database/`, `storage/`, `tests/`, `bin/`,
   `vendor/` y a todo archivo que empiece con `.` (por ejemplo `.env`).

   Con Laragon: `Document Root` = carpeta `web` del proyecto.

4. Verificar:

   ```
   curl http://localhost:8000/api/health/database
   curl http://localhost:8000/api/categorias
   curl http://localhost:8000/api/propiedades
   ```

## Consola

```
php bin\console migrate          Aplica las migraciones pendientes
php bin\console migrate:status   Muestra cuáles ya se aplicaron
php bin\console seed             Ejecuta los datos iniciales (idempotente)
php bin\console migrate:fresh    Borra tablas, aplica migraciones y seeds
php bin\console db:create        Crea solo la base de datos
```

## Endpoints

| Método | Ruta                       | Descripción                             |
|--------|----------------------------|-----------------------------------------|
| GET    | `/api`                     | Información del servicio y endpoints    |
| GET    | `/api/health`              | Estado de la API                        |
| GET    | `/api/health/database`     | Estado de la conexión a MySQL           |
| GET    | `/api/categorias`          | Listar categorías                       |
| GET    | `/api/categorias/{id}`     | Ver una categoría con sus propiedades   |
| POST   | `/api/categorias`          | Crear categoría                         |
| PUT    | `/api/categorias/{id}`     | Actualizar categoría                    |
| DELETE | `/api/categorias/{id}`     | Eliminar categoría                      |
| GET    | `/api/propiedades`         | Listar propiedades con sus categorías   |
| GET    | `/api/propiedades/{id}`    | Ver una propiedad con sus categorías    |
| POST   | `/api/propiedades`         | Crear propiedad                         |
| PUT    | `/api/propiedades/{id}`    | Actualizar propiedad                    |
| DELETE | `/api/propiedades/{id}`    | Eliminar propiedad                      |

### Categorías

```json
POST /api/categorias
{ "nombre": "Casas", "descripcion": "Viviendas unifamiliares", "activo": true }
```

`nombre` es obligatorio y no puede repetirse (la comparación ignora mayúsculas,
minúsculas y acentos). `descripcion` y `activo` son opcionales.

En el listado cada categoría trae `propiedades_count`; en el detalle trae
`propiedades_ids`.

### Propiedades

```json
POST /api/propiedades
{
  "nombre": "Casa en el campo",
  "metros_cuadrados": 180.5,
  "valor": 185000.5,
  "cantidad_habitaciones": 3,
  "cantidad_ambientes": 4,
  "descripcion": "Casa de campo con pileta",
  "apto_credito": true,
  "estado": "disponible",
  "categorias": [2, 7]
}
```

- `nombre` es obligatorio; el resto de los campos es opcional.
- `estado` admite `disponible` (por defecto) y `alquilada`.
- `categorias` es una lista de ids de categorías existentes. Al crear o al enviar el
  campo en un `PUT`, la lista **reemplaza** las asociaciones; si no se envía, se
  conservan.
- En un `PUT` el campo `nombre` sigue siendo obligatorio y los campos omitidos
  mantienen su valor actual.

## Base de datos

```
categorias
  id, nombre (único), descripcion, activo, created_at, updated_at

propiedades
  id, nombre, metros_cuadrados, valor, cantidad_habitaciones, cantidad_ambientes,
  descripcion, apto_credito, estado ('disponible' | 'alquilada'), created_at, updated_at

categoria_propiedad            (N:M)
  categoria_id, propiedad_id, created_at
  PRIMARY KEY (categoria_id, propiedad_id)
  FOREIGN KEY ... ON DELETE CASCADE
```

Al borrar una propiedad o una categoría se eliminan sus filas de `categoria_propiedad`
en cascada; la otra entidad del lado no se toca.

Categorías iniciales (seed): Lotes, Casas, Departamentos, Locales, Oficinas, Campos y
Dúplex.

## Estructura

```
Backend-/
├── bin/console            Consola: migraciones, seeds y base de datos
├── bootstrap/             Arranque de la app y autocargador
├── config/                Configuración por dominio (app.php, database.php)
├── database/
│   ├── migrations/        Un archivo SQL por versión
│   ├── seeds/             Datos iniciales
│   └── setup.sql          Creación de la base (alternativa a la consola)
├── routes/api.php         Definición de endpoints
├── src/
│   ├── Controllers/       HealthController, CategoriaController, PropiedadController
│   ├── Core/              App, Router, Route, Request, Response, Database, Container,
│   │                      Config, Env, Validator, Middleware, Exceptions/
│   ├── Middleware/        CORS y manejo de errores
│   ├── Repositories/      Acceso a datos (categorías, propiedades, tabla intermedia)
│   └── Services/          Reglas de negocio y validación
├── storage/logs/          Logs de la aplicación
├── tests/                 (próxima etapa)
├── web/                   Document root: router.php y .htaccess
├── .env                   Variables de entorno (no se versiona)
└── composer.json
```

## Formato de respuestas

Éxito:

```json
{ "success": true, "data": {} }
```

Error:

```json
{
  "success": false,
  "error": {
    "code": 422,
    "message": "Los datos enviados no son válidos.",
    "details": { "nombre": "El campo nombre es obligatorio." }
  }
}
```

Códigos usados: `201` creado, `204` eliminado, `400` JSON inválido, `404` no existe,
`405` método no permitido, `422` validación.

## Agregar un endpoint

1. Crear el controller en `src/Controllers/`.
2. Inyectar dependencias por constructor (el contenedor las resuelve solas, por
   ejemplo `Database` o `Config`).
3. Registrar la ruta en `routes/api.php`:

   ```php
   $router->get('/api/propiedades/{id}', [PropiedadController::class, 'show']);
   ```

Los parámetros de ruta se pasan al método después del `Request`:

```php
public function show(Request $request, int $id): Response
```

Flujo recomendado: `Controller` (HTTP) → `Service` (reglas de negocio y validación) →
`Repository` (SQL). Para validaciones usar `App\Core\Validator` y lanzar
`ValidationException`; el middleware la traduce a `422` en JSON.

## Acceso a la base de datos

`Database` (PDO, prepared statements, `utf8mb4`, errores como excepción) está
registrado como singleton y se inyecta por constructor:

```php
$filas = $this->db->select('SELECT * FROM propiedades WHERE id = :id', ['id' => $id]);
$propiedad = $this->db->selectOne('SELECT * FROM propiedades WHERE id = :id', ['id' => $id]);
$total = $this->db->scalar('SELECT COUNT(*) FROM propiedades');
$this->db->statement('UPDATE propiedades SET estado = :estado WHERE id = :id', [...]);
$id = $this->db->insert('INSERT INTO propiedades (nombre) VALUES (:nombre)', [...]);

$this->db->transaction(function (): int {
    // INSERT / UPDATE / DELETE
});
```

## Próximas etapas

- Filtros, búsqueda y paginación de propiedades
- Autenticación y autorización
- Imágenes de las propiedades
- Ubicación (mapa) y amenities
