<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\Exceptions\NotFoundHttpException;
use App\Core\Exceptions\ValidationException;
use App\Repositories\PropiedadImagenRepository;
use App\Repositories\PropiedadRepository;
use finfo;
use RuntimeException;

final class PropiedadImagenService
{
    /** Errores de subida de PHP traducidos al mensaje que ve el cliente. */
    private const ERRORES_UPLOAD = [
        UPLOAD_ERR_INI_SIZE => 'El archivo supera el tamaño máximo permitido por el servidor (upload_max_filesize).',
        UPLOAD_ERR_FORM_SIZE => 'El archivo supera el tamaño máximo permitido por el formulario.',
        UPLOAD_ERR_PARTIAL => 'El archivo se subió incompleto. Volvé a intentarlo.',
        UPLOAD_ERR_NO_FILE => 'No se recibió ningún archivo en el campo imagen.',
        UPLOAD_ERR_NO_TMP_DIR => 'El servidor no tiene una carpeta temporal para recibir el archivo.',
        UPLOAD_ERR_CANT_WRITE => 'El servidor no pudo escribir el archivo en disco.',
        UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo.',
    ];

    /** Nombres que genera la API: propertyId + hash aleatorio + extension del MIME real. */
    private const PATRON_ARCHIVO = '/^prop-\d+-[0-9a-f]{32}\.(jpg|png|webp)$/';

    public function __construct(
        private readonly Database $db,
        private readonly Config $config,
        private readonly PropiedadImagenRepository $imagenes,
        private readonly PropiedadRepository $propiedades
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listar(int $propiedadId): array
    {
        $this->verificarPropiedad($propiedadId);

        return $this->formatearTodas($this->imagenes->dePropiedad($propiedadId));
    }

    /**
     * Sube una imagen y la agrega al final del orden de la propiedad.
     * Devuelve la imagen creada y la lista actualizada.
     *
     * @param  array<string, mixed>  $file  entrada normalizada de Request::file()
     * @return array{imagen: array<string, mixed>, imagenes: array<int, array<string, mixed>>}
     */
    public function agregar(int $propiedadId, array $file): array
    {
        $this->verificarPropiedad($propiedadId);

        $limite = $this->maximoImagenes();
        $cantidad = $this->imagenes->contar($propiedadId);

        if ($cantidad >= $limite) {
            throw new ValidationException('No se pudo subir la imagen.', [
                'imagen' => "La propiedad ya tiene el máximo de {$limite} imágenes.",
            ]);
        }

        $this->validarSubida($file);
        $mime = $this->detectarMime($file['tmp_name']);
        $extension = $this->extensionPara($mime);
        $nombreArchivo = $this->generarNombre($propiedadId, $extension);

        $this->guardar($file, $nombreArchivo);

        try {
            $id = $this->imagenes->create([
                'propiedad_id' => $propiedadId,
                'nombre_archivo' => $nombreArchivo,
                'nombre_original' => $this->sanearNombreOriginal($file['name'] ?? ''),
                'mime_type' => $mime,
                'tamano' => $file['size'],
                'orden' => $this->imagenes->siguienteOrden($propiedadId),
            ]);
        } catch (\Throwable $exception) {
            $this->borrarArchivo($nombreArchivo);

            throw $exception;
        }

        $creada = $this->imagenes->find($id);

        return [
            'imagen' => $this->formatear($creada),
            'imagenes' => $this->formatearTodas($this->imagenes->dePropiedad($propiedadId)),
        ];
    }

    /**
     * Reordena las imagenes de una propiedad. La lista debe traer todos los ids,
     * sin repetidos: el primero queda como orden 1 (imagen principal).
     *
     * @param  array<int, mixed>  $ids
     * @return array<int, array<string, mixed>>
     */
    public function ordenar(int $propiedadId, mixed $ids): array
    {
        $this->verificarPropiedad($propiedadId);
        $ids = $this->validarIdsDeOrden($ids);

        $actuales = $this->imagenes->dePropiedad($propiedadId);

        if ($actuales === []) {
            throw new ValidationException('No se pudieron ordenar las imágenes.', [
                'imagenes' => 'La propiedad no tiene imágenes para ordenar.',
            ]);
        }

        if (count($ids) !== count($actuales)) {
            throw new ValidationException('No se pudieron ordenar las imágenes.', [
                'imagenes' => sprintf(
                    'Hay que enviar los %d ids de imagen de la propiedad, en el orden deseado.',
                    count($actuales)
                ),
            ]);
        }

        $idsDeLaPropiedad = array_map(static fn (array $fila): int => (int) $fila['id'], $actuales);
        $faltantes = array_diff($ids, $idsDeLaPropiedad);

        if ($faltantes !== []) {
            throw new ValidationException('No se pudieron ordenar las imágenes.', [
                'imagenes' => 'Estos ids no pertenecen a la propiedad: ' . implode(', ', $faltantes) . '.',
            ]);
        }

        // Paso 1: mover los ordenes actuales a un rango libre. MySQL valida el indice
        // unico (propiedad_id, orden) en cada sentencia, asi que primero se reservan
        // los valores y despues se escribe el orden final dentro de la misma transaccion.
        $this->db->transaction(function () use ($propiedadId, $ids): void {
            $offset = $this->imagenes->maximoOrden($propiedadId) + count($ids) + 1;
            $this->imagenes->reservarOrdenes($propiedadId, $offset);

            foreach ($ids as $posicion => $imagenId) {
                $this->imagenes->actualizarOrdenDePropiedad($propiedadId, $imagenId, $posicion + 1);
            }
        });

        return $this->formatearTodas($this->imagenes->dePropiedad($propiedadId));
    }

    /**
     * Borra la imagen y renumera el resto para que el orden siga siendo 1..n.
     *
     * @return array<int, array<string, mixed>>  las imágenes que quedan
     */
    public function eliminar(int $propiedadId, int $imagenId): array
    {
        $this->verificarPropiedad($propiedadId);

        $imagen = $this->imagenes->findDePropiedad($propiedadId, $imagenId);

        if ($imagen === null) {
            throw new NotFoundHttpException("La imagen {$imagenId} no pertenece a la propiedad {$propiedadId}.");
        }

        // Primero se borra el archivo fisico y despues la fila, como se pidio.
        $this->borrarArchivo((string) $imagen['nombre_archivo']);
        $this->imagenes->delete($imagenId);
        $this->imagenes->compactarOrdenes($propiedadId);

        return $this->formatearTodas($this->imagenes->dePropiedad($propiedadId));
    }

    /**
     * Borra los archivos de una propiedad que se acaba de eliminar en la base.
     */
    public function eliminarArchivosDePropiedad(int $propiedadId): void
    {
        foreach ($this->imagenes->dePropiedad($propiedadId) as $imagen) {
            $this->borrarArchivo((string) $imagen['nombre_archivo']);
        }
    }

    /**
     * Resuelve el archivo a servir en la ruta publica. La carpeta de subidas esta
     * fuera del document root, asi que el unico camino para verla es este.
     *
     * @return array{contenido: string, mime: string, nombre: string}
     */
    public function contenidoPublico(string $nombreArchivo): array
    {
        // Sin separadores ni '..': el nombre tiene que ser solo eso.
        if ($nombreArchivo === '' || basename($nombreArchivo) !== $nombreArchivo) {
            throw new NotFoundHttpException('La imagen solicitada no existe.');
        }

        if (preg_match(self::PATRON_ARCHIVO, $nombreArchivo) !== 1) {
            throw new NotFoundHttpException('La imagen solicitada no existe.');
        }

        $raiz = realpath($this->carpetaDeSubidas());

        if ($raiz === false) {
            throw new NotFoundHttpException('La imagen solicitada no existe.');
        }

        $ruta = realpath($raiz . DIRECTORY_SEPARATOR . $nombreArchivo);

        if (
            $ruta === false
            || !is_file($ruta)
            || !str_starts_with($ruta, $raiz . DIRECTORY_SEPARATOR)
        ) {
            throw new NotFoundHttpException('La imagen solicitada no existe.');
        }

        $contenido = file_get_contents($ruta);

        if ($contenido === false) {
            throw new NotFoundHttpException('La imagen solicitada no existe.');
        }

        $mime = $this->mimeDeExtension(pathinfo($nombreArchivo, PATHINFO_EXTENSION));

        return ['contenido' => $contenido, 'mime' => $mime, 'nombre' => $nombreArchivo];
    }

    /**
     * Formatea las imágenes de varias propiedades, agrupadas por propiedad_id.
     *
     * @param  array<int, array<int, array<string, mixed>>>  $porPropiedad
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function formatearPorPropiedad(array $porPropiedad): array
    {
        $salida = [];

        foreach ($porPropiedad as $propiedadId => $filas) {
            $salida[(int) $propiedadId] = $this->formatearTodas($filas);
        }

        return $salida;
    }

    /**
     * Imagenes de varias propiedades en una sola consulta, agrupadas por
     * propiedad_id. Evita el N+1 en el listado.
     *
     * @param  array<int, int>  $propiedadIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function listarVarias(array $propiedadIds): array
    {
        return $this->formatearPorPropiedad($this->imagenes->dePropiedades($propiedadIds));
    }

    /**
     * @param  array<int, array<string, mixed>>  $filas
     * @return array<int, array<string, mixed>>
     */
    public function formatearTodas(array $filas): array
    {
        return array_map(fn (array $fila): array => $this->formatear($fila), $filas);
    }

    /**
     * @param  array<string, mixed>|null  $fila
     * @return array<string, mixed>|null
     */
    public function formatear(?array $fila): ?array
    {
        if ($fila === null) {
            return null;
        }

        $nombreArchivo = (string) $fila['nombre_archivo'];

        return [
            'id' => (int) $fila['id'],
            'propiedad_id' => (int) $fila['propiedad_id'],
            'nombre' => $nombreArchivo,
            'nombre_original' => (string) $fila['nombre_original'],
            'mime_type' => (string) $fila['mime_type'],
            'tamano' => (int) $fila['tamano'],
            'orden' => (int) $fila['orden'],
            'es_principal' => (int) $fila['orden'] === 1,
            'url' => $this->urlPublica($nombreArchivo),
            'created_at' => (string) $fila['created_at'],
        ];
    }

    public function urlPublica(string $nombreArchivo): string
    {
        return $this->baseUrl() . $this->rutaPublica() . '/' . rawurlencode($nombreArchivo);
    }

    public function maxAgeCache(): int
    {
        return max(0, (int) $this->config->get('app.uploads.max_age', 604800));
    }

    private function verificarPropiedad(int $propiedadId): void
    {
        if ($this->propiedades->find($propiedadId) === null) {
            throw new NotFoundHttpException("La propiedad {$propiedadId} no existe.");
        }
    }

    /**
     * @param  array<string, mixed>  $file
     */
    private function validarSubida(array $file): void
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error !== UPLOAD_ERR_OK) {
            throw new ValidationException(
                'No se pudo subir la imagen.',
                ['imagen' => self::ERRORES_UPLOAD[$error] ?? 'La subida del archivo falló.']
            );
        }

        $tmp = (string) ($file['tmp_name'] ?? '');

        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new ValidationException('No se pudo subir la imagen.', [
                'imagen' => 'No se recibió un archivo válido en el campo imagen.',
            ]);
        }

        $tamano = (int) ($file['size'] ?? 0);

        if ($tamano <= 0) {
            throw new ValidationException('No se pudo subir la imagen.', [
                'imagen' => 'El archivo está vacío.',
            ]);
        }

        $limiteBytes = $this->maximoBytes();

        if ($tamano > $limiteBytes) {
            throw new ValidationException('No se pudo subir la imagen.', [
                'imagen' => sprintf(
                    'El archivo pesa %s y el máximo permitido es %s.',
                    $this->formatearBytes($tamano),
                    $this->formatearBytes($limiteBytes)
                ),
            ]);
        }
    }

    private function detectarMime(string $ruta): string
    {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($ruta);

        if (!is_string($mime) || !isset($this->mimesPermitidos()[$mime])) {
            throw new ValidationException('No se pudo subir la imagen.', [
                'imagen' => 'El archivo no es una imagen JPG, PNG o WebP válida.',
            ]);
        }

        // getimagesize confirma que el contenido sea una imagen y no solo que
        // tenga la firma correcta.
        if (getimagesize($ruta) === false) {
            throw new ValidationException('No se pudo subir la imagen.', [
                'imagen' => 'El archivo está dañado o no es una imagen legible.',
            ]);
        }

        return $mime;
    }

    private function extensionPara(string $mime): string
    {
        return $this->mimesPermitidos()[$mime] ?? 'jpg';
    }

    private function mimeDeExtension(string $extension): string
    {
        foreach ($this->mimesPermitidos() as $mime => $ext) {
            if ($ext === strtolower($extension)) {
                return $mime;
            }
        }

        return 'application/octet-stream';
    }

    private function mimesPermitidos(): array
    {
        $mimes = $this->config->get('app.uploads.allowed_mimes', []);

        return is_array($mimes) && $mimes !== [] ? $mimes : ['image/jpeg' => 'jpg'];
    }

    /**
     * Tope de tamaño: el menor entre lo que permite PHP y lo configurado en la app.
     * No se modifica php.ini; UPLOAD_MAX_BYTES solo puede acotar.
     */
    private function maximoBytes(): int
    {
        $limites = [
            $this->bytesDeIni((string) ini_get('upload_max_filesize')),
            $this->bytesDeIni((string) ini_get('post_max_size')),
        ];

        $configurado = (int) $this->config->get('app.uploads.max_bytes', 0);

        if ($configurado > 0) {
            $limites[] = $configurado;
        }

        $limites = array_filter($limites, static fn (int $bytes): bool => $bytes > 0);

        return $limites === [] ? 0 : (int) min($limites);
    }

    private function bytesDeIni(string $valor): int
    {
        $valor = trim($valor);

        if ($valor === '' || $valor === '-1') {
            return 0;
        }

        $numero = (int) $valor;
        $multiplicador = match (strtoupper(substr($valor, -1))) {
            'G' => 1024 * 1024 * 1024,
            'M' => 1024 * 1024,
            'K' => 1024,
            default => 1,
        };

        return $numero * $multiplicador;
    }

    private function maximoImagenes(): int
    {
        $maximo = (int) $this->config->get('app.uploads.max_images', 12);

        return max(1, min($maximo, 100));
    }

    private function formatearBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }

    private function generarNombre(int $propiedadId, string $extension): string
    {
        do {
            $nombre = sprintf('prop-%d-%s.%s', $propiedadId, bin2hex(random_bytes(16)), $extension);
        } while ($this->imagenes->existeEnPropiedad($propiedadId, $nombre) || file_exists($this->rutaDe($nombre)));

        return $nombre;
    }

    private function sanearNombreOriginal(string $nombre): string
    {
        $nombre = basename(str_replace('\\', '/', $nombre));
        $nombre = preg_replace('/[\x00-\x1F\x7F]/u', '', $nombre) ?? '';
        $nombre = trim($nombre);

        return mb_substr($nombre === '' ? 'imagen' : $nombre, 0, 255);
    }

    /**
     * @param  array<string, mixed>  $file
     */
    private function guardar(array $file, string $nombreArchivo): void
    {
        $carpeta = $this->carpetaDeSubidas();

        if (!is_dir($carpeta) && !@mkdir($carpeta, 0775, true) && !is_dir($carpeta)) {
            throw new RuntimeException("No se pudo crear la carpeta de subidas: {$carpeta}");
        }

        if (!@move_uploaded_file((string) $file['tmp_name'], $this->rutaDe($nombreArchivo))) {
            throw new RuntimeException("No se pudo guardar la imagen en {$carpeta}.");
        }

        @chmod($this->rutaDe($nombreArchivo), 0644);
    }

    private function borrarArchivo(string $nombreArchivo): void
    {
        if (basename($nombreArchivo) !== $nombreArchivo || $nombreArchivo === '') {
            return;
        }

        if (is_file($this->rutaDe($nombreArchivo))) {
            @unlink($this->rutaDe($nombreArchivo));
        }
    }

    private function carpetaDeSubidas(): string
    {
        return rtrim((string) $this->config->get('app.uploads.path', ''), '/\\');
    }

    private function rutaDe(string $nombreArchivo): string
    {
        return $this->carpetaDeSubidas() . DIRECTORY_SEPARATOR . $nombreArchivo;
    }

    private function rutaPublica(): string
    {
        return rtrim((string) $this->config->get('app.uploads.public_path', '/uploads/propiedades'), '/');
    }

    /**
     * Base publica. Sale de APP_URL; si esta vacio se arma con el host de la
     * peticion. Nunca se deja localhost fijo en el codigo.
     */
    private function baseUrl(): string
    {
        $configurada = rtrim((string) $this->config->get('app.url', ''), '/');

        if ($configurada !== '') {
            return $configurada;
        }

        $esquema = ((string) ($_SERVER['HTTPS'] ?? '')) === 'on' ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

        return $esquema . '://' . $host;
    }

    /**
     * @return array<int, int>
     */
    private function validarIdsDeOrden(mixed $ids): array
    {
        if (!is_array($ids) || $ids === []) {
            throw new ValidationException('No se pudieron ordenar las imágenes.', [
                'imagenes' => 'Enviá la lista de ids de imagen en el orden nuevo, por ejemplo [3,1,2].',
            ]);
        }

        $limpios = [];

        foreach ($ids as $id) {
            $valor = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

            if ($valor === false) {
                throw new ValidationException('No se pudieron ordenar las imágenes.', [
                    'imagenes' => 'La lista debe contener solo ids numéricos mayores a cero.',
                ]);
            }

            $limpios[] = $valor;
        }

        if (count(array_unique($limpios)) !== count($limpios)) {
            throw new ValidationException('No se pudieron ordenar las imágenes.', [
                'imagenes' => 'La lista no puede repetir el mismo id de imagen.',
            ]);
        }

        return $limpios;
    }
}