# Backend - API REST Inmobiliaria

API REST en PHP 8.3 + MySQL (PDO), sin framework. Incluye la base del proyecto, las
migraciones, autenticación por token, el ABM de **categorías** y **propiedades** con
relación N:M y el catálogo geográfico precargado **País → Provincia → Localidad**
(solo consulta).

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
   C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe -S localhost:8000 -t web bootstrap\front.php
   ```

   `bootstrap/front.php` es el front controller (único punto de entrada) y vive fuera
   de la carpeta web. Con el servidor embebido se indica como router; con Apache/Laragon
   el `Document Root` es la carpeta `web` y `web/dispatch.php` delega en él. En ambos
   casos se bloquea el acceso a `src/`, `config/`, `bootstrap/`, `database/`,
   `storage/`, `tests/`, `bin/`, `vendor/` y a todo archivo que empiece con `.`
   (por ejemplo `.env`).

   Con Laragon: `Document Root` = carpeta `web` del proyecto.

4. Verificar:

   ```
   curl http://localhost:8000/api/health/database
   ```

   Los endpoints de datos requieren token. Conseguilo con el login y reenvialo:

   ```
   curl -X POST http://localhost:8000/api/auth/login -H "Content-Type: application/json" \
     -d "{\"email\":\"admin@inmobiliaria.com\",\"password\":\"admin123\"}"

   curl http://localhost:8000/api/categorias -H "Authorization: Bearer <token>"
   curl http://localhost:8000/api/propiedades -H "Authorization: Bearer <token>"
   curl http://localhost:8000/api/paises -H "Authorization: Bearer <token>"
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

Todos los endpoints salvo los de **salud** requieren autenticación. El único público
además de `/api/health*` es `POST /api/auth/login`.

| Método | Ruta                       | Descripción                             |
|--------|----------------------------|-----------------------------------------|
| GET    | `/api`                     | Información del servicio y endpoints    |
| GET    | `/api/health`              | Estado de la API                        |
| GET    | `/api/health/database`     | Estado de la conexión a MySQL           |
| POST   | `/api/auth/login`          | Iniciar sesión y obtener un token       |
| GET    | `/api/auth/me`             | Usuario del token enviado               |
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
| GET    | `/api/paises`              | Listar países (catálogo)                |
| GET    | `/api/paises/{id}`         | Ver un país con sus conteos             |
| GET    | `/api/provincias`          | Listar provincias (`?pais_id=`)         |
| GET    | `/api/provincias/{id}`     | Ver una provincia con sus localidades   |
| GET    | `/api/localidades`         | Listar localidades (`?provincia_id=`)   |
| GET    | `/api/localidades/{id}`    | Ver una localidad con país y provincia  |

Países, provincias y localidades **no tienen ABM**: no existen `POST`, `PUT` ni `DELETE`
para ellos (responden `405`). Los datos se cargan con los archivos de `database/seeds/`.

### Autenticación

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@inmobiliaria.com","password":"admin123"}'
```

```json
{ "success": true, "data": { "token": "eyJ1aWQiOjF9...", "expires_in": 43200,
  "user": { "id": 1, "nombre": "Administrador", "email": "admin@inmobiliaria.com", "rol": "admin" } } }
```

El token es un JWT-like firmado con HMAC-SHA256 y se manda en cada request:

```
Authorization: Bearer <token>
```

Si falta, venció o la firma no coincide, la API responde `401`.

- El secreto de firma sale de `AUTH_TOKEN_SECRET` en el `.env`. Sin ese valor el login
  falla con un error `500`: generá uno con
  `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`.
- `AUTH_TOKEN_TTL` define la vigencia en segundos (por defecto `43200`, 12 horas).
- Los tokens no se guardan en la base: son sin estado, así que el logout se resuelve
  descartando el token en el cliente.
- El usuario inicial lo crea `database/seeds/020_usuarios.sql`
  (`admin@inmobiliaria.com` / `admin123`). Cambiá esa clave antes de publicar.

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
  "categorias": [2, 7],
  "localidad_id": 2
}
```

- `nombre` es obligatorio; el resto de los campos es opcional.
- `estado` admite `disponible` (por defecto) y `alquilada`.
- `categorias` es una lista de ids de categorías existentes. Al crear o al enviar el
  campo en un `PUT`, la lista **reemplaza** las asociaciones; si no se envía, se
  conservan.
- `localidad_id` es el id de una localidad del catálogo. Si se envía `null` se quita la
  ubicación; si no se envía el campo, se conserva la actual.
- En un `PUT` el campo `nombre` sigue siendo obligatorio y los campos omitidos
  mantienen su valor actual.
- Un `localidad_id` inexistente o no numérico devuelve `422`.

La respuesta incluye la ubicación ya resuelta, para no tener que cruzar los tres
catálogos en el frontend:

```json
{
  "id": 1,
  "nombre": "Casa frente al mar",
  "localidad_id": 2,
  "ubicacion": {
    "localidad": { "id": 2, "nombre": "Mar del Plata" },
    "provincia": { "id": 1, "nombre": "Buenos Aires" },
    "pais": { "id": 1, "nombre": "Argentina", "codigo_iso": "ARG" }
  },
  "categorias": [{ "id": 2, "nombre": "Casas" }]
}
```

`ubicacion` es `null` cuando la propiedad no tiene localidad.

## Catálogo geográfico (País → Provincia → Localidad)

Es un catálogo **precargado y de solo lectura**. El administrador no crea, edita ni
borra países, provincias ni localidades: los selecciona al cargar o modificar una
propiedad. Para ampliarlo hay que agregar filas a los archivos de `database/seeds/`.

```
paises
  id, nombre (único), codigo_iso (único), activo, created_at, updated_at

provincias
  id, pais_id, nombre, codigo (único), activo, created_at, updated_at
  UNIQUE (pais_id, nombre)
  FOREIGN KEY pais_id -> paises ON DELETE CASCADE

localidades
  id, provincia_id, nombre, activo, created_at, updated_at
  UNIQUE (provincia_id, nombre)
  FOREIGN KEY provincia_id -> provincias ON DELETE CASCADE

propiedades.localidad_id
  NULL, FOREIGN KEY -> localidades ON DELETE SET NULL
```

La propiedad se vincula a la **localidad**; de ahí sale la provincia y el país. Si se
borra una localidad, las propiedades quedan sin ubicación en lugar de borrarse.

### Selectores dependientes

```bash
# 1. El administrador elige el país
curl http://localhost:8000/api/paises

# 2. Solo las provincias de ese país
curl "http://localhost:8000/api/provincias?pais_id=1"

# 3. Solo las localidades de esa provincia
curl "http://localhost:8000/api/localidades?provincia_id=1"

# 4. El detalle de una provincia ya trae sus localidades
curl http://localhost:8000/api/provincias/1

# 5. El detalle de una localidad ya trae provincia y país
curl http://localhost:8000/api/localidades/2
```

- `GET /api/paises` y `GET /api/paises/{id}` incluyen `provincias_count` y
  `localidades_count`.
- `GET /api/provincias` acepta `?pais_id=`; sin filtro devuelve las 145 provincias.
- `GET /api/localidades` acepta `?provincia_id=`; sin filtro devuelve las 476
  localidades.
- `GET /api/localidades/{id}` incluye `propiedades_ids` (las propiedades que usan esa
  localidad).
- Un `pais_id` o `provincia_id` inválido devuelve `422`; un id inexistente devuelve `404`.

### Datos precargados

| Archivo | Contenido |
|---------|-----------|
| `database/seeds/010_paises.sql` | 7 países de Sudamérica con su código ISO |
| `database/seeds/011_provincias.sql` | 145 provincias / estados / departamentos |
| `database/seeds/012_localidades.sql` | 476 localidades |

| País | ISO | Provincias | Localidades |
|------|-----|-----------|-------------|
| Argentina | ARG | 24 | 196 |
| Bolivia | BOL | 9 | 21 |
| Brasil | BRA | 27 | 76 |
| Chile | CHL | 16 | 47 |
| Colombia | COL | 32 | 55 |
| Paraguay | PRY | 18 | 40 |
| Uruguay | URY | 19 | 41 |

Argentina trae las 24 provincias y las principales localidades de cada una. Es una
base: si más adelante se necesita el listado completo de localidades de alguna
provincia, alcanza con agregar las filas al final del `INSERT` de
`database/seeds/012_localidades.sql` y volver a correr `php bin\console seed`.

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
├── bootstrap/             Arranque de la app, autocargador y front controller (front.php)
├── config/                Configuración por dominio (app.php, database.php)
├── database/
│   ├── migrations/        Un archivo SQL por versión
│   ├── seeds/             Datos iniciales (categorías y catálogo geográfico)
│   └── setup.sql          Creación de la base (alternativa a la consola)
├── routes/api.php         Definición de endpoints
├── src/
│   ├── Controllers/       Health, Categoria, Propiedad y Geografia
│   ├── Core/              App, Router, Route, Request, Response, Database, Container,
│   │                      Config, Env, Validator, Middleware, Exceptions/
│   ├── Middleware/        CORS y manejo de errores
│   ├── Repositories/      Acceso a datos (categorías, propiedades, tabla intermedia,
│   │                      país, provincia y localidad)
│   └── Services/          Reglas de negocio y validación
├── storage/logs/          Logs de la aplicación
├── tests/                 (próxima etapa)
├── web/                   Document root: dispatch.php y .htaccess
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
- Roles y permisos (hoy todos los usuarios autenticados son administradores)
- Imágenes de las propiedades
- Ubicación (mapa) y amenities
